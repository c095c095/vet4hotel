<?php
// ═══════════════════════════════════════════════════════════
// ADMIN DASHBOARD HOME — VET4 HOTEL
// Main landing page with KPI cards, alerts, and activity panels
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/dashboard_data.php';

// Status badge helper
function booking_status_badge($status)
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

// Status color map for distribution bar
$status_colors = [
    'pending_payment' => ['bg-warning', 'รอชำระ'],
    'verifying_payment' => ['bg-info', 'ตรวจสอบ'],
    'confirmed' => ['bg-success', 'ยืนยันแล้ว'],
    'checked_in' => ['bg-primary', 'เข้าพักอยู่'],
    'checked_out' => ['bg-base-300', 'เช็คเอาท์'],
    'cancelled' => ['bg-error', 'ยกเลิก'],
];

$greeting = '';
$hour = (int) date('H');
if ($hour < 12)
    $greeting = 'สวัสดีตอนเช้า';
elseif ($hour < 17)
    $greeting = 'สวัสดีตอนบ่าย';
else
    $greeting = 'สวัสดีตอนเย็น';

// Thai day names for upcoming check-ins
$thai_days_short = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ WELCOME HEADER + QUICK ACTIONS ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content">
                <?php echo $greeting; ?>,
                <?php echo sanitize(explode(' ', $employee_name)[0]); ?>
            </h1>
            <p class="text-base-content/60 text-sm mt-1">
                <i data-lucide="calendar" class="inline size-3.5 -mt-0.5"></i>
                <?php echo date('l, j F Y'); ?> —
                <?php echo SITE_NAME; ?> Admin Panel
            </p>
        </div>
        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-2">
            <a href="?page=bookings" class="btn btn-primary btn-sm gap-1.5 shadow-sm">
                <i data-lucide="calendar-plus" class="size-4"></i>
                การจอง
            </a>
            <a href="?page=payments"
                class="btn btn-sm gap-1.5 border-warning/30 bg-warning/10 text-warning hover:bg-warning/20 shadow-sm">
                <i data-lucide="credit-card" class="size-4"></i>
                ตรวจสอบชำระ
            </a>
            <a href="?page=care_tasks"
                class="btn btn-sm gap-1.5 border-accent/30 bg-accent/10 text-accent hover:bg-accent/20 shadow-sm">
                <i data-lucide="clipboard-check" class="size-4"></i>
                งานดูแล
            </a>
        </div>
    </div>

    <!-- ═══════════ AGGRESSIVE PET ALERT (Red Flag) ═══════════ -->
    <?php if (!empty($aggressive_pets)): ?>
        <div class="alert bg-error/10 border-2 border-error/30 shadow-lg">
            <div class="flex flex-col gap-3 w-full">
                <div class="flex items-center gap-2">
                    <i data-lucide="triangle-alert" class="size-6 text-error animate-pulse"></i>
                    <span class="font-bold text-error text-lg">⚠️ แจ้งเตือน: สัตว์เลี้ยงที่มีพฤติกรรมดุร้าย (
                        <?php echo count($aggressive_pets); ?> ตัว)
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <?php foreach ($aggressive_pets as $ap): ?>
                        <div class="flex items-center gap-3 bg-error/5 border border-error/20 rounded-xl p-3">
                            <div class="w-10 h-10 rounded-lg bg-error/20 flex items-center justify-center shrink-0">
                                <i data-lucide="alert-triangle" class="size-5 text-error"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-error text-sm truncate">
                                    <?php echo sanitize($ap['pet_name']); ?>
                                    <span class="badge badge-xs badge-error">ดุร้าย</span>
                                </p>
                                <p class="text-xs text-base-content/60">
                                    ห้อง
                                    <?php echo sanitize($ap['room_number']); ?> •
                                    <?php echo sanitize($ap['species_name']); ?>
                                    <?php if ($ap['breed_name']): ?>(
                                        <?php echo sanitize($ap['breed_name']); ?>)
                                    <?php endif; ?>
                                </p>
                                <?php if ($ap['behavior_note']): ?>
                                    <p class="text-xs text-error/70 mt-0.5 truncate">📝
                                        <?php echo sanitize($ap['behavior_note']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[10px] text-base-content/50">เจ้าของ</p>
                                <p class="font-medium">
                                    <?php echo sanitize($ap['owner_first'] . ' ' . $ap['owner_last']); ?>
                                </p>
                                <p class="text-[10px] text-base-content/50">
                                    <?php echo sanitize($ap['owner_phone']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══════════ PENDING PAYMENTS ALERT ═══════════ -->
    <?php if ($kpi_pending_payments > 0): ?>
        <div class="alert alert-warning shadow-sm">
            <i data-lucide="clock" class="size-5"></i>
            <span>มี <strong>
                    <?php echo $kpi_pending_payments; ?>
                </strong> รายการชำระเงินรอตรวจสอบ</span>
            <a href="?page=payments" class="btn btn-sm btn-light gap-1">
                <i data-lucide="external-link" class="size-3.5"></i>
                ตรวจสอบ
            </a>
        </div>
    <?php endif; ?>

    <!-- ═══════════ KPI STAT CARDS ═══════════ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Today's Bookings -->
        <div class="card border-0 shadow-sm overflow-hidden relative group hover:shadow-lg transition-all duration-300">
            <div
                class="absolute inset-0 bg-linear-to-br from-primary/5 via-primary/10 to-primary/5 group-hover:from-primary/10 group-hover:to-primary/15 transition-all duration-300">
            </div>
            <div class="card-body p-5 relative">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-primary/70 uppercase tracking-wider">จองวันนี้</p>
                        <p class="text-4xl font-bold text-primary mt-1.5 tracking-tight">
                            <?php echo $kpi_bookings_today; ?>
                        </p>
                        <p class="text-base-content/50 text-xs mt-1.5">รายการจองใหม่</p>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-primary/15 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="calendar-plus" class="size-7 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Check-ins -->
        <div class="card border-0 shadow-sm overflow-hidden relative group hover:shadow-lg transition-all duration-300">
            <div
                class="absolute inset-0 bg-linear-to-br from-success/5 via-success/10 to-success/5 group-hover:from-success/10 group-hover:to-success/15 transition-all duration-300">
            </div>
            <div class="card-body p-5 relative">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-success/70 uppercase tracking-wider">เข้าพักอยู่</p>
                        <p class="text-4xl font-bold text-success mt-1.5 tracking-tight">
                            <?php echo $kpi_active_checkins; ?>
                        </p>
                        <p class="text-base-content/50 text-xs mt-1.5">สัตว์เลี้ยงที่กำลังพัก</p>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-success/15 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="hotel" class="size-7 text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="card border-0 shadow-sm overflow-hidden relative group hover:shadow-lg transition-all duration-300">
            <div
                class="absolute inset-0 bg-linear-to-br from-warning/5 via-warning/10 to-warning/5 group-hover:from-warning/10 group-hover:to-warning/15 transition-all duration-300">
            </div>
            <div class="card-body p-5 relative">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-warning/70 uppercase tracking-wider">รอตรวจสอบ</p>
                        <p
                            class="text-4xl font-bold <?php echo $kpi_pending_payments > 0 ? 'text-warning' : 'text-base-content/60'; ?> mt-1.5 tracking-tight">
                            <?php echo $kpi_pending_payments; ?>
                        </p>
                        <p class="text-base-content/50 text-xs mt-1.5">การชำระเงิน</p>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-warning/15 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="credit-card" class="size-7 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="card border-0 shadow-sm overflow-hidden relative group hover:shadow-lg transition-all duration-300">
            <div
                class="absolute inset-0 bg-linear-to-br from-info/5 via-info/10 to-info/5 group-hover:from-info/10 group-hover:to-info/15 transition-all duration-300">
            </div>
            <div class="card-body p-5 relative">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-info/70 uppercase tracking-wider">รายได้เดือนนี้</p>
                        <p class="text-3xl font-bold text-base-content mt-1.5 tracking-tight">
                            ฿<?php echo number_format($kpi_monthly_revenue, 0); ?>
                        </p>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <?php if ($revenue_change_pct > 0): ?>
                                <span class="badge badge-xs badge-success gap-0.5">
                                    <i data-lucide="trending-up" class="size-3"></i>
                                    +<?php echo $revenue_change_pct; ?>%
                                </span>
                            <?php elseif ($revenue_change_pct < 0): ?>
                                <span class="badge badge-xs badge-error gap-0.5">
                                    <i data-lucide="trending-down" class="size-3"></i>
                                    <?php echo $revenue_change_pct; ?>%
                                </span>
                            <?php else: ?>
                                <span class="badge badge-xs badge-ghost gap-0.5">
                                    <i data-lucide="minus" class="size-3"></i> 0%
                                </span>
                            <?php endif; ?>
                            <span class="text-base-content/40 text-[10px]">vs เดือนที่แล้ว</span>
                        </div>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-info/15 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="trending-up" class="size-7 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ SECONDARY STATS + SATISFACTION ═══════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 text-center hover:shadow-sm transition-shadow">
            <i data-lucide="users" class="size-6 text-primary mx-auto mb-1.5 opacity-70"></i>
            <p class="text-2xl font-bold text-base-content"><?php echo $total_customers; ?></p>
            <p class="text-[11px] text-base-content/50 font-medium">ลูกค้าทั้งหมด</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 text-center hover:shadow-sm transition-shadow">
            <i data-lucide="paw-print" class="size-6 text-secondary mx-auto mb-1.5 opacity-70"></i>
            <p class="text-2xl font-bold text-base-content"><?php echo $total_pets; ?></p>
            <p class="text-[11px] text-base-content/50 font-medium">สัตว์เลี้ยง</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 text-center hover:shadow-sm transition-shadow">
            <i data-lucide="door-open" class="size-6 text-success mx-auto mb-1.5 opacity-70"></i>
            <p class="text-2xl font-bold text-success"><?php echo $available_rooms; ?></p>
            <p class="text-[11px] text-base-content/50 font-medium">ห้องว่าง</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 text-center hover:shadow-sm transition-shadow">
            <i data-lucide="wrench" class="size-6 text-warning mx-auto mb-1.5 opacity-70"></i>
            <p class="text-2xl font-bold text-warning"><?php echo $maintenance_rooms; ?></p>
            <p class="text-[11px] text-base-content/50 font-medium">ซ่อมบำรุง</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 text-center hover:shadow-sm transition-shadow">
            <i data-lucide="banknote" class="size-6 text-info mx-auto mb-1.5 opacity-70"></i>
            <p class="text-2xl font-bold text-base-content">฿<?php echo number_format($today_revenue, 0); ?></p>
            <p class="text-[11px] text-base-content/50 font-medium">รายได้วันนี้</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 text-center hover:shadow-sm transition-shadow">
            <i data-lucide="star" class="size-6 text-warning mx-auto mb-1.5 opacity-70"></i>
            <p class="text-2xl font-bold text-base-content">
                <?php echo $avg_rating > 0 ? $avg_rating : '-'; ?>
                <span class="text-xs text-base-content/40 font-normal">/5</span>
            </p>
            <p class="text-[11px] text-base-content/50 font-medium">
                คะแนนรีวิว
                <?php if ($total_reviews > 0): ?>
                    (<?php echo $total_reviews; ?>)
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- ═══════════ ROOM OCCUPANCY + BOOKING DISTRIBUTION ═══════════ -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        <!-- Room Occupancy Overview — 2 cols -->
        <div class="xl:col-span-2 card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                            <i data-lucide="building-2" class="size-4 text-primary"></i>
                        </div>
                        <h2 class="font-bold text-base-content">ภาพรวมห้องพัก</h2>
                    </div>
                    <span class="badge badge-primary badge-outline text-xs">
                        <?php echo $occupancy_pct; ?>% ถูกใช้งาน
                    </span>
                </div>

                <!-- Main progress bar -->
                <div class="w-full bg-base-200 rounded-full h-4 overflow-hidden mb-3">
                    <div class="h-full rounded-full bg-linear-to-r from-primary to-secondary transition-all duration-700"
                        style="width: <?php echo $occupancy_pct; ?>%"></div>
                </div>
                <div class="flex items-center justify-between text-sm text-base-content/50 mb-5">
                    <span><?php echo $occupied_rooms; ?> ห้องที่มีผู้เข้าพัก</span>
                    <span><?php echo $total_active_rooms; ?> ห้องทั้งหมด</span>
                </div>

                <!-- Room type breakdown -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($room_type_occupancy as $rto):
                        $rt_total = (int) $rto['total_rooms'];
                        $rt_occ = (int) $rto['occupied'];
                        $rt_pct = $rt_total > 0 ? round(($rt_occ / $rt_total) * 100) : 0;
                        $rt_avail = $rt_total - $rt_occ;
                        ?>
                        <div class="border border-base-200 rounded-xl p-3 hover:bg-base-200/30 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <span
                                    class="font-medium text-sm truncate"><?php echo sanitize($rto['room_type_name']); ?></span>
                                <span
                                    class="text-xs text-base-content/50"><?php echo $rt_occ; ?>/<?php echo $rt_total; ?></span>
                            </div>
                            <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 <?php echo $rt_pct >= 80 ? 'bg-error' : ($rt_pct >= 50 ? 'bg-warning' : 'bg-success'); ?>"
                                    style="width: <?php echo $rt_pct; ?>%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1.5 text-[11px] text-base-content/40">
                                <span><?php echo $rt_pct; ?>% ใช้งาน</span>
                                <span class="text-success"><?php echo $rt_avail; ?> ว่าง</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap gap-4 mt-4 text-sm border-t border-base-200 pt-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-primary"></span>
                        <span class="text-base-content/60">ใช้งาน (<?php echo $occupied_rooms; ?>)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-success"></span>
                        <span class="text-base-content/60">ว่าง (<?php echo $available_rooms; ?>)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-warning"></span>
                        <span class="text-base-content/60">ซ่อมบำรุง (<?php echo $maintenance_rooms; ?>)</span>
                    </div>
                    <?php if (isset($room_status_dist['out_of_service'])): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-error"></span>
                            <span class="text-base-content/60">ปิดให้บริการ
                                (<?php echo $room_status_dist['out_of_service']; ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Booking Status Distribution + Care Progress — 1 col -->
        <div class="space-y-4">
            <!-- Booking Status -->
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-secondary/10 flex items-center justify-center">
                            <i data-lucide="bar-chart-3" class="size-4 text-secondary"></i>
                        </div>
                        <h2 class="font-bold text-base-content text-sm">สถานะการจองทั้งหมด</h2>
                    </div>
                    <?php if ($total_bookings_all > 0): ?>
                        <!-- Horizontal stacked bar -->
                        <div class="w-full flex rounded-full h-5 overflow-hidden mb-3">
                            <?php foreach ($status_colors as $st => $info):
                                $cnt = $booking_status_dist[$st] ?? 0;
                                if ($cnt == 0)
                                    continue;
                                $pct = round(($cnt / $total_bookings_all) * 100, 1);
                                ?>
                                <div class="<?php echo $info[0]; ?> h-full transition-all duration-500 opacity-80 hover:opacity-100"
                                    style="width: <?php echo $pct; ?>%" title="<?php echo $info[1]; ?>: <?php echo $cnt; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Legend items -->
                        <div class="space-y-1.5">
                            <?php foreach ($status_colors as $st => $info):
                                $cnt = $booking_status_dist[$st] ?? 0;
                                if ($cnt == 0)
                                    continue;
                                ?>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full <?php echo $info[0]; ?> opacity-80"></span>
                                        <span class="text-base-content/70"><?php echo $info[1]; ?></span>
                                    </div>
                                    <span class="font-semibold text-base-content"><?php echo $cnt; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 text-base-content/40">
                            <p class="text-sm">ยังไม่มีข้อมูลการจอง</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Care Task Progress -->
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center">
                            <i data-lucide="clipboard-check" class="size-4 text-accent"></i>
                        </div>
                        <h2 class="font-bold text-base-content text-sm">ความคืบหน้างานดูแลวันนี้</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Circular progress -->
                        <div class="relative shrink-0">
                            <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                                <circle cx="40" cy="40" r="34" fill="none" stroke="currentColor" class="text-base-200"
                                    stroke-width="8" />
                                <circle cx="40" cy="40" r="34" fill="none"
                                    class="<?php echo $care_pct >= 80 ? 'text-success' : ($care_pct >= 50 ? 'text-warning' : 'text-accent'); ?>"
                                    stroke="currentColor" stroke-width="8" stroke-linecap="round"
                                    stroke-dasharray="<?php echo 2 * M_PI * 34; ?>"
                                    stroke-dashoffset="<?php echo 2 * M_PI * 34 * (1 - $care_pct / 100); ?>" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-bold text-base-content"><?php echo $care_pct; ?>%</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-base-content">
                                <?php echo $care_completed; ?><span class="text-base-content/40 text-sm font-normal"> /
                                    <?php echo $care_total; ?></span>
                            </p>
                            <p class="text-xs text-base-content/50">งานที่เสร็จแล้ว</p>
                            <?php if ($care_total - $care_completed > 0): ?>
                                <a href="?page=care_tasks" class="text-xs text-accent hover:underline mt-1 inline-block">
                                    ดูงานที่เหลือ (<?php echo $care_total - $care_completed; ?>)
                                    <i data-lucide="arrow-right" class="inline size-3"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Activity Summary -->
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center">
                            <i data-lucide="activity" class="size-4 text-info"></i>
                        </div>
                        <h2 class="font-bold text-base-content text-sm">กิจกรรมวันนี้</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 border border-base-200 rounded-xl">
                            <p class="text-xl font-bold text-success"><?php echo count($todays_checkins); ?></p>
                            <p class="text-[11px] text-base-content/50">เช็คอิน</p>
                        </div>
                        <div class="text-center p-3 border border-base-200 rounded-xl">
                            <p class="text-xl font-bold text-warning"><?php echo count($todays_checkouts); ?></p>
                            <p class="text-[11px] text-base-content/50">เช็คเอาท์</p>
                        </div>
                        <div class="text-center p-3 border border-base-200 rounded-xl">
                            <p class="text-xl font-bold text-info"><?php echo $today_updates_count; ?></p>
                            <p class="text-[11px] text-base-content/50">อัปเดตรายวัน</p>
                        </div>
                        <div class="text-center p-3 border border-base-200 rounded-xl">
                            <p
                                class="text-xl font-bold <?php echo $pending_refunds > 0 ? 'text-error' : 'text-base-content'; ?>">
                                <?php echo $pending_refunds; ?>
                            </p>
                            <p class="text-[11px] text-base-content/50">คืนเงินรอดำเนินการ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ CHECK-IN/OUT ACTIVITY TIMELINE ═══════════ -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        <!-- Check-in Today -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                            <i data-lucide="log-in" class="size-4 text-success"></i>
                        </div>
                        <h2 class="font-bold text-base-content text-sm">เช็คอินวันนี้</h2>
                    </div>
                    <span class="badge badge-success badge-sm">
                        <?php echo count($todays_checkins); ?> รายการ
                    </span>
                </div>
                <?php if (empty($todays_checkins)): ?>
                    <div class="text-center py-8 text-base-content/40">
                        <i data-lucide="calendar-x" class="size-10 mx-auto mb-2 opacity-40"></i>
                        <p class="text-sm">ไม่มีเช็คอินวันนี้</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($todays_checkins as $ci): ?>
                            <a href="?page=booking_detail&id=<?php echo $ci['booking_id']; ?>"
                                class="flex items-center gap-3 p-3 rounded-xl border border-base-200 hover:bg-success/5 hover:border-success/20 transition-all group">
                                <div
                                    class="w-9 h-9 rounded-lg bg-success/10 flex items-center justify-center shrink-0 group-hover:bg-success/20">
                                    <span
                                        class="text-xs font-bold text-success"><?php echo mb_substr($ci['first_name'], 0, 1); ?></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium truncate">
                                        <?php echo sanitize($ci['first_name'] . ' ' . $ci['last_name']); ?>
                                    </p>
                                    <p class="text-[11px] text-base-content/50 truncate">
                                        <span class="font-mono"><?php echo sanitize($ci['booking_ref']); ?></span>
                                        • ห้อง <?php echo sanitize($ci['room_number']); ?>
                                        <?php if ($ci['pet_names']): ?> • 🐾
                                            <?php echo sanitize($ci['pet_names']); ?>        <?php endif; ?>
                                    </p>
                                </div>
                                <?php echo booking_status_badge($ci['booking_status']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Check-out Today -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                            <i data-lucide="log-out" class="size-4 text-warning"></i>
                        </div>
                        <h2 class="font-bold text-base-content text-sm">เช็คเอาท์วันนี้</h2>
                    </div>
                    <span class="badge badge-warning badge-sm">
                        <?php echo count($todays_checkouts); ?> รายการ
                    </span>
                </div>
                <?php if (empty($todays_checkouts)): ?>
                    <div class="text-center py-8 text-base-content/40">
                        <i data-lucide="calendar-x" class="size-10 mx-auto mb-2 opacity-40"></i>
                        <p class="text-sm">ไม่มีเช็คเอาท์วันนี้</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($todays_checkouts as $co): ?>
                            <a href="?page=booking_detail&id=<?php echo $co['booking_id']; ?>"
                                class="flex items-center gap-3 p-3 rounded-xl border border-base-200 hover:bg-warning/5 hover:border-warning/20 transition-all group">
                                <div
                                    class="w-9 h-9 rounded-lg bg-warning/10 flex items-center justify-center shrink-0 group-hover:bg-warning/20">
                                    <span
                                        class="text-xs font-bold text-warning"><?php echo mb_substr($co['first_name'], 0, 1); ?></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium truncate">
                                        <?php echo sanitize($co['first_name'] . ' ' . $co['last_name']); ?>
                                    </p>
                                    <p class="text-[11px] text-base-content/50 truncate">
                                        <span class="font-mono"><?php echo sanitize($co['booking_ref']); ?></span>
                                        • ห้อง <?php echo sanitize($co['room_number']); ?>
                                        <?php if ($co['pet_names']): ?> • 🐾
                                            <?php echo sanitize($co['pet_names']); ?>        <?php endif; ?>
                                    </p>
                                </div>
                                <?php echo booking_status_badge($co['booking_status']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════ PENDING CARE TASKS + UPCOMING CHECK-INS ═══════════ -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <!-- Pending Care Tasks — 2 cols -->
        <div class="xl:col-span-2 card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center">
                            <i data-lucide="clipboard-check" class="size-4 text-accent"></i>
                        </div>
                        <h2 class="font-bold text-base-content">งานดูแลที่ต้องทำวันนี้</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-accent badge-outline badge-sm">
                            <?php echo count($pending_care_tasks); ?> ค้าง
                        </span>
                        <a href="?page=care_tasks" class="btn btn-ghost btn-xs gap-1 text-accent">
                            ดูทั้งหมด <i data-lucide="arrow-right" class="size-3"></i>
                        </a>
                    </div>
                </div>
                <?php if (empty($pending_care_tasks)): ?>
                    <div class="text-center py-10 text-base-content/40">
                        <i data-lucide="check-circle-2" class="size-12 mx-auto mb-2 text-success/40"></i>
                        <p class="font-medium text-success/60">เยี่ยม! ไม่มีงานค้าง</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php foreach ($pending_care_tasks as $task): ?>
                            <div
                                class="flex items-center gap-3 border border-base-200 rounded-xl p-3 hover:bg-base-200/30 transition-colors <?php echo $task['is_aggressive'] ? 'border-error/30 bg-error/5' : ''; ?>">
                                <div
                                    class="w-8 h-8 rounded-lg <?php echo $task['is_aggressive'] ? 'bg-error/20' : 'bg-primary/10'; ?> flex items-center justify-center shrink-0">
                                    <?php if ($task['is_aggressive']): ?>
                                        <i data-lucide="alert-triangle" class="size-4 text-error"></i>
                                    <?php else: ?>
                                        <i data-lucide="circle" class="size-4 text-primary/50"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium truncate">
                                        <?php echo sanitize($task['description']); ?>
                                    </p>
                                    <p class="text-xs text-base-content/50">
                                        🐾 <?php echo sanitize($task['pet_name']); ?>
                                        • ห้อง <?php echo sanitize($task['room_number']); ?>
                                        • <?php echo sanitize($task['task_type_name']); ?>
                                    </p>
                                </div>
                                <?php if ($task['is_aggressive']): ?>
                                    <span class="badge badge-xs badge-error shrink-0">ดุร้าย</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Check-ins (7 days) -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <i data-lucide="calendar-days" class="size-4 text-primary"></i>
                    </div>
                    <h2 class="font-bold text-base-content text-sm">เช็คอินที่จะถึง (7 วัน)</h2>
                </div>
                <?php if (empty($upcoming_checkins)): ?>
                    <div class="text-center py-8 text-base-content/40">
                        <i data-lucide="calendar-check" class="size-10 mx-auto mb-2 opacity-40"></i>
                        <p class="text-sm">ไม่มีเช็คอินที่จะถึง</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($upcoming_checkins as $uc):
                            $uc_date = strtotime($uc['ci_date']);
                            $day_name = $thai_days_short[(int) date('w', $uc_date)];
                            $is_today = $uc['ci_date'] === $today;
                            ?>
                            <div
                                class="flex items-center justify-between p-3 rounded-xl border <?php echo $is_today ? 'border-primary/30 bg-primary/5' : 'border-base-200'; ?> hover:bg-base-200/30 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="text-center min-w-12">
                                        <p class="text-xs text-base-content/50"><?php echo $day_name; ?></p>
                                        <p
                                            class="text-lg font-bold <?php echo $is_today ? 'text-primary' : 'text-base-content'; ?>">
                                            <?php echo date('j', $uc_date); ?>
                                        </p>
                                        <p class="text-[10px] text-base-content/40"><?php echo date('M', $uc_date); ?></p>
                                    </div>
                                    <?php if ($is_today): ?>
                                        <span class="badge badge-primary badge-xs">วันนี้</span>
                                    <?php endif; ?>
                                </div>
                                <div class="badge badge-primary badge-outline badge-sm gap-1">
                                    <i data-lucide="log-in" class="size-3"></i>
                                    <?php echo $uc['count']; ?> รายการ
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════ RECENT BOOKINGS TABLE ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <i data-lucide="list" class="size-4 text-primary"></i>
                    </div>
                    <h2 class="font-bold text-base-content">การจองล่าสุด</h2>
                </div>
                <a href="?page=bookings" class="btn btn-ghost btn-xs gap-1 text-primary">
                    ดูทั้งหมด
                    <i data-lucide="arrow-right" class="size-3"></i>
                </a>
            </div>
            <?php if (empty($recent_bookings)): ?>
                <div class="text-center py-10 text-base-content/40">
                    <i data-lucide="inbox" class="size-12 mx-auto mb-2 opacity-40"></i>
                    <p class="text-sm">ยังไม่มีการจอง</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto -mx-2">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-base-content/50">
                                <th>ลูกค้า</th>
                                <th>Ref</th>
                                <th class="text-center">ห้อง</th>
                                <th class="text-center">วันเข้าพัก</th>
                                <th class="text-end">ยอดรวม</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">สร้างเมื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $rb): ?>
                                <tr class="hover:bg-base-200/30 cursor-pointer transition-colors"
                                    onclick="window.location='?page=booking_detail&id=<?php echo $rb['booking_id']; ?>'">
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                                <span class="text-xs font-bold text-primary">
                                                    <?php echo mb_substr($rb['first_name'], 0, 1); ?>
                                                </span>
                                            </div>
                                            <span class="font-medium text-sm">
                                                <?php echo sanitize($rb['first_name'] . ' ' . $rb['last_name']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-mono text-primary font-semibold text-xs">
                                            <?php echo sanitize($rb['booking_ref']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-ghost badge-sm">
                                            <?php echo $rb['room_count']; ?> ห้อง
                                        </span>
                                    </td>
                                    <td class="text-center text-sm">
                                        <?php if ($rb['first_checkin'] && $rb['last_checkout']): ?>
                                            <?php echo date('d/m/y', strtotime($rb['first_checkin'])); ?> →
                                            <?php echo date('d/m/y', strtotime($rb['last_checkout'])); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-semibold text-end text-sm">
                                        ฿<?php echo number_format($rb['net_amount'], 0); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo booking_status_badge($rb['status']); ?>
                                    </td>
                                    <td class="text-center text-base-content/50 text-xs">
                                        <?php echo date('d/m/y H:i', strtotime($rb['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>