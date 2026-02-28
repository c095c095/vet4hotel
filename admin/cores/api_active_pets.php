<?php
// ═══════════════════════════════════════════════════════════
// ADMIN API: ACTIVE PETS — VET4 HOTEL
// Returns list of pets currently in active bookings (checked_in, confirmed)
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
require_once __DIR__ . '/../../cores/functions.php';

session_start();

// Check if admin/staff is logged in
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    $where_sql = "b.status IN ('confirmed', 'checked_in')";
    $params = [];

    if ($search !== '') {
        $where_sql .= " AND (p.name LIKE :search OR b.booking_ref LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    $query = "
        SELECT 
            b.id AS booking_id, 
            b.booking_ref, 
            bi.id AS booking_item_id, 
            r.room_number,
            p.id AS pet_id, 
            p.name AS pet_name, 
            p.weight_kg, 
            sp.name AS species_name,
            c.first_name AS customer_first,
            c.last_name AS customer_last
        FROM bookings b
        JOIN booking_items bi ON b.id = bi.booking_id
        JOIN rooms r ON bi.room_id = r.id
        JOIN booking_item_pets bip ON bi.id = bip.booking_item_id
        JOIN pets p ON bip.pet_id = p.id
        JOIN species sp ON p.species_id = sp.id
        JOIN customers c ON b.customer_id = c.id
        WHERE {$where_sql}
        ORDER BY b.booking_ref ASC, p.name ASC
    ";

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->execute();

    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $pets]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
