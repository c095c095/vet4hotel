<?php
// ═══════════════════════════════════════════════════════════
// ADMIN CARE TASKS PROCESSOR — VET4 HOTEL
// Handles Create, Update, Toggle Status, and Delete for daily tasks
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=care_tasks");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

$sub_action = trim($_POST['sub_action'] ?? '');
$employee_id = $_SESSION['employee_id'];

// ─── ADD TASK ───
if ($sub_action === 'add') {
    $booking_item_id = (int) ($_POST['booking_item_id'] ?? 0);
    $pet_id = (int) ($_POST['pet_id'] ?? 0);
    $task_date = trim($_POST['task_date'] ?? '');
    $task_type_id = (int) ($_POST['task_type_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Validation
    $errors = [];
    if ($booking_item_id <= 0 || $pet_id <= 0) {
        $errors[] = "กรุณาเลือกสัตว์เลี้ยงที่กำลังเข้าพัก";
    }
    if (empty($task_date)) {
        $errors[] = "กรุณาระบุวันที่ต้องดูแล";
    }
    if ($task_type_id <= 0) {
        $errors[] = "กรุณาเลือกประเภทงานดูแล";
    }
    if (empty($description)) {
        $errors[] = "กรุณากรอกรายละเอียดงาน";
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=care_tasks");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO daily_care_tasks (booking_item_id, pet_id, task_date, task_type_id, description, status) VALUES (:booking_item_id, :pet_id, :task_date, :task_type_id, :description, 'pending')");
        $stmt->execute([
            ':booking_item_id' => $booking_item_id,
            ':pet_id' => $pet_id,
            ':task_date' => $task_date,
            ':task_type_id' => $task_type_id,
            ':description' => $description
        ]);

        $_SESSION['msg_success'] = "เพิ่มงานดูแลสำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเพิ่มงานดูแลได้";
    }

    if (isset($_POST['return_to_booking']) && (int) $_POST['return_to_booking'] > 0) {
        header("Location: ?page=booking_detail&id=" . (int) $_POST['return_to_booking']);
    } else {
        header("Location: ?page=care_tasks&date={$task_date}");
    }
    exit();
}

// ─── EDIT TASK ───
if ($sub_action === 'edit') {
    $task_id = (int) ($_POST['task_id'] ?? 0);
    $task_date = trim($_POST['task_date'] ?? '');
    $task_type_id = (int) ($_POST['task_type_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Validation
    $errors = [];
    if ($task_id <= 0) {
        $errors[] = "ไม่พบข้อมูลงานดูแล";
    }
    if (empty($task_date)) {
        $errors[] = "กรุณาระบุวันที่ต้องดูแล";
    }
    if ($task_type_id <= 0) {
        $errors[] = "กรุณาเลือกประเภทงานดูแล";
    }
    if (empty($description)) {
        $errors[] = "กรุณากรอกรายละเอียดงาน";
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=care_tasks");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE daily_care_tasks SET task_date = :task_date, task_type_id = :task_type_id, description = :description WHERE id = :id");
        $stmt->execute([
            ':task_date' => $task_date,
            ':task_type_id' => $task_type_id,
            ':description' => $description,
            ':id' => $task_id
        ]);

        $_SESSION['msg_success'] = "แก้ไขงานดูแลสำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถแก้ไขงานดูแลได้";
    }

    if (isset($_POST['return_to_booking']) && (int) $_POST['return_to_booking'] > 0) {
        header("Location: ?page=booking_detail&id=" . (int) $_POST['return_to_booking']);
    } else {
        header("Location: ?page=care_tasks&date={$task_date}");
    }
    exit();
}

// ─── TOGGLE STATUS ───
if ($sub_action === 'toggle_status') {
    $task_id = (int) ($_POST['task_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');

    if ($task_id <= 0 || !in_array($new_status, ['pending', 'completed'])) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
        header("Location: ?page=care_tasks");
        exit();
    }

    try {
        if ($new_status === 'completed') {
            $stmt = $pdo->prepare("UPDATE daily_care_tasks SET status = 'completed', completed_at = NOW(), completed_by_employee_id = :emp_id WHERE id = :id");
            $stmt->execute([':emp_id' => $employee_id, ':id' => $task_id]);
            $_SESSION['msg_success'] = "บันทึกการทำรายการสำเร็จแล้ว";
        } else {
            $stmt = $pdo->prepare("UPDATE daily_care_tasks SET status = 'pending', completed_at = NULL, completed_by_employee_id = NULL WHERE id = :id");
            $stmt->execute([':id' => $task_id]);
            $_SESSION['msg_success'] = "ยกเลิกสถานะเสร็จสิ้นสำเร็จ";
        }
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถอัปเดตสถานะได้";
    }

    if (isset($_POST['return_to_booking']) && (int) $_POST['return_to_booking'] > 0) {
        header("Location: ?page=booking_detail&id=" . (int) $_POST['return_to_booking']);
    } else {
        // Capture the current URL parameter for date to redirect back smoothly
        $redirect_date = isset($_POST['current_date_filter']) ? trim($_POST['current_date_filter']) : '';
        header("Location: ?page=care_tasks" . ($redirect_date ? "&date={$redirect_date}" : ""));
    }
    exit();
}

// ─── DELETE TASK ───
if ($sub_action === 'delete') {
    $task_id = (int) ($_POST['task_id'] ?? 0);

    if ($task_id <= 0) {
        $_SESSION['msg_error'] = "ไม่พบข้อมูลงานดูแล";
        header("Location: ?page=care_tasks");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM daily_care_tasks WHERE id = :id");
        $stmt->execute([':id' => $task_id]);
        $_SESSION['msg_success'] = "ลบงานดูแลสำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถลบงานได้";
    }

    if (isset($_POST['return_to_booking']) && (int) $_POST['return_to_booking'] > 0) {
        header("Location: ?page=booking_detail&id=" . (int) $_POST['return_to_booking']);
    } else {
        $redirect_date = isset($_POST['current_date_filter']) ? trim($_POST['current_date_filter']) : '';
        header("Location: ?page=care_tasks" . ($redirect_date ? "&date={$redirect_date}" : ""));
    }
    exit();
}

// Fallback — unknown action
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=care_tasks");
exit();
