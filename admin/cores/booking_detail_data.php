<?php
// ═══════════════════════════════════════════════════════════
// ADMIN BOOKING DETAIL DATA CORE — VET4 HOTEL
// Fetches all data for a single booking by ID
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
require_once __DIR__ . '/../../cores/functions.php';

// Auth check
if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

// Validate booking ID
$booking_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($booking_id <= 0) {
    $_SESSION['msg_error'] = "ไม่พบรหัสการจองที่ระบุ";
    header("Location: ?page=bookings");
    exit();
}

// ─── 1. Booking Header + Customer ───
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        c.first_name, c.last_name, c.email, c.phone, c.address,
        c.emergency_contact_name, c.emergency_contact_phone,
        p.code AS promo_code, p.title AS promo_title
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    LEFT JOIN promotions p ON b.promotion_id = p.id
    WHERE b.id = :id
");
$stmt->execute([':id' => $booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['msg_error'] = "ไม่พบข้อมูลการจองหมายเลข #" . $booking_id;
    header("Location: ?page=bookings");
    exit();
}

// ─── 2. Booking Items (Rooms) ───
$stmt = $pdo->prepare("
    SELECT 
        bi.*,
        r.room_number,
        r.floor_level,
        rt.name AS room_type_name,
        rt.max_pets
    FROM booking_items bi
    JOIN rooms r ON bi.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE bi.booking_id = :booking_id
    ORDER BY bi.check_in_date ASC
");
$stmt->execute([':booking_id' => $booking_id]);
$booking_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── 3. Pets per Booking Item ───
$pets_by_item = [];
if (!empty($booking_items)) {
    $item_ids = array_column($booking_items, 'id');
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));

    $stmt = $pdo->prepare("
        SELECT 
            bip.booking_item_id,
            pet.id AS pet_id,
            pet.name AS pet_name,
            pet.is_aggressive,
            pet.behavior_note,
            pet.weight_kg,
            pet.gender,
            sp.name AS species_name,
            br.name AS breed_name
        FROM booking_item_pets bip
        JOIN pets pet ON bip.pet_id = pet.id
        JOIN species sp ON pet.species_id = sp.id
        LEFT JOIN breeds br ON pet.breed_id = br.id
        WHERE bip.booking_item_id IN ($placeholders)
        ORDER BY pet.name ASC
    ");
    $stmt->execute($item_ids);
    $all_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_pets as $p) {
        $pets_by_item[$p['booking_item_id']][] = $p;
    }
}

// ─── 4. Booking Services ───
$stmt = $pdo->prepare("
    SELECT 
        bs.*,
        s.name AS service_name,
        s.charge_type,
        pet.name AS pet_name
    FROM booking_services bs
    JOIN services s ON bs.service_id = s.id
    LEFT JOIN pets pet ON bs.pet_id = pet.id
    WHERE bs.booking_id = :booking_id
    ORDER BY s.name ASC
");
$stmt->execute([':booking_id' => $booking_id]);
$booking_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── 5. Payments ───
$stmt = $pdo->prepare("
    SELECT 
        pay.*,
        pc.name AS channel_name,
        pc.type AS channel_type,
        pc.bank_name,
        e.first_name AS verifier_first,
        e.last_name AS verifier_last
    FROM payments pay
    LEFT JOIN payment_channels pc ON pay.payment_channel_id = pc.id
    LEFT JOIN employees e ON pay.verified_by_employee_id = e.id
    WHERE pay.booking_id = :booking_id
    ORDER BY pay.created_at DESC
");
$stmt->execute([':booking_id' => $booking_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── 6. Transportation ───
$stmt = $pdo->prepare("
    SELECT * FROM pet_transportation
    WHERE booking_id = :booking_id
    ORDER BY scheduled_datetime ASC
");
$stmt->execute([':booking_id' => $booking_id]);
$transportations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── 7. Total paid amount ───
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) FROM payments 
    WHERE booking_id = :booking_id AND status = 'verified'
");
$stmt->execute([':booking_id' => $booking_id]);
$total_paid = (float) $stmt->fetchColumn();

// ─── 8. Daily Care Tasks ───
$stmt = $pdo->prepare("
    SELECT 
        dct.*,
        ctt.name AS task_type_name,
        pet.name AS pet_name,
        e.first_name AS emp_first_name
    FROM daily_care_tasks dct
    JOIN care_task_types ctt ON dct.task_type_id = ctt.id
    JOIN pets pet ON dct.pet_id = pet.id
    JOIN booking_items bi ON dct.booking_item_id = bi.id
    LEFT JOIN employees e ON dct.completed_by_employee_id = e.id
    WHERE bi.booking_id = :booking_id
    ORDER BY dct.task_date DESC, dct.status ASC
");
$stmt->execute([':booking_id' => $booking_id]);
$booking_care_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── 9. Fetch Care Task Types (For Modals) ───
$types_stmt = $pdo->query("SELECT * FROM care_task_types WHERE is_active = 1 ORDER BY name ASC");
$care_task_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Status Config ───
$status_config = [
    'pending_payment' => ['label' => 'รอชำระเงิน', 'class' => 'badge-warning', 'icon' => 'clock'],
    'verifying_payment' => ['label' => 'ตรวจสอบการชำระ', 'class' => 'badge-info', 'icon' => 'search'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'class' => 'badge-success', 'icon' => 'check-circle'],
    'checked_in' => ['label' => 'เข้าพักอยู่', 'class' => 'badge-primary', 'icon' => 'hotel'],
    'checked_out' => ['label' => 'เช็คเอาท์แล้ว', 'class' => 'badge-ghost', 'icon' => 'log-out'],
    'cancelled' => ['label' => 'ยกเลิก', 'class' => 'badge-error', 'icon' => 'x-circle'],
];

// ─── Allowed Status Transitions ───
$allowed_transitions = [
    'pending_payment' => [],
    'verifying_payment' => ['confirmed', 'cancelled'],
    'confirmed' => ['checked_in', 'cancelled'],
    'checked_in' => ['checked_out'],
    'checked_out' => [],
    'cancelled' => [],
];

$current_status = $booking['status'];
$available_actions = $allowed_transitions[$current_status] ?? [];

// Payment status config
$payment_status_config = [
    'pending' => ['label' => 'รอตรวจสอบ', 'class' => 'badge-warning'],
    'verified' => ['label' => 'ตรวจสอบแล้ว', 'class' => 'badge-success'],
    'rejected' => ['label' => 'ปฏิเสธ', 'class' => 'badge-error'],
    'refunded' => ['label' => 'คืนเงิน', 'class' => 'badge-ghost'],
];
