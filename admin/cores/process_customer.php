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
$customer_action = isset($_POST['customer_action']) ? trim($_POST['customer_action']) : ''; // 'add', 'ban' or 'unban'

if (!in_array($customer_action, ['add', 'ban', 'unban'])) {
    $_SESSION['msg_error'] = "ข้อมูลคำขอไม่ถูกต้อง";
    header("Location: ?page=customers");
    exit();
}

try {
    // ─── ADD CUSTOMER ───
    if ($customer_action === 'add') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '') ?: null;
        $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '') ?: null;
        $emergency_contact_phone = trim($_POST['emergency_contact_phone'] ?? '') ?: null;

        // Validation
        $errors = [];
        if (empty($first_name))
            $errors[] = "กรุณากรอกชื่อ";
        if (empty($last_name))
            $errors[] = "กรุณากรอกนามสกุล";
        if (empty($phone))
            $errors[] = "กรุณากรอกเบอร์โทรศัพท์";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "กรุณากรอกอีเมลให้ถูกต้อง";

        // Check for duplicates
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE (email = :email OR phone = :phone) AND deleted_at IS NULL");
            $stmt->execute([':email' => $email, ':phone' => $phone]);
            if ($stmt->fetch()) {
                $errors[] = "อีเมลหรือเบอร์โทรศัพท์นี้มีในระบบแล้ว";
            }
        }

        if (!empty($errors)) {
            $_SESSION['msg_error'] = implode("<br>", $errors);
            header("Location: ?page=customers");
            exit();
        }

        // Generate password hash (using phone number as default for the first login)
        $password_hash = password_hash($phone, PASSWORD_DEFAULT);

        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO customers (
                first_name, last_name, phone, email, password_hash, address, 
                emergency_contact_name, emergency_contact_phone, is_active, created_at, updated_at
            ) VALUES (
                :first_name, :last_name, :phone, :email, :password_hash, :address,
                :emergency_contact_name, :emergency_contact_phone, 1, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':phone' => $phone,
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':address' => $address,
            ':emergency_contact_name' => $emergency_contact_name,
            ':emergency_contact_phone' => $emergency_contact_phone,
        ]);

        $_SESSION['msg_success'] = "เพิ่มลูกค้าใหม่เรียบร้อยแล้ว (รหัสผ่านเริ่มต้นคือเบอร์โทรศัพท์)";
        header("Location: ?page=customers");
        exit();
    }

    // ─── BAN / UNBAN EXISTING CUSTOMERS ───
    if ($customer_id <= 0) {
        throw new Exception("ไม่พบข้อมูลรหัสลูกค้าสำหรับการอัปเดตสถานะ");
    }

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
