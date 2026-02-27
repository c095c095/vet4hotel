<?php
// ═══════════════════════════════════════════════════════════
// ADMIN BOOKINGS DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for bookings list
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
$limit = 15; // Items per page
$page = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// 2. Filters Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? trim($_GET['date']) : '';

// 3. Build Base Query and Parameters
$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(b.booking_ref LIKE :search OR c.first_name LIKE :search OR c.last_name LIKE :search OR c.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status_filter !== '' && $status_filter !== 'all') {
    $where_clauses[] = "b.status = :status";
    $params[':status'] = $status_filter;
}

if ($date_filter !== '') {
    // If date is provided, filter bookings where any booking item overlaps with the date
    $where_clauses[] = "b.id IN (SELECT booking_id FROM booking_items WHERE :date BETWEEN check_in_date AND check_out_date)";
    $params[':date'] = $date_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total Records for Pagination
$count_query = "SELECT COUNT(b.id) 
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                WHERE {$where_sql}";
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

// 5. Fetch Bookings Data
$query = "SELECT 
            b.id, 
            b.booking_ref, 
            b.net_amount, 
            b.status, 
            b.created_at,
            c.id as customer_id,
            c.first_name, 
            c.last_name, 
            c.phone,
            (SELECT COUNT(id) FROM booking_items WHERE booking_id = b.id) as room_count,
            (SELECT MIN(check_in_date) FROM booking_items WHERE booking_id = b.id) as first_checkin,
            (SELECT MAX(check_out_date) FROM booking_items WHERE booking_id = b.id) as last_checkout
          FROM bookings b
          JOIN customers c ON b.customer_id = c.id
          WHERE {$where_sql}
          ORDER BY b.created_at DESC
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

$bookings = $stmt->fetchAll();

// 6. Aggregate Status Counts (for filter badges)
$status_counts = [];
$status_query = "SELECT status, COUNT(id) as count FROM bookings GROUP BY status";
$stmt = $pdo->query($status_query);
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}
// Also count all
$status_counts['all'] = array_sum($status_counts);

// Calculate active checkins for today just in case
$active_checkins_query = "SELECT COUNT(DISTINCT b.id) FROM bookings b JOIN booking_items bi ON b.id = bi.booking_id WHERE bi.check_in_date <= CURRENT_DATE AND bi.check_out_date >= CURRENT_DATE AND b.status IN ('confirmed', 'checked_in')";
$active_checkins = $pdo->query($active_checkins_query)->fetchColumn();

// Setup status config for UI
$status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'pending_payment' => ['label' => 'รอชำระเงิน', 'class' => 'badge-warning'],
    'verifying_payment' => ['label' => 'ตรวจสอบการชำระ', 'class' => 'badge-info'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'class' => 'badge-success'],
    'checked_in' => ['label' => 'เข้าพักอยู่', 'class' => 'badge-primary'],
    'checked_out' => ['label' => 'เช็คเอาท์แล้ว', 'class' => 'badge-ghost'],
    'cancelled' => ['label' => 'ยกเลิก', 'class' => 'badge-error']
];
