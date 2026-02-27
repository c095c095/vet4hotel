<?php
// ═══════════════════════════════════════════════════════════
// ADMIN API: FETCH PETS BY CUSTOMER — VET4 HOTEL
// ═══════════════════════════════════════════════════════════
require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$customer_id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : 0;

if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Customer ID']);
    exit;
}

try {
    $sql = "SELECT p.id, p.name, p.is_aggressive, s.name as species, b.name as breed 
            FROM pets p 
            LEFT JOIN species s ON p.species_id = s.id 
            LEFT JOIN breeds b ON p.breed_id = b.id 
            WHERE p.customer_id = :cid AND p.deleted_at IS NULL
            ORDER BY p.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cid' => $customer_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $pets]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error']);
}
