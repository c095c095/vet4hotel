<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PAYMENTS PAGE UI — VET4 HOTEL
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/payments_data.php';

// Safe date default
$date_placeholder = date('Y-m-d');
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ HEADER ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
                <i data-lucide="credit-card" class="size-6 text-primary"></i>
                จัดการการชำระเงิน
            </h1>
            <p class="text-sm text-base-content/60 mt-1">ตรวจสอบและยืนยันสลิปการโอนเงิน คืนเงิน
                หรือดูรายการประวัติทั้งหมด</p>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="index.php" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="payments">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="รหัสการจอง, ชื่อลูกค้า, เบอร์โทร..." />
                    </label>
                </div>

                <!-- Status Filter -->
                <div class="form-control w-full xl:w-48">
                    <label class="label pt-0"><span class="label-text font-medium">สถานะ</span></label>
                    <select name="status" class="select select-bordered w-full focus:border-primary"
                        onchange="this.form.submit()">
                        <?php foreach ($status_config as $key => $config): ?>
                            <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $config['label']; ?> (
                                <?php echo $status_counts[$key] ?? 0; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="form-control w-full xl:w-48">
                    <label class="label pt-0"><span class="label-text font-medium">ประเภทการจ่าย</span></label>
                    <select name="type" class="select select-bordered w-full focus:border-primary"
                        onchange="this.form.submit()">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                        <?php foreach ($type_config as $key => $config): ?>
                            <option value="<?php echo $key; ?>" <?php echo $type_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $config['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2 mt-4 xl:mt-0">
                    <button type="submit" class="btn btn-primary flex-1 xl:flex-none">
                        <i data-lucide="filter" class="size-4"></i>
                        กรองข้อมูล
                    </button>
                    <a href="?page=payments"
                        class="btn btn-square btn-ghost text-base-content/70 hover:text-base-content tooltip"
                        data-tip="ล้างตัวกรอง">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                    </a>
                </div>
            </form>

            <?php if (!empty($status_counts)): ?>
                <!-- Quick Status Badges -->
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-base-200">
                    <span
                        class="text-xs font-medium text-base-content/50 uppercase tracking-widest mr-2 flex items-center">ตัวกรองด่วน:</span>
                    <?php foreach ($status_config as $key => $config): ?>
                        <?php if ($key !== 'all' && ($status_counts[$key] ?? 0) > 0): ?>
                            <a href="?page=payments&status=<?php echo $key; ?>"
                                class="badge <?php echo $config['class']; ?> badge-sm hover:opacity-80 transition flex items-center gap-1 border-0">
                                <?php echo $config['label']; ?> (
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
                        <th class="font-medium">รหัสชำระเงิน</th>
                        <th class="font-medium">ข้อมูลลูกค้า / การจอง</th>
                        <th class="font-medium">วันที่ทำรายการ</th>
                        <th class="font-medium text-right">จำนวนเงิน</th>
                        <th class="font-medium">ช่องทาง / ประเภท</th>
                        <th class="font-medium text-center">สถานะ</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-base-content/50">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="inbox" class="size-10 opacity-50"></i>
                                    ไม่มีข้อมูลการชำระเงินที่ตรงกับเงื่อนไขการค้นหา
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr class="hover group">
                                <td class="font-mono text-xs text-base-content/70">
                                    #
                                    <?php echo str_pad($p['id'], 5, '0', STR_PAD_LEFT); ?>
                                    <?php if ($p['transaction_ref']): ?>
                                        <div class="text-[10px] opacity-70 mt-1 truncate max-w-[120px]"
                                            title="Ref: <?php echo htmlspecialchars($p['transaction_ref']); ?>">
                                            Ref:
                                            <?php echo htmlspecialchars($p['transaction_ref']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder hidden sm:flex">
                                            <div
                                                class="bg-base-200 text-base-content rounded-xl w-10 h-10 flex items-center justify-center font-bold text-sm">
                                                <?php echo mb_substr($p['first_name'], 0, 1) . mb_substr($p['last_name'], 0, 1); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-medium text-sm flex items-center gap-2">
                                                <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>
                                            </div>
                                            <div class="text-xs text-base-content/60 mt-0.5 flex items-center gap-2">
                                                <a href="?page=booking_detail&id=<?php echo $p['booking_id']; ?>"
                                                    class="link link-primary font-mono font-semibold hover:underline">
                                                    <?php echo htmlspecialchars($p['booking_ref']); ?>
                                                </a>
                                                •
                                                <?php echo htmlspecialchars($p['phone'] ?? '-'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap">
                                    <div class="text-sm font-medium">
                                        <?php echo date('d M Y', strtotime($p['created_at'])); ?>
                                    </div>
                                    <div class="text-xs text-base-content/50">
                                        <?php echo date('H:i', strtotime($p['created_at'])); ?> น.
                                    </div>
                                </td>

                                <td class="text-right whitespace-nowrap">
                                    <div class="font-bold text-primary">฿
                                        <?php echo number_format($p['amount'], 2); ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="flex flex-col gap-1 items-start">
                                        <?php echo payment_type_badge_ui($p['payment_type']); ?>
                                        <div class="text-[11px] text-base-content/60 flex items-center gap-1 mt-0.5">
                                            <i data-lucide="wallet" class="size-3"></i>
                                            <?php echo htmlspecialchars($p['channel_name'] ?? 'ไม่ระบุช่องทาง'); ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <?php echo payment_status_badge_ui($p['status']); ?>

                                    <?php if ($p['status'] === 'verified' && $p['verifier_first_name']): ?>
                                        <div class="text-[10px] text-base-content/50 mt-1 whitespace-nowrap tooltip"
                                            data-tip="ตรวจสอบโดย: <?php echo htmlspecialchars($p['verifier_first_name']); ?>">
                                            <i data-lucide="user-check" class="size-3 inline align-middle"></i>
                                            <?php echo htmlspecialchars($p['verifier_first_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <button
                                        class="btn btn-sm <?php echo $p['status'] === 'pending' ? 'btn-primary' : 'btn-ghost'; ?>"
                                        onclick="document.getElementById('modal_payment_<?php echo $p['id']; ?>').showModal()">
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <i data-lucide="file-search" class="size-4"></i> ตรวจสอบ
                                        <?php else: ?>
                                            <i data-lucide="eye" class="size-4"></i> ดูรายละเอียด
                                        <?php endif; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ═══════════ PAGINATION ═══════════ -->
        <?php if ($total_pages > 1): ?>
            <div
                class="bg-base-200/30 border-t border-base-200 p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-base-content/70">
                    แสดง
                    <?php echo min($offset + 1, $total_records); ?> ถึง
                    <?php echo min($offset + $limit, $total_records); ?>
                    จากทั้งหมด <span class="font-bold">
                        <?php echo $total_records; ?>
                    </span> รายการ
                </div>
                <!-- Pagination buttons structure same as bookings -->
                <div class="join">
                    <?php if ($page > 1): ?>
                        <a href="?page=payments&p=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>"
                            class="join-item btn btn-sm bg-base-100 hover:bg-base-200 border-base-300">
                            <i data-lucide="chevron-left" class="size-4"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1): ?>
                        <a href="?page=payments&p=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>"
                            class="join-item btn btn-sm bg-base-100 border-base-300">1</a>
                        <?php if ($start_page > 2): ?>
                            <button class="join-item btn btn-sm btn-disabled bg-base-100 border-base-300">...</button>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=payments&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>"
                            class="join-item btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'bg-base-100 hover:bg-base-200 border-base-300'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <button class="join-item btn btn-sm btn-disabled bg-base-100 border-base-300">...</button>
                        <?php endif; ?>
                        <a href="?page=payments&p=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>"
                            class="join-item btn btn-sm bg-base-100 border-base-300">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=payments&p=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>"
                            class="join-item btn btn-sm bg-base-100 hover:bg-base-200 border-base-300">
                            <i data-lucide="chevron-right" class="size-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════ MODALS ═══════════ -->
    <?php foreach ($payments as $p): ?>
        <dialog id="modal_payment_<?php echo $p['id']; ?>" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box w-11/12 max-w-3xl p-0 overflow-hidden bg-base-100 rounded-2xl">
                <!-- Modal Header -->
                <div class="bg-base-200/50 border-b border-base-200 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-base-content flex items-center gap-2">
                            <i data-lucide="file-search" class="size-5 text-primary"></i>
                            รายละเอียดการชำระเงิน #
                            <?php echo str_pad($p['id'], 5, '0', STR_PAD_LEFT); ?>
                        </h3>
                        <p class="text-sm text-base-content/60 mt-1">
                            การจอง <a href="?page=booking_detail&id=<?php echo $p['booking_id']; ?>"
                                class="link font-mono link-primary hover:underline" target="_blank">
                                <?php echo htmlspecialchars($p['booking_ref']); ?>
                            </a>
                            •
                            <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>
                        </p>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left: Proof Image -->
                        <div>
                            <div class="text-sm font-semibold mb-3">สลิป/หลักฐานการโอนเงิน</div>
                            <div
                                class="bg-base-200 rounded-xl flex items-center justify-center p-2 min-h-[300px] border border-base-300 relative group overflow-hidden">
                                <?php if (!empty($p['proof_image_url'])): ?>
                                    <div
                                        class="text-xs text-base-content/50 absolute top-2 right-2 bg-base-100/80 px-2 py-1 rounded backdrop-blur z-10">
                                        คลิกเพื่อดูรูปใหญ่
                                    </div>
                                    <a href="../<?php echo htmlspecialchars($p['proof_image_url']); ?>" target="_blank"
                                        class="block w-full h-full">
                                        <img src="../<?php echo htmlspecialchars($p['proof_image_url']); ?>" alt="Slip"
                                            class="w-full h-auto max-h-[400px] object-contain rounded-lg transition-transform group-hover:scale-105 duration-300"
                                            onerror="this.src='<?php echo assets('images/placeholder.png'); ?>'">
                                    </a>
                                <?php else: ?>
                                    <div class="flex flex-col items-center justify-center text-base-content/40 space-y-2 py-10">
                                        <i data-lucide="image-off" class="size-16"></i>
                                        <span class="text-sm">ไม่มีรูปหลักฐานแนบมา</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right: Payment Details -->
                        <div class="space-y-6">
                            <div>
                                <div class="text-sm font-semibold mb-3 border-b border-base-200 pb-2">ข้อมูลธุรกรรม</div>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-base-content/60">วันที่และเวลา:</span>
                                        <span class="font-medium text-right">
                                            <?php echo date('d M Y, H:i:s', strtotime($p['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-base-content/60">ช่องทางการชำระ:</span>
                                        <span class="font-medium text-right">
                                            <?php echo htmlspecialchars($p['channel_name'] ?? '-'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-base-content/60">ประเภท:</span>
                                        <span class="text-right">
                                            <?php echo payment_type_badge_ui($p['payment_type']); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-base-content/60">รหัสอ้างอิง:</span>
                                        <span class="font-mono font-medium text-right">
                                            <?php echo htmlspecialchars($p['transaction_ref'] ?: '-'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-base-content/60">สถานะปัจจุบัน:</span>
                                        <div class="text-right">
                                            <?php echo payment_status_badge_ui($p['status']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-primary/5 rounded-xl border border-primary/20 p-4">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-semibold text-primary">ยอดเงินที่ต้องตรวจสอบ</span>
                                    <span class="text-2xl font-bold text-primary">฿
                                        <?php echo number_format($p['amount'], 2); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($p['status'] !== 'pending'): ?>
                                <div
                                    class="alert <?php echo $p['status'] === 'verified' ? 'alert-success' : 'alert-error'; ?> shadow-sm text-sm p-3">
                                    <?php if ($p['status'] === 'verified'): ?>
                                        <i data-lucide="check-circle-2" class="size-5"></i>
                                        <span>รายการนี้ถูกตรวจสอบและยืนยันแล้ว
                                            <?php echo $p['verifier_first_name'] ? "โดย {$p['verifier_first_name']} {$p['verifier_last_name']}" : ""; ?>
                                        </span>
                                    <?php else: ?>
                                        <i data-lucide="x-circle" class="size-5"></i>
                                        <span>รายการนี้ถูกปฏิเสธแล้ว
                                            <?php echo $p['verifier_first_name'] ? "โดย {$p['verifier_first_name']} {$p['verifier_last_name']}" : ""; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="border-t border-base-200 px-6 py-4 flex items-center justify-between bg-base-200/30">
                    <form method="dialog">
                        <button class="btn btn-ghost hover:bg-base-300">ปิด</button>
                    </form>

                    <?php if ($p['status'] === 'pending'): ?>
                        <div class="flex gap-2">
                            <button type="button"
                                onclick="openPaymentConfirmModal(<?php echo $p['id']; ?>, 'reject', '<?php echo number_format($p['amount'], 2); ?>')"
                                class="btn btn-error text-white">
                                <i data-lucide="x" class="size-4"></i> ปฏิเสธ
                            </button>

                            <button type="button"
                                onclick="openPaymentConfirmModal(<?php echo $p['id']; ?>, 'verify', '<?php echo number_format($p['amount'], 2); ?>')"
                                class="btn btn-success text-white">
                                <i data-lucide="check" class="size-4"></i> ยืนยันว่าถูกต้อง
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    <?php endforeach; ?>
</div>

<!-- ═══════════ CONFIRM ACTION MODAL ═══════════ -->
<dialog id="modal_confirm_payment" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div id="payment_confirm_icon_wrap"
                class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-warning/10">
                <i id="payment_confirm_icon" data-lucide="alert-triangle" class="size-7 text-warning"></i>
            </div>
            <h3 class="font-bold text-lg mb-2" id="payment_confirm_title">ยืนยันการทำรายการ</h3>
            <p class="text-base-content/60" id="payment_confirm_message">ต้องการยืนยันใช่หรือไม่?</p>
        </div>
        <form method="POST" action="?action=payment" id="payment_status_form">
            <input type="hidden" name="payment_id" id="payment_confirm_id">
            <input type="hidden" name="payment_action" id="payment_confirm_action">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_confirm_payment').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" id="payment_confirm_submit_btn" class="btn btn-warning gap-2">
                    <i id="payment_confirm_submit_icon" data-lucide="check" class="size-4"></i>
                    <span id="payment_confirm_submit_text">ยืนยัน</span>
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    function openPaymentConfirmModal(paymentId, action, amountFormatted) {
        // Populate hidden fields
        document.getElementById('payment_confirm_id').value = paymentId;
        document.getElementById('payment_confirm_action').value = action;

        const titleEl = document.getElementById('payment_confirm_title');
        const msgEl = document.getElementById('payment_confirm_message');
        const btn = document.getElementById('payment_confirm_submit_btn');
        const iconWrap = document.getElementById('payment_confirm_icon_wrap');
        const icon = document.getElementById('payment_confirm_icon');
        const submitIcon = document.getElementById('payment_confirm_submit_icon');
        const submitText = document.getElementById('payment_confirm_submit_text');

        if (action === 'verify') {
            titleEl.textContent = 'ยืนยันการชำระเงิน';
            msgEl.innerHTML = 'ตรวจสอบและ <strong class="text-success">"ยืนยัน"</strong> การชำระเงินยอด <strong>฿' + amountFormatted + '</strong> ถูกต้องหรือไม่?';

            btn.className = 'btn gap-2 btn-success text-white';
            iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-success/10';

            icon.setAttribute('data-lucide', 'check-circle-2');
            icon.className = 'size-7 text-success';

            submitIcon.setAttribute('data-lucide', 'check');
            submitText.textContent = 'ยืนยันว่าถูกต้อง';
        } else {
            titleEl.textContent = 'ปฏิเสธการชำระเงิน';
            msgEl.innerHTML = 'ยืนยัน <strong class="text-error">"การปฏิเสธ"</strong> รายการชำระเงินยอด <strong>฿' + amountFormatted + '</strong> ใช่หรือไม่?';

            btn.className = 'btn gap-2 btn-error text-white';
            iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-error/10';

            icon.setAttribute('data-lucide', 'x-circle');
            icon.className = 'size-7 text-error';

            submitIcon.setAttribute('data-lucide', 'x');
            submitText.textContent = 'ปฏิเสธ';
        }

        // Must re-init lucide icons since we changed data-lucide attribute dynamically
        if (typeof lucide !== 'undefined') lucide.createIcons();

        document.getElementById('modal_confirm_payment').showModal();
    }
</script>