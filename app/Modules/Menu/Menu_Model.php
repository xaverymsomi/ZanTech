<?php

namespace Modules\Menu;

use Database\Model;
use Logging\Log;

class Menu_Model extends Model
{
    protected string $table = "mx_menu";
    private string $view_dir = "menu/";
    protected string $title = "Menu";

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

    public function getTitle($plural = false): string
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
            $rows = $this->db->select("
                SELECT id, txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value, txt_sidebar_group
                FROM mx_menu
                ORDER BY
                    CASE WHEN int_parent IS NULL THEN 0 ELSE 1 END,
                    int_parent ASC,
                    int_position ASC,
                    id ASC
            ");

            $parents = [];
            $children = [];
            foreach ($rows ?: [] as $row) {
                $parentId = $row['int_parent'] ?? null;
                $isRoot = ($parentId === null || $parentId === '' || $parentId === false);
                if ($isRoot) {
                    $row['children'] = [];
                    $parents[(int)$row['id']] = $row;
                    continue;
                }

                $children[(int)$parentId][] = $row;
            }

            $out = [];
            foreach ($parents as $p) {
                $p['children'] = $children[(int)$p['id']] ?? [];
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

            // Next slot per parent — from full root list (before excluding self on edit).
            $nextChildByParent = [];
            $positionRows = $this->db->select("
                SELECT int_parent, MAX(int_position) AS last_position
                FROM mx_menu
                WHERE int_parent IS NOT NULL
                GROUP BY int_parent
            ");
            foreach ($positionRows ?: [] as $row) {
                $nextChildByParent[(int)$row['int_parent']] = (int)($row['last_position'] ?? 0) + 1;
            }

            foreach ($parents as $row) {
                $pid = (int)$row['id'];
                $nextChildByParent[$pid] = $nextChildByParent[$pid] ?? 1;
            }

            $int_parent_ids = array_map(static function ($r) {
                return ['id' => (int)$r['id'], 'name' => $r['txt_name']];
            }, $parents);

            // Editing a root menu: cannot pick itself as parent when switching to Sub Menu.
            if ($id !== null && $id !== '') {
                $rid = (int)$id;
                if ($rid > 0) {
                    $cur = $this->getRecord($rid, $this->table);
                    if ($cur !== []) {
                        $par = $cur['int_parent'] ?? null;
                        $isRoot = ($par === null || $par === '' || $par === false || (int)$par <= 0);
                        if ($isRoot) {
                            $int_parent_ids = array_values(array_filter(
                                $int_parent_ids,
                                static fn(array $r): bool => (int)$r['id'] !== $rid
                            ));
                        }
                    }
                }
            }

            $permissions = $this->db->select("SELECT id, txt_name as name FROM mx_permission ORDER BY txt_name ASC");

            return [
                'int_parent_ids'                => $int_parent_ids,
                'opt_mx_permission_ids'         => $permissions,
                'all_menus'                     => $this->getMenus(),
                'next_top_position'             => $this->getLastPositionInScope(null) + 1,
                'next_child_position_by_parent' => $nextChildByParent,
            ];

        } catch (\Exception $e) {
            Log::sysLog("MENU_MODEL_DROPDOWNS_ERROR:" . $e->getMessage());
            return [
                'int_parent_ids'                => [],
                'all_menus'                     => [],
                'next_top_position'             => 1,
                'next_child_position_by_parent' => [],
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
