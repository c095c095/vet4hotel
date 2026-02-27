<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROCESS CUSTOMER — VET4 HOTEL
// Handles POST to ?action=customer
// ═══════════════════════════════════════════════════════════

require_once '../cores/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=customers");
    exit();
}

$customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
$customer_action = isset($_POST['customer_action']) ? trim($_POST['customer_action']) : ''; // 'ban' or 'unban'

if ($customer_id <= 0 || !in_array($customer_action, ['ban', 'unban'])) {
    $_SESSION['msg_error'] = "ข้อมูลคำขอไม่ถูกต้อง";
    header("Location: ?page=customers");
    exit();
}

try {
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT first_name, last_name, is_active FROM customers WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute([':id' => $customer_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        throw new Exception("ไม่พบข้อมูลลูกค้าในระบบ");
    }

    $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];
    $is_active = ($customer_action === 'unban') ? 1 : 0;

    // Optional: Could prevent banning if they have active bookings
    if ($customer_action === 'ban') {
        $check_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = :id AND status IN ('pending_payment', 'verifying_payment', 'confirmed', 'checked_in')");
        $check_bookings->execute([':id' => $customer_id]);
        if ($check_bookings->fetchColumn() > 0) {
            throw new Exception("ไม่สามารถระงับบัญชีลูกค้านี้ได้ เนื่องจากมีการจองที่กำลังดำเนินการอยู่");
        }
    }

    // Update customer status
    $stmt = $pdo->prepare("UPDATE customers SET is_active = :is_active, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':is_active' => $is_active,
        ':id' => $customer_id
    ]);

    if ($customer_action === 'ban') {
        $_SESSION['msg_success'] = "ระงับบัญชีลูกค้า {$customer_name} เรียบร้อยแล้ว";
    } else {
        $_SESSION['msg_success'] = "ปลดระงับบัญชีลูกค้า {$customer_name} เรียบร้อยแล้ว";
    }

} catch (Exception $e) {
    $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

header("Location: ?page=customers");
exit();
