<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Modules\Logout;

use Library\\Auth;
use Library\\Database;
use Library\\Model;

/**
 * Description of Logout
 *
 * @author abdirahmanhassan
 */
class Logout extends Model {

    public function __construct() {
        Auth::checkLogin();
        if (!isset($_SESSION)) {

            session_start();
        }

        $logged = $_SESSION['rp_signed_in'];
        if ($logged == true) {
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            $this->updateUserState('mx_login_credential', 0, $_SESSION['id']);
            header('location: ' . URL);
            exit;
        }
        session_destroy();
    }

    private function updateUserState($table, $state, $user_id) {
        $db = new Database();
        //SET USER AS ACTIVE
        $query = "UPDATE " . $table . " SET int_active=" . filter_var($state, FILTER_SANITIZE_NUMBER_INT) . " WHERE id ='" . filter_var($user_id, FILTER_SANITIZE_SPECIAL_CHARS) . "'";
        $stmt = $db->prepare($query);
        $stmt->execute();
    }

    //put your code here
}
