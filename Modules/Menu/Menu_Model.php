<?php

namespace Modules\Menu;

use Library\Model;
use Loggers\Log;

class Menu_Model extends Model
{
    public string $table = "mx_menu";
    private string $view_dir = "menu/";
    private string $title = "Menu";

    public array $no_old_data = ['saveMenu'];

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
        return [
            ["action" => "Edit", "name" => "Edit", "icon" => "fa-edit", "color" => "blue", "url" => "edit"],
        ];
    }

    public function getTable($view_table = false): string
    {
        return $this->table;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getViewDir(): string
    {
        return $this->view_dir;
    }

    /**
     * Returns menu tree (parents with children)
     */
    public function getMenus(): array
    {
        try {
            $parents = $this->db->select("
                SELECT id, txt_name, txt_icon, int_position, txt_link, txt_title, txt_row_value
                FROM mx_menu
                WHERE int_parent IS NULL
                ORDER BY int_position ASC
            ");

            $out = [];

            foreach ($parents as $p) {
                $children = $this->db->select("
                    SELECT id, txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value
                    FROM mx_menu
                    WHERE int_parent = :pid
                    ORDER BY int_position ASC
                ", [':pid' => (int)$p['id']]);

                $p['children'] = $children;
                $out[] = $p;
            }

            return $out;

        } catch (\Exception $e) {
            Log::sysLog("MENU_MODEL_GETMENUS_ERROR:" . $e->getMessage());
            return [];
        }
    }

    /**
     * Dropdowns for create/edit screen
     */
    public function getFormDropdowns($id = null): array
    {
        try {
            $parents = $this->db->select("
                SELECT id, txt_name
                FROM mx_menu
                WHERE int_parent IS NULL
                ORDER BY int_position ASC
            ");

            $int_parent_ids = array_map(function ($r) {
                return ['id' => (int)$r['id'], 'name' => $r['txt_name']];
            }, $parents);

            $permissions = $this->db->select("SELECT id, txt_name as name FROM mx_permission ORDER BY txt_name ASC");

            return [
                'int_parent_ids'         => $int_parent_ids,
                'opt_mx_permission_ids'  => $permissions,
                'all_menus'              => $this->getMenus(),
            ];

        } catch (\Exception $e) {
            Log::sysLog("MENU_MODEL_DROPDOWNS_ERROR:" . $e->getMessage());
            return [
                'int_parent_ids' => [],
                'all_menus'      => [],
            ];
        }
    }

    public function getMainMenu()
    {
        return $this->db->select(
            "SELECT * FROM mx_menu WHERE int_parent IS NULL ORDER BY int_position"
        );
    }

    public function getSubMenu($id)
    {
        return $this->db->select(
            "SELECT * FROM mx_menu WHERE int_parent = :id ORDER BY int_position ASC",
            [':id' => (int)$id]
        );
    }

    public function getLastPositionInScope(?int $parent): int
    {
        if ($parent === null) {
            $r = $this->db->select(
                "SELECT TOP 1 int_position AS last_position
                 FROM mx_menu
                 WHERE int_parent IS NULL
                 ORDER BY int_position DESC"
            );
        } else {
            $r = $this->db->select(
                "SELECT TOP 1 int_position AS last_position
                 FROM mx_menu
                 WHERE int_parent = :p
                 ORDER BY int_position DESC",
                [':p' => $parent]
            );
        }

        return (int)($r[0]['last_position'] ?? 0);
    }

    public function shiftPositionsDown(?int $parent, int $fromPosition): void
    {
        if ($parent === null) {
            $sql = "UPDATE mx_menu
                    SET int_position = int_position + 1
                    WHERE int_parent IS NULL
                      AND int_position >= :pos";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':pos' => $fromPosition]);
        } else {
            $sql = "UPDATE mx_menu
                    SET int_position = int_position + 1
                    WHERE int_parent = :p
                      AND int_position >= :pos";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':p' => $parent, ':pos' => $fromPosition]);
        }
    }

    public function closeGapAfterRemoval(?int $parent, int $removedPos): void
    {
        if ($parent === null) {
            $sql = "UPDATE mx_menu
                    SET int_position = int_position - 1
                    WHERE int_parent IS NULL
                      AND int_position > :pos";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':pos' => $removedPos]);
        } else {
            $sql = "UPDATE mx_menu
                    SET int_position = int_position - 1
                    WHERE int_parent = :p
                      AND int_position > :pos";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':p' => $parent, ':pos' => $removedPos]);
        }
    }
}
