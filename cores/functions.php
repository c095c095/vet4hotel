<?php
function base_url($path = '')
{
    return SITE_URL . '/' . ltrim($path, '/');
}

function assets($path = '')
{
    return base_url('assets/' . ltrim($path, '/'));
}

function redirect($page)
{
    echo "<script> window.location.href = '?page=" . $page . "'; </script> ";
    exit();
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function current_user()
{
    global $pdo;
    if (!is_logged_in())
        return null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function require_login()
{
    if (!is_logged_in()) {
        redirect('login');
    }
}

function is_staff()
{
    return @$_SESSION['user_type'] === 'staff';
}

function is_customer()
{
    return @$_SESSION['user_type'] === 'customer';
}
