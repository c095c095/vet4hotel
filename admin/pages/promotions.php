<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROMOTIONS PAGE UI — VET4 HOTEL
// Promotions & Discounts management: list, search, filter, CRUD
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/promotions_data.php';

// Helper for discount type badge
function discount_type_badge($type)
{
    $map = [
        'percentage' => ['เปอร์เซ็นต์', 'badge-info'],
        'fixed_amount' => ['จำนวนเงิน', 'badge-primary'],
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

// Helper for formatting discount value
function format_discount($value, $type)
{
    if ($type === 'percentage') {
        return number_format($value, 0) . '%';
    }
    return '฿' . number_format($value, 2);
}
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ HEADER & ACTIONS ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="tag" class="size-5 text-primary"></i>
                </div>
                จัดการโปรโมชัน
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">
                จัดการโค้ดส่วนลดและแคมเปญโปรโมชันทั้งหมดในระบบ
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('modal_add_promotion').showModal()"
                class="btn btn-primary shadow-sm hover:shadow-md transition-shadow gap-2">
                <i data-lucide="plus" class="size-4"></i>
                เพิ่มโปรโมชัน
            </button>
        </div>
    </div>

    <!-- ═══════════ SUMMARY STAT CARDS ═══════════ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <!-- Total Promotions -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">โปรโมชันทั้งหมด</p>
                        <p class="text-2xl font-bold text-base-content mt-1">
                            <?php echo $stats['total'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-base-200/80 flex items-center justify-center">
                        <i data-lucide="tag" class="size-5 text-base-content/40"></i>
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
        <!-- Percentage Discount -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ลดเป็นเปอร์เซ็นต์
                        </p>
                        <p class="text-2xl font-bold text-info mt-1">
                            <?php echo $stats['percentage_count'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-info/10 flex items-center justify-center">
                        <i data-lucide="percent" class="size-5 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fixed Amount Discount -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ลดเป็นจำนวนเงิน</p>
                        <p class="text-2xl font-bold text-primary mt-1">
                            <?php echo $stats['fixed_amount_count'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i data-lucide="banknote" class="size-5 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="?page=promotions" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="promotions">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" placeholder="รหัสโปรโมชัน, ชื่อแคมเปญ..."
                            value="<?php echo htmlspecialchars($search); ?>" />
                    </label>
                </div>

                <!-- Discount Type Filter -->
                <div class="form-control w-full xl:w-52">
                    <label class="label pt-0"><span class="label-text font-medium">รูปแบบส่วนลด</span></label>
                    <select name="discount_type"
                        class="select select-bordered w-full focus:select-primary transition-colors">
                        <?php foreach ($discount_type_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>" <?php echo $discount_type_filter === $key ? 'selected' : ''; ?>>
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
                    <a href="?page=promotions"
                        class="btn btn-ghost btn-square text-base-content/50 hover:text-base-content tooltip"
                        data-tip="ล้างตัวกรอง">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                    </a>
                </div>
            </form>

            <?php if (($stats['total'] ?? 0) > 0): ?>
                <!-- Quick Type Badges -->
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-base-200">
                    <span class="text-sm text-base-content/60 mr-2 flex items-center">ตัวกรองด่วน:</span>
                    <?php
                    $quick_filters = [
                        'percentage' => ['label' => 'เปอร์เซ็นต์', 'count' => $stats['percentage_count'] ?? 0, 'class' => 'badge-info'],
                        'fixed_amount' => ['label' => 'จำนวนเงิน', 'count' => $stats['fixed_amount_count'] ?? 0, 'class' => 'badge-primary'],
                    ];
                    foreach ($quick_filters as $qkey => $qval):
                        if ($qval['count'] > 0): ?>
                            <a href="?page=promotions&discount_type=<?php echo $qkey; ?>"
                                class="badge badge-sm hover:scale-105 transition-transform cursor-pointer <?php echo $qval['class']; ?> <?php echo $discount_type_filter === $qkey ? 'ring-2 ring-offset-1 ring-primary' : 'opacity-80'; ?>">
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
                        <th class="font-medium">รหัสโปรโมชัน</th>
                        <th class="font-medium text-center">ประเภทส่วนลด</th>
                        <th class="font-medium text-right">มูลค่าที่ลด</th>
                        <th class="font-medium">เงื่อนไขเพิ่มเติม</th>
                        <th class="font-medium text-center">การใช้งานข้อมูล</th>
                        <th class="font-medium text-center">ระยะเวลาโปรโมชัน</th>
                        <th class="font-medium text-center">สถานะ</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($promotions)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-10 text-base-content/50">
                                <i data-lucide="search-x" class="size-10 mx-auto mb-3 opacity-30"></i>
                                ไม่มีข้อมูลโปรโมชันที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($promotions as $p): ?>
                            <tr class="hover group">
                                <td>
                                    <div>
                                        <div class="font-bold text-primary flex items-center gap-1">
                                            <i data-lucide="tag" class="size-4"></i>
                                            <?php echo htmlspecialchars($p['code']); ?>
                                        </div>
                                        <div class="text-sm opacity-70">
                                            <?php echo htmlspecialchars($p['title']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php echo discount_type_badge($p['discount_type']); ?>
                                </td>
                                <td class="text-right font-medium text-sm text-success">
                                    <?php echo format_discount($p['discount_value'], $p['discount_type']); ?>
                                </td>
                                <td class="text-xs text-base-content/70 max-w-xs truncate">
                                    <?php
                                    $conditions = [];
                                    if ($p['min_booking_amount'] > 0)
                                        $conditions[] = "ยอดขั้นต่ำ ฿" . number_format($p['min_booking_amount']);
                                    if ($p['discount_type'] === 'percentage' && !empty($p['max_discount_amount']))
                                        $conditions[] = "ลดสูงสุด ฿" . number_format($p['max_discount_amount']);
                                    echo !empty($conditions) ? implode(', ', $conditions) : '<span class="opacity-50">—</span>';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <div
                                        class="font-medium <?php echo ($p['usage_limit'] && $p['used_count'] >= $p['usage_limit']) ? 'text-error' : ''; ?>">
                                        <?php echo number_format($p['used_count']); ?> /
                                        <?php echo $p['usage_limit'] ? number_format($p['usage_limit']) : '∞'; ?> ครั้ง
                                    </div>
                                    <?php if ($p['usage_limit']):
                                        $percent = min(100, ($p['used_count'] / $p['usage_limit']) * 100);
                                        ?>
                                        <progress
                                            class="progress <?php echo $percent >= 100 ? 'progress-error' : 'progress-primary'; ?> w-16"
                                            value="<?php echo $percent; ?>" max="100"></progress>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-xs">
                                    <div>
                                        <?php echo date('d M y', strtotime($p['start_date'])); ?>
                                    </div>
                                    <div class="text-base-content/50">ถึง</div>
                                    <div>
                                        <?php echo date('d M y', strtotime($p['end_date'])); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php echo active_badge($p['is_active']); ?>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Edit Button -->
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($p)); ?>)"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-primary tooltip"
                                            data-tip="แก้ไข">
                                            <i data-lucide="pencil" class="size-4"></i>
                                        </button>
                                        <!-- Toggle Active Button -->
                                        <?php if ($p['is_active']): ?>
                                            <button type="button"
                                                onclick="openToggleModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['code'], ENT_QUOTES); ?>', 0)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-warning tooltip"
                                                data-tip="ปิดใช้งาน">
                                                <i data-lucide="pause-circle" class="size-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="openToggleModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['code'], ENT_QUOTES); ?>', 1)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-success tooltip"
                                                data-tip="เปิดใช้งาน">
                                                <i data-lucide="play-circle" class="size-4"></i>
                                            </button>
                                        <?php endif; ?>
                                        <!-- Delete Button -->
                                        <button type="button"
                                            onclick="openDeleteModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['code'], ENT_QUOTES); ?>')"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-error tooltip"
                                            data-tip="ลบข้อมูล">
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

<!-- ═══════════ ADD PROMOTION MODAL ═══════════ -->
<dialog id="modal_add_promotion" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl text-left">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="plus-circle" class="size-4 text-primary"></i>
            </div>
            เพิ่มโปรโมชันใหม่
        </h3>
        <form method="POST" action="?action=promotions" class="space-y-4">
            <input type="hidden" name="sub_action" value="add">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">รหัสโปรโมชัน (Code) <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="code" placeholder="เช่น NEWYEAR2026"
                        class="input input-bordered w-full focus:input-primary uppercase" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อแคมเปญ <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="title" placeholder="เช่น ส่วนลดต้อนรับปีใหม่ 20%"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ประเภทส่วนลด <span
                                class="text-error">*</span></span></label>
                    <select name="discount_type" class="select select-bordered w-full focus:select-primary" required
                        onchange="toggleMaxDiscountAttr(this, 'add_max_discount_wrapper')">
                        <option value="percentage">ลดเป็นเปอร์เซ็นต์ (%)</option>
                        <option value="fixed_amount">ลดเป็นจำนวนเงิน (฿)</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">มูลค่าส่วนลด <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="discount_value" placeholder="0" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ยอดจองขั้นต่ำ (฿)</span></label>
                    <input type="number" name="min_booking_amount" placeholder="0 (ไม่มีขั้นต่ำ)" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
                <div class="form-control" id="add_max_discount_wrapper">
                    <label class="label"><span class="label-text font-medium">ลดสูงสุดไม่เกิน (฿)</span></label>
                    <input type="number" name="max_discount_amount" placeholder="เว้นว่างถ้าไม่จำกัด" step="0.01"
                        min="0" class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <div class="divider my-1"></div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control md:col-span-1">
                    <label class="label"><span class="label-text font-medium">จำนวนครั้งที่ใช้ได้</span></label>
                    <input type="number" name="usage_limit" placeholder="เว้นว่าง = ไม่จำกัด" min="1" step="1"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
                <div class="form-control md:col-span-1">
                    <label class="label"><span class="label-text font-medium">วันเริ่มโปรโมชัน <span
                                class="text-error">*</span></span></label>
                    <input type="date" name="start_date" required
                        class="input input-bordered w-full focus:input-primary" />
                </div>
                <div class="form-control md:col-span-1">
                    <label class="label"><span class="label-text font-medium">วันสิ้นสุดプロโมชัน <span
                                class="text-error">*</span></span></label>
                    <input type="date" name="end_date" required
                        class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary gap-2 w-full sm:w-auto">
                    <i data-lucide="plus" class="size-4"></i>
                    เพิ่มโปรโมชัน
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ EDIT PROMOTION MODAL ═══════════ -->
<dialog id="modal_edit_promotion" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl text-left">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                <i data-lucide="pencil" class="size-4 text-warning"></i>
            </div>
            แก้ไขโปรโมชัน
        </h3>
        <form method="POST" action="?action=promotions" id="edit_promotion_form" class="space-y-4">
            <input type="hidden" name="sub_action" value="edit">
            <input type="hidden" name="promotion_id" id="edit_promotion_id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">รหัสโปรโมชัน (Code) <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="code" id="edit_code"
                        class="input input-bordered w-full focus:input-primary uppercase" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อแคมเปญ <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="title" id="edit_title"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ประเภทส่วนลด <span
                                class="text-error">*</span></span></label>
                    <select name="discount_type" id="edit_discount_type"
                        class="select select-bordered w-full focus:select-primary" required
                        onchange="toggleMaxDiscountAttr(this, 'edit_max_discount_wrapper')">
                        <option value="percentage">ลดเป็นเปอร์เซ็นต์ (%)</option>
                        <option value="fixed_amount">ลดเป็นจำนวนเงิน (฿)</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">มูลค่าส่วนลด <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="discount_value" id="edit_discount_value" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ยอดจองขั้นต่ำ (฿)</span></label>
                    <input type="number" name="min_booking_amount" id="edit_min_booking_amount" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
                <div class="form-control" id="edit_max_discount_wrapper">
                    <label class="label"><span class="label-text font-medium">ลดสูงสุดไม่เกิน (฿)</span></label>
                    <input type="number" name="max_discount_amount" id="edit_max_discount_amount" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <div class="divider my-1"></div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control md:col-span-1">
                    <label class="label"><span class="label-text font-medium">จำนวนครั้งที่ใช้ได้</span></label>
                    <input type="number" name="usage_limit" id="edit_usage_limit" min="1" step="1"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
                <div class="form-control md:col-span-1">
                    <label class="label"><span class="label-text font-medium">วันเริ่มโปรโมชัน <span
                                class="text-error">*</span></span></label>
                    <input type="date" name="start_date" id="edit_start_date" required
                        class="input input-bordered w-full focus:input-primary" />
                </div>
                <div class="form-control md:col-span-1">
                    <label class="label"><span class="label-text font-medium">วันสิ้นสุดโปรโมชัน <span
                                class="text-error">*</span></span></label>
                    <input type="date" name="end_date" id="edit_end_date" required
                        class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-warning gap-2 w-full sm:w-auto">
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
            <p class="text-base-content/60" id="toggle_message">ต้องการเปลี่ยนสถานะโปรโมชันนี้ใช่หรือไม่?</p>
        </div>
        <form method="POST" action="?action=promotions" id="toggle_form">
            <input type="hidden" name="sub_action" value="toggle_active">
            <input type="hidden" name="promotion_id" id="toggle_promotion_id">
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
<dialog id="modal_delete_promotion" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-error/10">
                <i data-lucide="trash-2" class="size-7 text-error"></i>
            </div>
            <h3 class="font-bold text-lg mb-2">ยืนยันการลบโปรโมชัน</h3>
            <p class="text-base-content/60" id="delete_message">ต้องการลบโปรโมชันนี้ใช่หรือไม่?</p>
            <p class="text-xs text-error mt-2">คำเตือน: หากมีการจองอ้างอิงโปรโมชันนี้ การลบอาจทำให้ประวัติหายไป
                ขอแนะนำให้แก้ไขเป็นการปิดใช้งานแทน</p>
        </div>
        <form method="POST" action="?action=promotions" id="delete_form">
            <input type="hidden" name="sub_action" value="delete">
            <input type="hidden" name="promotion_id" id="delete_promotion_id">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_delete_promotion').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" class="btn btn-error gap-2">
                    <i data-lucide="trash-2" class="size-4"></i>
                    ลบโปรโมชัน
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    function toggleMaxDiscountAttr(selectEl, wrapperId) {
        const wrapper = document.getElementById(wrapperId);
        if (selectEl.value === 'fixed_amount') {
            wrapper.style.display = 'none';
        } else {
            wrapper.style.display = 'block';
        }
    }

    function openEditModal(promotion) {
        document.getElementById('edit_promotion_id').value = promotion.id;
        document.getElementById('edit_code').value = promotion.code;
        document.getElementById('edit_title').value = promotion.title;
        document.getElementById('edit_discount_type').value = promotion.discount_type;
        document.getElementById('edit_discount_value').value = promotion.discount_value;
        document.getElementById('edit_min_booking_amount').value = promotion.min_booking_amount || '';
        document.getElementById('edit_max_discount_amount').value = promotion.max_discount_amount || '';
        document.getElementById('edit_usage_limit').value = promotion.usage_limit || '';

        // Extract YYYY-MM-DD from datetime
        if (promotion.start_date) {
            document.getElementById('edit_start_date').value = promotion.start_date.split(' ')[0];
        }
        if (promotion.end_date) {
            document.getElementById('edit_end_date').value = promotion.end_date.split(' ')[0];
        }

        toggleMaxDiscountAttr(document.getElementById('edit_discount_type'), 'edit_max_discount_wrapper');
        document.getElementById('modal_edit_promotion').showModal();
    }

    function openToggleModal(promotionId, promotionCode, newStatus) {
        document.getElementById('toggle_promotion_id').value = promotionId;
        document.getElementById('toggle_new_status').value = newStatus;

        const label = newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        document.getElementById('toggle_message').innerHTML =
            'ต้องการ <strong>' + label + '</strong> โปรโมชัน <strong class="text-primary">"' + promotionCode + '"</strong> ใช่หรือไม่?';

        const btn = document.getElementById('toggle_submit_btn');
        btn.className = 'btn gap-2';
        btn.classList.add(newStatus === 1 ? 'btn-success' : 'btn-warning');

        const iconWrap = document.getElementById('toggle_icon_wrap');
        iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4';
        iconWrap.classList.add(newStatus === 1 ? 'bg-success/10' : 'bg-warning/10');

        document.getElementById('modal_toggle_active').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openDeleteModal(promotionId, promotionCode) {
        document.getElementById('delete_promotion_id').value = promotionId;
        document.getElementById('delete_message').innerHTML =
            'ต้องการลบโปรโมชัน <strong class="text-primary">"' + promotionCode + '"</strong> ใช่หรือไม่?';
        document.getElementById('modal_delete_promotion').showModal();
    }

    // Re-init lucide icons after page load
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Init toggle max discount visibility for add form
        toggleMaxDiscountAttr(document.querySelector('select[name="discount_type"]'), 'add_max_discount_wrapper');
    });
</script>