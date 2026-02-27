<?php
// ═══════════════════════════════════════════════════════════
// ADMIN BOOKING STATUS CHANGE — VET4 HOTEL
// Handles POST to ?action=booking_status
// ═══════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=bookings");
    exit();
}

$booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

if ($booking_id <= 0 || $new_status === '') {
    $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
    header("Location: ?page=bookings");
    exit();
}

// Fetch current booking status
$stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = :id");
$stmt->execute([':id' => $booking_id]);
$current = $stmt->fetchColumn();

if (!$current) {
    $_SESSION['msg_error'] = "ไม่พบข้อมูลการจอง";
    header("Location: ?page=bookings");
    exit();
}

// Allowed transitions
$allowed = [
    'verifying_payment' => ['confirmed', 'cancelled'],
    'confirmed' => ['checked_in', 'cancelled'],
    'checked_in' => ['checked_out'],
];

$available = $allowed[$current] ?? [];
$is_force_override = isset($_POST['force_override']) && $_POST['force_override'] === '1';

if (!$is_force_override && !in_array($new_status, $available)) {
    $_SESSION['msg_error'] = "ไม่สามารถเปลี่ยนสถานะจาก '{$current}' เป็น '{$new_status}' ได้";
    header("Location: ?page=booking_detail&id=" . $booking_id);
    exit();
}

// Update booking status
$stmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
$stmt->execute([':status' => $new_status, ':id' => $booking_id]);

// If booking is confirmed or checked_in, ensure payments are verified
if (in_array($new_status, ['confirmed', 'checked_in'])) {
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = 'verified', 
            verified_by_employee_id = :emp_id 
        WHERE booking_id = :booking_id 
          AND status = 'pending'
    ");
    $stmt->execute([
        ':emp_id' => $_SESSION['employee_id'],
        ':booking_id' => $booking_id
    ]);
}

// Status labels for flash message
$labels = [
    'confirmed' => 'ยืนยันแล้ว',
    'checked_in' => 'เช็คอิน',
    'checked_out' => 'เช็คเอาท์',
    'cancelled' => 'ยกเลิก',
];

$_SESSION['msg_success'] = "เปลี่ยนสถานะการจองเป็น \"" . ($labels[$new_status] ?? $new_status) . "\" สำเร็จ";
header("Location: ?page=booking_detail&id=" . $booking_id);
exit();
