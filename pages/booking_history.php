<?php
// ═══════════════════════════════════════════════════════════
// BOOKING HISTORY PAGE — VET4 HOTEL
// ประวัติการจองทั้งหมดของลูกค้า
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    $current_url = '?' . http_build_query($_GET);
    header("Location: ?page=login&redirect=" . urlencode($current_url));
    exit();
}

$customer_id = $_SESSION['customer_id'];

// ═══════════════════════════════════════════════════════════
// DATA FETCHING (PDO Prepared Statements)
// ═══════════════════════════════════════════════════════════

$bookings = [];
$booking_items_map = [];   // booking_id => [items]
$item_pets_map = [];       // booking_item_id => [pets]
$booking_services_map = []; // booking_id => [services]
$booking_payments_map = []; // booking_id => [payments]
$booking_transport_map = []; // booking_id => [transport]

try {
    // 1. All bookings for this customer (with promotion info)
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            p.code AS promo_code,
            p.title AS promo_title,
            p.discount_type AS promo_discount_type,
            p.discount_value AS promo_discount_value
        FROM bookings b
        LEFT JOIN promotions p ON b.promotion_id = p.id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($bookings)) {
        $booking_ids = array_column($bookings, 'id');
        $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));

        // 2. Booking items (rooms) with room type & room info
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
            WHERE bi.booking_id IN ($placeholders)
            ORDER BY bi.check_in_date ASC
        ");
        $stmt->execute($booking_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $booking_items_map[$item['booking_id']][] = $item;
        }

        // 3. Pets per booking item
        $all_item_ids = [];
        foreach ($booking_items_map as $items) {
            foreach ($items as $item) {
                $all_item_ids[] = $item['id'];
            }
        }

        if (!empty($all_item_ids)) {
            $item_ph = implode(',', array_fill(0, count($all_item_ids), '?'));
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
                WHERE bip.booking_item_id IN ($item_ph)
            ");
            $stmt->execute($all_item_ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $item_pets_map[$row['booking_item_id']][] = $row;
            }
        }

        // 4. Booking services (add-ons)
        $stmt = $pdo->prepare("
            SELECT 
                bs.*,
                s.name AS service_name,
                s.charge_type,
                pet.name AS pet_name
            FROM booking_services bs
            JOIN services s ON bs.service_id = s.id
            LEFT JOIN pets pet ON bs.pet_id = pet.id
            WHERE bs.booking_id IN ($placeholders)
            ORDER BY bs.id ASC
        ");
        $stmt->execute($booking_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $svc) {
            $booking_services_map[$svc['booking_id']][] = $svc;
        }

        // 5. Payment history
        $stmt = $pdo->prepare("
            SELECT 
                pay.*,
                pc.provider_name AS channel_name
            FROM payments pay
            LEFT JOIN payment_channels pc ON pay.payment_channel_id = pc.id
            WHERE pay.booking_id IN ($placeholders)
            ORDER BY pay.created_at ASC
        ");
        $stmt->execute($booking_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pay) {
            $booking_payments_map[$pay['booking_id']][] = $pay;
        }

        // 6. Pet transportation
        $stmt = $pdo->prepare("
            SELECT *
            FROM pet_transportation
            WHERE booking_id IN ($placeholders)
            ORDER BY scheduled_datetime ASC
        ");
        $stmt->execute($booking_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tr) {
            $booking_transport_map[$tr['booking_id']][] = $tr;
        }
    }
} catch (PDOException $e) {
    $bookings = [];
}

// ═══════════════════════════════════════════════════════════
// HELPER VALUES
// ═══════════════════════════════════════════════════════════

$status_config = [
    'pending_payment' => ['label' => 'รอชำระเงิน', 'badge' => 'badge-warning', 'icon' => 'clock', 'color' => 'text-warning'],
    'confirmed'       => ['label' => 'ยืนยันแล้ว', 'badge' => 'badge-info', 'icon' => 'check-circle', 'color' => 'text-info'],
    'checked_in'      => ['label' => 'เข้าพักอยู่', 'badge' => 'badge-success', 'icon' => 'home', 'color' => 'text-success'],
    'checked_out'     => ['label' => 'เช็คเอาท์แล้ว', 'badge' => 'badge-neutral', 'icon' => 'log-out', 'color' => 'text-base-content/60'],
    'cancelled'       => ['label' => 'ยกเลิก', 'badge' => 'badge-error', 'icon' => 'x-circle', 'color' => 'text-error'],
];

$payment_status_config = [
    'pending'  => ['label' => 'รอตรวจสอบ', 'badge' => 'badge-warning'],
    'verified' => ['label' => 'ชำระแล้ว', 'badge' => 'badge-success'],
    'rejected' => ['label' => 'ถูกปฏิเสธ', 'badge' => 'badge-error'],
    'refunded' => ['label' => 'คืนเงินแล้ว', 'badge' => 'badge-info'],
];

$transport_type_labels = [
    'pickup'    => 'รับสัตว์เลี้ยง',
    'dropoff'   => 'ส่งสัตว์เลี้ยง',
    'roundtrip' => 'รับ-ส่ง',
];

$transport_status_labels = [
    'pending'    => ['label' => 'รอดำเนินการ', 'badge' => 'badge-warning'],
    'assigned'   => ['label' => 'มอบหมายแล้ว', 'badge' => 'badge-info'],
    'in_transit' => ['label' => 'กำลังเดินทาง', 'badge' => 'badge-accent'],
    'completed'  => ['label' => 'เสร็จสิ้น', 'badge' => 'badge-success'],
    'cancelled'  => ['label' => 'ยกเลิก', 'badge' => 'badge-error'],
];

// Stats
$total_bookings = count($bookings);

// Thai date helper
function thaiDateShort($date)
{
    if (!$date) return '-';
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $ts = strtotime($date);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = (int) date('Y', $ts) + 543;
    return "$d {$months[$m]} $y";
}

function thaiDateTime($datetime)
{
    if (!$datetime) return '-';
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $ts = strtotime($datetime);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = (int) date('Y', $ts) + 543;
    $time = date('H:i', $ts);
    return "$d {$months[$m]} $y เวลา $time น.";
}

function nightsCount($cin, $cout)
{
    return max(1, (int) ((strtotime($cout) - strtotime($cin)) / 86400));
}
?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- BOOKING HISTORY UI                                         -->
<!-- ═══════════════════════════════════════════════════════════ -->

<section class="py-6 md:py-12 bg-base-200/50 min-h-[85vh] relative overflow-hidden">
    <!-- Floating decorations -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        <div class="floating-paw absolute top-[6%] left-[5%] opacity-15 text-primary" style="animation-delay:0.3s;">
            <i data-lucide="calendar-check" class="size-14"></i>
        </div>
        <div class="floating-paw absolute bottom-[4%] right-[8%] opacity-10 text-secondary" style="animation-delay:1.5s;">
            <i data-lucide="paw-print" class="size-18"></i>
        </div>
        <div class="floating-paw absolute top-[20%] right-[4%] opacity-8 text-accent" style="animation-delay:2.2s;">
            <i data-lucide="receipt" class="size-14"></i>
        </div>
    </div>

    <div class="w-full max-w-6xl mx-auto px-4 relative z-10">

        <!-- ═══ PAGE HEADER ═══ -->
        <div class="text-center mb-8 md:mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4">
                <i data-lucide="history" class="size-8"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-base-content mb-2">ประวัติการจอง</h1>
            <p class="text-base-content/60 max-w-md mx-auto">ดูรายละเอียดการจองทั้งหมดของคุณ พร้อมสถานะและข้อมูลการชำระเงิน</p>
        </div>

        <?php if (empty($bookings)): ?>
            <!-- ═══ EMPTY STATE ═══ -->
            <div class="card bg-base-100 max-w-lg mx-auto border border-base-200">
                <div class="card-body items-center text-center py-16 px-8">
                    <div class="relative mb-6">
                        <div class="bg-base-200 p-6 rounded-full">
                            <i data-lucide="calendar-x" class="size-16 text-base-content/20"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 bg-primary/10 text-primary p-2 rounded-full">
                            <i data-lucide="paw-print" class="size-6"></i>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-base-content mb-2">ยังไม่มีประวัติการจอง</h2>
                    <p class="text-base-content/60 mb-8 max-w-sm">
                        เริ่มจองห้องพักให้สัตว์เลี้ยงของคุณได้เลย!<br>
                        เราพร้อมดูแลน้องๆ อย่างดีที่สุด 🐾
                    </p>
                    <a href="?page=booking" class="btn btn-primary btn-lg px-8 gap-2">
                        <i data-lucide="calendar-plus" class="size-5"></i>
                        จองห้องพักเลย
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- ═══ STATUS FILTER TABS ═══ -->
            <div class="flex flex-wrap gap-2 mb-6 justify-center" id="status-filters">
                <button class="btn btn-sm btn-primary gap-1.5 filter-btn active" data-filter="all">
                    <i data-lucide="layers" class="size-3.5"></i> ทั้งหมด
                    <span class="badge badge-sm bg-primary-content/20 text-primary-content"><?php echo $total_bookings; ?></span>
                </button>
                <?php
                $status_counts = array_count_values(array_column($bookings, 'status'));
                foreach ($status_config as $sKey => $sCfg):
                    $cnt = $status_counts[$sKey] ?? 0;
                    if ($cnt === 0) continue;
                ?>
                    <button class="btn btn-sm btn-ghost gap-1.5 filter-btn" data-filter="<?php echo $sKey; ?>">
                        <i data-lucide="<?php echo $sCfg['icon']; ?>" class="size-3.5"></i>
                        <?php echo $sCfg['label']; ?>
                        <span class="badge badge-sm badge-ghost"><?php echo $cnt; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- ═══ BOOKING CARDS ═══ -->
            <div class="space-y-4" id="booking-list">
                <?php foreach ($bookings as $bIdx => $booking):
                    $bId = $booking['id'];
                    $items = $booking_items_map[$bId] ?? [];
                    $services = $booking_services_map[$bId] ?? [];
                    $payments = $booking_payments_map[$bId] ?? [];
                    $transports = $booking_transport_map[$bId] ?? [];
                    $sCfg = $status_config[$booking['status']] ?? $status_config['pending_payment'];

                    // Aggregate pets across all items
                    $all_pets = [];
                    foreach ($items as $item) {
                        $pets = $item_pets_map[$item['id']] ?? [];
                        foreach ($pets as $pet) {
                            $all_pets[$pet['pet_id']] = $pet;
                        }
                    }

                    // First item dates for summary
                    $first_cin = $items[0]['check_in_date'] ?? null;
                    $first_cout = $items[0]['check_out_date'] ?? null;
                    $total_nights = $first_cin && $first_cout ? nightsCount($first_cin, $first_cout) : 0;
                ?>

                    <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden booking-card group"
                        data-status="<?php echo $booking['status']; ?>">
                        <div class="card-body p-0">

                            <!-- Card Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-linear-to-r from-primary/5 to-transparent p-4 md:p-5 border-b border-base-200 gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="bg-primary text-primary-content p-2.5 rounded-xl shadow-sm">
                                        <i data-lucide="<?php echo $sCfg['icon']; ?>" class="size-5"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-bold text-lg text-base-content">
                                                <?php echo htmlspecialchars($booking['booking_ref']); ?>
                                            </h3>
                                            <span class="badge <?php echo $sCfg['badge']; ?> badge-sm gap-1">
                                                <i data-lucide="<?php echo $sCfg['icon']; ?>" class="size-3"></i>
                                                <?php echo $sCfg['label']; ?>
                                            </span>
                                        </div>
                                        <div class="text-xs text-base-content/50 mt-0.5">
                                            จองเมื่อ <?php echo thaiDateTime($booking['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xl md:text-2xl font-black text-primary">
                                        ฿<?php echo number_format($booking['net_amount'], 0); ?>
                                    </div>
                                    <?php if ((float) $booking['discount_amount'] > 0): ?>
                                        <div class="text-xs text-success flex items-center gap-1 justify-end">
                                            <i data-lucide="tag" class="size-3"></i>
                                            ประหยัด ฿<?php echo number_format($booking['discount_amount']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 md:p-5 space-y-4">
                                <!-- Dates & Rooms Summary -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <?php if ($first_cin): ?>
                                        <div class="flex items-center gap-2.5 text-sm">
                                            <div class="bg-base-200 p-2 rounded-lg">
                                                <i data-lucide="calendar" class="size-4 text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs text-base-content/50 font-medium">วันที่เข้าพัก</div>
                                                <div class="font-semibold text-base-content">
                                                    <?php echo thaiDateShort($first_cin); ?> — <?php echo thaiDateShort($first_cout); ?>
                                                    <span class="badge badge-ghost badge-xs ml-1"><?php echo $total_nights; ?> คืน</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-2.5 text-sm">
                                        <div class="bg-base-200 p-2 rounded-lg">
                                            <i data-lucide="bed-double" class="size-4 text-secondary"></i>
                                        </div>
                                        <div>
                                            <div class="text-xs text-base-content/50 font-medium">ห้องพัก</div>
                                            <div class="font-semibold text-base-content">
                                                <?php
                                                $room_names = array_unique(array_column($items, 'room_type_name'));
                                                echo count($items) . ' ห้อง';
                                                if (!empty($room_names)) {
                                                    echo ' <span class="font-normal text-base-content/60">(' . htmlspecialchars(implode(', ', $room_names)) . ')</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pets -->
                                <?php if (!empty($all_pets)): ?>
                                    <div>
                                        <div class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">
                                            สัตว์เลี้ยงที่เข้าพัก (<?php echo count($all_pets); ?> ตัว)
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($all_pets as $pet): ?>
                                                <div class="badge badge-outline gap-1.5 py-3 px-3 border-primary/30 text-primary">
                                                    <?php if ($pet['species_id'] == 1): ?>
                                                        <i data-lucide="dog" class="size-3"></i>
                                                    <?php elseif ($pet['species_id'] == 2): ?>
                                                        <i data-lucide="cat" class="size-3"></i>
                                                    <?php else: ?>
                                                        <i data-lucide="paw-print" class="size-3"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($pet['pet_name']); ?>
                                                    <span class="text-[10px] text-base-content/40">(<?php echo htmlspecialchars($pet['species_name']); ?>)</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Services summary -->
                                <?php if (!empty($services)): ?>
                                    <div class="flex items-center gap-2 text-sm text-base-content/60">
                                        <i data-lucide="sparkles" class="size-4 text-accent"></i>
                                        <span>บริการเสริม: <?php echo count($services); ?> รายการ</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Price breakdown -->
                                <?php if ((float) $booking['discount_amount'] > 0): ?>
                                    <div class="flex items-center gap-3 text-sm bg-success/5 border border-success/20 rounded-xl px-4 py-2.5">
                                        <i data-lucide="ticket" class="size-4 text-success shrink-0"></i>
                                        <div class="flex-1">
                                            <span class="text-base-content/70">โค้ดส่วนลด</span>
                                            <?php if ($booking['promo_code']): ?>
                                                <span class="font-bold text-success ml-1"><?php echo htmlspecialchars($booking['promo_code']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="font-bold text-success">-฿<?php echo number_format($booking['discount_amount']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="flex items-center justify-between pt-2 border-t border-base-200">
                                    <!-- Payment status indicator -->
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($payments)):
                                            $last_pay = end($payments);
                                            $pCfg = $payment_status_config[$last_pay['status']] ?? $payment_status_config['pending'];
                                        ?>
                                            <span class="badge <?php echo $pCfg['badge']; ?> badge-sm gap-1">
                                                <i data-lucide="credit-card" class="size-3"></i>
                                                <?php echo $pCfg['label']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-ghost badge-sm gap-1">
                                                <i data-lucide="credit-card" class="size-3"></i>
                                                ยังไม่ชำระ
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($transports)): ?>
                                            <span class="badge badge-ghost badge-sm gap-1">
                                                <i data-lucide="truck" class="size-3"></i>
                                                Pet Taxi
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <button onclick="document.getElementById('detail_modal_<?php echo $bId; ?>').checked = true"
                                        class="btn btn-primary btn-sm gap-1.5 shadow-sm">
                                        <i data-lucide="eye" class="size-4"></i>
                                        ดูรายละเอียด
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════ -->
                    <!-- DETAIL MODAL — per booking                 -->
                    <!-- ═══════════════════════════════════════════ -->
                    <input type="checkbox" id="detail_modal_<?php echo $bId; ?>" class="modal-toggle" />
                    <div class="modal modal-bottom sm:modal-middle">
                        <div class="modal-box rounded-t-3xl rounded-b-none max-h-[90vh] sm:rounded-2xl p-0 sm:max-w-2xl flex flex-col">
                            <!-- Drag handle (mobile) -->
                            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                                <div class="w-12 h-1.5 bg-base-300 rounded-full"></div>
                            </div>

                            <!-- Modal Header -->
                            <div class="px-5 sm:px-6 py-4 border-b border-base-200 sticky top-0 bg-base-100 z-20 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="bg-primary/10 text-primary rounded-full p-2">
                                        <i data-lucide="receipt" class="size-5"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-base-content"><?php echo htmlspecialchars($booking['booking_ref']); ?></div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="badge <?php echo $sCfg['badge']; ?> badge-xs gap-1">
                                                <?php echo $sCfg['label']; ?>
                                            </span>
                                            <span class="text-xs text-base-content/40"><?php echo thaiDateShort($booking['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <label for="detail_modal_<?php echo $bId; ?>" class="btn btn-ghost btn-sm btn-circle">
                                    <i data-lucide="x" class="size-4"></i>
                                </label>
                            </div>

                            <!-- Modal Body (scrollable) -->
                            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-4 space-y-5">

                                <!-- § Room Items -->
                                <?php if (!empty($items)): ?>
                                    <div>
                                        <h4 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                                            <i data-lucide="bed-double" class="size-4"></i> ห้องพัก
                                        </h4>
                                        <div class="space-y-3">
                                            <?php foreach ($items as $item):
                                                $nights = nightsCount($item['check_in_date'], $item['check_out_date']);
                                                $itemPets = $item_pets_map[$item['id']] ?? [];
                                            ?>
                                                <div class="rounded-xl border border-base-200 bg-base-200/30 p-4">
                                                    <div class="flex items-start justify-between gap-3 mb-2">
                                                        <div>
                                                            <div class="font-bold text-base-content">
                                                                <?php echo htmlspecialchars($item['room_type_name']); ?>
                                                            </div>
                                                            <div class="text-xs text-base-content/50 mt-0.5">
                                                                ห้อง <?php echo htmlspecialchars($item['room_number']); ?>
                                                                · ชั้น <?php echo htmlspecialchars($item['floor_level']); ?>
                                                                <?php if ($item['size_sqm']): ?>
                                                                    · <?php echo number_format($item['size_sqm'], 0); ?> ตร.ม.
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="text-right shrink-0">
                                                            <div class="font-bold text-primary">฿<?php echo number_format($item['subtotal']); ?></div>
                                                            <div class="text-[10px] text-base-content/40">
                                                                ฿<?php echo number_format($item['locked_unit_price']); ?>/คืน × <?php echo $nights; ?> คืน
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs text-base-content/60 mb-2">
                                                        <i data-lucide="calendar" class="size-3"></i>
                                                        <?php echo thaiDateShort($item['check_in_date']); ?> — <?php echo thaiDateShort($item['check_out_date']); ?>
                                                    </div>
                                                    <?php if (!empty($itemPets)): ?>
                                                        <div class="flex flex-wrap gap-1.5">
                                                            <?php foreach ($itemPets as $pet): ?>
                                                                <span class="badge badge-outline badge-sm gap-1 border-primary/20 text-primary">
                                                                    <?php if ($pet['species_id'] == 1): ?>
                                                                        <i data-lucide="dog" class="size-2.5"></i>
                                                                    <?php elseif ($pet['species_id'] == 2): ?>
                                                                        <i data-lucide="cat" class="size-2.5"></i>
                                                                    <?php else: ?>
                                                                        <i data-lucide="paw-print" class="size-2.5"></i>
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($pet['pet_name']); ?>
                                                                    <span class="text-[9px] text-base-content/40"><?php echo htmlspecialchars($pet['breed_name'] ?? $pet['species_name']); ?></span>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- § Services -->
                                <?php if (!empty($services)): ?>
                                    <div>
                                        <h4 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                                            <i data-lucide="sparkles" class="size-4"></i> บริการเสริม
                                        </h4>
                                        <div class="space-y-2">
                                            <?php foreach ($services as $svc):
                                                $chargeLabel = '';
                                                if ($svc['charge_type'] === 'per_night') $chargeLabel = 'ต่อคืน';
                                                elseif ($svc['charge_type'] === 'per_pet') $chargeLabel = 'ต่อตัว';
                                                else $chargeLabel = 'ต่อการเข้าพัก';
                                            ?>
                                                <div class="flex justify-between items-center text-sm bg-base-200/40 rounded-lg px-3 py-2.5">
                                                    <div class="flex items-center gap-2 text-base-content/70">
                                                        <i data-lucide="plus-circle" class="size-3.5 text-accent"></i>
                                                        <span><?php echo htmlspecialchars($svc['service_name']); ?></span>
                                                        <?php if ($svc['pet_name']): ?>
                                                            <span class="text-xs text-base-content/40">(<?php echo htmlspecialchars($svc['pet_name']); ?>)</span>
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
                                <?php endif; ?>

                                <!-- § Transportation -->
                                <?php if (!empty($transports)): ?>
                                    <div>
                                        <h4 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                                            <i data-lucide="truck" class="size-4"></i> Pet Taxi
                                        </h4>
                                        <div class="space-y-2">
                                            <?php foreach ($transports as $tr):
                                                $trType = $transport_type_labels[$tr['transport_type']] ?? $tr['transport_type'];
                                                $trStatus = $transport_status_labels[$tr['status']] ?? ['label' => $tr['status'], 'badge' => 'badge-ghost'];
                                            ?>
                                                <div class="rounded-xl border border-base-200 bg-base-200/30 p-3">
                                                    <div class="flex items-center justify-between mb-1.5">
                                                        <span class="font-medium text-sm text-base-content"><?php echo $trType; ?></span>
                                                        <span class="badge <?php echo $trStatus['badge']; ?> badge-sm"><?php echo $trStatus['label']; ?></span>
                                                    </div>
                                                    <div class="text-xs text-base-content/60 space-y-0.5">
                                                        <div class="flex items-center gap-1.5">
                                                            <i data-lucide="map-pin" class="size-3"></i>
                                                            <?php echo htmlspecialchars($tr['address']); ?>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <i data-lucide="clock" class="size-3"></i>
                                                            <?php echo thaiDateTime($tr['scheduled_datetime']); ?>
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
                                                    <div class="text-right mt-1">
                                                        <span class="font-bold text-primary text-sm">฿<?php echo number_format($tr['price']); ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- § Payment History -->
                                <div>
                                    <h4 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <i data-lucide="credit-card" class="size-4"></i> ประวัติการชำระเงิน
                                    </h4>
                                    <?php if (!empty($payments)): ?>
                                        <div class="space-y-2">
                                            <?php foreach ($payments as $pay):
                                                $pCfg = $payment_status_config[$pay['status']] ?? $payment_status_config['pending'];
                                                $payTypeLabel = '';
                                                switch ($pay['payment_type']) {
                                                    case 'deposit':
                                                        $payTypeLabel = 'มัดจำ';
                                                        break;
                                                    case 'full_payment':
                                                        $payTypeLabel = 'ชำระเต็มจำนวน';
                                                        break;
                                                    case 'balance_due':
                                                        $payTypeLabel = 'ชำระส่วนที่เหลือ';
                                                        break;
                                                    case 'extra_charge':
                                                        $payTypeLabel = 'ค่าบริการเพิ่มเติม';
                                                        break;
                                                    default:
                                                        $payTypeLabel = $pay['payment_type'];
                                                }
                                            ?>
                                                <div class="flex items-center justify-between rounded-xl border border-base-200 bg-base-200/30 px-4 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="bg-base-300 p-1.5 rounded-lg">
                                                            <i data-lucide="banknote" class="size-4 text-base-content/60"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-medium text-sm text-base-content"><?php echo $payTypeLabel; ?></div>
                                                            <div class="text-[10px] text-base-content/40 mt-0.5">
                                                                <?php echo thaiDateTime($pay['paid_at'] ?? $pay['created_at']); ?>
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
                                                        <div class="font-bold text-sm text-base-content">฿<?php echo number_format($pay['amount']); ?></div>
                                                        <span class="badge <?php echo $pCfg['badge']; ?> badge-xs mt-0.5"><?php echo $pCfg['label']; ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4 rounded-xl bg-base-200/40 border border-dashed border-base-300">
                                            <i data-lucide="credit-card" class="size-6 text-base-content/20 mx-auto mb-1"></i>
                                            <p class="text-xs text-base-content/40">ยังไม่มีรายการชำระเงิน</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- § Special Requests -->
                                <?php if ($booking['special_requests']): ?>
                                    <div>
                                        <h4 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                                            <i data-lucide="message-square" class="size-4"></i> คำขอพิเศษ
                                        </h4>
                                        <div class="bg-base-200/40 rounded-xl px-4 py-3 text-sm text-base-content/70 italic">
                                            "<?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>"
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- § Price Summary -->
                                <div class="bg-linear-to-br from-primary/5 to-primary/10 rounded-2xl p-4 border border-primary/10">
                                    <h4 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <i data-lucide="calculator" class="size-4"></i> สรุปยอดชำระ
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-base-content/60">ยอดรวมก่อนส่วนลด</span>
                                            <span class="font-medium">฿<?php echo number_format($booking['subtotal_amount']); ?></span>
                                        </div>
                                        <?php if ((float) $booking['discount_amount'] > 0): ?>
                                            <div class="flex justify-between text-success">
                                                <span>
                                                    ส่วนลด
                                                    <?php if ($booking['promo_code']): ?>
                                                        (<?php echo htmlspecialchars($booking['promo_code']); ?>)
                                                    <?php endif; ?>
                                                </span>
                                                <span class="font-medium">-฿<?php echo number_format($booking['discount_amount']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="border-t border-primary/20 pt-2 flex justify-between">
                                            <span class="font-bold text-base text-base-content">ยอดสุทธิ</span>
                                            <span class="font-black text-xl text-primary">฿<?php echo number_format($booking['net_amount']); ?></span>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /modal body -->

                            <!-- Modal Footer -->
                            <div class="px-5 sm:px-6 py-4 border-t border-base-200 bg-base-100 flex justify-end gap-2">
                                <?php if ($booking['status'] === 'checked_in'): ?>
                                    <a href="?page=active_stay" class="btn btn-primary btn-sm gap-1.5">
                                        <i data-lucide="radio" class="size-4"></i>
                                        ติดตามสถานะ Live
                                    </a>
                                <?php endif; ?>
                                <label for="detail_modal_<?php echo $bId; ?>" class="btn btn-ghost btn-sm">ปิด</label>
                            </div>
                        </div>
                        <label class="modal-backdrop" for="detail_modal_<?php echo $bId; ?>"></label>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</section>

<!-- ═══ CLIENT-SIDE: Status Filter ═══ -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filters = document.querySelectorAll('.filter-btn');
        const cards = document.querySelectorAll('.booking-card');

        filters.forEach(btn => {
            btn.addEventListener('click', function () {
                const filter = this.dataset.filter;

                // Update active button
                filters.forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-ghost');
                });
                this.classList.remove('btn-ghost');
                this.classList.add('btn-primary', 'active');

                // Show/hide cards with animation
                cards.forEach(card => {
                    if (filter === 'all' || card.dataset.status === filter) {
                        card.style.display = '';
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(10px)';
                        requestAnimationFrame(() => {
                            card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        });
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Re-init Lucide icons for modal content
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
