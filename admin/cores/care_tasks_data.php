<?php
// ═══════════════════════════════════════════════════════════
// ADMIN CARE TASKS DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for care tasks list
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
$task_type_filter = isset($_GET['task_type']) ? trim($_GET['task_type']) : '';
$date_filter = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d'); // Default to today

// 3. Fetch Task Types for options
$types_stmt = $pdo->query("SELECT * FROM care_task_types WHERE is_active = 1 ORDER BY name ASC");
$care_task_types = $types_stmt->fetchAll();

// 4. Build Base Query and Parameters
$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(dct.description LIKE :search OR p.name LIKE :search OR b.booking_ref LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status_filter !== '' && $status_filter !== 'all') {
    $where_clauses[] = "dct.status = :status";
    $params[':status'] = $status_filter;
}

if ($task_type_filter !== '' && $task_type_filter !== 'all') {
    $where_clauses[] = "dct.task_type_id = :task_type";
    $params[':task_type'] = (int) $task_type_filter;
}

if ($date_filter !== '' && $date_filter !== 'all') {
    $where_clauses[] = "dct.task_date = :task_date";
    $params[':task_date'] = $date_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 5. Count Total Records for Pagination
$count_query = "
    SELECT COUNT(dct.id)
    FROM daily_care_tasks dct
    JOIN nets_pet p ON dct.pet_id = p.id
    WAIT A MINUTE! THE TABLE IS pets p! Let me correct: -> JOIN pets p ON dct.pet_id = p.id
    JOIN booking_items bi ON dct.booking_item_id = bi.id
    JOIN bookings b ON bi.booking_id = b.id
    WHERE {$where_sql}";

$count_query = "
    SELECT COUNT(dct.id)
    FROM daily_care_tasks dct
    JOIN pets p ON dct.pet_id = p.id
    JOIN booking_items bi ON dct.booking_item_id = bi.id
    JOIN bookings b ON bi.booking_id = b.id
    WHERE {$where_sql}";

$stmt = $pdo->prepare($count_query);
foreach ($params as $key => $val) {
    if (is_int($val))
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    else
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 6. Fetch Tasks Data
$query = "
    SELECT 
        dct.*,
        ctt.name AS task_type_name,
        p.name AS pet_name,
        p.weight_kg,
        p.is_aggressive,
        sp.name AS species_name,
        b.id AS booking_id,
        b.booking_ref,
        r.room_number,
        e.first_name AS emp_first_name,
        e.last_name AS emp_last_name
    FROM daily_care_tasks dct
    JOIN care_task_types ctt ON dct.task_type_id = ctt.id
    JOIN pets p ON dct.pet_id = p.id
    LEFT JOIN species sp ON p.species_id = sp.id
    JOIN booking_items bi ON dct.booking_item_id = bi.id
    JOIN bookings b ON bi.booking_id = b.id
    JOIN rooms r ON bi.room_id = r.id
    LEFT JOIN employees e ON dct.completed_by_employee_id = e.id
    WHERE {$where_sql}
    ORDER BY dct.status ASC, dct.task_date ASC, p.name ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    if (is_int($val))
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    else
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$tasks = $stmt->fetchAll();

// 7. Aggregate Counts for the currently selected date or overall if 'all' dates
$stats_query = "
    SELECT 
        COUNT(dct.id) AS total,
        SUM(dct.status = 'pending') AS pending_count,
        SUM(dct.status = 'completed') AS completed_count
    FROM daily_care_tasks dct
";
if ($date_filter !== 'all' && $date_filter !== '') {
    $stats_query .= " WHERE dct.task_date = :date_filter";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute([':date_filter' => $date_filter]);
} else {
    $stats_stmt = $pdo->query($stats_query);
}
$stats = $stats_stmt->fetch();

// 8. Status configuration for UI
$status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'pending' => ['label' => 'รอดำเนินการ', 'class' => 'badge-warning', 'icon' => 'clock'],
    'completed' => ['label' => 'เสร็จสิ้น', 'class' => 'badge-success', 'icon' => 'check-circle'],
];
