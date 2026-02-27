<?php
// ═══════════════════════════════════════════════════════════
// DASHBOARD DATA LAYER — VET4 HOTEL
// Pure logic: all dashboard metrics via PDO prepared statements
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

// ── 1. KPI: Total Bookings Created Today ──────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$kpi_bookings_today = (int) $stmt->fetchColumn();

// ── 2. KPI: Active Check-ins (currently staying) ──────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'");
$stmt->execute();
$kpi_active_checkins = (int) $stmt->fetchColumn();

// ── 3. KPI: Pending Payment Verifications ─────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
$stmt->execute();
$kpi_pending_payments = (int) $stmt->fetchColumn();

// ── 4. KPI: Monthly Revenue (verified payments) ──────────
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
    WHERE status = 'verified'
      AND DATE(paid_at) BETWEEN ? AND ?
");
$stmt->execute([$month_start, $month_end]);
$kpi_monthly_revenue = (float) $stmt->fetchColumn();

// ── 5. Room Occupancy ─────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE status = 'active' AND deleted_at IS NULL");
$stmt->execute();
$total_active_rooms = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT bi.room_id)
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    WHERE b.status = 'checked_in'
      AND bi.check_in_date <= ?
      AND bi.check_out_date > ?
");
$stmt->execute([$today, $today]);
$occupied_rooms = (int) $stmt->fetchColumn();

$available_rooms = $total_active_rooms - $occupied_rooms;
$occupancy_pct = $total_active_rooms > 0 ? round(($occupied_rooms / $total_active_rooms) * 100) : 0;

// Maintenance rooms
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance' AND deleted_at IS NULL");
$stmt->execute();
$maintenance_rooms = (int) $stmt->fetchColumn();

// ── 6. Today's Check-ins ──────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        b.booking_ref,
        b.status AS booking_status,
        c.first_name, c.last_name, c.phone,
        bi.check_in_date, bi.check_out_date,
        r.room_number,
        rt.name AS room_type_name,
        GROUP_CONCAT(p.name SEPARATOR ', ') AS pet_names
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    JOIN customers c ON c.id = b.customer_id
    JOIN rooms r ON r.id = bi.room_id
    JOIN room_types rt ON rt.id = r.room_type_id
    LEFT JOIN booking_item_pets bip ON bip.booking_item_id = bi.id
    LEFT JOIN pets p ON p.id = bip.pet_id
    WHERE bi.check_in_date = ?
      AND b.status IN ('confirmed', 'checked_in')
    GROUP BY bi.id
    ORDER BY b.created_at DESC
    LIMIT 20
");
$stmt->execute([$today]);
$todays_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 7. Today's Check-outs ─────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        b.booking_ref,
        b.status AS booking_status,
        c.first_name, c.last_name, c.phone,
        bi.check_in_date, bi.check_out_date,
        r.room_number,
        rt.name AS room_type_name,
        GROUP_CONCAT(p.name SEPARATOR ', ') AS pet_names
    FROM booking_items bi
    JOIN bookings b ON b.id = bi.booking_id
    JOIN customers c ON c.id = b.customer_id
    JOIN rooms r ON r.id = bi.room_id
    JOIN room_types rt ON rt.id = r.room_type_id
    LEFT JOIN booking_item_pets bip ON bip.booking_item_id = bi.id
    LEFT JOIN pets p ON p.id = bip.pet_id
    WHERE bi.check_out_date = ?
      AND b.status IN ('checked_in', 'checked_out')
    GROUP BY bi.id
    ORDER BY b.created_at DESC
    LIMIT 20
");
$stmt->execute([$today]);
$todays_checkouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 8. Pending Care Tasks Today ───────────────────────────
$stmt = $pdo->prepare("
    SELECT
        dct.id AS task_id,
        dct.description,
        dct.status AS task_status,
        ctt.name AS task_type_name,
        p.name AS pet_name,
        p.is_aggressive,
        s.name AS species_name,
        r.room_number,
        bi.check_in_date, bi.check_out_date
    FROM daily_care_tasks dct
    JOIN care_task_types ctt ON ctt.id = dct.task_type_id
    JOIN pets p ON p.id = dct.pet_id
    JOIN species s ON s.id = p.species_id
    JOIN booking_items bi ON bi.id = dct.booking_item_id
    JOIN rooms r ON r.id = bi.room_id
    WHERE dct.task_date = ?
      AND dct.status = 'pending'
    ORDER BY p.is_aggressive DESC, dct.id ASC
    LIMIT 30
");
$stmt->execute([$today]);
$pending_care_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 9. Aggressive Pets Currently In Stay ──────────────────
$stmt = $pdo->prepare("
    SELECT
        p.name AS pet_name,
        s.name AS species_name,
        br.name AS breed_name,
        p.behavior_note,
        r.room_number,
        c.first_name AS owner_first, c.last_name AS owner_last, c.phone AS owner_phone
    FROM booking_item_pets bip
    JOIN booking_items bi ON bi.id = bip.booking_item_id
    JOIN bookings b ON b.id = bi.booking_id
    JOIN pets p ON p.id = bip.pet_id
    JOIN species s ON s.id = p.species_id
    LEFT JOIN breeds br ON br.id = p.breed_id
    JOIN rooms r ON r.id = bi.room_id
    JOIN customers c ON c.id = b.customer_id
    WHERE b.status = 'checked_in'
      AND p.is_aggressive = 1
      AND bi.check_in_date <= ?
      AND bi.check_out_date > ?
    ORDER BY r.room_number ASC
");
$stmt->execute([$today, $today]);
$aggressive_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 10. Recent Bookings (Last 10) ─────────────────────────
$stmt = $pdo->prepare("
    SELECT
        b.id AS booking_id,
        b.booking_ref,
        b.status,
        b.net_amount,
        b.created_at,
        c.first_name, c.last_name,
        (SELECT COUNT(*) FROM booking_items WHERE booking_id = b.id) AS room_count,
        (SELECT MIN(check_in_date) FROM booking_items WHERE booking_id = b.id) AS first_checkin,
        (SELECT MAX(check_out_date) FROM booking_items WHERE booking_id = b.id) AS last_checkout
    FROM bookings b
    JOIN customers c ON c.id = b.customer_id
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 11. Room Status Distribution ──────────────────────────
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) AS count
    FROM rooms
    WHERE deleted_at IS NULL
    GROUP BY status
");
$stmt->execute();
$room_status_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$room_status_dist = [];
foreach ($room_status_rows as $row) {
    $room_status_dist[$row['status']] = (int) $row['count'];
}

// ── 12. Total Customers ───────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL");
$stmt->execute();
$total_customers = (int) $stmt->fetchColumn();

// ── 13. Total Pets ────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE deleted_at IS NULL");
$stmt->execute();
$total_pets = (int) $stmt->fetchColumn();
