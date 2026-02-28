<?php
// ═══════════════════════════════════════════════════════════
// ADMIN SETTINGS DATA CORE — VET4 HOTEL
// Fetches data for: Employees, Payment Channels, Seasonal
// Pricing, and Lookup Tables (medical, update, care types)
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
require_once __DIR__ . '/../../cores/functions.php';



// Admin-only access
if (!isset($_SESSION['employee_id']) || ($_SESSION['employee_role'] ?? '') !== 'admin') {
    $_SESSION['msg_error'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: ?page=home");
    exit();
}

// ─── Active Tab (persist via query param) ───
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'employees';
$valid_tabs = ['employees', 'payment_channels', 'seasonal_pricing', 'lookup_tables'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'employees';
}

// ═══════════════════════════════════════════════
// 1. EMPLOYEES
// ═══════════════════════════════════════════════
$employees = $pdo->query(
    "SELECT id, email, first_name, last_name, role, is_active, created_at
     FROM employees
     ORDER BY role DESC, is_active DESC, first_name ASC"
)->fetchAll();

$employee_stats = [
    'total' => count($employees),
    'admin' => count(array_filter($employees, fn($e) => $e['role'] === 'admin')),
    'staff' => count(array_filter($employees, fn($e) => $e['role'] === 'staff')),
    'active' => count(array_filter($employees, fn($e) => $e['is_active'])),
    'inactive' => count(array_filter($employees, fn($e) => !$e['is_active'])),
];

// ═══════════════════════════════════════════════
// 2. PAYMENT CHANNELS
// ═══════════════════════════════════════════════
$payment_channels = $pdo->query(
    "SELECT id, type, name, bank_name, account_name, account_number, icon_class, fee_percent, is_active, sort_order
     FROM payment_channels
     ORDER BY sort_order ASC, is_active DESC, name ASC"
)->fetchAll();

$channel_type_config = [
    'qr_promptpay' => ['label' => 'QR พร้อมเพย์', 'class' => 'badge-info'],
    'bank_transfer' => ['label' => 'โอนธนาคาร', 'class' => 'badge-primary'],
    'credit_card' => ['label' => 'บัตรเครดิต', 'class' => 'badge-secondary'],
    'cash' => ['label' => 'เงินสด', 'class' => 'badge-success'],
];

// ═══════════════════════════════════════════════
// 3. SEASONAL PRICING
// ═══════════════════════════════════════════════
$seasonal_pricings = $pdo->query(
    "SELECT id, season_name, start_date, end_date, price_multiplier_percent, is_active, created_at
     FROM seasonal_pricings
     ORDER BY start_date DESC"
)->fetchAll();

// ═══════════════════════════════════════════════
// 4. LOOKUP TABLES
// ═══════════════════════════════════════════════

// 4a. Medical Record Types
$medical_record_types = $pdo->query(
    "SELECT id, name, is_active FROM medical_record_types ORDER BY is_active DESC, name ASC"
)->fetchAll();

// 4b. Daily Update Types
$daily_update_types = $pdo->query(
    "SELECT id, name, icon_class, is_active FROM daily_update_types ORDER BY is_active DESC, name ASC"
)->fetchAll();

// 4c. Care Task Types
$care_task_types = $pdo->query(
    "SELECT id, name, is_active FROM care_task_types ORDER BY is_active DESC, name ASC"
)->fetchAll();
