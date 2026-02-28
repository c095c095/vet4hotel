<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROFILE PROCESSOR — VET4 HOTEL
// Handles updating personal details and changing password
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    require_once __DIR__ . '/init.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=profile");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    $_SESSION['msg_error'] = "กรุณาเข้าสู่ระบบ";
    header("Location: ?page=login");
    exit();
}

$employee_id = (int) $_SESSION['employee_id'];

// We determine the action from a hidden input or URL parameter depending on how the form is submitted.
// Since the user asked to use <form action="?action=profile">, we'll check $_GET['action'] if needed 
// or continue using a POST variable to distinguish between update info and change password.
$sub_action = trim($_POST['sub_action'] ?? '');

// ═══════════════════════════════════════════════
// 1. UPDATE PERSONAL INFORMATION
// ═══════════════════════════════════════════════
if ($sub_action === 'update_profile') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $errors = [];
    if (empty($first_name))
        $errors[] = "กรุณากรอกชื่อ";
    if (empty($last_name))
        $errors[] = "กรุณากรอกนามสกุล";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "อีเมลไม่ถูกต้อง";

    // Check if email already exists for another user
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $employee_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "อีเมลนี้มีผู้ใช้งานรายอื่นแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$first_name, $last_name, $email, $employee_id])) {
                $_SESSION['msg_success'] = "อัปเดตข้อมูลส่วนตัวสำเร็จ";
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            }
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            error_log($e->getMessage());
        }
    }
    header("Location: ?page=profile");
    exit();
}

// ═══════════════════════════════════════════════
// 2. CHANGE PASSWORD
// ═══════════════════════════════════════════════
if ($sub_action === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($current_password))
        $errors[] = "กรุณากรอกรหัสผ่านปัจจุบัน";
    if (empty($new_password) || strlen($new_password) < 8)
        $errors[] = "รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร";
    if ($new_password !== $confirm_password)
        $errors[] = "รหัสผ่านใหม่และการยืนยันไม่ตรงกัน";

    if (empty($errors)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            $errors[] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE employees SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$hash, $employee_id])) {
                $_SESSION['msg_success'] = "เปลี่ยนรหัสผ่านสำเร็จแล้ว";
            }
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
            error_log($e->getMessage());
        }
    }
    header("Location: ?page=profile");
    exit();
}

// Fallback
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=profile");
exit();
