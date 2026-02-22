<?php
// process_booking.php
if (!isset($pdo))
    exit('No direct access allowed.');

// ตรวจสอบ Session (ถ้าใน index.php มี session_start แล้ว บรรทัดนี้จะไม่ทำงานซ้ำ)
if (session_status() === PHP_SESSION_NONE)
    session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=booking');
    exit();
}

function validate_booking_form($form, $cart, $pdo, $customer_id)
{
    $room_type_id = $form['room_type_id'] ?? null;
    $check_in_date = $form['check_in_date'] ?? null;
    $check_out_date = $form['check_out_date'] ?? null;
    $pet_ids = $form['pet_ids'] ?? [];

    // 1. Validate วันที่
    if (!$check_in_date || !$check_out_date) {
        return ['step' => 1, 'error' => 'กรุณาเลือกวันที่เข้าพักและวันที่เช็คเอาท์'];
    }
    if (strtotime($check_in_date) >= strtotime($check_out_date)) {
        return ['step' => 1, 'error' => 'วันที่เช็คอินต้องน้อยกว่าวันที่เช็คเอาท์'];
    }

    // 2. Validate ห้อง
    if (!$room_type_id) {
        return ['step' => 2, 'error' => 'กรุณาเลือกประเภทห้องพัก'];
    }

    // 3. Validate สัตว์เลี้ยง
    if (empty($pet_ids)) {
        return ['step' => 2, 'error' => 'กรุณาเลือกสัตว์เลี้ยงที่จะเข้าพัก'];
    }

    // 4. ตรวจสอบจำนวนห้องว่าง (เทียบกับของในตะกร้า + ห้องปัจจุบัน)
    $cart_count = 0;
    foreach ($cart as $item) {
        if (isset($item['room_type_id']) && $item['room_type_id'] == $room_type_id) {
            $cin = $item['check_in_date'] ?? '';
            $cout = $item['check_out_date'] ?? '';
            if ($cin && $cout) {
                if (strtotime($check_in_date) < strtotime($cout) && strtotime($check_out_date) > strtotime($cin)) {
                    $cart_count++;
                }
            }
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = ? AND r.status = 'active' AND r.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id WHERE bi.room_id = r.id AND b.status NOT IN ('cancelled') AND bi.check_in_date < ? AND bi.check_out_date > ?)");
        $stmt->execute([$room_type_id, $check_out_date, $check_in_date]);
        $available_rooms = (int) $stmt->fetchColumn();

        if ($cart_count + 1 > $available_rooms) {
            return ['step' => 2, 'error' => 'จำนวนห้องที่เลือกเกินจำนวนที่มีอยู่ กรุณาเลือกประเภทห้องอื่น'];
        }

        // 5. Validate max_pets
        $stmt = $pdo->prepare("SELECT max_pets FROM room_types WHERE id = ?");
        $stmt->execute([$room_type_id]);
        $max_pets = (int) $stmt->fetchColumn();

        if (count($pet_ids) > $max_pets) {
            return ['step' => 2, 'error' => "ห้องประเภทนี้พักได้สูงสุด $max_pets ตัว"];
        }

        // 6. Validate เจ้าของสัตว์เลี้ยง (Same Family)
        $in = implode(',', array_fill(0, count($pet_ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE id IN ($in) AND customer_id != ?");
        $params = array_merge($pet_ids, [$customer_id]);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() > 0) {
            return ['step' => 2, 'error' => 'พบสัตว์เลี้ยงที่ไม่ได้อยู่ในบัญชีของคุณ'];
        }

    } catch (PDOException $e) {
        return ['step' => 2, 'error' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล'];
    }

    return null; // ผ่านการตรวจสอบ
}

$current_step = isset($_POST['current_step']) ? (int) $_POST['current_step'] : 1;

// ตรวจสอบว่ามีตะกร้าหรือยัง
if (!isset($_SESSION['booking_cart']))
    $_SESSION['booking_cart'] = [];
if (!isset($_SESSION['booking_form']))
    $_SESSION['booking_form'] = [];

// --- Logic การจัดการแต่ละ Step ---

if ($current_step === 1) {
    // Validate วันที่
    $cin = $_POST['check_in_date'] ?? '';
    $cout = $_POST['check_out_date'] ?? '';
    if (!$cin || !$cout) {
        $_SESSION['booking_error'] = 'กรุณาเลือกวันที่เข้าพักและวันที่เช็คเอาท์';
        header('Location: ?page=booking&step=1');
        exit();
    }
    if (strtotime($cin) >= strtotime($cout)) {
        $_SESSION['booking_error'] = 'วันที่เช็คเอาท์ต้องมาหลังจากวันที่เข้าพักอย่างน้อย 1 วัน';
        header('Location: ?page=booking&step=1');
        exit();
    }

    // บันทึกวันที่
    $_SESSION['booking_form']['check_in_date'] = $cin;
    $_SESSION['booking_form']['check_out_date'] = $cout;
    header('Location: ?page=booking&step=2');
    exit();
}

if ($current_step === 2) {
    // บันทึกห้องและสัตว์เลี้ยง
    $_SESSION['booking_form']['room_type_id'] = (int) $_POST['room_type_id'];
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
    // 1. ตรวจสอบข้อมูลก่อนทำรายการใดๆ
    $validate_result = validate_booking_form($_SESSION['booking_form'], $_SESSION['booking_cart'], $pdo, $_SESSION['customer_id']);

    if ($validate_result) {
        $_SESSION['booking_error'] = $validate_result['error'];
        header('Location: ?page=booking&step=' . $validate_result['step']);
        exit();
    }

    // 2. ถ้าผ่านการตรวจสอบ ให้นำข้อมูลเข้าตะกร้า
    $_SESSION['booking_cart'][] = $_SESSION['booking_form'];

    // 3. แยกทางเลือกระหว่าง "จองเพิ่ม" หรือ "ไปตะกร้า"
    if (isset($_POST['add_another'])) {
        // เก็บเฉพาะวันที่ไว้ ล้างข้อมูลห้องและสัตว์เลี้ยงเพื่อจองห้องใหม่
        $keep_dates = [
            'check_in_date' => $_SESSION['booking_form']['check_in_date'],
            'check_out_date' => $_SESSION['booking_form']['check_out_date'],
            'room_type_id' => null,
            'pet_ids' => [],
            'service_ids' => []
        ];
        $_SESSION['booking_form'] = $keep_dates;
        header('Location: ?page=booking&step=2');
        exit();
    } else {
        // กรณี "ยืนยัน" หรือ "confirm"
        unset($_SESSION['booking_form']); // ล้างฟอร์มชั่วคราวทิ้งเพราะเข้าตะกร้าไปแล้ว
        header('Location: ?page=cart');
        exit();
    }
}

// Fallback
header('Location: ?page=booking');
exit();
