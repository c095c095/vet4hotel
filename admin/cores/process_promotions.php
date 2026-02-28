<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROMOTIONS PROCESSOR — VET4 HOTEL
// Handles Create, Update, Toggle Active, and Delete for promotions
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    require_once __DIR__ . '/../../cores/config.php';
    require_once __DIR__ . '/../../cores/database.php';
    require_once __DIR__ . '/../../cores/functions.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=promotions");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

$action = trim($_POST['action'] ?? '');
if ($action === 'promotions') {
    $sub_action = trim($_POST['sub_action'] ?? '');
} else {
    $sub_action = trim($_POST['sub_action'] ?? '');
}

// ─── ADD PROMOTION ───
if ($sub_action === 'add') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $discount_type = trim($_POST['discount_type'] ?? 'percentage');
    $discount_value = isset($_POST['discount_value']) ? (float) $_POST['discount_value'] : 0;
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float) $_POST['max_discount_amount'] : null;
    $min_booking_amount = isset($_POST['min_booking_amount']) ? (float) $_POST['min_booking_amount'] : 0;
    $usage_limit = !empty($_POST['usage_limit']) ? (int) $_POST['usage_limit'] : null;
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    // Validation
    $errors = [];
    if (empty($code))
        $errors[] = "กรุณากรอกรหัสโปรโมชัน";
    if (empty($title))
        $errors[] = "กรุณากรอกชื่อแคมเปญ";
    if ($discount_value <= 0)
        $errors[] = "มูลค่าส่วนลดต้องมากกว่า 0";
    if (!in_array($discount_type, ['percentage', 'fixed_amount']))
        $errors[] = "ประเภทส่วนลดไม่ถูกต้อง";
    if (empty($start_date) || empty($end_date))
        $errors[] = "กรุณาระบุวันเริ่มและวันสิ้นสุดโปรโมชัน";
    if ($start_date > $end_date)
        $errors[] = "วันเริ่มต้นต้องไม่มากกว่าวันสิ้นสุด";

    // Check duplicate code
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM promotions WHERE code = :code");
        $stmt->execute([':code' => $code]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "รหัสโปรโมชัน \"{$code}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=promotions");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO promotions (code, title, discount_type, discount_value, max_discount_amount, min_booking_amount, usage_limit, start_date, end_date, is_active) 
            VALUES (:code, :title, :type, :val, :max_val, :min_book, :limit, :start, :end, 1)");
        $stmt->execute([
            ':code' => $code,
            ':title' => $title,
            ':type' => $discount_type,
            ':val' => $discount_value,
            ':max_val' => $max_discount_amount,
            ':min_book' => $min_booking_amount,
            ':limit' => $usage_limit,
            ':start' => $start_date . ' 00:00:00',
            ':end' => $end_date . ' 23:59:59',
        ]);

        $_SESSION['msg_success'] = "เพิ่มโปรโมชัน \"{$code}\" สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเพิ่มโปรโมชันได้";
    }

    header("Location: ?page=promotions");
    exit();
}

// ─── EDIT PROMOTION ───
if ($sub_action === 'edit') {
    $promotion_id = (int) ($_POST['promotion_id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $discount_type = trim($_POST['discount_type'] ?? 'percentage');
    $discount_value = isset($_POST['discount_value']) ? (float) $_POST['discount_value'] : 0;
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float) $_POST['max_discount_amount'] : null;
    $min_booking_amount = isset($_POST['min_booking_amount']) ? (float) $_POST['min_booking_amount'] : 0;
    $usage_limit = !empty($_POST['usage_limit']) ? (int) $_POST['usage_limit'] : null;
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    // Validation
    $errors = [];
    if ($promotion_id <= 0)
        $errors[] = "ไม่พบข้อมูลโปรโมชัน";
    if (empty($code))
        $errors[] = "กรุณากรอกรหัสโปรโมชัน";
    if (empty($title))
        $errors[] = "กรุณากรอกชื่อแคมเปญ";
    if ($discount_value <= 0)
        $errors[] = "มูลค่าส่วนลดต้องมากกว่า 0";
    if (!in_array($discount_type, ['percentage', 'fixed_amount']))
        $errors[] = "ประเภทส่วนลดไม่ถูกต้อง";
    if (empty($start_date) || empty($end_date))
        $errors[] = "กรุณาระบุวันเริ่มและวันสิ้นสุดโปรโมชัน";
    if ($start_date > $end_date)
        $errors[] = "วันเริ่มต้นต้องไม่มากกว่าวันสิ้นสุด";

    // Check duplicate code (exclude self)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM promotions WHERE code = :code AND id != :id");
        $stmt->execute([':code' => $code, ':id' => $promotion_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "รหัสโปรโมชัน \"{$code}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=promotions");
        exit();
    }

    // append time if it's only a date
    if (strlen($start_date) === 10)
        $start_date .= ' 00:00:00';
    if (strlen($end_date) === 10)
        $end_date .= ' 23:59:59';

    try {
        $stmt = $pdo->prepare("UPDATE promotions SET 
            code = :code, title = :title, discount_type = :type, discount_value = :val, 
            max_discount_amount = :max_val, min_booking_amount = :min_book, 
            usage_limit = :limit, start_date = :start, end_date = :end 
            WHERE id = :id");
        $stmt->execute([
            ':code' => $code,
            ':title' => $title,
            ':type' => $discount_type,
            ':val' => $discount_value,
            ':max_val' => $max_discount_amount,
            ':min_book' => $min_booking_amount,
            ':limit' => $usage_limit,
            ':start' => $start_date,
            ':end' => $end_date,
            ':id' => $promotion_id,
        ]);

        $_SESSION['msg_success'] = "แก้ไขโปรโมชัน \"{$code}\" สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถแก้ไขโปรโมชันได้";
    }

    header("Location: ?page=promotions");
    exit();
}

// ─── TOGGLE ACTIVE ───
if ($sub_action === 'toggle_active') {
    $promotion_id = (int) ($_POST['promotion_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);

    if ($promotion_id <= 0 || !in_array($new_status, [0, 1])) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
        header("Location: ?page=promotions");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE promotions SET is_active = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $promotion_id]);

        $label = $new_status ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $_SESSION['msg_success'] = "เปลี่ยนสถานะเป็น \"{$label}\" สำเร็จ";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเปลี่ยนสถานะได้";
    }

    header("Location: ?page=promotions");
    exit();
}

// ─── HARD DELETE ───
if ($sub_action === 'delete') {
    $promotion_id = (int) ($_POST['promotion_id'] ?? 0);

    if ($promotion_id <= 0) {
        $_SESSION['msg_error'] = "ไม่พบข้อมูลโปรโมชัน";
        header("Location: ?page=promotions");
        exit();
    }

    // Check for existing bookings using this promotion (since ON DELETE SET NULL would remove the link)
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM bookings WHERE promotion_id = :id");
    $stmt->execute([':id' => $promotion_id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['msg_error'] = "ไม่สามารถลบโปรโมชันนี้ได้ เนื่องจากมีการจองใช้โปรโมชันนี้แล้ว แนะนำให้ทำการปิดการใช้งานแทน";
        header("Location: ?page=promotions");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = :id");
        $stmt->execute([':id' => $promotion_id]);

        $_SESSION['msg_success'] = "ลบโปรโมชันสำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถลบโปรโมชันได้";
    }

    header("Location: ?page=promotions");
    exit();
}

// Fallback — unknown action
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=promotions");
exit();
