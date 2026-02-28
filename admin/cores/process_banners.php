<?php
// ═══════════════════════════════════════════════════════════
// CMS BANNERS PROCESSOR - VET4 HOTEL ADMIN
// Handles uploading, updating, reordering, and deleting banners
// ═══════════════════════════════════════════════════════════

session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: ./?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_action = $_POST['sub_action'] ?? '';

    if ($sub_action === 'add_banner') {
        $title = trim($_POST['title']);
        $target_url = trim($_POST['target_url']);
        $display_order = intval($_POST['display_order']);

        // Handle Image Upload
        $image_url = null;
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/banners/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed_exts)) {
                $_SESSION['msg_error'] = "ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะ JPG, PNG, WEBP";
                header("Location: ./?page=cms_banners");
                exit();
            }

            if ($_FILES['banner_image']['size'] > 5 * 1024 * 1024) {
                $_SESSION['msg_error'] = "ขนาดไฟล์ใหญ่เกินไป (สูงสุด 5MB)";
                header("Location: ./page=cms_banners");
                exit();
            }

            $new_filename = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
                $image_url = '../../uploads/banners/' . $new_filename;
            } else {
                $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                header("Location: ./?page=cms_banners");
                exit();
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO banners (title, image_url, target_url, display_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $image_url, $target_url, $display_order]);
                $_SESSION['msg_success'] = "เพิ่มแบนเนอร์ใหม่สำเร็จ";
            } catch (PDOException $e) {
                $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        } else {
            $_SESSION['msg_error'] = "กรุณาแนบรูปภาพแบนเนอร์";
        }
    } elseif ($sub_action === 'edit_banner') {
        $banner_id = intval($_POST['banner_id']);
        $title = trim($_POST['title']);
        $target_url = trim($_POST['target_url']);
        $display_order = intval($_POST['display_order']);

        try {
            $stmt = $pdo->prepare("UPDATE banners SET title = ?, target_url = ?, display_order = ? WHERE id = ?");
            $stmt->execute([$title, $target_url, $display_order, $banner_id]);
            $_SESSION['msg_success'] = "อัปเดตแบนเนอร์สำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }

    } elseif ($sub_action === 'toggle_banner') {
        $banner_id = intval($_POST['banner_id']);
        $new_status = intval($_POST['new_status']);

        try {
            $stmt = $pdo->prepare("UPDATE banners SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $banner_id]);
            $_SESSION['msg_success'] = "อัปเดตสถานะสำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }

    } elseif ($sub_action === 'delete_banner') {
        $banner_id = intval($_POST['banner_id']);

        try {
            // get image URL to delete file
            $stmt = $pdo->prepare("SELECT image_url FROM banners WHERE id = ?");
            $stmt->execute([$banner_id]);
            $banner = $stmt->fetch();

            if ($banner && $banner['image_url']) {
                $file_path = '../../' . $banner['image_url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$banner_id]);
            $_SESSION['msg_success'] = "ลบแบนเนอร์สำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }

    header("Location: ./?page=cms_banners");
    exit();
}
