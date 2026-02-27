<?php
// ═══════════════════════════════════════════════════════════
// PROCESS CART — VET4 HOTEL
// จัดการตะกร้า: ลบรายการ, ใช้โค้ดส่วนลด, ยืนยันการจอง
// ═══════════════════════════════════════════════════════════

if (!isset($pdo))
    exit('No direct access allowed.');

if (session_status() === PHP_SESSION_NONE)
    session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=cart');
    exit();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: ?page=login');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart = &$_SESSION['booking_cart'];
if (!isset($cart))
    $cart = [];

// ─────────────────────────────────────────────────────────
// ACTION: Remove item from cart
// ─────────────────────────────────────────────────────────
if (isset($_POST['remove_item'])) {
    $index = (int) $_POST['remove_index'];
    if (isset($cart[$index])) {
        unset($cart[$index]);
        $cart = array_values($cart); // Re-index
    }
    // ถ้าตะกร้าว่างให้ลบโปรโมชันด้วย
    if (empty($cart)) {
        unset($_SESSION['booking_promo']);
    }
    $_SESSION['msg_success'] = 'ลบรายการออกจากตะกร้าแล้ว';
    header('Location: ?page=cart');
    exit();
}

// ─────────────────────────────────────────────────────────
// ACTION: Apply promo code
// ─────────────────────────────────────────────────────────
if (isset($_POST['apply_promo'])) {
    $code = trim($_POST['promo_code'] ?? '');
    if (empty($code)) {
        $_SESSION['msg_error'] = 'กรุณากรอกรหัสโปรโมชัน';
        header('Location: ?page=cart');
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM promotions 
            WHERE code = ? 
              AND is_active = 1 
              AND start_date <= NOW() 
              AND end_date >= NOW()
            LIMIT 1
        ");
        $stmt->execute([$code]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            $_SESSION['msg_error'] = 'รหัสโปรโมชันไม่ถูกต้องหรือหมดอายุแล้ว';
            header('Location: ?page=cart');
            exit();
        }

        // เช็คโควต้าการใช้งาน
        if ($promo['usage_limit'] !== null && $promo['used_count'] >= $promo['usage_limit']) {
            $_SESSION['msg_error'] = 'โปรโมชันนี้ถูกใช้ครบจำนวนแล้ว';
            header('Location: ?page=cart');
            exit();
        }

        // คำนวณยอดรวมเพื่อเช็ค min_booking_amount
        $subtotal = calculate_cart_subtotal($cart, $pdo);
        if ($subtotal < (float) $promo['min_booking_amount']) {
            $_SESSION['msg_error'] = 'ยอดจองขั้นต่ำสำหรับโค้ดนี้คือ ฿' . number_format($promo['min_booking_amount']);
            header('Location: ?page=cart');
            exit();
        }

        $_SESSION['booking_promo'] = $promo;
        $_SESSION['msg_success'] = 'ใช้โค้ดส่วนลด "' . sanitize($promo['title']) . '" สำเร็จ!';

    } catch (PDOException $e) {
        $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการตรวจสอบโค้ด';
    }

    header('Location: ?page=cart');
    exit();
}

// ─────────────────────────────────────────────────────────
// ACTION: Remove promo code
// ─────────────────────────────────────────────────────────
if (isset($_POST['remove_promo'])) {
    unset($_SESSION['booking_promo']);
    $_SESSION['msg_success'] = 'ลบโค้ดส่วนลดแล้ว';
    header('Location: ?page=cart');
    exit();
}

// ─────────────────────────────────────────────────────────
// ACTION: Confirm booking
// ─────────────────────────────────────────────────────────
if (isset($_POST['confirm_booking'])) {
    if (empty($cart)) {
        $_SESSION['msg_error'] = 'ตะกร้าของคุณว่างเปล่า';
        header('Location: ?page=cart');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. คำนวณยอดรวม + ส่วนลด
        $subtotal = calculate_cart_subtotal($cart, $pdo);
        $discount_amount = 0;
        $promotion_id = null;
        $promo = $_SESSION['booking_promo'] ?? null;

        if ($promo) {
            $promotion_id = (int) $promo['id'];
            if ($promo['discount_type'] === 'percentage') {
                $discount_amount = $subtotal * ((float) $promo['discount_value'] / 100);
                if ($promo['max_discount_amount'] !== null && $discount_amount > (float) $promo['max_discount_amount']) {
                    $discount_amount = (float) $promo['max_discount_amount'];
                }
            } else {
                $discount_amount = (float) $promo['discount_value'];
            }
            $discount_amount = min($discount_amount, $subtotal);
        }

        $net_amount = $subtotal - $discount_amount;

        // 2. สร้าง Booking Reference
        $booking_ref = 'BK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $max_attempts = 999;
        while (true) {
            if (--$max_attempts <= 0) {
                $pdo->rollBack();
                $_SESSION['msg_error'] = 'ไม่สามารถสร้างหมายเลขการจองที่ไม่ซ้ำได้ กรุณาลองใหม่';
                header('Location: ?page=cart');
                exit();
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_ref = ?");
            $stmt->execute([$booking_ref]);
            if ($stmt->fetchColumn() == 0) {
                break; // ไม่ซ้ำ
            }
            $booking_ref = 'BK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        // 3. Insert bookings (Header)
        $special_requests = trim($_POST['special_requests'] ?? '');
        $stmt = $pdo->prepare("
            INSERT INTO bookings (booking_ref, customer_id, subtotal_amount, promotion_id, discount_amount, net_amount, status, special_requests, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending_payment', ?, NOW())
        ");
        $stmt->execute([$booking_ref, $customer_id, $subtotal, $promotion_id, $discount_amount, $net_amount, $special_requests ?: null]);
        $booking_id = (int) $pdo->lastInsertId();

        // 4. Insert booking_items + booking_item_pets + booking_services
        foreach ($cart as $item) {
            $room_type_id = (int) $item['room_type_id'];
            $check_in = $item['check_in_date'];
            $check_out = $item['check_out_date'];
            $pet_ids = (array) ($item['pet_ids'] ?? []);
            $service_ids = (array) ($item['service_ids'] ?? []);

            // 4a. หาห้องว่าง (room ที่ไม่ได้ถูกจองในช่วงวันที่ซ้อนทับ)
            $stmt = $pdo->prepare("
                SELECT r.id FROM rooms r
                WHERE r.room_type_id = ?
                  AND r.status = 'active'
                  AND r.deleted_at IS NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM booking_items bi
                      JOIN bookings b ON b.id = bi.booking_id
                      WHERE bi.room_id = r.id
                        AND b.status NOT IN ('cancelled')
                        AND bi.check_in_date < ?
                        AND bi.check_out_date > ?
                  )
                LIMIT 1
            ");
            $stmt->execute([$room_type_id, $check_out, $check_in]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) {
                $pdo->rollBack();
                $_SESSION['msg_error'] = 'ขออภัย ห้องพักประเภทที่เลือกเต็มแล้วสำหรับวันดังกล่าว กรุณาลองใหม่';
                header('Location: ?page=cart');
                exit();
            }

            $room_id = (int) $room['id'];

            // 4b. คำนวณราคาห้องพร้อม Seasonal Pricing
            $item_subtotal = calculate_room_price($room_type_id, $check_in, $check_out, $pdo);

            // 4c. ดึง base_price_per_night สำหรับ locked_unit_price
            $stmt = $pdo->prepare("SELECT base_price_per_night FROM room_types WHERE id = ?");
            $stmt->execute([$room_type_id]);
            $locked_price = (float) $stmt->fetchColumn();

            // 4d. Insert booking_items
            $stmt = $pdo->prepare("
                INSERT INTO booking_items (booking_id, room_id, check_in_date, check_out_date, locked_unit_price, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$booking_id, $room_id, $check_in, $check_out, $locked_price, $item_subtotal]);
            $booking_item_id = (int) $pdo->lastInsertId();

            // 4e. Insert booking_item_pets
            if (!empty($pet_ids)) {
                $stmt = $pdo->prepare("INSERT INTO booking_item_pets (booking_item_id, pet_id) VALUES (?, ?)");
                foreach ($pet_ids as $pet_id) {
                    $stmt->execute([$booking_item_id, (int) $pet_id]);
                }
            }

            // 4f. Insert booking_services
            if (!empty($service_ids)) {
                foreach ($service_ids as $sid) {
                    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
                    $stmt->execute([(int) $sid]);
                    $svc = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($svc) {
                        $svc_price = (float) $svc['price'];
                        $quantity = 1;

                        // คำนวณราคาตาม charge_type
                        $nights = max(1, round((strtotime($check_out) - strtotime($check_in)) / 86400));
                        $pet_count = count($pet_ids);

                        if ($svc['charge_type'] === 'per_night') {
                            $total_svc = $svc_price * $nights;
                            $quantity = $nights;
                        } elseif ($svc['charge_type'] === 'per_pet') {
                            $total_svc = $svc_price * $pet_count;
                            $quantity = $pet_count;
                        } else {
                            $total_svc = $svc_price;
                        }

                        $stmt = $pdo->prepare("
                            INSERT INTO booking_services (booking_id, booking_item_id, service_id, quantity, locked_unit_price, total_price) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$booking_id, $booking_item_id, (int) $sid, $quantity, $svc_price, $total_svc]);
                    }
                }
            }
        }

        // 5. อัปเดตจำนวนการใช้โปรโมชัน
        if ($promotion_id) {
            $stmt = $pdo->prepare("UPDATE promotions SET used_count = used_count + 1 WHERE id = ?");
            $stmt->execute([$promotion_id]);
        }

        $pdo->commit();

        // 6. ล้าง session ตะกร้า
        unset($_SESSION['booking_cart'], $_SESSION['booking_promo'], $_SESSION['booking_form']);

        $_SESSION['msg_success'] = 'จองห้องพักสำเร็จ! หมายเลขการจอง: ' . $booking_ref;
        header('Location: ?page=booking_detail&id=' . $booking_id);
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองอีกครั้ง';
        header('Location: ?page=cart');
        exit();
    }
}

// Fallback
header('Location: ?page=cart');
exit();

// ═══════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════

/**
 * คำนวณราคาห้องพักต่อคืนพร้อม Seasonal Pricing
 */
function calculate_room_price($room_type_id, $check_in, $check_out, $pdo)
{
    $stmt = $pdo->prepare("SELECT base_price_per_night FROM room_types WHERE id = ?");
    $stmt->execute([$room_type_id]);
    $base_price = (float) $stmt->fetchColumn();

    // ดึง seasonal pricings ที่ active
    $stmt = $pdo->prepare("
        SELECT start_date, end_date, price_multiplier_percent 
        FROM seasonal_pricings 
        WHERE is_active = 1 
          AND start_date <= ? 
          AND end_date >= ?
    ");
    $stmt->execute([$check_out, $check_in]);
    $seasonals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    $current = strtotime($check_in);
    $end = strtotime($check_out);

    while ($current < $end) {
        $current_date = date('Y-m-d', $current);
        $price_for_night = $base_price;

        foreach ($seasonals as $season) {
            if ($current_date >= $season['start_date'] && $current_date <= $season['end_date']) {
                $multiplier = 1 + ((float) $season['price_multiplier_percent'] / 100);
                $price_for_night = $base_price * $multiplier;
                break;
            }
        }

        $total += $price_for_night;
        $current = strtotime('+1 day', $current);
    }

    return round($total, 2);
}

/**
 * คำนวณยอดรวมทั้งตะกร้า (ห้อง + บริการเสริม)
 */
function calculate_cart_subtotal($cart, $pdo)
{
    $subtotal = 0;

    foreach ($cart as $item) {
        // ค่าห้อง
        $subtotal += calculate_room_price(
            (int) $item['room_type_id'],
            $item['check_in_date'],
            $item['check_out_date'],
            $pdo
        );

        // ค่าบริการเสริม
        $service_ids = (array) ($item['service_ids'] ?? []);
        $pet_ids = (array) ($item['pet_ids'] ?? []);
        $nights = max(1, round((strtotime($item['check_out_date']) - strtotime($item['check_in_date'])) / 86400));

        foreach ($service_ids as $sid) {
            $stmt = $pdo->prepare("SELECT price, charge_type FROM services WHERE id = ? AND is_active = 1");
            $stmt->execute([(int) $sid]);
            $svc = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($svc) {
                $svc_price = (float) $svc['price'];
                if ($svc['charge_type'] === 'per_night') {
                    $subtotal += $svc_price * $nights;
                } elseif ($svc['charge_type'] === 'per_pet') {
                    $subtotal += $svc_price * count($pet_ids);
                } else {
                    $subtotal += $svc_price;
                }
            }
        }
    }

    return round($subtotal, 2);
}
