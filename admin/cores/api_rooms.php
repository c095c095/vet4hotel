<?php
// ═══════════════════════════════════════════════════════════
// ADMIN API: FETCH AVAILABLE ROOMS — VET4 HOTEL
// ═══════════════════════════════════════════════════════════
require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$check_in = isset($_GET['check_in']) ? trim($_GET['check_in']) : '';
$check_out = isset($_GET['check_out']) ? trim($_GET['check_out']) : '';

if (empty($check_in) || empty($check_out) || strtotime($check_in) >= strtotime($check_out)) {
    echo json_encode(['success' => false, 'error' => 'Invalid dates']);
    exit;
}

try {
    // 1. Get room types and how many physical rooms are available for them during these dates
    $sql = "SELECT rt.id, rt.name, rt.base_price_per_night, rt.max_pets, 
                   COUNT(DISTINCT r.id) AS available_rooms
            FROM room_types rt
            JOIN rooms r ON r.room_type_id = rt.id AND r.status = 'active' AND r.deleted_at IS NULL
            AND NOT EXISTS (
                SELECT 1
                FROM booking_items bi
                JOIN bookings b ON b.id = bi.booking_id
                WHERE bi.room_id = r.id 
                  AND b.status NOT IN ('cancelled') 
                  AND bi.check_in_date < :check_out 
                  AND bi.check_out_date > :check_in
            )
            WHERE rt.is_active = 1
            GROUP BY rt.id
            HAVING available_rooms > 0
            ORDER BY rt.base_price_per_night ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':check_in' => $check_in,
        ':check_out' => $check_out
    ]);

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rooms]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error']);
}
