<?php
// ═══════════════════════════════════════════════════════════
// PROCESS PROFILE — VET4 HOTEL
// จัดการอัปเดตข้อมูลส่วนตัว + เปลี่ยนรหัสผ่าน
// ═══════════════════════════════════════════════════════════

if (!isset($pdo))
    exit('No direct access allowed.');

if (session_status() === PHP_SESSION_NONE)
    session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=profile');
    exit();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: ?page=login');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$update_type = $_POST['update_type'] ?? '';

// ─────────────────────────────────────────────────────────
// 1. UPDATE PERSONAL INFO
// ─────────────────────────────────────────────────────────

if ($update_type === 'personal_info') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($phone)) {
        $_SESSION['msg_error'] = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
        header('Location: ?page=profile');
        exit();
    }

    if (!preg_match('/^[0-9]{9,10}$/', $phone)) {
        $_SESSION['msg_error'] = 'เบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นตัวเลข 9-10 หลัก)';
        header('Location: ?page=profile');
        exit();
    }

    // Check phone uniqueness (exclude current user)
    try {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND id != ? LIMIT 1");
        $stmt->execute([$phone, $customer_id]);
        if ($stmt->fetch()) {
            $_SESSION['msg_error'] = 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว';
            header('Location: ?page=profile');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล';
        header('Location: ?page=profile');
        exit();
    }

    // Update
    try {
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET first_name = ?, last_name = ?, phone = ?, address = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$first_name, $last_name, $phone, $address ?: null, $customer_id]);

        // Update session name
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;

        $_SESSION['msg_success'] = 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว';
        header('Location: ?page=profile');
        exit();
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองอีกครั้ง';
        header('Location: ?page=profile');
        exit();
    }
}

// ─────────────────────────────────────────────────────────
// 2. UPDATE EMERGENCY CONTACT
// ─────────────────────────────────────────────────────────

if ($update_type === 'emergency_contact') {
    $ec_name = trim($_POST['emergency_contact_name'] ?? '');
    $ec_phone = trim($_POST['emergency_contact_phone'] ?? '');

    // Validate phone format if provided
    if (!empty($ec_phone) && !preg_match('/^[0-9]{9,10}$/', $ec_phone)) {
        $_SESSION['msg_error'] = 'เบอร์ผู้ติดต่อฉุกเฉินไม่ถูกต้อง (ต้องเป็นตัวเลข 9-10 หลัก)';
        header('Location: ?page=profile');
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET emergency_contact_name = ?, emergency_contact_phone = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ec_name ?: null, $ec_phone ?: null, $customer_id]);

        $_SESSION['msg_success'] = 'อัปเดตผู้ติดต่อฉุกเฉินเรียบร้อยแล้ว';
        header('Location: ?page=profile');
        exit();
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองอีกครั้ง';
        header('Location: ?page=profile');
        exit();
    }
}

// ─────────────────────────────────────────────────────────
// 3. CHANGE PASSWORD
// ─────────────────────────────────────────────────────────

if ($update_type === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['msg_error'] = 'กรุณากรอกรหัสผ่านให้ครบทุกช่อง';
        header('Location: ?page=profile');
        exit();
    }

    if (mb_strlen($new_password) < 6) {
        $_SESSION['msg_error'] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
        header('Location: ?page=profile');
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['msg_error'] = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
        header('Location: ?page=profile');
        exit();
    }

    // Verify current password
    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($current_password, $row['password_hash'])) {
            $_SESSION['msg_error'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            header('Location: ?page=profile');
            exit();
        }

        // Update with new hash
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE customers SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_hash, $customer_id]);

        $_SESSION['msg_success'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
        header('Location: ?page=profile');
        exit();

    } catch (PDOException $e) {
        $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน กรุณาลองอีกครั้ง';
        header('Location: ?page=profile');
        exit();
    }
}

// Fallback — unknown update_type
$_SESSION['msg_error'] = 'คำขอไม่ถูกต้อง';
header('Location: ?page=profile');
exit();
