<?php

namespace Modules\Permission;

use Authentication\Perm_Auth;
use Exception;
use Http\Controller;
use Database\Database;
use Modules\Permission\Service\PermissionService;
use Modules\Permission\Service\PermissionValidator;

class Permission extends Controller
{
    public string $module = 'Permission';
    private PermissionService $service;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Permission_Model();
        $this->service = new PermissionService(new Database(), $this->model);
    }

    public function index()
    {
        try {
            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('view_permissions')) {
                $this->permissionDenied();
            }

            $this->view()->permission_details = $this->model->loadData();
            $this->render('index');

        } catch (Exception $e) {
            $this->service->logError('index', $e);
            $this->render('templates/error');
        }
    }

    public function create()
    {
        try {
            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('add_permission')) {
                $this->permissionDenied();
            }

            $this->view()->class = get_class($this->model);
            $this->view()->title = 'New ' . $this->model->getTitle();
            $this->view()->fields = $this->model->getClassFields($this->model->getTable());
            $this->view()->formHiddenFields = $this->model->getFormHiddenFields();
            $this->render('create');

        } catch (Exception $e) {
            $this->service->logError('create', $e);
            $this->render('templates/error');
        }
    }

    /** GET user groups (AJAX) */
    public function getUserGroups()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateDomainAndRowValue($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $result = $this->service->getUserGroups($payload['domain'], $payload['id']);
            return $this->responseSuccess(200, 'User groups loaded', ['data' => $result]);

        } catch (Exception $e) {
            $this->service->logError('getUserGroups', $e);
            return $this->responseError('Failed to load user groups', 500);
        }
    }

    /** POST assign user groups */
    public function postUserGroup()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateUserGroupPayload($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $result = $this->service->saveUserGroups($payload['id'], $payload['new_data']);
            
            if (($result['status'] ?? 200) >= 400) {
                return $this->responseError($result['title'] ?? 'Error', $result['status'] ?? 500);
            }

            return $this->responseSuccess(200, $result['title'] ?? 'User groups saved');

        } catch (Exception $e) {
            $this->service->logError('postUserGroup', $e);
            return $this->responseError('An error occurred while saving groups', 500);
        }
    }

    /** GET group permissions */
    public function getGroupPermissions()
    {
        try {
            $body = PermissionValidator::readJsonBody();
            $groupId = is_array($body) ? ($body['group_id'] ?? $body['id'] ?? null) : $body;

            $errors = PermissionValidator::validateIntId($groupId, 'group_id');
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('view_group_permissions')) {
                $this->permissionDenied();
            }

            $result = $this->service->getGroupPermissions((int)$groupId);
            return $this->responseSuccess(200, 'Group permissions loaded', ['data' => $result]);

        } catch (Exception $e) {
            $this->service->logError('getGroupPermissions', $e);
            return $this->responseError('Failed to load group permissions', 500);
        }
    }

    /** POST assign group permissions */
    public function postGroupPermission()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateGroupPermissionPayload($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $result = $this->service->saveGroupPermissions((int)$payload['id'], $payload['new_data']);
            
            if (($result['status'] ?? 200) >= 400) {
                return $this->responseError($result['title'] ?? 'Error', $result['status'] ?? 500);
            }

            return $this->responseSuccess(200, $result['title'] ?? 'Group permissions updated');

        } catch (Exception $e) {
            $this->service->logError('postGroupPermission', $e);
            return $this->responseError('Failed to update group permissions', 500);
        }
    }

    /** GET user permissions */
    public function getUserPermissions()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateDomainAndRowValue($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('view_user_permissions')) {
                $this->permissionDenied();
            }

            $result = $this->service->getUserPermissions($payload['domain'], $payload['id']);
            return $this->responseSuccess(200, 'User permissions loaded', ['data' => $result]);

        } catch (Exception $e) {
            $this->service->logError('getUserPermissions', $e);
            return $this->responseError('Failed to load user permissions', 500);
        }
    }

    /** POST assign user permissions */
    public function postUserPermission()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateUserPermissionPayload($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('assign_user_permissions')) {
                $this->permissionDenied();
            }

            $result = $this->service->saveUserPermissions(
                $payload['domain'],
                $payload['id'],
                $payload['new_data']
            );
            
            if (($result['status'] ?? 200) >= 400) {
                return $this->responseError($result['title'] ?? 'Error', $result['status'] ?? 500);
            }

            return $this->responseSuccess(200, $result['title'] ?? 'User permissions updated');

        } catch (Exception $e) {
            $this->service->logError('postUserPermission', $e);
            return $this->responseError('Failed to update user permissions', 500);
        }
    }

    public function saveGroup()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateCreateGroupPayload($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('add_group')) {
                $this->permissionDenied();
            }

            $result = $this->service->createGroup($payload['name'], (int)($_SESSION['user_id'] ?? 0));
            
            if (($result['status'] ?? 200) >= 400) {
                return $this->responseError($result['title'] ?? 'Error', $result['status'] ?? 500);
            }

            return $this->responseSuccess(200, $result['title'] ?? 'Group saved');

        } catch (Exception $e) {
            $this->service->logError('saveGroup', $e);
            return $this->responseError('Failed to save group', 500);
        }
    }

    public function savePermission()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateCreatePermissionPayload($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $result = $this->service->createPermission(
                $payload['display_name'],
                $payload['name'],
                (int)$payload['section_id']
            );
            
            if (($result['status'] ?? 200) >= 400) {
                return $this->responseError($result['title'] ?? 'Error', $result['status'] ?? 500);
            }

            return $this->responseSuccess(200, $result['title'] ?? 'Permission saved');

        } catch (Exception $e) {
            $this->service->logError('savePermission', $e);
            return $this->responseError('Failed to save permission', 500);
        }
    }

    public function saveSection()
    {
        try {
            $payload = PermissionValidator::readJsonBody();
            $errors = PermissionValidator::validateCreateSectionPayload($payload);
            if ($errors) {
                return $this->responseError('Validation error', 422, ['errors' => $errors]);
            }

            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('add_section')) {
                $this->permissionDenied();
            }

            $result = $this->service->createSection($payload['txt_name']);
            
            if (($result['status'] ?? 200) >= 400) {
                return $this->responseError($result['title'] ?? 'Error', $result['status'] ?? 500);
            }

            return $this->responseSuccess(200, $result['title'] ?? 'Section saved');

        } catch (Exception $e) {
            $this->service->logError('saveSection', $e);
            return $this->responseError('Failed to save section', 500);
        }
    }

    /** AJAX: Load Permission Management bootstrap data */
    public function loadData()
    {
        try {
            $perm = Perm_Auth::getPermissions();
            if (!$perm->verifyPermission('view_permissions')) {
                $this->permissionDenied();
            }

            $data = $this->model->loadData();
            return $this->responseSuccess(200, 'Permission data loaded', ['data' => $data]);

        } catch (Exception $e) {
            $this->service->logError('loadData', $e);
            return $this->responseError('Failed to load permission data', 500);
        }
    }
}
