<?php

namespace Modules\Finance;

use Database\Model;

class Finance_Model extends Model
{
    protected string $table = "mx_employee_salary";
    protected string $title = "Finance Management";

    public function getControls(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [];
    }
}
