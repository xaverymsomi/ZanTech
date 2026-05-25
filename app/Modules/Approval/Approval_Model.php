<?php

namespace Modules\Approval;

use Database\Model;
use PDO;

class Approval_Model extends Model
{
    protected string $table = "mx_dual_activity_log";
    protected string $view_table = "mx_dual_activity_log_view";
    protected string $title = "Pending Approval";

    /**
     * Fetch all pending requests for the current checker.
     */
    public function getPendingRequests(int $checkerId): array
    {
        $sql = "SELECT al.id, al.txt_token, al.txt_module_name, al.txt_method_name, 
                       al.txt_column_value as reference, al.dat_activity_triggered_date as date,
                       u.txt_username as maker_name,
                       da.txt_name as activity_name
                FROM mx_dual_activity_log al
                JOIN mx_user u ON al.int_activity_triggered_by = u.id
                JOIN mx_dual_activity da ON al.opt_mx_dual_activity_id = da.id
                WHERE al.opt_mx_login_credential_id = :cid
                AND al.int_status = 0
                ORDER BY al.dat_activity_triggered_date DESC";

        return $this->db->select($sql, [':cid' => $checkerId]);
    }

    /**
     * Get full details of a specific pending request.
     */
    public function getRequestDetails(int $requestId): ?array
    {
        $sql = "SELECT * FROM mx_dual_activity_log WHERE id = :id";
        $rows = $this->db->select($sql, [':id' => $requestId]);
        return $rows[0] ?? null;
    }

    public function getControls(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "view_details",
                "name"   => "Review",
                "icon"   => "fa-eye",
                "color"  => "primary",
                "url"    => "Approval/details"
            ]
        ];
    }
}
