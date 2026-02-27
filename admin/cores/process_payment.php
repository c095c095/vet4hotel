<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROCESS PAYMENT — VET4 HOTEL
// Handles POST to ?action=payment
// ═══════════════════════════════════════════════════════════

require_once '../cores/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=payments");
    exit();
}

$payment_id = isset($_POST['payment_id']) ? (int) $_POST['payment_id'] : 0;
$payment_action = isset($_POST['payment_action']) ? trim($_POST['payment_action']) : ''; // 'verify' or 'reject'

if ($payment_id <= 0 || !in_array($payment_action, ['verify', 'reject'])) {
    $_SESSION['msg_error'] = "ข้อมูลคำขอไม่ถูกต้อง";
    header("Location: ?page=payments");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Fetch current payment and associated booking
    $stmt = $pdo->prepare("
        SELECT p.status AS payment_status, p.amount, p.booking_id, b.status AS booking_status
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        WHERE p.id = :id FOR UPDATE
    ");
    $stmt->execute([':id' => $payment_id]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception("ไม่พบข้อมูลการชำระเงินในระบบ");
    }

    if ($payment['payment_status'] !== 'pending') {
        throw new Exception("ไม่สามารถดำเนินการได้ เนื่องจากมีการจัดการสำเร็จไปแล้ว (สถานะปัจจุบัน: " . $payment['payment_status'] . ")");
    }

    $new_status = ($payment_action === 'verify') ? 'verified' : 'rejected';

    // 2. Update payment status
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = :status, verified_by_employee_id = :emp_id, updated_at = NOW() 
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $new_status,
        ':emp_id' => $_SESSION['employee_id'],
        ':id' => $payment_id
    ]);

    // 3. Update booking status if payment is verified
    // We only bump 'pending_payment' or 'verifying_payment' up to 'confirmed'
    if ($new_status === 'verified' && in_array($payment['booking_status'], ['pending_payment', 'verifying_payment'])) {
        // Calculate total verified payments for this booking
        $stmt_total = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE booking_id = :booking_id AND status = 'verified'");
        $stmt_total->execute([':booking_id' => $payment['booking_id']]);
        $total_paid = $stmt_total->fetchColumn();

        // Get booking net_amount
        $stmt_booking = $pdo->prepare("SELECT net_amount FROM bookings WHERE id = :booking_id");
        $stmt_booking->execute([':booking_id' => $payment['booking_id']]);
        $booking_amount = $stmt_booking->fetchColumn();

        // Let's assume minimum required to confirm is 50% deposit
        $min_required = $booking_amount * 0.5;

        // Note: For partial / extra payments we'd do a more complex check, but bumping status generally is fine
        // if they met the required amount threshold or it's just considered 'verified' deposit.
        if ($total_paid >= $min_required) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', updated_at = NOW() WHERE id = :booking_id");
            $stmt->execute([':booking_id' => $payment['booking_id']]);
            $booking_updated = true;
        }
    } else if ($new_status === 'rejected' && $payment['booking_status'] === 'verifying_payment') {
        // Look if there are still other pending payments, if not maybe revert booking status back to 'pending_payment' or 'cancelled' if expired.
        // For safe logic, switch back down to pending_payment
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE booking_id = :booking_id AND status = 'pending'");
        $stmt_check->execute([':booking_id' => $payment['booking_id']]);
        $has_pending = $stmt_check->fetchColumn() > 0;

        if (!$has_pending) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'pending_payment', updated_at = NOW() WHERE id = :booking_id");
            $stmt->execute([':booking_id' => $payment['booking_id']]);
        }
    }

    $pdo->commit();

    if ($new_status === 'verified') {
        $_SESSION['msg_success'] = "ตรวจสอบและยืนยันการชำระเงิน รหัสอ้างอิง #" . $payment_id . " สำเร็จ";
    } else {
        $_SESSION['msg_success'] = "ปฏิเสธการชำระเงิน รหัสอ้างอิง #" . $payment_id . " แล้ว";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

header("Location: ?page=payments");
exit();
