<?php
namespace Modules\DualActivity;

use Database\Model;

class DualActivity_Model extends Model {

    protected string $table = "mx_dual_activity";
    private string $view_dir = "/dualactivity/";
    protected string $title = "Dual Activity";

    public function getHiddenFields() {
        return ['txt_row_value'];
    }

    public function getFormHiddenFields() {
        return [];
    }

    public function getControls() {
        return [];
    }

    public function getActions() {
        return [["action" => "Edit_Dual_Activity", "name" => "Edit", "icon" => "fa-edit", "color" => "blue", "url" => "DualActivity"]];
    }

    public function getTable($view_table = false): string
    {
        if ($view_table && property_exists($this, 'view_table') && !empty($this->view_table)) {
            return $this->view_table;
        }
        return $this->table;
    }

    public function setTable($table) {
        $this->table = $table;
    }

    public function getTitle($plural = false): string {
        return $this->title;
    }

    public function getViewDir() {
        return $this->view_dir;
    }

    public function setViewDir($view_dir) {
        $this->view_dir = $view_dir;
    }

    public function getFormDropdowns() {
        $array = [];
        $sql = "SELECT mx_group.*, mx_council.txt_name AS 'council_name' FROM mx_group JOIN mx_council ON mx_council.id = mx_group.opt_mx_council_id ORDER BY id ASC";
        $result = $this->db->select($sql);

        if ($result) {
            foreach ($result as $value) {
                $array[] = ['id' => $value['txt_row_value'], 'name' => $value['txt_name'] . ' (' . $value['council_name'] . ')'];
            }
        }
        $data['opt_mx_group_ids'] = $array;

        $required = [['id' => 1, 'name' => 'Yes'], ['id' => 0, 'name' => 'No']];

        $data['opt_require_dual_activity'] = $required;

        return $data;
    }

    public function getCouncilsGroups($activity) {
        $data = [];
        $councils = $this->db->select("SELECT id, txt_name, txt_row_value FROM mx_council WHERE opt_mx_state_id = :state", [':state' => 1]);
        if (count($councils) > 0){
            foreach ($councils as $council) {
                $groups = $this->db->select("SELECT id, txt_name, txt_row_value, (SELECT COUNT(mx_dual_activity_group.opt_mx_group_id) FROM mx_dual_activity_group WHERE mx_dual_activity_group.opt_mx_group_id = mx_group.id AND mx_dual_activity_group.opt_mx_dual_activity_id = :activity) AS 'selected' FROM mx_group WHERE opt_mx_council_id = :council", [':activity' => $activity, ':council' => $council['id']]);
                $council_groups = [];
                if (count($groups) > 0) {
                    foreach ($groups as $group) {
                        $council_groups[] = ['id' => $group['txt_row_value'], 'name' => $group['txt_name'], 'selected' => $group['selected']];
                    }
                }
                $data[] = [
                    'id' => $council['txt_row_value'],
                    'name' => $council['txt_name'],
                    'groups' => $council_groups
                ];
            }
        }
        return $data;
    }
    
    public function manageDualActvitySetting($activity, $groups) {
        $stmt = $this->db->prepare("DELETE FROM mx_dual_activity_group WHERE opt_mx_dual_activity_id = :activity");
        $stmt->execute([':activity' => $activity]);
        foreach ($groups as $group) {
            $group_id = $this->getRecordIdByRowValue('mx_group', $group['group']);
            $stmt2 = $this->db->prepare('INSERT INTO mx_dual_activity_group(opt_mx_dual_activity_id, opt_mx_group_id) VALUES (:activity, :group)');
            $stmt2->execute([':activity' => $activity, ':group' => $group_id]);
        }
    }
}
