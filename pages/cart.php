<?php
// ═══════════════════════════════════════════════════════════
// CART PAGE — VET4 HOTEL
// หน้าตะกร้าสำหรับตรวจสอบรายการจองก่อนยืนยัน
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$cart = $_SESSION['booking_cart'] ?? [];
$promo = $_SESSION['booking_promo'] ?? null;

// ─── ดึงข้อมูลพื้นฐานจาก DB ───
$room_types_map = [];
$services_map = [];
$pets_map = [];

try {
    // Room Types
    $stmt = $pdo->prepare("SELECT * FROM room_types WHERE is_active = 1");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rt) {
        $room_types_map[$rt['id']] = $rt;
    }

    // Services
    $stmt = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sv) {
        $services_map[$sv['id']] = $sv;
    }

    // Pets ของลูกค้า
    $stmt = $pdo->prepare("SELECT p.*, s.name AS species_name FROM pets p LEFT JOIN species s ON s.id = p.species_id WHERE p.customer_id = ? AND p.deleted_at IS NULL");
    $stmt->execute([$customer_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pet) {
        $pets_map[$pet['id']] = $pet;
    }

    // Seasonal Pricings
    $stmt = $pdo->prepare("SELECT * FROM seasonal_pricings WHERE is_active = 1");
    $stmt->execute();
    $seasonals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $seasonals = [];
}

// ─── ฟังก์ชันคำนวณราคาห้องพร้อม Seasonal Pricing ───
function cart_room_price($room_type, $check_in, $check_out, $seasonals)
{
    $base = (float) $room_type['base_price_per_night'];
    $total = 0;
    $current = strtotime($check_in);
    $end = strtotime($check_out);
    $breakdown = [];

    while ($current < $end) {
        $d = date('Y-m-d', $current);
        $price = $base;
        $is_peak = false;

        foreach ($seasonals as $s) {
            if ($d >= $s['start_date'] && $d <= $s['end_date']) {
                $price = $base * (1 + (float) $s['price_multiplier_percent'] / 100);
                $is_peak = true;
                break;
            }
        }

        $total += $price;
        $breakdown[] = ['date' => $d, 'price' => $price, 'peak' => $is_peak];
        $current = strtotime('+1 day', $current);
    }

    return ['total' => round($total, 2), 'breakdown' => $breakdown, 'nights' => count($breakdown)];
}

// ─── คำนวณรายการในตะกร้า ───
$cart_items = [];
$grand_subtotal = 0;

foreach ($cart as $index => $item) {
    $rt = $room_types_map[$item['room_type_id']] ?? null;
    if (!$rt)
        continue;

    $room_calc = cart_room_price($rt, $item['check_in_date'], $item['check_out_date'], $seasonals);
    $nights = $room_calc['nights'];
    $room_total = $room_calc['total'];
    $has_peak = false;
    foreach ($room_calc['breakdown'] as $bd) {
        if ($bd['peak']) {
            $has_peak = true;
            break;
        }
    }

    // บริการเสริม
    $item_services = [];
    $services_total = 0;
    $pet_ids = (array) ($item['pet_ids'] ?? []);

    foreach ((array) ($item['service_ids'] ?? []) as $sid) {
        $sv = $services_map[$sid] ?? null;
        if (!$sv)
            continue;

        $svc_price = (float) $sv['price'];
        if ($sv['charge_type'] === 'per_night') {
            $svc_total = $svc_price * $nights;
            $svc_label = '× ' . $nights . ' คืน';
        } elseif ($sv['charge_type'] === 'per_pet') {
            $svc_total = $svc_price * count($pet_ids);
            $svc_label = '× ' . count($pet_ids) . ' ตัว';
        } else {
            $svc_total = $svc_price;
            $svc_label = 'ต่อการเข้าพัก';
        }

        $services_total += $svc_total;
        $item_services[] = ['name' => $sv['name'], 'price' => $svc_price, 'total' => $svc_total, 'label' => $svc_label];
    }

    $item_total = $room_total + $services_total;
    $grand_subtotal += $item_total;

    // ชื่อสัตว์เลี้ยง
    $pet_names = [];
    foreach ($pet_ids as $pid) {
        $pet_names[] = $pets_map[$pid] ?? null;
    }

    $cart_items[] = [
        'index' => $index,
        'room_type' => $rt,
        'check_in' => $item['check_in_date'],
        'check_out' => $item['check_out_date'],
        'nights' => $nights,
        'room_total' => $room_total,
        'has_peak' => $has_peak,
        'pets' => array_filter($pet_names),
        'services' => $item_services,
        'services_total' => $services_total,
        'item_total' => $item_total,
    ];
}

// ─── คำนวณส่วนลด ───
$discount_amount = 0;
if ($promo && $grand_subtotal > 0) {
    if ($promo['discount_type'] === 'percentage') {
        $discount_amount = $grand_subtotal * ((float) $promo['discount_value'] / 100);
        if ($promo['max_discount_amount'] !== null && $discount_amount > (float) $promo['max_discount_amount']) {
            $discount_amount = (float) $promo['max_discount_amount'];
        }
    } else {
        $discount_amount = (float) $promo['discount_value'];
    }
    $discount_amount = min($discount_amount, $grand_subtotal);
}
$net_total = $grand_subtotal - $discount_amount;
?>

<section class="py-6 md:py-12 bg-base-200/50 min-h-[85vh] relative overflow-hidden">
    <!-- Decorative -->
    <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-primary/5 rounded-full blur-3xl pointer-events-none">
    </div>
    <div
        class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-secondary/5 rounded-full blur-3xl pointer-events-none">
    </div>

    <div class="w-full max-w-6xl mx-auto px-4 relative z-10">

        <!-- Header -->
        <div class="text-center mb-8 md:mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4">
                <i data-lucide="shopping-cart" class="size-8"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-primary mb-2">ตะกร้าของฉัน</h1>
            <p class="text-base-content/60">
                <?php if (!empty($cart_items)): ?>
                    ตรวจสอบรายการจองของคุณก่อนดำเนินการยืนยัน
                <?php else: ?>
                    ยังไม่มีรายการในตะกร้า
                <?php endif; ?>
            </p>
        </div>

        <?php if (empty($cart_items)): ?>
            <!-- ═══ EMPTY STATE ═══ -->
            <div class="card bg-base-100 max-w-lg mx-auto border border-base-200">
                <div class="card-body items-center text-center py-16 px-8">
                    <div class="relative mb-6">
                        <div class="bg-base-200 p-6 rounded-full">
                            <i data-lucide="shopping-cart" class="size-16 text-base-content/20"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 bg-primary/10 text-primary p-2 rounded-full">
                            <i data-lucide="paw-print" class="size-6"></i>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-base-content mb-2">ตะกร้าว่างเปล่า</h2>
                    <p class="text-base-content/60 mb-8 max-w-sm">
                        เริ่มจองห้องพักให้สัตว์เลี้ยงของคุณได้เลย!<br>
                        เราพร้อมดูแลน้องๆ อย่างดีที่สุด 🐾
                    </p>
                    <a href="?page=booking" class="btn btn-primary btn-lg px-8 gap-2">
                        <i data-lucide="calendar-plus" class="size-5"></i>
                        เริ่มจองห้องพัก
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- ═══ CART CONTENT ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT: Cart Items (2 cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cart_items as $ci): ?>
                        <div
                            class="card bg-base-100 shadow-md border border-base-200 overflow-hidden transition-all duration-300 hover:shadow-lg group">
                            <div class="card-body p-0">
                                <!-- Card Header -->
                                <div
                                    class="flex items-center justify-between bg-linear-to-r from-primary/5 to-transparent p-4 md:p-5 border-b border-base-200">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-primary text-primary-content p-2.5 rounded-xl shadow-sm">
                                            <i data-lucide="bed-double" class="size-5"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-lg text-base-content">
                                                <?php echo htmlspecialchars($ci['room_type']['name']); ?>
                                            </h3>
                                            <div class="flex items-center gap-2 text-sm text-base-content/60 mt-0.5">
                                                <i data-lucide="calendar" class="size-3.5"></i>
                                                <?php
                                                echo date('d M Y', strtotime($ci['check_in']));
                                                echo ' — ';
                                                echo date('d M Y', strtotime($ci['check_out']));
                                                ?>
                                                <span class="badge badge-ghost badge-sm">
                                                    <?php echo $ci['nights']; ?> คืน
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <form action="?action=cart" method="POST" class="inline">
                                        <input type="hidden" name="remove_item" value="1">
                                        <input type="hidden" name="remove_index" value="<?php echo $ci['index']; ?>">
                                        <button type="submit"
                                            class="btn btn-ghost btn-sm btn-circle text-base-content/40 hover:text-error hover:bg-error/10 transition-colors"
                                            title="ลบรายการนี้">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="p-4 md:p-5 space-y-4">
                                    <!-- Room Price -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-base-content/70">
                                            <i data-lucide="hotel" class="size-4 text-primary/70"></i>
                                            <span>ค่าห้องพัก
                                                (฿
                                                <?php echo number_format($ci['room_type']['base_price_per_night']); ?>/คืน
                                                ×
                                                <?php echo $ci['nights']; ?> คืน)
                                            </span>
                                        </div>
                                        <span class="font-bold text-base-content">
                                            ฿
                                            <?php echo number_format($ci['room_total']); ?>
                                        </span>
                                    </div>

                                    <?php if ($ci['has_peak']): ?>
                                        <div
                                            class="flex items-center gap-2 text-xs text-warning bg-warning/10 px-3 py-1.5 rounded-lg w-fit">
                                            <i data-lucide="trending-up" class="size-3.5"></i>
                                            <span>ช่วง Peak Season — ราคาอาจปรับเพิ่มบางคืน</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Pets -->
                                    <div>
                                        <div class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">
                                            สัตว์เลี้ยงเข้าพัก (
                                            <?php echo count($ci['pets']); ?> ตัว)
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($ci['pets'] as $pet): ?>
                                                <div class="badge badge-outline gap-1.5 py-3 px-3 border-primary/30 text-primary">
                                                    <i data-lucide="paw-print" class="size-3"></i>
                                                    <?php echo htmlspecialchars($pet['name']); ?>
                                                    <span class="text-[10px] text-base-content/40">(
                                                        <?php echo htmlspecialchars($pet['species_name'] ?? ''); ?>)
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Services -->
                                    <?php if (!empty($ci['services'])): ?>
                                        <div class="border-t border-base-200 pt-3">
                                            <div class="text-xs font-semibold text-base-content/50 uppercase tracking-wider mb-2">
                                                บริการเสริม
                                            </div>
                                            <div class="space-y-1.5">
                                                <?php foreach ($ci['services'] as $svc): ?>
                                                    <div
                                                        class="flex justify-between items-center text-sm bg-base-200/40 rounded-lg px-3 py-2">
                                                        <div class="flex items-center gap-2 text-base-content/70">
                                                            <i data-lucide="sparkles" class="size-3.5 text-accent"></i>
                                                            <?php echo htmlspecialchars($svc['name']); ?>
                                                            <span class="text-xs text-base-content/40">
                                                                <?php echo $svc['label']; ?>
                                                            </span>
                                                        </div>
                                                        <span class="font-medium">+฿
                                                            <?php echo number_format($svc['total']); ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Item Total -->
                                    <div class="border-t border-dashed border-base-200 pt-3 flex justify-between items-center">
                                        <span class="font-medium text-base-content/60">รวมรายการนี้</span>
                                        <span class="text-xl font-bold text-primary">
                                            ฿
                                            <?php echo number_format($ci['item_total']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add more rooms -->
                    <a href="?page=booking&step=1"
                        class="card bg-base-100 shadow-sm border-2 border-dashed border-base-300 hover:border-primary/50 transition-all duration-300 group cursor-pointer">
                        <div class="card-body flex-row items-center justify-center gap-3 py-6">
                            <div
                                class="bg-primary/10 text-primary p-2 rounded-full group-hover:bg-primary group-hover:text-primary-content transition-colors">
                                <i data-lucide="plus" class="size-5"></i>
                            </div>
                            <span
                                class="font-medium text-base-content/60 group-hover:text-primary transition-colors">จองห้องเพิ่มเติม</span>
                        </div>
                    </a>
                </div>

                <!-- RIGHT: Summary Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-20 space-y-4">

                        <!-- Promo Code -->
                        <div class="card bg-base-100 shadow-md border border-base-200">
                            <div class="card-body p-5">
                                <h3 class="font-bold text-base flex items-center gap-2 mb-3">
                                    <i data-lucide="ticket" class="size-5 text-accent"></i>
                                    โค้ดส่วนลด
                                </h3>

                                <?php if ($promo): ?>
                                    <!-- มีโปรโมชันแล้ว -->
                                    <div class="bg-success/10 border border-success/30 rounded-xl p-4 flex items-start gap-3">
                                        <div class="bg-success text-success-content p-1.5 rounded-full shrink-0 mt-0.5">
                                            <i data-lucide="check" class="size-4"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-bold text-success text-sm">
                                                <?php echo htmlspecialchars($promo['code']); ?>
                                            </div>
                                            <div class="text-xs text-base-content/60 mt-0.5">
                                                <?php echo htmlspecialchars($promo['title']); ?>
                                            </div>
                                            <div class="text-sm font-bold text-success mt-1">
                                                -฿
                                                <?php echo number_format($discount_amount); ?>
                                            </div>
                                        </div>
                                        <form action="?action=cart" method="POST">
                                            <input type="hidden" name="remove_promo" value="1">
                                            <button type="submit"
                                                class="btn btn-ghost btn-xs btn-circle text-base-content/40 hover:text-error">
                                                <i data-lucide="x" class="size-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <!-- ยังไม่มีโปรโมชัน -->
                                    <form action="?action=cart" method="POST" class="flex gap-2">
                                        <input type="hidden" name="apply_promo" value="1">
                                        <input type="text" name="promo_code" placeholder="กรอกรหัสโปรโมชัน"
                                            class="input input-bordered input-sm flex-1 focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            required>
                                        <button type="submit" class="btn btn-primary btn-sm gap-1 px-4">
                                            <i data-lucide="tag" class="size-3.5"></i> ใช้โค้ด
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="card bg-base-100 shadow-xl border border-base-200 overflow-hidden">
                            <div class="card-body p-5 space-y-4">
                                <h3 class="font-bold text-lg flex items-center gap-2">
                                    <i data-lucide="receipt" class="size-5 text-primary"></i>
                                    สรุปรายการ
                                </h3>

                                <div class="space-y-3">
                                    <!-- Items breakdown -->
                                    <?php foreach ($cart_items as $i => $ci): ?>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-base-content/70 truncate max-w-[70%]">
                                                <?php echo ($i + 1) . '. ' . htmlspecialchars($ci['room_type']['name']); ?>
                                                <span class="text-xs text-base-content/40">(
                                                    <?php echo $ci['nights']; ?> คืน)
                                                </span>
                                            </span>
                                            <span class="font-medium whitespace-nowrap">
                                                ฿
                                                <?php echo number_format($ci['item_total']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="border-t border-base-200 pt-3 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-base-content/60">ยอดรวม</span>
                                        <span class="font-medium">฿
                                            <?php echo number_format($grand_subtotal); ?>
                                        </span>
                                    </div>

                                    <?php if ($discount_amount > 0): ?>
                                        <div class="flex justify-between text-sm text-success">
                                            <span>ส่วนลด</span>
                                            <span class="font-medium">-฿
                                                <?php echo number_format($discount_amount); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Net Total -->
                                <div class="bg-primary text-primary-content rounded-2xl p-5 -mx-1">
                                    <div class="text-primary-content/80 text-xs font-medium mb-1">ยอดสุทธิที่ต้องชำระ</div>
                                    <div class="text-3xl font-black text-white tracking-tight">
                                        ฿
                                        <?php echo number_format($net_total); ?>
                                    </div>
                                    <?php if ($discount_amount > 0): ?>
                                        <div class="text-primary-content/60 text-xs mt-1">
                                            ประหยัดไป ฿
                                            <?php echo number_format($discount_amount); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Special Requests -->
                                <form action="?action=cart" method="POST" id="confirm-form">
                                    <input type="hidden" name="confirm_booking" value="1">

                                    <div class="form-control mb-4">
                                        <label class="label pb-1">
                                            <span class="label-text text-sm font-medium flex items-center gap-1.5">
                                                <i data-lucide="message-square" class="size-4 text-base-content/50"></i>
                                                คำขอพิเศษ (ไม่จำเป็น)
                                            </span>
                                        </label>
                                        <textarea name="special_requests" rows="3"
                                            class="textarea textarea-bordered text-sm resize-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            placeholder="เช่น ต้องการอาหารพิเศษ, แพ้อะไรบ้าง ฯลฯ"></textarea>
                                    </div>

                                    <button type="submit"
                                        class="btn btn-primary btn-lg w-full gap-2 font-bold text-base ">
                                        <i data-lucide="check-circle" class="size-5"></i>
                                        ยืนยันการจอง
                                    </button>
                                </form>

                                <p class="text-xs text-center text-base-content/40 leading-relaxed">
                                    หลังยืนยันจะได้รับหมายเลขการจอง<br>และสามารถชำระเงินได้ในขั้นตอนถัดไป
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>