<?php
// ═══════════════════════════════════════════════════════════
// ADMIN ROOMS DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for rooms list
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
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? (int) $_GET['type'] : 0;

// 3. Build Base Query and Parameters
$where_clauses = ["r.deleted_at IS NULL"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(r.room_number LIKE :search OR rt.name LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status_filter !== '' && $status_filter !== 'all') {
    $where_clauses[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if ($type_filter > 0) {
    $where_clauses[] = "r.room_type_id = :type_id";
    $params[':type_id'] = $type_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total Records for Pagination
$count_query = "SELECT COUNT(r.id) 
                FROM rooms r
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE {$where_sql}";
$stmt = $pdo->prepare($count_query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 5. Fetch Rooms Data
$query = "SELECT 
            r.id,
            r.room_number,
            r.room_type_id,
            r.floor_level,
            r.status,
            r.cctv_url,
            r.created_at,
            rt.name AS type_name,
            rt.base_price_per_night,
            rt.max_pets,
            rt.size_sqm
          FROM rooms r
          JOIN room_types rt ON r.room_type_id = rt.id
          WHERE {$where_sql}
          ORDER BY r.room_number ASC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rooms = $stmt->fetchAll();

// 6. Aggregate Status Counts (for filter badges)
$status_counts = [];
$status_query = "SELECT status, COUNT(id) as count FROM rooms WHERE deleted_at IS NULL GROUP BY status";
$stmt = $pdo->query($status_query);
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}
$status_counts['all'] = array_sum($status_counts);

// 7. Fetch Room Types (for filter dropdown and create/edit form)
$room_types = $pdo->query("SELECT id, name, base_price_per_night, max_pets, size_sqm FROM room_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// 8. Status configuration for UI
$room_status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'active' => ['label' => 'พร้อมใช้งาน', 'class' => 'badge-success'],
    'maintenance' => ['label' => 'ซ่อมบำรุง', 'class' => 'badge-warning'],
    'out_of_service' => ['label' => 'ปิดให้บริการ', 'class' => 'badge-error'],
];

// 9. Count rooms currently occupied (have active bookings today)
$today = date('Y-m-d');
$occupied_query = "SELECT COUNT(DISTINCT bi.room_id) 
                   FROM booking_items bi 
                   JOIN bookings b ON b.id = bi.booking_id 
                   WHERE bi.check_in_date <= :today 
                   AND bi.check_out_date > :today 
                   AND b.status IN ('confirmed', 'checked_in')";
$stmt = $pdo->prepare($occupied_query);
$stmt->execute([':today' => $today]);
$occupied_count = $stmt->fetchColumn();
