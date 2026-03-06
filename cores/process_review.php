<?php
// ═══════════════════════════════════════════════════════════
// CUSTOMER REVIEW PROCESSOR — VET4 HOTEL
// Handles submitting a review for a checked-out booking
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['customer_id'];
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['msg_error'] = "กรุณาให้คะแนน 1-5 ดาว";
        header("Location: ?page=booking_detail&id=" . $booking_id);
        exit();
    }

    // Validate comment
    if (empty($comment)) {
        $_SESSION['msg_error'] = "กรุณากรอกข้อความรีวิว";
        header("Location: ?page=booking_detail&id=" . $booking_id);
        exit();
    }

    try {
        // Verify booking belongs to customer and is checked_out
        $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND customer_id = ? LIMIT 1");
        $stmt->execute([$booking_id, $customer_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $_SESSION['msg_error'] = "ไม่พบข้อมูลการจองนี้";
            header("Location: ?page=booking_history");
            exit();
        }

        if ($booking['status'] !== 'checked_out') {
            $_SESSION['msg_error'] = "สามารถรีวิวได้เฉพาะการจองที่เช็คเอาท์แล้วเท่านั้น";
            header("Location: ?page=booking_detail&id=" . $booking_id);
            exit();
        }

        // Check if review already exists (UNIQUE constraint backup)
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ? LIMIT 1");
        $stmt->execute([$booking_id]);
        if ($stmt->fetch()) {
            $_SESSION['msg_error'] = "คุณได้รีวิวการจองนี้ไปแล้ว";
            header("Location: ?page=booking_detail&id=" . $booking_id);
            exit();
        }

        // Insert review (is_published = 0, pending moderation)
        $stmt = $pdo->prepare("INSERT INTO reviews (booking_id, customer_id, rating, comment, is_published) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$booking_id, $customer_id, $rating, $comment]);

        $_SESSION['msg_success'] = "ส่งรีวิวเรียบร้อยแล้ว ขอบคุณสำหรับความคิดเห็นค่ะ ♥";
    } catch (PDOException $e) {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: ?page=booking_detail&id=" . $booking_id);
    exit();
}
