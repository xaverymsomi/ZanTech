<?php

namespace Modules\User;

use Foundation\BaseModuleController;
use Authentication\Perm_Auth;
use Authentication\Session;
use Exception;
use Logging\Log;
use Services\DbErrorHandler;
use Services\EmailValidator;
use Services\Hash;
use Services\LogSanitizer;
use Services\MXMailGun;
use Services\MXPhoneNumber;
use Services\MXSms;
use Services\NameValidator;
use Services\RBAC;

/**
 * User Module
 * Handles system user management with automatic listing and Dual Control.
 */
class User extends BaseModuleController
{
    /**
     * Methods requiring approval from a second person (Maker-Checker).
     */
    protected array $dualControl = ['updateStatus', 'delete'];

    public function profile($id): void
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = 'view_users';
        $extra_data = [];
        parent::getProfile($record_id, $permission, $extra_data);
    }

    public function password(): void
    {
        $this->view()->title = "All " . $this->model->getTitle();
        $this->view()->buttons = $this->model->getControls();
        $this->view()->class = getClassName(get_class($this->model));
        $this->view()->allRecords = $this->model->getFilteredRecords($this->model->getTable(), ['opt_mx_status_id'], [ACTIVE])[0];
        $this->view()->headings = $this->model->getClassFields($this->model->getTable())['properties'];
        $this->view()->hidden = $this->model->getHiddenFields();
        $this->view()->actions = $this->model->getActions();
        $this->view()->table = $this->model->getTable();
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

    public function save()
    {
        // This method is now protected by Dual Control (Maker-Checker).
        // If a user attempts to save, the Oryn kernel will intercept it,
        // create an approval request, and only execute this code after approval.

        $posted_data = $this->request()->all();
        // 1. Gather and sanitize input values
        $id       = isset($posted_data['id']) ? (int)$posted_data['id'] : 0;
        $name     = isset($posted_data['txt_name']) ? trim((string)$posted_data['txt_name']) : '';
        $email    = isset($posted_data['email']) ? trim((string)$posted_data['email']) : '';
        $mobile   = isset($posted_data['txt_mobile']) ? trim((string)$posted_data['txt_mobile']) : '';
        $groupId  = isset($posted_data['group_id']) ? (int)$posted_data['group_id'] : 0;

        // 2. Validate input constraints
//        if ($name === '' || !NameValidator::isValid($name)) {
//            return $this->responseError("Invalid user name.", 400);
//        }

//        if ($email === '' || !EmailValidator::isValid($email)) {
//            return $this->responseError("Invalid email address.", 400);
//        }

        if ($mobile === '') {
            return $this->responseError("Mobile number is required.", 400);
        }

        $normalizedMobile = MXPhoneNumber::normalizeTz($mobile);
        if ($normalizedMobile === null) {
            return $this->responseError("Invalid mobile phone number format.", 400);
        }

        if ($groupId <= 0) {
            return $this->responseError("User group assignment is required.", 400);
        }

        // 3. Enforce RBAC security
        $currentUserGroupId = $_SESSION['opt_mx_group_id'] ?? 3; // Fallback to User group
        if (!RBAC::canEditGroup($currentUserGroupId, $groupId)) {
            return $this->responseError("You are not authorized to assign or manage users in group ID: " . $groupId);
        }

        // 4. Derive secure username
        $username = explode('@', $email)[0];

        // 5. Unify Save/Update transaction
        try {
            if ($id > 0) {
                // UPDATE FLOW
                
                // Uniqueness checks for edit
                $emailConflict = $this->model->db->select("SELECT id FROM mx_user WHERE email = :email AND id != :id", [
                    ':email' => $email,
                    ':id'    => $id
                ]);
                if (!empty($emailConflict)) {
                    return $this->responseError("The email address is already in use by another user.");
                }

                $mobileConflict = $this->model->db->select("SELECT id FROM mx_user WHERE txt_mobile = :mobile AND id != :id", [
                    ':mobile' => $normalizedMobile,
                    ':id'     => $id
                ]);
                if (!empty($mobileConflict)) {
                    return $this->responseError("The mobile number is already registered for another user.");
                }

                \Database\DB::transaction(function ($db) use ($id, $name, $email, $normalizedMobile, $groupId, $username) {
                    // Update user base
                    $this->model->updateRecord([
                        'txt_name'          => $name,
                        'email'             => $email,
                        'txt_mobile'        => $normalizedMobile,
                        'opt_mx_groups_ids' => $groupId
                    ], 'mx_user', $id);

                    // Update login credential
                    $credential = $this->model->db->select("SELECT id FROM mx_login_credential WHERE user_id = :uid", [':uid' => $id]);
                    if (!empty($credential)) {
                        $credId = (int)$credential[0]['id'];
                        $this->model->updateRecord([
                            'txt_username' => $username
                        ], 'mx_login_credential', $credId);

                        // Reset and update login group mapping
                        $this->model->db->statement("DELETE FROM mx_login_credential_group WHERE opt_mx_login_credential_id = :cid", [':cid' => $credId]);
                        $this->model->create([
                            'opt_mx_login_credential_id' => $credId,
                            'opt_mx_group_id'            => $groupId
                        ], 'mx_login_credential_group');
                    }
                });

                Log::sysLog("User ID={$id} updated successfully by Admin ID=" . ($_SESSION['id'] ?? 'System'));
                return $this->responseSuccess(200, "User details updated successfully.");

            } else {
                // CREATE FLOW

                // Uniqueness checks for create
                $emailConflict = $this->model->db->select("SELECT id FROM mx_user WHERE email = :email", [':email' => $email]);
                if (!empty($emailConflict)) {
                    return $this->responseError("A user with this email address already exists.");
                }

                $mobileConflict = $this->model->db->select("SELECT id FROM mx_user WHERE txt_mobile = :mobile", [':mobile' => $normalizedMobile]);
                if (!empty($mobileConflict)) {
                    return $this->responseError("A user with this mobile number already exists.");
                }

                // Auto-generate temp password
                $tempPassword = $this->model->generateRandomString(8);
                $hashedPassword = Hash::make($tempPassword);

                $userId = \Database\DB::transaction(function ($db) use ($name, $email, $hashedPassword, $normalizedMobile, $groupId, $username) {
                    // Create base user record
                    $uid = $this->model->create([
                        'txt_name'          => $name,
                        'email'             => $email,
                        'password'          => $hashedPassword,
                        'txt_mobile'        => $normalizedMobile,
                        'opt_mx_status_id'  => 1, // Active status
                        'opt_mx_groups_ids' => $groupId,
                        'int_added_by'      => $_SESSION['id'] ?? 1
                    ], 'mx_user');

                    // Create centralized login credential
                    $credId = $this->model->create([
                        'user_id'          => $uid,
                        'txt_domain'       => 'mx_user',
                        'txt_username'     => $username,
                        'txt_password'     => $hashedPassword,
                        'opt_mx_status_id' => 1
                    ], 'mx_login_credential');

                    // Assign group role mapping
                    $this->model->create([
                        'opt_mx_login_credential_id' => $credId,
                        'opt_mx_group_id'            => $groupId
                    ], 'mx_login_credential_group');

                    return $uid;
                });

                // Trigger simulated credentials SMS notification
                $sms = new MXSms();
                $sms->sendTemplateSMS(
                    'USER_CREATION_WELCOME',
                    $normalizedMobile,
                    $_SESSION['id'] ?? null,
                    null,
                    null,
                    ['_name', '_username', '_password'],
                    [$name, $username, $tempPassword]
                );

                // Trigger simulated credentials Email notification
                $mailgun = new MXMailGun();
                $mailgun->sendEmail(
                    'USER_CREATION_WELCOME_EMAIL',
                    $email,
                    null,
                    ['_name', '_username', '_password'],
                    [$name, $username, $tempPassword]
                );

                Log::sysLog("User ID={$userId} successfully provisioned by Admin ID=" . ($_SESSION['id'] ?? 'System'));
                return $this->responseSuccess(201, "User saved successfully. Temp Password: {$tempPassword}");
            }

        } catch (Exception $ex) {
            Log::sysLog("User saving error: " . $ex->getMessage());
            return $this->responseError("System error occurred: " . $ex->getMessage());
        }
    }
}
