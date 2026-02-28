<?php
// ═══════════════════════════════════════════════════════════
// PET TRANSPORTATION (TAXI) UI - VET4 HOTEL
// Allows staff to manage pickup/dropoff schedules and assign drivers
// ═══════════════════════════════════════════════════════════

// Filter
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? date('Y-m-d');

// Build query
$query = "SELECT 
            pt.*,
            bk.booking_ref,
            c.first_name as customer_name,
            c.phone as customer_phone
          FROM pet_transportation pt
          JOIN bookings bk ON pt.booking_id = bk.id
          JOIN customers c ON bk.customer_id = c.id
          WHERE 1=1";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND pt.status = :status";
    $params['status'] = $status_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(pt.scheduled_datetime) = :date_filter";
    $params['date_filter'] = $date_filter;
}

$query .= " ORDER BY pt.scheduled_datetime ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transport_jobs = $stmt->fetchAll();

// Stats
$stmt_stats = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
    SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit
    FROM pet_transportation WHERE DATE(scheduled_datetime) = ?");
$stmt_stats->execute([$date_filter]);
$stats = $stmt_stats->fetch();

// Helpers
function getTransportTypeLabel($type)
{
    if ($type === 'pickup')
        return ['label' => 'รับเข้า (Pickup)', 'icon' => 'arrow-right-circle', 'color' => 'text-info'];
    if ($type === 'dropoff')
        return ['label' => 'ส่งกลับ (Dropoff)', 'icon' => 'arrow-left-circle', 'color' => 'text-warning'];
    if ($type === 'roundtrip')
        return ['label' => 'ไป-กลับ (Roundtrip)', 'icon' => 'refresh-cw', 'color' => 'text-primary'];
    return ['label' => $type, 'icon' => 'truck', 'color' => ''];
}

function getTransportStatusBadge($status)
{
    switch ($status) {
        case 'pending':
            return '<span class="badge badge-sm badge-warning badge-outline">รอดำเนินการ</span>';
        case 'assigned':
            return '<span class="badge badge-sm badge-info badge-outline">มอบหมายคนขับแล้ว</span>';
        case 'in_transit':
            return '<span class="badge badge-sm badge-primary">กำลังเดินทาง</span>';
        case 'completed':
            return '<span class="badge badge-sm badge-success">เสร็จสิ้น</span>';
        case 'cancelled':
            return '<span class="badge badge-sm badge-error">ยกเลิกแล้ว</span>';
        default:
            return '<span class="badge badge-sm">' . $status . '</span>';
    }
}
?>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="truck" class="size-5 text-primary"></i>
                </div>
                บริการรับ-ส่ง (Pet Taxi)
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">จัดการตารางรถรับ-ส่งสัตว์เลี้ยงและมอบหมายคนขับ</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">งานวันนี้ทั้งหมด</p>
                <p class="text-2xl font-bold">
                    <?php echo $stats['total'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">รอมอบหมาย</p>
                <p class="text-2xl font-bold text-warning">
                    <?php echo $stats['pending'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">มอบหมายแล้ว</p>
                <p class="text-2xl font-bold text-info">
                    <?php echo $stats['assigned'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">กำลังเดินทาง</p>
                <p class="text-2xl font-bold text-primary">
                    <?php echo $stats['in_transit'] ?? 0; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div
            class="card-body p-4 flex flex-col sm:flex-row gap-4 items-end sm:items-center justify-between bg-base-100/50 rounded-2xl">
            <form method="GET" action="index.php" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="page" value="pet_transportation">

                <div class="form-control">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                        class="input input-bordered input-sm">
                </div>

                <div class="form-control">
                    <select name="status" class="select select-bordered select-sm w-40">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>สถานะทั้งหมด
                        </option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>รอดำเนินการ
                        </option>
                        <option value="assigned" <?php echo $status_filter === 'assigned' ? 'selected' : ''; ?>>
                            มอบหมายแล้ว</option>
                        <option value="in_transit" <?php echo $status_filter === 'in_transit' ? 'selected' : ''; ?>>
                            กำลังเดินทาง</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>
                            เสร็จสิ้น</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-sm btn-primary">กรองข้อมูล</button>
            </form>

            <!-- In future, could add "Add manual transport" button here -->
        </div>
    </div>

    <!-- Jobs Table -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="overflow-x-auto rounded-2xl">
            <table class="table table-zebra w-full">
                <thead class="bg-base-200/50 text-sm">
                    <tr>
                        <th class="w-10 text-center">เวลา</th>
                        <th>ข้อมูลลูกค้า & บริการ</th>
                        <th>สถานที่รับ-ส่ง</th>
                        <th>ข้อมูลคนขับ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-right">ราคา (บาท)</th>
                        <th class="text-center w-20">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transport_jobs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-8 text-base-content/50">
                                <i data-lucide="truck" class="size-12 mx-auto mb-3 opacity-20"></i>
                                ไม่มีรายการรับ-ส่งในวันที่เลือก
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transport_jobs as $job):
                            $type_info = getTransportTypeLabel($job['transport_type']);
                            ?>
                            <tr>
                                <td class="text-center font-bold text-lg">
                                    <?php echo date('H:i', strtotime($job['scheduled_datetime'])); ?>
                                </td>
                                <td>
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="<?php echo $type_info['icon']; ?>"
                                            class="size-5 mt-0.5 <?php echo $type_info['color']; ?>"></i>
                                        <div>
                                            <div class="font-bold flex items-center gap-2">
                                                <?php echo htmlspecialchars($job['customer_name']); ?>
                                                <a href="?page=booking_detail&id=<?php echo $job['booking_id']; ?>"
                                                    class="text-xs text-primary hover:underline font-mono bg-primary/10 px-1 rounded">
                                                    <?php echo htmlspecialchars($job['booking_ref']); ?>
                                                </a>
                                            </div>
                                            <div class="text-xs text-base-content/70 flex items-center gap-1 mt-0.5">
                                                <i data-lucide="phone" class="size-3"></i>
                                                <?php echo htmlspecialchars($job['customer_phone']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm max-w-xs truncate"
                                        title="<?php echo htmlspecialchars($job['address']); ?>">
                                        <?php echo htmlspecialchars($job['address']); ?>
                                    </div>
                                    <div class="text-xs text-base-content/50 mt-0.5">
                                        ระยะทาง:
                                        <?php echo (float) $job['distance_km']; ?> กม.
                                    </div>
                                </td>
                                <td>
                                    <?php if ($job['driver_name']): ?>
                                        <div class="font-medium text-sm text-base-content/90">
                                            <?php echo htmlspecialchars($job['driver_name']); ?>
                                        </div>
                                        <?php if ($job['driver_phone']): ?>
                                            <div class="text-xs text-base-content/60">
                                                <?php echo htmlspecialchars($job['driver_phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="dropdown dropdown-end">
                                            <button tabindex="0" class="btn btn-xs btn-outline btn-warning gap-1">
                                                <i data-lucide="user-plus" class="size-3"></i> มอบหมาย
                                            </button>
                                            <ul tabindex="0"
                                                class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52 border border-base-200 mt-1">
                                                <li><a onclick="openAssignModal(<?php echo $job['id']; ?>)">ระบุชื่อคนขับ</a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo getTransportStatusBadge($job['status']); ?>
                                </td>
                                <td class="text-right font-medium">
                                    <?php echo number_format($job['price'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown dropdown-end">
                                        <label tabindex="0" class="btn btn-xs btn-ghost btn-square">
                                            <i data-lucide="more-vertical" class="size-4 text-base-content/70"></i>
                                        </label>
                                        <ul tabindex="0"
                                            class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-48 border border-base-200">
                                            <li class="menu-title"><span>อัปเดตสถานะ</span></li>
                                            <?php if ($job['status'] !== 'assigned'): ?>
                                                <li><a onclick="updateStatus(<?php echo $job['id']; ?>, 'assigned')"><i
                                                            data-lucide="user-check" class="size-4 text-info"></i> มอบหมายคนขับ</a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ($job['status'] !== 'in_transit' && $job['status'] !== 'completed'): ?>
                                                <li><a onclick="updateStatus(<?php echo $job['id']; ?>, 'in_transit')"><i
                                                            data-lucide="navigation" class="size-4 text-primary"></i>
                                                        กำลังเดินทาง</a></li>
                                            <?php endif; ?>
                                            <?php if ($job['status'] !== 'completed'): ?>
                                                <li><a onclick="updateStatus(<?php echo $job['id']; ?>, 'completed')"><i
                                                            data-lucide="check-circle-2" class="size-4 text-success"></i>
                                                        เสร็จสิ้นภารกิจ</a></li>
                                            <?php endif; ?>
                                            <div class="divider my-0"></div>
                                            <li><a onclick="updateStatus(<?php echo $job['id']; ?>, 'cancelled')"
                                                    class="text-error"><i data-lucide="x-circle" class="size-4"></i>
                                                    ยกเลิกงาน</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Driver Modal -->
<dialog id="modal_assign_driver" class="modal">
    <div class="modal-box max-w-sm">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <i data-lucide="user-plus" class="size-5 text-primary"></i>
            ระบุคนขับรถ
        </h3>

        <form method="POST" action="?action=transportation" class="space-y-4">
            <input type="hidden" name="sub_action" value="assign_driver">
            <input type="hidden" name="transport_id" id="assign_transport_id">

            <div class="form-control">
                <label class="label"><span class="label-text">ชื่อคนขับ (หรือบริษัท Outsource) *</span></label>
                <input type="text" name="driver_name" class="input input-sm input-bordered" required>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text">เบอร์ติดต่อ *</span></label>
                <input type="text" name="driver_phone" class="input input-sm input-bordered" required>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">บันทึกข้อมูลคนขับ</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Form to submit status change -->
<form id="status-form" method="POST" action="?action=transportation" style="display: none;">
    <input type="hidden" name="sub_action" value="update_status">
    <input type="hidden" name="transport_id" id="status_transport_id">
    <input type="hidden" name="status" id="status_val">
</form>

<script>
    function openAssignModal(id) {
        document.getElementById('assign_transport_id').value = id;
        document.getElementById('modal_assign_driver').showModal();
    }

    function updateStatus(id, newStatus) {
        if (confirm('ยืนยันการเปลี่ยนสถานะ?')) {
            document.getElementById('status_transport_id').value = id;
            document.getElementById('status_val').value = newStatus;
            document.getElementById('status-form').submit();
        }
    }
</script>