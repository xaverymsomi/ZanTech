<?php
namespace Modules\Miscellaneous;

use Exception;
use Database\Model;

/**
 * Miscellaneous_Model
 */
class Miscellaneous_Model extends Model {
    protected string $table = "mx_rule_configuration";
    private string $view_dir = "miscellaneous/";
    protected string $title = "Miscellaneous";
    protected string $title_plural = "Miscellaneous";


    public function getHiddenFields() {
        return ['id'];
    }

    public function getFormHiddenFields() {
        return array('id');
    }

    public function getControls() {
        return [];
    }

    public function getActions() {
        return [];
    }

    public function getTable($view_table = false): string
    {
        if ($view_table && property_exists($this, 'view_table') && !empty($this->view_table)) {
            return $this->view_table;
        }
        return $this->table;
    }

    public function getTitle($plural = false): string {
        return $plural ? $this->title_plural : $this->title;
    }

    public function getViewDir() {
        return $this->view_dir;
    }

    public function getConfigurationData($status = null) {
        $sql = "SELECT 
                mx_rule.id, 
                mx_rule_configuration.id AS config_id, 
                mx_rule_configuration.opt_mx_status_id,
                mx_rule.txt_name, 
                mx_rule.txt_description, 
                mx_rule.txt_type, 
                mx_rule_configuration.txt_value, 
                mx_rule_configuration.dat_effective_start_date, 
                mx_rule_configuration.dat_effective_end_date, 
                mx_rule_configuration.txt_row_value 
            FROM mx_rule
            JOIN mx_rule_configuration ON mx_rule.id = mx_rule_configuration.int_mx_rule_id";

        if ($status != null){
            if ($status == 1) {$sql .= " AND opt_mx_status_id = 1 ";}
            elseif ($status == 2) {$sql .= " AND opt_mx_status_id = 2 ";}
        }
        return $this->db->select($sql);
    }

    public function getMiscellaneousDropdowns() {
        $data = [];
        $result = $this->db->select("SELECT txt_row_value, txt_description, txt_type FROM mx_rule ORDER BY txt_name ASC");
        if ($result) {
            foreach ($result as $value) {
                $data["opt_mx_rule_ids"][] = ['id' => $value['txt_row_value'], 'name' => $value['txt_description'], 'type' => $value['txt_type']];
            }
        }
        return $data;
    }

    public function updateConfiguration($data) {
        try {
            $value = $data['txt_value'];
            $dat_effective_start_date = date('Y-m-d', strtotime($data['txt_effective_start_date']));
            $dat_effective_end_date = date('Y-m-d', strtotime($data['txt_effective_end_date']));
            if ($data['txt_type'] == 'checkbox') {
                if ($value == 1 || $value == true) {
                    $value = "True";
                } else {
                    $value = "False";
                }
            }

            $qry = "UPDATE mx_rule_configuration SET txt_value = :value, dat_effective_start_date = :dat_effective_start_date, dat_effective_end_date = :dat_effective_end_date WHERE txt_row_value = :id";
            $stmt = $this->db->prepare($qry);
            $stmt->execute([':value' => $value,':dat_effective_start_date' => $dat_effective_start_date,':dat_effective_end_date' => $dat_effective_end_date, ':id' => $data['txt_row_value']]);
            return 210;
        } catch (Exception $ex) {
            return 300;
        }
    }

    public function saveConfiguration($data) {
        try {
            $rule_id = $this->getRecordIdByRowValue('mx_rule', $data['int_mx_rule_id']);
            $value =  $data['txt_value'];
            $dat_effective_start_date = date('Y-m-d', strtotime($data['dat_effective_start_date']));
            $dat_effective_end_date = date('Y-m-d', strtotime($data['dat_effective_end_date']));

            $this->create([
                'int_mx_rule_id' => $rule_id,
                'txt_value' => $value,
                'dat_effective_start_date' => $dat_effective_start_date,
                'dat_effective_end_date' => $dat_effective_end_date,
            ], 'mx_rule_configuration');

            return 200;
        } catch (Exception $ex) {
            return 300;
        }
    }
}
