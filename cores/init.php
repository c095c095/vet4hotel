<?php

session_start();
date_default_timezone_set("Asia/Bangkok");

require_once 'config.php';
require_once 'routes.php';
require_once 'db.php';
require_once 'functions.php';

$p = isset($_GET['page']) ? $_GET['page'] : 'home';

if (!array_key_exists($p, $pages)) {
    $p = '404';
}

$currentPage = $pages[$p];
