<?php
// ═══════════════════════════════════════════════════════════
// CUSTOMER REFUND PROCESSOR — VET4 HOTEL
// Handles refund requests from customer booking detail page
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['customer_id'];
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $refund_amount = floatval($_POST['refund_amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    $redirect = "?page=booking_detail&id=" . $booking_id;

    // Basic validation
    if ($booking_id <= 0 || $payment_id <= 0 || $refund_amount <= 0) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง กรุณาลองใหม่";
        header("Location: " . $redirect);
        exit();
    }

    if (empty($reason)) {
        $_SESSION['msg_error'] = "กรุณาระบุเหตุผลในการขอคืนเงิน";
        header("Location: " . $redirect);
        exit();
    }

    try {
        // 1. Verify booking ownership and status
        $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND customer_id = ?");
        $stmt->execute([$booking_id, $customer_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $_SESSION['msg_error'] = "ไม่พบข้อมูลการจองนี้";
            header("Location: ?page=booking_history");
            exit();
        }

        if ($booking['status'] !== 'cancelled') {
            $_SESSION['msg_error'] = "สามารถขอคืนเงินได้เฉพาะการจองที่ถูกยกเลิกแล้วเท่านั้น";
            header("Location: " . $redirect);
            exit();
        }

        // 2. Verify payment belongs to this booking and is verified
        $stmt = $pdo->prepare("SELECT id, amount, status FROM payments WHERE id = ? AND booking_id = ? AND status = 'verified'");
        $stmt->execute([$payment_id, $booking_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            $_SESSION['msg_error'] = "ไม่พบรายการชำระเงินที่สามารถขอคืนได้";
            header("Location: " . $redirect);
            exit();
        }

        // 3. Check existing refunds for this payment
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(refund_amount), 0) FROM refunds WHERE payment_id = ? AND status IN ('pending', 'processed')");
        $stmt->execute([$payment_id]);
        $already_refunded = (float) $stmt->fetchColumn();

        $max_refundable = $payment['amount'] - $already_refunded;

        if ($refund_amount > $max_refundable) {
            $_SESSION['msg_error'] = "ยอดคืนเงินเกินจำนวนที่สามารถคืนได้ (สูงสุด ฿" . number_format($max_refundable, 2) . ")";
            header("Location: " . $redirect);
            exit();
        }

        // 4. Check no duplicate pending refund by this customer for same payment
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM refunds WHERE payment_id = ? AND booking_id = ? AND status = 'pending'");
        $stmt->execute([$payment_id, $booking_id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $_SESSION['msg_error'] = "คุณมีคำร้องขอคืนเงินสำหรับรายการนี้อยู่แล้ว กรุณารอการดำเนินการ";
            header("Location: " . $redirect);
            exit();
        }

        // 5. Insert refund request
        $stmt = $pdo->prepare("INSERT INTO refunds (payment_id, booking_id, refund_amount, refund_type, reason, status) VALUES (?, ?, ?, 'cash', ?, 'pending')");
        $stmt->execute([$payment_id, $booking_id, $refund_amount, $reason]);

        $_SESSION['msg_success'] = "ส่งคำร้องขอคืนเงินสำเร็จ ทีมงานจะดำเนินการโดยเร็ว";

    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
    }

    header("Location: " . $redirect);
    exit();
}

// If not POST, redirect
header("Location: ?page=booking_history");
exit();
