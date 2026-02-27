<?php
// ═══════════════════════════════════════════════════════════
// ADMIN API: SEARCH CUSTOMERS — VET4 HOTEL
// ═══════════════════════════════════════════════════════════
require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    $sql = "SELECT id, first_name, last_name, phone, email 
            FROM customers 
            WHERE is_active = 1 AND deleted_at IS NULL";

    $params = [];
    if ($search !== '') {
        $sql .= " AND (first_name LIKE :q OR last_name LIKE :q OR phone LIKE :q OR email LIKE :q)";
        $params[':q'] = "%$search%";
    }

    $sql .= " ORDER BY first_name ASC LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $customers]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error']);
}
