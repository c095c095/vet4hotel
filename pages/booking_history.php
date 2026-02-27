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
                pc.name AS channel_name
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
    'verifying_payment' => ['label' => 'กำลังตรวจสอบ', 'badge' => 'badge-info', 'icon' => 'search', 'color' => 'text-info'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'badge' => 'badge-info', 'icon' => 'check-circle', 'color' => 'text-info'],
    'checked_in' => ['label' => 'เข้าพักอยู่', 'badge' => 'badge-success', 'icon' => 'home', 'color' => 'text-success'],
    'checked_out' => ['label' => 'เช็คเอาท์แล้ว', 'badge' => 'badge-neutral', 'icon' => 'log-out', 'color' => 'text-base-content/60'],
    'cancelled' => ['label' => 'ยกเลิก', 'badge' => 'badge-error', 'icon' => 'x-circle', 'color' => 'text-error'],
];

$payment_status_config = [
    'pending' => ['label' => 'รอตรวจสอบ', 'badge' => 'badge-warning'],
    'verified' => ['label' => 'ชำระแล้ว', 'badge' => 'badge-success'],
    'rejected' => ['label' => 'ถูกปฏิเสธ', 'badge' => 'badge-error'],
    'refunded' => ['label' => 'คืนเงินแล้ว', 'badge' => 'badge-info'],
];

// Stats
$total_bookings = count($bookings);

// Thai date helper
function thaiDateShort($date)
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

function thaiDateTime($datetime)
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
        <div class="floating-paw absolute bottom-[4%] right-[8%] opacity-10 text-secondary"
            style="animation-delay:1.5s;">
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
            <h1 class="text-2xl md:text-3xl font-bold mb-2 text-primary">ประวัติการจอง</h1>
            <p class="text-base-content/60 max-w-md mx-auto">ดูรายละเอียดการจองทั้งหมดของคุณ
                พร้อมสถานะและข้อมูลการชำระเงิน</p>
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
                    <span
                        class="badge badge-sm bg-primary-content/20 text-primary-content"><?php echo $total_bookings; ?></span>
                </button>
                <?php
                $status_counts = array_count_values(array_column($bookings, 'status'));
                foreach ($status_config as $sKey => $sCfg):
                    $cnt = $status_counts[$sKey] ?? 0;
                    if ($cnt === 0)
                        continue;
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

                    // Compute overall date range
                    $earliest_cin = null;
                    $latest_cout = null;
                    $total_nights = 0;
                    foreach ($items as $item) {
                        if ($earliest_cin === null || $item['check_in_date'] < $earliest_cin) {
                            $earliest_cin = $item['check_in_date'];
                        }
                        if ($latest_cout === null || $item['check_out_date'] > $latest_cout) {
                            $latest_cout = $item['check_out_date'];
                        }
                        $total_nights += nightsCount($item['check_in_date'], $item['check_out_date']);
                    }
                    ?>

                    <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden booking-card group"
                        data-status="<?php echo $booking['status']; ?>">
                        <div class="card-body p-0">

                            <!-- Card Header -->
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between bg-linear-to-r from-primary/5 to-transparent p-4 md:p-5 border-b border-base-200 gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="bg-primary text-primary-content p-2.5 rounded-xl shadow-sm">
                                        <i data-lucide="<?php echo $sCfg['icon']; ?>" class="size-5"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-bold text-lg text-base-content">
                                                <?php echo sanitize($booking['booking_ref']); ?>
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
                                    <?php if ($earliest_cin): ?>
                                        <div class="flex items-center gap-2.5 text-sm">
                                            <div class="bg-base-200 p-2 rounded-lg">
                                                <i data-lucide="calendar" class="size-4 text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs text-base-content/50 font-medium">ช่วงเข้าพัก</div>
                                                <div class="font-semibold text-base-content">
                                                    <?php echo thaiDateShort($earliest_cin); ?> —
                                                    <?php echo thaiDateShort($latest_cout); ?>
                                                    <span class="badge badge-ghost badge-xs ml-1"><?php echo $total_nights; ?>
                                                        คืน</span>
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
                                                    echo ' <span class="font-normal text-base-content/60">(' . sanitize(implode(', ', $room_names)) . ')</span>';
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
                                                    <?php echo sanitize($pet['pet_name']); ?>
                                                    <span
                                                        class="text-[10px] text-base-content/40">(<?php echo sanitize($pet['species_name']); ?>)</span>
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
                                    <div
                                        class="flex items-center gap-3 text-sm bg-success/5 border border-success/20 rounded-xl px-4 py-2.5">
                                        <i data-lucide="ticket" class="size-4 text-success shrink-0"></i>
                                        <div class="flex-1">
                                            <span class="text-base-content/70">โค้ดส่วนลด</span>
                                            <?php if ($booking['promo_code']): ?>
                                                <span
                                                    class="font-bold text-success ml-1"><?php echo sanitize($booking['promo_code']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span
                                            class="font-bold text-success">-฿<?php echo number_format($booking['discount_amount']); ?></span>
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

                                    <a href="?page=booking_detail&id=<?php echo $bId; ?>"
                                        class="btn btn-primary btn-sm gap-1.5 shadow-sm">
                                        <i data-lucide="eye" class="size-4"></i>
                                        ดูรายละเอียด
                                    </a>
                                </div>
                            </div>
                        </div>
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

        // Re-init Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>