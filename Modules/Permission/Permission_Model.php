<?php

namespace Modules\Permission;

use Database\Model;

/**
 * Permission Model
 *
 * Provides data for Permission Management UI:
 * - groups dropdown
 * - users dropdown
 * - sections dropdown
 * - allPermissions list (with section_name) for grouping
 */
class Permission_Model extends Model
{
    protected string $table = 'mx_permission';

    private string $view_dir = 'permission/';
    protected string $title = 'Permission';

    public function getHiddenFields(): array
    {
        return ['id'];
    }

    public function getFormHiddenFields(): array
    {
        return ['id'];
    }

    public function getControls(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [];
    }

    public function getTable($view_table = false): string
    {
        if ($view_table && property_exists($this, 'view_table') && !empty($this->view_table)) {
            return $this->view_table;
        }
        return $this->table;
    }

    public function getTitle($plural = false): string
    {
        return $this->title;
    }

    public function getViewDir(): string
    {
        return $this->view_dir;
    }

    /**
     * Load initial dropdown data used by Permission UI
     */
    public function loadData(): array
    {
        $groups = $this->getGroups();
        $users = $this->getUsers();
        $sections = $this->getSections();
        $allPermissions = $this->getAllPermissionsWithSections();

        // Kept for backward-compat (some old views might still read this)
        $permissions = $this->getPermissionKeys();

        return [
            'groups' => $groups,
            'permissions' => $permissions,
            'users' => $users,
            'sections' => $sections,
            'allPermissions' => $allPermissions,
        ];
    }

    private function getGroups(): array
    {
        $rows = $this->db->select("
            SELECT id, txt_name
            FROM mx_group
            ORDER BY txt_name ASC
        ");

        $out = [];
        foreach (($rows ?: []) as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'name' => (string) $r['txt_name'],
            ];
        }
        return $out;
    }

    /**
     * Users list for dropdown.
     * UI expects: { id, name, domain }
     */
    private function getUsers(): array
    {
        // If you truly have multiple "domains", replace this with your real logic.
        $domain = $_SESSION['domain'] ?? 'mx_user';

        $rows = $this->db->select("
            SELECT txt_row_value, txt_name
            FROM mx_user
            ORDER BY txt_name ASC
        ");

        $out = [];
        foreach (($rows ?: []) as $r) {
            $out[] = [
                'id' => (string) $r['txt_row_value'],
                'name' => (string) $r['txt_name'],
                'domain' => (string) $domain,
            ];
        }
        return $out;
    }

    private function getSections(): array
    {
        $rows = $this->db->select("
            SELECT id, txt_name
            FROM mx_section
            ORDER BY txt_name ASC
        ");

        $out = [];
        foreach (($rows ?: []) as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'name' => (string) $r['txt_name'],
            ];
        }
        return $out;
    }

    /**
     * Old/simple list of permission keys.
     * (Optional — kept because your original model returned it)
     */
    private function getPermissionKeys(): array
    {
        $rows = $this->db->select("
            SELECT id, txt_name
            FROM mx_permission
            ORDER BY txt_name ASC
        ");

        $out = [];
        foreach (($rows ?: []) as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'name' => (string) $r['txt_name'],
            ];
        }
        return $out;
    }

    /**
     * Used by TAB 3 and for grouping by section_name on the UI.
     */
    private function getAllPermissionsWithSections(): array
    {
        $rows = $this->db->select("
            SELECT
                s.id        AS section_id,
                s.txt_name  AS section_name,
                p.id        AS permission_id,
                p.txt_name  AS permission_name,
                p.txt_name  AS permission_display_name
            FROM mx_permission p
            JOIN mx_section s
              ON s.id = p.opt_mx_section_id
            ORDER BY s.txt_name ASC, p.txt_name ASC
        ");

        $out = [];
        foreach (($rows ?: []) as $r) {
            // Display name: "view_permissions" => "View Permissions"
            $display = str_replace('_', ' ', (string) $r['permission_display_name']);
            $display = ucwords(strtolower($display));

            $out[] = [
                'section_id' => (int) $r['section_id'],
                'section_name' => (string) $r['section_name'],
                'permission_id' => (int) $r['permission_id'],
                'permission_name' => (string) $r['permission_name'],
                'permission_display_name' => $display,
            ];
        }

        return $out;
    }
}
