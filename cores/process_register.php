<?php
if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=register");
    exit();
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Save form data to session so we can refill it on error
$_SESSION['form_data'] = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'phone' => $phone,
    'email' => $email
];

if (empty($first_name) || empty($last_name) || empty($phone) || empty($email) || empty($password)) {
    $_SESSION['error_msg'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
    header("Location: ?page=register");
    exit();
} elseif ($password !== $confirm_password) {
    $_SESSION['error_msg'] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
    header("Location: ?page=register");
    exit();
} elseif (strlen($password) < 6) {
    $_SESSION['error_msg'] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    header("Location: ?page=register");
    exit();
}

try {
    // Check if email or phone already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);
    if ($stmt->fetch()) {
        $_SESSION['error_msg'] = "อีเมลหรือเบอร์โทรศัพท์นี้ถูกใช้งานแล้ว";
        header("Location: ?page=register");
        exit();
    } else {
        // Insert new customer
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, phone, email, password_hash) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $phone, $email, $hashed_password]);

        // Clear form data
        unset($_SESSION['form_data']);

        // Redirect to login with success message
        $_SESSION['success_msg'] = "สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ";
        header("Location: ?page=login");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_msg'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่อีกครั้ง";
    // error_log($e->getMessage()); // In production, log this
    header("Location: ?page=register");
    exit();
}
