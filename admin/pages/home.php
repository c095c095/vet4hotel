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

$greeting = '';
$hour = (int) date('H');
if ($hour < 12)
    $greeting = 'สวัสดีตอนเช้า';
elseif ($hour < 17)
    $greeting = 'สวัสดีตอนบ่าย';
else
    $greeting = 'สวัสดีตอนเย็น';
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ WELCOME HEADER ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
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
        <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-base-content/50 uppercase tracking-wide">จองวันนี้</p>
                        <p class="text-3xl font-bold text-base-content mt-1">
                            <?php echo $kpi_bookings_today; ?>
                        </p>
                        <p class="text-base-content/40 mt-1">รายการจองใหม่</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center">
                        <i data-lucide="calendar-plus" class="size-6 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Check-ins -->
        <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-base-content/50 uppercase tracking-wide">เข้าพักอยู่</p>
                        <p class="text-3xl font-bold text-success mt-1">
                            <?php echo $kpi_active_checkins; ?>
                        </p>
                        <p class="text-base-content/40 mt-1">สัตว์เลี้ยงที่กำลังพัก</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-success/10 flex items-center justify-center">
                        <i data-lucide="hotel" class="size-6 text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-base-content/50 uppercase tracking-wide">รอตรวจสอบ</p>
                        <p
                            class="text-3xl font-bold <?php echo $kpi_pending_payments > 0 ? 'text-warning' : 'text-base-content'; ?> mt-1">
                            <?php echo $kpi_pending_payments; ?>
                        </p>
                        <p class="text-base-content/40 mt-1">การชำระเงิน</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-warning/10 flex items-center justify-center">
                        <i data-lucide="credit-card" class="size-6 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-base-content/50 uppercase tracking-wide">รายได้เดือนนี้</p>
                        <p class="text-3xl font-bold text-base-content mt-1">฿
                            <?php echo number_format($kpi_monthly_revenue, 0); ?>
                        </p>
                        <p class="text-base-content/40 mt-1">
                            <?php echo date('F Y'); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-info/10 flex items-center justify-center">
                        <i data-lucide="trending-up" class="size-6 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ QUICK STATS ROW ═══════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-base-100 border border-base-200 rounded-xl p-4 text-center">
            <i data-lucide="users" class="size-7 text-primary mx-auto mb-1"></i>
            <p class="text-xl font-bold">
                <?php echo $total_customers; ?>
            </p>
            <p class="text-base-content/50 uppercase tracking-wider">ลูกค้าทั้งหมด</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-xl p-4 text-center">
            <i data-lucide="paw-print" class="size-7 text-secondary mx-auto mb-1"></i>
            <p class="text-xl font-bold">
                <?php echo $total_pets; ?>
            </p>
            <p class="text-base-content/50 uppercase tracking-wider">สัตว์เลี้ยง</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-xl p-4 text-center">
            <i data-lucide="door-open" class="size-7 text-success mx-auto mb-1"></i>
            <p class="text-xl font-bold">
                <?php echo $available_rooms; ?>
            </p>
            <p class="text-base-content/50 uppercase tracking-wider">ห้องว่าง</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-xl p-4 text-center">
            <i data-lucide="wrench" class="size-7 text-warning mx-auto mb-1"></i>
            <p class="text-xl font-bold">
                <?php echo $maintenance_rooms; ?>
            </p>
            <p class="text-base-content/50 uppercase tracking-wider">ซ่อมบำรุง</p>
        </div>
    </div>

    <!-- ═══════════ ROOM OCCUPANCY OVERVIEW ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="building-2" class="size-5 text-primary"></i>
                    <h2 class="font-bold text-base-content">ภาพรวมห้องพัก</h2>
                </div>
                <span class="badge badge-primary badge-outline text-xs">
                    <?php echo $occupancy_pct; ?>% ถูกใช้งาน
                </span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-base-200 rounded-full h-4 overflow-hidden">
                <div class="h-full rounded-full bg-linear-to-r from-primary to-secondary transition-all duration-700"
                    style="width: <?php echo $occupancy_pct; ?>%"></div>
            </div>
            <div class="flex items-center justify-between mt-2 text-base-content/50">
                <span>
                    <?php echo $occupied_rooms; ?> ห้องที่มีผู้เข้าพัก
                </span>
                <span>
                    <?php echo $total_active_rooms; ?> ห้องทั้งหมด
                </span>
            </div>
            <!-- Legend -->
            <div class="flex flex-wrap gap-4 mt-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-primary"></span>
                    <span class="text-base-content/60">ใช้งาน (
                        <?php echo $occupied_rooms; ?>)
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-success"></span>
                    <span class="text-base-content/60">ว่าง (
                        <?php echo $available_rooms; ?>)
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-warning"></span>
                    <span class="text-base-content/60">ซ่อมบำรุง (
                        <?php echo $maintenance_rooms; ?>)
                    </span>
                </div>
                <?php if (isset($room_status_dist['out_of_service'])): ?>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-error"></span>
                        <span class="text-base-content/60">ปิดให้บริการ (
                            <?php echo $room_status_dist['out_of_service']; ?>)
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════ TODAY'S ACTIVITY GRID ═══════════ -->
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
                    <div class="overflow-x-auto -mx-2">
                        <table class="table table-xs">
                            <thead>
                                <tr class="text-base-content/50">
                                    <th>Ref</th>
                                    <th>ลูกค้า</th>
                                    <th>ห้อง</th>
                                    <th>สัตว์เลี้ยง</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todays_checkins as $ci): ?>
                                    <tr class="hover">
                                        <td class="font-mono text-xs">
                                            <?php echo sanitize($ci['booking_ref']); ?>
                                        </td>
                                        <td class="font-medium text-xs">
                                            <?php echo sanitize($ci['first_name'] . ' ' . $ci['last_name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-ghost badge-xs">
                                                <?php echo sanitize($ci['room_number']); ?>
                                            </span>
                                        </td>
                                        <td class="text-xs max-w-24 truncate">
                                            <?php echo sanitize($ci['pet_names'] ?? '-'); ?>
                                        </td>
                                        <td>
                                            <?php echo booking_status_badge($ci['booking_status']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                    <div class="overflow-x-auto -mx-2">
                        <table class="table table-xs">
                            <thead>
                                <tr class="text-base-content/50">
                                    <th>Ref</th>
                                    <th>ลูกค้า</th>
                                    <th>ห้อง</th>
                                    <th>สัตว์เลี้ยง</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todays_checkouts as $co): ?>
                                    <tr class="hover">
                                        <td class="font-mono text-xs">
                                            <?php echo sanitize($co['booking_ref']); ?>
                                        </td>
                                        <td class="font-medium text-xs">
                                            <?php echo sanitize($co['first_name'] . ' ' . $co['last_name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-ghost badge-xs">
                                                <?php echo sanitize($co['room_number']); ?>
                                            </span>
                                        </td>
                                        <td class="text-xs max-w-24 truncate">
                                            <?php echo sanitize($co['pet_names'] ?? '-'); ?>
                                        </td>
                                        <td>
                                            <?php echo booking_status_badge($co['booking_status']); ?>
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

    <!-- ═══════════ PENDING CARE TASKS ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center">
                        <i data-lucide="clipboard-check" class="size-4 text-accent"></i>
                    </div>
                    <h2 class="font-bold text-base-content">งานดูแลที่ต้องทำวันนี้</h2>
                </div>
                <span class="badge badge-accent badge-outline badge-sm">
                    <?php echo count($pending_care_tasks); ?> รายการค้าง
                </span>
            </div>
            <?php if (empty($pending_care_tasks)): ?>
                <div class="text-center py-10 text-base-content/40">
                    <i data-lucide="check-circle-2" class="size-12 mx-auto mb-2 text-success/40"></i>
                    <p class="font-medium text-success/60">เยี่ยม! ไม่มีงานค้าง</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
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
                                    🐾
                                    <?php echo sanitize($task['pet_name']); ?>
                                    • ห้อง
                                    <?php echo sanitize($task['room_number']); ?>
                                    •
                                    <?php echo sanitize($task['task_type_name']); ?>
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
                                <th>Ref</th>
                                <th>ลูกค้า</th>
                                <th class="text-center">ห้อง</th>
                                <th class="text-center">วันเข้าพัก</th>
                                <th class="text-end">ยอดรวม</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">สร้างเมื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $rb): ?>
                                <tr class="hover">
                                    <td class="font-mono font-semibold text-primary">
                                        <?php echo sanitize($rb['booking_ref']); ?>
                                    </td>
                                    <td class="font-medium">
                                        <?php echo sanitize($rb['first_name'] . ' ' . $rb['last_name']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-ghost badge-sm">
                                            <?php echo $rb['room_count']; ?> ห้อง
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($rb['first_checkin'] && $rb['last_checkout']): ?>
                                            <?php echo date('d/m/y', strtotime($rb['first_checkin'])); ?> →
                                            <?php echo date('d/m/y', strtotime($rb['last_checkout'])); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-medium text-end">฿
                                        <?php echo number_format($rb['net_amount'], 0); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo booking_status_badge($rb['status']); ?>
                                    </td>
                                    <td class="text-center text-base-content/50">
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