<?php
// ═══════════════════════════════════════════════════════════
// DAILY UPDATES (DIGITAL REPORT CARD) UI - VET4 HOTEL
// Allows staff to send pictures and updates of pets to customers
// ═══════════════════════════════════════════════════════════

// Fetch Types for the modal dropdown
$stmt = $pdo->query("SELECT * FROM daily_update_types WHERE is_active = 1");
$update_types = $stmt->fetchAll();

// Date Filter
$filter_date = $_GET['date'] ?? date('Y-m-d');

// 1. Fetch Active Pets (Pets currently Checked-In OR checking out today)
$query = "SELECT 
            bip.pet_id,
            bip.booking_item_id,
            p.name as pet_name,
            s.name as species_name,
            b.name as breed_name,
            c.first_name as customer_name,
            bk.booking_ref,
            rm.room_number
          FROM booking_item_pets bip
          JOIN booking_items bi ON bip.booking_item_id = bi.id
          JOIN bookings bk ON bi.booking_id = bk.id
          JOIN pets p ON bip.pet_id = p.id
          JOIN species s ON p.species_id = s.id
          LEFT JOIN breeds b ON p.breed_id = b.id
          JOIN customers c ON bk.customer_id = c.id
          JOIN rooms rm ON bi.room_id = rm.id
          WHERE bk.status IN ('checked_in', 'checked_out')
          AND bi.check_in_date <= :filter_date
          AND bi.check_out_date >= :filter_date
          ORDER BY rm.room_number ASC";

$stmt = $pdo->prepare($query);
$stmt->execute(['filter_date' => $filter_date]);
$active_pets = $stmt->fetchAll();

// 2. Fetch Todays Updates
$query_updates = "SELECT 
                    du.*,
                    p.name as pet_name,
                    rm.room_number,
                    dt.name as type_name,
                    dt.icon_class,
                    e.first_name as emp_name
                  FROM daily_updates du
                  JOIN pets p ON du.pet_id = p.id
                  JOIN booking_items bi ON du.booking_item_id = bi.id
                  JOIN rooms rm ON bi.room_id = rm.id
                  JOIN daily_update_types dt ON du.update_type_id = dt.id
                  JOIN employees e ON du.employee_id = e.id
                  WHERE DATE(du.created_at) = :filter_date
                  ORDER BY du.created_at DESC";

$stmt_updates = $pdo->prepare($query_updates);
$stmt_updates->execute(['filter_date' => $filter_date]);
$todays_updates = $stmt_updates->fetchAll();

// Helper to group updates by Pet
$updates_by_pet = [];
foreach ($todays_updates as $upd) {
    if (!isset($updates_by_pet[$upd['pet_id']])) {
        $updates_by_pet[$upd['pet_id']] = [];
    }
    $updates_by_pet[$upd['pet_id']][] = $upd;
}

?>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="camera" class="size-5 text-primary"></i>
                </div>
                สมุดพกสัตว์เลี้ยงประจำวัน
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">ถ่ายรูปและอัปเดตสถานะน้องๆ ส่งตรงให้เจ้าของ</p>
        </div>

        <form method="GET" action="index.php" class="flex gap-2">
            <input type="hidden" name="page" value="daily_updates">
            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>"
                class="input input-bordered input-sm">
            <button type="submit" class="btn btn-sm btn-primary">ดูข้อมูล</button>
        </form>
    </div>

    <!-- Active Pets List -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="p-4 border-b border-base-200 bg-base-100/50 rounded-t-2xl">
            <h2 class="card-title text-base font-bold">สัตว์เลี้ยงที่เข้าพักวันนี้ (
                <?php echo count($active_pets); ?> ตัว)
            </h2>
        </div>

        <?php if (empty($active_pets)): ?>
            <div class="p-8 text-center text-base-content/50">
                <i data-lucide="cat" class="size-12 mx-auto mb-3 opacity-20"></i>
                ไม่มีสัตว์เลี้ยงเข้าพักในวันที่เลือก
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
                <?php foreach ($active_pets as $pet): ?>
                    <?php $pet_updates = $updates_by_pet[$pet['pet_id']] ?? []; ?>

                    <div class="border border-base-200 rounded-xl p-4 flex flex-col hover:border-primary/30 transition-colors">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center justify-center gap-3">
                                <div class="avatar placeholder">
                                    <div
                                        class="bg-primary/10 text-primary rounded-full w-12 h-12 flex items-center justify-center">
                                        <i data-lucide="paw-print" class="size-6"></i>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg leading-tight">
                                        <?php echo htmlspecialchars($pet['pet_name']); ?>
                                    </h3>
                                    <p class="text-xs text-base-content/60">ห้อง
                                        <?php echo htmlspecialchars($pet['room_number']); ?> •
                                        <?php echo htmlspecialchars($pet['species_name']); ?>
                                    </p>
                                </div>
                            </div>
                            <span
                                class="badge <?php echo count($pet_updates) > 0 ? 'badge-success badge-outline' : 'badge-ghost'; ?> badge-sm">
                                อัปเดตแล้ว
                                <?php echo count($pet_updates); ?> ครั้ง
                            </span>
                        </div>

                        <!-- Mini Timeline of todays updates -->
                        <?php if (count($pet_updates) > 0): ?>
                            <div class="bg-base-200/30 rounded-lg p-2 mb-3 flex-1">
                                <div class="flex flex-col gap-2 max-h-32 overflow-y-auto pr-1 text-sm">
                                    <?php foreach (array_slice($pet_updates, 0, 3) as $upd): ?>
                                        <div class="flex items-start gap-2">
                                            <i data-lucide="<?php echo $upd['icon_class'] ?? 'info'; ?>"
                                                class="size-4 shrink-0 text-primary mt-0.5"></i>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="font-medium text-base-content/80">
                                                        <?php echo htmlspecialchars($upd['type_name']); ?>
                                                    </span>
                                                    <span class="text-base-content/40">
                                                        <?php echo date('H:i', strtotime($upd['created_at'])); ?>
                                                    </span>
                                                </div>
                                                <p class="text-xs text-base-content/60 truncate">
                                                    <?php echo htmlspecialchars($upd['message']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($pet_updates) > 3): ?>
                                        <div class="text-xs text-center text-primary/70 mt-1 cursor-pointer">ดูทั้งหมด...</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div
                                class="flex-1 flex justify-center items-center py-4 bg-base-200/20 rounded-lg mb-3 border border-dashed border-base-200">
                                <span class="text-xs text-base-content/40">ยังไม่มีอัปเดตวันนี้</span>
                            </div>
                        <?php endif; ?>

                        <div class="mt-auto">
                            <button
                                onclick="openUpdateModal(<?php echo $pet['booking_item_id']; ?>, <?php echo $pet['pet_id']; ?>, '<?php echo htmlspecialchars(addslashes($pet['pet_name'])); ?>')"
                                class="btn btn-sm btn-primary w-full gap-2">
                                <i data-lucide="plus" class="size-4"></i> เพิ่มอัปเดต
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- All Updates Timeline Feed -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="p-4 border-b border-base-200 bg-base-100/50 rounded-t-2xl">
            <h2 class="card-title text-base font-bold">ฟีดความเคลื่อนไหวล่าสุด</h2>
        </div>
        <div class="p-4">
            <?php if (empty($todays_updates)): ?>
                <div class="text-center text-base-content/40 py-8">ไม่มีความเคลื่อนไหวในวันนี้</div>
            <?php else: ?>
                <ul class="timeline timeline-vertical max-w-2xl mx-auto">
                    <?php foreach ($todays_updates as $i => $upd): ?>
                        <li>
                            <?php if ($i > 0): ?>
                                <hr class="bg-primary/20" />
                            <?php endif; ?>
                            <div class="timeline-start text-xs text-base-content/50 pt-1">
                                <?php echo date('H:i', strtotime($upd['created_at'])); ?>
                            </div>
                            <div class="timeline-middle">
                                <div
                                    class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary ring-4 ring-base-100">
                                    <i data-lucide="<?php echo $upd['icon_class'] ?? 'info'; ?>" class="size-4"></i>
                                </div>
                            </div>
                            <div class="timeline-end mb-6 w-full ml-4">
                                <div class="card bg-base-200/30 border border-base-200 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-bold text-sm">
                                                    <?php echo htmlspecialchars($upd['pet_name']); ?> <span
                                                        class="text-xs font-normal text-base-content/60 ml-1">ห้อง
                                                        <?php echo htmlspecialchars($upd['room_number']); ?>
                                                    </span>
                                                </h4>
                                                <span class="badge badge-sm badge-primary badge-outline mt-1 mb-2">
                                                    <?php echo htmlspecialchars($upd['type_name']); ?>
                                                </span>
                                            </div>
                                            <form method="POST" action="?action=daily_updates"
                                                onsubmit="return confirm('ยืนยันการลบอัปเดตนี้?');">
                                                <input type="hidden" name="sub_action" value="delete_update">
                                                <input type="hidden" name="update_id" value="<?php echo $upd['id']; ?>">
                                                <input type="hidden" name="redirect_to"
                                                    value="?page=daily_updates&date=<?php echo $filter_date; ?>">
                                                <button type="submit"
                                                    class="btn btn-xs btn-ghost text-error/50 hover:text-error hover:bg-error/10"><i
                                                        data-lucide="trash-2" class="size-3.5"></i></button>
                                            </form>
                                        </div>
                                        <p class="text-sm">
                                            <?php echo nl2br(htmlspecialchars($upd['message'])); ?>
                                        </p>

                                        <?php if ($upd['image_url']): ?>
                                            <div class="mt-3 rounded-xl overflow-hidden border border-base-200 max-w-sm">
                                                <img src="../<?php echo htmlspecialchars($upd['image_url']); ?>" alt="Pet Update"
                                                    class="w-full h-auto object-cover max-h-64 cursor-pointer hover:opacity-90 transition-opacity"
                                                    onclick="window.open(this.src, '_blank')">
                                            </div>
                                        <?php endif; ?>

                                        <div class="text-[10px] text-base-content/40 mt-3 flex items-center gap-1">
                                            <i data-lucide="user" class="size-3"></i> อัปเดตโดย:
                                            <?php echo htmlspecialchars($upd['emp_name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($i < count($todays_updates) - 1): ?>
                                <hr class="bg-primary/20" />
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Update Modal -->
<dialog id="modal_add_update" class="modal">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <i data-lucide="pen-line" class="size-5 text-primary"></i>
            เพิ่มอัปเดตสำหรับ: <span id="modal_pet_name" class="text-primary"></span>
        </h3>

        <form method="POST" action="?action=daily_updates" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="sub_action" value="add_update">
            <input type="hidden" name="booking_item_id" id="modal_booking_item_id">
            <input type="hidden" name="pet_id" id="modal_pet_id">
            <input type="hidden" name="redirect_to" value="?page=daily_updates&date=<?php echo $filter_date; ?>">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ประเภทการอัปเดต</span></label>
                <select name="update_type_id" class="select select-bordered" required>
                    <option value="" disabled selected>-- เลือกประเภท --</option>
                    <?php foreach ($update_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>">
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ข้อความอัปเดต</span></label>
                <textarea name="message" class="textarea textarea-bordered h-24"
                    placeholder="พิมพ์ข้อความที่ต้องการแจ้งเจ้าของน้องๆ..." required></textarea>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">แนบรูปภาพ (ถ้ามี)</span></label>
                <input type="file" name="update_image" accept="image/jpeg, image/png, image/webp"
                    class="file-input file-input-bordered file-input-primary w-full" />
                <label class="label"><span class="label-text-alt text-base-content/50">ขนาดไม่เกิน 5MB (JPG, PNG,
                        WEBP)</span></label>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">บันทึกและส่งข้อมูล</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    function openUpdateModal(bookingItemId, petId, petName) {
        document.getElementById('modal_booking_item_id').value = bookingItemId;
        document.getElementById('modal_pet_id').value = petId;
        document.getElementById('modal_pet_name').textContent = petName;
        document.getElementById('modal_add_update').showModal();
    }
</script>