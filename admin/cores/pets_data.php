<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PETS DATA CORE — VET4 HOTEL
// Handles fetching, filtering, and pagination for pets list
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../../cores/config.php';
require_once __DIR__ . '/../../cores/database.php';
require_once __DIR__ . '/../../cores/functions.php';

// Check if admin/staff is logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

// 1. Pagination Setup
$limit = 15;
$page = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// 2. Filters Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$species_filter = isset($_GET['species']) ? trim($_GET['species']) : '';
$gender_filter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$aggressive_filter = isset($_GET['aggressive']) ? trim($_GET['aggressive']) : '';

// 3. Build Base Query and Parameters
$where_clauses = ["p.deleted_at IS NULL"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(p.name LIKE :search OR CONCAT(c.first_name, ' ', c.last_name) LIKE :search OR b.name LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($species_filter !== '' && $species_filter !== 'all') {
    $where_clauses[] = "p.species_id = :species_id";
    $params[':species_id'] = (int) $species_filter;
}

if ($gender_filter !== '' && $gender_filter !== 'all') {
    $where_clauses[] = "p.gender = :gender";
    $params[':gender'] = $gender_filter;
}

if ($aggressive_filter !== '' && $aggressive_filter !== 'all') {
    $where_clauses[] = "p.is_aggressive = :is_aggressive";
    $params[':is_aggressive'] = (int) $aggressive_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// 4. Count Total Records for Pagination
$count_query = "SELECT COUNT(p.id) 
                FROM pets p 
                LEFT JOIN customers c ON p.customer_id = c.id 
                LEFT JOIN breeds b ON p.breed_id = b.id 
                WHERE {$where_sql}";
$stmt = $pdo->prepare($count_query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 5. Fetch Pets Data with joins
$query = "SELECT 
            p.id,
            p.name,
            p.dob,
            p.weight_kg,
            p.gender,
            p.vet_name,
            p.vet_phone,
            p.is_aggressive,
            p.behavior_note,
            p.customer_id,
            p.species_id,
            p.breed_id,
            p.created_at,
            s.name AS species_name,
            b.name AS breed_name,
            c.first_name AS owner_first_name,
            c.last_name AS owner_last_name,
            c.phone AS owner_phone,
            (SELECT COUNT(pv.id) FROM pet_vaccinations pv WHERE pv.pet_id = p.id) AS vaccination_count,
            (SELECT COUNT(pv.id) FROM pet_vaccinations pv WHERE pv.pet_id = p.id AND pv.expiry_date >= CURDATE()) AS valid_vaccination_count,
            (SELECT COUNT(DISTINCT bip.booking_item_id) 
             FROM booking_item_pets bip 
             JOIN booking_items bi ON bi.id = bip.booking_item_id 
             JOIN bookings bk ON bk.id = bi.booking_id 
             WHERE bip.pet_id = p.id
            ) AS booking_count,
            (SELECT COUNT(DISTINCT bip.booking_item_id) 
             FROM booking_item_pets bip 
             JOIN booking_items bi ON bi.id = bip.booking_item_id 
             JOIN bookings bk ON bk.id = bi.booking_id 
             WHERE bip.pet_id = p.id AND bk.status IN ('confirmed', 'checked_in')
            ) AS active_booking_count
          FROM pets p
          LEFT JOIN species s ON p.species_id = s.id
          LEFT JOIN breeds b ON p.breed_id = b.id
          LEFT JOIN customers c ON p.customer_id = c.id
          WHERE {$where_sql}
          ORDER BY p.is_aggressive DESC, p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pets = $stmt->fetchAll();

// 6. Fetch all species for filter dropdown
$species_list = $pdo->query("SELECT id, name FROM species ORDER BY name ASC")->fetchAll();

// 6.1. Fetch all active customers for Add Pet modal
$customers_list = $pdo->query("SELECT id, first_name, last_name, phone FROM customers WHERE is_active = 1 AND deleted_at IS NULL ORDER BY first_name ASC")->fetchAll();

// 7. Fetch all breeds grouped by species (for edit modal)
$breeds_by_species = [];
$breeds_all = $pdo->query("SELECT id, species_id, name FROM breeds ORDER BY name ASC")->fetchAll();
foreach ($breeds_all as $br) {
    $breeds_by_species[$br['species_id']][] = $br;
}

// 8. Aggregate Stats
$stats_query = "SELECT 
                    COUNT(id) AS total,
                    SUM(is_aggressive = 1) AS aggressive_count,
                    SUM(gender = 'male') AS male_count,
                    SUM(gender = 'female') AS female_count
                FROM pets 
                WHERE deleted_at IS NULL";
$stats = $pdo->query($stats_query)->fetch();

// Species breakdown
$species_stats = $pdo->query("SELECT s.name, COUNT(p.id) AS cnt 
                              FROM pets p 
                              JOIN species s ON p.species_id = s.id 
                              WHERE p.deleted_at IS NULL 
                              GROUP BY p.species_id, s.name 
                              ORDER BY cnt DESC")->fetchAll();

// Pets currently checked in
$stmt = $pdo->query("SELECT COUNT(DISTINCT bip.pet_id) 
                     FROM booking_item_pets bip 
                     JOIN booking_items bi ON bi.id = bip.booking_item_id 
                     JOIN bookings bk ON bk.id = bi.booking_id 
                     WHERE bk.status = 'checked_in'");
$checked_in_pet_count = $stmt->fetchColumn();

// 9. Gender config for filter UI
$gender_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    'male' => ['label' => 'ผู้', 'class' => 'badge-info'],
    'female' => ['label' => 'เมีย', 'class' => 'badge-secondary'],
    'spayed' => ['label' => 'ทำหมันแล้ว (เมีย)', 'class' => 'badge-accent'],
    'neutered' => ['label' => 'ทำหมันแล้ว (ผู้)', 'class' => 'badge-accent'],
    'unknown' => ['label' => 'ไม่ระบุ', 'class' => 'badge-ghost'],
];

$aggressive_config = [
    'all' => ['label' => 'ทั้งหมด', 'class' => 'badge-ghost'],
    '1' => ['label' => 'ดุ/ก้าวร้าว', 'class' => 'badge-error'],
    '0' => ['label' => 'ปกติ', 'class' => 'badge-success'],
];
