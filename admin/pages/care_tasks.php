<?php
// ═══════════════════════════════════════════════════════════
// ADMIN CARE TASKS PAGE UI — VET4 HOTEL
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/care_tasks_data.php';

// Helper for status badge
function status_badge($status)
{
    global $status_config;
    $cfg = $status_config[$status] ?? ['label' => $status, 'class' => 'badge-ghost', 'icon' => 'help-circle'];
    return "<div class='badge {$cfg['class']} gap-1 border-0'><i data-lucide='{$cfg['icon']}' class='size-3'></i> {$cfg['label']}</div>";
}
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">
    <!-- ═══════════ HEADER & ACTIONS ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold flex items-center gap-3 text-base-content">
                <i data-lucide="clipboard-check" class="size-8 text-primary"></i>
                งานดูแลรายวัน
            </h1>
            <p class="text-base-content/60 mt-1">จัดการหน้ารายการงานดูแลสัตว์เลี้ยงประจำวัน</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openAddModal()"
                class="btn btn-primary shadow-sm hover:shadow-md hover:shadow-primary/20 transition-all rounded-xl">
                <i data-lucide="plus-circle" class="size-5"></i>
                เพิ่มงานดูแล
            </button>
        </div>
    </div>

    <!-- ═══════════ SUMMARY STAT CARDS ═══════════ -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:gap-4">
        <!-- Total -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">งานทั้งหมด (ตามวันที่เลือก)</p>
                        <p class="text-2xl font-bold mt-1">
                            <?php echo number_format($stats['total']); ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-base-200 flex items-center justify-center">
                        <i data-lucide="list" class="size-5 text-base-content/60"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pending -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">รอดำเนินการ</p>
                        <p class="text-2xl font-bold mt-1 text-warning">
                            <?php echo number_format($stats['pending_count'] ?? 0); ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center">
                        <i data-lucide="clock" class="size-5 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Completed -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">เสร็จสิ้นแล้ว</p>
                        <p class="text-2xl font-bold mt-1 text-success">
                            <?php echo number_format($stats['completed_count'] ?? 0); ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
                        <i data-lucide="check-circle" class="size-5 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="index.php" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="care_tasks">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label
                        class="input input-bordered flex items-center gap-2 rounded-xl focus-within:outline-primary/50 focus-within:border-primary w-full transition-colors">
                        <i data-lucide="search" class="size-4 text-base-content/50"></i>
                        <input type="text" name="search" class="grow"
                            placeholder="ชื่อสัตว์เลี้ยง, รายละเอียด, หมายเลขการจอง..."
                            value="<?php echo htmlspecialchars($search); ?>" />
                    </label>
                </div>

                <!-- Date Filter -->
                <div class="form-control w-full xl:w-48">
                    <label class="label pt-0"><span class="label-text font-medium">วันที่</span></label>
                    <select name="date"
                        class="select select-bordered rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                        onchange="this.form.submit()">
                        <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>ทุกวัน</option>
                        <option value="<?php echo date('Y-m-d'); ?>" <?php echo $date_filter === date('Y-m-d') ? 'selected' : ''; ?>>วันนี้ (
                            <?php echo date('d/m/Y'); ?>)
                        </option>
                        <option value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" <?php echo $date_filter === date('Y-m-d', strtotime('-1 day')) ? 'selected' : ''; ?>>เมื่อวาน</option>
                        <option value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" <?php echo $date_filter === date('Y-m-d', strtotime('+1 day')) ? 'selected' : ''; ?>>พรุ่งนี้</option>
                        <?php if (!in_array($date_filter, ['all', date('Y-m-d'), date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('+1 day'))])): ?>
                                <option value="<?php echo htmlspecialchars($date_filter); ?>" selected>
                                    <?php echo date('d/m/Y', strtotime($date_filter)); ?>
                                </option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Task Type Form -->
                <div class="form-control w-full xl:w-48">
                    <label class="label pt-0"><span class="label-text font-medium">ประเภทงาน</span></label>
                    <select name="task_type"
                        class="select select-bordered rounded-xl focus:outline-primary/50 focus:border-primary w-full transition-colors"
                        onchange="this.form.submit()">
                        <option value="all">ทั้งหมด</option>
                        <?php foreach ($care_task_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $task_type_filter == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="form-control w-full xl:w-44">
                    <label class="label pt-0"><span class="label-text font-medium">สถานะ</span></label>
                    <select name="status"
                        class="select select-bordered rounded-xl focus:outline-primary/50 focus:border-primary w-full transition-colors"
                        onchange="this.form.submit()">
                        <?php foreach ($status_config as $val => $cfg): ?>
                                <option value="<?php echo $val; ?>" <?php echo $status_filter === (string) $val ? 'selected' : ''; ?>>
                                    <?php echo $cfg['label']; ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="form-control pt-7">
                    <a href="?page=care_tasks" class="btn btn-ghost text-base-content/60 hover:bg-base-200 rounded-xl"
                        data-tip="ล้างตัวกรอง">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════ TABLE ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="table w-full whitespace-nowrap">
                <!-- head -->
                <thead class="bg-base-200/50 text-base-content/80 text-sm">
                    <tr>
                        <th class="font-semibold py-4 w-12 text-center">#</th>
                        <th class="font-semibold py-4">สัตว์เลี้ยง</th>
                        <th class="font-semibold py-4">งาน / รายละเอียด</th>
                        <th class="font-semibold py-4">สถานะ</th>
                        <th class="font-semibold py-4">ผู้ดำเนินการ</th>
                        <th class="font-semibold py-4 text-center w-24">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-10">
                                    <div class="flex flex-col items-center justify-center text-base-content/40 space-y-3">
                                        <i data-lucide="clipboard-x" class="size-12"></i>
                                        <p class="text-base font-medium">ไม่พบข้อมูลงานดูแล</p>
                                        <p class="text-sm">ลองเปลี่ยนเงื่อนไขการค้นหา หรือเพิ่มงานใหม่</p>
                                    </div>
                                </td>
                            </tr>
                    <?php else: ?>
                            <?php foreach ($tasks as $index => $row): ?>
                                    <tr class="hover:bg-base-200/30 transition-colors border-b border-base-200">
                                        <td class="text-center text-base-content/50">
                                            <?php echo $offset + $index + 1; ?>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="avatar placeholder">
                                                    <div
                                                        class="bg-primary/10 text-primary uppercase rounded-xl w-10 h-10 font-bold border border-primary/20 flex items-center justify-center">
                                                        <span>
                                                            <?php echo mb_substr($row['pet_name'], 0, 1, 'UTF-8'); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-base flex items-center gap-2">
                                                        <a href="?page=pets&search=<?php echo urlencode($row['pet_name']); ?>"
                                                            class="hover:text-primary transition-colors">
                                                            <?php echo sanitize($row['pet_name']); ?>
                                                        </a>
                                                        <?php if ($row['is_aggressive']): ?>
                                                                <div class="badge badge-error badge-sm gap-1" data-tip="ดุ/กัด">
                                                                    <i data-lucide="alert-triangle" class="size-3"></i> ดุ
                                                                </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-xs text-base-content/60 mt-0.5 flex flex-wrap gap-x-2">
                                                        <span>
                                                            <?php echo sanitize($row['species_name']); ?>
                                                        </span>
                                                        <span>• ห้อง:
                                                            <?php echo sanitize($row['room_number']); ?>
                                                        </span>
                                                        <a href="?page=booking_detail&id=<?php echo sanitize($row['booking_id']); ?>"
                                                            class="text-primary hover:underline">
                                                            (
                                                            <?php echo sanitize($row['booking_ref']); ?>)
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-medium">
                                                <?php echo sanitize($row['task_type_name']); ?>
                                            </div>
                                            <div class="text-xs text-base-content/60 max-w-xs truncate"
                                                title="<?php echo htmlspecialchars($row['description']); ?>">
                                                <?php echo sanitize($row['description']); ?>
                                            </div>
                                            <?php if ($date_filter === 'all' || $date_filter === ''): ?>
                                                    <div class="text-xs text-base-content/50 mt-1">
                                                        <i data-lucide="calendar" class="size-3 inline align-middle mr-1"></i>
                                                        <?php echo date('d/m/Y', strtotime($row['task_date'])); ?>
                                                    </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo status_badge($row['status']); ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'completed' && $row['completed_at']): ?>
                                                    <div class="flex items-center gap-2">
                                                        <div class="avatar placeholder">
                                                            <div class="bg-base-300 text-base-content rounded-full w-6 h-6 text-xs flex items-center justify-center">
                                                                <span>
                                                                    <?php echo mb_substr($row['emp_first_name'], 0, 1, 'UTF-8'); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs font-medium">
                                                                <?php echo sanitize($row['emp_first_name']); ?>
                                                            </div>
                                                            <div class="text-[10px] text-base-content/50">
                                                                <?php echo date('H:i', strtotime($row['completed_at'])); ?> น.
                                                            </div>
                                                        </div>
                                                    </div>
                                            <?php else: ?>
                                                    <span class="text-base-content/40 text-xs">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="flex items-center justify-center gap-1">
                                                <!-- Quick Complete Toggle Form -->
                                                <form action="?action=care_tasks" method="POST" class="inline">
                                                    <input type="hidden" name="sub_action" value="toggle_status">
                                                    <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="new_status"
                                                        value="<?php echo $row['status'] === 'completed' ? 'pending' : 'completed'; ?>">
                                                    <input type="hidden" name="current_date_filter"
                                                        value="<?php echo htmlspecialchars($date_filter); ?>">

                                                    <?php if ($row['status'] === 'pending'): ?>
                                                            <button type="submit"
                                                                class="btn btn-sm btn-circle btn-ghost text-success hover:bg-success/10"
                                                                data-tip="ทำสำเร็จ">
                                                                <i data-lucide="check" class="size-4"></i>
                                                            </button>
                                                    <?php else: ?>
                                                            <button type="submit"
                                                                class="btn btn-sm btn-circle btn-ghost text-warning hover:bg-warning/10"
                                                                data-tip="ยกเลิกการทำ">
                                                                <i data-lucide="rotate-ccw" class="size-4"></i>
                                                            </button>
                                                    <?php endif; ?>
                                                </form>

                                                <button
                                                    class="btn btn-sm btn-circle btn-ghost text-base-content/70 hover:text-primary hover:bg-primary/10 transition-colors"
                                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                    data-tip="แก้ไข">
                                                    <i data-lucide="edit-3" class="size-4"></i>
                                                </button>
                                                <button
                                                    class="btn btn-sm btn-circle btn-ghost text-error/70 hover:text-error hover:bg-error/10 transition-colors"
                                                    onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['task_type_name'] . ' - ' . $row['pet_name']); ?>')"
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

        <!-- ═══════════ PAGINATION ═══════════ -->
        <?php if ($total_pages > 1): ?>
                <div class="border-t border-base-200 p-4 flex items-center justify-between bg-base-100/50">
                    <div class="text-sm text-base-content/60">
                        แสดง
                        <?php echo min($offset + 1, $total_records); ?> ถึง
                        <?php echo min($offset + $limit, $total_records); ?>
                        จากทั้งหมด
                        <?php echo $total_records; ?> รายการ
                    </div>
                    <div class="join border border-base-200 shadow-sm rounded-xl">
                        <!-- Page numbers logic implementation -->
                        <?php
                        $queryParams = $_GET;
                        unset($queryParams['p']);
                        $queryString = http_build_query($queryParams);
                        $baseUrl = "?" . $queryString . "&p=";

                        // Prev
                        if ($page > 1): ?>
                                <a href="<?php echo $baseUrl . ($page - 1); ?>"
                                    class="join-item btn btn-sm bg-base-100 border-0 hover:bg-base-200">«</a>
                        <?php else: ?>
                                <button class="join-item btn btn-sm bg-base-100 border-0 btn-disabled">«</button>
                        <?php endif; ?>

                        <!-- Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="<?php echo $baseUrl . $i; ?>"
                                    class="join-item btn btn-sm border-0 <?php echo $i === $page ? 'bg-primary text-primary-content hover:bg-primary/90' : 'bg-base-100 hover:bg-base-200'; ?>">
                                    <?php echo $i; ?>
                                </a>
                        <?php endfor; ?>

                        <!-- Next -->
                        <?php if ($page < $total_pages): ?>
                                <a href="<?php echo $baseUrl . ($page + 1); ?>"
                                    class="join-item btn btn-sm bg-base-100 border-0 hover:bg-base-200">»</a>
                        <?php else: ?>
                                <button class="join-item btn btn-sm bg-base-100 border-0 btn-disabled">»</button>
                        <?php endif; ?>
                    </div>
                </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════ MODALS ═══════════ -->

<!-- 1. Add Care Task Modal -->
<dialog id="modal-add-care-task" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box bg-base-100 rounded-t-3xl sm:rounded-3xl p-0 overflow-hidden shadow-2xl max-w-md">
        <div class="p-6 border-b border-base-200 flex items-center gap-3 bg-base-100/50">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                <i data-lucide="plus" class="size-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-base-content leading-tight">เพิ่มงานดูแลรายวัน</h3>
                <p class="text-sm text-base-content/60 mt-0.5">ระบุงานดูแลสำหรับสัตว์เลี้ยงที่กำลังเข้าพัก</p>
            </div>
            <form method="dialog" class="ml-auto">
                <button class="btn btn-sm btn-circle btn-ghost text-base-content/50 hover:text-base-content hover:bg-base-200">
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </form>
        </div>

        <form action="?action=care_tasks" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="sub_action" value="add">
            
            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">สัตว์เลี้ยงที่กำลังเข้าพัก <span class="text-error">*</span></span></label>
                <select name="pet_info" id="add-pet-select" class="select select-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors" required>
                    <option value="" disabled selected>-- กำลังโหลดข้อมูล... --</option>
                </select>
                <!-- We will use JS to split pet_info into booking_item_id and pet_id before submit -->
                <input type="hidden" name="booking_item_id" id="add-booking-item-id">
                <input type="hidden" name="pet_id" id="add-pet-id">
            </div>

            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">วันที่ต้องดูแล <span class="text-error">*</span></span></label>
                <input type="date" name="task_date" class="input input-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">ประเภทงาน <span class="text-error">*</span></span></label>
                <select name="task_type_id" class="select select-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors" required>
                    <option value="" disabled selected>-- เลือกประเภท --</option>
                    <?php foreach ($care_task_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">รายละเอียดงาน <span class="text-error">*</span></span></label>
                <textarea name="description" class="textarea textarea-bordered h-24 rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full" placeholder="เช่น ป้อนยาแก้แพ้ 1 เม็ดหลังอาหารเช้า" required></textarea>
            </div>

            <div class="modal-action mt-6">
                <button type="button" class="btn btn-ghost rounded-xl font-medium" onclick="document.getElementById('modal-add-care-task').close()">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-xl font-medium gap-2 shadow-sm" onclick="return prepareAddSubmit()">
                    <i data-lucide="save" class="size-4"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- 2. Edit Care Task Modal -->
<dialog id="modal-edit-care-task" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box bg-base-100 rounded-t-3xl sm:rounded-3xl p-0 overflow-hidden shadow-2xl max-w-md">
        <div class="p-6 border-b border-base-200 flex items-center gap-3 bg-base-100/50">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                <i data-lucide="edit-3" class="size-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-base-content leading-tight">แก้ไขงานดูแลรายวัน</h3>
                <p class="text-sm text-base-content/60 mt-0.5" id="edit-pet-name">สัตว์เลี้ยง: -</p>
            </div>
            <form method="dialog" class="ml-auto">
                <button class="btn btn-sm btn-circle btn-ghost text-base-content/50 hover:text-base-content hover:bg-base-200">
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </form>
        </div>

        <form action="?action=care_tasks" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="sub_action" value="edit">
            <input type="hidden" name="task_id" id="edit-task-id">
            
            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">วันที่ต้องดูแล <span class="text-error">*</span></span></label>
                <input type="date" name="task_date" id="edit-task-date" class="input input-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors" required>
            </div>

            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">ประเภทงาน <span class="text-error">*</span></span></label>
                <select name="task_type_id" id="edit-task-type" class="select select-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors" required>
                    <option value="" disabled>-- เลือกประเภท --</option>
                    <?php foreach ($care_task_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">รายละเอียดงาน <span class="text-error">*</span></span></label>
                <textarea name="description" id="edit-description" class="textarea textarea-bordered h-24 rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full" required></textarea>
            </div>

            <div class="modal-action mt-6">
                <button type="button" class="btn btn-ghost rounded-xl font-medium" onclick="document.getElementById('modal-edit-care-task').close()">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-xl font-medium gap-2 shadow-sm">
                    <i data-lucide="save" class="size-4"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- 3. Delete Modal -->
<dialog id="modal-delete-care-task" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box bg-base-100 rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl max-w-sm text-center">
        <div class="w-16 h-16 rounded-full bg-error/10 flex items-center justify-center text-error mx-auto mb-4">
            <i data-lucide="alert-triangle" class="size-8"></i>
        </div>
        <h3 class="font-bold text-xl text-base-content mb-2">ลบงานดูแล?</h3>
        <p class="text-base-content/70 text-sm mb-6" id="delete-task-name"></p>

        <form action="?action=care_tasks" method="POST" class="flex flex-col gap-2">
            <input type="hidden" name="sub_action" value="delete">
            <input type="hidden" name="task_id" id="delete-task-id">
            <input type="hidden" name="current_date_filter" value="<?php echo htmlspecialchars($date_filter); ?>">
            
            <button type="submit" class="btn btn-error text-white rounded-xl w-full font-medium">ยืนยันการลบ</button>
            <button type="button" class="btn btn-ghost rounded-xl w-full font-medium" onclick="document.getElementById('modal-delete-care-task').close()">ยกเลิก</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- JavaScript -->
<script>
    // Fetch Active Pets for the Add Modal
    let activePetsLoaded = false;
    
    function loadActivePets() {
        if (activePetsLoaded) return;
        
        const selectEl = document.getElementById('add-pet-select');
        selectEl.innerHTML = '<option value="" disabled selected>-- กำลังโหลดข้อมูล... --</option>';
        
        // Fetch via API
        fetch('cores/api_active_pets.php')
            .then(response => response.json())
            .then(res => {
                if(res.status === 'success') {
                    selectEl.innerHTML = '<option value="" disabled selected>-- เลือกสัตว์เลี้ยง (ห้อง) --</option>';
                    if (res.data.length === 0) {
                        selectEl.innerHTML = '<option value="" disabled>ไม่พบสัตว์เลี้ยงที่กำลังเข้าพักตอนนี้</option>';
                    } else {
                        res.data.forEach(pet => {
                            const opt = document.createElement('option');
                            const valStr = pet.booking_item_id + '|' + pet.pet_id;
                            opt.value = valStr;
                            opt.innerHTML = `${pet.pet_name} (${pet.species_name}) - ห้อง ${pet.room_number} [${pet.booking_ref}]`;
                            selectEl.appendChild(opt);
                        });
                    }
                    activePetsLoaded = true;
                } else {
                    selectEl.innerHTML = '<option value="" disabled selected>เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
                }
            })
            .catch(err => {
                console.error('Error fetching pets:', err);
                selectEl.innerHTML = '<option value="" disabled selected>โหลดข้อมูลล้มเหลว</option>';
            });
    }

    function prepareAddSubmit() {
        const selectEl = document.getElementById('add-pet-select');
        if (!selectEl.value) {
            alert('กรุณาเลือกสัตว์เลี้ยง');
            return false;
        }
        
        const parts = selectEl.value.split('|');
        if (parts.length === 2) {
            document.getElementById('add-booking-item-id').value = parts[0];
            document.getElementById('add-pet-id').value = parts[1];
            return true;
        }
        return false;
    }

    function openAddModal() {
        document.getElementById('modal-add-care-task').showModal();
        loadActivePets();
    }

    function openEditModal(task) {
        document.getElementById('edit-task-id').value = task.id;
        document.getElementById('edit-task-date').value = task.task_date;
        document.getElementById('edit-task-type').value = task.task_type_id;
        document.getElementById('edit-description').value = task.description;
        document.getElementById('edit-pet-name').innerText = `สัตว์เลี้ยง: ${task.pet_name} (ห้อง ${task.room_number})`;
        
        document.getElementById('modal-edit-care-task').showModal();
        
        setTimeout(() => {
            const selectEl = document.getElementById('edit-task-type');
            if(selectEl) selectEl.focus();
        }, 100);
    }

    function openDeleteModal(id, title) {
        document.getElementById('delete-task-id').value = id;
        document.getElementById('delete-task-name').innerText = title;
        document.getElementById('modal-delete-care-task').showModal();
    }
</script>