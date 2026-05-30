<?php

namespace Modules\Menu;

use Authentication\Auth;
use Authentication\Gate;
use Exception;
use Http\Controller;
use Logging\Log;

class Menu extends Controller
{
    public string $module = 'Menu';

    public function __construct()
    {
        parent::__construct();
        $this->model = new Menu_Model();
    }

    public function index()
    {
        try {
            $this->requirePermission('view_menu');
            $this->view()->title = "All " . $this->model->getTitle() . "s";
            $this->view()->dropdowns = $this->model->getFormDropdowns();
            $this->render('home');
        } catch (Exception $e) {
            Log::exception($e, 'MENU_INDEX_ERROR', ['action' => 'index']);
            $this->render('templates/error');
        }
    }

    /**
     * API: used by refresh button
     * GET /Menu/getAllMenus
     */
    public function getAllMenus()
    {
        try {
            $this->requirePermission('view_menu');
            $menus = $this->model->getMenus();
            $dropdowns = $this->model->getFormDropdowns();
            return $this->responseSuccess(200, 'Menus loaded', [
                'data' => $menus,
                'dropdowns' => $dropdowns
            ]);
        } catch (Exception $e) {
            Log::exception($e, 'MENU_GET_ALL_ERROR', ['action' => 'getAllMenus']);
            return $this->responseError('Failed to load menus', 500);
        }
    }

    /**
     * CREATE MENU
     * POST /Menu/saveMenu
     */
    public function saveMenu()
    {
        try {
            $this->requirePermission('add_menu');

            $posted = json_decode(file_get_contents("php://input"), true);
            if (!is_array($posted)) {
                return $this->responseError("Invalid input data", 422);
            }

            $data = $posted['new_data'] ?? [];
            if (!is_array($data)) {
                return $this->responseError("Invalid new_data", 422);
            }

            $name   = trim((string)($data['txt_name'] ?? ''));
            $title  = trim((string)($data['txt_title'] ?? ''));
            $link   = trim((string)($data['txt_link'] ?? '#'));
            $icon   = trim((string)($data['txt_icon'] ?? ''));

            $parent = $data['int_parent'] ?? null;
            $parent = ($parent === '' || $parent === null) ? null : (int)$parent;
            if ($parent !== null && $parent <= 0) $parent = null;

            if ($name === '')  return $this->responseError("Menu name required", 422);
            if ($title === '') return $this->responseError("Menu title required", 422);
            $link = self::normalizeSubmittedLink($link);
            if ($link === null) {
                return $this->responseError("Invalid link", 422);
            }

            $isSub = ($parent !== null);

            $position = isset($data['int_position']) && is_numeric($data['int_position'])
                ? max(1, (int)$data['int_position'])
                : null;

            $this->model->db->beginTransaction();

            if ($isSub) {
                if ($parent === null) return $this->responseError("Parent is required for submenu", 422);

                if ($position === null) {
                    $position = $this->model->getLastPositionInScope($parent) + 1;
                } else {
                    $this->model->shiftPositionsDown($parent, $position);
                }
                $iconToSave = null;
            } else {
                if ($position === null) {
                    $position = $this->model->getLastPositionInScope(null) + 1;
                } else {
                    $this->model->shiftPositionsDown(null, $position);
                }
                $iconToSave = ($icon === '' ? null : $icon);
            }

            $sidebarGroup = null;
            if (!$isSub) {
                $g = trim((string)($data['txt_sidebar_group'] ?? ''));
                $sidebarGroup = ($g === '' ? null : $g);
            }

            $sql = "INSERT INTO mx_menu
                    (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value, txt_sidebar_group)
                    VALUES (:name, :icon, :parent, :pos, :link, :title, NEWID(), :sidebar_group)";

            $params = [
                ':name'           => $name,
                ':icon'           => $iconToSave,
                ':parent'         => $parent,
                ':pos'            => $position,
                ':link'           => $link,
                ':title'          => $title,
                ':sidebar_group'  => $sidebarGroup,
            ];

            $stmt = $this->model->db->prepare($sql);
            $ok = $stmt->execute($params);

            $this->model->db->commit();

            return $this->responseSuccess($ok ? 200 : 100, "Menu saved", [
                'data' => [
                    'int_parent'   => $parent,
                    'int_position' => $position,
                ]
            ]);
        } catch (Exception $e) {
            try {
                $this->model->db->rollBack();
            } catch (Exception $ignore) {
            }
            Log::exception($e, 'MENU_SAVE_ERROR', ['action' => 'saveMenu']);
            return $this->responseError("Failed to save menu", 500);
        }
    }

    public function edit($id)
    {
        try {
            $this->requirePermission('edit_menu');
            $id = (string)$id;

            $recordId = $this->model->getRecordIdByRowValue($this->model->getTable(), $id);
            if ($recordId < 0) {
                $this->renderFull('views/templates/not_found');
                return;
            }

            $data = $this->model->getRecord($recordId, $this->model->getTable());

            $this->view()->title = 'Update ' . $this->model->getTitle();
            $this->view()->data  = [
                'id'                 => $id,
                'int_menu_record_id' => $recordId,
                'txt_name'           => $data['txt_name'],
                'txt_link'           => $data['txt_link'],
                'txt_title'          => $data['txt_title'],
                'txt_icon'           => $data['txt_icon'],
                'int_parent'         => ($data['int_parent'] !== null) ? (int)$data['int_parent'] : null,
                'int_position'       => (int)$data['int_position'],
                'relation'           => (($data['int_parent'] ?? null) !== null && (int)$data['int_parent'] > 0) ? 1 : 0,
                'txt_sidebar_group'  => isset($data['txt_sidebar_group']) ? trim((string)$data['txt_sidebar_group']) : '',
            ];

            $this->view()->dropdowns = $this->model->getFormDropdowns($recordId);
            $this->render('edit');
        } catch (Exception $e) {
            Log::exception($e, 'MENU_EDIT_ERROR', ['action' => 'edit']);
            $this->render('templates/error');
        }
    }

    public function postEdit()
    {
        try {
            $this->requirePermission('edit_menu');

            $raw = file_get_contents('php://input');
            $posted = json_decode((string)$raw, true);
            if (!is_array($posted) || $posted === []) {
                $posted = $_POST;
            }
            if (!is_array($posted)) {
                return $this->responseError("Invalid input", 422);
            }

            $row_value = trim((string)($posted['id'] ?? ''));
            if ($row_value === '') return $this->responseError("Missing id", 422);

            $id = $this->model->getRecordIdByRowValue($this->model->getTable(), $row_value);
            if ($id < 0) return $this->responseError("Record not found", 404);

            $current = $this->model->getRecord($id, $this->model->getTable());

            $name   = trim((string)($posted['txt_name'] ?? $current['txt_name']));
            $title  = trim((string)($posted['txt_title'] ?? $current['txt_title']));
            $link   = trim((string)($posted['txt_link'] ?? $current['txt_link']));
            $icon   = trim((string)($posted['txt_icon'] ?? ($current['txt_icon'] ?? '')));

            $parent = $posted['int_parent'] ?? $current['int_parent'];
            $parent = ($parent === '' || $parent === null) ? null : (int)$parent;
            if ($parent !== null && $parent <= 0) $parent = null;

            $isSub = ($parent !== null);
            if ($isSub) {
                $icon = '';
            }

            $position = isset($posted['int_position']) && is_numeric($posted['int_position'])
                ? max(1, (int)$posted['int_position'])
                : (int)$current['int_position'];

            if ($name === '')  return $this->responseError("Menu name required", 422);
            if ($title === '') return $this->responseError("Menu title required", 422);
            $link = self::normalizeSubmittedLink($link);
            if ($link === null) return $this->responseError("Invalid link", 422);

            $oldParent = $current['int_parent'];
            $oldParent = ($oldParent === null ? null : (int)$oldParent);
            $oldPos    = (int)$current['int_position'];

            $this->model->db->beginTransaction();

            $this->reorderOnMove($id, $oldParent, $oldPos, $parent, $position);

            $sidebarGroup = null;
            if (!$isSub) {
                $g = trim((string)($posted['txt_sidebar_group'] ?? ($current['txt_sidebar_group'] ?? '')));
                $sidebarGroup = ($g === '' ? null : $g);
            }

            $update = [
                'txt_name'           => $name,
                'txt_link'           => $link,
                'txt_title'          => $title,
                'txt_icon'           => ($icon === '' ? null : $icon),
                'int_parent'         => $parent,
                'int_position'       => $position,
                'txt_sidebar_group'  => $sidebarGroup,
            ];

            $this->model->update($update, $this->model->getTable(), $id);

            $this->model->db->commit();
            return $this->responseSuccess(201, "Updated");
        } catch (Exception $e) {
            try {
                $this->model->db->rollBack();
            } catch (Exception $ignore) {
            }
            Log::exception($e, 'MENU_POST_EDIT_ERROR', ['action' => 'postEdit']);
            return $this->responseError("Edit failed", 500);
        }
    }

    /**
     * GET USER MENUS (sidebar)
     * Optimized: Fetches all menus in one query to avoid N+1 problem.
     */
    public function getUserMenus()
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return $this->responseError("User not authenticated", 401);
            }

            // 1. Fetch all menus with their linked permission slug in one query.
            //    Falls back to a plain query if rbac_v2.sql migration hasn't been run yet.
            try {
                $allMenus = $this->model->db->select(
                    "SELECT m.id, m.txt_name, m.txt_icon, m.int_parent, m.int_position,
                            m.txt_link, m.txt_title, m.txt_row_value, m.txt_sidebar_group,
                            p.txt_name AS txt_permission_slug
                     FROM mx_menu m
                     LEFT JOIN mx_permission p ON p.id = m.opt_mx_permission_id
                     ORDER BY m.int_parent ASC, m.int_position ASC, m.id ASC"
                );
            } catch (\Throwable $migrationErr) {
                // Migration not yet applied — opt_mx_permission_id column missing.
                // Show all menus to authenticated users (NULL slug = visible to all).
                Log::sysLog('GATE_MENU_FALLBACK: run database/migrations/rbac_v2.sql to enable per-menu permissions');
                $allMenus = $this->model->db->select(
                    "SELECT id, txt_name, txt_icon, int_parent, int_position,
                            txt_link, txt_title, txt_row_value, txt_sidebar_group,
                            NULL AS txt_permission_slug
                     FROM mx_menu
                     ORDER BY int_parent ASC, int_position ASC, id ASC"
                );
            }

            // 2. Organise into parents and children, filtering by permission
            $parents  = [];
            $children = [];

            foreach ($allMenus as $menu) {
                $parentId = $menu['int_parent'] ?? null;
                $isRoot   = ($parentId === null || $parentId === '' || $parentId === false);
                $slug     = $menu['txt_permission_slug'] ?? null;

                // Permission check: null slug = visible to all; otherwise Gate checks it
                $allowed = ($slug === null) || Gate::allows((string)$slug);

                if ($isRoot) {
                    if ($allowed) {
                        $parents[$menu['id']] = $menu;
                    }
                } else {
                    $pid = (int) $parentId;
                    if ($pid > 0 && $allowed) {
                        $children[$pid][] = $menu;
                    }
                }
            }

            uasort($parents, static function (array $a, array $b): int {
                return ((int) ($a['int_position'] ?? 0)) <=> ((int) ($b['int_position'] ?? 0));
            });

            $final = [];
            foreach ($parents as $parentId => $menu) {
                $pid  = (int) $parentId;
                $sub  = $children[$pid] ?? [];

                $items = array_map(function ($item) {
                    $link = trim((string)($item['txt_link'] ?? '#'));
                    $link = ($link === '' ? '#' : $link);
                    return [
                        'name'  => trans($item['txt_name']),
                        'link'  => ($link === '#' ? '#' : self::menuLinkForClient($link)),
                        'title' => $item['txt_title'],
                        'icon'  => $item['txt_icon'],
                    ];
                }, $sub);

                $link = trim((string)($menu['txt_link'] ?? '#'));
                $link = ($link === '' ? '#' : $link);

                $final[] = [
                    'id'           => $menu['txt_row_value'],
                    'name'         => trans($menu['txt_name']),
                    'link'         => ($link === '#' ? '#' : self::menuLinkForClient($link)),
                    'title'        => $menu['txt_title'],
                    'icon'         => $menu['txt_icon'],
                    'sidebarGroup' => trim((string) ($menu['txt_sidebar_group'] ?? '')),
                    'int_position' => (int) ($menu['int_position'] ?? 0),
                    'submenus'     => $items,
                ];
            }

            return $this->responseSuccess(200, "Menus loaded", ['data' => $final]);
        } catch (Exception $e) {
            Log::exception($e, 'MENU_USER_MENU_ERROR', ['action' => 'getUserMenus']);
            return $this->responseError("Failed to load menus", 500);
        }
    }

    /**
     * Normalized path for SPA / XHR (single leading slash, no APP_DIR — base URL already includes it).
     */
    private static function menuLinkForClient(string $link): string
    {
        $link = trim($link);
        if ($link === '' || $link === '#') {
            return '#';
        }
        if ($link[0] !== '/') {
            $link = '/' . $link;
        }

        return $link;
    }

    private static function normalizeSubmittedLink(string $link): ?string
    {
        $link = trim($link);
        if ($link === '') {
            return '#';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $link)) {
            return null;
        }

        $lower = strtolower($link);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:') || str_starts_with($lower, '//')) {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $link)) {
            return null;
        }

        return mb_substr($link, 0, 500);
    }

    /**
     * DELETE MENU
     * POST /Menu/deleteMenu
     */
    public function deleteMenu()
    {
        try {
            $this->requirePermission('delete_menu');

            $raw = file_get_contents('php://input');
            $posted = json_decode((string)$raw, true);
            
            $id = trim((string)($posted['id'] ?? ''));
            if ($id === '') return $this->responseError("Missing ID", 422);

            $record = $this->model->getRecordByRowValue($this->model->getTable(), $id);
            if (!$record) return $this->responseError("Record not found", 404);

            $recordId = (int)$record['id'];
            $parent   = $record['int_parent'] ?? null;
            $parent   = ($parent === null) ? null : (int)$parent;
            $position = (int)$record['int_position'];

            $this->model->db->beginTransaction();

            // 1. Delete children first if it's a parent
            if ($parent === null) {
                $this->model->db->prepare("DELETE FROM mx_menu WHERE int_parent = :p")
                    ->execute([':p' => $recordId]);
            }

            // 2. Delete the record
            $ok = $this->model->deleteRecord($this->model->getTable(), $recordId);

            // 3. Close the gap in positions
            $this->model->closeGapAfterRemoval($parent, $position);

            $this->model->db->commit();

            \Services\AuditTrail::log('MENU_DELETED', "Menu: {$record['txt_name']}", ['data' => $record]);

            return $this->responseSuccess($ok ? 200 : 100, "Menu deleted");

        } catch (Exception $e) {
            try {
                $this->model->db->rollBack();
            } catch (Exception $ignore) {}
            Log::exception($e, 'MENU_DELETE_ERROR', ['action' => 'deleteMenu']);
            return $this->responseError("Delete failed", 500);
        }
    }

    private function reorderOnMove(int $recordId, ?int $oldParent, int $oldPos, ?int $newParent, int $newPos): void
    {
        if ($oldParent === $newParent && $oldPos === $newPos) return;

        $this->model->closeGapAfterRemoval($oldParent, $oldPos);

        $max = $this->model->getLastPositionInScope($newParent) + 1;
        if ($newPos > $max) $newPos = $max;
        if ($newPos < 1) $newPos = 1;

        $this->model->shiftPositionsDown($newParent, $newPos);

        $this->model->update([
            'int_parent'   => $newParent,
            'int_position' => $newPos,
        ], $this->model->getTable(), $recordId);
    }
}
