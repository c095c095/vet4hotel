<?php
// ═══════════════════════════════════════════════════════════
// CMS REVIEWS PROCESSOR - VET4 HOTEL ADMIN
// Handles approving (publishing) or hiding customer reviews
// ═══════════════════════════════════════════════════════════

session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: ./?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_action = $_POST['sub_action'] ?? '';

    if ($sub_action === 'toggle_review') {
        $review_id = intval($_POST['review_id']);
        $new_status = intval($_POST['new_status']); // 1 = publish, 0 = hide

        try {
            $stmt = $pdo->prepare("UPDATE reviews SET is_published = ? WHERE id = ?");
            $stmt->execute([$new_status, $review_id]);
            $_SESSION['msg_success'] = "อัปเดตสถานะรีวิวสำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } elseif ($sub_action === 'delete_review') {
        $review_id = intval($_POST['review_id']);

        try {
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            $_SESSION['msg_success'] = "ลบรีวิวสำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }

    header("Location: ./?page=cms_reviews");
    exit();
}
