<?php
// ═══════════════════════════════════════════════════════════
// ADMIN ROOMS PROCESSOR — VET4 HOTEL
// Handles Create, Update, and Status Toggle for physical rooms
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=rooms");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

$sub_action = trim($_POST['sub_action'] ?? '');

// ─── ADD ROOM ───
if ($sub_action === 'add') {
    $room_number = trim($_POST['room_number'] ?? '');
    $room_type_id = (int) ($_POST['room_type_id'] ?? 0);
    $floor_level = trim($_POST['floor_level'] ?? '1');
    $cctv_url = trim($_POST['cctv_url'] ?? '');

    // Validation
    $errors = [];
    if (empty($room_number)) {
        $errors[] = "กรุณากรอกหมายเลขห้อง";
    }
    if ($room_type_id <= 0) {
        $errors[] = "กรุณาเลือกประเภทห้อง";
    }
    if (empty($floor_level)) {
        $errors[] = "กรุณากรอกชั้น";
    }

    // Check duplicate room number
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM rooms WHERE room_number = :rn AND deleted_at IS NULL");
        $stmt->execute([':rn' => $room_number]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "หมายเลขห้อง \"{$room_number}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=rooms");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO rooms (room_type_id, room_number, floor_level, status, cctv_url) VALUES (:type, :num, :floor, 'active', :cctv)");
        $stmt->execute([
            ':type' => $room_type_id,
            ':num' => $room_number,
            ':floor' => $floor_level,
            ':cctv' => $cctv_url ?: null,
        ]);

        $_SESSION['msg_success'] = "เพิ่มห้อง {$room_number} สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเพิ่มห้องได้";
    }

    header("Location: ?page=rooms");
    exit();
}

// ─── EDIT ROOM ───
if ($sub_action === 'edit') {
    $room_id = (int) ($_POST['room_id'] ?? 0);
    $room_number = trim($_POST['room_number'] ?? '');
    $room_type_id = (int) ($_POST['room_type_id'] ?? 0);
    $floor_level = trim($_POST['floor_level'] ?? '1');
    $cctv_url = trim($_POST['cctv_url'] ?? '');

    // Validation
    $errors = [];
    if ($room_id <= 0) {
        $errors[] = "ไม่พบข้อมูลห้องพัก";
    }
    if (empty($room_number)) {
        $errors[] = "กรุณากรอกหมายเลขห้อง";
    }
    if ($room_type_id <= 0) {
        $errors[] = "กรุณาเลือกประเภทห้อง";
    }

    // Check duplicate room number (exclude self)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM rooms WHERE room_number = :rn AND id != :id AND deleted_at IS NULL");
        $stmt->execute([':rn' => $room_number, ':id' => $room_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "หมายเลขห้อง \"{$room_number}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=rooms");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE rooms SET room_type_id = :type, room_number = :num, floor_level = :floor, cctv_url = :cctv WHERE id = :id");
        $stmt->execute([
            ':type' => $room_type_id,
            ':num' => $room_number,
            ':floor' => $floor_level,
            ':cctv' => $cctv_url ?: null,
            ':id' => $room_id,
        ]);

        $_SESSION['msg_success'] = "แก้ไขห้อง {$room_number} สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถแก้ไขห้องได้";
    }

    header("Location: ?page=rooms");
    exit();
}

// ─── TOGGLE STATUS ───
if ($sub_action === 'toggle_status') {
    $room_id = (int) ($_POST['room_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $valid_statuses = ['active', 'maintenance', 'out_of_service'];

    if ($room_id <= 0 || !in_array($new_status, $valid_statuses)) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
        header("Location: ?page=rooms");
        exit();
    }

    // If deactivating, check for active/future bookings
    if ($new_status !== 'active') {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare(
            "SELECT COUNT(bi.id) 
             FROM booking_items bi 
             JOIN bookings b ON b.id = bi.booking_id 
             WHERE bi.room_id = :room_id 
             AND bi.check_out_date >= :today 
             AND b.status IN ('confirmed', 'checked_in')"
        );
        $stmt->execute([':room_id' => $room_id, ':today' => $today]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['msg_error'] = "ไม่สามารถเปลี่ยนสถานะได้ เนื่องจากห้องนี้มีการจองที่ยังไม่เสร็จสิ้น";
            header("Location: ?page=rooms");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE rooms SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $room_id]);

        $status_labels = [
            'active' => 'พร้อมใช้งาน',
            'maintenance' => 'ซ่อมบำรุง',
            'out_of_service' => 'ปิดให้บริการ'
        ];
        $_SESSION['msg_success'] = "เปลี่ยนสถานะห้องเป็น \"" . ($status_labels[$new_status] ?? $new_status) . "\" สำเร็จ";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเปลี่ยนสถานะห้องได้";
    }

    header("Location: ?page=rooms");
    exit();
}

// Fallback — unknown action
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=rooms");
exit();
