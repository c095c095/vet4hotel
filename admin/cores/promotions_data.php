<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROMOTIONS DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for promotions list
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
require_once __DIR__ . '/../../cores/functions.php';

// Check if admin/staff is logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

// 1. Pagination Setup
$limit = 15;
$page = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// 2. Filters Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$discount_type_filter = isset($_GET['discount_type']) ? trim($_GET['discount_type']) : '';
$active_filter = isset($_GET['active']) ? trim($_GET['active']) : '';

// 3. Build Base Query and Parameters
$where_clauses = ["1=1"]; // No deleted_at in promotions table
$params = [];

if ($search !== '') {
    $where_clauses[] = "(p.code LIKE :search OR p.title LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($discount_type_filter !== '' && $discount_type_filter !== 'all') {
    $where_clauses[] = "p.discount_type = :discount_type";
    $params[':discount_type'] = $discount_type_filter;
}

if ($active_filter !== '' && $active_filter !== 'all') {
    $where_clauses[] = "p.is_active = :is_active";
    $params[':is_active'] = (int) $active_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total Records for Pagination
$count_query = "SELECT COUNT(p.id) FROM promotions p WHERE {$where_sql}";
$stmt = $pdo->prepare($count_query);
foreach ($params as $key => $val) {
    if (is_int($val)) {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 5. Fetch Promotions Data
$query = "SELECT 
            p.id,
            p.code,
            p.title,
            p.discount_type,
            p.discount_value,
            p.max_discount_amount,
            p.min_booking_amount,
            p.usage_limit,
            p.used_count,
            p.start_date,
            p.end_date,
            p.is_active,
            p.created_at
          FROM promotions p
          WHERE {$where_sql}
          ORDER BY p.is_active DESC, p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    if (is_int($val)) {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$promotions = $stmt->fetchAll();

// 6. Aggregate Counts (for stat cards and filter badges)
$stats_query = "SELECT 
                    COUNT(id) AS total,
                    SUM(is_active = 1) AS active_count,
                    SUM(is_active = 0) AS inactive_count,
                    SUM(discount_type = 'percentage') AS percentage_count,
                    SUM(discount_type = 'fixed_amount') AS fixed_amount_count
                FROM promotions";
$stats = $pdo->query($stats_query)->fetch();

// 7. Discount type configuration for UI
$discount_type_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'percentage' => ['label' => 'เปอร์เซ็นต์', 'class' => 'badge-info'],
    'fixed_amount' => ['label' => 'จำนวนเงิน', 'class' => 'badge-primary'],
];

$active_status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    '1' => ['label' => 'เปิดใช้งาน', 'class' => 'badge-success'],
    '0' => ['label' => 'ปิดใช้งาน', 'class' => 'badge-error'],
];
