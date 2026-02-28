<?php
// ═══════════════════════════════════════════════════════════
// ADMIN SERVICES DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for services list
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
$charge_type_filter = isset($_GET['charge_type']) ? trim($_GET['charge_type']) : '';
$active_filter = isset($_GET['active']) ? trim($_GET['active']) : '';

// 3. Build Base Query and Parameters
$where_clauses = ["s.deleted_at IS NULL"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(s.name LIKE :search OR s.description LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($charge_type_filter !== '' && $charge_type_filter !== 'all') {
    $where_clauses[] = "s.charge_type = :charge_type";
    $params[':charge_type'] = $charge_type_filter;
}

if ($active_filter !== '' && $active_filter !== 'all') {
    $where_clauses[] = "s.is_active = :is_active";
    $params[':is_active'] = (int) $active_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total Records for Pagination
$count_query = "SELECT COUNT(s.id) FROM services s WHERE {$where_sql}";
$stmt = $pdo->prepare($count_query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 5. Fetch Services Data with usage count
$query = "SELECT 
            s.id,
            s.name,
            s.description,
            s.price,
            s.charge_type,
            s.is_active,
            s.deleted_at,
            (SELECT COUNT(bs.id) 
             FROM booking_services bs 
             JOIN bookings b ON b.id = bs.booking_id 
             WHERE bs.service_id = s.id 
             AND b.status IN ('confirmed', 'checked_in')
            ) AS active_usage_count
          FROM services s
          WHERE {$where_sql}
          ORDER BY s.is_active DESC, s.name ASC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$services = $stmt->fetchAll();

// 6. Aggregate Counts (for stat cards and filter badges)
$stats_query = "SELECT 
                    COUNT(id) AS total,
                    SUM(is_active = 1) AS active_count,
                    SUM(is_active = 0) AS inactive_count,
                    SUM(charge_type = 'per_stay') AS per_stay_count,
                    SUM(charge_type = 'per_night') AS per_night_count,
                    SUM(charge_type = 'per_pet') AS per_pet_count
                FROM services 
                WHERE deleted_at IS NULL";
$stats = $pdo->query($stats_query)->fetch();

// 7. Count services currently in use (active bookings)
$in_use_query = "SELECT COUNT(DISTINCT bs.service_id) 
                 FROM booking_services bs 
                 JOIN bookings b ON b.id = bs.booking_id 
                 WHERE b.status IN ('confirmed', 'checked_in')";
$in_use_count = $pdo->query($in_use_query)->fetchColumn();

// 8. Charge type configuration for UI
$charge_type_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'per_stay' => ['label' => 'ต่อการเข้าพัก', 'class' => 'badge-info'],
    'per_night' => ['label' => 'ต่อคืน', 'class' => 'badge-primary'],
    'per_pet' => ['label' => 'ต่อตัว', 'class' => 'badge-secondary'],
];

$active_status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    '1' => ['label' => 'เปิดใช้งาน', 'class' => 'badge-success'],
    '0' => ['label' => 'ปิดใช้งาน', 'class' => 'badge-error'],
];
