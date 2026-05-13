<?php

namespace Modules\User;

use Exception;
use Library\\Controller;
use Library\\DbErrorHandler;
use Library\\EmailValidator;
use Library\\Hash;
use Library\\iMartSMS;
use Library\\Log;
use Library\\LogSanitizer;
use Library\\MXMailGun;
use Library\\MXPhoneNumber;
use Library\\MXSms;
use Library\\NameValidator;
use Library\\RBAC;
use Library\\SmsSender;
use Library\\Perm_Auth;
use Library\\Session;

/**
 * Description of User
 * MX file for system user
 *
 * @author abdirahmanhassan
 */
class User extends Controller
{

    /**
     * @var User_Model
     */
    public $model;

    public function __construct()
    {
        $this->model = new User_Model();
        parent::__construct();
    }

    public function index(): void
    {
        $permission = 'view_users';
        $data = $this->model->getAllRecords($this->model->getTable(true));
        $title = "All " . $this->model->getTitle();
        $this->pageFilter($title, $data, $permission);
    }

    public function active(): void
    {
        $permission = 'view_users';
        $data = $this->model->getFilteredRecords($this->model->getTable(true), ['opt_mx_status_id'], [filter_var(ACTIVE, FILTER_SANITIZE_NUMBER_INT)]);
        $title = "Active " . $this->model->getTitle();
        $this->pageFilter($title, $data, $permission);
    }

    public function inactive(): void
    {
        $permission = 'view_users';
        $data = $this->model->getFilteredRecords($this->model->getTable(true), ['opt_mx_status_id'], [filter_var(INACTIVE, FILTER_SANITIZE_NUMBER_INT)]);
        $title = "Inactive " . $this->model->getTitle();
        $this->pageFilter($title, $data, $permission);
    }

    public function profile($id): void
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = 'view_users';
        $extra_data = [];
        parent::getProfile($record_id, $permission, $extra_data);
    }

    public function password(): void
    {
        $this->view->title = "All " . $this->model->getTitle();
        $this->view->buttons = $this->model->getControls();
        $this->view->class = getClassName(get_class($this->model));
        $this->view->allRecords = $this->model->getFilteredRecords($this->model->getTable(), ['opt_mx_status_id'], [ACTIVE])[0];
        $this->view->headings = $this->model->getClassFields($this->model->getTable())['properties'];
        $this->view->hidden = $this->model->getHiddenFields();
        $this->view->actions = $this->model->getActions();
        $this->view->table = $this->model->getTable();
        $this->render('change_password');
    }

    public function associated_records($id, $caller): void
    {
        $call_mappers = [];
        $permission = 'view_users';
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $valid_caller = filter_var($caller, FILTER_SANITIZE_SPECIAL_CHARS);
        parent::getAssociatedRecords($record_id, $valid_caller, $call_mappers, $permission);
    }

    public function save(): void
    {
        $filters      = $this->model->getInputFilters();
        $posted_data  = json_decode(file_get_contents("php://input"), true) ?? [];
        $validated    = filter_var_array($posted_data, $filters);

        // ---------------------------
        // Session + Admin Security
        // ---------------------------
        if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
            response([
                'code'    => 401,
                'status'  => false,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        $clientIp        = $_SERVER['REMOTE_ADDR']        ?? '0.0.0.0';


        $currentLoginId    = (int) filter_var($_SESSION['id'], FILTER_SANITIZE_NUMBER_INT);
        $currentGroupId    = (int) $_SESSION['role']; // or fetch from DB if more reliable
        $requestedGroupId  = isset($validated['group_id']) ? (int) $validated['group_id'] : 0;

        // ---------------------------
        // RBAC: Role assignment control
        // ---------------------------
        $roleAssignmentAllowed = RBAC::canAssignGroup($currentGroupId, $requestedGroupId);

        // if (!$roleAssignmentAllowed) {
        //     Log::sysLog(json_encode([
        //         'event'         => 'unauthorized_role_assignment_attempt',
        //         'by_user_id'    => $currentLoginId,
        //         'by_group_id'   => $currentGroupId,
        //         'target_group'  => $requestedGroupId,
        //         'ip'            => $clientIp
        //     ]));

        //     response([
        //         'code'    => 100,
        //         'status'  => false,
        //         'message' => 'You are not allowed to create users with this role.'
        //     ]);
        //     return;
        // }

        // ---------------------------
        // Start DB transaction
        // ---------------------------
        $this->model->db->beginTransaction();

        // ---------------------------
        // Password generation (strong)
        // ---------------------------
        $passwordResult = $this->model->generate(12);

        if (!$passwordResult['status']) {
            Log::sysLog(json_encode([
                'event'       => 'password_generation_failed',
                'by_user_id'  => $currentLoginId,
                'reason'      => 'Length or generator error',
            ]));

            $this->model->db->rollBack();

            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'Failed to generate a secure password.'
            ]);
            return;
        }

        $password = $passwordResult['password'];

        // ---------------------------
        // Email validation (strong)
        // ---------------------------
        $email = isset($posted_data['email'])
            ? filter_var($posted_data['email'], FILTER_SANITIZE_EMAIL)
            : '';

        if (empty($email)) {
            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'Email is required.'
            ]);
            return;
        }

        if (!EmailValidator::isValid($email, true)) { // MX check enabled
            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'Invalid or unauthorized email address.'
            ]);
            return;
        }

        // ---------------------------
        // Name validation (official names only)
        // ---------------------------
        $name = trim($validated['txt_name'] ?? '');

        if (!NameValidator::isValid($name)) {
            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'Invalid name. Only official names are allowed.'
            ]);
            return;
        }

        // Additional dangerous char blocking (defense-in-depth)
        if (preg_match('/[<>"\'`{}();]/u', $name)) {
            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'Invalid characters detected in name.'
            ]);
            return;
        }

        // ---------------------------
        // Mobile validation + normalization
        // ---------------------------
        $rawMobile = $posted_data['txt_mobile'] ?? '';
        $mobile = MXPhoneNumber::normalizeTz($rawMobile);
        if ($mobile === null) {
            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'Invalid mobile number format.'
            ]);
            return;
        }
        // ---------------------------
        // Uniqueness checks
        // ---------------------------
        $mobile_exist = $this->model->checkMobileIfExists($rawMobile);
        if ($mobile_exist) {
            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'User Phone already exists.'
            ]);
            return;
        }


        // ---------------------------
        // Uniqueness checks
        // ---------------------------
        $email_exist = $this->model->checkEmailIfExists($email);

        if ($email_exist) {
            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'User email already exists.'
            ]);
            return;
        }

        $userId = 'US' . $this->model->generateRandomString(6);

        try {
            // ---------------------------
            // Prepare main user data
            // ---------------------------
            if (isset($validated['added_by'])) {
                $data = [
                    'id'               => $validated['id'] ?? $userId,
                    'txt_name'         => $name,
                    'txt_added_by'     => $currentLoginId,
                    'dat_added_date'   => date('Y-m-d', strtotime($validated['date'] ?? 'now')),
                    'txt_attended_by'  => filter_var($_SESSION['user_id'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'dat_attended_date'=> date('Y-m-d H:i:s'),
                    'opt_mx_status_id' => ACTIVE,
                    'int_token'        => time(),
                    'txt_pin'          => Hash::create(HASH_ALGO, $password, PASS_SALT),
                    'txt_mobile'       => $mobile,
                    'email'            => $email,
                ];
            } else {
                $data = [
                    'id'               => $userId,
                    'txt_name'         => $name,
                    'txt_added_by'     => $currentLoginId,
                    'dat_added_date'   => date('Y-m-d'),
                    'opt_mx_status_id' => ACTIVE,
                    'int_token'        => time(),
                    'txt_mobile'       => $mobile,
                    'email'            => $email,
                    'txt_pin'          => Hash::create(HASH_ALGO, $password, PASS_SALT),
                    'txt_row_value'    => $this->model->getGUID()
                ];
            }

            $result = $this->model->create($data, $this->model->getTable());

            if ($result) {
                // Login Data
                $login_credential = [
                    'user_id'          => $userId,
                    'txt_username'     => $email,
                    'password'         => Hash::create(HASH_ALGO, $password, PASS_SALT),
                    'opt_mx_status_id' => ACTIVE,
                    'txt_domain'       => $this->model->getTable(),
                    'txt_row_value'    => $this->model->getGUID()
                ];

                $this->model->create($login_credential, 'mx_login_credential');

                $login_id = $this->model->getlastInsertId(
                    'mx_login_credential',
                    $login_credential['txt_row_value']
                );

                $group_data = [
                    'opt_mx_login_credential_id' => $login_id,
                    'opt_mx_group_id'            => $requestedGroupId,
                    'txt_row_value'              => $this->model->getGUID()
                ];

                $this->model->create($group_data, 'mx_login_credential_group');

            }

            $this->model->db->commit();

            // ⚠ Long-term: Replace password sending with activation link
            $sms = new MXSms();
            $sms->sendTemplateSMS(
                MX_USER_CREATED_REASON,
                $mobile,
                $userId,
                null,
                null,
                ['_name', '_id', '_password', '_link'],
                [$name, $email, $password, URL]
            );

            $mail = new MXMailGun();
            $mail->sendEmail(
                MX_USER_CREATED_REASON,
                $email,
                null,
                ['_name', '_id', '_password', '_link'],
                [$name, $email, $password, URL]
            );

            $log = new Log();
            $log->emailErr("New User created: " . LogSanitizer::maskEmail($email));

            response([
                'code'    => 200,
                'status'  => true,
                'message' => 'User created successfully'
            ]);

        } catch (\Throwable $ex) {
            $this->model->db->rollBack();

            Log::sysLog(json_encode([
                'event'   => 'user_create_error',
                'error'   => LogSanitizer::sanitizeString($ex->getMessage()),
            ]));

            response([
                'code'    => 100,
                'status'  => false,
                'message' => 'An error occurred. Failed to create user.'
            ]);
        }
    }

    public function create(): void
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('add_user')) { //checking permission
            $this->view->class = getClassName(get_class($this->model));
            $this->view->title = 'New ' . $this->model->getTitle();
            $this->view->data = ['has_extra' => 0];
            $this->view->dropdowns = $this->model->getFormDropdowns();
            $this->view->disabled = [];
            $this->render('create');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function edit($id): void
    {
        // -------------------------
        // 1. Session integrity check
        // -------------------------
        if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
            $this->view->subtitle = "Unauthorized access.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $clientIp        = $_SERVER['REMOTE_ADDR']        ?? '0.0.0.0';


        // -------------------------
        // 2. Clean the incoming ID
        // -------------------------
        $posted_id = trim(filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS));

        if (!$posted_id || preg_match('/[^\w\-]/', $posted_id)) {

            $this->view->subtitle = "Invalid user reference.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // -------------------------
        // 3. Permission check
        // -------------------------
        $permission = Perm_Auth::getPermissions();

        if (!$permission->verifyPermission('edit_user')) {
            $this->_permissionDenied(__METHOD__);
            return;
        }

        // Convert public row_value → internal numerical ID
        $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);

        if ($returned_id <= 0) {
            $this->view->subtitle = "User not found.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // -------------------------
        // 4. Fetch user details safely
        // -------------------------
        $data = $this->model->getRecord($returned_id, $this->model->getTable());

        if (!$data) {
            $this->view->subtitle = "User not found.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // -------------------------
        // 5. Fetch user login credential → determine his group/role
        // -------------------------
        $user_login = $this->model->getRecordByFieldName(
            'mx_login_credential',
            'user_id',
            filter_var($returned_id, FILTER_SANITIZE_SPECIAL_CHARS)
        );

        if (!$user_login) {
            $this->view->subtitle = "User login record missing.";
            $this->renderFull('views/templates/not_found');
            return;

        }

        // Get group role
        $group_data = $this->model->db->select(
            'SELECT TOP 1 * 
                FROM mx_login_credential_group 
                WHERE opt_mx_login_credential_id = :id',
            [':id' => $user_login['id']]
        );

        $targetGroupId = $group_data ? ($group_data[0]['opt_mx_group_id'] ?? null) : null;
        $currentGroupId = (int) $_SESSION['role'];
        $currentUserId  = (int) $_SESSION['id'];

        // -------------------------
        // 6. Enforce RBAC edit restrictions
        // -------------------------
        if (!RBAC::canEditGroup($currentGroupId, $targetGroupId)) {
            Log::sysLog(json_encode([
                'event'             => 'unauthorized_user_edit_attempt',
                'current_user_id'   => $currentUserId,
                'current_role'      => $currentGroupId,
                'target_group_id'   => $targetGroupId,
                'target_record'     => $posted_id,
                'ip'                => $clientIp
            ]));

            $this->view->subtitle = "You are not allowed to edit this user.";
            $this->renderFull('views/templates/not_found');

            return;
        }
        $mobile = $data['txt_mobile'];

        if (preg_match('/^255(6|7)\d{8}$/', $mobile)) {
            // Convert 2557XXXXXXX → 07XXXXXXXXX
            $mobile = '0' . substr($mobile, 3);
        }
        // -------------------------
        // 7. Build safe data for the view
        // -------------------------
        $view_data = [
            'id'             => $posted_id,
            'txt_name'       => htmlspecialchars($data['txt_name'], ENT_QUOTES, 'UTF-8'),
            'email'          => htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8'),
            'txt_mobile'     => htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8'),
            'opt_mx_group_id'=> $targetGroupId,
            'has_extra'      => 1
        ];

        // -------------------------
        // 8. Render
        // -------------------------
        $this->view->title     = 'Update ' . $this->model->getTitle();
        $this->view->data      = $view_data;
        $this->view->dropdowns = $this->model->getFormDropdowns();

        $this->render('edit');
    }

    public function post_edit(): void
    {
        $posted_data = json_decode(file_get_contents("php://input"), true) ?? [];
        $filters = $this->model->getInputFilters();

        // ===========================
        // 1. Basic Session + RBAC
        // ===========================
        if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
            response(['code' => 100, 'status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $currentUserId  = (int) $_SESSION['id'];
        $currentGroupId = (int) $_SESSION['role'];
        $clientIp       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // ===========================
        // 2. Clean incoming ID
        // ===========================
        $row_id = filter_var($posted_data['id'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$row_id || preg_match('/[^\w\-]/', $row_id)) {
            response(['code' => 400, 'status' => false, 'message' => 'Invalid user reference.']);
            return;
        }

        // Convert public row_value → internal ID
        $id = $this->model->getRecordIdByRowValue($this->model->getTable(), $row_id);
        if ($id <= 0) {
            response(['code' => 404, 'status' => false, 'message' => 'User not found.']);
            return;
        }

        // ===========================
        // 3. Fetch target user
        // ===========================
        $user = $this->model->db->select(
            "SELECT TOP 1 * FROM mx_user WHERE id = :uid",
            [':uid' => $id]
        );

        if (!$user) {
            response(['code' => 404, 'status' => false, 'message' => 'User record missing.']);
            return;
        }

        // ===========================
        // 4. RBAC: Can current user edit this user's role?
        // ===========================
        $user_login = $this->model->db->select(
            "SELECT TOP 1 * FROM mx_login_credential WHERE user_id = :uid",
            [':uid' => $id]
        );

        if (!$user_login) {
            response(['code' => 500, 'status' => false, 'message' => 'Login credentials missing.']);
            return;
        }

        $group_data = $this->model->db->select(
            "SELECT TOP 1 * FROM mx_login_credential_group WHERE opt_mx_login_credential_id = :id",
            [':id' => $user_login[0]['id']]
        );

        $targetGroupId = $group_data ? $group_data[0]['opt_mx_group_id'] : null;

        if (!RBAC::canEditGroup($currentGroupId, (int) $targetGroupId)) {
            Log::sysLog(json_encode([
                'event' => 'unauthorized_user_edit_attempt',
                'current_user_id' => $currentUserId,
                'target_id' => $id,
                'current_role' => $currentGroupId,
                'target_role'  => $targetGroupId,
                'ip' => $clientIp,
            ]));
            response(['code' => 403, 'status' => false, 'message' => 'You are not allowed to edit this user.']);
            return;
        }

        // ===========================
        // 5. Validate Fields
        // ===========================
        $posted_name = trim($posted_data['txt_name'] ?? '');
        $posted_email = trim($posted_data['email'] ?? '');
        $posted_mobile = trim($posted_data['txt_mobile'] ?? '');

        // Name validation
        if (!NameValidator::isValid($posted_name)) {
            response(['code' => 400, 'status' => false, 'message' => 'Invalid name.']);
            return;
        }

        // Email validation
        if (!EmailValidator::isValid($posted_email, true)) {
            response(['code' => 400, 'status' => false, 'message' => 'Invalid or unauthorized email.']);
            return;
        }

        // Mobile normalization
        $normalizedMobile = MXPhoneNumber::normalizeTz($posted_mobile);
        if ($normalizedMobile === null) {
            response(['code' => 400, 'status' => false, 'message' => 'Invalid mobile number format.']);
            return;
        }

        // ===========================
        // 6. Duplicate Checks
        // ===========================
        if ($this->model->checkEmailIfExists($posted_email, $id)) {
            response(['code' => 409, 'status' => false, 'message' => 'Email already exists.']);
            return;
        }

        if ($this->model->checkMobileIfExists($normalizedMobile)) {
            response(['code' => 409, 'status' => false, 'message' => 'Mobile number already exists.']);
            return;
        }

        // ===========================
        // 7. Update Data
        // ===========================
        try {
            $this->model->db->beginTransaction();

            // Update mx_user
            $data_user = [
                'txt_name'   => $posted_name,
                'txt_mobile' => $normalizedMobile,
                'email'      => $posted_email,
            ];

            $this->model->db->update('mx_user', $data_user, $id);

            // Update login username
            $this->model->db->update('mx_login_credential', [
                'txt_username' => $posted_email
            ], $user_login[0]['id']);

            $this->model->db->commit();

            response(['code' => 200, 'status' => true, 'message' => 'User updated successfully']);

        } catch (\Throwable $e) {

            $this->model->db->rollBack();

            DbErrorHandler::logException($e, "USER_UPDATE", [
                'id' => $id,
                'email' => $posted_email
            ]);

            response(['code' => 100, 'status' => false, 'message' => 'Failed to update user.']);
        }
    }

    public function update(): void
    {
        try {
            $filters = $this->model->getInputFilters();
            $posted_data = json_decode(file_get_contents("php://input"), true);
            $validated_data = filter_var_array($posted_data, $filters);
            $id = $validated_data['id'];
            unset($validated_data['id']);
            $result = $this->model->update($validated_data, $this->model->getTable(), $id);

            echo json_encode($result);
        } catch (Exception $ex) {
            echo json_encode(500);
        }
    }

    public function suspend($id): void
    {
        // -----------------------------------------
        // 1. Session validation
        // -----------------------------------------
        if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
            $this->view->subtitle = "Unauthorized access.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $currentUserId  = (int) $_SESSION['id'];
        $currentGroupId = (int) $_SESSION['role'];

        // -----------------------------------------
        // 2. Validate & sanitize ID
        // -----------------------------------------
        $posted_id = trim(filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS));

        if (!$posted_id || preg_match('/[^\w\-]/', $posted_id)) {
            $this->view->subtitle = "Invalid user reference.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // Convert row_value → internal numeric ID
        $targetUserId = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);

        if ($targetUserId <= 0) {
            $this->view->subtitle = "User not found.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // -----------------------------------------
        // 3. Prevent self suspension
        // -----------------------------------------
        if ($targetUserId === $currentUserId) {
            $this->view->subtitle = "You cannot suspend your own account.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // -----------------------------------------
        // 4. Fetch target user's role
        // -----------------------------------------
        $login_credential = $this->model->db->select(
            "SELECT TOP 1 id FROM mx_login_credential WHERE user_id = :uid",
            [':uid' => $targetUserId]
        );

        if (!$login_credential) {
            $this->view->subtitle = "Target user login record missing.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $group_data = $this->model->db->select(
            "SELECT TOP 1 opt_mx_group_id 
         FROM mx_login_credential_group 
         WHERE opt_mx_login_credential_id = :id",
            [':id' => $login_credential[0]['id']]
        );

        $targetGroupId = $group_data ? (int) $group_data[0]['opt_mx_group_id'] : null;

        if ($targetGroupId === null) {
            $this->view->subtitle = "Target user has no assigned role.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // -----------------------------------------
        // 5. Check permission + RBAC restrictions
        // -----------------------------------------
        $permission = Perm_Auth::getPermissions();

        if (!$permission->verifyPermission('suspend_user')) {
            $this->_permissionDenied(__METHOD__);
            return;
        }

        // Advanced RBAC (SU can suspend anyone, Admin cannot suspend SU/Admin/Dev)
        if (!RBAC::canSuspendGroup($currentGroupId, $targetGroupId)) {

            Log::sysLog(json_encode([
                'event'          => 'unauthorized_suspend_page_access',
                'by_user_id'     => $currentUserId,
                'target_user_id' => $targetUserId,
                'by_group'       => $currentGroupId,
                'target_group'   => $targetGroupId,
                'ip'             => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]));

            $this->view->subtitle = "You are not allowed to suspend this user.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // -----------------------------------------
        // 6. Safe Render suspend confirmation page
        // -----------------------------------------
        $this->view->title      = 'Suspend User';
        $this->view->subtitle   = "User Suspension";
        $this->view->controller = "User";
        $this->view->action     = "post_suspend";
        $this->view->name       = "";
        $this->view->data       = ['id' => $posted_id];

        $this->renderFull('views/templates/suspend');
    }

    public function post_suspend(): void
    {
        $posted_data = json_decode(file_get_contents("php://input"), true) ?? [];

        // ------------------------------------
        // 1. Session validation
        // ------------------------------------
        if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
            response(['code' => 100, 'status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $currentUserId  = (int) $_SESSION['id'];
        $currentGroupId = (int) $_SESSION['role'];
        $clientIp       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // ------------------------------------
        // 2. Validate incoming ID
        // ------------------------------------
        $posted_id = filter_var($posted_data['id'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$posted_id || preg_match('/[^\w\-]/', $posted_id)) {
            response(['code' => 400, 'status' => false, 'message' => 'Invalid user reference.']);
            return;
        }

        // Convert row_value to actual DB user ID
        $user_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);

        if ($user_id <= 0) {
            response(['code' => 404, 'status' => false, 'message' => 'User not found.']);
            return;
        }

        // ------------------------------------
        // 3. Prevent suspending yourself
        // ------------------------------------
        if ($currentUserId === (int) $user_id) {
            response(['code' => 403, 'status' => false, 'message' => "You cannot suspend your own account."]);
            return;
        }

        // ------------------------------------
        // 4. Fetch target user's role
        // ------------------------------------
        $target_login = $this->model->db->select(
            "SELECT TOP 1 id FROM mx_login_credential WHERE user_id = :uid",
            [':uid' => $user_id]
        );

        if (!$target_login) {
            response(['code' => 500, 'status' => false, 'message' => 'Target login credential missing.']);
            return;
        }

        $group_data = $this->model->db->select(
            "SELECT TOP 1 opt_mx_group_id 
         FROM mx_login_credential_group 
         WHERE opt_mx_login_credential_id = :id",
            [':id' => $target_login[0]['id']]
        );

        $targetGroupId = $group_data ? (int) $group_data[0]['opt_mx_group_id'] : null;

        // ------------------------------------
        // 5. RBAC check — can you suspend this role?
        // ------------------------------------
        if (!RBAC::canSuspendGroup($currentGroupId, $targetGroupId)) {

            Log::sysLog(json_encode([
                'event' => 'unauthorized_suspend_attempt',
                'by_user_id' => $currentUserId,
                'target_user_id' => $user_id,
                'by_group' => $currentGroupId,
                'target_group' => $targetGroupId,
                'ip' => $clientIp,
            ]));

            response(['code' => 403, 'status' => false, 'message' => "You are not allowed to suspend this user."]);
            return;
        }

        // ------------------------------------
        // 6. Perform the suspension
        // ------------------------------------
        try {
            $this->model->db->beginTransaction();

            // Suspend main user
            $this->model->db->update('mx_user', [
                'opt_mx_status_id' => INACTIVE
            ], $user_id);

            // Suspend login credential
            $this->model->db->update('mx_login_credential', [
                'opt_mx_status_id' => INACTIVE
            ], $target_login[0]['id']);

            $this->model->db->commit();

            // ------------------------------------
            // 7. Secure audit log
            // ------------------------------------
            Log::sysLog(json_encode([
                'event'            => 'user_suspended',
                'by_user_id'       => $currentUserId,
                'target_user_id'   => $user_id,
                'target_group_id'  => $targetGroupId,
                'ip'               => $clientIp,
            ]));

            response(['code' => 200, 'status' => true, 'message' => 'User suspended successfully']);

        } catch (\Throwable $ex) {

            $this->model->db->rollBack();

            DbErrorHandler::logException($ex, "USER_SUSPEND", [
                'target_user_id' => $user_id
            ]);

            response(['code' => 100, 'status' => false, 'message' => 'Failed to suspend user.']);
        }
    }

    public function activate($id): void
    {
        // ----------------------------------------------------
        // 1. Session validation
        // ----------------------------------------------------
        if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
            $this->view->subtitle = "Unauthorized access.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $currentUserId  = (int) $_SESSION['id'];
        $currentGroupId = (int) $_SESSION['role'];

        // ----------------------------------------------------
        // 2. Clean / validate incoming ID
        // ----------------------------------------------------
        $posted_id = trim(filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS));

        if (!$posted_id || preg_match('/[^\w\-]/', $posted_id)) {
            $this->view->subtitle = "Invalid user reference.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // Convert row_value → numeric internal ID
        $targetUserId = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);

        if ($targetUserId <= 0) {
            $this->view->subtitle = "User not found.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // ----------------------------------------------------
        // 3. Prevent self-activation to avoid bypass
        // ----------------------------------------------------
        if ($targetUserId === $currentUserId) {
            $this->view->subtitle = "You cannot activate your own account.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // ----------------------------------------------------
        // 4. Fetch target user role
        // ----------------------------------------------------
        $login_credential = $this->model->db->select(
            "SELECT TOP 1 id FROM mx_login_credential WHERE user_id = :uid",
            [':uid' => $targetUserId]
        );

        if (!$login_credential) {
            $this->view->subtitle = "User login credential missing.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $group_data = $this->model->db->select(
            "SELECT TOP 1 opt_mx_group_id
         FROM mx_login_credential_group
         WHERE opt_mx_login_credential_id = :id",
            [':id' => $login_credential[0]['id']]
        );

        $targetGroupId = $group_data ? (int) $group_data[0]['opt_mx_group_id'] : null;

        if ($targetGroupId === null) {
            $this->view->subtitle = "Target user has no assigned role.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // ----------------------------------------------------
        // 5. Permission check
        // ----------------------------------------------------
        $permission = Perm_Auth::getPermissions();
        if (!$permission->verifyPermission('activate_user')) {
            $this->_permissionDenied(__METHOD__);
            return;
        }

        // ----------------------------------------------------
        // 6. RBAC check: Can this user activate this role?
        // ----------------------------------------------------
        if (!RBAC::canActivateGroup($currentGroupId, $targetGroupId)) {

            Log::sysLog(json_encode([
                'event'            => 'unauthorized_user_activation_attempt',
                'by_user_id'       => $currentUserId,
                'target_user_id'   => $targetUserId,
                'by_group'         => $currentGroupId,
                'target_group'     => $targetGroupId,
                'ip'               => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]));

            $this->view->subtitle = "You are not allowed to activate this user.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // ----------------------------------------------------
        // 7. Safe rendering of activation confirmation page
        // ----------------------------------------------------
        $this->view->title      = 'Activate User';
        $this->view->subtitle   = "User Activation";
        $this->view->controller = "User";
        $this->view->action     = "post_activate";
        $this->view->name       = "";
        $this->view->data       = ['id' => $posted_id];

        $this->renderFull('views/templates/activate');
    }

    public function post_activate(): void
    {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $this->model->db->beginTransaction();
        try {
            $posted_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
            $user_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            $this->model->db->update('mx_user', ['opt_mx_status_id' => filter_var(ACTIVE, FILTER_SANITIZE_NUMBER_INT)], $user_id);
            $sql = "UPDATE mx_login_credential SET opt_mx_status_id=:status WHERE user_id=:user AND txt_domain =:domain";
            $stmt = $this->model->db->prepare($sql);
            $stmt->execute([
                ':status' => filter_var(ACTIVE, FILTER_SANITIZE_NUMBER_INT),
                ':user' => $user_id,
                ':domain' => 'mx_user'
            ]);
            $this->model->db->commit();
            response(['code' => 201, 'status' => false, 'message' => 'User activated successfully']);
        } catch (Exception $ex) {
            $this->model->db->rollBack();
            response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to activate user.']);
            echo $ex->getMessage();
        }
    }

    public function reset_password($id): void
    {
        // --------------------------------------------------------
        // 1. Session integrity check
        // --------------------------------------------------------
        if (empty($_SESSION['id']) || empty($_SESSION['role'])) {
            $this->view->subtitle = "Unauthorized access.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $currentUserId  = (int) $_SESSION['id'];
        $currentGroupId = (int) $_SESSION['role'];
        $clientIp       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // --------------------------------------------------------
        // 2. Validate & sanitize incoming ID
        // --------------------------------------------------------
        $posted_id = trim(filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS));

        if (!$posted_id || preg_match('/[^\w\-]/', $posted_id)) {
            $this->view->subtitle = "Invalid user reference.";
            $this->renderFull('views/templates/not_found');
            return;
        }


        // --------------------------------------------------------
        // 4. Fetch login credential to determine role
        // --------------------------------------------------------
        $login_credential = $this->model->db->select(
            "SELECT TOP 1 id 
         FROM mx_login_credential 
         WHERE user_id = :uid",
            [':uid' => $posted_id]
        );

        if (!$login_credential) {
            $this->view->subtitle = "Login credential not found.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $group_data = $this->model->db->select(
            "SELECT TOP 1 opt_mx_group_id 
         FROM mx_login_credential_group 
         WHERE opt_mx_login_credential_id = :id",
            [':id' => $login_credential[0]['id']]
        );

        $targetGroupId = $group_data ? (int) $group_data[0]['opt_mx_group_id'] : null;

        if ($targetGroupId === null) {
            $this->view->subtitle = "Target user role not found.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // --------------------------------------------------------
        // 5. Check user permission
        // --------------------------------------------------------
        $permission = Perm_Auth::getPermissions();
        if (!$permission->verifyPermission('reset_user_password')) {
            $this->_permissionDenied(__METHOD__);
            return;
        }

        // --------------------------------------------------------
        // 6. Enforce RBAC privileges for password reset
        // --------------------------------------------------------
        if (!RBAC::canResetPassword($currentGroupId, $targetGroupId)) {

            Log::sysLog(json_encode([
                'event'            => 'unauthorized_password_reset_page_access',
                'by_user_id'       => $currentUserId,
                'target_user_id'   => $posted_id,
                'by_group'         => $currentGroupId,
                'target_group'     => $targetGroupId,
                'ip'               => $clientIp,
            ]));

            $this->view->subtitle = "You are not allowed to reset password for this user.";
            $this->renderFull('views/templates/not_found');
            return;
        }

        // --------------------------------------------------------
        // 7. Safe rendering of reset-password page
        // --------------------------------------------------------
        $this->view->title      = 'Reset User Password';
        $this->view->subtitle   = "User Password Reset";
        $this->view->controller = "User";
        $this->view->action     = "post_reset_password";
        $this->view->name       = "";
        $this->view->data       = ['id' => $posted_id];

        $this->render('reset_password');
    }

    public function post_reset_password(): void
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object
        $data = $this->model->getRecord(filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS), 'mx_user');

        $otp = $this->model->generateRandomString(8);
        $hashed_password = Hash::create(HASH_ALGO, $otp, PASS_SALT);
        $user = $this->model->getRecord($posted_data['id'], $this->model->getTable());

        $sql = "UPDATE mx_login_credential SET password=:pwd WHERE user_id=:user";

        $stmt = $this->model->db->prepare($sql);
        $obj_data = [':pwd' => $hashed_password, ':user' => filter_var($user['id'], FILTER_SANITIZE_SPECIAL_CHARS)];
        $result = $stmt->execute($obj_data);

        $sms = new MXSms();
        $sms->sendTemplateSMS(
            RESET_USER_PASSWORD,
            $user['txt_mobile'],
            $_SESSION['user_id'],
            null,
            null,
            ['_name', '_password', '_link'],
            [$user['txt_name'], $otp, URL]
        );

        if ($result) {
            response(['code' => 201, 'status' => true, 'message' => 'Password updated successfully']);
        } else {
            response(['status' => false, 'code' => 100, 'message' => 'An error occurred. Failed to reset user password.']);
        }
    }

    public function changePassword(): void
    {
        $userId = $_SESSION['id'] ?? null;

        if ($userId === null) {
            response([
                'code'    => 401,
                'status'  => false,
                'message' => 'You are not authenticated.',
            ]);
            return;
        }

        // Read JSON body
        $posted = json_decode(file_get_contents('php://input'), true) ?? [];

        $oldPassword      = $posted['old_password']      ?? '';
        $newPassword      = $posted['new_password']      ?? '';
        $confirmPassword  = $posted['confirm_password']  ?? '';

        // --- BASIC CHECKS ---
        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            response([
                'code'    => 422,
                'status'  => false,
                'message' => 'Please provide old password, new password, and confirm password.',
            ]);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            response([
                'code'    => 422,
                'status'  => false,
                'message' => 'New password and confirmation do not match.',
            ]);
            return;
        }

        // ---------------------------------------------------
        // 🔐 ADVANCED PASSWORD VALIDATION RULES
        // ---------------------------------------------------
        $errors = [];

        if (strlen($newPassword) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }

        if (strlen($newPassword) > 64) {
            $errors[] = "Password cannot exceed 64 characters.";
        }

        if (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = "Password must contain at least one uppercase letter (A–Z).";
        }

        if (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = "Password must contain at least one lowercase letter (a–z).";
        }

        if (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = "Password must contain at least one digit (0–9).";
        }

        if (!preg_match('/[\W_]/', $newPassword)) {
            $errors[] = "Password must contain at least one special character (!@#$%^&* etc).";
        }

        if (preg_match('/\s/', $newPassword)) {
            $errors[] = "Password cannot contain spaces.";
        }

        if (!empty($errors)) {
            response([
                'code'    => 422,
                'status'  => false,
                'message' => "Password validation failed.",
                'errors'  => $errors,
            ]);
            return;
        }

        // ---------------------------------------------------
        // VERIFY OLD PASSWORD
        // ---------------------------------------------------
        $credentials = $this->model->getRecord($userId, 'mx_login_credential');

        if (!$credentials) {
            response([
                'code'    => 404,
                'status'  => false,
                'message' => 'User credentials not found.',
            ]);
            return;
        }

        $storedHash = $credentials['password'] ?? '';
        $oldHash    = Hash::create(HASH_ALGO, $oldPassword, PASS_SALT);

        if (!hash_equals($storedHash, $oldHash)) {
            response([
                'code'    => 403,
                'status'  => false,
                'message' => 'Your old password is incorrect.',
            ]);
            return;
        }

        // ---------------------------------------------------
        // SAVE NEW PASSWORD
        // ---------------------------------------------------
        $newHash = Hash::create(HASH_ALGO, $newPassword, PASS_SALT);
        $now     = date('Y-m-d H:i:s');

        $update = [
            'password'            => $newHash,
            'dat_date_last_reset' => $now,
        ];

        try {
            $ok = $this->model->db->update('mx_login_credential', $update, $userId);

            if ($ok) {
                response([
                    'code'    => 200,
                    'status'  => true,
                    'message' => 'Password changed successfully.',
                ]);
            } else {
                response([
                    'code'    => 500,
                    'status'  => false,
                    'message' => 'Unable to change password. Please try again later.',
                ]);
            }

        } catch (\Throwable $e) {

            mxException($e);

            response([
                'code'    => 500,
                'status'  => false,
                'message' => 'System error occurred while updating password.',
            ]);
        }
    }
}
