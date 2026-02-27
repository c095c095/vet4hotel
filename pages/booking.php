<?php
// ═══════════════════════════════════════════════════════════
// BOOKING PAGE — VET4 HOTEL
// Multi-step Wizard สำหรับจองห้องพักสัตว์เลี้ยง
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    $current_url = '?' . http_build_query($_GET);
    header("Location: ?page=login&redirect=" . urlencode($current_url));
    exit();
}

$customer_id = $_SESSION['customer_id'];

// หากมีการส่ง room_type_id มาจากหน้า rooms.php ให้เก็บไว้ใน session แล้ว redirect เพื่อลบออกจาก URL
if (isset($_GET['room_type_id'])) {
    $_SESSION['booking_form']['room_type_id'] = (int) $_GET['room_type_id'];

    $query = $_GET;
    unset($query['room_type_id']);
    $query_string = http_build_query($query);

    echo "<script>window.location.replace('?" . $query_string . "');</script>";
    exit();
}

// รับ Step จาก GET ถ้าไม่มีให้เริ่มที่ 1
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;

// --- ดึงข้อมูลพื้นฐาน ---
$pets = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, s.name as species, b.name as breed FROM pets p LEFT JOIN species s ON p.species_id = s.id LEFT JOIN breeds b ON p.breed_id = b.id WHERE p.customer_id = ? AND p.deleted_at IS NULL ORDER BY p.name ASC");
    $stmt->execute([$customer_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pets = [];
}

$form = @$_SESSION['booking_form'];
$selected_room_type = @$form['room_type_id'];
$selected_pets = (array) @$form['pet_ids'];
$selected_services = (array) @$form['service_ids'];
$check_in_date = @$form['check_in_date'];
$check_out_date = @$form['check_out_date'];

$room_types = [];
try {
    $stmt = $pdo->prepare("SELECT rt.*, COUNT(DISTINCT r.id) AS available_rooms
        FROM room_types rt
        JOIN rooms r ON r.room_type_id = rt.id AND r.status = 'active' AND r.deleted_at IS NULL
        AND NOT EXISTS (
                SELECT 1
                FROM booking_items bi
                JOIN bookings b ON b.id = bi.booking_id
                WHERE bi.room_id = r.id AND b.status NOT IN ('cancelled') AND bi.check_in_date < :check_out AND bi.check_out_date > :check_in
        )
        WHERE rt.is_active = 1
        GROUP BY rt.id
        HAVING available_rooms > 0
        ORDER BY rt.base_price_per_night ASC
    ");

    $stmt->execute([
        ':check_in' => !empty($check_in_date) ? $check_in_date : date('Y-m-d'),
        ':check_out' => !empty($check_out_date) ? $check_out_date : date('Y-m-d', strtotime('+1 day')),
    ]);

    $sql = $stmt->queryString;
    $params = [
        ':check_in' => !empty($check_in_date) ? $check_in_date : date('Y-m-d'),
        ':check_out' => !empty($check_out_date) ? $check_out_date : date('Y-m-d', strtotime('+1 day'))
    ];
    foreach ($params as $key => $value) {
        $sql = str_replace($key, "'$value'", $sql);
    }

    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $room_types = [];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') ORDER BY name ASC");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $services = [];
}

if ($step === 2) {
    if (!$check_in_date || !$check_out_date) {
        $_SESSION['booking_error'] = 'กรุณาเลือกวันที่เข้าพักและวันที่เช็คเอาท์';
        echo "<script>window.location.href='?page=booking&step=1';</script>";
        exit();
    }
    if (strtotime($check_in_date) >= strtotime($check_out_date)) {
        $_SESSION['booking_error'] = 'วันที่เช็คเอาท์ต้องมาหลังจากวันที่เข้าพักอย่างน้อย 1 วัน';
        echo "<script>window.location.href='?page=booking&step=1';</script>";
        exit();
    }
}

if ($step === 3 || $step === 4) {
    if (!$selected_room_type) {
        $_SESSION['booking_error'] = 'กรุณาเลือกประเภทห้องพัก';
        echo "<script>window.location.href='?page=booking&step=2';</script>";
        exit();
    }

    if (empty($selected_pets)) {
        $_SESSION['booking_error'] = 'กรุณาเลือกสัตว์เลี้ยงที่จะเข้าพัก';
        echo "<script>window.location.href='?page=booking&step=2';</script>";
        exit();
    }
}

function estimate_total($room_types, $selected_room_type, $check_in_date, $check_out_date, $services, $selected_services, $selected_pets = [])
{
    $total = 0;
    $nights = 1;
    if ($check_in_date && $check_out_date) {
        $start = strtotime($check_in_date);
        $end = strtotime($check_out_date);
        $nights = max(1, round(($end - $start) / 86400));
    }
    $pet_count = max(1, count((array) $selected_pets));
    foreach ($room_types as $rt) {
        if ($rt['id'] == $selected_room_type) {
            $total += $rt['base_price_per_night'] * $nights;
            break;
        }
    }
    foreach ($services as $sv) {
        if (in_array($sv['id'], (array) $selected_services)) {
            if ($sv['charge_type'] === 'per_night') {
                $total += $sv['price'] * $nights;
            } elseif ($sv['charge_type'] === 'per_pet') {
                $total += $sv['price'] * $pet_count;
            } else {
                $total += $sv['price'];
            }
        }
    }
    return $total;
}
?>

<section class="py-6 md:py-12 bg-base-200/50 min-h-[85vh] flex items-center justify-center relative overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-primary/5 rounded-full blur-3xl pointer-events-none">
    </div>
    <div
        class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-secondary/5 rounded-full blur-3xl pointer-events-none">
    </div>

    <div class="w-full max-w-4xl mx-auto md:px-4 relative z-10">
        <div
            class="card bg-base-100 shadow-2xl shadow-base-content/5 border border-base-100 backdrop-blur-sm relative overflow-visible rounded-none md:rounded-md">
            <div class="card-body p-6 md:p-10">
                <?php if (!empty($_SESSION['booking_error'])): ?>
                    <div
                        class="alert alert-error mb-6 shadow-sm border border-error/20 flex items-start animate-in fade-in slide-in-from-top-2">
                        <i data-lucide="alert-triangle" class="size-5 mt-0.5"></i>
                        <span><?php echo sanitize($_SESSION['booking_error']); ?></span>
                    </div>
                    <?php unset($_SESSION['booking_error']); ?>
                <?php endif; ?>

                <!-- Progress Steps -->
                <div class="mb-10 w-full overflow-x-auto pb-4">
                    <ul class="steps steps-horizontal w-full min-w-[320px]">
                        <li class="step <?php if ($step >= 1)
                            echo 'step-primary font-bold'; ?>">
                            <div class="flex flex-col items-center mt-2 group">
                                <i data-lucide="calendar"
                                    class="size-5 mb-1 <?php echo $step >= 1 ? 'text-primary' : 'opacity-40'; ?>"></i>
                                <span class="text-xs sm:text-sm">วันเข้าพัก</span>
                            </div>
                        </li>
                        <li class="step <?php if ($step >= 2)
                            echo 'step-primary font-bold'; ?>">
                            <div class="flex flex-col items-center mt-2 group">
                                <i data-lucide="bed"
                                    class="size-5 mb-1 <?php echo $step >= 2 ? 'text-primary' : 'opacity-40'; ?>"></i>
                                <span class="text-xs sm:text-sm">ห้อง/สัตว์เลี้ยง</span>
                            </div>
                        </li>
                        <li class="step <?php if ($step >= 3)
                            echo 'step-primary font-bold'; ?>">
                            <div class="flex flex-col items-center mt-2 group">
                                <i data-lucide="sparkles"
                                    class="size-5 mb-1 <?php echo $step >= 3 ? 'text-primary' : 'opacity-40'; ?>"></i>
                                <span class="text-xs sm:text-sm">บริการเสริม</span>
                            </div>
                        </li>
                        <li class="step <?php if ($step >= 4)
                            echo 'step-primary font-bold'; ?>">
                            <div class="flex flex-col items-center mt-2 group">
                                <i data-lucide="clipboard-check"
                                    class="size-5 mb-1 <?php echo $step >= 4 ? 'text-primary' : 'opacity-40'; ?>"></i>
                                <span class="text-xs sm:text-sm">ยืนยัน</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <form action="?action=booking" method="POST" autocomplete="off">
                    <input type="hidden" name="current_step" value="<?php echo $step; ?>">

                    <?php if ($step === 1): ?>
                        <!-- STEP 1: เลือกวันเข้าพัก -->
                        <div class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div class="text-center mb-8">
                                <h1 class="text-2xl md:text-3xl font-bold text-primary mb-2">
                                    เริ่มวางแผนวันหยุดให้สัตว์เลี้ยงของคุณ</h1>
                                <p class="text-base-content/70">เลือกวันที่ต้องการเข้าพักและเช็คเอาท์เพื่อดูห้องว่าง</p>
                            </div>

                            <div
                                class="bg-base-200/50 rounded-3xl p-6 md:p-8 border border-base-200 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                                    <i data-lucide="calendar" class="w-32 h-32"></i>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10 w-full">
                                    <div class="form-control w-full">
                                        <label class="label font-medium text-base-content justify-start gap-2"
                                            for="check_in_date">
                                            <i data-lucide="calendar-check" class="size-5 text-primary"></i>
                                            วันที่เช็คอิน <span class="text-error">*</span>
                                        </label>
                                        <input type="date" id="check_in_date" name="check_in_date"
                                            class="input input-bordered input-lg w-full bg-base-100 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium"
                                            min="<?php echo date('Y-m-d'); ?>"
                                            value="<?php echo sanitize($check_in_date); ?>" required>
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="label font-medium text-base-content justify-start gap-2"
                                            for="check_out_date">
                                            <i data-lucide="calendar-x" class="size-5 text-primary"></i>
                                            วันที่เช็คเอาท์ <span class="text-error">*</span>
                                        </label>
                                        <input type="date" id="check_out_date" name="check_out_date"
                                            class="input input-bordered input-lg w-full bg-base-100 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-medium"
                                            min="<?php echo date('Y-m-d'); ?>"
                                            value="<?php echo sanitize($check_out_date); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-8 flex justify-end">
                                <button type="submit" id="next-step-1" class="btn btn-primary btn-lg px-8 gap-2" disabled>
                                    ขั้นตอนถัดไป <i data-lucide="arrow-right" class="size-5"></i>
                                </button>
                            </div>
                        </div>

                    <?php elseif ($step === 2): ?>
                        <!-- STEP 2: เลือกห้อง & สัตว์เลี้ยง -->
                        <div class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div class="text-center mb-8">
                                <h1 class="text-2xl md:text-3xl font-bold text-primary mb-2">เลือกห้องพักและสัตว์เลี้ยง</h1>
                                <p class="text-base-content/70">เลือกห้องที่ใช่สำหรับสัตว์เลี้ยงของคุณ
                                    (<?php echo $check_in_date; ?> - <?php echo $check_out_date; ?>)</p>
                            </div>

                            <h2 class="text-lg md:text-xl font-bold text-base-content mb-4 flex items-center gap-2">
                                <i data-lucide="bed" class="size-6 text-primary"></i> ประเภทห้องพัก
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                                <?php
                                // นับจำนวนห้องที่เลือกในตะกร้าแต่ละ room_type
                                $cart_count_map = [];
                                foreach ($_SESSION['booking_cart'] ?? [] as $item) {
                                    $rid = $item['room_type_id'] ?? null;
                                    $cin = $item['check_in_date'] ?? '';
                                    $cout = $item['check_out_date'] ?? '';
                                    if ($rid && $cin === $check_in_date && $cout === $check_out_date) {
                                        $cart_count_map[$rid] = ($cart_count_map[$rid] ?? 0) + 1;
                                    }
                                }

                                $has_room = false;
                                foreach ($room_types as $rt) {
                                    $cart_count = $cart_count_map[$rt['id']] ?? 0;
                                    if ($rt['available_rooms'] <= $cart_count) {
                                        // ซ่อนห้องที่เต็มแล้ว
                                        continue;
                                    }
                                    $has_room = true;
                                    $is_selected = ($selected_room_type == $rt['id']);
                                    ?>
                                    <label class="group relative block cursor-pointer h-full"
                                        data-max-pets="<?php echo (int) $rt['max_pets']; ?>">
                                        <input type="radio" name="room_type_id" value="<?php echo (int) $rt['id']; ?>"
                                            class="peer absolute opacity-0 w-0 h-0" <?php echo $is_selected ? 'checked' : ''; ?>
                                            required>
                                        <div
                                            class="card bg-base-100 border-2 border-base-200 transition-all overflow-hidden hover:shadow-md hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:shadow-primary/10 peer-checked:shadow-md h-full group-has-checked:border-primary">
                                            <div class="card-body p-5 flex flex-row gap-4 relative z-10">
                                                <div class="flex items-start pt-1">
                                                    <div
                                                        class="shrink-0 w-5 h-5 rounded-full border-2 border-base-300 flex items-center justify-center transition-colors group-hover:border-primary/50 group-has-checked:border-primary bg-base-100 mt-1">
                                                        <div
                                                            class="w-2.5 h-2.5 rounded-full bg-primary opacity-0 scale-50 transition-all duration-200 group-has-checked:opacity-100 group-has-checked:scale-100">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <h3
                                                            class="font-bold text-lg text-base-content group-hover:text-primary transition-colors">
                                                            <?php echo sanitize($rt['name']); ?>
                                                        </h3>
                                                        <div class="badge badge-success badge-sm gap-1">
                                                            <i data-lucide="check-circle" class="size-3"></i> ว่าง
                                                            <?php echo ($rt['available_rooms'] - $cart_count); ?>
                                                        </div>
                                                    </div>
                                                    <p class="text-sm text-base-content/70 line-clamp-2 mb-3">
                                                        <?php echo sanitize($rt['description']); ?>
                                                    </p>
                                                    <div class="flex flex-wrap gap-2 text-xs text-base-content/60 mb-3">
                                                        <div class="flex items-center gap-1 bg-base-200 px-2 py-1 rounded-md">
                                                            <i data-lucide="maximize" class="size-3"></i>
                                                            <?php echo sanitize($rt['size_sqm']); ?> ตร.ม.
                                                        </div>
                                                        <div class="flex items-center gap-1 bg-base-200 px-2 py-1 rounded-md">
                                                            <i data-lucide="users" class="size-3"></i> สูงสุด
                                                            <?php echo (int) $rt['max_pets']; ?> ตัว
                                                        </div>
                                                    </div>
                                                    <div class="mt-auto flex items-end justify-between">
                                                        <div class="text-primary font-bold text-xl">
                                                            ฿<?php echo number_format($rt['base_price_per_night']); ?>
                                                            <span class="text-sm font-normal text-base-content/50">/คืน</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <?php
                                }
                                if (!$has_room) {
                                    echo '<div class="col-span-full"><div role="alert" class="alert alert-error bg-error/10 text-error border border-error/20 flex items-start"><i data-lucide="alert-circle" class="size-5 mt-0.5"></i> <div><h3 class="font-bold">ขออภัย ห้องพักเต็ม</h3><div class="text-sm">ขณะนี้ไม่มีห้องว่างสำหรับวันดังกล่าว กรุณาลองเลือกวันอื่น หรือสอบถามพนักงาน</div></div></div></div>';
                                }
                                ?>
                            </div>

                            <h2 class="text-lg md:text-xl font-bold text-base-content mb-4 flex items-center gap-2">
                                <i data-lucide="dog" class="size-6 text-primary"></i> เลือกสัตว์เลี้ยง
                            </h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                <?php
                                $max_pets_val = 1;
                                foreach ($room_types as $rt) {
                                    if ($selected_room_type == $rt['id']) {
                                        $max_pets_val = (int) $rt['max_pets'];
                                        break;
                                    }
                                }
                                if (empty($pets)) {
                                    ?>
                                    <div
                                        class="col-span-full bg-base-200/50 border border-base-200 rounded-2xl p-8 flex flex-col items-center justify-center text-center">
                                        <div class="bg-base-100 p-4 rounded-full shadow-sm mb-4">
                                            <i data-lucide="cat" class="size-12 text-base-content/30"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-base-content mb-2">คุณยังไม่มีสัตว์เลี้ยงในระบบ</h3>
                                        <p class="text-base-content/60 mb-6 max-w-sm">
                                            เพิ่มข้อมูลสัตว์เลี้ยงของคุณเพื่อดำเนินการจองห้องพักให้เสร็จสิ้น</p>
                                        <a href="?page=my_pets" class="btn btn-primary rounded-full px-8 shadow-sm">
                                            <i data-lucide="plus" class="size-5"></i> เพิ่มสัตว์เลี้ยงใหม่
                                        </a>
                                    </div>
                                    <?php
                                } else {
                                    foreach ($pets as $pet) {
                                        $is_pet_selected = in_array($pet['id'], $selected_pets);
                                        ?>
                                        <label class="cursor-pointer group relative">
                                            <input type="checkbox" name="pet_ids[]" value="<?php echo (int) $pet['id']; ?>"
                                                class="peer hidden" <?php echo $is_pet_selected ? 'checked' : ''; ?>>
                                            <div
                                                class="border-2 rounded-xl p-4 transition-all duration-200 flex flex-col items-center justify-center gap-2 text-center peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary peer-disabled:opacity-50 peer-disabled:cursor-not-allowed border-base-200 bg-base-100 hover:border-primary/30">
                                                <div class="avatar placeholder">
                                                    <div
                                                        class="bg-neutral text-neutral-content w-12 rounded-full flex items-center justify-center">
                                                        <span
                                                            class="text-xl"><?php echo mb_substr($pet['name'], 0, 1, "UTF-8"); ?></span>
                                                    </div>
                                                    <div
                                                        class="absolute -top-1 -right-1 bg-primary text-primary-content rounded-full p-1 opacity-0 scale-50 transition-all duration-200 peer-checked:opacity-100 peer-checked:scale-100 z-10 hidden group-[.peer:checked+&]:block">
                                                        <i data-lucide="check" class="size-3"></i>
                                                    </div>
                                                </div>
                                                <span class="font-medium truncate w-full"
                                                    title="<?php echo sanitize($pet['name']); ?>"><?php echo sanitize($pet['name']); ?></span>
                                                <span class="text-[10px] text-base-content/50 uppercase tracking-wider">
                                                    <?php
                                                    $species_text = $pet['species'] ?? 'Pet';
                                                    $breed_text = $pet['breed'] ?? '';
                                                    echo sanitize($species_text . ($breed_text ? ' • ' . $breed_text : ''));
                                                    ?>
                                                </span>
                                            </div>
                                        </label>
                                        <?php
                                    }
                                }
                                ?>
                            </div>

                            <div
                                class="mt-10 flex flex-wrap-reverse justify-between items-center gap-4 pt-6 border-t border-base-200">
                                <a href="?page=booking&step=1"
                                    class="btn btn-ghost text-base-content/70 hover:text-base-content gap-2 px-6">
                                    <i data-lucide="arrow-left" class="size-5"></i> ย้อนกลับ
                                </a>
                                <?php
                                if (!empty($_SESSION["booking_cart"]) && !$has_room) {
                                    ?>
                                    <a href="?page=cart" class="btn btn-primary px-8">ไปหน้าตะกร้าของคุณ</a>
                                    <?php
                                } else {
                                    ?>
                                    <button type="submit" id="next-step-2" class="btn btn-primary btn-lg px-8 gap-2" disabled>
                                        ขั้นตอนถัดไป <i data-lucide="arrow-right" class="size-5"></i>
                                    </button>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>

                    <?php elseif ($step === 3): ?>
                        <!-- STEP 3: เลือกบริการเสริม -->
                        <div class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div class="text-center mb-8">
                                <h1 class="text-2xl md:text-3xl font-bold text-primary mb-2">เพิ่มบริการพิเศษ</h1>
                                <p class="text-base-content/70">ดูแลสัตว์เลี้ยงของคุณให้ดีที่สุดด้วยบริการเสริมของเรา
                                    (เลือกหรือไม่เลือกก็ได้)</p>
                            </div>

                            <?php if (empty($services)): ?>
                                <div class="text-center py-10 bg-base-200/50 rounded-2xl border border-base-200">
                                    <i data-lucide="sparkles" class="size-12 mx-auto text-base-content/30 mb-3"></i>
                                    <h3 class="font-medium text-base-content">ยังไม่มีบริการเสริมในขณะนี้</h3>
                                </div>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                                    <?php foreach ($services as $sv):
                                        $is_srv_selected = in_array($sv['id'], $selected_services);
                                        ?>
                                        <label class="group relative cursor-pointer block">
                                            <input type="checkbox" name="service_ids[]" value="<?php echo (int) $sv['id']; ?>"
                                                class="peer hidden" <?php echo $is_srv_selected ? 'checked' : ''; ?>>
                                            <div
                                                class="bg-base-100 border-2 rounded-2xl p-4 transition-all duration-300 group-has-checked:border-primary group-has-checked:bg-primary/5 group-has-checked:shadow-sm group-has-checked:shadow-primary/10 border-base-200 hover:border-primary/40 flex items-start gap-4">
                                                <div
                                                    class="mt-1 shrink-0 w-6 h-6 rounded-md border-2 border-base-300 flex items-center justify-center transition-colors group-hover:border-primary/50 group-has-checked:bg-primary group-has-checked:border-primary text-primary-content">
                                                    <i data-lucide="check"
                                                        class="size-4 opacity-0 scale-50 transition-all duration-200 group-has-checked:opacity-100 group-has-checked:scale-100"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex justify-between items-start mb-1 gap-2">
                                                        <h4
                                                            class="font-bold text-base-content group-hover:text-primary transition-colors leading-tight">
                                                            <?php echo sanitize($sv['name']); ?>
                                                        </h4>
                                                        <div
                                                            class="text-primary font-bold whitespace-nowrap bg-primary/10 px-2 py-0.5 rounded-md text-sm">
                                                            +฿<?php echo number_format($sv['price']); ?>
                                                        </div>
                                                    </div>
                                                    <p class="text-sm text-base-content/60 leading-snug">
                                                        <?php echo sanitize($sv['description']); ?>

                                                    <div class="text-xs text-base-content/40 mt-1">
                                                        <?php
                                                        if ($sv['charge_type'] === 'per_night') {
                                                            echo 'คิดค่าบริการต่อคืน';
                                                        } elseif ($sv['charge_type'] === 'per_pet') {
                                                            echo 'คิดค่าบริการต่อสัตว์เลี้ยง';
                                                        } else {
                                                            echo 'คิดค่าบริการต่อการเข้าพัก';
                                                        }
                                                        ?>
                                                    </div>
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div
                                class="mt-10 flex flex-wrap-reverse justify-between items-center gap-4 pt-6 border-t border-base-200">
                                <a href="?page=booking&step=2"
                                    class="btn btn-ghost text-base-content/70 hover:text-base-content gap-2 px-6">
                                    <i data-lucide="arrow-left" class="size-5"></i> ย้อนกลับ
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg px-8 gap-2">
                                    สรุปการจอง <i data-lucide="arrow-right" class="size-5"></i>
                                </button>
                            </div>
                        </div>

                    <?php elseif ($step === 4): ?>
                        <!-- STEP 4: สรุปและยืนยัน -->
                        <div class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div class="text-center mb-8">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-success/10 text-success mb-4">
                                    <i data-lucide="clipboard-check" class="size-8"></i>
                                </div>
                                <h1 class="text-2xl md:text-3xl font-bold text-primary mb-2">ตรวจสอบรายการจอง</h1>
                                <p class="text-base-content/70">กรุณาตรวจสอบข้อมูลให้ถูกต้องก่อนเพิ่มลงตะกร้า</p>
                            </div>

                            <div
                                class="bg-base-100 rounded-3xl overflow-hidden md:border border-base-200 md:shadow-sm mb-8">
                                <div class="p-0 md:p-8">
                                    <!-- Header Receipt -->
                                    <div
                                        class="flex items-center justify-between border-b-2 border-dashed border-base-200 pb-6 mb-6">
                                        <div>
                                            <h3 class="font-bold text-lg">รายละเอียดการเข้าพัก</h3>
                                            <div class="text-sm text-base-content/60 mt-1 flex items-center gap-2">
                                                <i data-lucide="calendar" class="size-4"></i>
                                                <?php
                                                $ci_fmt = date('d M Y', strtotime($check_in_date));
                                                $co_fmt = date('d M Y', strtotime($check_out_date));
                                                $nights = max(1, round((strtotime($check_out_date) - strtotime($check_in_date)) / 86400));
                                                echo "$ci_fmt - $co_fmt ($nights คืน)";
                                                ?>
                                            </div>
                                        </div>
                                        <div class="bg-base-200 p-3 rounded-full hidden sm:block">
                                            <i data-lucide="hotel" class="size-6 text-primary"></i>
                                        </div>
                                    </div>

                                    <!-- Room & Pets Info -->
                                    <div class="grid gap-6 md:grid-cols-2 mb-6">
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3">
                                                ห้องพัก</h4>
                                            <div class="flex items-start gap-3">
                                                <div class="bg-primary/10 text-primary p-2 rounded-lg mt-0.5">
                                                    <i data-lucide="bed-double" class="size-5"></i>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-base-content text-lg">
                                                        <?php foreach ($room_types as $rt) {
                                                            if ($rt['id'] == $selected_room_type) {
                                                                echo sanitize($rt['name']);
                                                                $selected_rt_price = $rt['base_price_per_night'];
                                                                break;
                                                            }
                                                        } ?>
                                                    </div>
                                                    <div class="text-sm text-base-content/60">
                                                        ฿<?php echo number_format($selected_rt_price ?? 0); ?> / คืน</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3">
                                                สัตว์เลี้ยงเข้าพัก (<?php echo count($selected_pets); ?> ตัว)</h4>
                                            <div class="flex flex-wrap gap-2">
                                                <?php
                                                foreach ($pets as $p) {
                                                    if (in_array($p['id'], $selected_pets)) {
                                                        echo '<div class="badge badge-accent badge-outline gap-1 py-3 px-3"><i data-lucide="paw-print" class="size-3"></i> ' . sanitize($p['name']) . '</div>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Services Info -->
                                    <?php if (!empty($selected_services)): ?>
                                        <div class="mb-6">
                                            <h4
                                                class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3">
                                                บริการเสริม</h4>
                                            <ul class="space-y-2">
                                                <?php
                                                foreach ($services as $s) {
                                                    if (in_array($s['id'], $selected_services)) {
                                                        // คำนวณราคาจริงตาม charge_type
                                                        $s_nights = max(1, round((strtotime($check_out_date) - strtotime($check_in_date)) / 86400));
                                                        $s_pets = max(1, count($selected_pets));
                                                        if ($s['charge_type'] === 'per_night') {
                                                            $s_total = $s['price'] * $s_nights;
                                                            $s_label = '× ' . $s_nights . ' คืน';
                                                        } elseif ($s['charge_type'] === 'per_pet') {
                                                            $s_total = $s['price'] * $s_pets;
                                                            $s_label = '× ' . $s_pets . ' ตัว';
                                                        } else {
                                                            $s_total = $s['price'];
                                                            $s_label = 'ต่อการเข้าพัก';
                                                        }
                                                        echo '<li class="flex justify-between items-center bg-base-200/50 rounded-lg p-3">';
                                                        echo '<div class="flex items-center gap-2 font-medium"><i data-lucide="plus-circle" class="size-4 text-primary"></i> ' . sanitize($s['name']) . ' <span class="text-xs text-base-content/40">' . $s_label . '</span></div>';
                                                        echo '<div class="text-sm font-bold">฿' . number_format($s_total) . '</div>';
                                                        echo '</li>';
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Total -->
                                    <div class="border-t-2 border-dashed border-base-200 pt-6 mt-2">
                                        <div
                                            class="bg-primary text-primary-content rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-lg shadow-primary/30">
                                            <div>
                                                <div class="text-primary-content/80 text-sm font-medium mb-1">
                                                    ยอดรวมโดยประมาณ</div>
                                                <div class="text-xs text-primary-content/60">* ยอดสุดท้ายอาจเปลี่ยนแปลงตาม
                                                    Peak Season</div>
                                            </div>
                                            <div class="text-4xl font-black text-white tracking-tight">
                                                ฿<?php echo number_format(estimate_total($room_types, $selected_room_type, $check_in_date, $check_out_date, $services, $selected_services, $selected_pets)); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col-reverse sm:flex-row justify-between gap-4">
                                <a href="?page=booking&step=3"
                                    class="btn btn-ghost text-base-content/70 hover:text-base-content gap-2 px-6">
                                    <i data-lucide="arrow-left" class="size-5"></i> ย้อนกลับ
                                </a>
                                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                                    <button type="submit" name="add_another" value="1"
                                        class="btn btn-outline btn-lg border-2 border-primary text-primary gap-2 font-bold px-6">
                                        <i data-lucide="plus" class="size-4"></i> เพิ่มห้องนี้ และจองห้องต่อ
                                    </button>
                                    <button type="submit" name="confirm" value="1"
                                        class="btn btn-primary btn-lg px-8 gap-2 font-bold text-base">
                                        เพิ่มห้องนี้ และไปที่ตะกร้า <i data-lucide="shopping-cart" class="size-5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    // Logic สำหรับ Step 1
    const nextStep1 = document.getElementById('next-step-1');
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');

    if (nextStep1 && checkInInput && checkOutInput) {
        const validateStep1 = () => {
            if (checkInInput.value) {
                // อัปเดต min ของวันเช็คเอาท์ ไม่ให้น้อยกว่าวันเช็คอิน + 1 วัน
                let parts = checkInInput.value.split('-');
                if (parts.length === 3) {
                    let d = new Date(parts[0], parts[1] - 1, parts[2]);
                    d.setDate(d.getDate() + 1);
                    let minCheckOut = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                    checkOutInput.min = minCheckOut;

                    if (checkOutInput.value && checkOutInput.value < minCheckOut) {
                        checkOutInput.value = minCheckOut;
                    }
                }
            }
            // เปิดปุ่มถ้ามีการเลือกทั้งวันเช็คอินและเช็คเอาท์
            nextStep1.disabled = !(checkInInput.value && checkOutInput.value);
        };
        checkInInput.addEventListener('change', validateStep1);
        checkOutInput.addEventListener('change', validateStep1);
        validateStep1(); // รันครั้งแรกเผื่อมีค่าจาก Session
    }

    // Logic สำหรับ Step 2
    const nextStep2 = document.getElementById('next-step-2');
    if (nextStep2) {
        const validateStep2 = () => {
            const isRoomSelected = !!document.querySelector('input[name="room_type_id"]:checked');
            const isPetSelected = !!document.querySelector('input[name="pet_ids[]"]:checked');

            // เปิดปุ่มถ้าเลือกทั้งห้องพัก (Radio) และสัตว์เลี้ยงอย่างน้อย 1 ตัว (Checkbox)
            nextStep2.disabled = !(isRoomSelected && isPetSelected);
        };

        // ใช้ Event Delegation หรือดึง element มาผูก event
        document.querySelectorAll('input[name="room_type_id"]').forEach(r => {
            r.addEventListener('change', validateStep2);
        });
        document.querySelectorAll('input[name="pet_ids[]"]').forEach(c => {
            c.addEventListener('change', validateStep2);
        });

        validateStep2(); // รันครั้งแรกเผื่อมีค่าจาก Session
    }

    // จำกัดจำนวน checkbox สัตว์เลี้ยงตาม max_pets ของห้อง และจำนวนห้องที่เลือกในตะกร้า
    document.addEventListener('DOMContentLoaded', function () {
        const petCheckboxes = document.querySelectorAll('input[type="checkbox"][name="pet_ids[]"]');
        const roomRadios = document.querySelectorAll('input[type="radio"][name="room_type_id"]');
        function updatePetLimit() {
            let max = 1;
            const checkedRoom = document.querySelector('input[type="radio"][name="room_type_id"]:checked');
            if (checkedRoom) {
                const card = checkedRoom.closest('label.group');
                if (card && card.dataset.maxPets) {
                    max = parseInt(card.dataset.maxPets);
                }
            }
            let checkedCount = 0;
            petCheckboxes.forEach(cb => {
                if (cb.checked) {
                    if (checkedCount >= max) {
                        cb.checked = false; // Uncheck excess pets immediately
                    } else {
                        checkedCount++;
                    }
                }
            });
            petCheckboxes.forEach(cb => {
                if (!cb.checked && checkedCount >= max) cb.disabled = true;
                else cb.disabled = false;
            });
        }
        roomRadios.forEach(r => r.addEventListener('change', updatePetLimit));
        petCheckboxes.forEach(cb => cb.addEventListener('change', updatePetLimit));
        updatePetLimit();

        // Client-side validation: prevent selecting more rooms than available
        const bookingForm = document.querySelector('form[action*="booking"]');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function (e) {
                // เฉพาะ step 2 และ step 4 (add_another)
                const stepInput = bookingForm.querySelector('input[name="current_step"]');
                const step = stepInput ? parseInt(stepInput.value) : 1;
                if (step === 4 && bookingForm.querySelector('button[name="add_another"]')) {
                    // เช็คจำนวนห้องที่เลือกในตะกร้า
                    const roomTypeId = bookingForm.querySelector('input[name="room_type_id"]')?.value;
                    const checkInDate = bookingForm.querySelector('input[name="check_in_date"]')?.value;
                    const checkOutDate = bookingForm.querySelector('input[name="check_out_date"]')?.value;
                    let cartCount = 0;
                    let availableRooms = 0;
                    // ดึงค่าจาก PHP (ฝังไว้ใน JS)
                    <?php
                    // Prepare JS array for cart
                    $cart_js = [];
                    foreach ($_SESSION['booking_cart'] ?? [] as $item) {
                        $cart_js[] = [
                            'room_type_id' => $item['room_type_id'] ?? null,
                            'check_in_date' => $item['check_in_date'] ?? '',
                            'check_out_date' => $item['check_out_date'] ?? ''
                        ];
                    }
                    echo 'const bookingCart = ' . json_encode($cart_js) . ';';
                    // Prepare availableRooms per room_type_id
                    $room_avail_js = [];
                    foreach ($room_types as $rt) {
                        $room_avail_js[$rt['id']] = $rt['available_rooms'];
                    }
                    echo 'const availableRoomsMap = ' . json_encode($room_avail_js) . ';';
                    ?>
                    if (roomTypeId && checkInDate && checkOutDate) {
                        cartCount = bookingCart.filter(item =>
                            item.room_type_id == roomTypeId &&
                            item.check_in_date == checkInDate &&
                            item.check_out_date == checkOutDate
                        ).length;
                        availableRooms = availableRoomsMap[roomTypeId] || 0;
                        if (cartCount + 1 > availableRooms) {
                            alert('จำนวนห้องที่เลือกเกินจำนวนที่มีอยู่ กรุณาเลือกใหม่');
                            e.preventDefault();
                        }
                    }
                }
            });
        }
    });
</script>