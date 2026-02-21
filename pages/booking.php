<?php
// ═══════════════════════════════════════════════════════════
// BOOKING PAGE — VET4 HOTEL
// Multi-step Wizard สำหรับจองห้องพักสัตว์เลี้ยง
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login");
    exit();
}

$customer_id = $_SESSION['customer_id'];
// รับ Step จาก GET ถ้าไม่มีให้เริ่มที่ 1
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;

// --- ดึงข้อมูลพื้นฐาน ---
$pets = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE customer_id = ? AND deleted_at IS NULL ORDER BY name ASC");
    $stmt->execute([$customer_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pets = [];
}

$room_types = [];
try {
    $stmt = $pdo->prepare("SELECT rt.*, COUNT(r.id) AS available_rooms
        FROM room_types rt
        LEFT JOIN rooms r ON r.room_type_id = rt.id AND r.status = 'active' AND r.deleted_at IS NULL
        WHERE rt.is_active = 1
        GROUP BY rt.id
        HAVING available_rooms > 0
        ORDER BY rt.base_price_per_night ASC");
    $stmt->execute();
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

$form = @$_SESSION['booking_form'];
$selected_room_type = @$form['room_type_id'];
$selected_pets = (array) @$form['pet_ids'];
$selected_services = (array) @$form['service_ids'];
$check_in_date = @$form['check_in_date'];
$check_out_date = @$form['check_out_date'];

if ($step === 2) {
    if (!$check_in_date || !$check_out_date) {
        $_SESSION['booking_error'] = 'กรุณาเลือกวันที่เข้าพักและวันที่เช็คเอาท์';
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

function estimate_total($room_types, $selected_room_type, $check_in_date, $check_out_date, $services, $selected_services)
{
    $total = 0;
    $nights = 1;
    if ($check_in_date && $check_out_date) {
        $start = strtotime($check_in_date);
        $end = strtotime($check_out_date);
        $nights = max(1, round(($end - $start) / 86400));
    }
    foreach ($room_types as $rt) {
        if ($rt['id'] == $selected_room_type) {
            $total += $rt['base_price_per_night'] * $nights;
            break;
        }
    }
    foreach ($services as $sv) {
        if (in_array($sv['id'], (array) $selected_services)) {
            // สมมติคิดราคา per_stay
            $total += $sv['price'];
        }
    }
    return $total;
}
?>

<section class="py-12 md:py-16 bg-base-100 min-h-[80vh] flex items-center justify-center">
    <div class="w-full max-w-3xl mx-auto">
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body">
                <?php if (!empty($_SESSION['booking_error'])): ?>
                    <div class="alert alert-error mb-4">
                        <span><?php echo htmlspecialchars($_SESSION['booking_error']); ?></span>
                    </div>
                    <?php unset($_SESSION['booking_error']); ?>
                <?php endif; ?>
                <!-- Progress Steps -->
                <ul class="steps steps-horizontal w-full mb-8">
                    <li class="step<?php if ($step >= 1)
                        echo ' step-primary'; ?>">เลือกวันเข้าพัก</li>
                    <li class="step<?php if ($step >= 2)
                        echo ' step-primary'; ?>">เลือกห้อง & สัตว์เลี้ยง</li>
                    <li class="step<?php if ($step >= 3)
                        echo ' step-primary'; ?>">บริการเสริม</li>
                    <li class="step<?php if ($step >= 4)
                        echo ' step-primary'; ?>">สรุป & ยืนยัน</li>
                </ul>

                <form action="?action=booking" method="POST" autocomplete="off">
                    <input type="hidden" name="current_step" value="<?php echo $step; ?>">

                    <?php if ($step === 1): ?>
                        <!-- STEP 1: เลือกวันเข้าพัก -->
                        <div>
                            <h2 class="text-xl font-bold text-primary mb-4">เลือกวันเข้าพัก</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-control">
                                    <label class="label font-medium text-base-content" for="check_in_date">วันที่เช็คอิน
                                        <span class="text-error">*</span></label>
                                    <input type="date" id="check_in_date" name="check_in_date"
                                        class="input input-bordered w-full" min="<?php echo date('Y-m-d'); ?>"
                                        value="<?php echo htmlspecialchars($check_in_date); ?>" required>
                                </div>
                                <div class="form-control">
                                    <label class="label font-medium text-base-content" for="check_out_date">วันที่เช็คเอาท์
                                        <span class="text-error">*</span></label>
                                    <input type="date" id="check_out_date" name="check_out_date"
                                        class="input input-bordered w-full" min="<?php echo date('Y-m-d'); ?>"
                                        value="<?php echo htmlspecialchars($check_out_date); ?>" required>
                                </div>
                            </div>
                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="btn btn-primary gap-2">ถัดไป <i data-lucide="arrow-right"
                                        class="size-4"></i></button>
                            </div>
                        </div>

                    <?php elseif ($step === 2): ?>
                        <!-- STEP 2: เลือกห้อง & สัตว์เลี้ยง -->
                        <div>
                            <h2 class="text-xl font-bold text-primary mb-4">เลือกประเภทห้องพัก</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
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
                                    ?>
                                    <label class="card bg-base-200/60 border border-base-200 hover:border-primary transition-all cursor-pointer <?php if ($selected_room_type == $rt['id'])
                                        echo 'ring-2 ring-primary'; ?>">
                                        <div class="card-body flex-row items-center gap-4">
                                            <input type="radio" name="room_type_id" value="<?php echo (int) $rt['id']; ?>"
                                                class="radio radio-primary" <?php if ($selected_room_type == $rt['id'])
                                                    echo 'checked'; ?> required>
                                            <div>
                                                <div class="font-bold text-base-content text-lg">
                                                    <?php echo htmlspecialchars($rt['name']); ?>
                                                </div>
                                                <div class="text-sm text-base-content/60 mb-1">
                                                    <?php echo htmlspecialchars($rt['description']); ?>
                                                </div>
                                                <div class="text-xs text-base-content/50">ขนาด
                                                    <?php echo htmlspecialchars($rt['size_sqm']); ?> ตร.ม. | สูงสุด
                                                    <?php echo (int) $rt['max_pets']; ?> ตัว
                                                </div>
                                                <div class="mt-1 text-primary font-semibold">
                                                    ฿<?php echo number_format($rt['base_price_per_night']); ?>/คืน</div>
                                                <div class="badge badge-outline badge-success mt-1">ว่าง
                                                    <?php echo ($rt['available_rooms'] - $cart_count); ?> ห้อง</div>
                                            </div>
                                        </div>
                                    </label>
                                    <?php
                                }
                                if (!$has_room) {
                                    echo '<div class="col-span-2"><div role="alert" class="alert alert-error alert-soft"><span>ขออภัย ขณะนี้ไม่มีห้องว่างสำหรับเข้าพัก กรุณาลองเลือกวันอื่น หรือสอบถามพนักงาน</span></div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <h2 class="text-xl font-bold text-primary mb-4">เลือกสัตว์เลี้ยงที่จะเข้าพัก</h2>
                            <div class="flex flex-wrap gap-4">
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
                                    <div role="alert" class="alert alert-error alert-soft w-full">
                                        คุณยังไม่มีสัตว์เลี้ยงในระบบกรุณา<a href="?page=my_pets"
                                            class="underline">เพิ่มสัตว์เลี้ยง</a>ก่อนจองห้องพัก
                                    </div>
                                    <?php
                                } else {
                                    foreach ($pets as $pet) {
                                        ?>
                                        <label class="flex items-center gap-2 bg-base-200 px-4 py-2 rounded-xl cursor-pointer">
                                            <input type="checkbox" name="pet_ids[]" value="<?php echo (int) $pet['id']; ?>"
                                                class="checkbox checkbox-primary" <?php if (in_array($pet['id'], $selected_pets))
                                                    echo 'checked'; ?>>
                                            <span><?php echo htmlspecialchars($pet['name']); ?></span>
                                        </label>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <div class="mt-8 flex justify-between">
                                <a href="?page=booking&step=1" class="btn btn-outline gap-2"><i data-lucide="arrow-left"
                                        class="size-4"></i> ย้อนกลับ</a>
                                <?php
                                if (!empty($_SESSION["booking_cart"]) && !$has_room) {
                                    ?>
                                    <div class="mt-4 flex justify-center"></div>
                                    <a href="?page=cart" class="btn btn-primary">ไปหน้าตะกร้าของคุณ</a>
                                </div>
                                <?php
                                } else {
                                    ?>
                                <button type="submit" class="btn btn-primary gap-2">ถัดไป <i data-lucide="arrow-right"
                                        class="size-4"></i></button>
                                <?php
                                }
                                ?>
                        </div>
                </div>

            <?php elseif ($step === 3): ?>
                <!-- STEP 3: เลือกบริการเสริม -->
                <div>
                    <h2 class="text-xl font-bold text-primary mb-4">เลือกบริการเสริม (Optional)</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <?php foreach ($services as $sv): ?>
                            <label class="flex items-center gap-3 bg-base-200 px-4 py-3 rounded-xl cursor-pointer">
                                <input type="checkbox" name="service_ids[]" value="<?php echo (int) $sv['id']; ?>"
                                    class="checkbox checkbox-primary" <?php if (in_array($sv['id'], $selected_services))
                                        echo 'checked'; ?>>
                                <div>
                                    <div class="font-medium text-base-content">
                                        <?php echo htmlspecialchars($sv['name']); ?>
                                    </div>
                                    <div class="text-xs text-base-content/60">
                                        <?php echo htmlspecialchars($sv['description']); ?>
                                    </div>
                                </div>
                                <div class="ml-auto text-primary font-semibold whitespace-nowrap">
                                    +฿<?php echo number_format($sv['price']); ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-8 flex justify-between">
                        <a href="?page=booking&step=2" class="btn btn-outline gap-2"><i data-lucide="arrow-left"
                                class="size-4"></i> ย้อนกลับ</a>
                        <button type="submit" class="btn btn-primary gap-2">ถัดไป <i data-lucide="arrow-right"
                                class="size-4"></i></button>
                    </div>
                </div>

            <?php elseif ($step === 4): ?>
                <!-- STEP 4: สรุปและยืนยัน -->
                <div>
                    <h2 class="text-xl font-bold text-primary mb-4">สรุปรายการจอง</h2>
                    <div class="mb-6">
                        <div class="mb-2"><span class="font-medium text-base-content">วันที่เข้าพัก:</span> <span
                                class="text-base-content/70"><?php echo $check_in_date; ?> -
                                <?php echo $check_out_date; ?></span></div>
                        <div class="mb-2"><span class="font-medium text-base-content">ประเภทห้อง:</span> <span
                                class="text-base-content/70"><?php foreach ($room_types as $rt) {
                                    if ($rt['id'] == $selected_room_type) {
                                        echo htmlspecialchars($rt['name']);
                                        break;
                                    }
                                } ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="font-medium text-base-content">สัตว์เลี้ยง:</span>
                            <span class="text-base-content/70"><?php $p_names = [];
                            foreach ($pets as $p) {
                                if (in_array($p['id'], $selected_pets))
                                    $p_names[] = $p['name'];
                            }
                            echo implode(', ', $p_names); ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="font-medium text-base-content">บริการเสริม:</span>
                            <span class="text-base-content/70"><?php $s_names = [];
                            foreach ($services as $s) {
                                if (in_array($s['id'], $selected_services))
                                    $s_names[] = $s['name'];
                            }
                            echo $s_names ? implode(', ', $s_names) : '-'; ?></span>
                        </div>
                        <div class="mt-4 p-4 bg-base-200 rounded-xl flex items-center justify-between">
                            <span class="font-bold text-lg text-primary">ราคารวมเบื้องต้น</span>
                            <span
                                class="font-bold text-xl text-primary">฿<?php echo number_format(estimate_total($room_types, $selected_room_type, $check_in_date, $check_out_date, $services, $selected_services)); ?></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <a href="?page=booking&step=3" class="btn btn-outline gap-2 w-full md:w-auto">
                                <i data-lucide="arrow-left" class="size-4"></i> ย้อนกลับ
                            </a>
                        </div>
                        <div class="flex w-full justify-between gap-2">
                            <button type="submit" name="add_another" value="1" class="btn btn-outline gap-2"><i
                                    data-lucide="plus" class="size-4"></i>
                                จองห้องอื่นเพิ่ม</button>
                            <button type="submit" name="confirm" value="1" class="btn btn-primary gap-2">ไปที่หน้าตะกร้า <i
                                    data-lucide="arrow-right" class="size-4"></i></button>
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
    // จำกัดจำนวน checkbox สัตว์เลี้ยงตาม max_pets ของห้อง และจำนวนห้องที่เลือกในตะกร้า
    document.addEventListener('DOMContentLoaded', function () {
        const petCheckboxes = document.querySelectorAll('input[type="checkbox"][name="pet_ids[]"]');
        const roomRadios = document.querySelectorAll('input[type="radio"][name="room_type_id"]');
        function updatePetLimit() {
            let max = 1;
            const checkedRoom = document.querySelector('input[type="radio"][name="room_type_id"]:checked');
            if (checkedRoom) {
                const card = checkedRoom.closest('.card');
                if (card) {
                    const txt = card.innerText;
                    const match = txt.match(/สูงสุด\s(\d+)\sตัว/);
                    if (match) max = parseInt(match[1]);
                }
            }
            let checkedCount = 0;
            petCheckboxes.forEach(cb => { if (cb.checked) checkedCount++; });
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