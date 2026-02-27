<?php

session_start();
date_default_timezone_set("Asia/Bangkok");

require_once '../cores/config.php';
require_once 'routes.php';
require_once '../cores/database.php';
require_once '../cores/functions.php';

// Action Routing (Handle Form Submissions via index.php?action=...)
if (isset($_GET['action'])) {
    $action = trim($_GET['action']);
    $action_file = __DIR__ . '/process_' . $action . '.php';
    if (file_exists($action_file)) {
        require_once $action_file;
        exit();
    }
}

$current_page = isset($_GET['page']) ? trim($_GET['page']) : 'home';

if (!array_key_exists($current_page, $pages)) {
    $current_page = '404';
}

$ignore_redirect_pages = ['logout'];

if (!isset($_SESSION['employee_id']) && $current_page !== 'login') {
    if (in_array($current_page, $ignore_redirect_pages) || empty($_GET['page'])) {
        header("Location: ?page=login");
    } else {
        $_SESSION['error_msg'] = "กรุณาเข้าสู่ระบบก่อนเข้าใช้งานหน้านี้";
        $current_url = '?' . http_build_query($_GET);
        header("Location: ?page=login&redirect=" . urlencode($current_url));
    }
    exit();
}

$page_title = "หลังบ้าน " . $pages[$current_page]['title'] . " | " . SITE_NAME;
$page_file = $pages[$current_page]['file'];


// Intercept pages that must process and redirect without outputting any HTML structure
$intercept_pages = ['logout'];
if (in_array($current_page, $intercept_pages)) {
    require_once $page_file;
    exit();
}
