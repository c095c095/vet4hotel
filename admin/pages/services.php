<?php
// ═══════════════════════════════════════════════════════════
// ADMIN SERVICES PAGE UI — VET4 HOTEL
// Service (add-on) management: list, search, filter, CRUD
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/services_data.php';

// Helper for charge type badge
function charge_type_badge($type)
{
    $map = [
        'per_stay' => ['ต่อการเข้าพัก', 'badge-info'],
        'per_night' => ['ต่อคืน', 'badge-primary'],
        'per_pet' => ['ต่อตัว', 'badge-secondary'],
    ];
    $info = $map[$type] ?? ['ไม่ทราบ', 'badge-ghost'];
    return '<span class="badge badge-sm ' . $info[1] . ' gap-1">' . $info[0] . '</span>';
}

// Helper for active status badge
function active_badge($is_active)
{
    if ($is_active) {
        return '<span class="badge badge-sm badge-success gap-1"><i data-lucide="check" class="size-3"></i> เปิดใช้งาน</span>';
    }
    return '<span class="badge badge-sm badge-error gap-1"><i data-lucide="x" class="size-3"></i> ปิดใช้งาน</span>';
}
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ HEADER & ACTIONS ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="sparkles" class="size-5 text-primary"></i>
                </div>
                จัดการบริการเสริม
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">
                จัดการบริการเสริมทั้งหมด (Add-ons) เช่น อาบน้ำ ตัดขน สปา ฝึกพิเศษ
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('modal_add_service').showModal()"
                class="btn btn-primary shadow-sm hover:shadow-md transition-shadow gap-2">
                <i data-lucide="plus" class="size-4"></i>
                เพิ่มบริการ
            </button>
        </div>
    </div>

    <!-- ═══════════ SUMMARY STAT CARDS ═══════════ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <!-- Total Services -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">บริการทั้งหมด</p>
                        <p class="text-2xl font-bold text-base-content mt-1">
                            <?php echo $stats['total'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-base-200/80 flex items-center justify-center">
                        <i data-lucide="sparkles" class="size-5 text-base-content/40"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Active -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">เปิดใช้งาน</p>
                        <p class="text-2xl font-bold text-success mt-1">
                            <?php echo $stats['active_count'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
                        <i data-lucide="check-circle" class="size-5 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- In Use Today -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ใช้งานอยู่ตอนนี้</p>
                        <p class="text-2xl font-bold text-primary mt-1">
                            <?php echo $in_use_count; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i data-lucide="activity" class="size-5 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Inactive -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ปิดใช้งาน</p>
                        <p class="text-2xl font-bold text-warning mt-1">
                            <?php echo $stats['inactive_count'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center">
                        <i data-lucide="pause-circle" class="size-5 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="index.php" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="services">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" placeholder="ชื่อบริการ, รายละเอียด..."
                            value="<?php echo htmlspecialchars($search); ?>" />
                    </label>
                </div>

                <!-- Charge Type Filter -->
                <div class="form-control w-full xl:w-52">
                    <label class="label pt-0"><span class="label-text font-medium">ประเภทคิดค่าบริการ</span></label>
                    <select name="charge_type"
                        class="select select-bordered w-full focus:select-primary transition-colors">
                        <?php foreach ($charge_type_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>" <?php echo $charge_type_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $cfg['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Active Filter -->
                <div class="form-control w-full xl:w-44">
                    <label class="label pt-0"><span class="label-text font-medium">สถานะ</span></label>
                    <select name="active" class="select select-bordered w-full focus:select-primary transition-colors">
                        <?php foreach ($active_status_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>" <?php echo $active_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $cfg['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2 mt-2 xl:mt-0">
                    <button type="submit" class="btn btn-primary gap-2 w-full sm:w-auto">
                        <i data-lucide="filter" class="size-4"></i>
                        กรองข้อมูล
                    </button>
                    <a href="?page=services"
                        class="btn btn-ghost btn-square text-base-content/50 hover:text-base-content tooltip"
                        data-tip="ล้างตัวกรอง">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                    </a>
                </div>
            </form>

            <?php if (($stats['total'] ?? 0) > 0): ?>
                <!-- Quick Charge Type Badges -->
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-base-200">
                    <span class="text-sm text-base-content/60 mr-2 flex items-center">ตัวกรองด่วน:</span>
                    <?php
                    $quick_filters = [
                        'per_stay' => ['label' => 'ต่อการเข้าพัก', 'count' => $stats['per_stay_count'] ?? 0, 'class' => 'badge-info'],
                        'per_night' => ['label' => 'ต่อคืน', 'count' => $stats['per_night_count'] ?? 0, 'class' => 'badge-primary'],
                        'per_pet' => ['label' => 'ต่อตัว', 'count' => $stats['per_pet_count'] ?? 0, 'class' => 'badge-secondary'],
                    ];
                    foreach ($quick_filters as $qkey => $qval):
                        if ($qval['count'] > 0): ?>
                            <a href="?page=services&charge_type=<?php echo $qkey; ?>"
                                class="badge badge-sm hover:scale-105 transition-transform cursor-pointer <?php echo $qval['class']; ?> <?php echo $charge_type_filter === $qkey ? 'ring-2 ring-offset-1 ring-primary' : 'opacity-80'; ?>">
                                <?php echo $qval['label']; ?> (
                                <?php echo $qval['count']; ?>)
                            </a>
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════ DATA TABLE ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-sm sm:table-md w-full">
                <thead class="bg-base-200/50 text-base-content/70">
                    <tr>
                        <th class="font-medium">ชื่อบริการ</th>
                        <th class="font-medium text-right">ราคา (฿)</th>
                        <th class="font-medium text-center">ประเภทคิดเงิน</th>
                        <th class="font-medium text-center">ใช้งานอยู่</th>
                        <th class="font-medium text-center">สถานะ</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-base-content/50">
                                <i data-lucide="search-x" class="size-10 mx-auto mb-3 opacity-30"></i>
                                ไม่มีข้อมูลบริการที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $s): ?>
                            <tr class="hover group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                            <i data-lucide="sparkles" class="size-4 text-primary"></i>
                                        </div>
                                        <span class="font-semibold text-base-content">
                                            <?php echo htmlspecialchars($s['name']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-right font-medium text-sm">
                                    <?php echo number_format($s['price'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo charge_type_badge($s['charge_type']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['active_usage_count'] > 0): ?>
                                        <span class="badge badge-sm badge-outline badge-primary gap-1">
                                            <i data-lucide="zap" class="size-3"></i>
                                            <?php echo $s['active_usage_count']; ?> การจอง
                                        </span>
                                    <?php else: ?>
                                        <span class="text-base-content/30 text-xs">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo active_badge($s['is_active']); ?>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Edit Button -->
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-primary tooltip"
                                            data-tip="แก้ไข">
                                            <i data-lucide="pencil" class="size-4"></i>
                                        </button>
                                        <!-- Toggle Active Button -->
                                        <?php if ($s['is_active']): ?>
                                            <button type="button"
                                                onclick="openToggleModal(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>', 0)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-warning tooltip"
                                                data-tip="ปิดใช้งาน">
                                                <i data-lucide="pause-circle" class="size-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="openToggleModal(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>', 1)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-success tooltip"
                                                data-tip="เปิดใช้งาน">
                                                <i data-lucide="play-circle" class="size-4"></i>
                                            </button>
                                        <?php endif; ?>
                                        <!-- Delete Button -->
                                        <button type="button"
                                            onclick="openDeleteModal(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name'], ENT_QUOTES); ?>')"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-error tooltip"
                                            data-tip="ลบ">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="border-t border-base-200 p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-sm text-base-content/60">
                    แสดง
                    <?php echo ($offset + 1); ?> ถึง
                    <?php echo min($offset + $limit, $total_records); ?> จาก
                    <?php echo $total_records; ?> รายการ
                </span>
                <div class="join shadow-sm">
                    <?php
                    $query_string = $_GET;
                    unset($query_string['p']);
                    $base_url = '?' . http_build_query($query_string) . '&p=';

                    if ($page > 1): ?>
                        <a href="<?php echo $base_url . ($page - 1); ?>"
                            class="join-item btn btn-sm bg-base-100 hover:bg-base-200">«</a>
                    <?php else: ?>
                        <button class="join-item btn btn-sm bg-base-100 btn-disabled">«</button>
                    <?php endif;

                    for ($i = 1; $i <= $total_pages; $i++):
                        if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)):
                            $active = ($i == $page) ? 'btn-primary' : 'bg-base-100 hover:bg-base-200';
                            ?>
                            <a href="<?php echo $base_url . $i; ?>" class="join-item btn btn-sm <?php echo $active; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <button class="join-item btn btn-sm bg-base-100 btn-disabled">...</button>
                        <?php endif;
                    endfor;

                    if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url . ($page + 1); ?>"
                            class="join-item btn btn-sm bg-base-100 hover:bg-base-200">»</a>
                    <?php else: ?>
                        <button class="join-item btn btn-sm bg-base-100 btn-disabled">»</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ═══════════ ADD SERVICE MODAL ═══════════ -->
<dialog id="modal_add_service" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-lg">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="plus-circle" class="size-4 text-primary"></i>
            </div>
            เพิ่มบริการเสริมใหม่
        </h3>
        <form method="POST" action="?action=services" class="space-y-4">
            <input type="hidden" name="sub_action" value="add">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ชื่อบริการ <span
                            class="text-error">*</span></span></label>
                <input type="text" name="name" placeholder="เช่น อาบน้ำ, ตัดขน, สปา..."
                    class="input input-bordered w-full focus:input-primary" required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">รายละเอียด <span
                            class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                <textarea name="description" placeholder="อธิบายรายละเอียดบริการ..."
                    class="textarea textarea-bordered w-full focus:textarea-primary h-20" rows="2"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ราคา (฿) <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="price" placeholder="0.00" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ประเภทคิดเงิน <span
                                class="text-error">*</span></span></label>
                    <select name="charge_type" class="select select-bordered w-full focus:select-primary" required>
                        <option value="per_stay">ต่อการเข้าพัก</option>
                        <option value="per_night">ต่อคืน</option>
                        <option value="per_pet">ต่อตัว</option>
                    </select>
                </div>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary gap-2">
                    <i data-lucide="plus" class="size-4"></i>
                    เพิ่มบริการ
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ EDIT SERVICE MODAL ═══════════ -->
<dialog id="modal_edit_service" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-lg">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                <i data-lucide="pencil" class="size-4 text-warning"></i>
            </div>
            แก้ไขข้อมูลบริการ
        </h3>
        <form method="POST" action="?action=services" id="edit_service_form" class="space-y-4">
            <input type="hidden" name="sub_action" value="edit">
            <input type="hidden" name="service_id" id="edit_service_id">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ชื่อบริการ <span
                            class="text-error">*</span></span></label>
                <input type="text" name="name" id="edit_name" class="input input-bordered w-full focus:input-primary"
                    required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">รายละเอียด <span
                            class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                <textarea name="description" id="edit_description"
                    class="textarea textarea-bordered w-full focus:textarea-primary h-20" rows="2"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ราคา (฿) <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="price" id="edit_price" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ประเภทคิดเงิน <span
                                class="text-error">*</span></span></label>
                    <select name="charge_type" id="edit_charge_type"
                        class="select select-bordered w-full focus:select-primary" required>
                        <option value="per_stay">ต่อการเข้าพัก</option>
                        <option value="per_night">ต่อคืน</option>
                        <option value="per_pet">ต่อตัว</option>
                    </select>
                </div>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-warning gap-2">
                    <i data-lucide="save" class="size-4"></i>
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ CONFIRM TOGGLE ACTIVE MODAL ═══════════ -->
<dialog id="modal_toggle_active" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div id="toggle_icon_wrap"
                class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-warning/10">
                <i data-lucide="alert-triangle" class="size-7 text-warning"></i>
            </div>
            <h3 class="font-bold text-lg mb-2">ยืนยันการเปลี่ยนสถานะ</h3>
            <p class="text-base-content/60" id="toggle_message">ต้องการเปลี่ยนสถานะบริการนี้ใช่หรือไม่?</p>
        </div>
        <form method="POST" action="?action=services" id="toggle_form">
            <input type="hidden" name="sub_action" value="toggle_active">
            <input type="hidden" name="service_id" id="toggle_service_id">
            <input type="hidden" name="new_status" id="toggle_new_status">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_toggle_active').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" id="toggle_submit_btn" class="btn btn-warning gap-2">
                    <i data-lucide="check" class="size-4"></i>
                    ยืนยัน
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ CONFIRM DELETE MODAL ═══════════ -->
<dialog id="modal_delete_service" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-error/10">
                <i data-lucide="trash-2" class="size-7 text-error"></i>
            </div>
            <h3 class="font-bold text-lg mb-2">ยืนยันการลบบริการ</h3>
            <p class="text-base-content/60" id="delete_message">ต้องการลบบริการนี้ใช่หรือไม่?</p>
            <p class="text-xs text-base-content/40 mt-2">การลบนี้สามารถกู้คืนได้โดยผู้ดูแลระบบ</p>
        </div>
        <form method="POST" action="?action=services" id="delete_form">
            <input type="hidden" name="sub_action" value="delete">
            <input type="hidden" name="service_id" id="delete_service_id">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_delete_service').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" class="btn btn-error gap-2">
                    <i data-lucide="trash-2" class="size-4"></i>
                    ลบบริการ
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    function openEditModal(service) {
        document.getElementById('edit_service_id').value = service.id;
        document.getElementById('edit_name').value = service.name;
        document.getElementById('edit_description').value = service.description || '';
        document.getElementById('edit_price').value = service.price;
        document.getElementById('edit_charge_type').value = service.charge_type;
        document.getElementById('modal_edit_service').showModal();
    }

    function openToggleModal(serviceId, serviceName, newStatus) {
        document.getElementById('toggle_service_id').value = serviceId;
        document.getElementById('toggle_new_status').value = newStatus;

        const label = newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        document.getElementById('toggle_message').innerHTML =
            'ต้องการ <strong>' + label + '</strong> บริการ <strong class="text-primary">"' + serviceName + '"</strong> ใช่หรือไม่?';

        const btn = document.getElementById('toggle_submit_btn');
        btn.className = 'btn gap-2';
        btn.classList.add(newStatus === 1 ? 'btn-success' : 'btn-warning');

        const iconWrap = document.getElementById('toggle_icon_wrap');
        iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4';
        iconWrap.classList.add(newStatus === 1 ? 'bg-success/10' : 'bg-warning/10');

        document.getElementById('modal_toggle_active').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openDeleteModal(serviceId, serviceName) {
        document.getElementById('delete_service_id').value = serviceId;
        document.getElementById('delete_message').innerHTML =
            'ต้องการลบบริการ <strong class="text-primary">"' + serviceName + '"</strong> ใช่หรือไม่?';
        document.getElementById('modal_delete_service').showModal();
    }

    // Re-init lucide icons after page load
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>