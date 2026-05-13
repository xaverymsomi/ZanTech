<?php

namespace Modules\Settings;

use Library\Model;

class Settings_Model extends Model
{
    protected string $table = 'mx_setting';

    public function getAllSettings(): array
    {
        return $this->query()->orderBy('txt_key')->get();
    }
}
