<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PAYMENTS DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for payments list
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
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';

// 3. Build Base Query and Parameters
$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(b.booking_ref LIKE :search OR c.first_name LIKE :search OR c.last_name LIKE :search OR c.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($status_filter !== '' && $status_filter !== 'all') {
    $where_clauses[] = "p.status = :status";
    $params[':status'] = $status_filter;
}

if ($type_filter !== '' && $type_filter !== 'all') {
    $where_clauses[] = "p.payment_type = :type";
    $params[':type'] = $type_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total Records for Pagination
$count_query = "SELECT COUNT(p.id) 
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
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

// 5. Fetch Payments Data
$query = "SELECT 
            p.id, 
            p.amount, 
            p.payment_type, 
            p.status, 
            p.created_at,
            p.proof_image_url,
            p.transaction_ref,
            b.id AS booking_id,
            b.booking_ref, 
            b.status AS booking_status,
            c.id AS customer_id,
            c.first_name, 
            c.last_name, 
            c.phone,
            pc.name AS channel_name,
            pc.type AS channel_type,
            e.first_name AS verifier_first_name,
            e.last_name AS verifier_last_name
          FROM payments p
          JOIN bookings b ON p.booking_id = b.id
          JOIN customers c ON b.customer_id = c.id
          LEFT JOIN payment_channels pc ON p.payment_channel_id = pc.id
          LEFT JOIN employees e ON p.verified_by_employee_id = e.id
          WHERE {$where_sql}
          ORDER BY p.created_at DESC
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

$payments = $stmt->fetchAll();

// 6. Aggregate Status Counts (for filter badges)
$status_counts = [];
$status_query = "SELECT status, COUNT(id) as count FROM payments GROUP BY status";
$stmt = $pdo->query($status_query);
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}
$status_counts['all'] = array_sum($status_counts);

// Setup status config for UI
$status_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'pending' => ['label' => 'รอตรวจสอบ', 'class' => 'badge-warning'],
    'verified' => ['label' => 'ตรวจสอบแล้ว', 'class' => 'badge-success'],
    'rejected' => ['label' => 'ปฏิเสธ', 'class' => 'badge-error'],
    'refunded' => ['label' => 'คืนเงินแล้ว', 'class' => 'badge-info']
];

$type_config = [
    'deposit' => ['label' => 'มัดจำ', 'class' => 'badge-primary'],
    'full_payment' => ['label' => 'จ่ายเต็มจำนวน', 'class' => 'badge-primary'],
    'balance_due' => ['label' => 'จ่ายส่วนที่เหลือ', 'class' => 'badge-secondary'],
    'extra_charge' => ['label' => 'ค่าใช้จ่ายเพิ่มเติม', 'class' => 'badge-accent']
];

// Helper Function for Payment Type Badge
function payment_type_badge_ui($type)
{
    global $type_config;
    $config = $type_config[$type] ?? ['label' => $type, 'class' => 'badge-ghost'];
    return "<div class='badge {$config['class']} badge-sm'>{$config['label']}</div>";
}

// Helper Function for Payment Status Badge
function payment_status_badge_ui($status)
{
    global $status_config;
    $config = $status_config[$status] ?? ['label' => $status, 'class' => 'badge-ghost'];

    $icon = '';
    if ($status === 'verified')
        $icon = '<i data-lucide="check-circle-2" class="size-3 mr-1"></i>';
    if ($status === 'pending')
        $icon = '<i data-lucide="clock" class="size-3 mr-1"></i>';
    if ($status === 'rejected')
        $icon = '<i data-lucide="x-circle" class="size-3 mr-1"></i>';

    return "<div class='badge {$config['class']} badge-sm font-medium border-0'>{$icon}{$config['label']}</div>";
}
?>