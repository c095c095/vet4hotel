<?php
// ═══════════════════════════════════════════════════════════
// LOGOUT — VET4 HOTEL
// Securely destroy session and redirect user
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: ../?page=home");
exit();