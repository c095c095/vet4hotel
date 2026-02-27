<?php
// ═══════════════════════════════════════════════════════════
// ADMIN CUSTOMERS DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for customers list
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

// 3. Build WHERE Clauses
$where_clauses = ["c.deleted_at IS NULL"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(c.first_name LIKE :search OR c.last_name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status_filter === 'active') {
    $where_clauses[] = "c.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_clauses[] = "c.is_active = 0";
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total for Pagination
$count_query = "SELECT COUNT(c.id) FROM customers c WHERE {$where_sql}";
$stmt = $pdo->prepare($count_query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 5. Fetch Customers Data with aggregated stats
$query = "SELECT 
            c.id, 
            c.first_name, 
            c.last_name, 
            c.email, 
            c.phone,
            c.address,
            c.emergency_contact_name,
            c.emergency_contact_phone,
            c.is_active, 
            c.created_at,
            (SELECT COUNT(id) FROM pets WHERE customer_id = c.id AND deleted_at IS NULL) AS pet_count,
            (SELECT COUNT(id) FROM bookings WHERE customer_id = c.id) AS booking_count,
            (SELECT COALESCE(SUM(net_amount), 0) FROM bookings WHERE customer_id = c.id AND status NOT IN ('cancelled')) AS total_spent,
            (SELECT MAX(created_at) FROM bookings WHERE customer_id = c.id) AS last_booking_at
          FROM customers c
          WHERE {$where_sql}
          ORDER BY c.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

// 6. Fetch Pets for each customer (for detail modal)
$customer_pets = [];
if (!empty($customers)) {
    $cust_ids = array_column($customers, 'id');
    $placeholders = implode(',', array_fill(0, count($cust_ids), '?'));
    $pets_query = "SELECT p.id, p.name, p.customer_id, p.gender, p.is_aggressive,
                          s.name AS species_name, b.name AS breed_name
                   FROM pets p
                   LEFT JOIN species s ON p.species_id = s.id
                   LEFT JOIN breeds b ON p.breed_id = b.id
                   WHERE p.customer_id IN ({$placeholders}) AND p.deleted_at IS NULL
                   ORDER BY p.name ASC";
    $stmt = $pdo->prepare($pets_query);
    $stmt->execute($cust_ids);
    while ($row = $stmt->fetch()) {
        $customer_pets[$row['customer_id']][] = $row;
    }
}

// 7. Status Counts (for filter badges)
$status_counts = [];
$sq = $pdo->query("SELECT is_active, COUNT(id) AS cnt FROM customers WHERE deleted_at IS NULL GROUP BY is_active");
while ($row = $sq->fetch()) {
    if ($row['is_active'] == 1) {
        $status_counts['active'] = $row['cnt'];
    } else {
        $status_counts['inactive'] = $row['cnt'];
    }
}
$status_counts['all'] = ($status_counts['active'] ?? 0) + ($status_counts['inactive'] ?? 0);

// 8. Summary Stats
$stats = [];

// Total customers
$stats['total'] = $status_counts['all'];

// New customers this month
$stmt = $pdo->query("SELECT COUNT(id) FROM customers WHERE deleted_at IS NULL AND MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
$stats['new_this_month'] = $stmt->fetchColumn();

// Customers with active bookings (checked_in)
$stmt = $pdo->query("SELECT COUNT(DISTINCT customer_id) FROM bookings WHERE status = 'checked_in'");
$stats['with_active_stays'] = $stmt->fetchColumn();

// Status config for filter UI
$status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'active' => ['label' => 'ใช้งาน', 'class' => 'badge-success'],
    'inactive' => ['label' => 'ปิดการใช้งาน', 'class' => 'badge-error'],
];
