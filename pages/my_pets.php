<?php
// ═══════════════════════════════════════════════════════════
// MY PETS PAGE — VET4 HOTEL (Mobile First + DaisyUI modal-bottom)
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login");
    exit();
}

$current_customer_id = $_SESSION['customer_id'];

// ดึง Master Data สำหรับ Modal
$species_list = $pdo->query("SELECT * FROM species ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$breeds_list = $pdo->query("SELECT * FROM breeds ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$pets = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            s.name AS species_name, 
            b.name AS breed_name,
            (SELECT MIN(expiry_date) FROM pet_vaccinations WHERE pet_id = p.id AND expiry_date >= CURDATE()) AS next_vaccine_date
        FROM pets p
        INNER JOIN species s ON p.species_id = s.id
        LEFT JOIN breeds b ON p.breed_id = b.id
        WHERE p.customer_id = ? AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$current_customer_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pets = [];
}

// ฟังก์ชันช่วยคำนวณอายุ
function calculateAge($dob)
{
    if (!$dob)
        return "ไม่ระบุ";
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $y = $today->diff($birthDate)->y;
    $m = $today->diff($birthDate)->m;
    if ($y > 0)
        return "$y ปี $m เดือน";
    return "$m เดือน";
}
?>

<?php
// ดึง vaccine types สำหรับ modal (พร้อม species_id)
$vaccine_types = $pdo->query("SELECT * FROM vaccine_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// ดึงประวัติวัคซีนของสัตว์เลี้ยงทั้งหมดในครั้งเดียว
$vaccine_records = [];
if (!empty($pets)) {
    $pet_ids = array_column($pets, 'id');
    $placeholders = implode(',', array_fill(0, count($pet_ids), '?'));
    try {
        $vStmt = $pdo->prepare("
            SELECT pv.*, vt.name AS vaccine_name
            FROM pet_vaccinations pv
            JOIN vaccine_types vt ON pv.vaccine_type_id = vt.id
            WHERE pv.pet_id IN ($placeholders)
            ORDER BY pv.expiry_date DESC
        ");
        $vStmt->execute($pet_ids);
        foreach ($vStmt->fetchAll(PDO::FETCH_ASSOC) as $rec) {
            $vaccine_records[$rec['pet_id']][] = $rec;
        }
    } catch (PDOException $e) {
        $vaccine_records = [];
    }
}
?>

<section class="relative min-h-[80vh] bg-base-100 overflow-hidden">
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        <div class="floating-paw absolute top-[8%] left-[6%] opacity-20 text-primary" style="animation-delay: 0.5s;">
            <i data-lucide="paw-print" class="size-16"></i>
        </div>
        <div class="floating-paw absolute bottom-[2%] right-[12%] opacity-15 text-secondary"
            style="animation-delay: 1.2s;">
            <i data-lucide="dog" class="size-20"></i>
        </div>
        <div class="floating-paw absolute top-[15%] right-[6%] opacity-10 text-accent" style="animation-delay: 2s;">
            <i data-lucide="cat" class="size-20"></i>
        </div>
        <div class="floating-paw absolute bottom-[8%] left-[30%] opacity-10 text-primary"
            style="animation-delay: 2.5s;">
            <i data-lucide="heart" class="size-14"></i>
        </div>
    </div>

    <div class="container mx-auto px-4 lg:px-8 relative z-10 py-12 w-full">
        <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
            <div class="flex items-center gap-3 mb-10">
                <div
                    class="bg-primary text-primary-content rounded-full w-12 h-12 flex items-center justify-center shadow-lg">
                    <i data-lucide="paw-print" class="size-7"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-base-content">สัตว์เลี้ยงของฉัน</h1>
            </div>

            <button onclick="document.getElementById('add_pet_modal').checked = true"
                class="btn btn-primary btn-lg gap-2 shadow-lg shadow-primary/20">
                <i data-lucide="plus-circle" class="size-6"></i>
                เพิ่มสัตว์เลี้ยงใหม่
            </button>
        </div>

        <?php if (!empty($pets)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($pets as $pet): ?>
                    <!-- Card -->
                    <div
                        class="card bg-base-100 shadow-xl border border-base-200 hover:shadow-2xl transition-all duration-300 group overflow-hidden">
                        <div
                            class="h-48 bg-linear-to-br from-primary/5 to-primary/20 flex items-center justify-center relative">
                            <?php if ($pet['is_aggressive']): ?>
                                <div class="absolute top-4 left-4 badge badge-error gap-1 p-3 shadow-md">
                                    <i data-lucide="alert-triangle" class="size-3"></i> ดุ/กัด
                                </div>
                            <?php endif; ?>
                            <div
                                class="p-8 bg-base-100 rounded-full shadow-inner group-hover:scale-110 transition-transform duration-500">
                                <?php if ($pet['species_id'] == 1): ?>
                                    <i data-lucide="dog" class="size-16 text-primary"></i>
                                <?php elseif ($pet['species_id'] == 2): ?>
                                    <i data-lucide="cat" class="size-16 text-secondary"></i>
                                <?php else: ?>
                                    <i data-lucide="paw-print" class="size-16 text-accent"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-6">
                            <div class="flex justify-between items-start mb-2">
                                <h2 class="card-title text-2xl font-bold text-base-content">
                                    <?php echo htmlspecialchars($pet['name']); ?>
                                </h2>
                                <div class="badge badge-outline opacity-70">
                                    <?php echo htmlspecialchars($pet['species_name']); ?>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 my-4">
                                <div class="flex items-center gap-2 text-sm text-base-content/70">
                                    <i data-lucide="info" class="size-4 text-primary"></i>
                                    <span><?php echo htmlspecialchars($pet['breed_name'] ?? 'ไม่ระบุสายพันธุ์'); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-base-content/70">
                                    <i data-lucide="calendar" class="size-4 text-primary"></i>
                                    <span>อายุ <?php echo calculateAge($pet['dob']); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-base-content/70">
                                    <i data-lucide="weight" class="size-4 text-primary"></i>
                                    <span><?php echo number_format($pet['weight_kg'], 1); ?> กก.</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-base-content/70">
                                    <?php if ($pet['gender'] == 'male'): ?>
                                        <i data-lucide="mars" class="size-4 text-blue-500"></i> ตัวผู้
                                    <?php else: ?>
                                        <i data-lucide="venus" class="size-4 text-pink-500"></i> ตัวเมีย
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Vaccine info -->
                            <?php if ($pet['next_vaccine_date']): ?>
                                <div
                                    class="alert alert-sm bg-success/10 border-success/20 text-success-content flex gap-2 py-2 mb-2">
                                    <i data-lucide="check-circle" class="size-4"></i>
                                    <span class="text-xs font-medium">วัคซีนครบถ้วน (หมดอายุ:
                                        <?php echo htmlspecialchars($pet['next_vaccine_date']); ?>)</span>
                                </div>
                            <?php else: ?>
                                <div
                                    class="alert alert-sm bg-warning/10 border-warning/20 text-warning-content flex gap-2 py-2 mb-2">
                                    <i data-lucide="alert-circle" class="size-4"></i>
                                    <span class="text-xs font-medium">ยังไม่เพิ่มข้อมูลวัคซีน</span>
                                </div>
                            <?php endif; ?>
                            <hr class="border-base-200 my-2">
                            <div class="card-actions justify-end mt-4">
                                <button
                                    onclick="document.getElementById('vaccine_modal_<?php echo $pet['id']; ?>').checked = true"
                                    class="btn btn-secondary btn-outline btn-sm gap-2">
                                    <i data-lucide="syringe" class="size-4"></i> วัคซีน
                                </button>
                                <button onclick="openEditPetModal(<?php echo $pet['id']; ?>)"
                                    class="btn btn-outline btn-primary btn-sm gap-2">
                                    <i data-lucide="edit-3" class="size-4"></i> แก้ไข
                                </button>
                                <button
                                    onclick="document.getElementById('pet_details_modal_<?php echo $pet['id']; ?>').checked = true"
                                    class="btn btn-ghost btn-sm gap-2">
                                    <i data-lucide="eye" class="size-4"></i> รายละเอียด
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Modal (Mobile Bottom / Desktop Middle, styled like navbar modal) -->
                    <input type="checkbox" id="pet_details_modal_<?php echo $pet['id']; ?>" class="modal-toggle" />
                    <div class="modal modal-bottom sm:modal-middle">
                        <div class="modal-box rounded-t-3xl rounded-b-none max-h-[85vh] sm:rounded-2xl p-0 max-w-lg">
                            <!-- Drag handle indicator (mobile only) -->
                            <div class="flex justify-center pt-3 pb-2 sm:hidden">
                                <div class="w-12 h-1.5 bg-base-300 rounded-full"></div>
                            </div>
                            <div
                                class="px-5 sm:px-6 py-4 border-b border-base-200 sticky top-0 bg-base-100 z-20 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="bg-primary/10 text-primary rounded-full p-2">
                                        <i data-lucide="eye" class="size-5"></i>
                                    </div>
                                    <div class="font-bold text-base-content">ข้อมูลสัตว์เลี้ยง</div>
                                </div>
                                <label for="vaccine_modal_<?php echo $pet['id']; ?>" class="btn btn-ghost btn-sm btn-circle">
                                    <i data-lucide="x" class="size-4"></i>
                                </label>
                            </div>
                            <div class="px-5 sm:px-6 py-4">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="avatar">
                                        <div
                                            class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden">
                                            <?php if ($pet['species_id'] == 1): ?>
                                                <i data-lucide="dog" class="size-10 text-primary"></i>
                                            <?php elseif ($pet['species_id'] == 2): ?>
                                                <i data-lucide="cat" class="size-10 text-secondary"></i>
                                            <?php else: ?>
                                                <i data-lucide="paw-print" class="size-10 text-accent"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xl font-bold text-base-content mb-1">
                                            <?php echo htmlspecialchars($pet['name']); ?>
                                        </div>
                                        <div class="badge badge-primary badge-lg">
                                            <?php echo htmlspecialchars($pet['species_name']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3 mb-4">
                                    <div class="flex items-center gap-2 text-base-content/70">
                                        <i data-lucide="info" class="size-4"></i>
                                        <span>สายพันธุ์: <?php echo htmlspecialchars($pet['breed_name'] ?? 'ไม่ระบุ'); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-base-content/70">
                                        <i data-lucide="calendar" class="size-4"></i>
                                        <span>วันเกิด:
                                            <?php echo $pet['dob'] ? htmlspecialchars($pet['dob']) : 'ไม่ระบุ'; ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-base-content/70">
                                        <i data-lucide="weight" class="size-4"></i>
                                        <span>น้ำหนัก: <?php echo number_format($pet['weight_kg'], 1); ?> กก.</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-base-content/70">
                                        <?php if ($pet['gender'] == 'male'): ?>
                                            <i data-lucide="mars" class="size-4 text-blue-500"></i> ตัวผู้
                                        <?php else: ?>
                                            <i data-lucide="venus" class="size-4 text-pink-500"></i> ตัวเมีย
                                        <?php endif; ?>
                                    </div>
                                    <!-- Vaccine info -->
                                    <?php if ($pet['next_vaccine_date']): ?>
                                        <div
                                            class="alert alert-sm bg-success/10 border-success/20 text-success-content flex gap-2 py-2 mb-2">
                                            <i data-lucide="check-circle" class="size-4"></i>
                                            <span class="text-xs font-medium">วัคซีนครบถ้วน (หมดอายุ:
                                                <?php echo htmlspecialchars($pet['next_vaccine_date']); ?>)</span>
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="alert alert-sm bg-warning/10 border-warning/20 text-warning-content flex gap-2 py-2 mb-2">
                                            <i data-lucide="alert-circle" class="size-4"></i>
                                            <span class="text-xs font-medium">ยังไม่เพิ่มข้อมูลวัคซีน</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($pet['behavior_note']): ?>
                                        <div class="flex items-center gap-2 text-base-content/70">
                                            <i data-lucide="alert-circle" class="size-4 text-warning"></i>
                                            <span>หมายเหตุ: <?php echo htmlspecialchars($pet['behavior_note']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($pet['is_aggressive']): ?>
                                        <div class="flex items-center gap-2 text-error font-semibold">
                                            <i data-lucide="alert-triangle" class="size-4"></i>
                                            <span>พฤติกรรมดุร้าย / กัด</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="divider my-4"></div>
                                <div class="flex justify-between gap-2">
                                    <label for="delete_pet_modal_<?php echo $pet['id']; ?>"
                                        class="btn btn-error btn-outline btn-sm gap-1 hover:text-white">
                                        <i data-lucide="trash-2" class="size-4"></i> ลบ
                                    </label>
                                    <label for="pet_details_modal_<?php echo $pet['id']; ?>" class="btn w-auto">ปิด</label>
                                </div>
                            </div>
                        </div>
                        <label class="modal-backdrop" for="pet_details_modal_<?php echo $pet['id']; ?>"></label>
                    </div>

                    <!-- ═══════════════════════════════════════════ -->
                    <!-- Delete Confirm Modal ─ per pet             -->
                    <!-- ═══════════════════════════════════════════ -->
                    <input type="checkbox" id="delete_pet_modal_<?php echo $pet['id']; ?>" class="modal-toggle" />
                    <div class="modal modal-middle">
                        <div class="modal-box rounded-t-3xl sm:rounded-2xl lg:max-w-sm text-center">
                            <div
                                class="bg-error/10 text-error rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="alert-triangle" class="size-8"></i>
                            </div>
                            <h3 class="font-bold text-lg text-base-content mb-2">ยืนยันการลบ</h3>
                            <p class="text-base-content/60 text-sm mb-1">
                                คุณต้องการลบข้อมูลของ
                                <span
                                    class="font-semibold text-base-content"><?php echo htmlspecialchars($pet['name']); ?></span>
                                หรือไม่?
                            </p>
                            <p class="text-xs text-base-content/40 mb-6">ข้อมูลจะถูกซ่อนจากระบบ
                                สามารถกู้คืนได้โดยติดต่อเจ้าหน้าที่</p>
                            <div class="flex gap-3 justify-center">
                                <label for="delete_pet_modal_<?php echo $pet['id']; ?>" class="btn btn-ghost">ยกเลิก</label>
                                <form method="POST" action="?action=pet">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                    <button type="submit" class="btn btn-error text-white gap-1">
                                        <i data-lucide="trash-2" class="size-4"></i> ลบข้อมูล
                                    </button>
                                </form>
                            </div>
                        </div>
                        <label class="modal-backdrop" for="delete_pet_modal_<?php echo $pet['id']; ?>"></label>
                    </div>

                    <!-- ═══════════════════════════════════════════ -->
                    <!-- Vaccine Modal ─ per pet                     -->
                    <!-- ═══════════════════════════════════════════ -->
                    <input type="checkbox" id="vaccine_modal_<?php echo $pet['id']; ?>" class="modal-toggle" />
                    <div class="modal modal-bottom sm:modal-middle">
                        <div
                            class="modal-box rounded-t-3xl rounded-b-none max-h-[90vh] p-0 sm:rounded-2xl flex flex-col max-w-lg">
                            <!-- Drag handle (mobile) -->
                            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                                <div class="w-12 h-1.5 bg-base-300 rounded-full"></div>
                            </div>
                            <!-- Header -->
                            <div
                                class="px-5 sm:px-6 py-4 border-b border-base-200 sticky top-0 bg-base-100 z-20 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="bg-secondary/10 text-secondary rounded-full p-2">
                                        <i data-lucide="syringe" class="size-5"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-base-content">บันทึกวัคซีน</div>
                                        <div class="text-xs text-base-content/50"><?php echo htmlspecialchars($pet['name']); ?>
                                        </div>
                                    </div>
                                </div>
                                <label for="vaccine_modal_<?php echo $pet['id']; ?>" class="btn btn-ghost btn-sm btn-circle">
                                    <i data-lucide="x" class="size-4"></i>
                                </label>
                            </div>
                            <!-- Scrollable body -->
                            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-4 space-y-6">

                                <!-- ── Existing Records ─────────────────────── -->
                                <div>
                                    <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3">
                                        ประวัติวัคซีน</h3>
                                    <?php $records = $vaccine_records[$pet['id']] ?? []; ?>
                                    <?php if (!empty($records)): ?>
                                        <div class="space-y-2">
                                            <?php foreach ($records as $rec): ?>
                                                <?php
                                                $isExpired = strtotime($rec['expiry_date']) < time();
                                                $rowClass = $isExpired ? 'border-error/30 bg-error/5' : 'border-success/30 bg-success/5';
                                                $badgeClass = $isExpired ? 'badge-error' : 'badge-success';
                                                $badgeText = $isExpired ? 'หมดอายุแล้ว' : 'ยังมีผล';
                                                $expiryFmt = date('d/m/Y', strtotime($rec['expiry_date']));
                                                $adminFmt = $rec['administered_date'] ? date('d/m/Y', strtotime($rec['administered_date'])) : '-';
                                                ?>
                                                <div
                                                    class="flex items-start justify-between gap-3 rounded-xl border p-3 <?php echo $rowClass; ?>">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <i data-lucide="shield-check"
                                                            class="size-4 shrink-0 <?php echo $isExpired ? 'text-error' : 'text-success'; ?>"></i>
                                                        <div class="min-w-0">
                                                            <div class="font-medium text-sm text-base-content truncate">
                                                                <?php echo htmlspecialchars($rec['vaccine_name']); ?>
                                                            </div>
                                                            <div class="text-xs text-base-content/50">ฉีด: <?php echo $adminFmt; ?>
                                                                &nbsp;|&nbsp; หมดอายุ: <?php echo $expiryFmt; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="shrink-0 flex flex-col items-end gap-1">
                                                        <span
                                                            class="badge <?php echo $badgeClass; ?> badge-sm"><?php echo $badgeText; ?></span>
                                                        <?php if ($rec['is_verified']): ?>
                                                            <span class="badge badge-ghost badge-sm gap-1"><i data-lucide="check"
                                                                    class="size-3"></i>ยืนยันแล้ว</span>
                                                        <?php endif; ?>
                                                        <form method="POST" action="?action=vaccine"
                                                            onsubmit="return confirm('คุณต้องการลบวัคซีนนี้หรือไม่?');" class="inline">
                                                            <input type="hidden" name="vaccine_action" value="delete">
                                                            <input type="hidden" name="vaccine_id" value="<?php echo $rec['id']; ?>">
                                                            <button type="submit"
                                                                class="btn btn-ghost btn-xs text-error hover:bg-error/10"
                                                                title="ลบวัคซีน">
                                                                <i data-lucide="trash-2" class="size-3.5"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="text-center py-6 rounded-xl bg-base-200/60 border border-dashed border-base-300">
                                            <i data-lucide="shield-off" class="size-8 text-base-content/30 mx-auto mb-2"></i>
                                            <p class="text-sm text-base-content/50">ยังไม่มีบันทึกวัคซีน</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- ── Add New Vaccine Form ─────────────────── -->
                                <div>
                                    <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3">
                                        เพิ่มวัคซีนใหม่</h3>
                                    <form method="POST" action="?action=vaccine" class="space-y-4">
                                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">

                                        <div class="form-control">
                                            <label class="label pb-1" for="vaccine_type_id_<?php echo $pet['id']; ?>">
                                                <span class="label-text font-medium">ประเภทวัคซีน <span
                                                        class="text-error">*</span></span>
                                            </label>
                                            <select name="vaccine_type_id" id="vaccine_type_id_<?php echo $pet['id']; ?>"
                                                class="select select-bordered w-full" required>
                                                <option value="">-- เลือกประเภทวัคซีน --</option>
                                                <?php foreach ($vaccine_types as $vt): ?>
                                                    <?php if ($vt['species_id'] == $pet['species_id']): ?>
                                                        <option value="<?php echo $vt['id']; ?>">
                                                            <?php echo htmlspecialchars($vt['name']); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="form-control">
                                                <label class="label pb-1" for="administered_date_<?php echo $pet['id']; ?>">
                                                    <span class="label-text font-medium">วันที่ฉีด</span>
                                                </label>
                                                <input type="date" name="administered_date"
                                                    id="administered_date_<?php echo $pet['id']; ?>"
                                                    class="input input-bordered w-full" max="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="form-control">
                                                <label class="label pb-1" for="expiry_date_<?php echo $pet['id']; ?>">
                                                    <span class="label-text font-medium">วันหมดอายุ <span
                                                            class="text-error">*</span></span>
                                                </label>
                                                <input type="date" name="expiry_date" id="expiry_date_<?php echo $pet['id']; ?>"
                                                    class="input input-bordered w-full" required
                                                    min="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                                            <label for="vaccine_modal_<?php echo $pet['id']; ?>"
                                                class="btn btn-ghost">ยกเลิก</label>
                                            <button type="submit" class="btn btn-secondary gap-2">
                                                <i data-lucide="plus-circle" class="size-4"></i>
                                                บันทึกวัคซีน
                                            </button>
                                        </div>
                                    </form>
                                </div>

                            </div><!-- /scrollable body -->
                        </div>
                        <label class="modal-backdrop" for="vaccine_modal_<?php echo $pet['id']; ?>"></label>
                    </div><!-- /vaccine modal -->

                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20 bg-base-200/50 rounded-3xl border-2 border-dashed border-base-300">
                <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="paw-print" class="size-12 text-primary/40"></i>
                </div>
                <h3 class="text-2xl font-bold text-base-content mb-2">ยังไม่มีข้อมูลสัตว์เลี้ยง</h3>
                <p class="text-base-content/60 max-w-md mx-auto mb-8">
                    เพิ่มข้อมูลสัตว์เลี้ยงของคุณตอนนี้ เพื่อเริ่มจองห้องพักและรับการอัปเดตประจำวันจากเรา
                </p>
                <a href="?page=add_pet" class="btn btn-primary px-8">
                    <i data-lucide="plus" class="size-5"></i>
                    เพิ่มสัตว์เลี้ยงตัวแรก
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal: Add Pet (Mobile Bottom / Desktop Middle, styled like navbar modal, header fixed top, actions fixed bottom) -->
<input type="checkbox" id="add_pet_modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
    <div
        class="modal-box rounded-t-3xl rounded-b-none max-h-[85vh] md:max-h-screen p-0 sm:rounded-2xl flex flex-col lg:w-11/12 max-w-5xl">
        <!-- Drag handle indicator (mobile only) -->
        <div class="flex justify-center pt-3 pb-2 sm:hidden">
            <div class="w-12 h-1.5 bg-base-300 rounded-full"></div>
        </div>
        <!-- Fixed header -->
        <div class="px-5 sm:px-6 border-b border-base-200 bg-base-100 sticky top-0 z-20">
            <div class="flex items-center justify-between py-4">
                <span class="font-bold text-lg tracking-tight text-primary">เพิ่มสัตว์เลี้ยงใหม่</span>
                <label for="add_pet_modal" class="btn btn-ghost btn-sm btn-circle">
                    <i data-lucide="x" class="size-4"></i>
                </label>
            </div>
        </div>
        <!-- Scrollable form body -->
        <form method="POST" action="?action=pet" autocomplete="off" class="px-5 sm:px-6 py-4 flex-1 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-4">
                    <div class="form-control">
                        <label for="add_name" class="label label-text font-medium">ชื่อสัตว์เลี้ยง <span
                                class="text-error">*</span></label>
                        <input type="text" name="name" id="add_name" autocomplete="off"
                            class="input input-bordered w-full" required>
                    </div>
                    <div class="form-control">
                        <label for="add_species_id" class="label label-text font-medium">ชนิดสัตว์ <span
                                class="text-error">*</span></label>
                        <select name="species_id" id="add_species_id" class="select select-bordered w-full" required
                            onchange="filterBreeds(this.value)">
                            <option value="">-- เลือกชนิดสัตว์ --</option>
                            <?php foreach ($species_list as $sp): ?>
                                <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control">
                        <label for="breed_select" class="label label-text font-medium">สายพันธุ์</label>
                        <select name="breed_id" class="select select-bordered w-full" id="breed_select">
                            <option value="">-- เลือกสายพันธุ์ --</option>
                            <?php foreach ($breeds_list as $br): ?>
                                <option value="<?php echo $br['id']; ?>" data-species="<?php echo $br['species_id']; ?>">
                                    <?php echo htmlspecialchars($br['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control">
                        <label for="add_gender" class="label label-text font-medium">เพศ <span
                                class="text-error">*</span></label>
                        <select name="gender" id="add_gender" class="select select-bordered w-full" required>
                            <option value="male">ตัวผู้</option>
                            <option value="female">ตัวเมีย</option>
                            <option value="spayed">ทำหมันแล้ว (เมีย)</option>
                            <option value="neutered">ทำหมันแล้ว (ผู้)</option>
                            <option value="unknown">ไม่ระบุ</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label for="add_dob" class="label label-text font-medium">วันเกิด</label>
                        <input type="date" name="dob" id="add_dob" class="input input-bordered w-full">
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="form-control">
                        <label for="add_weight_kg" class="label label-text font-medium">น้ำหนัก</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="weight_kg" id="add_weight_kg" class="input input-bordered w-full"
                                min="0" step="0.01" placeholder="น้ำหนัก">
                            <span class="text-base-content/60 text-sm">kg</span>
                        </div>
                    </div>
                    <div class="form-control">
                        <label for="add_vet_name"
                            class="label label-text font-medium">ชื่อคลินิก/สัตวแพทย์ประจำตัว</label>
                        <input type="text" name="vet_name" id="add_vet_name" class="input input-bordered w-full"
                            placeholder="ชื่อคลินิก/สัตวแพทย์">
                    </div>
                    <div class="form-control">
                        <label for="add_vet_phone" class="label label-text font-medium">เบอร์คลินิก/สัตวแพทย์</label>
                        <input type="text" name="vet_phone" id="add_vet_phone" class="input input-bordered w-full"
                            placeholder="เบอร์โทรศัพท์">
                    </div>
                    <div class="form-control">
                        <label for="add_behavior_note" class="label label-text font-medium">หมายเหตุพฤติกรรม</label>
                        <textarea name="behavior_note" id="add_behavior_note" class="textarea textarea-bordered w-full"
                            placeholder="เช่น กลัวฟ้าร้อง, ไม่ชอบอาบน้ำ"></textarea>
                    </div>
                    <div class="form-control">
                        <label for="add_is_aggressive" class="label cursor-pointer flex items-center gap-2">
                            <input type="checkbox" name="is_aggressive" id="add_is_aggressive"
                                class="checkbox checkbox-error text-white" />
                            <span class="text-error font-semibold">ดุ/กัด (แจ้งเตือนพนักงาน)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <label for="add_pet_modal" class="btn btn-outline">ยกเลิก</label>
                <button type="submit" class="btn btn-primary gap-2 font-semibold">
                    <i data-lucide="check" class="size-4"></i>
                    เพิ่มสัตว์เลี้ยง
                </button>
            </div>
        </form>
    </div>
    <label class="modal-backdrop" for="add_pet_modal"></label>
</div>

<!-- Edit Pet Modal (Mobile Bottom / Desktop Middle, styled like navbar modal) -->
<input type="checkbox" id="edit_pet_modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
    <div
        class="modal-box rounded-t-3xl rounded-b-none max-h-[85vh] md:max-h-screen p-0 sm:rounded-2xl flex flex-col lg:w-11/12 max-w-5xl">
        <!-- Drag handle indicator (mobile only) -->
        <div class="flex justify-center pt-3 pb-2 sm:hidden">
            <div class="w-12 h-1.5 bg-base-300 rounded-full"></div>
        </div>
        <!-- Fixed header -->
        <div class="px-5 sm:px-6 border-b border-base-200 bg-base-100 sticky top-0 z-20">
            <div class="flex items-center justify-between py-4">
                <span class="font-bold text-lg tracking-tight text-primary">แก้ไขข้อมูลสัตว์เลี้ยง</span>
                <label for="edit_pet_modal" class="btn btn-ghost btn-sm btn-circle">
                    <i data-lucide="x" class="size-4"></i>
                </label>
            </div>
        </div>
        <!-- Scrollable form body -->
        <form method="POST" action="?action=pet" autocomplete="off" class="px-5 sm:px-6 py-4 flex-1 overflow-y-auto"
            id="edit_pet_form">
            <input type="hidden" name="pet_id" id="edit_pet_id">
            <input type="hidden" name="action" value="edit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-4">
                    <div class="form-control">
                        <label for="edit_name" class="label label-text font-medium">ชื่อสัตว์เลี้ยง <span
                                class="text-error">*</span></label>
                        <input type="text" name="name" id="edit_name" autocomplete="off"
                            class="input input-bordered w-full" required>
                    </div>
                    <div class="form-control">
                        <label for="edit_species_id" class="label label-text font-medium">ชนิดสัตว์ <span
                                class="text-error">*</span></label>
                        <select name="species_id" id="edit_species_id" class="select select-bordered w-full" required
                            onchange="filterEditBreeds(this.value)">
                            <option value="">-- เลือกชนิดสัตว์ --</option>
                            <?php foreach ($species_list as $sp): ?>
                                <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control">
                        <label for="edit_breed_id" class="label label-text font-medium">สายพันธุ์</label>
                        <select name="breed_id" id="edit_breed_id" class="select select-bordered w-full">
                            <option value="">-- เลือกสายพันธุ์ --</option>
                            <?php foreach ($breeds_list as $br): ?>
                                <option value="<?php echo $br['id']; ?>" data-species="<?php echo $br['species_id']; ?>">
                                    <?php echo htmlspecialchars($br['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control">
                        <label for="edit_gender" class="label label-text font-medium">เพศ <span
                                class="text-error">*</span></label>
                        <select name="gender" id="edit_gender" class="select select-bordered w-full" required>
                            <option value="male">ตัวผู้</option>
                            <option value="female">ตัวเมีย</option>
                            <option value="spayed">ทำหมันแล้ว (เมีย)</option>
                            <option value="neutered">ทำหมันแล้ว (ผู้)</option>
                            <option value="unknown">ไม่ระบุ</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label for="edit_dob" class="label label-text font-medium">วันเกิด</label>
                        <input type="date" name="dob" id="edit_dob" class="input input-bordered w-full">
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="form-control">
                        <label for="edit_weight_kg" class="label label-text font-medium">น้ำหนัก</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="weight_kg" id="edit_weight_kg"
                                class="input input-bordered w-full" min="0" step="0.01" placeholder="น้ำหนัก">
                            <span class="text-base-content/60 text-sm">kg</span>
                        </div>
                    </div>
                    <div class="form-control">
                        <label for="edit_vet_name"
                            class="label label-text font-medium">ชื่อคลินิก/สัตวแพทย์ประจำตัว</label>
                        <input type="text" name="vet_name" id="edit_vet_name" class="input input-bordered w-full"
                            placeholder="ชื่อคลินิก/สัตวแพทย์">
                    </div>
                    <div class="form-control">
                        <label for="edit_vet_phone" class="label label-text font-medium">เบอร์คลินิก/สัตวแพทย์</label>
                        <input type="text" name="vet_phone" id="edit_vet_phone" class="input input-bordered w-full"
                            placeholder="เบอร์โทรศัพท์">
                    </div>
                    <div class="form-control">
                        <label for="edit_behavior_note" class="label label-text font-medium">หมายเหตุพฤติกรรม</label>
                        <textarea name="behavior_note" id="edit_behavior_note" class="textarea textarea-bordered w-full"
                            placeholder="เช่น กลัวฟ้าร้อง, ไม่ชอบอาบน้ำ"></textarea>
                    </div>
                    <div class="form-control">
                        <label for="edit_is_aggressive" class="label cursor-pointer flex items-center gap-2">
                            <input type="checkbox" name="is_aggressive" id="edit_is_aggressive"
                                class="checkbox checkbox-error text-white" />
                            <span class="text-error font-semibold">ดุ/กัด (แจ้งเตือนพนักงาน)</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <label for="edit_pet_modal" class="btn btn-outline">ยกเลิก</label>
                <button type="submit" class="btn btn-primary gap-2 font-semibold">
                    <i data-lucide="check" class="size-4"></i>
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
    <label class="modal-backdrop" for="edit_pet_modal"></label>
</div>

<script>
    const petsData = <?php echo json_encode($pets); ?>;

    // Dynamic breed filter
    function filterBreeds(speciesId) {
        const breedSelect = document.getElementById('breed_select');
        Array.from(breedSelect.options).forEach(opt => {
            if (!opt.value) {
                opt.style.display = '';
                return;
            }
            opt.style.display = (opt.getAttribute('data-species') === speciesId) ? '' : 'none';
        });
        breedSelect.value = '';
    }

    // Dynamic breed filter for edit modal
    function filterEditBreeds(speciesId) {
        const breedSelect = document.getElementById('edit_breed_id');
        Array.from(breedSelect.options).forEach(opt => {
            if (!opt.value) {
                opt.style.display = '';
                return;
            }
            opt.style.display = (opt.getAttribute('data-species') === speciesId) ? '' : 'none';
        });
        breedSelect.value = '';
    }

    // Open edit modal and fill form
    function openEditPetModal(petId) {
        const pet = petsData.find(p => p.id == petId);
        if (!pet) return;

        document.getElementById('edit_pet_id').value = pet.id;
        document.getElementById('edit_name').value = pet.name || '';
        document.getElementById('edit_species_id').value = pet.species_id || '';
        filterEditBreeds(pet.species_id);
        document.getElementById('edit_breed_id').value = pet.breed_id || '';
        document.getElementById('edit_gender').value = pet.gender || '';
        document.getElementById('edit_dob').value = pet.dob || '';
        document.getElementById('edit_weight_kg').value = pet.weight_kg || '';
        document.getElementById('edit_behavior_note').value = pet.behavior_note || '';
        document.getElementById('edit_is_aggressive').checked = pet.is_aggressive == 1;
        document.getElementById('edit_vet_name').value = pet.vet_name || '';
        document.getElementById('edit_vet_phone').value = pet.vet_phone || '';
        document.getElementById('edit_pet_modal').checked = true;
    }
</script>