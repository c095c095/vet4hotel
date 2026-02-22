<?php
// ═══════════════════════════════════════════════════════════
// PROCESS VACCINE — VET4 HOTEL
// รับข้อมูล POST จากฟอร์มเพิ่มวัคซีน (vaccine modal ใน my_pets.php)
// ═══════════════════════════════════════════════════════════

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

$customer_id = $_SESSION['customer_id'];
$vaccine_action = $_POST['vaccine_action'] ?? 'add';

try {

    // ══════════════════════════════════════════════════════════
    // DELETE VACCINE
    // ══════════════════════════════════════════════════════════
    if ($vaccine_action === 'delete') {
        $vaccine_id = (int) ($_POST['vaccine_id'] ?? 0);
        if ($vaccine_id <= 0) {
            throw new Exception("ไม่พบข้อมูลวัคซีนที่ต้องการลบ");
        }

        // Ownership check: ตรวจว่าวัคซีนนี้เป็นของสัตว์เลี้ยงที่ customer เป็นเจ้าของจริง
        $checkStmt = $pdo->prepare("
            SELECT pv.id FROM pet_vaccinations pv
            INNER JOIN pets p ON pv.pet_id = p.id
            WHERE pv.id = ? AND p.customer_id = ? AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $checkStmt->execute([$vaccine_id, $customer_id]);
        if (!$checkStmt->fetch()) {
            throw new Exception("ไม่พบข้อมูลวัคซีนหรือคุณไม่มีสิทธิ์ลบข้อมูลนี้");
        }

        $delStmt = $pdo->prepare("DELETE FROM pet_vaccinations WHERE id = ?");
        $delStmt->execute([$vaccine_id]);

        $_SESSION['msg_success'] = "ลบข้อมูลวัคซีนเรียบร้อยแล้ว";
        header("Location: ?page=my_pets");
        exit();
    }

    // ══════════════════════════════════════════════════════════
    // ADD VACCINE (default)
    // ══════════════════════════════════════════════════════════
    $pet_id = (int) ($_POST['pet_id'] ?? 0);
    $vaccine_type_id = (int) ($_POST['vaccine_type_id'] ?? 0);
    $administered = !empty($_POST['administered_date']) ? $_POST['administered_date'] : null;
    $expiry = trim($_POST['expiry_date'] ?? '');

    // ── Validation ─────────────────────────────────────────
    if ($pet_id <= 0 || $vaccine_type_id <= 0 || empty($expiry)) {
        throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
    }

    // ── Ownership check ────────────────────────────────────
    $ownerStmt = $pdo->prepare("SELECT id FROM pets WHERE id = ? AND customer_id = ? AND deleted_at IS NULL LIMIT 1");
    $ownerStmt->execute([$pet_id, $customer_id]);
    if (!$ownerStmt->fetch()) {
        throw new Exception("ไม่พบข้อมูลสัตว์เลี้ยงหรือคุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้");
    }

    // ── Insert ──────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO pet_vaccinations (pet_id, vaccine_type_id, administered_date, expiry_date, created_at)
        VALUES (:pet_id, :vaccine_type_id, :administered_date, :expiry_date, NOW())
    ");
    $stmt->execute([
        ':pet_id' => $pet_id,
        ':vaccine_type_id' => $vaccine_type_id,
        ':administered_date' => $administered,
        ':expiry_date' => $expiry,
    ]);

    $_SESSION['msg_success'] = "บันทึกข้อมูลวัคซีนเรียบร้อยแล้ว!";
    header("Location: ?page=my_pets");
    exit();

} catch (Exception $e) {
    $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("Location: ?page=my_pets");
    exit();
}
