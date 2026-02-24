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

    // 6. Transportation
    $stmt = $pdo->prepare("
        SELECT * FROM pet_transportation
        WHERE booking_id = ?
        ORDER BY scheduled_datetime ASC
    ");
    $stmt->execute([$booking_id]);
    $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$transport_type_labels = [
    'pickup' => 'รับสัตว์เลี้ยง',
    'dropoff' => 'ส่งสัตว์เลี้ยง',
    'roundtrip' => 'รับ-ส่ง',
];

$transport_status_labels = [
    'pending' => ['label' => 'รอดำเนินการ', 'badge' => 'badge-warning'],
    'assigned' => ['label' => 'มอบหมายแล้ว', 'badge' => 'badge-info'],
    'in_transit' => ['label' => 'กำลังเดินทาง', 'badge' => 'badge-accent'],
    'completed' => ['label' => 'เสร็จสิ้น', 'badge' => 'badge-success'],
    'cancelled' => ['label' => 'ยกเลิก', 'badge' => 'badge-error'],
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
        <div class="mb-6">
            <a href="?page=booking_history"
                class="btn btn-ghost btn-sm gap-2 text-base-content/60 hover:text-primary transition-colors -ml-2">
                <i data-lucide="arrow-left" class="size-4"></i>
                กลับไปประวัติการจอง
            </a>
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
                                        <?php echo htmlspecialchars($booking['booking_ref']); ?>
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
                                        <img src="<?php echo htmlspecialchars($item['room_image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['room_type_name']); ?>"
                                            class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                                        <i data-lucide="bed-double" class="size-7 text-primary/50"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-bold text-lg text-base-content leading-tight">
                                        <?php echo htmlspecialchars($item['room_type_name']); ?>
                                    </h3>
                                    <div class="text-xs text-base-content/50 mt-0.5 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="door-open" class="size-3"></i>
                                            ห้อง <?php echo htmlspecialchars($item['room_number']); ?>
                                        </span>
                                        <span>·</span>
                                        <span>ชั้น <?php echo htmlspecialchars($item['floor_level']); ?></span>
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
                                                        <?php echo htmlspecialchars($pet['pet_name']); ?>
                                                    </div>
                                                    <div class="text-[10px] text-base-content/40">
                                                        <?php echo htmlspecialchars($pet['breed_name'] ?? $pet['species_name']); ?>
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
                                                        <?php echo htmlspecialchars($svc['service_name']); ?>
                                                    </span>
                                                    <?php if ($svc['pet_name']): ?>
                                                        <span
                                                            class="text-xs text-base-content/40">(<?php echo htmlspecialchars($svc['pet_name']); ?>)</span>
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
                                    <span><?php echo htmlspecialchars($svc['service_name']); ?></span>
                                    <?php if ($svc['pet_name']): ?>
                                        <span
                                            class="text-xs text-base-content/40">(<?php echo htmlspecialchars($svc['pet_name']); ?>)</span>
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

        <!-- ═══ TRANSPORTATION ═══ -->
        <?php if (!empty($transports)): ?>
            <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
                <div class="card-body p-5">
                    <h3
                        class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i data-lucide="truck" class="size-4"></i> Pet Taxi
                    </h3>
                    <div class="space-y-2">
                        <?php foreach ($transports as $tr):
                            $trType = $transport_type_labels[$tr['transport_type']] ?? $tr['transport_type'];
                            $trStatus = $transport_status_labels[$tr['status']] ?? ['label' => $tr['status'], 'badge' => 'badge-ghost'];
                            ?>
                            <div class="rounded-xl border border-base-200 bg-base-200/30 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-sm text-base-content"><?php echo $trType; ?></span>
                                    <span
                                        class="badge <?php echo $trStatus['badge']; ?> badge-sm"><?php echo $trStatus['label']; ?></span>
                                </div>
                                <div class="text-xs text-base-content/60 space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="size-3"></i>
                                        <?php echo htmlspecialchars($tr['address']); ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="clock" class="size-3"></i>
                                        <?php echo thaiDateTime_d($tr['scheduled_datetime']); ?>
                                    </div>
                                    <?php if ($tr['distance_km']): ?>
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="navigation" class="size-3"></i>
                                            ระยะทาง <?php echo number_format($tr['distance_km'], 1); ?> กม.
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($tr['driver_name']): ?>
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="user" class="size-3"></i>
                                            <?php echo htmlspecialchars($tr['driver_name']); ?>
                                            <?php if ($tr['driver_phone']): ?>
                                                · <?php echo htmlspecialchars($tr['driver_phone']); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right mt-2">
                                    <span
                                        class="font-bold text-primary text-sm">฿<?php echo number_format($tr['price']); ?></span>
                                </div>
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
                                                · <?php echo htmlspecialchars($pay['channel_name']); ?>
                                            <?php endif; ?>
                                            <?php if ($pay['transaction_ref']): ?>
                                                · Ref: <?php echo htmlspecialchars($pay['transaction_ref']); ?>
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

        <!-- ═══ SPECIAL REQUESTS ═══ -->
        <?php if ($booking['special_requests']): ?>
            <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden mb-6">
                <div class="card-body p-5">
                    <h3
                        class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i data-lucide="message-square" class="size-4"></i> คำขอพิเศษ
                    </h3>
                    <div class="bg-base-200/40 rounded-xl px-4 py-3 text-sm text-base-content/70 italic">
                        "<?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>"
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
                                            class="badge badge-success badge-sm badge-outline"><?php echo htmlspecialchars($booking['promo_code']); ?></span>
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

        <!-- ═══ BOTTOM ACTIONS ═══ -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-6">
            <a href="?page=booking_history" class="btn btn-ghost gap-2 w-full sm:w-auto">
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
                <?php if ($booking['status'] === 'checked_in'): ?>
                    <a href="?page=active_stay" class="btn btn-primary gap-2 flex-1 sm:flex-none">
                        <i data-lucide="radio" class="size-4"></i>
                        ติดตามสถานะ Live
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

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