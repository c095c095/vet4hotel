<?php
if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=my_pets');
    exit();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: ?page=login');
    exit();
}

try {
    // รับค่าและทำความสะอาดข้อมูลเบื้องต้น
    $customer_id = $_SESSION['customer_id'];
    $action = $_POST['action'] ?? 'add';

    // ══════════════════════════════════════════════════════════
    // SOFT DELETE PET
    // ══════════════════════════════════════════════════════════
    if ($action === 'delete') {
        $pet_id = (int) ($_POST['pet_id'] ?? 0);
        if ($pet_id <= 0) {
            throw new Exception("ไม่พบข้อมูลสัตว์เลี้ยงที่ต้องการลบ");
        }

        // Ownership check
        $checkStmt = $pdo->prepare("SELECT name FROM pets WHERE id = ? AND customer_id = ? AND deleted_at IS NULL LIMIT 1");
        $checkStmt->execute([$pet_id, $customer_id]);
        $pet = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$pet) {
            throw new Exception("ไม่พบข้อมูลสัตว์เลี้ยงหรือคุณไม่มีสิทธิ์ลบข้อมูลนี้");
        }

        $delStmt = $pdo->prepare("UPDATE pets SET deleted_at = NOW() WHERE id = ? AND customer_id = ?");
        $delStmt->execute([$pet_id, $customer_id]);

        $_SESSION['msg_success'] = "ลบข้อมูลของ " . sanitize($pet['name']) . " เรียบร้อยแล้ว";
        header("Location: ?page=my_pets");
        exit();
    }

    // ── รับค่าและทำความสะอาดข้อมูล (ใช้ร่วมกัน add/edit) ──
    $name = trim($_POST['name'] ?? '');
    $species_id = $_POST['species_id'] ?? null;
    $breed_id = !empty($_POST['breed_id']) ? $_POST['breed_id'] : null;
    $gender = $_POST['gender'] ?? 'male';
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $weight_kg = !empty($_POST['weight_kg']) ? (float) $_POST['weight_kg'] : 0.0;
    $is_aggressive = isset($_POST['is_aggressive']) ? 1 : 0;
    $behavior_note = trim($_POST['behavior_note'] ?? '');
    $vet_name = trim($_POST['vet_name'] ?? '');
    $vet_phone = trim($_POST['vet_phone'] ?? '');

    // Validation ขั้นพื้นฐาน
    if (empty($name) || empty($species_id)) {
        throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
    }

    // ══════════════════════════════════════════════════════════
    // EDIT PET
    // ══════════════════════════════════════════════════════════
    if ($action === 'edit') {
        $pet_id = (int) ($_POST['pet_id'] ?? 0);
        if ($pet_id <= 0) {
            throw new Exception("ไม่พบข้อมูลสัตว์เลี้ยงที่ต้องการแก้ไข");
        }

        // Ownership check
        $checkStmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND customer_id = ? AND deleted_at IS NULL LIMIT 1");
        $checkStmt->execute([$pet_id, $customer_id]);
        if (!$checkStmt->fetch()) {
            throw new Exception("ไม่พบข้อมูลสัตว์เลี้ยงหรือคุณไม่มีสิทธิ์แก้ไขข้อมูลนี้");
        }

        $stmt = $pdo->prepare("
            UPDATE pets SET
                species_id = :species_id,
                breed_id = :breed_id,
                name = :name,
                gender = :gender,
                dob = :dob,
                weight_kg = :weight_kg,
                is_aggressive = :is_aggressive,
                behavior_note = :behavior_note,
                vet_name = :vet_name,
                vet_phone = :vet_phone,
                updated_at = NOW()
            WHERE id = :pet_id AND customer_id = :customer_id
        ");
        $stmt->execute([
            ':species_id' => $species_id,
            ':breed_id' => $breed_id,
            ':name' => $name,
            ':gender' => $gender,
            ':dob' => $dob,
            ':weight_kg' => $weight_kg,
            ':is_aggressive' => $is_aggressive,
            ':behavior_note' => $behavior_note,
            ':vet_name' => $vet_name,
            ':vet_phone' => $vet_phone,
            ':pet_id' => $pet_id,
            ':customer_id' => $customer_id,
        ]);

        $_SESSION['msg_success'] = "แก้ไขข้อมูลของ " . sanitize($name) . " เรียบร้อยแล้ว!";
        header("Location: ?page=my_pets");
        exit();
    }

    // ══════════════════════════════════════════════════════════
    // ADD PET (default)
    // ══════════════════════════════════════════════════════════
    $stmt = $pdo->prepare("
        INSERT INTO pets (
            customer_id, species_id, breed_id, name,
            gender, dob, weight_kg, is_aggressive,
            behavior_note, vet_name, vet_phone, created_at
        ) VALUES (
            :customer_id, :species_id, :breed_id, :name,
            :gender, :dob, :weight_kg, :is_aggressive,
            :behavior_note, :vet_name, :vet_phone, NOW()
        )
    ");
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':species_id' => $species_id,
        ':breed_id' => $breed_id,
        ':name' => $name,
        ':gender' => $gender,
        ':dob' => $dob,
        ':weight_kg' => $weight_kg,
        ':is_aggressive' => $is_aggressive,
        ':behavior_note' => $behavior_note,
        ':vet_name' => $vet_name,
        ':vet_phone' => $vet_phone,
    ]);

    $_SESSION['msg_success'] = "เพิ่มข้อมูลของ " . sanitize($name) . " เรียบร้อยแล้ว!";
    header("Location: ?page=my_pets");
    exit();

} catch (Exception $e) {
    $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("Location: ?page=my_pets");
    exit();
}