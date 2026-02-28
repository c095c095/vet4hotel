<?php
// ═══════════════════════════════════════════════════════════
// ADMIN ROOM TYPES PAGE UI — VET4 HOTEL
// Room Types management: list, search, filter, CRUD
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/room_types_data.php';

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
                    <i data-lucide="bed-double" class="size-5 text-primary"></i>
                </div>
                ประเภทห้องพัก
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">
                จัดการประเภทห้องพัก ราคา และจำนวนสัตว์เลี้ยงสูงสุด
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('modal_add_room_type').showModal()"
                class="btn btn-primary shadow-sm hover:shadow-md transition-shadow gap-2">
                <i data-lucide="plus" class="size-4"></i>
                เพิ่มประเภทห้องพัก
            </button>
        </div>
    </div>

    <!-- ═══════════ SUMMARY STAT CARDS ═══════════ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <!-- Total Room Types -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ประเภทห้องทั้งหมด
                        </p>
                        <p class="text-2xl font-bold text-base-content mt-1">
                            <?php echo $stats['total'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-base-200/80 flex items-center justify-center">
                        <i data-lucide="bed-double" class="size-5 text-base-content/40"></i>
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
        <!-- Inactive -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ปิดใช้งาน</p>
                        <p class="text-2xl font-bold text-error mt-1">
                            <?php echo $stats['inactive_count'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-error/10 flex items-center justify-center">
                        <i data-lucide="x-circle" class="size-5 text-error"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Average Price -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ราคาเฉลี่ยต่อคืน</p>
                        <p class="text-2xl font-bold text-primary mt-1">
                            ฿
                            <?php echo number_format($stats['avg_price'] ?? 0, 0); ?>
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
            <form action="index.php" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="room_types">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" placeholder="ชื่อประเภทห้อง, รายละเอียด..."
                            value="<?php echo htmlspecialchars($search); ?>" />
                    </label>
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
                    <a href="?page=room_types"
                        class="btn btn-ghost btn-square text-base-content/50 hover:text-base-content tooltip"
                        data-tip="ล้างตัวกรอง">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════ DATA TABLE ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="w-full">
            <table class="table table-zebra table-sm sm:table-md w-full">
                <thead class="bg-base-200/50 text-base-content/70">
                    <tr>
                        <th class="font-medium">ชื่อประเภทห้องพัก</th>
                        <th class="font-medium text-right">ราคาเริ่มต้น/คืน</th>
                        <th class="font-medium text-center">สัตว์เลี้ยงสูงสุด</th>
                        <th class="font-medium text-center">ขนาด (ตร.ม.)</th>
                        <th class="font-medium text-center">จำนวนห้องพัก</th>
                        <th class="font-medium text-center">สถานะ</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($room_types)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-base-content/50">
                                <i data-lucide="search-x" class="size-10 mx-auto mb-3 opacity-30"></i>
                                ไม่มีข้อมูลประเภทห้องพักที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($room_types as $rt): ?>
                            <tr class="hover group">
                                <td>
                                    <div>
                                        <div class="font-bold text-primary flex items-center gap-1">
                                            <i data-lucide="bed-double" class="size-4"></i>
                                            <?php echo htmlspecialchars($rt['name']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right font-medium text-success">
                                    ฿
                                    <?php echo number_format($rt['base_price_per_night'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <div class="badge badge-ghost font-medium">
                                        <?php echo number_format($rt['max_pets']); ?> ตัว
                                    </div>
                                </td>
                                <td class="text-center text-sm text-base-content/70">
                                    <?php echo !empty($rt['size_sqm']) ? number_format($rt['size_sqm'], 1) . ' ตร.ม.' : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <div class="font-medium text-sm">
                                        <?php echo number_format($rt['total_rooms_count']); ?> ห้อง
                                    </div>
                                    <?php if ($rt['total_rooms_count'] > 0): ?>
                                        <div class="text-xs text-success">
                                            (พร้อมใช้
                                            <?php echo number_format($rt['active_rooms_count']); ?> ห้อง)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo active_badge($rt['is_active']); ?>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Edit Button -->
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($rt)); ?>)"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-primary tooltip"
                                            data-tip="แก้ไข">
                                            <i data-lucide="pencil" class="size-4"></i>
                                        </button>
                                        <!-- Toggle Active Button -->
                                        <?php if ($rt['is_active']): ?>
                                            <button type="button"
                                                onclick="openToggleModal(<?php echo $rt['id']; ?>, '<?php echo htmlspecialchars($rt['name'], ENT_QUOTES); ?>', 0)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-warning tooltip"
                                                data-tip="ปิดใช้งาน">
                                                <i data-lucide="pause-circle" class="size-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="openToggleModal(<?php echo $rt['id']; ?>, '<?php echo htmlspecialchars($rt['name'], ENT_QUOTES); ?>', 1)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-success tooltip"
                                                data-tip="เปิดใช้งาน">
                                                <i data-lucide="play-circle" class="size-4"></i>
                                            </button>
                                        <?php endif; ?>
                                        <!-- Delete Button -->
                                        <?php if ($rt['total_rooms_count'] > 0): ?>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/20 cursor-not-allowed tooltip"
                                                data-tip="ไม่สามารถลบได้ (มีการใช้งานอยู่)">
                                                <i data-lucide="trash-2" class="size-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="openDeleteModal(<?php echo $rt['id']; ?>, '<?php echo htmlspecialchars($rt['name'], ENT_QUOTES); ?>')"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-error tooltip"
                                                data-tip="ลบข้อมูล">
                                                <i data-lucide="trash-2" class="size-4"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- ═══════════ ADD ROOM TYPE MODAL ═══════════ -->
<dialog id="modal_add_room_type" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl text-left">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="plus-circle" class="size-4 text-primary"></i>
            </div>
            เพิ่มประเภทห้องพักใหม่
        </h3>
        <form method="POST" action="?action=room_types" class="space-y-4">
            <input type="hidden" name="sub_action" value="add">

            <div class="grid grid-cols-1 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อประเภทห้องพัก <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="name" placeholder="เช่น Standard Room, VIP Suite"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">รายละเอียดเพิ่มเติม</span></label>
                    <textarea name="description" class="textarea textarea-bordered h-20 focus:textarea-primary w-full"
                        placeholder="คำอธิบายถึงจุดเด่นของห้องนี้..."></textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ราคาเริ่มต้น/คืน (฿) <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="base_price_per_night" placeholder="0" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
                <!-- Max Pets -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">สัตว์เลี้ยงสูงสุด (ตัว) <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="max_pets" placeholder="1" step="1" min="1"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ขนาดห้อง (ตร.ม.)</span></label>
                    <input type="number" name="size_sqm" placeholder="เช่น 15.5" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary gap-2 w-full sm:w-auto">
                    <i data-lucide="plus" class="size-4"></i>
                    เพิ่มประเภทห้องพัก
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ EDIT ROOM TYPE MODAL ═══════════ -->
<dialog id="modal_edit_room_type" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl text-left">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                <i data-lucide="pencil" class="size-4 text-warning"></i>
            </div>
            แก้ไขประเภทห้องพัก
        </h3>
        <form method="POST" action="?action=room_types" id="edit_room_type_form" class="space-y-4">
            <input type="hidden" name="sub_action" value="edit">
            <input type="hidden" name="room_type_id" id="edit_room_type_id">

            <div class="grid grid-cols-1 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อประเภทห้องพัก <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="name" id="edit_name"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">รายละเอียดเพิ่มเติม</span></label>
                    <textarea name="description" id="edit_description"
                        class="textarea textarea-bordered h-20 focus:textarea-primary w-full"></textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ราคาเริ่มต้น/คืน (฿) <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="base_price_per_night" id="edit_base_price_per_night" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
                <!-- Max Pets -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">สัตว์เลี้ยงสูงสุด (ตัว) <span
                                class="text-error">*</span></span></label>
                    <input type="number" name="max_pets" id="edit_max_pets" step="1" min="1"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ขนาดห้อง (ตร.ม.)</span></label>
                    <input type="number" name="size_sqm" id="edit_size_sqm" step="0.01" min="0"
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
            <p class="text-base-content/60" id="toggle_message">ต้องการเปลี่ยนสถานะประเภทห้องพักนี้ใช่หรือไม่?</p>
        </div>
        <form method="POST" action="?action=room_types" id="toggle_form">
            <input type="hidden" name="sub_action" value="toggle_active">
            <input type="hidden" name="room_type_id" id="toggle_room_type_id">
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
<dialog id="modal_delete_room_type" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-error/10">
                <i data-lucide="trash-2" class="size-7 text-error"></i>
            </div>
            <h3 class="font-bold text-lg mb-2">ยืนยันการลบประเภทห้องพัก</h3>
            <p class="text-base-content/60" id="delete_message">ต้องการลบประเภทห้องพักนี้ใช่หรือไม่?</p>
            <p class="text-xs text-error mt-2">คำเตือน: การลบข้อมูลจะไม่สามารถกู้คืนได้ (หากมีห้องพักระบุประเภทนี้อยู่
                จะไม่สามารถลบได้)</p>
        </div>
        <form method="POST" action="?action=room_types" id="delete_form">
            <input type="hidden" name="sub_action" value="delete">
            <input type="hidden" name="room_type_id" id="delete_room_type_id">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_delete_room_type').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" class="btn btn-error gap-2">
                    <i data-lucide="trash-2" class="size-4"></i>
                    ลบข้อมูล
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    function openEditModal(roomType) {
        document.getElementById('edit_room_type_id').value = roomType.id;
        document.getElementById('edit_name').value = roomType.name;
        document.getElementById('edit_description').value = roomType.description;
        document.getElementById('edit_base_price_per_night').value = roomType.base_price_per_night;
        document.getElementById('edit_max_pets').value = roomType.max_pets;
        document.getElementById('edit_size_sqm').value = roomType.size_sqm || '';

        document.getElementById('modal_edit_room_type').showModal();
    }

    function openToggleModal(roomTypeId, roomTypeName, newStatus) {
        document.getElementById('toggle_room_type_id').value = roomTypeId;
        document.getElementById('toggle_new_status').value = newStatus;

        const label = newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        document.getElementById('toggle_message').innerHTML =
            'ต้องการ <strong>' + label + '</strong> ประเภทห้องพัก <strong class="text-primary">"' + roomTypeName + '"</strong> ใช่หรือไม่?';

        const btn = document.getElementById('toggle_submit_btn');
        btn.className = 'btn gap-2';
        btn.classList.add(newStatus === 1 ? 'btn-success' : 'btn-warning');

        const iconWrap = document.getElementById('toggle_icon_wrap');
        iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4';
        iconWrap.classList.add(newStatus === 1 ? 'bg-success/10' : 'bg-warning/10');

        document.getElementById('modal_toggle_active').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openDeleteModal(roomTypeId, roomTypeName) {
        document.getElementById('delete_room_type_id').value = roomTypeId;
        document.getElementById('delete_message').innerHTML =
            'ต้องการลบประเภทห้องพัก <strong class="text-primary">"' + roomTypeName + '"</strong> ใช่หรือไม่?';
        document.getElementById('modal_delete_room_type').showModal();
    }

    // Re-init lucide icons after page load
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>