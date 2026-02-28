<?php
// ═══════════════════════════════════════════════════════════
// ADMIN ROOM TYPES DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for room types
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
$active_filter = isset($_GET['active']) ? trim($_GET['active']) : '';

// 3. Build Base Query and Parameters
$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(rt.name LIKE :search OR rt.description LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($active_filter !== '' && $active_filter !== 'all') {
    $where_clauses[] = "rt.is_active = :is_active";
    $params[':is_active'] = (int) $active_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total Records for Pagination
$count_query = "SELECT COUNT(rt.id) FROM room_types rt WHERE {$where_sql}";
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

// 5. Fetch Room Types Data
// Adding subquery to see how many physical rooms belong to this type
$query = "SELECT 
            rt.id,
            rt.name,
            rt.description,
            rt.base_price_per_night,
            rt.max_pets,
            rt.size_sqm,
            rt.is_active,
            rt.created_at,
            rt.updated_at,
            (SELECT COUNT(r.id) FROM rooms r WHERE r.room_type_id = rt.id AND r.deleted_at IS NULL AND r.status != 'maintenance') AS active_rooms_count,
            (SELECT COUNT(r.id) FROM rooms r WHERE r.room_type_id = rt.id AND r.deleted_at IS NULL) AS total_rooms_count
          FROM room_types rt
          WHERE {$where_sql}
          ORDER BY rt.is_active DESC, rt.name ASC
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

$room_types = $stmt->fetchAll();

// 6. Aggregate Counts (for stat cards)
$stats_query = "SELECT 
                    COUNT(id) AS total,
                    SUM(is_active = 1) AS active_count,
                    SUM(is_active = 0) AS inactive_count,
                    AVG(base_price_per_night) AS avg_price
                FROM room_types";
$stats = $pdo->query($stats_query)->fetch();

// 7. Active Status configuration for UI
$active_status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    '1' => ['label' => 'เปิดใช้งาน', 'class' => 'badge-success'],
    '0' => ['label' => 'ปิดใช้งาน', 'class' => 'badge-error'],
];
