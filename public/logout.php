<?php
require_once __DIR__ . '/../src/bootstrap.php';

// GET on /logout does NOT trigger logout (CSRF protection).
// Logout requires POST with a valid CSRF token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/login.php');
}

verifyCsrf();
Auth::logout();
redirectTo('/login.php');
