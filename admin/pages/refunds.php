<?php
// ═══════════════════════════════════════════════════════════
// REFUND MANAGEMENT UI - VET4 HOTEL ADMIN
// Manage cash refunds and credit notes
// ═══════════════════════════════════════════════════════════

// Filter
$status_filter = $_GET['status'] ?? 'pending';

$query = "SELECT 
            r.*,
            bk.booking_ref,
            c.first_name,
            c.last_name,
            c.phone,
            p.payment_type,
            p.amount as original_payment_amount,
            p.transaction_ref,
            pc.name as channel_name,
            e.first_name as processed_by_name
          FROM refunds r
          JOIN bookings bk ON r.booking_id = bk.id
          JOIN customers c ON bk.customer_id = c.id
          JOIN payments p ON r.payment_id = p.id
          LEFT JOIN payment_channels pc ON p.payment_channel_id = pc.id
          LEFT JOIN employees e ON r.processed_by_employee_id = e.id
          WHERE 1=1";

$params = [];
if ($status_filter !== 'all') {
    $query .= " AND r.status = :status";
    $params['status'] = $status_filter;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$refunds = $stmt->fetchAll();

// Stats
$stmt_stats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
    SUM(CASE WHEN status = 'processed' THEN refund_amount ELSE 0 END) as total_refunded_amount
    FROM refunds");
$stats = $stmt_stats->fetch();

function getRefundStatusBadge($status)
{
    if ($status === 'pending')
        return '<span class="badge badge-warning badge-sm badge-outline">รอดำเนินการ</span>';
    if ($status === 'processed')
        return '<span class="badge badge-success badge-sm">คืนเงินแล้ว</span>';
    if ($status === 'failed')
        return '<span class="badge badge-error badge-sm">ถูกปฏิเสธ/มีปัญหา</span>';
    return '<span class="badge badge-ghost badge-sm">' . $status . '</span>';
}
function getRefundTypeBadge($type)
{
    if ($type === 'cash')
        return '<span class="badge badge-neutral badge-sm">โอนเงินคืน (Cash)</span>';
    if ($type === 'credit_note')
        return '<span class="badge badge-info badge-sm">เครดิต (Credit Note)</span>';
    return '<span class="badge badge-ghost badge-sm">' . $type . '</span>';
}
?>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="banknote" class="size-5 text-primary"></i>
                </div>
                จัดการคืนเงิน (Refunds)
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">ประวัติและรายการขอคืนเงินให้ลูกค้า</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">คำร้องทั้งหมด</p>
                <p class="text-2xl font-bold">
                    <?php echo $stats['total'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">รอดำเนินการ</p>
                <p class="text-2xl font-bold text-warning">
                    <?php echo $stats['pending'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">คืนเงินแล้ว</p>
                <p class="text-2xl font-bold text-success">
                    <?php echo $stats['processed'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">ยอดเงินคืนสะสม (บาท)</p>
                <p class="text-2xl font-bold text-base-content">
                    <?php echo number_format($stats['total_refunded_amount'] ?? 0, 2); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 flex flex-col sm:flex-row gap-4 justify-between bg-base-100/50 rounded-2xl">
            <div role="tablist" class="tabs tabs-boxed bg-base-200/50 p-1 font-medium text-sm">
                <a href="?page=refunds&status=pending" role="tab"
                    class="tab <?php echo $status_filter === 'pending' ? 'tab-active bg-primary text-primary-content' : ''; ?>">รอดำเนินการ
                    <?php if (($stats['pending'] ?? 0) > 0): ?>
                        <span class="badge badge-sm badge-error ml-2">
                            <?php echo $stats['pending']; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?page=refunds&status=processed" role="tab"
                    class="tab <?php echo $status_filter === 'processed' ? 'tab-active bg-primary text-primary-content' : ''; ?>">คืนเงินแล้ว</a>
                <a href="?page=refunds&status=failed" role="tab"
                    class="tab <?php echo $status_filter === 'failed' ? 'tab-active bg-primary text-primary-content' : ''; ?>">ถูกปฏิเสธ/มีปัญหา</a>
                <a href="?page=refunds&status=all" role="tab"
                    class="tab <?php echo $status_filter === 'all' ? 'tab-active bg-primary text-primary-content' : ''; ?>">ทั้งหมด</a>
            </div>
        </div>
    </div>

    <!-- Refunds Table -->
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="overflow-x-auto rounded-2xl">
            <table class="table table-zebra table-sm w-full">
                <thead class="bg-base-200/50">
                    <tr>
                        <th class="w-32">วันที่ขอคืนเงิน</th>
                        <th>เลขอ้างอิง / ลูกค้า</th>
                        <th>รายละเอียดการโอนเดิม</th>
                        <th class="text-right">ยอดที่ต้องคืน</th>
                        <th class="text-center">ประเภทการคืน</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center w-20">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($refunds)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-8 text-base-content/50">
                                <i data-lucide="check-circle-2" class="size-12 mx-auto mb-3 opacity-20"></i>
                                ไม่มีรายการค้างดำเนินการ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($refunds as $r): ?>
                            <tr>
                                <td class="text-xs">
                                    <?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="font-bold flex items-center gap-2">
                                        <a href="?page=booking_detail&id=<?php echo $r['booking_id']; ?>"
                                            class="text-xs text-primary hover:underline font-mono bg-primary/10 px-1 rounded">
                                            <?php echo htmlspecialchars($r['booking_ref']); ?>
                                        </a>
                                    </div>
                                    <div class="text-sm">
                                        <?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?>
                                        <div class="text-[10px] text-base-content/60">
                                            <?php echo htmlspecialchars($r['phone']); ?>
                                        </div>
                                    </div>
                                    <?php if ($r['reason']): ?>
                                        <div class="text-xs text-error/80 mt-1 max-w-xs truncate"
                                            title="<?php echo htmlspecialchars($r['reason']); ?>">เหตุผล:
                                            <?php echo htmlspecialchars($r['reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-sm text-base-content/80">
                                        ยอดเดิม:
                                        <?php echo number_format($r['original_payment_amount'], 2); ?>
                                    </div>
                                    <div class="text-xs text-base-content/60">
                                        ช่องทาง:
                                        <?php echo htmlspecialchars($r['channel_name'] ?? 'ไม่ระบุ'); ?>
                                    </div>
                                    <?php if ($r['transaction_ref']): ?>
                                        <div class="text-[10px] font-mono mt-0.5 opacity-60">Ref:
                                            <?php echo htmlspecialchars($r['transaction_ref']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-bold text-error text-lg">
                                    <?php echo number_format($r['refund_amount'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo getRefundTypeBadge($r['refund_type']); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo getRefundStatusBadge($r['status']); ?>
                                    <?php if ($r['status'] !== 'pending' && $r['processed_by_name']): ?>
                                        <div class="text-[10px] text-base-content/40 mt-1">โดย:
                                            <?php echo htmlspecialchars($r['processed_by_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="dropdown dropdown-end">
                                            <label tabindex="0" class="btn btn-xs btn-primary btn-outline gap-1">
                                                จัดการ <i data-lucide="chevron-down" class="size-3"></i>
                                            </label>
                                            <ul tabindex="0"
                                                class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-48 border border-base-200">
                                                <li><a onclick="updateRefundStatus(<?php echo $r['id']; ?>, 'processed')"
                                                        class="text-success"><i data-lucide="check" class="size-4"></i>
                                                        อนุมัติการคืนเงินแล้ว</a></li>
                                                <li><a onclick="updateRefundStatus(<?php echo $r['id']; ?>, 'failed')"
                                                        class="text-error"><i data-lucide="x" class="size-4"></i>
                                                        ปฏิเสธการคืนเงิน</a></li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn btn-xs btn-ghost btn-square" disabled><i data-lucide="lock"
                                                class="size-4 text-base-content/30"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="status-form" method="POST" action="?action=refunds" style="display: none;">
    <input type="hidden" name="sub_action" value="process_refund">
    <input type="hidden" name="refund_id" id="status_refund_id">
    <input type="hidden" name="status" id="status_val">
</form>

<script>
    function updateRefundStatus(id, newStatus) {
        let msg = newStatus === 'processed'
            ? 'ยืนยันว่าคุณได้โอนเงินคืนให้ลูกค้าเรียบร้อยแล้ว?'
            : 'ยืนยันการปฏิเสธคำร้องนี้?';
        if (confirm(msg)) {
            document.getElementById('status_refund_id').value = id;
            document.getElementById('status_val').value = newStatus;
            document.getElementById('status-form').submit();
        }
    }
</script>