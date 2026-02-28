<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PETS PROCESSOR — VET4 HOTEL
// Handles Edit, Toggle Aggressive, and Soft Delete for pets
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=pets");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

$sub_action = trim($_POST['sub_action'] ?? '');

// ─── EDIT PET ───
if ($sub_action === 'edit') {
    $pet_id = (int) ($_POST['pet_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $species_id = (int) ($_POST['species_id'] ?? 0);
    $breed_id = !empty($_POST['breed_id']) ? (int) $_POST['breed_id'] : null;
    $dob = trim($_POST['dob'] ?? '') ?: null;
    $weight_kg = isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? (float) $_POST['weight_kg'] : null;
    $gender = trim($_POST['gender'] ?? 'unknown');
    $vet_name = trim($_POST['vet_name'] ?? '') ?: null;
    $vet_phone = trim($_POST['vet_phone'] ?? '') ?: null;
    $is_aggressive = isset($_POST['is_aggressive']) ? 1 : 0;
    $behavior_note = trim($_POST['behavior_note'] ?? '') ?: null;

    // Validation
    $errors = [];
    if ($pet_id <= 0) {
        $errors[] = "ไม่พบข้อมูลสัตว์เลี้ยง";
    }
    if (empty($name)) {
        $errors[] = "กรุณากรอกชื่อสัตว์เลี้ยง";
    }
    if ($species_id <= 0) {
        $errors[] = "กรุณาเลือกชนิดสัตว์";
    }
    if (!in_array($gender, ['male', 'female', 'spayed', 'neutered', 'unknown'])) {
        $errors[] = "เพศไม่ถูกต้อง";
    }
    if ($weight_kg !== null && $weight_kg < 0) {
        $errors[] = "น้ำหนักต้องไม่ต่ำกว่า 0";
    }

    // Verify pet exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, name FROM pets WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $pet_id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            $errors[] = "ไม่พบสัตว์เลี้ยงในระบบ";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=pets");
        exit();
    }

    try {
        $stmt = $pdo->prepare(
            "UPDATE pets SET 
                name = :name, 
                species_id = :species_id, 
                breed_id = :breed_id, 
                dob = :dob, 
                weight_kg = :weight_kg, 
                gender = :gender, 
                vet_name = :vet_name, 
                vet_phone = :vet_phone, 
                is_aggressive = :is_aggressive, 
                behavior_note = :behavior_note 
             WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([
            ':name' => $name,
            ':species_id' => $species_id,
            ':breed_id' => $breed_id,
            ':dob' => $dob,
            ':weight_kg' => $weight_kg,
            ':gender' => $gender,
            ':vet_name' => $vet_name,
            ':vet_phone' => $vet_phone,
            ':is_aggressive' => $is_aggressive,
            ':behavior_note' => $behavior_note,
            ':id' => $pet_id,
        ]);

        $_SESSION['msg_success'] = "แก้ไขข้อมูล \"{$name}\" สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถแก้ไขข้อมูลสัตว์เลี้ยงได้";
    }

    header("Location: ?page=pets");
    exit();
}

// ─── TOGGLE AGGRESSIVE ───
if ($sub_action === 'toggle_aggressive') {
    $pet_id = (int) ($_POST['pet_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);

    if ($pet_id <= 0 || !in_array($new_status, [0, 1])) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
        header("Location: ?page=pets");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE pets SET is_aggressive = :status WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':status' => $new_status, ':id' => $pet_id]);

        $label = $new_status ? 'ดุ/ก้าวร้าว ⚠️' : 'ปกติ ✅';
        $_SESSION['msg_success'] = "เปลี่ยนสถานะพฤติกรรมเป็น \"{$label}\" สำเร็จ";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเปลี่ยนสถานะพฤติกรรมได้";
    }

    header("Location: ?page=pets");
    exit();
}

// ─── SOFT DELETE ───
if ($sub_action === 'delete') {
    $pet_id = (int) ($_POST['pet_id'] ?? 0);

    if ($pet_id <= 0) {
        $_SESSION['msg_error'] = "ไม่พบข้อมูลสัตว์เลี้ยง";
        header("Location: ?page=pets");
        exit();
    }

    // Check for active bookings
    $stmt = $pdo->prepare(
        "SELECT COUNT(bip.pet_id) 
         FROM booking_item_pets bip 
         JOIN booking_items bi ON bi.id = bip.booking_item_id 
         JOIN bookings bk ON bk.id = bi.booking_id 
         WHERE bip.pet_id = :pet_id 
         AND bk.status IN ('pending_payment', 'verifying_payment', 'confirmed', 'checked_in')"
    );
    $stmt->execute([':pet_id' => $pet_id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['msg_error'] = "ไม่สามารถลบสัตว์เลี้ยงนี้ได้ เนื่องจากมีการจองที่ยังใช้งานอยู่";
        header("Location: ?page=pets");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE pets SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $pet_id]);

        $_SESSION['msg_success'] = "ลบสัตว์เลี้ยงออกจากระบบสำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถลบสัตว์เลี้ยงได้";
    }

    header("Location: ?page=pets");
    exit();
}

// Fallback — unknown action
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=pets");
exit();
