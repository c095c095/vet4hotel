<?php
// ═══════════════════════════════════════════════════════════
// ADMIN BOOKINGS PAGE UI — VET4 HOTEL
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/bookings_data.php';

// Helper for badges
function booking_status_badge_ui($status)
{
    $map = [
        'pending_payment' => ['รอชำระ', 'badge-warning'],
        'verifying_payment' => ['ตรวจสอบ', 'badge-info'],
        'confirmed' => ['ยืนยันแล้ว', 'badge-success'],
        'checked_in' => ['เข้าพักอยู่', 'badge-primary'],
        'checked_out' => ['เช็คเอาท์', 'badge-ghost'],
        'cancelled' => ['ยกเลิก', 'badge-error'],
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
                    <i data-lucide="calendar-range" class="size-5 text-primary"></i>
                </div>
                จัดการการจอง
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">
                แสดงและค้นหารายการจองทั้งหมดในระบบ
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="?page=booking_create" class="btn btn-primary shadow-sm hover:shadow-md transition-shadow gap-2">
                <i data-lucide="plus" class="size-4"></i>
                สร้างการจองใหม่
            </a>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="index.php" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="bookings">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" placeholder="Ref, ชื่อ, นามสกุล, เบอร์โทร..." />
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

                <!-- Date Filter -->
                <div class="form-control w-full xl:w-56">
                    <label class="label pt-0"><span class="label-text font-medium">วันเข้าพัก (คาบเกี่ยว)</span></label>
                    <input type="date" name="date"
                        class="input input-bordered w-full focus:input-primary transition-colors"
                        value="<?php echo htmlspecialchars($date_filter); ?>" />
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2 mt-2 xl:mt-0">
                    <button type="submit" class="btn btn-primary gap-2 w-full sm:w-auto">
                        <i data-lucide="filter" class="size-4"></i>
                        กรองข้อมูล
                    </button>
                    <a href="?page=bookings"
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
                            <a href="?page=bookings&status=<?php echo $key; ?>"
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
                        <th class="font-medium">Booking Ref</th>
                        <th class="font-medium">ลูกค้า</th>
                        <th class="font-medium text-center">ห้อง</th>
                        <th class="font-medium text-center">วันเข้าพัก</th>
                        <th class="font-medium text-right">ยอดรวม (฿)</th>
                        <th class="font-medium text-center">สถานะ</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-base-content/50">
                                <i data-lucide="search-x" class="size-10 mx-auto mb-3 opacity-30"></i>
                                ไม่มีข้อมูลการจองที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr class="hover group">
                                <td class="font-mono text-sm font-semibold text-primary">
                                    <?php echo htmlspecialchars($b['booking_ref']); ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder hidden sm:flex">
                                            <div
                                                class="bg-base-300 text-base-content w-8 h-8 rounded-full flex items-center justify-center">
                                                <span class="text-xs">
                                                    <?php echo mb_substr($b['first_name'], 0, 1); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-medium">
                                                <?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?>
                                            </div>
                                            <div class="text-xs text-base-content/50">
                                                <?php echo htmlspecialchars($b['phone']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-ghost badge-sm">
                                        <?php echo $b['room_count']; ?> ห้อง
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($b['first_checkin'] && $b['last_checkout']): ?>
                                        <div class="text-sm">
                                            <?php echo date('d/m/y', strtotime($b['first_checkin'])); ?>
                                            <i data-lucide="arrow-right" class="size-3 inline mx-1 text-base-content/40"></i>
                                            <?php echo date('d/m/y', strtotime($b['last_checkout'])); ?>
                                        </div>
                                        <div class="text-[10px] text-base-content/40 mt-0.5">
                                            <?php
                                            // Calc nights
                                            $diff = strtotime($b['last_checkout']) - strtotime($b['first_checkin']);
                                            echo round($diff / (60 * 60 * 24)) . ' คืน';
                                            ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-base-content/40 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-medium text-sm">
                                    <?php echo number_format($b['net_amount'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo booking_status_badge_ui($b['status']); ?>
                                </td>
                                <td class="text-center">
                                    <a href="?page=booking_detail&id=<?php echo $b['id']; ?>"
                                        class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-primary tooltip tooltip-left"
                                        data-tip="ดูรายละเอียด">
                                        <i data-lucide="eye" class="size-4"></i>
                                    </a>
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
                    // Keep existing GET params
                    $query_string = $_GET;
                    unset($query_string['p']);
                    $base_url = '?' . http_build_query($query_string) . '&p=';

                    // Prev
                    if ($page > 1): ?>
                        <a href="<?php echo $base_url . ($page - 1); ?>"
                            class="join-item btn btn-sm bg-base-100 hover:bg-base-200">«</a>
                    <?php else: ?>
                        <button class="join-item btn btn-sm bg-base-100 btn-disabled">«</button>
                    <?php endif;

                    // Page numbers
                    for ($i = 1; $i <= $total_pages; $i++):
                        // Show max 5 pages around current
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

                    // Next
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