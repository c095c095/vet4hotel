<?php
if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=my_pets');
    exit();
}

try {
    // รับค่าและทำความสะอาดข้อมูลเบื้องต้น
    $customer_id = $_SESSION['customer_id'];
    $name = trim($_POST['name'] ?? '');
    $species_id = $_POST['species_id'] ?? null;
    $breed_id = !empty($_POST['breed_id']) ? $_POST['breed_id'] : null; // ถ้าไม่เลือกให้เป็น NULL
    $gender = $_POST['gender'] ?? 'male';
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null; // ถ้าไม่ระบุวันเกิดให้เป็น NULL
    $weight_kg = !empty($_POST['weight_kg']) ? (float) $_POST['weight_kg'] : 0.0;
    $is_aggressive = isset($_POST['is_aggressive']) ? 1 : 0; // Checkbox ถ้าไม่ติ๊กจะไม่มีค่าส่งมา
    $behavior_note = trim($_POST['behavior_note'] ?? '');

    // Validation ขั้นพื้นฐาน
    if (empty($name) || empty($species_id)) {
        throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
    }

    // เตรียมคำสั่ง SQL (Prepare Statement เพื่อกัน SQL Injection)
    $sql = "INSERT INTO pets (
                customer_id, species_id, breed_id, name, 
                gender, dob, weight_kg, is_aggressive, 
                behavior_note, created_at
            ) VALUES (
                :customer_id, :species_id, :breed_id, :name, 
                :gender, :dob, :weight_kg, :is_aggressive, 
                :behavior_note, NOW()
            )";

    $stmt = $pdo->prepare($sql);

    // Bind Parameters
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':species_id' => $species_id,
        ':breed_id' => $breed_id,
        ':name' => $name,
        ':gender' => $gender,
        ':dob' => $dob,
        ':weight_kg' => $weight_kg,
        ':is_aggressive' => $is_aggressive,
        ':behavior_note' => $behavior_note
    ]);

    // บันทึกสำเร็จ: ส่งกลับหน้าเดิมพร้อมข้อความ Success (Flash Message)
    $_SESSION['msg_success'] = "เพิ่มข้อมูลของ " . htmlspecialchars($name) . " เรียบร้อยแล้ว!";
    header("Location: index.php?page=my_pets");
    exit();

} catch (Exception $e) {
    //  จัดการเมื่อเกิด Error
    $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("Location: ?page=my_pets");
    exit();
}