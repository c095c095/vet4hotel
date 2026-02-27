<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROCESS BOOKING — VET4 HOTEL
// Handle single form submission for Admin Create Booking
// ═══════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=bookings");
    exit();
}

if (!isset($_SESSION['employee_id'])) {
    $_SESSION['msg_error'] = 'กรุณาเข้าสู่ระบบก่อน';
    header("Location: ?page=login");
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Receive POST data
$customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
$check_in_date = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
$check_out_date = isset($_POST['check_out_date']) ? $_POST['check_out_date'] : '';
$room_type_id = isset($_POST['room_type_id']) ? (int) $_POST['room_type_id'] : 0;
$pet_ids = isset($_POST['pet_ids']) && is_array($_POST['pet_ids']) ? $_POST['pet_ids'] : [];
$service_ids = isset($_POST['service_ids']) && is_array($_POST['service_ids']) ? $_POST['service_ids'] : [];
$special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';

// Validation
if ($customer_id <= 0 || empty($check_in_date) || empty($check_out_date) || $room_type_id <= 0 || empty($pet_ids)) {
    $_SESSION['msg_error'] = 'ข้อมูลไม่ครบถ้วน กรุณาเลือกข้อมูลให้ครบเพื่อสร้างการจอง';
    header("Location: ?page=booking_create");
    exit();
}

if (strtotime($check_in_date) >= strtotime($check_out_date)) {
    $_SESSION['msg_error'] = 'วันที่เช็คเอาท์ต้องมาทีหลังวันที่เช็คอิน';
    header("Location: ?page=booking_create");
    exit();
}

$nights = max(1, round((strtotime($check_out_date) - strtotime($check_in_date)) / 86400));
$total_pets = count($pet_ids);

try {
    $pdo->beginTransaction();

    // 1. Verify Room Availability and get a Physical Room
    $stmt = $pdo->prepare("SELECT id FROM rooms 
                           WHERE room_type_id = :rt AND status = 'active' AND deleted_at IS NULL
                           AND NOT EXISTS (
                               SELECT 1 FROM booking_items bi
                               JOIN bookings b ON b.id = bi.booking_id
                               WHERE bi.room_id = rooms.id AND b.status != 'cancelled'
                               AND bi.check_in_date < :cout AND bi.check_out_date > :cin
                           ) LIMIT 1");
    $stmt->execute([
        ':rt' => $room_type_id,
        ':cin' => $check_in_date,
        ':cout' => $check_out_date
    ]);

    $available_room_id = $stmt->fetchColumn();

    if (!$available_room_id) {
        throw new Exception('ขออภัย ห้องพักประเภทที่คุณเลือกเต็มในช่วงเวลาดังกล่าว');
    }

    // 2. Calculate Room Price
    $stmt = $pdo->prepare("SELECT base_price_per_night FROM room_types WHERE id = ?");
    $stmt->execute([$room_type_id]);
    $room_price = $stmt->fetchColumn();
    $room_subtotal = $room_price * $nights;

    $grand_total = $room_subtotal;

    // 3. Generate Booking Reference
    $date_prefix = date('Ymd');
    $stmt = $pdo->query("SELECT MAX(id) FROM bookings");
    $last_id = $stmt->fetchColumn() ?: 0;
    $booking_ref = 'BK-' . $date_prefix . '-' . str_pad($last_id + 1, 3, '0', STR_PAD_LEFT);

    // 4. Create Booking
    $stmt = $pdo->prepare("INSERT INTO bookings (booking_ref, customer_id, subtotal_amount, net_amount, status, special_requests) 
                           VALUES (:ref, :cid, :subtotal, :net, 'pending_payment', :note)");
    $stmt->execute([
        ':ref' => $booking_ref,
        ':cid' => $customer_id,
        ':subtotal' => $grand_total, // Will update later if there are services
        ':net' => $grand_total,
        ':note' => $special_requests
    ]);
    $booking_id = $pdo->lastInsertId();

    // 5. Create Booking Item (Room assignment)
    $stmt = $pdo->prepare("INSERT INTO booking_items (booking_id, room_id, check_in_date, check_out_date, locked_unit_price, subtotal)
                           VALUES (:bid, :rid, :cin, :cout, :price, :subtotal)");
    $stmt->execute([
        ':bid' => $booking_id,
        ':rid' => $available_room_id,
        ':cin' => $check_in_date,
        ':cout' => $check_out_date,
        ':price' => $room_price,
        ':subtotal' => $room_subtotal
    ]);
    $booking_item_id = $pdo->lastInsertId();

    // 6. Map Pets to Booking Item
    $stmt_pet = $pdo->prepare("INSERT INTO booking_item_pets (booking_item_id, pet_id) VALUES (?, ?)");
    foreach ($pet_ids as $pid) {
        $stmt_pet->execute([$booking_item_id, $pid]);
    }

    // 7. Add Services (if any)
    if (!empty($service_ids)) {
        $services_total = 0;
        $in = str_repeat('?,', count($service_ids) - 1) . '?';
        $stmt_sv = $pdo->prepare("SELECT id, price, charge_type FROM services WHERE id IN ($in)");
        $stmt_sv->execute($service_ids);
        $svc_data = $stmt_sv->fetchAll(PDO::FETCH_ASSOC);

        $stmt_insert_sv = $pdo->prepare("INSERT INTO booking_services (booking_id, booking_item_id, service_id, quantity, locked_unit_price, total_price)
                                         VALUES (:bid, :bi_id, :sid, 1, :price, :total)");
        foreach ($svc_data as $sv) {
            if ($sv['charge_type'] === 'per_night') {
                $st_total = $sv['price'] * $nights;
            } elseif ($sv['charge_type'] === 'per_pet') {
                $st_total = $sv['price'] * $total_pets;
            } else {
                $st_total = $sv['price']; // per_stay
            }

            $stmt_insert_sv->execute([
                ':bid' => $booking_id,
                ':bi_id' => $booking_item_id,
                ':sid' => $sv['id'],
                ':price' => $sv['price'],
                ':total' => $st_total
            ]);
            $services_total += $st_total;
        }

        // Update booking totals
        if ($services_total > 0) {
            $grand_total += $services_total;
            $stmt_upd = $pdo->prepare("UPDATE bookings SET subtotal_amount = :st, net_amount = :nt WHERE id = :bid");
            $stmt_upd->execute([
                ':st' => $grand_total,
                ':nt' => $grand_total,
                ':bid' => $booking_id
            ]);
        }
    }

    $pdo->commit();

    $_SESSION['msg_success'] = "สร้างรายการจองสำเร็จ (Ref: {$booking_ref})";
    header("Location: ?page=bookings");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    header("Location: ?page=booking_create");
    exit();
}
