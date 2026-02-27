<?php
// ═══════════════════════════════════════════════════════════
// ADMIN CUSTOMERS PAGE UI — VET4 HOTEL
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/customers_data.php';
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ HEADER ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="size-5 text-primary"></i>
                </div>
                จัดการลูกค้า
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">
                ดูข้อมูลลูกค้า สัตว์เลี้ยง และประวัติการจองทั้งหมด
            </p>
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
            <form action="index.php" method="GET" class="flex flex-col xl:flex-row gap-4">
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
                    <div class="text-sm font-semibold mb-3 border-b border-base-200 pb-2 flex items-center gap-2">
                        <i data-lucide="paw-print" class="size-4 text-primary"></i>
                        สัตว์เลี้ยง (
                        <?php echo $c['pet_count']; ?>)
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
                <?php if ($c['booking_count'] > 0): ?>
                    <a href="?page=bookings&search=<?php echo urlencode($c['phone']); ?>" class="btn btn-primary btn-sm gap-2">
                        <i data-lucide="calendar-range" class="size-4"></i>
                        ดูประวัติการจอง
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
<?php endforeach; ?>