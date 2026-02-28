<?php
// ═══════════════════════════════════════════════════════════
// ADMIN BOOKING DETAIL PAGE — VET4 HOTEL
// Comprehensive single-booking view for admin/staff
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/booking_detail_data.php';

// Helper: status badge
function detail_status_badge($status, $config)
{
    $info = $config[$status] ?? ['label' => 'ไม่ทราบ', 'class' => 'badge-ghost', 'icon' => 'help-circle'];
    return '<span class="badge ' . $info['class'] . ' gap-1.5"><i data-lucide="' . $info['icon'] . '" class="size-3"></i>' . $info['label'] . '</span>';
}

// Helper: payment badge
function payment_badge($status, $config)
{
    $info = $config[$status] ?? ['label' => 'ไม่ทราบ', 'class' => 'badge-ghost'];
    return '<span class="badge badge-sm ' . $info['class'] . '">' . $info['label'] . '</span>';
}

// Helper: gender label
function gender_label($g)
{
    $map = ['male' => 'ผู้', 'female' => 'เมีย', 'spayed' => 'ทำหมันแล้ว(เมีย)', 'neutered' => 'ทำหมันแล้ว(ผู้)', 'unknown' => 'ไม่ระบุ'];
    return $map[$g] ?? 'ไม่ระบุ';
}

// Helper: charge type label
function charge_type_label($t)
{
    $map = ['per_stay' => 'ต่อการเข้าพัก', 'per_night' => 'ต่อคืน', 'per_pet' => 'ต่อตัว'];
    return $map[$t] ?? $t;
}

// Helper: transport type label
function transport_label($t)
{
    $map = ['pickup' => '🚗 รับสัตว์เลี้ยง', 'dropoff' => '🏠 ส่งสัตว์เลี้ยง', 'roundtrip' => '🔄 รับ-ส่ง ไป-กลับ'];
    return $map[$t] ?? $t;
}

// Helper: transport status label
function transport_status_badge($s)
{
    $map = [
        'pending' => ['รอดำเนินการ', 'badge-warning'],
        'assigned' => ['มอบหมายแล้ว', 'badge-info'],
        'in_transit' => ['กำลังเดินทาง', 'badge-primary'],
        'completed' => ['เสร็จสิ้น', 'badge-success'],
        'cancelled' => ['ยกเลิก', 'badge-error'],
    ];
    $info = $map[$s] ?? ['ไม่ทราบ', 'badge-ghost'];
    return '<span class="badge badge-sm ' . $info[1] . '">' . $info[0] . '</span>';
}

// Calculate nights helper
function calc_nights($checkin, $checkout)
{
    return max(1, (int) round((strtotime($checkout) - strtotime($checkin)) / 86400));
}

// Action button config
$action_btn_config = [
    'confirmed' => ['label' => 'ยืนยันการจอง', 'class' => 'btn-success', 'icon' => 'check-circle'],
    'checked_in' => ['label' => 'เช็คอิน', 'class' => 'btn-primary', 'icon' => 'log-in'],
    'checked_out' => ['label' => 'เช็คเอาท์', 'class' => 'btn-secondary', 'icon' => 'log-out'],
    'cancelled' => ['label' => 'ยกเลิกการจอง', 'class' => 'btn-error btn-outline', 'icon' => 'x-circle'],
];
?>

<div class="p-4 lg:p-8 space-y-6 max-w-5xl mx-auto">

    <!-- ═══════════ HEADER ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <a href="?page=bookings" class="btn btn-ghost btn-sm btn-square mt-1">
                <i data-lucide="arrow-left" class="size-5"></i>
            </a>
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3 flex-wrap">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                        <i data-lucide="file-text" class="size-5 text-primary"></i>
                    </div>
                    <span class="font-mono"><?php echo htmlspecialchars($booking['booking_ref']); ?></span>
                    <?php echo detail_status_badge($current_status, $status_config); ?>
                </h1>
                <p class="text-base-content/60 text-sm mt-1 ml-13">
                    สร้างเมื่อ <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?>
                </p>
            </div>
        </div>

        <!-- Status Action Buttons -->
        <div class="flex flex-wrap items-center justify-end gap-2">
            <?php if (!empty($available_actions)): ?>
                <?php foreach ($available_actions as $action): ?>
                    <?php $btn = $action_btn_config[$action] ?? null;
                    if (!$btn)
                        continue; ?>
                    <button type="button"
                        onclick="openBookingStatusModal('<?php echo $booking_id; ?>', '<?php echo $action; ?>', '<?php echo $btn['label']; ?>', '<?php echo $btn['icon']; ?>', '', '<?php echo $btn['class']; ?>')"
                        class="btn btn-sm <?php echo $btn['class']; ?> gap-1.5 shadow-sm">
                        <i data-lucide="<?php echo $btn['icon']; ?>" class="size-4"></i>
                        <?php echo $btn['label']; ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Advanced Admin Override -->
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-sm btn-outline btn-ghost gap-1 shadow-sm">
                    <i data-lucide="settings-2" class="size-4"></i>
                    เปลี่ยนสถานะ (แอดมิน)
                </div>
                <ul tabindex="0"
                    class="dropdown-content z-10 menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-200 mt-1">
                    <li class="menu-title px-4 py-2 text-xs opacity-50">เลือกสถานะที่ต้องการ</li>
                    <?php foreach ($status_config as $s_key => $s_cfg): ?>
                        <?php if ($s_key !== $current_status): ?>
                            <li>
                                <button type="button"
                                    onclick="openBookingStatusModal('<?php echo $booking_id; ?>', '<?php echo $s_key; ?>', '<?php echo $s_cfg['label']; ?>', '<?php echo $s_cfg['icon']; ?>', '1', '')"
                                    class="w-full text-left flex items-center gap-2 py-1">
                                    <i data-lucide="<?php echo $s_cfg['icon']; ?>" class="size-4"></i>
                                    <?php echo $s_cfg['label']; ?>
                                </button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- ═══════════ CUSTOMER INFO ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                    <i data-lucide="user" class="size-4 text-primary"></i>
                </div>
                <h2 class="font-bold text-base-content">ข้อมูลลูกค้า</h2>
            </div>

            <div class="flex items-start gap-4">
                <div class="avatar placeholder hidden sm:flex">
                    <div class="bg-primary text-primary-content w-14 h-14 rounded-2xl flex items-center justify-center">
                        <span class="text-xl font-bold">
                            <?php echo mb_substr($booking['first_name'], 0, 1); ?>
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 flex-1">
                    <div>
                        <p class="text-xs text-base-content/50 uppercase tracking-wider">ชื่อ-นามสกุล</p>
                        <p class="font-semibold">
                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 uppercase tracking-wider">อีเมล</p>
                        <p class="font-medium text-sm">
                            <?php echo htmlspecialchars($booking['email']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 uppercase tracking-wider">เบอร์โทร</p>
                        <p class="font-medium text-sm">
                            <?php echo htmlspecialchars($booking['phone']); ?>
                        </p>
                    </div>
                    <?php if ($booking['address']): ?>
                        <div>
                            <p class="text-xs text-base-content/50 uppercase tracking-wider">ที่อยู่</p>
                            <p class="text-sm"><?php echo htmlspecialchars($booking['address']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['emergency_contact_name']): ?>
                        <div class="sm:col-span-2 mt-2 pt-2 border-t border-base-200">
                            <p class="text-xs text-base-content/50 uppercase tracking-wider">ผู้ติดต่อฉุกเฉิน</p>
                            <p class="text-sm font-medium">
                                <?php echo htmlspecialchars($booking['emergency_contact_name']); ?>
                                <?php if ($booking['emergency_contact_phone']): ?>
                                    <span class="text-base-content/60 ml-2">
                                        📞 <?php echo htmlspecialchars($booking['emergency_contact_phone']); ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ ROOMS & PETS ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                    <i data-lucide="bed" class="size-4 text-success"></i>
                </div>
                <h2 class="font-bold text-base-content">ห้องพักและสัตว์เลี้ยง</h2>
                <span class="badge badge-ghost badge-sm"><?php echo count($booking_items); ?> ห้อง</span>
            </div>

            <div class="space-y-4">
                <?php foreach ($booking_items as $idx => $item): ?>
                    <?php $nights = calc_nights($item['check_in_date'], $item['check_out_date']); ?>
                    <div class="border border-base-200 rounded-xl overflow-hidden">
                        <!-- Room Header -->
                        <div
                            class="bg-base-200/40 px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-sm font-bold text-primary">
                                    <?php echo $idx + 1; ?>
                                </div>
                                <div>
                                    <span class="font-bold text-base-content">
                                        ห้อง <?php echo htmlspecialchars($item['room_number']); ?>
                                    </span>
                                    <span class="text-sm text-base-content/60 ml-2">
                                        <?php echo htmlspecialchars($item['room_type_name']); ?>
                                    </span>
                                    <span class="text-xs text-base-content/40 ml-1">(ชั้น
                                        <?php echo htmlspecialchars($item['floor_level']); ?>)</span>
                                </div>
                            </div>
                            <div class="text-sm font-semibold text-primary">
                                ฿<?php echo number_format($item['subtotal'], 2); ?>
                            </div>
                        </div>

                        <!-- Room Details -->
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-base-content/50">เช็คอิน</p>
                                    <p class="font-medium"><?php echo date('d/m/Y', strtotime($item['check_in_date'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-base-content/50">เช็คเอาท์</p>
                                    <p class="font-medium"><?php echo date('d/m/Y', strtotime($item['check_out_date'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-base-content/50">จำนวนคืน</p>
                                    <p class="font-medium"><?php echo $nights; ?> คืน</p>
                                </div>
                                <div>
                                    <p class="text-xs text-base-content/50">ราคา/คืน</p>
                                    <p class="font-medium">฿<?php echo number_format($item['locked_unit_price'], 2); ?></p>
                                </div>
                            </div>

                            <!-- Pets in this room -->
                            <?php $room_pets = $pets_by_item[$item['id']] ?? []; ?>
                            <?php if (!empty($room_pets)): ?>
                                <div class="border-t border-base-200 pt-3">
                                    <p
                                        class="text-xs text-base-content/50 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <i data-lucide="paw-print" class="size-3"></i>
                                        สัตว์เลี้ยงในห้องนี้ (<?php echo count($room_pets); ?> ตัว)
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($room_pets as $pet): ?>
                                            <div
                                                class="flex items-center gap-2 border rounded-lg px-3 py-2 text-sm <?php echo $pet['is_aggressive'] ? 'border-error/30 bg-error/5' : 'border-base-200 bg-base-100'; ?>">
                                                <?php if ($pet['is_aggressive']): ?>
                                                    <i data-lucide="alert-triangle" class="size-4 text-error shrink-0"></i>
                                                <?php else: ?>
                                                    <i data-lucide="heart" class="size-4 text-primary/50 shrink-0"></i>
                                                <?php endif; ?>
                                                <div>
                                                    <span
                                                        class="font-semibold"><?php echo htmlspecialchars($pet['pet_name']); ?></span>
                                                    <?php if ($pet['is_aggressive']): ?>
                                                        <span class="badge badge-xs badge-error ml-1">ดุร้าย</span>
                                                    <?php endif; ?>
                                                    <div class="text-[11px] text-base-content/50">
                                                        <?php echo htmlspecialchars($pet['species_name']); ?>
                                                        <?php if ($pet['breed_name']): ?>
                                                            (<?php echo htmlspecialchars($pet['breed_name']); ?>)
                                                        <?php endif; ?>
                                                        <?php if ($pet['weight_kg']): ?>
                                                            • <?php echo $pet['weight_kg']; ?>kg
                                                        <?php endif; ?>
                                                        • <?php echo gender_label($pet['gender']); ?>
                                                    </div>
                                                    <?php if ($pet['is_aggressive'] && $pet['behavior_note']): ?>
                                                        <div class="text-[11px] text-error/70 mt-0.5">
                                                            📝 <?php echo htmlspecialchars($pet['behavior_note']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════ SERVICES ═══════════ -->
    <?php if (!empty($booking_services)): ?>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-secondary/10 flex items-center justify-center">
                        <i data-lucide="sparkles" class="size-4 text-secondary"></i>
                    </div>
                    <h2 class="font-bold text-base-content">บริการเสริม</h2>
                    <span class="badge badge-ghost badge-sm"><?php echo count($booking_services); ?> รายการ</span>
                </div>

                <div class="overflow-x-auto -mx-2">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-base-content/50">
                                <th>บริการ</th>
                                <th>สัตว์เลี้ยง</th>
                                <th class="text-center">ประเภทคิดเงิน</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-right">ราคา/หน่วย</th>
                                <th class="text-right">รวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $services_total = 0;
                            foreach ($booking_services as $bs):
                                $services_total += $bs['total_price'];
                                ?>
                                <tr class="hover">
                                    <td class="font-medium"><?php echo htmlspecialchars($bs['service_name']); ?></td>
                                    <td class="text-sm text-base-content/70">
                                        <?php echo $bs['pet_name'] ? htmlspecialchars($bs['pet_name']) : '-'; ?>
                                    </td>
                                    <td class="text-center text-sm">
                                        <span class="badge badge-ghost badge-xs">
                                            <?php echo charge_type_label($bs['charge_type']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo $bs['quantity']; ?></td>
                                    <td class="text-right text-sm">฿<?php echo number_format($bs['locked_unit_price'], 2); ?>
                                    </td>
                                    <td class="text-right font-medium">฿<?php echo number_format($bs['total_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-base-200">
                                <td colspan="5" class="text-right font-bold">รวมบริการเสริม</td>
                                <td class="text-right font-bold text-primary">
                                    ฿<?php echo number_format($services_total, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══════════ TRANSPORTATION ═══════════ -->
    <?php if (!empty($transportations)): ?>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center">
                        <i data-lucide="truck" class="size-4 text-info"></i>
                    </div>
                    <h2 class="font-bold text-base-content">บริการรับ-ส่งสัตว์เลี้ยง</h2>
                </div>

                <div class="space-y-3">
                    <?php foreach ($transportations as $tr): ?>
                        <div class="border border-base-200 rounded-xl p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                <div class="font-medium">
                                    <?php echo transport_label($tr['transport_type']); ?>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php echo transport_status_badge($tr['status']); ?>
                                    <span class="font-bold text-primary">฿<?php echo number_format($tr['price'], 2); ?></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-base-content/50">📍 ที่อยู่:</span>
                                    <span><?php echo htmlspecialchars($tr['address']); ?></span>
                                </div>
                                <div>
                                    <span class="text-base-content/50">📅 กำหนดเวลา:</span>
                                    <span><?php echo date('d/m/Y H:i', strtotime($tr['scheduled_datetime'])); ?></span>
                                </div>
                                <?php if ($tr['distance_km']): ?>
                                    <div>
                                        <span class="text-base-content/50">📏 ระยะทาง:</span>
                                        <span><?php echo $tr['distance_km']; ?> กม.</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($tr['driver_name']): ?>
                                    <div>
                                        <span class="text-base-content/50">🚘 คนขับ:</span>
                                        <span>
                                            <?php echo htmlspecialchars($tr['driver_name']); ?>
                                            <?php if ($tr['driver_phone']): ?>
                                                (<?php echo htmlspecialchars($tr['driver_phone']); ?>)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══════════ SPECIAL REQUESTS ═══════════ -->
    <?php if (!empty($booking['special_requests'])): ?>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                        <i data-lucide="message-square" class="size-4 text-warning"></i>
                    </div>
                    <h2 class="font-bold text-base-content">คำขอพิเศษ / หมายเหตุ</h2>
                </div>
                <div class="bg-base-200/40 rounded-lg p-4 text-sm text-base-content/80 whitespace-pre-wrap">
                    <?php echo htmlspecialchars($booking['special_requests']); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══════════ CARE TASKS ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <i data-lucide="clipboard-check" class="size-4 text-primary"></i>
                    </div>
                    <h2 class="font-bold text-base-content">งานดูแลสัตว์เลี้ยง (Daily Care Tasks)</h2>
                    <span class="badge badge-ghost badge-sm"><?php echo count($booking_care_tasks); ?> รายการ</span>
                </div>
                <!-- Only allow adding if the booking is active -->
                <?php if (in_array($booking['status'], ['confirmed', 'checked_in'])): ?>
                    <button class="btn btn-sm btn-outline btn-primary gap-1" onclick="openAddCareTaskModal()">
                        <i data-lucide="plus" class="size-4"></i> เพิ่มงานดูแล
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($booking_care_tasks)): ?>
                <div class="text-center py-6 text-base-content/40 bg-base-200/20 rounded-xl border border-dashed border-base-300">
                    <i data-lucide="clipboard-x" class="size-8 mx-auto mb-2 opacity-50"></i>
                    <p class="text-sm">ไม่มีงานดูแลสำหรับรายการจองนี้</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto -mx-2">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-base-content/50">
                                <th>วันที่</th>
                                <th>สัตว์เลี้ยง</th>
                                <th>ประเภทงาน</th>
                                <th>รายละเอียด</th>
                                <th class="text-center">สถานะ</th>
                                <th>ผู้รับผิดชอบ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($booking_care_tasks as $task): ?>
                                <tr class="hover">
                                    <td class="text-sm whitespace-nowrap">
                                        <?php echo date('d/m/Y', strtotime($task['task_date'])); ?>
                                    </td>
                                    <td class="font-medium">
                                        <?php echo sanitize($task['pet_name']); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-ghost badge-sm"><?php echo sanitize($task['task_type_name']); ?></span>
                                    </td>
                                    <td class="text-sm text-base-content/70 max-w-xs truncate" title="<?php echo sanitize($task['description']); ?>">
                                        <?php echo sanitize($task['description']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($task['status'] === 'completed'): ?>
                                            <span class="badge badge-sm badge-success gap-1"><i data-lucide="check-circle" class="size-3"></i> เสร็จสิ้น</span>
                                        <?php else: ?>
                                            <span class="badge badge-sm badge-warning gap-1"><i data-lucide="clock" class="size-3"></i> รอดำเนินการ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-sm">
                                        <?php if ($task['status'] === 'completed' && $task['emp_first_name']): ?>
                                            <?php echo sanitize($task['emp_first_name']); ?>
                                            <div class="text-[10px] text-base-content/50"><?php echo date('H:i', strtotime($task['completed_at'])); ?> น.</div>
                                        <?php else: ?>
                                            <span class="text-base-content/40">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Quick Complete Toggle -->
                                            <form action="?action=care_tasks" method="POST" class="inline">
                                                <input type="hidden" name="sub_action" value="toggle_status">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $task['status'] === 'completed' ? 'pending' : 'completed'; ?>">
                                                <input type="hidden" name="return_to_booking" value="<?php echo $booking_id; ?>">
                                                
                                                <?php if ($task['status'] === 'pending'): ?>
                                                    <button type="submit" class="btn btn-xs btn-circle btn-ghost text-success hover:bg-success/10" data-tip="ทำสำเร็จ">
                                                        <i data-lucide="check" class="size-4"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-xs btn-circle btn-ghost text-warning hover:bg-warning/10" data-tip="ยกเลิกการทำ">
                                                        <i data-lucide="rotate-ccw" class="size-4"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            
                                            <!-- Delete Button -->
                                            <form action="?action=care_tasks" method="POST" class="inline" onsubmit="return confirm('ยืนยันการลบงานดูแลนี้?')">
                                                <input type="hidden" name="sub_action" value="delete">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <input type="hidden" name="return_to_booking" value="<?php echo $booking_id; ?>">
                                                <button type="submit" class="btn btn-xs btn-circle btn-ghost text-error/70 hover:text-error hover:bg-error/10" data-tip="ลบ">
                                                    <i data-lucide="trash-2" class="size-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════ FINANCIAL SUMMARY ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center">
                    <i data-lucide="receipt" class="size-4 text-accent"></i>
                </div>
                <h2 class="font-bold text-base-content">สรุปการเงิน</h2>
            </div>

            <div class="max-w-md ml-auto space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-base-content/60">ยอดรวมก่อนลด</span>
                    <span class="font-medium">฿<?php echo number_format($booking['subtotal_amount'], 2); ?></span>
                </div>

                <?php if ($booking['discount_amount'] > 0): ?>
                    <div class="flex justify-between text-sm text-success">
                        <span>
                            ส่วนลด
                            <?php if ($booking['promo_code']): ?>
                                <span class="badge badge-xs badge-outline badge-success ml-1">
                                    <?php echo htmlspecialchars($booking['promo_code']); ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span class="font-medium">-฿<?php echo number_format($booking['discount_amount'], 2); ?></span>
                    </div>
                <?php endif; ?>

                <div class="flex justify-between text-lg font-bold border-t border-base-200 pt-2">
                    <span>ยอดสุทธิ</span>
                    <span class="text-primary">฿<?php echo number_format($booking['net_amount'], 2); ?></span>
                </div>

                <div class="flex justify-between text-sm pt-1">
                    <span class="text-base-content/60">ชำระแล้ว</span>
                    <span class="font-medium text-success">฿<?php echo number_format($total_paid, 2); ?></span>
                </div>

                <?php
                $balance = $booking['net_amount'] - $total_paid;
                if ($balance > 0):
                    ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-base-content/60">ยอดค้างชำระ</span>
                        <span class="font-bold text-error">฿<?php echo number_format($balance, 2); ?></span>
                    </div>
                <?php elseif ($balance < 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-base-content/60">ชำระเกิน</span>
                        <span class="font-bold text-info">฿<?php echo number_format(abs($balance), 2); ?></span>
                    </div>
                <?php else: ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-base-content/60">สถานะ</span>
                        <span class="badge badge-success badge-sm">ชำระครบแล้ว</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════ PAYMENTS HISTORY ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center">
                    <i data-lucide="credit-card" class="size-4 text-info"></i>
                </div>
                <h2 class="font-bold text-base-content">ประวัติการชำระเงิน</h2>
                <span class="badge badge-ghost badge-sm"><?php echo count($payments); ?> รายการ</span>
            </div>

            <?php if (empty($payments)): ?>
                <div class="text-center py-8 text-base-content/40">
                    <i data-lucide="wallet" class="size-10 mx-auto mb-2 opacity-40"></i>
                    <p class="text-sm">ยังไม่มีรายการชำระเงิน</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto -mx-2">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-base-content/50">
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>ช่องทาง</th>
                                <th class="text-right">จำนวน (฿)</th>
                                <th class="text-center">สถานะ</th>
                                <th>ผู้ตรวจสอบ</th>
                                <th class="text-center">หลักฐาน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <tr class="hover">
                                    <td class="text-sm">
                                        <?php echo date('d/m/Y H:i', strtotime($pay['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $pay_type_map = [
                                            'deposit' => 'มัดจำ',
                                            'full_payment' => 'ชำระเต็ม',
                                            'balance_due' => 'ชำระส่วนที่เหลือ',
                                            'extra_charge' => 'ค่าใช้จ่ายเพิ่มเติม'
                                        ];
                                        echo $pay_type_map[$pay['payment_type']] ?? $pay['payment_type'];
                                        ?>
                                    </td>
                                    <td class="text-sm">
                                        <?php if ($pay['channel_name']): ?>
                                            <?php echo htmlspecialchars($pay['channel_name']); ?>
                                            <?php if ($pay['bank_name']): ?>
                                                <span class="text-xs text-base-content/50 block">
                                                    (<?php echo htmlspecialchars($pay['bank_name']); ?>)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-base-content/40">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right font-semibold">
                                        ฿<?php echo number_format($pay['amount'], 2); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo payment_badge($pay['status'], $payment_status_config); ?>
                                    </td>
                                    <td class="text-sm">
                                        <?php if ($pay['verifier_first']): ?>
                                            <?php echo htmlspecialchars($pay['verifier_first'] . ' ' . $pay['verifier_last']); ?>
                                        <?php else: ?>
                                            <span class="text-base-content/40">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pay['proof_image_url']): ?>
                                            <button type="button" class="btn btn-ghost btn-xs text-primary"
                                                onclick="document.getElementById('proofModal_<?php echo $pay['id']; ?>').showModal()">
                                                <i data-lucide="image" class="size-4"></i>
                                            </button>
                                            <!-- Proof Image Modal -->
                                            <dialog id="proofModal_<?php echo $pay['id']; ?>" class="modal">
                                                <div class="modal-box max-w-lg">
                                                    <form method="dialog">
                                                        <button
                                                            class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                                    </form>
                                                    <h3 class="font-bold text-lg mb-4">หลักฐานการชำระเงิน</h3>
                                                    <img src="../<?php echo htmlspecialchars($pay['proof_image_url']); ?>"
                                                        alt="Payment Proof" class="w-full rounded-lg border border-base-200"
                                                        loading="lazy">
                                                    <?php if ($pay['transaction_ref']): ?>
                                                        <p class="text-sm text-base-content/60 mt-3">
                                                            เลขอ้างอิง: <span
                                                                class="font-mono"><?php echo htmlspecialchars($pay['transaction_ref']); ?></span>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>close</button>
                                                </form>
                                            </dialog>
                                        <?php else: ?>
                                            <span class="text-base-content/30">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════ BOTTOM ACTIONS ═══════════ -->
    <div class="flex items-center justify-between">
        <a href="?page=bookings" class="btn btn-ghost gap-2">
            <i data-lucide="arrow-left" class="size-4"></i>
            กลับไปรายการจอง
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <?php if (!empty($available_actions)): ?>
                <?php foreach ($available_actions as $action): ?>
                    <?php $btn = $action_btn_config[$action] ?? null;
                    if (!$btn)
                        continue; ?>
                    <button type="button"
                        onclick="openBookingStatusModal('<?php echo $booking_id; ?>', '<?php echo $action; ?>', '<?php echo $btn['label']; ?>', '<?php echo $btn['icon']; ?>', '', '<?php echo $btn['class']; ?>')"
                        class="btn btn-sm <?php echo $btn['class']; ?> gap-1.5 shadow-sm">
                        <i data-lucide="<?php echo $btn['icon']; ?>" class="size-4"></i>
                        <?php echo $btn['label']; ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Advanced Admin Override (Bottom) -->
            <div class="dropdown dropdown-top dropdown-end">
                <div tabindex="0" role="button" class="btn btn-sm btn-outline btn-ghost gap-1 shadow-sm">
                    <i data-lucide="settings-2" class="size-4"></i>
                    เปลี่ยนสถานะ (แอดมิน)
                </div>
                <ul tabindex="0"
                    class="dropdown-content z-10 menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-200 mb-1">
                    <li class="menu-title px-4 py-2 text-xs opacity-50">เลือกสถานะที่ต้องการ</li>
                    <?php foreach ($status_config as $s_key => $s_cfg): ?>
                        <?php if ($s_key !== $current_status): ?>
                            <li>
                                <button type="button"
                                    onclick="openBookingStatusModal('<?php echo $booking_id; ?>', '<?php echo $s_key; ?>', '<?php echo $s_cfg['label']; ?>', '<?php echo $s_cfg['icon']; ?>', '1', '')"
                                    class="w-full text-left flex items-center gap-2 py-1">
                                    <i data-lucide="<?php echo $s_cfg['icon']; ?>" class="size-4"></i>
                                    <?php echo $s_cfg['label']; ?>
                                </button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════ CONFIRM BOOKING STATUS MODAL ═══════════ -->
<dialog id="modal_confirm_booking_status" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div id="bk_confirm_icon_wrap"
                class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-warning/10">
                <i data-lucide="alert-triangle" class="size-7 text-warning"></i>
            </div>
            <h3 class="font-bold text-lg mb-2" id="bk_confirm_title">ยืนยันการเปลี่ยนสถานะ</h3>
            <p class="text-base-content/60" id="bk_confirm_message">ต้องการดำเนินการนี้ใช่หรือไม่?</p>
        </div>
        <form method="POST" action="?action=booking_status" id="bk_status_form">
            <input type="hidden" name="booking_id" id="bk_confirm_booking_id">
            <input type="hidden" name="new_status" id="bk_confirm_new_status">
            <input type="hidden" name="force_override" id="bk_confirm_force" value="">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_confirm_booking_status').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" id="bk_confirm_submit_btn" class="btn btn-warning gap-2">
                    <i data-lucide="check" class="size-4"></i>
                    ยืนยัน
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    function openBookingStatusModal(bookingId, newStatus, statusLabel, iconName, forceOverride, btnClass) {
        // Close any open dropdown
        document.activeElement?.blur();

        // Populate hidden fields
        document.getElementById('bk_confirm_booking_id').value = bookingId;
        document.getElementById('bk_confirm_new_status').value = newStatus;
        document.getElementById('bk_confirm_force').value = forceOverride;

        // Title and message
        const isForce = forceOverride === '1';
        document.getElementById('bk_confirm_title').textContent = isForce
            ? '⚠️ ยืนยันการบังคับเปลี่ยนสถานะ'
            : 'ยืนยันการเปลี่ยนสถานะ';
        document.getElementById('bk_confirm_message').innerHTML = isForce
            ? 'ต้องการบังคับเปลี่ยนสถานะเป็น <strong>"' + statusLabel + '"</strong> ใช่หรือไม่?<br><span class="text-warning text-xs">การดำเนินการนี้ข้ามลำดับสถานะปกติ</span>'
            : 'ต้องการเปลี่ยนสถานะเป็น <strong>"' + statusLabel + '"</strong> ใช่หรือไม่?';

        // Style the confirm button
        const btn = document.getElementById('bk_confirm_submit_btn');
        btn.className = 'btn gap-2';
        if (btnClass) {
            btnClass.split(' ').forEach(c => { if (c) btn.classList.add(c); });
        } else {
            // Fallback styling based on status
            const styleMap = {
                'confirmed': 'btn-success',
                'checked_in': 'btn-primary',
                'checked_out': 'btn-secondary',
                'cancelled': 'btn-error',
                'pending_payment': 'btn-warning',
                'verifying_payment': 'btn-info'
            };
            btn.classList.add(styleMap[newStatus] || 'btn-warning');
        }

        // Style the icon wrapper
        const iconWrap = document.getElementById('bk_confirm_icon_wrap');
        iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4';
        const bgMap = {
            'confirmed': 'bg-success/10',
            'checked_in': 'bg-primary/10',
            'checked_out': 'bg-secondary/10',
            'cancelled': 'bg-error/10',
            'pending_payment': 'bg-warning/10',
            'verifying_payment': 'bg-info/10'
        };
        iconWrap.classList.add(bgMap[newStatus] || 'bg-warning/10');

        document.getElementById('modal_confirm_booking_status').showModal();

        // Re-init icons
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openAddCareTaskModal() {
        document.getElementById('modal-add-care-task').showModal();
    }
</script>

<!-- ═══════════ ADD CARE TASK MODAL ═══════════ -->
<dialog id="modal-add-care-task" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box bg-base-100 rounded-t-3xl sm:rounded-3xl p-0 overflow-hidden shadow-2xl max-w-md">
        <div class="p-6 border-b border-base-200 flex items-center gap-3 bg-base-100/50">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                <i data-lucide="clipboard-check" class="size-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-base-content leading-tight">เพิ่มงานดูแลสัตว์เลี้ยง</h3>
                <p class="text-sm text-base-content/60 mt-0.5">ระบุงานดูแลสำหรับรายการจองนี้</p>
            </div>
            <form method="dialog" class="ml-auto">
                <button class="btn btn-sm btn-circle btn-ghost text-base-content/50 hover:text-base-content hover:bg-base-200">
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </form>
        </div>

        <form action="?action=care_tasks" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="sub_action" value="add">
            <input type="hidden" name="return_to_booking" value="<?php echo $booking_id; ?>">
            
            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">สัตว์เลี้ยง <span class="text-error">*</span></span></label>
                <select name="pet_info" id="add-pet-select" class="select select-bordered w-full rounded-xl focus:outline-primary/50 focus:border-primary transition-colors" required onchange="updateAddCareTaskHiddenFields()">
                    <option value="" disabled selected>-- เลือกสัตว์เลี้ยง --</option>
                    <?php 
                    // Flatten the pets array from all booked items
                    foreach ($pets_by_item as $item_id => $pets) {
                        foreach ($pets as $p) {
                            $val = $item_id . '|' . $p['pet_id'];
                            echo '<option value="' . $val . '">' . htmlspecialchars($p['pet_name'] . ' (' . $p['species_name'] . ')') . '</option>';
                        }
                    }
                    ?>
                </select>
                <!-- JS will split this into these hidden fields on submit -->
                <input type="hidden" name="booking_item_id" id="add-booking-item-id">
                <input type="hidden" name="pet_id" id="add-pet-id">
            </div>

            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">วันที่ต้องดูแล <span class="text-error">*</span></span></label>
                <!-- Default to check in date if future, else today, but restrict min/max based on booking dates roughly if possible -->
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
                <textarea name="description" class="textarea textarea-bordered h-24 rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full" placeholder="เช่น ป้อนยา 1 เม็ดหลังอาหาร" required></textarea>
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

<script>
    function updateAddCareTaskHiddenFields() {
        const selectEl = document.getElementById('add-pet-select');
        if (selectEl.value) {
            const parts = selectEl.value.split('|');
            document.getElementById('add-booking-item-id').value = parts[0];
            document.getElementById('add-pet-id').value = parts[1];
        }
    }

    function prepareAddSubmit() {
        updateAddCareTaskHiddenFields();
        if(!document.getElementById('add-pet-id').value) {
            alert('กรุณาเลือกสัตว์เลี้ยง');
            return false;
        }
        return true;
    }
</script>