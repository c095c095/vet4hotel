<?php

session_start();
date_default_timezone_set("Asia/Bangkok");

require_once 'config.php';
require_once 'routes.php';
require_once 'database.php';
require_once 'functions.php';

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

if ($pages[$current_page]['auth_required'] === true) {
    if (!isset($_SESSION['customer_id'])) {
        $_SESSION['error_msg'] = "กรุณาเข้าสู่ระบบก่อนเข้าใช้งานหน้านี้";
        header("Location: index.php?page=login");
        exit();
    }
}

$page_title = $pages[$current_page]['title'] . " | " . SITE_NAME;
$page_file = $pages[$current_page]['file'];
