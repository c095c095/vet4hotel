<?php
// ═══════════════════════════════════════════════════════════
// PROCESS PAYMENT — VET4 HOTEL
// จัดการอัปโหลดสลิปและบันทึกข้อมูลการชำระเงิน
// ═══════════════════════════════════════════════════════════

if (!isset($pdo))
    exit('No direct access allowed.');

if (session_status() === PHP_SESSION_NONE)
    session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=booking_history');
    exit();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: ?page=login');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;

// ─────────────────────────────────────────────────────────
// VALIDATION
// ─────────────────────────────────────────────────────────

if ($booking_id <= 0) {
    $_SESSION['msg_error'] = 'ไม่พบข้อมูลการจอง';
    header('Location: ?page=booking_history');
    exit();
}

// Verify booking exists, belongs to customer, and is pending_payment
try {
    $stmt = $pdo->prepare("SELECT id, status, net_amount FROM bookings WHERE id = ? AND customer_id = ? LIMIT 1");
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $_SESSION['msg_error'] = 'ไม่พบข้อมูลการจอง หรือคุณไม่มีสิทธิ์เข้าถึง';
        header('Location: ?page=booking_history');
        exit();
    }

    if ($booking['status'] !== 'pending_payment') {
        $_SESSION['msg_error'] = 'การจองนี้ไม่สามารถชำระเงินได้ในขณะนี้';
        header('Location: ?page=booking_detail&id=' . $booking_id);
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
    header('Location: ?page=booking_history');
    exit();
}

// Validate form inputs
$payment_channel_id = isset($_POST['payment_channel_id']) ? (int) $_POST['payment_channel_id'] : 0;
$payment_type = $_POST['payment_type'] ?? '';
$amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
$transaction_ref = trim($_POST['transaction_ref'] ?? '');

$valid_types = ['deposit', 'full_payment', 'balance_due', 'extra_charge'];

if ($payment_channel_id <= 0) {
    $_SESSION['msg_error'] = 'กรุณาเลือกช่องทางชำระเงิน';
    header('Location: ?page=payment&booking_id=' . $booking_id);
    exit();
}

if (!in_array($payment_type, $valid_types)) {
    $_SESSION['msg_error'] = 'ประเภทการชำระเงินไม่ถูกต้อง';
    header('Location: ?page=payment&booking_id=' . $booking_id);
    exit();
}

if ($amount <= 0) {
    $_SESSION['msg_error'] = 'กรุณาระบุจำนวนเงินที่ถูกต้อง';
    header('Location: ?page=payment&booking_id=' . $booking_id);
    exit();
}

// Verify payment channel exists
try {
    $stmt = $pdo->prepare("SELECT id FROM payment_channels WHERE id = ? AND is_active = 1");
    $stmt->execute([$payment_channel_id]);
    if (!$stmt->fetch()) {
        $_SESSION['msg_error'] = 'ช่องทางชำระเงินไม่ถูกต้อง';
        header('Location: ?page=payment&booking_id=' . $booking_id);
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
    header('Location: ?page=payment&booking_id=' . $booking_id);
    exit();
}

// ─────────────────────────────────────────────────────────
// FILE UPLOAD (Slip Image)
// ─────────────────────────────────────────────────────────

$proof_image_url = null;

if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['proof_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 32 * 1024 * 1024; // 32MB

    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->file($file['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['msg_error'] = 'ประเภทไฟล์ไม่รองรับ กรุณาอัปโหลดไฟล์ JPG, PNG, WEBP หรือ GIF';
        header('Location: ?page=payment&booking_id=' . $booking_id);
        exit();
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        $_SESSION['msg_error'] = 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 32MB)';
        header('Location: ?page=payment&booking_id=' . $booking_id);
        exit();
    }

    // Create upload directory if not exists
    $upload_dir = __DIR__ . '/../uploads/slips/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $ext = match ($file_type) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'jpg',
    };
    $filename = 'slip_' . $booking_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $_SESSION['msg_error'] = 'ไม่สามารถบันทึกไฟล์ได้ กรุณาลองอีกครั้ง';
        header('Location: ?page=payment&booking_id=' . $booking_id);
        exit();
    }

    // URL relative to project root
    $proof_image_url = 'assets/uploads/slips/' . $filename;

} else {
    $_SESSION['msg_error'] = 'กรุณาอัปโหลดหลักฐานการชำระเงิน (สลิป)';
    header('Location: ?page=payment&booking_id=' . $booking_id);
    exit();
}

// ─────────────────────────────────────────────────────────
// INSERT PAYMENT RECORD
// ─────────────────────────────────────────────────────────

try {
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            booking_id, 
            payment_channel_id, 
            payment_type, 
            amount, 
            transaction_ref, 
            proof_image_url, 
            status, 
            paid_at, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([
        $booking_id,
        $payment_channel_id,
        $payment_type,
        $amount,
        $transaction_ref ?: null,
        $proof_image_url,
    ]);

    $_SESSION['msg_success'] = 'ส่งหลักฐานการชำระเงินสำเร็จ! พนักงานจะตรวจสอบและยืนยันให้โดยเร็ว';
    header('Location: ?page=booking_detail&id=' . $booking_id);
    exit();

} catch (PDOException $e) {
    // If file was uploaded, clean it up on DB failure
    if ($proof_image_url && file_exists(__DIR__ . '/../' . $proof_image_url)) {
        unlink(__DIR__ . '/../' . $proof_image_url);
    }

    $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลการชำระเงิน กรุณาลองอีกครั้ง';
    header('Location: ?page=payment&booking_id=' . $booking_id);
    exit();
}
