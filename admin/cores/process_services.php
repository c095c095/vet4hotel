<?php
// ═══════════════════════════════════════════════════════════
// ADMIN SERVICES PROCESSOR — VET4 HOTEL
// Handles Create, Update, Toggle Active, and Soft Delete for services
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=services");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

$sub_action = trim($_POST['sub_action'] ?? '');

// ─── ADD SERVICE ───
if ($sub_action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $charge_type = trim($_POST['charge_type'] ?? 'per_stay');

    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = "กรุณากรอกชื่อบริการ";
    }
    if ($price < 0) {
        $errors[] = "ราคาต้องไม่ต่ำกว่า 0";
    }
    if (!in_array($charge_type, ['per_stay', 'per_night', 'per_pet'])) {
        $errors[] = "ประเภทการคิดค่าบริการไม่ถูกต้อง";
    }

    // Check duplicate name
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM services WHERE name = :name AND deleted_at IS NULL");
        $stmt->execute([':name' => $name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "บริการ \"{$name}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=services");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO services (name, description, price, charge_type, is_active) VALUES (:name, :desc, :price, :charge, 1)");
        $stmt->execute([
            ':name' => $name,
            ':desc' => $description ?: null,
            ':price' => $price,
            ':charge' => $charge_type,
        ]);

        $_SESSION['msg_success'] = "เพิ่มบริการ \"{$name}\" สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเพิ่มบริการได้";
    }

    header("Location: ?page=services");
    exit();
}

// ─── EDIT SERVICE ───
if ($sub_action === 'edit') {
    $service_id = (int) ($_POST['service_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $charge_type = trim($_POST['charge_type'] ?? 'per_stay');

    // Validation
    $errors = [];
    if ($service_id <= 0) {
        $errors[] = "ไม่พบข้อมูลบริการ";
    }
    if (empty($name)) {
        $errors[] = "กรุณากรอกชื่อบริการ";
    }
    if ($price < 0) {
        $errors[] = "ราคาต้องไม่ต่ำกว่า 0";
    }
    if (!in_array($charge_type, ['per_stay', 'per_night', 'per_pet'])) {
        $errors[] = "ประเภทการคิดค่าบริการไม่ถูกต้อง";
    }

    // Check duplicate name (exclude self)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM services WHERE name = :name AND id != :id AND deleted_at IS NULL");
        $stmt->execute([':name' => $name, ':id' => $service_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "บริการ \"{$name}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=services");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE services SET name = :name, description = :desc, price = :price, charge_type = :charge WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([
            ':name' => $name,
            ':desc' => $description ?: null,
            ':price' => $price,
            ':charge' => $charge_type,
            ':id' => $service_id,
        ]);

        $_SESSION['msg_success'] = "แก้ไขบริการ \"{$name}\" สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถแก้ไขบริการได้";
    }

    header("Location: ?page=services");
    exit();
}

// ─── TOGGLE ACTIVE ───
if ($sub_action === 'toggle_active') {
    $service_id = (int) ($_POST['service_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);

    if ($service_id <= 0 || !in_array($new_status, [0, 1])) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
        header("Location: ?page=services");
        exit();
    }

    // If deactivating, check for active bookings using this service
    if ($new_status === 0) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(bs.id) 
             FROM booking_services bs 
             JOIN bookings b ON b.id = bs.booking_id 
             WHERE bs.service_id = :service_id 
             AND b.status IN ('pending_payment', 'verifying_payment', 'confirmed', 'checked_in')"
        );
        $stmt->execute([':service_id' => $service_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['msg_error'] = "ไม่สามารถปิดบริการนี้ได้ เนื่องจากมีการจองที่ยังใช้งานอยู่";
            header("Location: ?page=services");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE services SET is_active = :status WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':status' => $new_status, ':id' => $service_id]);

        $label = $new_status ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $_SESSION['msg_success'] = "เปลี่ยนสถานะบริการเป็น \"{$label}\" สำเร็จ";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเปลี่ยนสถานะบริการได้";
    }

    header("Location: ?page=services");
    exit();
}

// ─── SOFT DELETE ───
if ($sub_action === 'delete') {
    $service_id = (int) ($_POST['service_id'] ?? 0);

    if ($service_id <= 0) {
        $_SESSION['msg_error'] = "ไม่พบข้อมูลบริการ";
        header("Location: ?page=services");
        exit();
    }

    // Check for active bookings using this service
    $stmt = $pdo->prepare(
        "SELECT COUNT(bs.id) 
         FROM booking_services bs 
         JOIN bookings b ON b.id = bs.booking_id 
         WHERE bs.service_id = :service_id 
         AND b.status IN ('pending_payment', 'verifying_payment', 'confirmed', 'checked_in')"
    );
    $stmt->execute([':service_id' => $service_id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['msg_error'] = "ไม่สามารถลบบริการนี้ได้ เนื่องจากมีการจองที่ยังใช้งานอยู่";
        header("Location: ?page=services");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE services SET deleted_at = NOW(), is_active = 0 WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $service_id]);

        $_SESSION['msg_success'] = "ลบบริการสำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถลบบริการได้";
    }

    header("Location: ?page=services");
    exit();
}

// Fallback — unknown action
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=services");
exit();
