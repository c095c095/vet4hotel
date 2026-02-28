<?php
// ═══════════════════════════════════════════════════════════
// REFUNDS PROCESSOR - VET4 HOTEL ADMIN
// Handles processing refunds and credit notes
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_action = $_POST['sub_action'] ?? '';

    if ($sub_action === 'process_refund') {
        $refund_id = intval($_POST['refund_id']);
        $status = trim($_POST['status']); // processed or failed

        if (in_array($status, ['processed', 'failed'])) {
            try {
                $pdo->beginTransaction();

                // Update refund table
                $stmt = $pdo->prepare("UPDATE refunds SET status = ?, processed_by_employee_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$status, $_SESSION['employee_id'], $refund_id]);

                // Update original payment status if processed
                if ($status === 'processed') {
                    $stmt_pay = $pdo->prepare("SELECT payment_id FROM refunds WHERE id = ?");
                    $stmt_pay->execute([$refund_id]);
                    $refund = $stmt_pay->fetch();

                    if ($refund) {
                        $stmt_upd = $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE id = ? AND status = 'verified'");
                        $stmt_upd->execute([$refund['payment_id']]);
                    }
                }

                $pdo->commit();
                $_SESSION['msg_success'] = "ดำเนินการเรื่องขอคืนเงินสำเร็จ";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        }
    } elseif ($sub_action === 'request_refund') {
        // This is usually triggered from booking_detail or payments page
        $payment_id = intval($_POST['payment_id']);
        $booking_id = intval($_POST['booking_id']);
        $refund_amount = floatval($_POST['refund_amount']);
        $refund_type = trim($_POST['refund_type']);
        $reason = trim($_POST['reason']);

        try {
            $stmt = $pdo->prepare("INSERT INTO refunds (payment_id, booking_id, refund_amount, refund_type, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$payment_id, $booking_id, $refund_amount, $refund_type, $reason]);

            $_SESSION['msg_success'] = "เปิดคำร้องขอคืนเงินสำเร็จ รอดำเนินการ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }

        $redirect = $_POST['redirect_to'] ?? './?page=refunds';
        header("Location: " . $redirect);
        exit();
    }

    header("Location: ./?page=refunds");
    exit();
}
