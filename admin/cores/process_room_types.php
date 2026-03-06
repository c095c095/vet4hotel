<?php
// ═══════════════════════════════════════════════════════════
// ADMIN ROOM TYPES PROCESSOR — VET4 HOTEL
// Handles Create, Update, Toggle Active, and Delete for room types
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    require_once __DIR__ . '/../../cores/config.php';
    require_once __DIR__ . '/../../cores/database.php';
    require_once __DIR__ . '/../../cores/functions.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=room_types");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

$action = trim($_POST['action'] ?? '');
// Ensure sub_action fallback logic is safe
$sub_action = trim($_POST['sub_action'] ?? '');

// ─── ADD ROOM TYPE ───
if ($sub_action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $base_price_per_night = isset($_POST['base_price_per_night']) ? (float) $_POST['base_price_per_night'] : 0;
    $max_pets = isset($_POST['max_pets']) ? (int) $_POST['max_pets'] : 1;
    $size_sqm = isset($_POST['size_sqm']) && $_POST['size_sqm'] !== '' ? (float) $_POST['size_sqm'] : null;

    // Validation
    $errors = [];
    if (empty($name))
        $errors[] = "กรุณากรอกชื่อประเภทห้องพัก";
    if ($base_price_per_night <= 0)
        $errors[] = "ราคาต่อคืนต้องมากกว่า 0";
    if ($max_pets < 1)
        $errors[] = "จำนวนสัตว์เลี้ยงสูงสุดต้องเป็น 1 หรือมากกว่า";

    // Check duplicate name
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM room_types WHERE name = :name");
        $stmt->execute([':name' => $name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "ชื่อประเภทห้องพัก \"{$name}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=room_types");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO room_types (name, description, base_price_per_night, max_pets, size_sqm, is_active) 
            VALUES (:name, :desc, :price, :max_pets, :size, 1)");
        $stmt->execute([
            ':name' => $name,
            ':desc' => $description,
            ':price' => $base_price_per_night,
            ':max_pets' => $max_pets,
            ':size' => $size_sqm,
        ]);
        $room_type_id = $pdo->lastInsertId();

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/room_types/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_exts) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $new_filename = 'rt_' . $room_type_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = 'uploads/room_types/' . $new_filename;
                    $stmt_img = $pdo->prepare("INSERT INTO room_type_images (room_type_id, image_url, is_primary) VALUES (?, ?, 1)");
                    $stmt_img->execute([$room_type_id, $image_url]);
                }
            }
        }

        $_SESSION['msg_success'] = "เพิ่มประเภทห้องพัก \"{$name}\" สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเพิ่มประเภทห้องพักได้";
    }

    header("Location: ?page=room_types");
    exit();
}

// ─── EDIT ROOM TYPE ───
if ($sub_action === 'edit') {
    $room_type_id = (int) ($_POST['room_type_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $base_price_per_night = isset($_POST['base_price_per_night']) ? (float) $_POST['base_price_per_night'] : 0;
    $max_pets = isset($_POST['max_pets']) ? (int) $_POST['max_pets'] : 1;
    $size_sqm = isset($_POST['size_sqm']) && $_POST['size_sqm'] !== '' ? (float) $_POST['size_sqm'] : null;

    // Validation
    $errors = [];
    if ($room_type_id <= 0)
        $errors[] = "ไม่พบข้อมูลประเภทห้องพัก";
    if (empty($name))
        $errors[] = "กรุณากรอกชื่อประเภทห้องพัก";
    if ($base_price_per_night <= 0)
        $errors[] = "ราคาต่อคืนต้องมากกว่า 0";
    if ($max_pets < 1)
        $errors[] = "จำนวนสัตว์เลี้ยงสูงสุดต้องเป็น 1 หรือมากกว่า";

    // Check duplicate name (exclude self)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM room_types WHERE name = :name AND id != :id");
        $stmt->execute([':name' => $name, ':id' => $room_type_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "ชื่อประเภทห้องพัก \"{$name}\" มีอยู่ในระบบแล้ว";
        }
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
        header("Location: ?page=room_types");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE room_types SET 
            name = :name, description = :desc, base_price_per_night = :price, 
            max_pets = :max_pets, size_sqm = :size 
            WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':desc' => $description,
            ':price' => $base_price_per_night,
            ':max_pets' => $max_pets,
            ':size' => $size_sqm,
            ':id' => $room_type_id,
        ]);

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/room_types/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_exts) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $new_filename = 'rt_' . $room_type_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = 'uploads/room_types/' . $new_filename;
                    $stmt_old = $pdo->prepare("SELECT image_url FROM room_type_images WHERE room_type_id = ? AND is_primary = 1");
                    $stmt_old->execute([$room_type_id]);
                    $old_img = $stmt_old->fetch();
                    if ($old_img && $old_img['image_url']) {
                        $old_path = '../' . ltrim($old_img['image_url'], '/');
                        if (file_exists($old_path))
                            unlink($old_path);
                        $stmt_img = $pdo->prepare("UPDATE room_type_images SET image_url = ? WHERE room_type_id = ? AND is_primary = 1");
                        $stmt_img->execute([$image_url, $room_type_id]);
                    } else {
                        $stmt_img = $pdo->prepare("INSERT INTO room_type_images (room_type_id, image_url, is_primary) VALUES (?, ?, 1)");
                        $stmt_img->execute([$room_type_id, $image_url]);
                    }
                }
            }
        }

        $_SESSION['msg_success'] = "แก้ไขประเภทห้องพัก \"{$name}\" สำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถแก้ไขประเภทห้องพักได้";
    }

    header("Location: ?page=room_types");
    exit();
}

// ─── TOGGLE ACTIVE ───
if ($sub_action === 'toggle_active') {
    $room_type_id = (int) ($_POST['room_type_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);

    if ($room_type_id <= 0 || !in_array($new_status, [0, 1])) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
        header("Location: ?page=room_types");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE room_types SET is_active = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $room_type_id]);

        $label = $new_status ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $_SESSION['msg_success'] = "เปลี่ยนสถานะสำเร็จ";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถเปลี่ยนสถานะได้";
    }

    header("Location: ?page=room_types");
    exit();
}

// ─── HARD DELETE ───
if ($sub_action === 'delete') {
    $room_type_id = (int) ($_POST['room_type_id'] ?? 0);

    if ($room_type_id <= 0) {
        $_SESSION['msg_error'] = "ไม่พบข้อมูลประเภทห้องพัก";
        header("Location: ?page=room_types");
        exit();
    }

    // Check for existing rooms using this room type
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM rooms WHERE room_type_id = :id");
    $stmt->execute([':id' => $room_type_id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['msg_error'] = "ไม่สามารถลบประเภทห้องพักนี้ได้ เนื่องจากมีห้องพักที่อ้างอิงถึงประเภทนี้ แนะนำให้ทำการปิดการใช้งานแทน";
        header("Location: ?page=room_types");
        exit();
    }

    try {
        // delete actual files
        $stmt_img = $pdo->prepare("SELECT image_url FROM room_type_images WHERE room_type_id = ?");
        $stmt_img->execute([$room_type_id]);
        $images = $stmt_img->fetchAll();
        foreach ($images as $img) {
            if ($img['image_url']) {
                $file_path = '../' . ltrim($img['image_url'], '/');
                if (file_exists($file_path))
                    unlink($file_path);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM room_types WHERE id = :id");
        $stmt->execute([':id' => $room_type_id]);

        $_SESSION['msg_success'] = "ลบประเภทห้องพักสำเร็จแล้ว";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: ไม่สามารถลบประเภทห้องพักได้";
    }

    header("Location: ?page=room_types");
    exit();
}

// Fallback — unknown action
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=room_types");
exit();
