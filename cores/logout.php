<?php

$user_type = @$_SESSION['user_type'];

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

if ($user_type == 'admin' || $user_type == 'user') {
    redirect('admin/login');
} else {
    redirect('login');
}

exit();
