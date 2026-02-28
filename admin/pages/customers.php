<?php
// ═══════════════════════════════════════════════════════════
// ADMIN CUSTOMERS PAGE UI — VET4 HOTEL
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/customers_data.php';
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ HEADER & ACTIONS ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="size-5 text-primary"></i>
                </div>
                จัดการลูกค้า
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">ดูรายชื่อลูกค้า ข้อมูลสัตว์เลี้ยง และประวัติการจอง</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openAddCustomerModal()" class="btn btn-primary gap-2 shadow-sm">
                <i data-lucide="user-plus" class="size-4"></i>
                เพิ่มลูกค้าใหม่
            </button>
        </div>
    </div>

    <!-- ═══════════ STAT CARDS ═══════════ -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Customers -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 flex-row items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="size-6 text-primary"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-base-content">
                        <?php echo number_format($stats['total']); ?>
                    </div>
                    <div class="text-xs text-base-content/50">ลูกค้าทั้งหมด</div>
                </div>
            </div>
        </div>
        <!-- New This Month -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 flex-row items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                    <i data-lucide="user-plus" class="size-6 text-success"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-base-content">
                        <?php echo number_format($stats['new_this_month']); ?>
                    </div>
                    <div class="text-xs text-base-content/50">สมัครเดือนนี้</div>
                </div>
            </div>
        </div>
        <!-- Active Stays -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 flex-row items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
                    <i data-lucide="hotel" class="size-6 text-info"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-base-content">
                        <?php echo number_format($stats['with_active_stays']); ?>
                    </div>
                    <div class="text-xs text-base-content/50">กำลังเข้าพักอยู่</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="?page=customers" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="customers">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" placeholder="ชื่อ, นามสกุล, อีเมล, เบอร์โทร..."
                            value="<?php echo htmlspecialchars($search); ?>" />
                    </label>
                </div>

                <!-- Status Filter -->
                <div class="form-control w-full xl:w-56">
                    <label class="label pt-0"><span class="label-text font-medium">สถานะ</span></label>
                    <select name="status" class="select select-bordered w-full focus:select-primary transition-colors">
                        <?php foreach ($status_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $cfg['label']; ?> (
                                <?php echo $status_counts[$key] ?? 0; ?>)
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
                    <a href="?page=customers"
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
                    <?php foreach ($status_config as $key => $cfg): ?>
                        <?php if ($key !== 'all' && ($status_counts[$key] ?? 0) > 0): ?>
                            <a href="?page=customers&status=<?php echo $key; ?>"
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
                        <th class="font-medium">ลูกค้า</th>
                        <th class="font-medium">อีเมล</th>
                        <th class="font-medium">เบอร์โทร</th>
                        <th class="font-medium text-center">สัตว์เลี้ยง</th>
                        <th class="font-medium text-center">การจอง</th>
                        <th class="font-medium text-right">ยอดรวม (฿)</th>
                        <th class="font-medium text-center">สถานะ</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-10 text-base-content/50">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="search-x" class="size-10 opacity-30"></i>
                                    ไม่มีข้อมูลลูกค้าที่ตรงกับเงื่อนไขการค้นหา
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <tr class="hover group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder hidden sm:flex">
                                            <div
                                                class="bg-base-300 text-base-content w-9 h-9 rounded-full flex items-center justify-center">
                                                <span class="text-xs font-semibold">
                                                    <?php echo mb_substr($c['first_name'], 0, 1); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-medium">
                                                <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                            </div>
                                            <div class="text-[11px] text-base-content/40">
                                                สมัคร
                                                <?php echo date('d/m/Y', strtotime($c['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm">
                                    <?php echo htmlspecialchars($c['email']); ?>
                                </td>
                                <td class="text-sm whitespace-nowrap">
                                    <?php echo htmlspecialchars($c['phone']); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-ghost badge-sm gap-1">
                                        <i data-lucide="paw-print" class="size-3"></i>
                                        <?php echo $c['pet_count']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-ghost badge-sm">
                                        <?php echo $c['booking_count']; ?> ครั้ง
                                    </span>
                                </td>
                                <td class="text-right font-medium text-sm">
                                    <?php echo number_format($c['total_spent'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($c['is_active']): ?>
                                        <span class="badge badge-success badge-sm gap-1 border-0">
                                            <i data-lucide="check-circle-2" class="size-3"></i> ใช้งาน
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-error badge-sm gap-1 border-0">
                                            <i data-lucide="x-circle" class="size-3"></i> ปิดการใช้งาน
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button
                                        class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-primary tooltip tooltip-left"
                                        data-tip="ดูรายละเอียด"
                                        onclick="document.getElementById('modal_customer_<?php echo $c['id']; ?>').showModal()">
                                        <i data-lucide="eye" class="size-4"></i>
                                    </button>
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

<!-- ═══════════ DETAIL MODALS ═══════════ -->
<?php foreach ($customers as $c): ?>
    <dialog id="modal_customer_<?php echo $c['id']; ?>" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box w-11/12 max-w-3xl p-0 overflow-hidden bg-base-100 rounded-2xl">
            <!-- Modal Header -->
            <div class="bg-base-200/50 border-b border-base-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="avatar placeholder">
                        <div class="bg-primary/10 text-primary rounded-xl w-12 h-12 flex items-center justify-center">
                            <span class="text-lg font-bold">
                                <?php echo mb_substr($c['first_name'], 0, 1) . mb_substr($c['last_name'], 0, 1); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-base-content">
                            <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                        </h3>
                        <p class="text-sm text-base-content/60">
                            รหัสลูกค้า #
                            <?php echo str_pad($c['id'], 5, '0', STR_PAD_LEFT); ?>
                            •
                            <?php if ($c['is_active']): ?>
                                <span class="text-success font-medium">ใช้งาน</span>
                            <?php else: ?>
                                <span class="text-error font-medium">ปิดการใช้งาน</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-6">
                <!-- Customer Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Contact Info -->
                    <div>
                        <div class="text-sm font-semibold mb-3 border-b border-base-200 pb-2 flex items-center gap-2">
                            <i data-lucide="contact" class="size-4 text-primary"></i>
                            ข้อมูลการติดต่อ
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-base-content/60">อีเมล:</span>
                                <span class="font-medium text-right">
                                    <?php echo htmlspecialchars($c['email']); ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-base-content/60">เบอร์โทร:</span>
                                <span class="font-medium">
                                    <?php echo htmlspecialchars($c['phone']); ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-start text-sm">
                                <span class="text-base-content/60 shrink-0">ที่อยู่:</span>
                                <span class="font-medium text-right max-w-[200px]">
                                    <?php echo htmlspecialchars($c['address'] ?: '-'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency & Stats -->
                    <div>
                        <div class="text-sm font-semibold mb-3 border-b border-base-200 pb-2 flex items-center gap-2">
                            <i data-lucide="phone-call" class="size-4 text-error"></i>
                            ผู้ติดต่อฉุกเฉิน
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-base-content/60">ชื่อ:</span>
                                <span class="font-medium">
                                    <?php echo htmlspecialchars($c['emergency_contact_name'] ?: '-'); ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-base-content/60">เบอร์โทร:</span>
                                <span class="font-medium">
                                    <?php echo htmlspecialchars($c['emergency_contact_phone'] ?: '-'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Mini Stats -->
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="bg-base-200/50 rounded-lg p-3 text-center">
                                <div class="text-lg font-bold text-primary">
                                    <?php echo $c['booking_count']; ?>
                                </div>
                                <div class="text-[10px] text-base-content/50 uppercase">การจอง</div>
                            </div>
                            <div class="bg-base-200/50 rounded-lg p-3 text-center">
                                <div class="text-lg font-bold text-primary">
                                    <?php echo $c['pet_count']; ?>
                                </div>
                                <div class="text-[10px] text-base-content/50 uppercase">สัตว์เลี้ยง</div>
                            </div>
                            <div class="bg-base-200/50 rounded-lg p-3 text-center">
                                <div class="text-lg font-bold text-primary">
                                    <?php echo number_format($c['total_spent'], 0); ?>
                                </div>
                                <div class="text-[10px] text-base-content/50 uppercase">ยอดรวม (฿)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm bg-base-200/30 rounded-lg p-3">
                    <div class="flex items-center gap-1.5 text-base-content/60">
                        <i data-lucide="calendar-plus" class="size-3.5"></i>
                        สมัครเมื่อ: <span class="font-medium text-base-content">
                            <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                        </span>
                    </div>
                    <?php if ($c['last_booking_at']): ?>
                        <div class="flex items-center gap-1.5 text-base-content/60">
                            <i data-lucide="calendar-check" class="size-3.5"></i>
                            จองล่าสุด: <span class="font-medium text-base-content">
                                <?php echo date('d/m/Y', strtotime($c['last_booking_at'])); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pets Section -->
                <div>
                    <div class="text-sm font-semibold mb-3 border-b border-base-200 pb-2 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="paw-print" class="size-4 text-primary"></i>
                            สัตว์เลี้ยง (<?php echo $c['pet_count']; ?>)
                        </div>
                        <button type="button" class="btn btn-xs btn-outline btn-primary gap-1"
                            onclick="openAddPetCustomerModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['first_name'] . ' ' . $c['last_name'])); ?>')">
                            <i data-lucide="plus" class="size-3"></i> เพิ่มสัตว์เลี้ยง
                        </button>
                    </div>
                    <?php $pets = $customer_pets[$c['id']] ?? []; ?>
                    <?php if (empty($pets)): ?>
                        <div class="text-sm text-base-content/40 text-center py-4">ยังไม่มีข้อมูลสัตว์เลี้ยง</div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <?php foreach ($pets as $pet): ?>
                                <div
                                    class="flex items-center gap-3 p-3 rounded-xl bg-base-200/40 border border-base-200 hover:border-primary/30 transition-colors">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                        <i data-lucide="paw-print" class="size-4 text-primary"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-sm flex items-center gap-2">
                                            <?php echo htmlspecialchars($pet['name']); ?>
                                            <?php if ($pet['is_aggressive']): ?>
                                                <span class="badge badge-error badge-xs gap-0.5">
                                                    <i data-lucide="alert-triangle" class="size-2.5"></i> ดุ
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-[11px] text-base-content/50">
                                            <?php echo htmlspecialchars($pet['species_name'] ?? '-'); ?>
                                            <?php if ($pet['breed_name']): ?>
                                                •
                                                <?php echo htmlspecialchars($pet['breed_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-base-200 px-6 py-4 flex items-center justify-between bg-base-200/30">
                <form method="dialog">
                    <button class="btn btn-ghost hover:bg-base-300">ปิด</button>
                </form>
                <div class="flex gap-2">
                    <?php if ($c['is_active']): ?>
                        <button type="button" class="btn btn-error btn-sm gap-2 text-white"
                            onclick="openCustomerConfirmModal(<?php echo $c['id']; ?>, 'ban', '<?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>')">
                            <i data-lucide="ban" class="size-4"></i> ระงับบัญชี
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-success btn-sm gap-2 text-white"
                            onclick="openCustomerConfirmModal(<?php echo $c['id']; ?>, 'unban', '<?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>')">
                            <i data-lucide="check-circle" class="size-4"></i> ปลดระงับบัญชี
                        </button>
                    <?php endif; ?>

                    <?php if ($c['booking_count'] > 0): ?>
                        <a href="?page=bookings&search=<?php echo urlencode($c['phone']); ?>"
                            class="btn btn-primary btn-sm gap-2">
                            <i data-lucide="calendar-range" class="size-4"></i>
                            ดูประวัติการจอง
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
<?php endforeach; ?>

<!-- ═══════════ CONFIRM ACTION MODAL ═══════════ -->
<dialog id="modal_confirm_customer" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div id="customer_confirm_icon_wrap"
                class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-error/10">
                <i id="customer_confirm_icon" data-lucide="ban" class="size-7 text-error"></i>
            </div>
            <h3 class="font-bold text-lg mb-2" id="customer_confirm_title">ระงับบัญชีลูกค้า</h3>
            <p class="text-base-content/60" id="customer_confirm_message">ต้องการยืนยันใช่หรือไม่?</p>
        </div>
        <form method="POST" action="?action=customer">
            <input type="hidden" name="customer_id" id="customer_confirm_id">
            <input type="hidden" name="customer_action" id="customer_confirm_action">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_confirm_customer').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" id="customer_confirm_submit_btn" class="btn btn-error gap-2 text-white">
                    <i id="customer_confirm_submit_icon" data-lucide="ban" class="size-4"></i>
                    <span id="customer_confirm_submit_text">ยืนยันระงับบัญชี</span>
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ ADD PET (FROM CUSTOMER) MODAL ═══════════ -->
<dialog id="modal_add_pet_customer" class="modal modal-bottom sm:modal-middle" style="z-index: 9999;">
    <div class="modal-box w-11/12 max-w-2xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="plus" class="size-4 text-primary"></i>
            </div>
            เพิ่มสัตว์เลี้ยงใหม่
        </h3>

        <div class="bg-base-200/50 rounded-lg p-3 mb-4 flex items-center gap-2 border border-base-200">
            <i data-lucide="user" class="size-4 text-base-content/60"></i>
            <span class="text-sm">เจ้าของ: <strong id="add_pet_customer_name_display"
                    class="text-primary"></strong></span>
        </div>

        <form method="POST" action="?action=pets" id="add_pet_customer_form" class="space-y-4">
            <input type="hidden" name="sub_action" value="add">
            <input type="hidden" name="origin_page" value="customers">
            <!-- Automatically attached customer ID -->
            <input type="hidden" name="customer_id" id="add_pet_customer_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อสัตว์เลี้ยง <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="name" id="add_pet_customer_name"
                        class="input input-bordered w-full focus:input-primary" placeholder="เช่น นิกกี้, มอมแมม"
                        required />
                </div>

                <!-- Species -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชนิดสัตว์ <span
                                class="text-error">*</span></span></label>
                    <select name="species_id" id="add_pet_customer_species"
                        class="select select-bordered w-full focus:select-primary" required
                        onchange="updateAddPetCustomerBreedDropdown(this.value)">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($species_list as $sp): ?>
                            <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Breed -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">สายพันธุ์ <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <select name="breed_id" id="add_pet_customer_breed"
                        class="select select-bordered w-full focus:select-primary">
                        <option value="">-- เลือกชนิดสัตว์ก่อน --</option>
                    </select>
                </div>

                <!-- Gender -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">เพศ</span></label>
                    <select name="gender" id="add_pet_customer_gender"
                        class="select select-bordered w-full focus:select-primary">
                        <option value="male">ผู้ (Male)</option>
                        <option value="female">เมีย (Female)</option>
                        <option value="neutered">ทำหมันแล้ว — ผู้ (Neutered)</option>
                        <option value="spayed">ทำหมันแล้ว — เมีย (Spayed)</option>
                        <option value="unknown" selected>ไม่ระบุ</option>
                    </select>
                </div>

                <!-- DOB -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">วันเกิด <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="date" name="dob" id="add_pet_customer_dob"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Weight -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">น้ำหนัก (kg) <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="number" name="weight_kg" id="add_pet_customer_weight" step="0.01" min="0"
                        placeholder="เช่น 4.5" class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Vet Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">คลินิก/หมอประจำตัว <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="text" name="vet_name" id="add_pet_customer_vet_name"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Vet Phone -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">เบอร์คลินิก <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="text" name="vet_phone" id="add_pet_customer_vet_phone"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <!-- Aggressive Checkbox -->
            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" name="is_aggressive" id="add_pet_customer_aggressive"
                        class="checkbox checkbox-error" value="1" />
                    <div>
                        <span class="label-text font-medium">ดุ/ก้าวร้าว</span>
                        <p class="text-xs text-base-content/50 mt-0.5">
                            ทำเครื่องหมายนี้เพื่อแจ้งเตือนพนักงานเรื่องความปลอดภัย</p>
                    </div>
                </label>
            </div>

            <!-- Behavior Note -->
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">หมายเหตุพฤติกรรม <span
                            class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                <textarea name="behavior_note" id="add_pet_customer_behavior_note"
                    class="textarea textarea-bordered w-full focus:textarea-primary h-20" rows="2"
                    placeholder="เช่น กลัวฟ้าร้อง, ชอบกัด, ต้องดูแลพิเศษ..."></textarea>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary gap-2">
                    <i data-lucide="save" class="size-4"></i>
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ ADD CUSTOMER MODAL ═══════════ -->
<dialog id="modal_add_customer" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="user-plus" class="size-4 text-primary"></i>
            </div>
            เพิ่มลูกค้าใหม่
        </h3>

        <form method="POST" action="?action=customer" id="add_customer_form" class="space-y-4">
            <!-- Ensure your backend process_customer.php receives this action flag -->
            <input type="hidden" name="customer_action" value="add">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- First Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อ <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="first_name" id="add_cust_first_name"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>

                <!-- Last Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">นามสกุล <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="last_name" id="add_cust_last_name"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>

                <!-- Phone -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">เบอร์โทรศัพท์ <span
                                class="text-error">*</span></span></label>
                    <input type="tel" name="phone" id="add_cust_phone"
                        class="input input-bordered w-full focus:input-primary" placeholder="เช่น 0812345678"
                        required />
                </div>

                <!-- Email -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">อีเมล <span
                                class="text-error">*</span></span></label>
                    <input type="email" name="email" id="add_cust_email"
                        class="input input-bordered w-full focus:input-primary" placeholder="เช่น user@example.com"
                        required />
                </div>

                <!-- Address (Full width) -->
                <div class="form-control sm:col-span-2">
                    <label class="label"><span class="label-text font-medium">ที่อยู่ <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <textarea name="address" id="add_cust_address"
                        class="textarea textarea-bordered w-full focus:textarea-primary h-20" rows="2"></textarea>
                </div>

                <div class="sm:col-span-2 mt-2 pt-2 border-t border-base-200">
                    <h4 class="text-sm font-semibold mb-2">ข้อมูลติดต่อฉุกเฉิน</h4>
                </div>

                <!-- Emergency Contact Name -->
                <div class="form-control">
                    <label class="label pt-0"><span class="label-text font-medium">ชื่อบุคคลติดต่อฉุกเฉิน <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="text" name="emergency_contact_name" id="add_cust_emergency_name"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Emergency Contact Phone -->
                <div class="form-control">
                    <label class="label pt-0"><span class="label-text font-medium">เบอร์ติดต่อฉุกเฉิน <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="tel" name="emergency_contact_phone" id="add_cust_emergency_phone"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <div class="sm:col-span-2 text-xs text-error mt-1">
                    <i data-lucide="info" class="size-3 inline-block -mt-0.5 mr-1"></i>
                    รหัสผ่านเริ่มต้นสำหรับลูกค้าจะใช้เบอร์โทรศัพท์ในการเข้าสู่ระบบ
                    หลังจากเพิ่มลูกค้าแล้วแนะนำให้แจ้งลูกค้าให้เปลี่ยนรหัสผ่านเพื่อความปลอดภัยของบัญชี
                </div>

            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary gap-2">
                    <i data-lucide="user-check" class="size-4"></i>
                    เพิ่มลูกค้า
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    // Breeds data from PHP for add pet modal
    const breedsBySpecies = <?php echo json_encode($breeds_by_species); ?>;

    function openAddCustomerModal() {
        document.getElementById('add_customer_form').reset();
        document.getElementById('modal_add_customer').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openCustomerConfirmModal(customerId, action, customerName) {
        // Populate hidden fields
        document.getElementById('customer_confirm_id').value = customerId;
        document.getElementById('customer_confirm_action').value = action;

        const titleEl = document.getElementById('customer_confirm_title');
        const msgEl = document.getElementById('customer_confirm_message');
        const btn = document.getElementById('customer_confirm_submit_btn');
        const iconWrap = document.getElementById('customer_confirm_icon_wrap');
        const icon = document.getElementById('customer_confirm_icon');
        const submitIcon = document.getElementById('customer_confirm_submit_icon');
        const submitText = document.getElementById('customer_confirm_submit_text');

        if (action === 'ban') {
            titleEl.textContent = 'ระงับบัญชีลูกค้า';
            msgEl.innerHTML = 'ยืนยัน <strong class="text-error">"การระงับบัญชี"</strong> ลูกค้า <strong>' + customerName + '</strong> ใช่หรือไม่? ลูกค้าจะไม่สามารถเข้าสู่ระบบและจองห้องพักได้';

            btn.className = 'btn gap-2 btn-error text-white';
            iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-error/10';

            icon.setAttribute('data-lucide', 'ban');
            icon.className = 'size-7 text-error';

            submitIcon.setAttribute('data-lucide', 'ban');
            submitText.textContent = 'ยืนยันระงับบัญชี';
        } else {
            titleEl.textContent = 'ปลดระงับบัญชีลูกค้า';
            msgEl.innerHTML = 'ยืนยัน <strong class="text-success">"การปลดระงับบัญชี"</strong> ลูกค้า <strong>' + customerName + '</strong> ใช่หรือไม่? ลูกค้าจะกลับมาใช้งานได้ตามปกติ';

            btn.className = 'btn gap-2 btn-success text-white';
            iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-success/10';

            icon.setAttribute('data-lucide', 'check-circle-2');
            icon.className = 'size-7 text-success';

            submitIcon.setAttribute('data-lucide', 'check');
            submitText.textContent = 'ยืนยันปลดระงับ';
        }

        // Must re-init lucide icons since we changed data-lucide attribute dynamically
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Close the previous modal if it is open (so we don't stack dialogs improperly)
        document.getElementById('modal_customer_' + customerId).close();
        document.getElementById('modal_confirm_customer').showModal();
    }

    function updateAddPetCustomerBreedDropdown(speciesId) {
        const breedSelect = document.getElementById('add_pet_customer_breed');
        breedSelect.innerHTML = '<option value="">-- เลือก --</option>';

        if (speciesId && breedsBySpecies[speciesId]) {
            breedsBySpecies[speciesId].forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                breedSelect.appendChild(opt);
            });
        }
    }

    function openAddPetCustomerModal(customerId, customerName) {
        // Reset form
        document.getElementById('add_pet_customer_form').reset();
        document.getElementById('add_pet_customer_breed').innerHTML = '<option value="">-- เลือกชนิดสัตว์ก่อน --</option>';

        // Set customer context
        document.getElementById('add_pet_customer_id').value = customerId;
        document.getElementById('add_pet_customer_name_display').textContent = customerName;

        // Close detail modal and open add pet modal
        document.getElementById('modal_customer_' + customerId).close();
        document.getElementById('modal_add_pet_customer').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
</script>