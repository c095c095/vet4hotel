<?php
// ═══════════════════════════════════════════════════════════
// PAYMENT PAGE — VET4 HOTEL
// หน้าชำระเงิน: แสดงสรุปการจอง + ช่องทางชำระ + อัปโหลดสลิป
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login&redirect=" . urlencode('?page=payment&booking_id=' . ($_GET['booking_id'] ?? '')));
    exit();
}

$customer_id = $_SESSION['customer_id'];
$booking_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($booking_id <= 0) {
    $_SESSION['msg_error'] = 'ไม่พบข้อมูลการจอง';
    header("Location: ?page=booking_history");
    echo "<script>window.location.href='?page=booking_history';</script>";
    exit();
}

// ═══════════════════════════════════════════════════════════
// DATA FETCHING
// ═══════════════════════════════════════════════════════════

$booking = null;
$items = [];
$item_pets_map = [];
$payment_channels = [];
$existing_payments = [];

try {
    // 1. Booking header
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            p.code AS promo_code,
            p.title AS promo_title
        FROM bookings b
        LEFT JOIN promotions p ON b.promotion_id = p.id
        WHERE b.id = ? AND b.customer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $_SESSION['msg_error'] = 'ไม่พบข้อมูลการจอง หรือคุณไม่มีสิทธิ์เข้าถึง';
        header("Location: ?page=booking_history");
        echo "<script>window.location.href='?page=booking_history';</script>";
        exit();
    }

    // Guard: must be pending_payment
    if ($booking['status'] !== 'pending_payment') {
        $_SESSION['msg_error'] = 'การจองนี้ไม่สามารถชำระเงินได้ในขณะนี้';
        echo "<script>window.location.href='?page=booking_detail&id={$booking_id}';</script>";
        header("Location: ?page=booking_detail&id=" . $booking_id);
        exit();
    }

    // 2. Booking items (rooms) + room type info
    $stmt = $pdo->prepare("
        SELECT 
            bi.*,
            r.room_number,
            rt.name AS room_type_name,
            rt.base_price_per_night,
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
                pet.name AS pet_name,
                sp.name AS species_name
            FROM booking_item_pets bip
            JOIN pets pet ON bip.pet_id = pet.id
            JOIN species sp ON pet.species_id = sp.id
            WHERE bip.booking_item_id IN ($ph)
        ");
        $stmt->execute($all_item_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $item_pets_map[$row['booking_item_id']][] = $row;
        }
    }

    // 4. Payment channels
    $stmt = $pdo->prepare("SELECT * FROM payment_channels WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $stmt->execute();
    $payment_channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Existing payments for this booking (to show remaining balance)
    $stmt = $pdo->prepare("
        SELECT * FROM payments 
        WHERE booking_id = ? AND status IN ('pending', 'verified')
        ORDER BY created_at ASC
    ");
    $stmt->execute([$booking_id]);
    $existing_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Booking services (per item)
    $item_services_map = [];
    $total_services_amount = 0;
    if (!empty($all_item_ids)) {
        $ph2 = implode(',', array_fill(0, count($all_item_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT 
                bs.*,
                s.name AS service_name,
                s.charge_type
            FROM booking_services bs
            JOIN services s ON bs.service_id = s.id
            WHERE bs.booking_item_id IN ($ph2)
            ORDER BY s.name ASC
        ");
        $stmt->execute($all_item_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $bsvc) {
            $item_services_map[$bsvc['booking_item_id']][] = $bsvc;
            $total_services_amount += (float) $bsvc['total_price'];
        }
    }

} catch (PDOException $e) {
    $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
    header("Location: ?page=booking_history");
    exit();
}

// ═══════════════════════════════════════════════════════════
// HELPER VALUES
// ═══════════════════════════════════════════════════════════

function thaiDateShort_pay($date)
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

function nightsCount_pay($cin, $cout)
{
    return max(1, (int) ((strtotime($cout) - strtotime($cin)) / 86400));
}

$net_amount = (float) $booking['net_amount'];
$paid_amount = 0;
foreach ($existing_payments as $ep) {
    $paid_amount += (float) $ep['amount'];
}
$remaining_amount = max(0, $net_amount - $paid_amount);

$earliest_cin = !empty($items) ? min(array_column($items, 'check_in_date')) : null;
$latest_cout = !empty($items) ? max(array_column($items, 'check_out_date')) : null;
$total_nights = 0;
foreach ($items as $item) {
    $total_nights += nightsCount_pay($item['check_in_date'], $item['check_out_date']);
}
?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- PAYMENT PAGE UI                                            -->
<!-- ═══════════════════════════════════════════════════════════ -->

<section class="py-6 md:py-10 bg-base-200/50 min-h-[85vh] relative overflow-hidden">
    <!-- Decorative blurs -->
    <div class="absolute top-0 right-0 -mt-24 -mr-24 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none">
    </div>
    <div
        class="absolute bottom-0 left-0 -mb-24 -ml-24 w-96 h-96 bg-secondary/5 rounded-full blur-3xl pointer-events-none">
    </div>
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        <div class="floating-paw absolute top-[8%] right-[6%] opacity-10 text-primary" style="animation-delay:0.5s;">
            <i data-lucide="credit-card" class="size-14"></i>
        </div>
        <div class="floating-paw absolute bottom-[6%] left-[4%] opacity-10 text-secondary" style="animation-delay:2s;">
            <i data-lucide="paw-print" class="size-16"></i>
        </div>
    </div>

    <div class="w-full max-w-6xl mx-auto px-4 relative z-10">

        <!-- ═══ BACK NAVIGATION ═══ -->
        <div class="mb-6">
            <a href="?page=booking_detail&id=<?php echo $booking_id; ?>"
                class="btn btn-ghost btn-sm gap-2 text-base-content/60 hover:text-primary transition-colors -ml-2">
                <i data-lucide="arrow-left" class="size-4"></i>
                กลับไปรายละเอียดการจอง
            </a>
        </div>

        <!-- ═══ HEADER ═══ -->
        <div class="text-center mb-8 md:mb-10 animate-fade-in-up">
            <div
                class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4 shadow-lg shadow-primary/10">
                <i data-lucide="wallet" class="size-8"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-black text-primary mb-2">ชำระเงิน</h1>
            <p class="text-base-content/60 text-sm">
                ตรวจสอบรายละเอียดการจอง เลือกช่องทาง และอัปโหลดหลักฐานการชำระเงิน
            </p>
        </div>

        <!-- ═══ MAIN GRID ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            <!-- ─── LEFT: Booking Summary (3 cols) ─── -->
            <div class="lg:col-span-3 space-y-5">

                <!-- Booking Ref Card -->
                <div class="card bg-base-100 shadow-lg border border-base-200 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                    style="animation-delay:0.05s;">
                    <div class="card-body p-0">
                        <!-- Status banner -->
                        <div class="bg-warning/10 border-b border-base-200 px-5 py-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="bg-primary text-primary-content p-3 rounded-2xl shadow-md">
                                        <i data-lucide="receipt" class="size-6"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h2 class="text-xl font-black text-base-content">
                                                <?php echo htmlspecialchars($booking['booking_ref']); ?>
                                            </h2>
                                            <span class="badge badge-warning gap-1.5 py-3 px-3">
                                                <i data-lucide="clock" class="size-3.5"></i>
                                                รอชำระเงิน
                                            </span>
                                        </div>
                                        <div class="text-xs text-base-content/50 mt-1 flex items-center gap-1.5">
                                            <i data-lucide="calendar-plus" class="size-3.5"></i>
                                            จองเมื่อ
                                            <?php echo thaiDateShort_pay($booking['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-base-content/50 font-medium uppercase tracking-wider">
                                        ยอดที่ต้องชำระ</div>
                                    <div class="text-3xl font-black text-primary mt-0.5">
                                        ฿
                                        <?php echo number_format($remaining_amount, 0); ?>
                                    </div>
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
                                    <?php echo thaiDateShort_pay($earliest_cin); ?>
                                </div>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <div class="text-xs text-base-content/50 font-medium">เช็คเอาท์</div>
                                <div class="text-sm font-bold text-base-content mt-0.5">
                                    <?php echo thaiDateShort_pay($latest_cout); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Room Items -->
                <?php foreach ($items as $idx => $item):
                    $nights = nightsCount_pay($item['check_in_date'], $item['check_out_date']);
                    $itemPets = $item_pets_map[$item['id']] ?? [];
                    ?>
                    <div class="card bg-base-100 shadow-md border border-base-200 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                        style="animation-delay: <?php echo 0.1 + $idx * 0.08; ?>s;">
                        <div class="card-body p-4 sm:p-5">
                            <div class="flex items-start gap-4">
                                <!-- Room image / icon -->
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

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 class="font-bold text-base text-base-content leading-tight">
                                                <?php echo htmlspecialchars($item['room_type_name']); ?>
                                            </h3>
                                            <div class="text-xs text-base-content/50 mt-0.5 flex items-center gap-1.5">
                                                <i data-lucide="door-open" class="size-3"></i>
                                                ห้อง
                                                <?php echo htmlspecialchars($item['room_number']); ?>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-lg font-bold text-primary">
                                                ฿
                                                <?php echo number_format($item['subtotal']); ?>
                                            </div>
                                            <div class="text-[10px] text-base-content/40">
                                                <?php echo $nights; ?> คืน
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dates row -->
                                    <div class="flex items-center gap-2 mt-2 text-xs text-base-content/60">
                                        <i data-lucide="calendar" class="size-3 text-primary/60"></i>
                                        <?php echo thaiDateShort_pay($item['check_in_date']); ?>
                                        <i data-lucide="arrow-right" class="size-3 text-base-content/30"></i>
                                        <?php echo thaiDateShort_pay($item['check_out_date']); ?>
                                        <span class="badge badge-ghost badge-xs">
                                            <?php echo $nights; ?> คืน
                                        </span>
                                    </div>

                                    <!-- Pets -->
                                    <?php if (!empty($itemPets)): ?>
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            <?php foreach ($itemPets as $pet): ?>
                                                <span
                                                    class="badge badge-outline badge-sm gap-1 border-primary/25 text-primary py-2.5">
                                                    <i data-lucide="paw-print" class="size-2.5"></i>
                                                    <?php echo htmlspecialchars($pet['pet_name']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Services -->
                                    <?php
                                    $itemSvcs = $item_services_map[$item['id']] ?? [];
                                    if (!empty($itemSvcs)): ?>
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            <?php foreach ($itemSvcs as $bsvc): ?>
                                                <span
                                                    class="badge badge-accent badge-outline badge-sm gap-1 py-2.5 border-accent/25">
                                                    <i data-lucide="sparkles" class="size-2.5"></i>
                                                    <?php echo htmlspecialchars($bsvc['service_name']); ?>
                                                    <span
                                                        class="text-[10px] text-base-content/50">฿<?php echo number_format($bsvc['total_price']); ?></span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Price Summary Card -->
                <div class="card bg-base-100 shadow-lg border border-primary/20 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                    style="animation-delay: <?php echo 0.2 + count($items) * 0.08; ?>s;">
                    <div class="card-body p-0">
                        <div class="bg-linear-to-br from-primary/5 to-primary/10 p-5">
                            <h3
                                class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <i data-lucide="calculator" class="size-4"></i>
                                สรุปยอดชำระ
                            </h3>
                            <div class="space-y-2.5 text-sm">
                                <!-- Room subtotal breakdown -->
                                <?php
                                $rooms_only_total = (float) $booking['subtotal_amount'] - $total_services_amount;
                                ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-base-content/60 flex items-center gap-1.5">
                                        <i data-lucide="bed-double" class="size-3.5"></i>
                                        ค่าห้องพัก
                                    </span>
                                    <span class="font-medium">฿<?php echo number_format($rooms_only_total); ?></span>
                                </div>

                                <?php if ($total_services_amount > 0): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-base-content/60 flex items-center gap-1.5">
                                            <i data-lucide="sparkles" class="size-3.5"></i>
                                            ค่าบริการเสริม
                                        </span>
                                        <span
                                            class="font-medium">+฿<?php echo number_format($total_services_amount); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between items-center pt-2 border-t border-base-200">
                                    <span class="text-base-content/60">ยอดรวมก่อนส่วนลด</span>
                                    <span
                                        class="font-medium">฿<?php echo number_format($booking['subtotal_amount']); ?></span>
                                </div>
                                <?php if ((float) $booking['discount_amount'] > 0): ?>
                                    <div class="flex justify-between items-center text-success">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="ticket" class="size-3.5"></i>
                                            ส่วนลด
                                            <?php if ($booking['promo_code']): ?>
                                                <span class="badge badge-success badge-sm badge-outline">
                                                    <?php echo htmlspecialchars($booking['promo_code']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="font-bold">-฿
                                            <?php echo number_format($booking['discount_amount']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between items-center pt-2.5 border-t-2 border-primary/20">
                                    <span class="font-bold text-lg text-base-content">ยอดสุทธิ</span>
                                    <span class="font-black text-2xl text-primary">฿
                                        <?php echo number_format($net_amount); ?>
                                    </span>
                                </div>
                                <?php if ($paid_amount > 0): ?>
                                    <div class="flex justify-between items-center text-info text-xs pt-1">
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="check-circle" class="size-3"></i>
                                            ชำระแล้ว
                                        </span>
                                        <span class="font-medium">฿
                                            <?php echo number_format($paid_amount); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-warning font-bold pt-1">
                                        <span>คงเหลือ</span>
                                        <span>฿
                                            <?php echo number_format($remaining_amount); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── RIGHT: Payment Panel (2 cols) ─── -->
            <div class="lg:col-span-2">
                <div class="sticky top-20 space-y-5">

                    <!-- Payment Channels -->
                    <div class="card bg-base-100 shadow-lg border border-base-200 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                        style="animation-delay:0.15s;">
                        <div class="card-body p-5">
                            <h3 class="font-bold text-base flex items-center gap-2 mb-4">
                                <div class="bg-accent/10 text-accent p-1.5 rounded-lg">
                                    <i data-lucide="landmark" class="size-5"></i>
                                </div>
                                ช่องทางชำระเงิน
                            </h3>

                            <?php if (!empty($payment_channels)): ?>
                                <div class="space-y-3" id="payment-channels">
                                    <?php foreach ($payment_channels as $cidx => $channel):
                                        $ch_icon = $channel['icon_class'] ?: match ($channel['type']) {
                                            'qr_promptpay' => 'qr-code',
                                            'bank_transfer' => 'building-2',
                                            'credit_card' => 'credit-card',
                                            'cash' => 'banknote',
                                            default => 'wallet',
                                        };
                                        ?>
                                        <label
                                            class="payment-channel-option flex items-start gap-3 p-4 rounded-xl border-2 border-base-200 cursor-pointer transition-all duration-200 hover:border-primary/40 hover:bg-primary/5 has-checked:border-primary has-checked:bg-primary/5 has-checked:shadow-md">
                                            <input type="radio" name="selected_channel_preview"
                                                value="<?php echo $channel['id']; ?>"
                                                class="radio radio-primary radio-sm mt-0.5 shrink-0" <?php echo $cidx === 0 ? 'checked' : ''; ?>
                                                onchange="document.getElementById('payment_channel_id').value = this.value;">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-bold text-sm text-base-content">
                                                    <?php echo htmlspecialchars($channel['name']); ?>
                                                </div>
                                                <?php if ($channel['bank_name']): ?>
                                                    <div class="text-xs text-base-content/50 mt-0.5">
                                                        <?php echo htmlspecialchars($channel['bank_name']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($channel['account_name'] || $channel['account_number']): ?>
                                                    <div class="mt-1.5 bg-base-200/60 rounded-lg px-3 py-2">
                                                        <?php if ($channel['account_name']): ?>
                                                            <div class="text-xs text-base-content/60 font-medium">
                                                                <?php echo htmlspecialchars($channel['account_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($channel['account_number']): ?>
                                                            <div class="text-sm font-bold text-base-content tracking-wide mt-0.5">
                                                                <?php echo htmlspecialchars($channel['account_number']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ((float) $channel['fee_percent'] > 0): ?>
                                                    <div class="text-[10px] text-warning mt-1 flex items-center gap-1">
                                                        <i data-lucide="info" class="size-2.5"></i>
                                                        ค่าธรรมเนียม <?php echo number_format($channel['fee_percent'], 2); ?>%
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="bg-primary/10 p-2 rounded-lg shrink-0">
                                                <i data-lucide="<?php echo htmlspecialchars($ch_icon); ?>"
                                                    class="size-5 text-primary"></i>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div
                                    class="text-center py-8 rounded-xl bg-base-200/40 border border-dashed border-base-300">
                                    <i data-lucide="alert-circle" class="size-8 text-warning mx-auto mb-2"></i>
                                    <p class="text-sm text-base-content/50">ยังไม่มีช่องทางชำระเงิน กรุณาติดต่อพนักงาน</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upload Slip Form -->
                    <div class="card bg-base-100 shadow-xl border border-primary/20 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                        style="animation-delay:0.25s;">
                        <div class="card-body p-5">
                            <h3 class="font-bold text-base flex items-center gap-2 mb-4">
                                <div class="bg-primary/10 text-primary p-1.5 rounded-lg">
                                    <i data-lucide="upload" class="size-5"></i>
                                </div>
                                อัปโหลดหลักฐานการชำระ
                            </h3>

                            <form action="?action=payment" method="POST" enctype="multipart/form-data"
                                id="payment-form">
                                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                <input type="hidden" name="payment_channel_id" id="payment_channel_id"
                                    value="<?php echo !empty($payment_channels) ? $payment_channels[0]['id'] : ''; ?>">

                                <!-- Amount -->
                                <div class="form-control mb-4">
                                    <label class="label pb-1">
                                        <span class="label-text text-sm font-medium flex items-center gap-1.5">
                                            <i data-lucide="banknote" class="size-4 text-base-content/50"></i>
                                            จำนวนเงินที่โอน (บาท)
                                        </span>
                                    </label>
                                    <input type="number" name="amount" step="0.01" min="1"
                                        value="<?php echo number_format($remaining_amount, 2, '.', ''); ?>"
                                        class="input input-bordered input-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                                        placeholder="เช่น 2500.00" required>
                                </div>

                                <!-- Transaction Ref -->
                                <div class="form-control mb-4">
                                    <label class="label pb-1">
                                        <span class="label-text text-sm font-medium flex items-center gap-1.5">
                                            <i data-lucide="hash" class="size-4 text-base-content/50"></i>
                                            เลขที่อ้างอิง / เลขที่รายการ
                                            <span class="text-base-content/30">(ไม่จำเป็น)</span>
                                        </span>
                                    </label>
                                    <input type="text" name="transaction_ref"
                                        class="input input-bordered input-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                                        placeholder="เช่น 20260224153000">
                                </div>

                                <!-- Slip Upload -->
                                <div class="form-control mb-5">
                                    <label class="label pb-1">
                                        <span class="label-text text-sm font-medium flex items-center gap-1.5">
                                            <i data-lucide="image" class="size-4 text-base-content/50"></i>
                                            หลักฐานการโอนเงิน (สลิป)
                                        </span>
                                    </label>
                                    <!-- Drop zone -->
                                    <div id="drop-zone"
                                        class="relative border-2 border-dashed border-base-300 rounded-2xl p-6 text-center cursor-pointer transition-all duration-300 hover:border-primary/50 hover:bg-primary/5 group">
                                        <input type="file" name="proof_image" accept="image/*" id="slip-input"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                            required>
                                        <div id="upload-placeholder">
                                            <div
                                                class="bg-base-200 p-3 rounded-full w-fit mx-auto mb-3 group-hover:bg-primary/10 transition-colors">
                                                <i data-lucide="cloud-upload"
                                                    class="size-7 text-base-content/30 group-hover:text-primary transition-colors"></i>
                                            </div>
                                            <p class="text-sm font-medium text-base-content/60">กดเลือก หรือ
                                                ลากไฟล์มาวาง</p>
                                            <p class="text-xs text-base-content/30 mt-1">รองรับ JPG, PNG, WEBP (สูงสุด
                                                32MB)</p>
                                        </div>
                                        <div id="upload-preview" class="hidden">
                                            <img id="preview-image" src="" alt="Preview"
                                                class="max-h-48 mx-auto rounded-xl shadow-md border border-base-200">
                                            <p id="preview-filename" class="text-xs text-base-content/50 mt-2"></p>
                                            <button type="button" id="remove-preview"
                                                class="btn btn-ghost btn-xs mt-2 text-error gap-1">
                                                <i data-lucide="x" class="size-3"></i>
                                                เปลี่ยนไฟล์
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <button type="submit" id="submit-btn"
                                    class="btn btn-primary btn-lg w-full gap-2 font-bold text-base shadow-lg shadow-primary/20 hover:shadow-primary/30 transition-all duration-300 <?php echo empty($payment_channels) ? 'btn-disabled' : ''; ?>">
                                    <i data-lucide="send" class="size-5"></i>
                                    ส่งหลักฐานการชำระเงิน
                                </button>
                            </form>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-start gap-2 text-xs text-base-content/40">
                                    <i data-lucide="shield-check" class="size-3.5 text-success shrink-0 mt-0.5"></i>
                                    <span>ข้อมูลของคุณจะถูกเก็บเป็นความลับ ใช้เพื่อยืนยันการชำระเงินเท่านั้น</span>
                                </div>
                                <div class="flex items-start gap-2 text-xs text-base-content/40">
                                    <i data-lucide="clock" class="size-3.5 text-info shrink-0 mt-0.5"></i>
                                    <span>หลังจากส่งหลักฐาน พนักงานจะตรวจสอบและยืนยันภายใน 24 ชั่วโมง</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- ANIMATIONS & SCRIPTS                                       -->
<!-- ═══════════════════════════════════════════════════════════ -->

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

    .payment-channel-option {
        transition: all 0.2s ease;
    }

    #drop-zone.drag-over {
        border-color: oklch(0.6 0.25 280);
        background: oklch(0.6 0.25 280 / 0.08);
        transform: scale(1.01);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // ─── File preview ───
        const slipInput = document.getElementById('slip-input');
        const dropZone = document.getElementById('drop-zone');
        const placeholder = document.getElementById('upload-placeholder');
        const preview = document.getElementById('upload-preview');
        const previewImage = document.getElementById('preview-image');
        const previewFilename = document.getElementById('preview-filename');
        const removeBtn = document.getElementById('remove-preview');

        function showPreview(file) {
            if (!file || !file.type.startsWith('image/')) return;

            // Validate file size (32MB)
            if (file.size > 32 * 1024 * 1024) {
                alert('ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 32MB)');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewFilename.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                placeholder.classList.add('hidden');
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        if (slipInput) {
            slipInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    showPreview(this.files[0]);
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                slipInput.value = '';
                placeholder.classList.remove('hidden');
                preview.classList.add('hidden');
                previewImage.src = '';
            });
        }

        // Drag & drop
        if (dropZone) {
            ['dragenter', 'dragover'].forEach(evt => {
                dropZone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropZone.classList.add('drag-over');
                });
            });

            ['dragleave', 'drop'].forEach(evt => {
                dropZone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropZone.classList.remove('drag-over');
                });
            });

            dropZone.addEventListener('drop', function (e) {
                const files = e.dataTransfer.files;
                if (files && files[0]) {
                    slipInput.files = files;
                    showPreview(files[0]);
                }
            });
        }

        // ─── Form submit guard ───
        const form = document.getElementById('payment-form');
        const submitBtn = document.getElementById('submit-btn');
        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังส่ง...';
            });
        }
    });
</script>