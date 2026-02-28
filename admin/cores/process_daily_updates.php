<?php
// ═══════════════════════════════════════════════════════════
// DAILY UPDATES PROCESSOR - VET4 HOTEL ADMIN
// Handles inserting and deleting pet daily updates
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['employee_id'])) {
    header("Location: ./?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_action = $_POST['sub_action'] ?? '';

    if ($sub_action === 'add_update') {
        $booking_item_id = intval($_POST['booking_item_id']);
        $pet_id = intval($_POST['pet_id']);
        $update_type_id = intval($_POST['update_type_id']);
        $message = trim($_POST['message']);
        $employee_id = $_SESSION['employee_id'];

        // Handle Image Upload
        $image_url = null;
        if (isset($_FILES['update_image']) && $_FILES['update_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/daily_updates/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['update_image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed_exts)) {
                $_SESSION['msg_error'] = "ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะ JPG, PNG, WEBP";
                header("Location: ./?page=daily_updates");
                exit();
            }

            // Limit size to 5MB
            if ($_FILES['update_image']['size'] > 5 * 1024 * 1024) {
                $_SESSION['msg_error'] = "ขนาดไฟล์ใหญ่เกินไป (สูงสุด 5MB)";
                header("Location: ./?page=daily_updates");
                exit();
            }

            $new_filename = 'update_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['update_image']['tmp_name'], $upload_path)) {
                $image_url = 'uploads/daily_updates/' . $new_filename;
            } else {
                $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                header("Location: ./?page=daily_updates");
                exit();
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO daily_updates (booking_item_id, pet_id, employee_id, update_type_id, message, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$booking_item_id, $pet_id, $employee_id, $update_type_id, $message, $image_url]);

            $_SESSION['msg_success'] = "บันทึกอัปเดตสถานะสำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }

        // Redirect back to either daily_updates or booking_detail depending on where they came from
        $redirect = $_POST['redirect_to'] ?? './?page=daily_updates';
        header("Location: " . $redirect);
        exit();

    } elseif ($sub_action === 'delete_update') {
        $update_id = intval($_POST['update_id']);

        try {
            // get image URL to delete file
            $stmt = $pdo->prepare("SELECT image_url FROM daily_updates WHERE id = ?");
            $stmt->execute([$update_id]);
            $update = $stmt->fetch();

            if ($update && $update['image_url']) {
                $file_path = '../' . $update['image_url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM daily_updates WHERE id = ?");
            $stmt->execute([$update_id]);
            $_SESSION['msg_success'] = "ลบรายการอัปเดตสำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }

        $redirect = $_POST['redirect_to'] ?? './?page=daily_updates';
        header("Location: " . $redirect);
        exit();
    }
}
