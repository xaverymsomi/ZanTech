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

        // Securely terminate the session and clear authentication fingerprints
        Auth::logout();

        header('Location: ' . URL);
        exit;
    }
}
