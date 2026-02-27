<?php
// ═══════════════════════════════════════════════════════════
// ADMIN ROOMS PAGE UI — VET4 HOTEL
// Room inventory management: list, search, filter, CRUD
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/rooms_data.php';

// Helper for room status badgespin
function room_status_badge($status)
{
    $map = [
        'active' => ['พร้อมใช้งาน', 'badge-success'],
        'maintenance' => ['ซ่อมบำรุง', 'badge-warning'],
        'out_of_service' => ['ปิดให้บริการ', 'badge-error'],
    ];
    $info = $map[$status] ?? ['ไม่ทราบ', 'badge-ghost'];
    return '<span class="badge badge-sm ' . $info[1] . ' gap-1">' . $info[0] . '</span>';
}
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ HEADER & ACTIONS ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="door-open" class="size-5 text-primary"></i>
                </div>
                จัดการห้องพัก
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">
                ดูแลและจัดการห้องพักทั้งหมดในระบบ
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('modal_add_room').showModal()"
                class="btn btn-primary shadow-sm hover:shadow-md transition-shadow gap-2">
                <i data-lucide="plus" class="size-4"></i>
                เพิ่มห้องพัก
            </button>
        </div>
    </div>

    <!-- ═══════════ SUMMARY STAT CARDS ═══════════ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <!-- Total Rooms -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ห้องทั้งหมด</p>
                        <p class="text-2xl font-bold text-base-content mt-1">
                            <?php echo $status_counts['all'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-base-200/80 flex items-center justify-center">
                        <i data-lucide="building" class="size-5 text-base-content/40"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Active -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">พร้อมใช้งาน</p>
                        <p class="text-2xl font-bold text-success mt-1">
                            <?php echo $status_counts['active'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
                        <i data-lucide="check-circle" class="size-5 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Occupied Today -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">มีผู้เข้าพักวันนี้
                        </p>
                        <p class="text-2xl font-bold text-primary mt-1">
                            <?php echo $occupied_count; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i data-lucide="paw-print" class="size-5 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Maintenance / Out of Service -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ซ่อมบำรุง / ปิด</p>
                        <p class="text-2xl font-bold text-warning mt-1">
                            <?php echo ($status_counts['maintenance'] ?? 0) + ($status_counts['out_of_service'] ?? 0); ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center">
                        <i data-lucide="wrench" class="size-5 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="index.php" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="rooms">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" placeholder="หมายเลขห้อง, ประเภทห้อง..."
                            value="<?php echo htmlspecialchars($search); ?>" />
                    </label>
                </div>

                <!-- Status Filter -->
                <div class="form-control w-full xl:w-52">
                    <label class="label pt-0"><span class="label-text font-medium">สถานะ</span></label>
                    <select name="status" class="select select-bordered w-full focus:select-primary transition-colors">
                        <?php foreach ($room_status_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $cfg['label']; ?> (
                                <?php echo $status_counts[$key] ?? 0; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Room Type Filter -->
                <div class="form-control w-full xl:w-52">
                    <label class="label pt-0"><span class="label-text font-medium">ประเภทห้อง</span></label>
                    <select name="type" class="select select-bordered w-full focus:select-primary transition-colors">
                        <option value="0">ทั้งหมด</option>
                        <?php foreach ($room_types as $rt): ?>
                            <option value="<?php echo $rt['id']; ?>" <?php echo $type_filter === (int) $rt['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rt['name']); ?>
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
                    <a href="?page=rooms"
                        class="btn btn-ghost btn-square text-base-content/50 hover:text-base-content tooltip"
                        data-tip="ล้างตัวกรอง">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                    </a>
                </div>
            </form>

            <?php if (!empty($status_counts)): ?>
                <!-- Quick Status Badges -->
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-base-200">
                    <span class="text-sm text-base-content/60 mr-2 flex items-center">ตัวกรองด่วน:</span>
                    <?php foreach ($room_status_config as $key => $cfg): ?>
                        <?php if ($key !== 'all' && ($status_counts[$key] ?? 0) > 0): ?>
                            <a href="?page=rooms&status=<?php echo $key; ?>"
                                class="badge badge-sm hover:scale-105 transition-transform cursor-pointer <?php echo $cfg['class']; ?> <?php echo $status_filter === $key ? 'ring-2 ring-offset-1 ring-primary' : 'opacity-80'; ?>">
                                <?php echo $cfg['label']; ?> (
                                <?php echo $status_counts[$key] ?? 0; ?>)
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
                        <th class="font-medium">หมายเลขห้อง</th>
                        <th class="font-medium">ประเภทห้อง</th>
                        <th class="font-medium text-center">ชั้น</th>
                        <th class="font-medium text-right">ราคา/คืน (฿)</th>
                        <th class="font-medium text-center">รับสัตว์สูงสุด</th>
                        <th class="font-medium text-center">CCTV</th>
                        <th class="font-medium text-center">สถานะ</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-10 text-base-content/50">
                                <i data-lucide="search-x" class="size-10 mx-auto mb-3 opacity-30"></i>
                                ไม่มีข้อมูลห้องพักที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rooms as $r): ?>
                            <tr class="hover group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                            <i data-lucide="door-open" class="size-4 text-primary"></i>
                                        </div>
                                        <span class="font-mono font-semibold text-primary">
                                            <?php echo htmlspecialchars($r['room_number']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium">
                                        <?php echo htmlspecialchars($r['type_name']); ?>
                                    </div>
                                    <?php if ($r['size_sqm']): ?>
                                        <div class="text-xs text-base-content/40">
                                            <?php echo $r['size_sqm']; ?> ตร.ม.
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-ghost badge-sm">ชั้น
                                        <?php echo htmlspecialchars($r['floor_level']); ?>
                                    </span>
                                </td>
                                <td class="text-right font-medium text-sm">
                                    <?php echo number_format($r['base_price_per_night'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-ghost badge-sm gap-1">
                                        <i data-lucide="paw-print" class="size-3"></i>
                                        <?php echo $r['max_pets']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['cctv_url']): ?>
                                        <span class="badge badge-sm badge-info gap-1 tooltip"
                                            data-tip="<?php echo htmlspecialchars($r['cctv_url']); ?>">
                                            <i data-lucide="video" class="size-3"></i> มี
                                        </span>
                                    <?php else: ?>
                                        <span class="text-base-content/30 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo room_status_badge($r['status']); ?>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Edit Button -->
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($r)); ?>)"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-primary tooltip"
                                            data-tip="แก้ไข">
                                            <i data-lucide="pencil" class="size-4"></i>
                                        </button>
                                        <!-- Status Toggle Dropdown -->
                                        <div class="dropdown dropdown-end">
                                            <label tabindex="0"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-warning tooltip"
                                                data-tip="เปลี่ยนสถานะ">
                                                <i data-lucide="power" class="size-4"></i>
                                            </label>
                                            <ul tabindex="0"
                                                class="dropdown-content menu p-2 shadow-lg bg-base-100 border border-base-200 rounded-xl w-48 z-50">
                                                <?php
                                                $status_options = [
                                                    'active' => ['label' => 'พร้อมใช้งาน', 'icon' => 'check-circle', 'class' => 'text-success'],
                                                    'maintenance' => ['label' => 'ซ่อมบำรุง', 'icon' => 'wrench', 'class' => 'text-warning'],
                                                    'out_of_service' => ['label' => 'ปิดให้บริการ', 'icon' => 'x-circle', 'class' => 'text-error'],
                                                ];
                                                foreach ($status_options as $skey => $sval):
                                                    if ($skey === $r['status'])
                                                        continue;
                                                    ?>
                                                    <li>
                                                        <button type="button"
                                                            onclick="openStatusModal(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['room_number']); ?>', '<?php echo $skey; ?>', '<?php echo $sval['label']; ?>', '<?php echo $sval['icon']; ?>')"
                                                            class="flex items-center gap-2 w-full <?php echo $sval['class']; ?>">
                                                            <i data-lucide="<?php echo $sval['icon']; ?>" class="size-4"></i>
                                                            <?php echo $sval['label']; ?>
                                                        </button>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
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

<!-- ═══════════ ADD ROOM MODAL ═══════════ -->
<dialog id="modal_add_room" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-lg">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="plus-circle" class="size-4 text-primary"></i>
            </div>
            เพิ่มห้องพักใหม่
        </h3>
        <form method="POST" action="?action=rooms" class="space-y-4">
            <input type="hidden" name="sub_action" value="add">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">หมายเลขห้อง <span
                            class="text-error">*</span></span></label>
                <input type="text" name="room_number" placeholder="เช่น S101, D201, V301..."
                    class="input input-bordered w-full focus:input-primary" required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ประเภทห้อง <span
                            class="text-error">*</span></span></label>
                <select name="room_type_id" class="select select-bordered w-full focus:select-primary" required>
                    <option value="" disabled selected>-- เลือกประเภทห้อง --</option>
                    <?php foreach ($room_types as $rt): ?>
                        <option value="<?php echo $rt['id']; ?>">
                            <?php echo htmlspecialchars($rt['name']); ?> — ฿
                            <?php echo number_format($rt['base_price_per_night'], 2); ?>/คืน (สูงสุด
                            <?php echo $rt['max_pets']; ?> ตัว)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ชั้น <span
                            class="text-error">*</span></span></label>
                <input type="text" name="floor_level" placeholder="เช่น 1, 2, 3..."
                    class="input input-bordered w-full focus:input-primary" value="1" required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">CCTV URL <span
                            class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                <input type="url" name="cctv_url" placeholder="https://cctv.example.com/room/..."
                    class="input input-bordered w-full focus:input-primary" />
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary gap-2">
                    <i data-lucide="plus" class="size-4"></i>
                    เพิ่มห้องพัก
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ EDIT ROOM MODAL ═══════════ -->
<dialog id="modal_edit_room" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-lg">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                <i data-lucide="pencil" class="size-4 text-warning"></i>
            </div>
            แก้ไขข้อมูลห้องพัก
        </h3>
        <form method="POST" action="?action=rooms" id="edit_room_form" class="space-y-4">
            <input type="hidden" name="sub_action" value="edit">
            <input type="hidden" name="room_id" id="edit_room_id">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">หมายเลขห้อง <span
                            class="text-error">*</span></span></label>
                <input type="text" name="room_number" id="edit_room_number"
                    class="input input-bordered w-full focus:input-primary" required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ประเภทห้อง <span
                            class="text-error">*</span></span></label>
                <select name="room_type_id" id="edit_room_type_id"
                    class="select select-bordered w-full focus:select-primary" required>
                    <?php foreach ($room_types as $rt): ?>
                        <option value="<?php echo $rt['id']; ?>">
                            <?php echo htmlspecialchars($rt['name']); ?> — ฿
                            <?php echo number_format($rt['base_price_per_night'], 2); ?>/คืน
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">ชั้น <span
                            class="text-error">*</span></span></label>
                <input type="text" name="floor_level" id="edit_floor_level"
                    class="input input-bordered w-full focus:input-primary" required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">CCTV URL <span
                            class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                <input type="url" name="cctv_url" id="edit_cctv_url"
                    class="input input-bordered w-full focus:input-primary" />
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

<!-- ═══════════ CONFIRM STATUS CHANGE MODAL ═══════════ -->
<dialog id="modal_confirm_status" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div id="confirm_icon_wrap"
                class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-warning/10">
                <i id="confirm_icon" data-lucide="alert-triangle" class="size-7 text-warning"></i>
            </div>
            <h3 class="font-bold text-lg mb-2">ยืนยันการเปลี่ยนสถานะ</h3>
            <p class="text-base-content/60" id="confirm_message">ต้องการเปลี่ยนสถานะห้องนี้ใช่หรือไม่?</p>
        </div>
        <form method="POST" action="?action=rooms" id="status_form">
            <input type="hidden" name="sub_action" value="toggle_status">
            <input type="hidden" name="room_id" id="confirm_room_id">
            <input type="hidden" name="new_status" id="confirm_new_status">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_confirm_status').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" id="confirm_submit_btn" class="btn btn-warning gap-2">
                    <i data-lucide="check" class="size-4"></i>
                    ยืนยัน
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    function openEditModal(room) {
        document.getElementById('edit_room_id').value = room.id;
        document.getElementById('edit_room_number').value = room.room_number;
        document.getElementById('edit_room_type_id').value = room.room_type_id;
        document.getElementById('edit_floor_level').value = room.floor_level;
        document.getElementById('edit_cctv_url').value = room.cctv_url || '';
        document.getElementById('modal_edit_room').showModal();
    }

    function openStatusModal(roomId, roomNumber, newStatus, statusLabel, iconName) {
        // Close any open dropdown by blurring
        document.activeElement?.blur();

        // Populate hidden fields
        document.getElementById('confirm_room_id').value = roomId;
        document.getElementById('confirm_new_status').value = newStatus;

        // Build message
        document.getElementById('confirm_message').innerHTML =
            'ต้องการเปลี่ยนสถานะห้อง <strong class="text-primary">' + roomNumber + '</strong> เป็น <strong>"' + statusLabel + '"</strong> ใช่หรือไม่?';

        // Style the confirm button based on status type
        const btn = document.getElementById('confirm_submit_btn');
        btn.className = 'btn gap-2';
        if (newStatus === 'active') btn.classList.add('btn-success');
        else if (newStatus === 'maintenance') btn.classList.add('btn-warning');
        else btn.classList.add('btn-error');

        // Style the icon
        const iconWrap = document.getElementById('confirm_icon_wrap');
        iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4';
        if (newStatus === 'active') iconWrap.classList.add('bg-success/10');
        else if (newStatus === 'maintenance') iconWrap.classList.add('bg-warning/10');
        else iconWrap.classList.add('bg-error/10');

        document.getElementById('modal_confirm_status').showModal();

        // Re-init icons for modal
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // Re-init lucide icons after page load (for dynamically inserted icons in modals)
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>