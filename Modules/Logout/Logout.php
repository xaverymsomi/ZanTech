<?php

namespace Modules\Logout;

use Authentication\Auth;
use Database\Database;
use Http\Controller;

/**
 * Logout Module Controller
 * Handles user session termination and database activity state updates.
 */
class Logout extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (Auth::isLogged()) {
            $userId = Auth::id();
            
            // Mark user session as inactive (0) in the database credentials table
            if ($userId !== null) {
                $this->updateUserState('mx_login_credential', 0, $userId);
            }
            
            // Securely terminate the session and clear authentication fingerprints
            Auth::logout();
        } else {
            // Fallback for anonymous sessions to ensure complete cleanup
            Auth::logout();
        }

        header('Location: ' . URL);
        exit;
    }

    /**
     * Update user active state in the database.
     */
    private function updateUserState(string $table, int $state, int $userId): void
    {
        $db = new Database();
        $query = "UPDATE " . $db->quoteTable($table) . " SET int_active = :state WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':state' => $state,
            ':id'    => $userId,
        ]);
    }
}
