<?php require "vendor/autoload.php"; require "bootstrap/config.php"; $db = new Database\Database(); print_r(array_keys($db->select("SELECT TOP 1 * FROM mx_login_credential")[0] ?? []));
