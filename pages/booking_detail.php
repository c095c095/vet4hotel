<?php
// ═══════════════════════════════════════════════════════════
// BOOKING DETAIL PAGE — VET4 HOTEL
// รายละเอียดการจองแบบเต็มหน้า — จัดกลุ่มบริการตามห้องพัก
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login&redirect=" . urlencode('?page=booking_detail&id=' . ($_GET['id'] ?? '')));
    exit();
}

$customer_id = $_SESSION['customer_id'];
$booking_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($booking_id <= 0) {
    header("Location: ?page=booking_history");
    exit();
}

// ═══════════════════════════════════════════════════════════
// DATA FETCHING
// ═══════════════════════════════════════════════════════════

$booking = null;
$items = [];
$item_pets_map = [];       // booking_item_id => [pets]
$item_services_map = [];   // booking_item_id => [services]
$general_services = [];    // services with no booking_item_id (legacy)
$payments = [];
$transports = [];

try {
    // 1. Booking header (with promotion)
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            p.code AS promo_code,
            p.title AS promo_title,
            p.discount_type AS promo_discount_type,
            p.discount_value AS promo_discount_value
        FROM bookings b
        LEFT JOIN promotions p ON b.promotion_id = p.id
        WHERE b.id = ? AND b.customer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        header("Location: ?page=booking_history");
        echo "<script> window.location.href = '?page=booking_history'; </script>";
        exit();
    }

    // 2. Booking items (rooms)
    $stmt = $pdo->prepare("
        SELECT 
            bi.*,
            r.room_number,
            r.floor_level,
            rt.name AS room_type_name,
            rt.base_price_per_night,
            rt.max_pets,
            rt.size_sqm,
            (SELECT image_url FROM room_type_images WHERE room_type_id = rt.id AND is_primary = 1 LIMIT 1) AS room_image
        FROM booking_items bi
        JOIN rooms r ON bi.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE bi.booking_id = ?
        ORDER BY bi.check_in_date ASC
    ");
    $stmt->execute([$booking_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Pets per booking item
    $all_item_ids = array_column($items, 'id');
    if (!empty($all_item_ids)) {
        $ph = implode(',', array_fill(0, count($all_item_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT 
                bip.booking_item_id,
                pet.id AS pet_id,
                pet.name AS pet_name,
                pet.species_id,
                sp.name AS species_name,
                br.name AS breed_name,
                pet.gender
            FROM booking_item_pets bip
            JOIN pets pet ON bip.pet_id = pet.id
            JOIN species sp ON pet.species_id = sp.id
            LEFT JOIN breeds br ON pet.breed_id = br.id
            WHERE bip.booking_item_id IN ($ph)
        ");
        $stmt->execute($all_item_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $item_pets_map[$row['booking_item_id']][] = $row;
        }
    }

    // 4. Services — grouped by booking_item_id
    $stmt = $pdo->prepare("
        SELECT 
            bs.*,
            s.name AS service_name,
            s.charge_type,
            pet.name AS pet_name
        FROM booking_services bs
        JOIN services s ON bs.service_id = s.id
        LEFT JOIN pets pet ON bs.pet_id = pet.id
        WHERE bs.booking_id = ?
        ORDER BY bs.id ASC
    ");
    $stmt->execute([$booking_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $svc) {
        if ($svc['booking_item_id']) {
            $item_services_map[$svc['booking_item_id']][] = $svc;
        } else {
            $general_services[] = $svc;
        }
    }

    // 5. Payments
    $stmt = $pdo->prepare("
        SELECT 
            pay.*,
            pc.name AS channel_name
        FROM payments pay
        LEFT JOIN payment_channels pc ON pay.payment_channel_id = pc.id
        WHERE pay.booking_id = ?
        ORDER BY pay.created_at ASC
    ");
    $stmt->execute([$booking_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5.5 Refunds for this booking
    $booking_refunds = [];
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            p.payment_type,
            p.amount AS original_payment_amount,
            pc.name AS channel_name
        FROM refunds r
        JOIN payments p ON r.payment_id = p.id
        LEFT JOIN payment_channels pc ON p.payment_channel_id = pc.id
        WHERE r.booking_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$booking_id]);
    $booking_refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5.6 Payments eligible for refund (only for cancelled bookings)
    $refundable_payments = [];
    if ($booking['status'] === 'cancelled') {
        $stmt = $pdo->prepare("
            SELECT 
                pay.id,
                pay.payment_type,
                pay.amount,
                pc.name AS channel_name,
                COALESCE((SELECT SUM(rf.refund_amount) FROM refunds rf WHERE rf.payment_id = pay.id AND rf.status IN ('pending', 'processed')), 0) AS already_refunded
            FROM payments pay
            LEFT JOIN payment_channels pc ON pay.payment_channel_id = pc.id
            WHERE pay.booking_id = ? AND pay.status = 'verified'
            HAVING (pay.amount - already_refunded) > 0
            ORDER BY pay.created_at DESC
        ");
        $stmt->execute([$booking_id]);
        $refundable_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. Daily Updates (สมุดพกสัตว์เลี้ยง)
    $daily_updates = [];
    if (in_array($booking['status'], ['checked_in', 'checked_out'])) {
        $stmt = $pdo->prepare("
            SELECT 
                du.*,
                dt.name AS type_name,
                dt.icon_class,
                p.name AS pet_name,
                p.species_id,
                rm.room_number,
                e.first_name AS emp_name
            FROM daily_updates du
            JOIN daily_update_types dt ON du.update_type_id = dt.id
            JOIN pets p ON du.pet_id = p.id
            JOIN booking_items bi ON du.booking_item_id = bi.id
            JOIN rooms rm ON bi.room_id = rm.id
            JOIN employees e ON du.employee_id = e.id
            WHERE bi.booking_id = ?
            ORDER BY du.created_at DESC
        ");
        $stmt->execute([$booking_id]);
        $daily_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Group daily updates by date
    $updates_by_date = [];
    foreach ($daily_updates as $upd) {
        $date_key = date('Y-m-d', strtotime($upd['created_at']));
        $updates_by_date[$date_key][] = $upd;
    }

    // 7. Review for this booking
    $existing_review = null;
    $stmt = $pdo->prepare("
        SELECT r.*, c.first_name, c.last_name
        FROM reviews r
        JOIN customers c ON r.customer_id = c.id
        WHERE r.booking_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$booking_id]);
    $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header("Location: ?page=booking_history");
    exit();
}

// ═══════════════════════════════════════════════════════════
// HELPER VALUES
// ═══════════════════════════════════════════════════════════

$status_config = [
    'pending_payment' => ['label' => 'รอชำระเงิน', 'badge' => 'badge-warning', 'icon' => 'clock', 'color' => 'text-warning', 'bg' => 'bg-warning/10'],
    'verifying_payment' => ['label' => 'กำลังตรวจสอบ', 'badge' => 'badge-info', 'icon' => 'search', 'color' => 'text-info', 'bg' => 'bg-info/10'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'badge' => 'badge-info', 'icon' => 'check-circle', 'color' => 'text-info', 'bg' => 'bg-info/10'],
    'checked_in' => ['label' => 'เข้าพักอยู่', 'badge' => 'badge-success', 'icon' => 'home', 'color' => 'text-success', 'bg' => 'bg-success/10'],
    'checked_out' => ['label' => 'เช็คเอาท์แล้ว', 'badge' => 'badge-neutral', 'icon' => 'log-out', 'color' => 'text-base-content/60', 'bg' => 'bg-base-200'],
    'cancelled' => ['label' => 'ยกเลิก', 'badge' => 'badge-error', 'icon' => 'x-circle', 'color' => 'text-error', 'bg' => 'bg-error/10'],
];

$payment_status_config = [
    'pending' => ['label' => 'รอตรวจสอบ', 'badge' => 'badge-warning'],
    'verified' => ['label' => 'ชำระแล้ว', 'badge' => 'badge-success'],
    'rejected' => ['label' => 'ถูกปฏิเสธ', 'badge' => 'badge-error'],
    'refunded' => ['label' => 'คืนเงินแล้ว', 'badge' => 'badge-info'],
];

$sCfg = $status_config[$booking['status']] ?? $status_config['pending_payment'];

// Thai date helpers
function thaiDateShort_d($date)
{
    if (!$date)
        return '-';
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $ts = strtotime($date);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = (int) date('Y', $ts) + 543;
    return "$d {$months[$m]} $y";
}

function thaiDateTime_d($datetime)
{
    if (!$datetime)
        return '-';
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $ts = strtotime($datetime);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = (int) date('Y', $ts) + 543;
    $time = date('H:i', $ts);
    return "$d {$months[$m]} $y เวลา $time น.";
}

function nightsCount_d($cin, $cout)
{
    return max(1, (int) ((strtotime($cout) - strtotime($cin)) / 86400));
}

// Aggregate stats
$total_nights = 0;
$total_room_cost = 0;
$total_service_cost = 0;
foreach ($items as $item) {
    $total_nights += nightsCount_d($item['check_in_date'], $item['check_out_date']);
    $total_room_cost += (float) $item['subtotal'];
}
foreach ($item_services_map as $svcs) {
    foreach ($svcs as $s)
        $total_service_cost += (float) $s['total_price'];
}
foreach ($general_services as $s) {
    $total_service_cost += (float) $s['total_price'];
}

$earliest_cin = !empty($items) ? min(array_column($items, 'check_in_date')) : null;
$latest_cout = !empty($items) ? max(array_column($items, 'check_out_date')) : null;
?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- BOOKING DETAIL UI                                          -->
<!-- ═══════════════════════════════════════════════════════════ -->

<section class="py-6 md:py-10 bg-base-200/50 min-h-[85vh] relative overflow-hidden">
    <!-- Floating decorations -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        <div class="floating-paw absolute top-[6%] left-[5%] opacity-15 text-primary" style="animation-delay:0.3s;">
            <i data-lucide="receipt" class="size-14"></i>
        </div>
        <div class="floating-paw absolute bottom-[4%] right-[8%] opacity-10 text-secondary"
            style="animation-delay:1.5s;">
            <i data-lucide="paw-print" class="size-18"></i>
        </div>
    </div>

    <div class="w-full max-w-4xl mx-auto px-4 relative z-10">

        <!-- ═══ BACK NAVIGATION ═══ -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-6">
            <a href="?page=booking_history"
                class="btn btn-ghost btn-sm gap-2 text-base-content/60 hover:text-primary transition-colors -ml-2">
                <i data-lucide="arrow-left" class="size-4"></i>
                กลับไปประวัติการจอง
            </a>
            <div class="flex gap-2 w-full sm:w-auto">
                <?php if ($booking['status'] === 'pending_payment'): ?>
                    <a href="?page=payment&id=<?php echo $booking['id']; ?>"
                        class="btn btn-primary gap-2 flex-1 sm:flex-none">
                        <i data-lucide="credit-card" class="size-4"></i>
                        ชำระเงิน
                    </a>
                <?php endif; ?>
            </div>
        </div>


        <!-- ═══ BOOKING HEADER CARD ═══ -->
        <div class="card bg-base-100 shadow-lg border border-base-200 overflow-hidden mb-6">
            <div class="card-body p-0">
                <!-- Status banner -->
                <div class="<?php echo $sCfg['bg']; ?> border-b border-base-200 px-5 py-4 sm:px-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary text-primary-content p-3 rounded-2xl shadow-md">
                                <i data-lucide="<?php echo $sCfg['icon']; ?>" class="size-6"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h1 class="text-xl sm:text-2xl font-black text-base-content">
                                        <?php echo sanitize($booking['booking_ref']); ?>
                                    </h1>
                                    <span class="badge <?php echo $sCfg['badge']; ?> gap-1.5 py-3 px-3">
                                        <i data-lucide="<?php echo $sCfg['icon']; ?>" class="size-3.5"></i>
                                        <?php echo $sCfg['label']; ?>
                                    </span>
                                </div>
                                <div class="text-sm text-base-content/50 mt-1 flex items-center gap-1.5">
                                    <i data-lucide="calendar-plus" class="size-3.5"></i>
                                    จองเมื่อ <?php echo thaiDateTime_d($booking['created_at']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-right sm:text-right">
                            <div class="text-xs text-base-content/50 font-medium uppercase tracking-wider">ยอดสุทธิ
                            </div>
                            <div class="text-3xl font-black text-primary mt-0.5">
                                ฿<?php echo number_format($booking['net_amount'], 0); ?>
                            </div>
                            <?php if ((float) $booking['discount_amount'] > 0): ?>
                                <div class="text-xs text-success flex items-center gap-1 justify-end mt-1">
                                    <i data-lucide="tag" class="size-3"></i>
                                    ประหยัด ฿<?php echo number_format($booking['discount_amount']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-base-200">
                    <div class="px-4 py-3 text-center">
                        <div class="text-xs text-base-content/50 font-medium">ห้องพัก</div>
                        <div class="text-lg font-bold text-base-content mt-0.5">
                            <?php echo count($items); ?> <span class="text-sm font-normal">ห้อง</span>
                        </div>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <div class="text-xs text-base-content/50 font-medium">จำนวนคืน</div>
                        <div class="text-lg font-bold text-base-content mt-0.5">
                            <?php echo $total_nights; ?> <span class="text-sm font-normal">คืน</span>
                        </div>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <div class="text-xs text-base-content/50 font-medium">เช็คอิน</div>
                        <div class="text-sm font-bold text-base-content mt-0.5">
                            <?php echo thaiDateShort_d($earliest_cin); ?>
                        </div>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <div class="text-xs text-base-content/50 font-medium">เช็คเอาท์</div>
                        <div class="text-sm font-bold text-base-content mt-0.5">
                            <?php echo thaiDateShort_d($latest_cout); ?>
                        </div>
                    </div>
                </div>
                <!-- Day counting remark -->
                <div
                    class="px-5 py-2.5 bg-info/5 border-t border-info/10 flex items-center gap-2 text-xs text-base-content/50">
                    <i data-lucide="info" class="size-3.5 text-info shrink-0"></i>
                    <span>หมายเหตุ: จำนวนคืนนับจากวันเช็คอินถึงวันเช็คเอาท์ เช่น เช็คอิน 14 → เช็คเอาท์ 15 = 1
                        คืน</span>
                </div>
            </div>
        </div>

        <!-- ═══ ROOM CARDS (Core – with grouped services) ═══ -->
        <div class="space-y-5 mb-6">
            <h2 class="text-lg font-bold text-base-content flex items-center gap-2">
                <div class="bg-primary/10 text-primary p-1.5 rounded-lg">
                    <i data-lucide="bed-double" class="size-5"></i>
                </div>
                ห้องพักและบริการ
            </h2>

            <?php foreach ($items as $idx => $item):
                $nights = nightsCount_d($item['check_in_date'], $item['check_out_date']);
                $itemPets = $item_pets_map[$item['id']] ?? [];
                $itemServices = $item_services_map[$item['id']] ?? [];
                $roomServiceTotal = 0;
                foreach ($itemServices as $s)
                    $roomServiceTotal += (float) $s['total_price'];
                ?>
                <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                    style="animation-delay: <?php echo $idx * 0.1; ?>s;">
                    <div class="card-body p-0">

                        <!-- Room header -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-base-200 bg-linear-to-r from-primary/5 to-transparent">
                            <div class="flex items-center gap-3">
                                <!-- Room image -->
                                <?php if ($item['room_image']): ?>
                                    <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 shadow-sm border border-base-200">
                                        <img src="<?php echo sanitize($item['room_image']); ?>"
                                            alt="<?php echo sanitize($item['room_type_name']); ?>"
                                            class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                                        <i data-lucide="bed-double" class="size-7 text-primary/50"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-bold text-lg text-base-content leading-tight">
                                        <?php echo sanitize($item['room_type_name']); ?>
                                    </h3>
                                    <div class="text-xs text-base-content/50 mt-0.5 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="door-open" class="size-3"></i>
                                            ห้อง <?php echo sanitize($item['room_number']); ?>
                                        </span>
                                        <span>·</span>
                                        <span>ชั้น <?php echo sanitize($item['floor_level']); ?></span>
                                        <?php if ($item['size_sqm']): ?>
                                            <span>·</span>
                                            <span><?php echo number_format($item['size_sqm'], 0); ?> ตร.ม.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xl font-black text-primary">
                                    ฿<?php echo number_format($item['subtotal']); ?></div>
                                <div class="text-[11px] text-base-content/40 mt-0.5">
                                    ฿<?php echo number_format($item['locked_unit_price']); ?>/คืน ×
                                    <?php echo $nights; ?> คืน
                                </div>
                            </div>
                        </div>

                        <!-- Room body content -->
                        <div class="px-5 py-4 space-y-4">

                            <!-- Dates -->
                            <div class="flex items-center gap-3 text-sm">
                                <div class="flex items-center gap-2 bg-base-200/60 rounded-xl px-3 py-2 flex-1">
                                    <i data-lucide="log-in" class="size-4 text-success"></i>
                                    <div>
                                        <div class="text-[10px] text-base-content/40 font-medium uppercase">เช็คอิน</div>
                                        <div class="font-semibold text-base-content text-xs">
                                            <?php echo thaiDateShort_d($item['check_in_date']); ?>
                                        </div>
                                    </div>
                                </div>
                                <i data-lucide="arrow-right" class="size-4 text-base-content/30 shrink-0"></i>
                                <div class="flex items-center gap-2 bg-base-200/60 rounded-xl px-3 py-2 flex-1">
                                    <i data-lucide="log-out" class="size-4 text-error"></i>
                                    <div>
                                        <div class="text-[10px] text-base-content/40 font-medium uppercase">เช็คเอาท์
                                        </div>
                                        <div class="font-semibold text-base-content text-xs">
                                            <?php echo thaiDateShort_d($item['check_out_date']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="badge badge-primary badge-outline badge-sm shrink-0">
                                    <?php echo $nights; ?> คืน
                                </div>
                            </div>

                            <!-- Pets in this room -->
                            <?php if (!empty($itemPets)): ?>
                                <div>
                                    <div
                                        class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i data-lucide="paw-print" class="size-3"></i>
                                        สัตว์เลี้ยงในห้องนี้ (<?php echo count($itemPets); ?> ตัว)
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($itemPets as $pet): ?>
                                            <div
                                                class="flex items-center gap-2 bg-primary/5 border border-primary/15 rounded-xl px-3 py-2">
                                                <div class="bg-primary/10 p-1 rounded-lg">
                                                    <?php if ($pet['species_id'] == 1): ?>
                                                        <i data-lucide="dog" class="size-4 text-primary"></i>
                                                    <?php elseif ($pet['species_id'] == 2): ?>
                                                        <i data-lucide="cat" class="size-4 text-primary"></i>
                                                    <?php else: ?>
                                                        <i data-lucide="paw-print" class="size-4 text-primary"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-sm text-base-content leading-tight">
                                                        <?php echo sanitize($pet['pet_name']); ?>
                                                    </div>
                                                    <div class="text-[10px] text-base-content/40">
                                                        <?php echo sanitize($pet['breed_name'] ?? $pet['species_name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Services for this room -->
                            <?php if (!empty($itemServices)): ?>
                                <div>
                                    <div
                                        class="text-[11px] font-semibold text-base-content/50 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i data-lucide="sparkles" class="size-3"></i>
                                        บริการเสริมของห้องนี้ (<?php echo count($itemServices); ?> รายการ)
                                    </div>
                                    <div class="space-y-1.5">
                                        <?php foreach ($itemServices as $svc):
                                            $chargeLabel = match ($svc['charge_type']) {
                                                'per_night' => 'ต่อคืน',
                                                'per_pet' => 'ต่อตัว',
                                                default => 'ต่อการเข้าพัก',
                                            };
                                            ?>
                                            <div
                                                class="flex items-center justify-between text-sm bg-accent/5 border border-accent/10 rounded-lg px-3 py-2.5">
                                                <div class="flex items-center gap-2 text-base-content/70 min-w-0">
                                                    <i data-lucide="plus-circle" class="size-3.5 text-accent shrink-0"></i>
                                                    <span class="truncate">
                                                        <?php echo sanitize($svc['service_name']); ?>
                                                    </span>
                                                    <?php if ($svc['pet_name']): ?>
                                                        <span
                                                            class="text-xs text-base-content/40">(<?php echo sanitize($svc['pet_name']); ?>)</span>
                                                    <?php endif; ?>
                                                    <span
                                                        class="badge badge-ghost badge-xs shrink-0"><?php echo $chargeLabel; ?></span>
                                                </div>
                                                <span class="font-semibold text-base-content shrink-0 ml-2">
                                                    ฿<?php echo number_format($svc['total_price']); ?>
                                                    <?php if ($svc['quantity'] > 1): ?>
                                                        <span class="text-xs text-base-content/40 font-normal">×
                                                            <?php echo $svc['quantity']; ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Room footer (subtotal) -->
                        <?php if (!empty($itemServices)): ?>
                            <div
                                class="px-5 py-3 bg-base-200/40 border-t border-base-200 flex items-center justify-between text-sm">
                                <span class="text-base-content/60">รวมห้องนี้ (ค่าห้อง + บริการ)</span>
                                <span
                                    class="font-bold text-primary text-base">฿<?php echo number_format((float) $item['subtotal'] + $roomServiceTotal); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ═══ GENERAL SERVICES (Legacy — no booking_item_id) ═══ -->
        <?php if (!empty($general_services)): ?>
            <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
                <div class="card-body p-5">
                    <h3
                        class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i data-lucide="sparkles" class="size-4 text-accent"></i> บริการเสริม
                    </h3>
                    <div class="space-y-2">
                        <?php foreach ($general_services as $svc):
                            $chargeLabel = match ($svc['charge_type']) {
                                'per_night' => 'ต่อคืน',
                                'per_pet' => 'ต่อตัว',
                                default => 'ต่อการเข้าพัก',
                            };
                            ?>
                            <div class="flex items-center justify-between text-sm bg-base-200/40 rounded-lg px-3 py-2.5">
                                <div class="flex items-center gap-2 text-base-content/70">
                                    <i data-lucide="plus-circle" class="size-3.5 text-accent"></i>
                                    <span><?php echo sanitize($svc['service_name']); ?></span>
                                    <?php if ($svc['pet_name']): ?>
                                        <span
                                            class="text-xs text-base-content/40">(<?php echo sanitize($svc['pet_name']); ?>)</span>
                                    <?php endif; ?>
                                    <span class="badge badge-ghost badge-xs"><?php echo $chargeLabel; ?></span>
                                </div>
                                <span class="font-medium shrink-0">
                                    ฿<?php echo number_format($svc['total_price']); ?>
                                    <?php if ($svc['quantity'] > 1): ?>
                                        <span class="text-xs text-base-content/40">× <?php echo $svc['quantity']; ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══ PAYMENT HISTORY ═══ -->
        <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
            <div class="card-body p-5">
                <h3
                    class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i data-lucide="credit-card" class="size-4"></i> ประวัติการชำระเงิน
                </h3>
                <?php if (!empty($payments)): ?>
                    <div class="space-y-2">
                        <?php foreach ($payments as $pay):
                            $pCfg = $payment_status_config[$pay['status']] ?? $payment_status_config['pending'];
                            $payTypeLabel = match ($pay['payment_type']) {
                                'deposit' => 'มัดจำ',
                                'full_payment' => 'ชำระเต็มจำนวน',
                                'balance_due' => 'ชำระส่วนที่เหลือ',
                                'extra_charge' => 'ค่าบริการเพิ่มเติม',
                                default => $pay['payment_type'],
                            };
                            ?>
                            <div
                                class="flex items-center justify-between rounded-xl border border-base-200 bg-base-200/30 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="bg-base-300 p-1.5 rounded-lg">
                                        <i data-lucide="banknote" class="size-4 text-base-content/60"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-base-content">
                                            <?php echo $payTypeLabel; ?>
                                        </div>
                                        <div class="text-[10px] text-base-content/40 mt-0.5">
                                            <?php echo thaiDateTime_d($pay['paid_at'] ?? $pay['created_at']); ?>
                                            <?php if ($pay['channel_name']): ?>
                                                · <?php echo sanitize($pay['channel_name']); ?>
                                            <?php endif; ?>
                                            <?php if ($pay['transaction_ref']): ?>
                                                · Ref: <?php echo sanitize($pay['transaction_ref']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-sm text-base-content">
                                        ฿<?php echo number_format($pay['amount']); ?></div>
                                    <span
                                        class="badge <?php echo $pCfg['badge']; ?> badge-xs mt-0.5"><?php echo $pCfg['label']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 rounded-xl bg-base-200/40 border border-dashed border-base-300">
                        <i data-lucide="credit-card" class="size-8 text-base-content/15 mx-auto mb-2"></i>
                        <p class="text-sm text-base-content/40">ยังไม่มีรายการชำระเงิน</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ REFUND STATUS ═══ -->
        <?php if (!empty($booking_refunds) || !empty($refundable_payments)): ?>
            <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3
                            class="text-sm font-semibold text-base-content/60 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="banknote" class="size-4 text-error"></i> สถานะการคืนเงิน
                        </h3>
                        <?php if (!empty($refundable_payments)): ?>
                            <button class="btn btn-sm btn-outline btn-error gap-1.5"
                                onclick="document.getElementById('modal-customer-refund').showModal()">
                                <i data-lucide="plus" class="size-3.5"></i>
                                ขอคืนเงิน
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($booking_refunds)): ?>
                        <div class="space-y-2.5">
                            <?php foreach ($booking_refunds as $rf):
                                $rf_pay_type = match ($rf['payment_type']) {
                                    'deposit' => 'มัดจำ',
                                    'full_payment' => 'ชำระเต็ม',
                                    'balance_due' => 'ส่วนที่เหลือ',
                                    'extra_charge' => 'เพิ่มเติม',
                                    default => $rf['payment_type'],
                                };
                                $rf_status_cfg = match ($rf['status']) {
                                    'pending' => ['label' => 'รอดำเนินการ', 'badge' => 'badge-warning', 'icon' => 'clock'],
                                    'processed' => ['label' => 'คืนเงินแล้ว', 'badge' => 'badge-success', 'icon' => 'check-circle'],
                                    'failed' => ['label' => 'ถูกปฏิเสธ', 'badge' => 'badge-error', 'icon' => 'x-circle'],
                                    default => ['label' => 'ไม่ทราบ', 'badge' => 'badge-ghost', 'icon' => 'help-circle'],
                                };
                                ?>
                                <div class="flex items-start gap-3 rounded-xl border border-base-200 bg-base-200/30 px-4 py-3">
                                    <!-- Status icon -->
                                    <div class="shrink-0 pt-0.5">
                                        <div
                                            class="w-9 h-9 rounded-full <?php echo $rf['status'] === 'processed' ? 'bg-success/10' : ($rf['status'] === 'failed' ? 'bg-error/10' : 'bg-warning/10'); ?> flex items-center justify-center">
                                            <i data-lucide="<?php echo $rf_status_cfg['icon']; ?>"
                                                class="size-4 <?php echo $rf['status'] === 'processed' ? 'text-success' : ($rf['status'] === 'failed' ? 'text-error' : 'text-warning'); ?>"></i>
                                        </div>
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <div>
                                                <div class="font-semibold text-sm text-base-content flex items-center gap-2">
                                                    ฿<?php echo number_format($rf['refund_amount'], 0); ?>
                                                    <span class="badge <?php echo $rf_status_cfg['badge']; ?> badge-xs gap-1">
                                                        <i data-lucide="<?php echo $rf_status_cfg['icon']; ?>" class="size-2.5"></i>
                                                        <?php echo $rf_status_cfg['label']; ?>
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-base-content/40 mt-0.5">
                                                    จาก<?php echo $rf_pay_type; ?>
                                                    (฿<?php echo number_format($rf['original_payment_amount'], 0); ?>)
                                                    <?php if ($rf['channel_name']): ?>
                                                        · <?php echo sanitize($rf['channel_name']); ?>
                                                    <?php endif; ?>
                                                    · <?php echo thaiDateTime_d($rf['created_at']); ?>
                                                </div>
                                            </div>
                                            <span class="badge badge-neutral badge-sm badge-outline">โอนเงินคืน</span>
                                        </div>
                                        <?php if ($rf['reason']): ?>
                                            <div class="text-xs text-base-content/50 mt-1.5 bg-base-200/60 rounded-lg px-3 py-2 italic">
                                                "<?php echo sanitize($rf['reason']); ?>"
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 rounded-xl bg-base-200/40 border border-dashed border-base-300">
                            <i data-lucide="banknote" class="size-8 text-base-content/15 mx-auto mb-2"></i>
                            <p class="text-sm text-base-content/40">ยังไม่มีรายการขอคืนเงิน</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══ SPECIAL REQUESTS ═══ -->
        <?php if ($booking['special_requests']): ?>
            <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
                <div class="card-body p-5">
                    <h3
                        class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i data-lucide="message-square" class="size-4"></i> คำขอพิเศษ
                    </h3>
                    <div class="bg-base-200/40 rounded-xl px-4 py-3 text-sm text-base-content/70 italic">
                        "<?php echo nl2br(sanitize($booking['special_requests'])); ?>"
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══ DAILY UPDATES TIMELINE (สมุดพกสัตว์เลี้ยง) ═══ -->
        <?php if (!empty($daily_updates)): ?>
            <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
                <div class="card-body p-0">
                    <!-- Section Header -->
                    <div
                        class="px-5 py-4 border-b border-base-200 bg-linear-to-r from-primary/5 to-transparent flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 text-primary p-2 rounded-xl">
                                <i data-lucide="camera" class="size-5"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-base-content">สมุดพกสัตว์เลี้ยง</h3>
                                <p class="text-[11px] text-base-content/50">อัปเดตรายวันจากทีมงานดูแลน้องๆ</p>
                            </div>
                        </div>
                        <span class="badge badge-primary badge-outline gap-1">
                            <i data-lucide="image" class="size-3"></i>
                            <?php echo count($daily_updates); ?> อัปเดต
                        </span>
                    </div>

                    <!-- Grouped by Date -->
                    <div class="divide-y divide-base-200">
                        <?php foreach ($updates_by_date as $date_key => $day_updates):
                            $is_today = ($date_key === date('Y-m-d'));
                            $day_label = $is_today ? 'วันนี้' : thaiDateShort_d($date_key);
                            ?>
                            <div class="collapse collapse-arrow bg-base-100" tabindex="0">
                                <input type="checkbox" <?php echo $is_today || array_key_first($updates_by_date) === $date_key ? 'checked' : ''; ?> />
                                <div class="collapse-title py-3 px-5 min-h-0">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar-days" class="size-4 text-primary"></i>
                                        <span class="font-semibold text-sm text-base-content"><?php echo $day_label; ?></span>
                                        <span class="badge badge-ghost badge-xs"><?php echo count($day_updates); ?>
                                            รายการ</span>
                                    </div>
                                </div>
                                <div class="collapse-content px-5 pb-4">
                                    <div class="space-y-3">
                                        <?php foreach ($day_updates as $upd): ?>
                                            <div class="flex gap-3 group/item">
                                                <!-- Icon -->
                                                <div class="shrink-0 pt-0.5">
                                                    <div
                                                        class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary ring-2 ring-base-100 group-hover/item:ring-primary/20 transition-all">
                                                        <i data-lucide="<?php echo htmlspecialchars($upd['icon_class'] ?? 'info'); ?>"
                                                            class="size-4"></i>
                                                    </div>
                                                </div>
                                                <!-- Content -->
                                                <div class="flex-1 min-w-0">
                                                    <div
                                                        class="bg-base-200/40 rounded-xl p-3 border border-base-200 hover:border-primary/20 transition-colors">
                                                        <!-- Header -->
                                                        <div class="flex items-center justify-between flex-wrap gap-1 mb-1.5">
                                                            <div class="flex items-center gap-2">
                                                                <span class="font-semibold text-sm text-base-content">
                                                                    <?php echo htmlspecialchars($upd['pet_name']); ?>
                                                                </span>
                                                                <span class="badge badge-primary badge-outline badge-xs">
                                                                    <?php echo htmlspecialchars($upd['type_name']); ?>
                                                                </span>
                                                            </div>
                                                            <span class="text-[10px] text-base-content/40 flex items-center gap-1">
                                                                <i data-lucide="clock" class="size-2.5"></i>
                                                                <?php echo date('H:i น.', strtotime($upd['created_at'])); ?>
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-base-content/50 mb-2 flex items-center gap-1">
                                                            <i data-lucide="door-open" class="size-3"></i>
                                                            ห้อง <?php echo htmlspecialchars($upd['room_number']); ?>
                                                            <span class="mx-1">·</span>
                                                            <i data-lucide="user" class="size-3"></i>
                                                            ดูแลโดย <?php echo htmlspecialchars($upd['emp_name']); ?>
                                                        </div>
                                                        <!-- Message -->
                                                        <p class="text-sm text-base-content/80 leading-relaxed">
                                                            <?php echo nl2br(htmlspecialchars($upd['message'])); ?>
                                                        </p>
                                                        <!-- Image -->
                                                        <?php if ($upd['image_url']): ?>
                                                            <div
                                                                class="mt-3 rounded-xl overflow-hidden border border-base-200 max-w-xs">
                                                                <img src="<?php echo htmlspecialchars($upd['image_url']); ?>"
                                                                    alt="อัปเดต <?php echo htmlspecialchars($upd['pet_name']); ?>"
                                                                    class="w-full h-auto object-cover max-h-56 cursor-pointer hover:opacity-90 hover:scale-[1.02] transition-all duration-200"
                                                                    onclick="window.open(this.src, '_blank')">
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══ PRICE SUMMARY ═══ -->
        <div class="card bg-base-100 shadow-lg border border-primary/20 overflow-hidden mb-6">
            <div class="card-body p-0">
                <div class="bg-linear-to-br from-primary/5 to-primary/10 p-5 sm:p-6">
                    <h3
                        class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="calculator" class="size-4"></i> สรุปยอดชำระ
                    </h3>
                    <div class="space-y-3 text-sm">
                        <!-- Room cost -->
                        <div class="flex justify-between items-center">
                            <span class="text-base-content/60 flex items-center gap-1.5">
                                <i data-lucide="bed-double" class="size-3.5"></i>
                                ค่าห้องพัก (<?php echo count($items); ?> ห้อง, <?php echo $total_nights; ?> คืน)
                            </span>
                            <span class="font-medium">฿<?php echo number_format($total_room_cost); ?></span>
                        </div>
                        <!-- Service cost -->
                        <?php if ($total_service_cost > 0): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-base-content/60 flex items-center gap-1.5">
                                    <i data-lucide="sparkles" class="size-3.5"></i>
                                    บริการเสริม
                                </span>
                                <span class="font-medium">฿<?php echo number_format($total_service_cost); ?></span>
                            </div>
                        <?php endif; ?>
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center pt-2 border-t border-base-content/10">
                            <span class="text-base-content/60">ยอดรวมก่อนส่วนลด</span>
                            <span class="font-medium">฿<?php echo number_format($booking['subtotal_amount']); ?></span>
                        </div>
                        <!-- Discount -->
                        <?php if ((float) $booking['discount_amount'] > 0): ?>
                            <div class="flex justify-between items-center text-success">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="ticket" class="size-3.5"></i>
                                    ส่วนลด
                                    <?php if ($booking['promo_code']): ?>
                                        <span
                                            class="badge badge-success badge-sm badge-outline"><?php echo sanitize($booking['promo_code']); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="font-bold">-฿<?php echo number_format($booking['discount_amount']); ?></span>
                            </div>
                        <?php endif; ?>
                        <!-- Net total -->
                        <div class="flex justify-between items-center pt-3 border-t-2 border-primary/20">
                            <span class="font-bold text-lg text-base-content">ยอดสุทธิ</span>
                            <span
                                class="font-black text-2xl text-primary">฿<?php echo number_format($booking['net_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ REVIEW SECTION ═══ -->
        <?php if ($booking['status'] === 'checked_out'): ?>
            <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
                <div class="card-body p-0">
                    <!-- Section Header -->
                    <div
                        class="px-5 py-4 border-b border-base-200 bg-linear-to-r from-warning/5 to-transparent flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-warning/10 text-warning p-2 rounded-xl">
                                <i data-lucide="star" class="size-5"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-base-content">รีวิวจากคุณ</h3>
                                <p class="text-[11px] text-base-content/50">แชร์ประสบการณ์การเข้าพักของน้องๆ
                                    ให้กำลังใจทีมงาน</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-5">
                        <?php if ($existing_review): ?>
                            <!-- Display existing review -->
                            <div class="space-y-4">
                                <!-- Rating -->
                                <div class="flex items-center gap-3">
                                    <div class="flex gap-0.5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i data-lucide="star"
                                                class="size-5 <?php echo $i <= $existing_review['rating'] ? 'fill-warning text-warning' : 'text-base-content/20'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span
                                        class="text-sm font-bold text-warning"><?php echo $existing_review['rating']; ?>.0</span>
                                    <?php if ($existing_review['is_published']): ?>
                                        <span class="badge badge-success badge-xs gap-1">
                                            <i data-lucide="check" class="size-2.5"></i> แสดงผลบนเว็บไซต์
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-ghost badge-xs gap-1">
                                            <i data-lucide="clock" class="size-2.5"></i> รอการตรวจสอบ
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Comment -->
                                <div class="bg-base-200/40 rounded-xl px-4 py-3 text-sm text-base-content/70 italic">
                                    "<?php echo nl2br(htmlspecialchars($existing_review['comment'] ?? '')); ?>"
                                </div>

                                <div class="text-[10px] text-base-content/40 flex items-center gap-1">
                                    <i data-lucide="calendar" class="size-3"></i>
                                    รีวิวเมื่อ <?php echo thaiDateTime_d($existing_review['created_at']); ?>
                                </div>

                                <!-- Staff reply -->
                                <?php if ($existing_review['staff_reply']): ?>
                                    <div class="bg-primary/5 border border-primary/15 rounded-xl px-4 py-3 mt-2">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <div class="bg-primary/10 p-1 rounded-lg">
                                                <i data-lucide="reply" class="size-3 text-primary"></i>
                                            </div>
                                            <span class="text-xs font-semibold text-primary">ตอบกลับจากทีมงาน VET4</span>
                                            <span class="text-[10px] text-base-content/40">
                                                <?php echo thaiDateTime_d($existing_review['staff_reply_at']); ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-base-content/70">
                                            <?php echo nl2br(htmlspecialchars($existing_review['staff_reply'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Review form -->
                            <div class="text-center py-2">
                                <p class="text-sm text-base-content/50 mb-4">
                                    คุณยังไม่ได้รีวิวการเข้าพักครั้งนี้ แชร์ประสบการณ์ให้พวกเราฟังหน่อยนะคะ 😊
                                </p>
                                <button class="btn btn-primary btn-sm gap-2 rounded-xl"
                                    onclick="document.getElementById('modal-review').showModal()">
                                    <i data-lucide="pen-line" class="size-4"></i>
                                    เขียนรีวิว
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ═══ BOTTOM ACTIONS ═══ -->
        <a href="?page=booking_history" class="btn btn-ghost gap-2 w-full sm:w-auto mb-6">
            <i data-lucide="arrow-left" class="size-4"></i>
            กลับไปประวัติการจอง
        </a>
    </div>
</section>

<!-- ═══════════ CUSTOMER REQUEST REFUND MODAL ═══════════ -->
<?php if (!empty($refundable_payments)): ?>
    <dialog id="modal-customer-refund" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box bg-base-100 rounded-t-3xl sm:rounded-3xl p-0 overflow-hidden shadow-2xl max-w-md">
            <div class="p-6 border-b border-base-200 flex items-center gap-3 bg-base-100/50">
                <div class="w-10 h-10 rounded-full bg-error/10 flex items-center justify-center text-error shrink-0">
                    <i data-lucide="banknote" class="size-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-base-content leading-tight">ขอคืนเงิน</h3>
                    <p class="text-sm text-base-content/60 mt-0.5">เลือกรายการชำระเงินและระบุยอดที่ต้องการคืน</p>
                </div>
                <form method="dialog" class="ml-auto">
                    <button
                        class="btn btn-sm btn-circle btn-ghost text-base-content/50 hover:text-base-content hover:bg-base-200">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </form>
            </div>

            <form action="?action=refund" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

                <div class="form-control">
                    <label class="label pt-0"><span class="label-text font-medium">เลือกรายการชำระเงิน <span
                                class="text-error">*</span></span></label>
                    <select name="payment_id" id="cust-refund-payment-select"
                        class="select select-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors"
                        required onchange="updateCustRefundMax()">
                        <option value="" disabled selected>-- เลือกรายการที่ต้องการคืนเงิน --</option>
                        <?php foreach ($refundable_payments as $rp):
                            $rp_type = match ($rp['payment_type']) {
                                'deposit' => 'มัดจำ',
                                'full_payment' => 'ชำระเต็ม',
                                'balance_due' => 'ส่วนที่เหลือ',
                                'extra_charge' => 'เพิ่มเติม',
                                default => $rp['payment_type'],
                            };
                            $rp_remaining = $rp['amount'] - $rp['already_refunded'];
                            ?>
                            <option value="<?php echo $rp['id']; ?>" data-max="<?php echo $rp_remaining; ?>"
                                data-original="<?php echo $rp['amount']; ?>">
                                <?php echo $rp_type; ?> — ฿<?php echo number_format($rp['amount'], 2); ?>
                                <?php if ($rp['already_refunded'] > 0): ?>
                                    (คืนไปแล้ว ฿<?php echo number_format($rp['already_refunded'], 2); ?>)
                                <?php endif; ?>
                                <?php if ($rp['channel_name']): ?>
                                    · <?php echo sanitize($rp['channel_name']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label pt-0"><span class="label-text font-medium">ยอดเงินที่ต้องการคืน (฿) <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="refund_amount" id="cust-refund-amount-input"
                        class="input input-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors"
                        step="0.01" min="0.01" required placeholder="0.00">
                    <label class="label"><span class="label-text-alt text-base-content/50"
                            id="cust-refund-max-label">เลือกรายการชำระเงินก่อน</span></label>
                </div>

                <div class="form-control">
                    <label class="label pt-0"><span class="label-text font-medium">เหตุผลการขอคืนเงิน <span
                                class="text-error">*</span></span></label>
                    <textarea name="reason"
                        class="textarea textarea-bordered h-24 rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                        placeholder="กรุณาระบุเหตุผล..." required></textarea>
                </div>

                <div class="modal-action mt-6">
                    <button type="button" class="btn btn-ghost rounded-xl font-medium"
                        onclick="document.getElementById('modal-customer-refund').close()">ยกเลิก</button>
                    <button type="submit" class="btn btn-error rounded-xl font-medium gap-2 shadow-sm">
                        <i data-lucide="banknote" class="size-4"></i> ส่งคำร้อง
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        function updateCustRefundMax() {
            const select = document.getElementById('cust-refund-payment-select');
            const input = document.getElementById('cust-refund-amount-input');
            const label = document.getElementById('cust-refund-max-label');
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.max) {
                const max = parseFloat(opt.dataset.max);
                input.max = max;
                input.value = max.toFixed(2);
                label.textContent = 'คืนได้สูงสุด ฿' + max.toLocaleString('th-TH', { minimumFractionDigits: 2 });
            }
        }
    </script>
<?php endif; ?>

<!-- ═══════════ REVIEW MODAL ═══════════ -->
<?php if ($booking['status'] === 'checked_out' && !$existing_review): ?>
    <dialog id="modal-review" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box bg-base-100 rounded-t-3xl sm:rounded-3xl p-0 overflow-hidden shadow-2xl max-w-md">
            <div class="p-6 border-b border-base-200 flex items-center gap-3 bg-base-100/50">
                <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center text-warning shrink-0">
                    <i data-lucide="star" class="size-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-base-content leading-tight">เขียนรีวิว</h3>
                    <p class="text-sm text-base-content/60 mt-0.5">ให้คะแนนและแชร์ประสบการณ์ของคุณ</p>
                </div>
                <form method="dialog" class="ml-auto">
                    <button
                        class="btn btn-sm btn-circle btn-ghost text-base-content/50 hover:text-base-content hover:bg-base-200">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </form>
            </div>

            <form action="?action=review" method="POST" class="p-6 space-y-5">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

                <!-- Star Rating -->
                <div class="form-control">
                    <label class="label pt-0"><span class="label-text font-medium">ให้คะแนนประสบการณ์ <span
                                class="text-error">*</span></span></label>
                    <div class="flex items-center gap-1 py-2" id="star-rating-container">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="star-btn cursor-pointer transition-transform hover:scale-125"
                                data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">
                                <i data-lucide="star" class="size-8 text-base-content/20 transition-colors duration-200"
                                    id="star-<?php echo $i; ?>"></i>
                            </button>
                        <?php endfor; ?>
                        <span class="text-lg font-bold text-warning ml-3" id="rating-display">0</span>
                    </div>
                    <input type="hidden" name="rating" id="rating-input" value="0" required>
                </div>

                <!-- Comment -->
                <div class="form-control">
                    <label class="label pt-0"><span class="label-text font-medium">ข้อความรีวิว <span
                                class="text-error">*</span></span></label>
                    <textarea name="comment"
                        class="textarea textarea-bordered h-28 rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                        placeholder="เล่าประสบการณ์การเข้าพักของน้องๆ ที่ VET4 Hotel ให้เราฟังหน่อยนะคะ..."
                        required></textarea>
                </div>

                <div class="modal-action mt-6">
                    <button type="button" class="btn btn-ghost rounded-xl font-medium"
                        onclick="document.getElementById('modal-review').close()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-xl font-medium gap-2 shadow-sm"
                        id="submit-review-btn" disabled>
                        <i data-lucide="send" class="size-4"></i> ส่งรีวิว
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <script>
        function setRating(rating) {
            document.getElementById('rating-input').value = rating;
            document.getElementById('rating-display').textContent = rating + '.0';
            for (let i = 1; i <= 5; i++) {
                const star = document.getElementById('star-' + i);
                if (i <= rating) {
                    star.classList.remove('text-base-content/20');
                    star.classList.add('fill-warning', 'text-warning');
                } else {
                    star.classList.remove('fill-warning', 'text-warning');
                    star.classList.add('text-base-content/20');
                }
            }
            // Enable submit button when rating is set
            document.getElementById('submit-review-btn').disabled = false;
        }
    </script>
<?php endif; ?>

<!-- Fade-in animation -->
<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>