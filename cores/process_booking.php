<?php
// process_booking.php
if (!isset($pdo)) exit('No direct access allowed.');

// ตรวจสอบ Session (ถ้าใน index.php มี session_start แล้ว บรรทัดนี้จะไม่ทำงานซ้ำ)
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=booking');
    exit();
}

$current_step = isset($_POST['current_step']) ? (int)$_POST['current_step'] : 1;

// ตรวจสอบว่ามีตะกร้าหรือยัง
if (!isset($_SESSION['booking_cart'])) $_SESSION['booking_cart'] = [];
if (!isset($_SESSION['booking_form'])) $_SESSION['booking_form'] = [];

// --- Logic การจัดการแต่ละ Step ---

if ($current_step === 1) {
    // บันทึกวันที่
    $_SESSION['booking_form']['check_in_date'] = $_POST['check_in_date'];
    $_SESSION['booking_form']['check_out_date'] = $_POST['check_out_date'];
    header('Location: ?page=booking&step=2');
    exit();
}

if ($current_step === 2) {
    // บันทึกห้องและสัตว์เลี้ยง
    $_SESSION['booking_form']['room_type_id'] = (int)$_POST['room_type_id'];
    $_SESSION['booking_form']['pet_ids'] = isset($_POST['pet_ids']) ? array_map('intval', $_POST['pet_ids']) : [];
    header('Location: ?page=booking&step=3');
    exit();
}

if ($current_step === 3) {
    // บันทึกบริการเสริม
    $_SESSION['booking_form']['service_ids'] = isset($_POST['service_ids']) ? array_map('intval', $_POST['service_ids']) : [];
    header('Location: ?page=booking&step=4');
    exit();
}

if ($current_step === 4) {
    // กด "จองห้องอื่นเพิ่ม"
    if (isset($_POST['add_another'])) {
        $_SESSION['booking_cart'][] = $_SESSION['booking_form'];
        
        // ล้างค่าห้องเก่าออก แต่เก็บวันที่ไว้ (เพื่อความสะดวก)
        $current_dates = [
            'check_in_date' => $_SESSION['booking_form']['check_in_date'],
            'check_out_date' => $_SESSION['booking_form']['check_out_date'],
            'room_type_id' => null,
            'pet_ids' => [],
            'service_ids' => []
        ];
        $_SESSION['booking_form'] = $current_dates;
        header('Location: ?page=booking&step=2');
        exit();
    }

    // กด "ไปที่หน้าชำระเงิน"
    if (isset($_POST['confirm'])) {
        $_SESSION['booking_cart'][] = $_SESSION['booking_form'];
        unset($_SESSION['booking_form']); // ล้างฟอร์มชั่วคราวออก
        header('Location: ?page=payment');
        exit();
    }
}

// Fallback
header('Location: ?page=booking');
exit();