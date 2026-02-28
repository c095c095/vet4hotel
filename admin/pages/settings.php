<?php
// ═══════════════════════════════════════════════════════════
// ADMIN SETTINGS UI PAGE — VET4 HOTEL
// Tabbed interface for Employees, Payment Channels, Seasonal
// Pricing, and Lookup Tables
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/settings_data.php';

// Helpers
function active_badge($is_active)
{
    return $is_active
        ? '<span class="badge badge-sm badge-success gap-1"><i data-lucide="check" class="size-3"></i> เปิดใช้งาน</span>'
        : '<span class="badge badge-sm badge-error gap-1"><i data-lucide="x" class="size-3"></i> ปิดใช้งาน</span>';
}
?>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-6">

    <!-- ═══════════ HEADER ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="settings" class="size-5 text-primary"></i>
                </div>
                ตั้งค่าระบบ
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">จัดการพนักงาน ช่องทางชำระเงิน ราคาตามฤดูกาล
                และข้อมูลพื้นฐานระบบ</p>
        </div>
    </div>

    <!-- ═══════════ TABS ═══════════ -->
    <div role="tablist" class="tabs tabs-boxed bg-base-100/50 p-1 font-medium">
        <a href="?page=settings&tab=employees" role="tab"
            class="tab <?php echo $active_tab === 'employees' ? 'tab-active bg-primary text-primary-content' : ''; ?>">พนักงาน</a>
        <a href="?page=settings&tab=payment_channels" role="tab"
            class="tab <?php echo $active_tab === 'payment_channels' ? 'tab-active bg-primary text-primary-content' : ''; ?>">ช่องทางชำระเงิน</a>
        <a href="?page=settings&tab=seasonal_pricing" role="tab"
            class="tab <?php echo $active_tab === 'seasonal_pricing' ? 'tab-active bg-primary text-primary-content' : ''; ?>">ราคาตามเทศกาล</a>
        <a href="?page=settings&tab=lookup_tables" role="tab"
            class="tab <?php echo $active_tab === 'lookup_tables' ? 'tab-active bg-primary text-primary-content' : ''; ?>">ข้อมูลพื้นฐาน</a>
    </div>

    <!-- ═══════════════════════════════════════════════
        TAB 1: EMPLOYEES
    ════════════════════════════════════════════════ -->
    <?php if ($active_tab === 'employees'): ?>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-xs text-base-content/50 font-medium uppercase">พนักงานทั้งหมด</p>
                    <p class="text-2xl font-bold">
                        <?php echo $employee_stats['total']; ?>
                    </p>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-xs text-base-content/50 font-medium uppercase">ผู้ดูแลระบบ (Admin)</p>
                    <p class="text-2xl font-bold text-primary">
                        <?php echo $employee_stats['admin']; ?>
                    </p>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-xs text-base-content/50 font-medium uppercase">พนักงานทั่วไป (Staff)</p>
                    <p class="text-2xl font-bold text-base-content">
                        <?php echo $employee_stats['staff']; ?>
                    </p>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-xs text-base-content/50 font-medium uppercase">ปิดใช้งาน</p>
                    <p class="text-2xl font-bold text-error">
                        <?php echo $employee_stats['inactive']; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0">
                <div class="p-4 border-b border-base-200 flex justify-between items-center bg-base-100/50 rounded-t-2xl">
                    <h2 class="card-title text-lg font-bold">รายชื่อพนักงาน</h2>
                    <button onclick="document.getElementById('modal_add_employee').showModal()"
                        class="btn btn-sm btn-primary gap-2">
                        <i data-lucide="plus" class="size-4"></i>เพิ่มพนักงาน
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm w-full">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <th>อีเมล</th>
                                <th class="text-center">บทบาท</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td class="font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="avatar placeholder">
                                                <div class="bg-neutral text-neutral-content rounded-full w-8">
                                                    <span>
                                                        <?php echo mb_substr($emp['first_name'], 0, 1); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            <?php if ($emp['id'] == $_SESSION['employee_id']): ?>
                                                <span class="badge badge-xs badge-info ml-1">คุณ</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($emp['email']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($emp['role'] === 'admin'): ?>
                                            <span class="badge badge-sm badge-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-sm badge-ghost">Staff</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo active_badge($emp['is_active']); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                onclick='editEmployee(<?php echo json_encode($emp, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                class="btn btn-xs btn-ghost text-base-content/60 hover:text-primary"><i
                                                    data-lucide="pencil" class="size-3.5"></i></button>
                                            <button
                                                onclick="resetPassword(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name'], ENT_QUOTES); ?>')"
                                                class="btn btn-xs btn-ghost text-base-content/60 hover:text-info tooltip"
                                                data-tip="เปลี่ยนรหัสผ่าน"><i data-lucide="key" class="size-3.5"></i></button>
                                            <?php if ($emp['id'] != $_SESSION['employee_id']): ?>
                                                <?php if ($emp['is_active']): ?>
                                                    <button onclick="toggleEmployee(<?php echo $emp['id']; ?>, 0)"
                                                        class="btn btn-xs btn-ghost text-warning"><i data-lucide="pause-circle"
                                                            class="size-3.5"></i></button>
                                                <?php else: ?>
                                                    <button onclick="toggleEmployee(<?php echo $emp['id']; ?>, 1)"
                                                        class="btn btn-xs btn-ghost text-success"><i data-lucide="play-circle"
                                                            class="size-3.5"></i></button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Employee Modal -->
        <dialog id="modal_add_employee" class="modal">
            <div class="modal-box">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">เพิ่มพนักงานใหม่</h3>
                <form method="POST" action="?action=settings" class="space-y-4">
                    <input type="hidden" name="sub_action" value="add_employee">
                    <input type="hidden" name="active_tab" value="employees">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control"><label class="label"><span class="label-text">ชื่อ</span></label><input
                                type="text" name="first_name" class="input input-sm input-bordered" required></div>
                        <div class="form-control"><label class="label"><span class="label-text">นามสกุล</span></label><input
                                type="text" name="last_name" class="input input-sm input-bordered" required></div>
                    </div>
                    <div class="form-control"><label class="label"><span class="label-text">อีเมล
                                (ใช้สำหรับเข้าสู่ระบบ)</span></label><input type="email" name="email"
                            class="input input-sm input-bordered w-full" required></div>
                    <div class="form-control"><label class="label"><span class="label-text">บทบาท</span></label>
                        <select name="role" class="select select-sm select-bordered w-full">
                            <option value="staff">พนักงานทั่วไป (Staff)</option>
                            <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                        </select>
                    </div>
                    <div class="form-control"><label class="label"><span
                                class="label-text">รหัสผ่านชั่วคราว</span></label><input type="text" name="password"
                            minlength="8" class="input input-sm input-bordered w-full" required></div>
                    <button type="submit" class="btn btn-primary w-full mt-4">บันทึก</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <!-- Edit Employee Modal -->
        <dialog id="modal_edit_employee" class="modal">
            <div class="modal-box">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">แก้ไขข้อมูลพนักงาน</h3>
                <form method="POST" action="?action=settings" class="space-y-4">
                    <input type="hidden" name="sub_action" value="edit_employee">
                    <input type="hidden" name="active_tab" value="employees">
                    <input type="hidden" name="employee_id" id="edit_emp_id">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control"><label class="label"><span class="label-text">ชื่อ</span></label><input
                                type="text" name="first_name" id="edit_emp_fname" class="input input-sm input-bordered"
                                required></div>
                        <div class="form-control"><label class="label"><span class="label-text">นามสกุล</span></label><input
                                type="text" name="last_name" id="edit_emp_lname" class="input input-sm input-bordered"
                                required></div>
                    </div>
                    <div class="form-control"><label class="label"><span class="label-text">อีเมล</span></label><input
                            type="email" name="email" id="edit_emp_email" class="input input-sm input-bordered w-full" required>
                    </div>
                    <div class="form-control"><label class="label"><span class="label-text">บทบาท</span></label>
                        <select name="role" id="edit_emp_role" class="select select-sm select-bordered w-full">
                            <option value="staff">พนักงานทั่วไป (Staff)</option>
                            <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning w-full mt-4">บันทึกการแก้ไข</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <!-- Reset Password Modal -->
        <dialog id="modal_reset_pwd" class="modal">
            <div class="modal-box">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">รีเซ็ตรหัสผ่าน</h3>
                <p class="text-sm text-base-content/70 mb-4 text-error">ตั้งรหัสผ่านใหม่ให้: <strong
                        id="reset_emp_name"></strong></p>
                <form method="POST" action="?action=settings" class="space-y-4">
                    <input type="hidden" name="sub_action" value="reset_password">
                    <input type="hidden" name="active_tab" value="employees">
                    <input type="hidden" name="employee_id" id="reset_emp_id">
                    <div class="form-control"><label class="label"><span class="label-text">รหัสผ่านใหม่ (อักขระ 8
                                ตัวขึ้นไป)</span></label><input type="text" name="new_password" minlength="8"
                            class="input input-sm input-bordered w-full" required></div>
                    <button type="submit" class="btn btn-error w-full mt-4">เปลี่ยนรหัสผ่าน</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <script>
            function editEmployee(e) {
                document.getElementById('edit_emp_id').value = e.id;
                document.getElementById('edit_emp_fname').value = e.first_name;
                document.getElementById('edit_emp_lname').value = e.last_name;
                document.getElementById('edit_emp_email').value = e.email;
                document.getElementById('edit_emp_role').value = e.role;
                document.getElementById('edit_emp_role').disabled = (e.id == <?php echo $_SESSION['employee_id']; ?>); // cannot change own role
                document.getElementById('modal_edit_employee').showModal();
            }
            function resetPassword(id, name) {
                document.getElementById('reset_emp_id').value = id;
                document.getElementById('reset_emp_name').textContent = name;
                document.getElementById('modal_reset_pwd').showModal();
            }
            function toggleEmployee(id, st) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = '?action=settings';
                form.innerHTML = `<input type="hidden" name="sub_action" value="toggle_employee"><input type="hidden" name="active_tab" value="employees"><input type="hidden" name="employee_id" value="${id}"><input type="hidden" name="new_status" value="${st}">`;
                document.body.appendChild(form); form.submit();
            }
        </script>

        <!-- ═══════════════════════════════════════════════
        TAB 2: PAYMENT CHANNELS
    ════════════════════════════════════════════════ -->
    <?php elseif ($active_tab === 'payment_channels'): ?>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0">
                <div class="p-4 border-b border-base-200 flex justify-between items-center bg-base-100/50 rounded-t-2xl">
                    <h2 class="card-title text-lg font-bold">ช่องทางชำระเงิน</h2>
                    <button onclick="document.getElementById('modal_add_channel').showModal()"
                        class="btn btn-sm btn-primary gap-2">
                        <i data-lucide="plus" class="size-4"></i>เพิ่มช่องทาง
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm w-full">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th>ลำดับ</th>
                                <th>ประเภท</th>
                                <th>ชื่อช่องทาง / ธนาคาร</th>
                                <th>ชื่อบัญชี / เลขที่</th>
                                <th class="text-right">ค่าธรรมเนียม</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_channels as $c): ?>
                                <tr>
                                    <td class="text-center text-base-content/50">
                                        <?php echo $c['sort_order']; ?>
                                    </td>
                                    <td>
                                        <?php $cfg = $channel_type_config[$c['type']]; ?>
                                        <span class="badge badge-sm <?php echo $cfg['class']; ?> gap-1">
                                            <?php if ($c['icon_class']): ?><i data-lucide="<?php echo $c['icon_class']; ?>"
                                                    class="size-3"></i>
                                            <?php endif; ?>
                                            <?php echo $cfg['label']; ?>
                                        </span>
                                    </td>
                                    <td class="font-medium">
                                        <?php echo htmlspecialchars($c['name']); ?>
                                        <?php if ($c['bank_name'])
                                            echo "<div class='text-xs font-normal text-base-content/60'>" . htmlspecialchars($c['bank_name']) . "</div>"; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['account_name'] || $c['account_number']): ?>
                                            <?php if ($c['account_name'])
                                                echo "<div class='font-medium'>" . htmlspecialchars($c['account_name']) . "</div>"; ?>
                                            <?php if ($c['account_number'])
                                                echo "<div class='text-xs font-mono bg-base-200 inline-block px-1 rounded'>" . htmlspecialchars($c['account_number']) . "</div>"; ?>
                                        <?php else: ?>
                                            <span class="text-base-content/30">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <?php echo $c['fee_percent'] > 0 ? (float) $c['fee_percent'] . '%' : '-'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo active_badge($c['is_active']); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                onclick='editChannel(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                class="btn btn-xs btn-ghost text-base-content/60 hover:text-primary"><i
                                                    data-lucide="pencil" class="size-3.5"></i></button>
                                            <?php if ($c['is_active']): ?>
                                                <button onclick="toggleChannel(<?php echo $c['id']; ?>, 0)"
                                                    class="btn btn-xs btn-ghost text-warning"><i data-lucide="pause-circle"
                                                        class="size-3.5"></i></button>
                                            <?php else: ?>
                                                <button onclick="toggleChannel(<?php echo $c['id']; ?>, 1)"
                                                    class="btn btn-xs btn-ghost text-success"><i data-lucide="play-circle"
                                                        class="size-3.5"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <dialog id="modal_add_channel" class="modal">
            <div class="modal-box w-11/12 max-w-2xl">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">เพิ่ม/แก้ไข ช่องทางชำระเงิน</h3>
                <form method="POST" action="?action=settings" class="space-y-4">
                    <input type="hidden" name="sub_action" id="channel_sub_action" value="add_payment_channel">
                    <input type="hidden" name="active_tab" value="payment_channels">
                    <input type="hidden" name="channel_id" id="channel_id">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control"><label class="label"><span class="label-text text-error">ประเภทช่องทาง
                                    *</span></label>
                            <select name="type" id="channel_type" class="select select-sm select-bordered" required>
                                <option value="bank_transfer">โอนเงินธนาคาร</option>
                                <option value="qr_promptpay">QR พร้อมเพย์</option>
                                <option value="credit_card">บัตรเครดิต/เดบิต</option>
                                <option value="cash">เงินสด</option>
                            </select>
                        </div>
                        <div class="form-control"><label class="label"><span class="label-text text-error">ชื่อเรียกแสดงผล
                                    *</span></label><input type="text" name="name" id="channel_name"
                                class="input input-sm input-bordered" required></div>
                        <div class="form-control"><label class="label"><span class="label-text">ชื่อธนาคาร /
                                    องค์กร</span></label><input type="text" name="bank_name" id="channel_bank"
                                class="input input-sm input-bordered"></div>
                        <div class="form-control"><label class="label"><span
                                    class="label-text">ชื่อบัญชี</span></label><input type="text" name="account_name"
                                id="channel_accname" class="input input-sm input-bordered"></div>
                        <div class="form-control"><label class="label"><span class="label-text">เลขที่บัญชี / เบอร์ /
                                    ลิงก์</span></label><input type="text" name="account_number" id="channel_accnum"
                                class="input input-sm input-bordered font-mono"></div>
                        <div class="form-control"><label class="label"><span class="label-text">ชื่อไอคอน (Lucide
                                    icon)</span></label><input type="text" name="icon_class" id="channel_icon"
                                placeholder="เช่น landmark, qr-code, banknote" class="input input-sm input-bordered"></div>
                        <div class="form-control"><label class="label"><span class="label-text">ค่าธรรมเนียม %
                                    (แอดมินชาร์จลูกค้าเพิ่ม)</span></label><input type="number" step="0.01" min="0"
                                name="fee_percent" id="channel_fee" value="0.00" class="input input-sm input-bordered">
                        </div>
                        <div class="form-control"><label class="label"><span
                                    class="label-text">ลำดับการแสดงผล</span></label><input type="number" min="0"
                                name="sort_order" id="channel_sort" value="0" class="input input-sm input-bordered"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4">บันทึกช่องทาง</button>
                    <div class="text-xs text-center mt-2 text-base-content/50">ค่าธรรมเนียม 0 เท่ากับไม่มีค่าธรรมเนียม.
                        หากช่องเป็นเงินสด ไม่ต้องใส่ข้อมูลธนาคาร.</div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <script>
            function editChannel(c) {
                document.getElementById('channel_sub_action').value = 'edit_payment_channel';
                document.getElementById('channel_id').value = c.id;
                document.getElementById('channel_type').value = c.type;
                document.getElementById('channel_name').value = c.name;
                document.getElementById('channel_bank').value = c.bank_name || '';
                document.getElementById('channel_accname').value = c.account_name || '';
                document.getElementById('channel_accnum').value = c.account_number || '';
                document.getElementById('channel_icon').value = c.icon_class || '';
                document.getElementById('channel_fee').value = c.fee_percent || 0.00;
                document.getElementById('channel_sort').value = c.sort_order || 0;
                document.getElementById('modal_add_channel').showModal();
            }
            function toggleChannel(id, st) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = '?action=settings';
                form.innerHTML = `<input type="hidden" name="sub_action" value="toggle_payment_channel"><input type="hidden" name="active_tab" value="payment_channels"><input type="hidden" name="channel_id" value="${id}"><input type="hidden" name="new_status" value="${st}">`;
                document.body.appendChild(form); form.submit();
            }
        </script>


        <!-- ═══════════════════════════════════════════════
        TAB 3: SEASONAL PRICING
    ════════════════════════════════════════════════ -->
    <?php elseif ($active_tab === 'seasonal_pricing'): ?>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0">
                <div class="p-4 border-b border-base-200 flex justify-between items-center bg-base-100/50 rounded-t-2xl">
                    <div>
                        <h2 class="card-title text-lg font-bold">ราคาตามเทศกาล (Dynamic Pricing)</h2>
                        <p class="text-xs text-base-content/60">คูณ % เพิ่มจากราคาปกติ (Base Price) ในระบบ</p>
                    </div>
                    <button onclick="document.getElementById('modal_add_season').showModal()"
                        class="btn btn-sm btn-primary gap-2">
                        <i data-lucide="plus" class="size-4"></i>เพิ่มเทศกาล
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm w-full">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th>ชื่อเทศกาล / ช่วงฤดู</th>
                                <th>วันที่เริ่ม</th>
                                <th>วันที่สิ้นสุด</th>
                                <th class="text-right">บวกเพิ่มอัตรา (%)</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seasonal_pricings as $sp): ?>
                                <tr>
                                    <td class="font-medium">
                                        <?php echo htmlspecialchars($sp['season_name']); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($sp['start_date'])); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($sp['end_date'])); ?>
                                    </td>
                                    <td class="text-right font-medium text-error">+
                                        <?php echo (float) $sp['price_multiplier_percent']; ?>%
                                    </td>
                                    <td class="text-center">
                                        <?php echo active_badge($sp['is_active']); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                onclick='editSeason(<?php echo json_encode($sp, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                class="btn btn-xs btn-ghost text-base-content/60 hover:text-primary"><i
                                                    data-lucide="pencil" class="size-3.5"></i></button>
                                            <button onclick="deleteSeason(<?php echo $sp['id']; ?>)"
                                                class="btn btn-xs btn-ghost text-base-content/60 hover:text-error"><i
                                                    data-lucide="trash-2" class="size-3.5"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <dialog id="modal_add_season" class="modal">
            <div class="modal-box max-w-md">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">เพิ่ม/แก้ไข เทศกาล</h3>
                <form method="POST" action="?action=settings" class="space-y-4">
                    <input type="hidden" name="sub_action" id="season_sub_action" value="add_seasonal_pricing">
                    <input type="hidden" name="active_tab" value="seasonal_pricing">
                    <input type="hidden" name="seasonal_id" id="season_id">

                    <div class="form-control"><label class="label"><span class="label-text text-error">ชื่อเทศกาล
                                *</span></label><input type="text" name="season_name" id="season_name"
                            placeholder="เช่น ช่วงปีใหม่ 2026" class="input input-sm input-bordered w-full" required></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control"><label class="label"><span class="label-text text-error">วันที่เริ่ม
                                    *</span></label><input type="date" name="start_date" id="season_start"
                                class="input input-sm input-bordered" required></div>
                        <div class="form-control"><label class="label"><span class="label-text text-error">วันที่สิ้นสุด
                                    *</span></label><input type="date" name="end_date" id="season_end"
                                class="input input-sm input-bordered" required></div>
                    </div>
                    <div class="form-control"><label class="label"><span class="label-text text-error">% บวกเพิ่ม
                                (จากราคาปกติ) *</span></label>
                        <label class="input input-sm input-bordered flex items-center gap-2  w-full">
                            <input type="number" step="0.01" min="0" name="price_multiplier_percent" id="season_pct"
                                class="grow" required />
                            <span class="text-base-content/50">%</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4">บันทึก</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <script>
            function editSeason(s) {
                document.getElementById('season_sub_action').value = 'edit_seasonal_pricing';
                document.getElementById('season_id').value = s.id;
                document.getElementById('season_name').value = s.season_name;
                document.getElementById('season_start').value = s.start_date;
                document.getElementById('season_end').value = s.end_date;
                document.getElementById('season_pct').value = s.price_multiplier_percent;
                document.getElementById('modal_add_season').showModal();
            }
            function deleteSeason(id) {
                if (confirm("ต้องการลบช่วงเวลานี้จริงหรือไม่?")) {
                    const form = document.createElement('form'); form.method = 'POST'; form.action = '?action=settings';
                    form.innerHTML = `<input type="hidden" name="sub_action" value="delete_seasonal_pricing"><input type="hidden" name="active_tab" value="seasonal_pricing"><input type="hidden" name="seasonal_id" value="${id}">`;
                    document.body.appendChild(form); form.submit();
                }
            }
        </script>

        <!-- ═══════════════════════════════════════════════
        TAB 4: LOOKUP TABLES
    ════════════════════════════════════════════════ -->
    <?php elseif ($active_tab === 'lookup_tables'): ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Daily Update Types -->
            <div class="card bg-base-100 shadow-sm border border-base-200 h-fit">
                <div class="p-3 border-b border-base-200 flex justify-between items-center bg-base-100/50 rounded-t-2xl">
                    <h2 class="font-bold text-sm">ประเภทการส่งภาพอัปเดต</h2>
                    <button onclick="addLookup('daily_update_types', 'ประเภทภาพอัปเดต', true)"
                        class="btn btn-xs btn-ghost text-primary"><i data-lucide="plus" class="size-3.5"></i></button>
                </div>
                <ul class="menu menu-xs p-2 w-full">
                    <?php foreach ($daily_update_types as $item): ?>
                        <li class="flex flex-row items-center justify-between p-1.5 hover:bg-base-200/50 rounded">
                            <span
                                class="flex items-center gap-2 <?php echo $item['is_active'] ? '' : 'opacity-50 line-through'; ?>">
                                <?php if ($item['icon_class']): ?><i data-lucide="<?php echo $item['icon_class']; ?>"
                                        class="size-3.5"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </span>
                            <div class="flex gap-1 shrink-0">
                                <button
                                    onclick="editLookup('daily_update_types', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', '<?php echo $item['icon_class']; ?>', true)"
                                    class="btn btn-xs btn-square btn-ghost"><i data-lucide="pencil"
                                        class="size-3.5 text-base-content/50"></i></button>
                                <?php if ($item['is_active']): ?>
                                    <button onclick="toggleLookup('daily_update_types', <?php echo $item['id']; ?>, 0)"
                                        class="btn btn-xs btn-square btn-ghost text-warning"><i data-lucide="pause-circle"
                                            class="size-3.5"></i></button>
                                <?php else: ?>
                                    <button onclick="toggleLookup('daily_update_types', <?php echo $item['id']; ?>, 1)"
                                        class="btn btn-xs btn-square btn-ghost text-success"><i data-lucide="play-circle"
                                            class="size-3.5"></i></button>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Care Task Types -->
            <div class="card bg-base-100 shadow-sm border border-base-200 h-fit">
                <div class="p-3 border-b border-base-200 flex justify-between items-center bg-base-100/50 rounded-t-2xl">
                    <h2 class="font-bold text-sm">ประเภทงานดูแลพื้นฐาน</h2>
                    <button onclick="addLookup('care_task_types', 'ประเภทงานดูแล', false)"
                        class="btn btn-xs btn-ghost text-primary"><i data-lucide="plus" class="size-3.5"></i></button>
                </div>
                <ul class="menu menu-xs p-2 w-full">
                    <?php foreach ($care_task_types as $item): ?>
                        <li class="flex flex-row items-center justify-between p-1.5 hover:bg-base-200/50 rounded">
                            <span class="<?php echo $item['is_active'] ? '' : 'opacity-50 line-through'; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </span>
                            <div class="flex gap-1 shrink-0">
                                <button
                                    onclick="editLookup('care_task_types', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', null, false)"
                                    class="btn btn-xs btn-square btn-ghost"><i data-lucide="pencil"
                                        class="size-3.5 text-base-content/50"></i></button>
                                <?php if ($item['is_active']): ?>
                                    <button onclick="toggleLookup('care_task_types', <?php echo $item['id']; ?>, 0)"
                                        class="btn btn-xs btn-square btn-ghost text-warning"><i data-lucide="pause-circle"
                                            class="size-3.5"></i></button>
                                <?php else: ?>
                                    <button onclick="toggleLookup('care_task_types', <?php echo $item['id']; ?>, 1)"
                                        class="btn btn-xs btn-square btn-ghost text-success"><i data-lucide="play-circle"
                                            class="size-3.5"></i></button>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Medical Record Types -->
            <div class="card bg-base-100 shadow-sm border border-base-200 h-fit">
                <div class="p-3 border-b border-base-200 flex justify-between items-center bg-base-100/50 rounded-t-2xl">
                    <h2 class="font-bold text-sm">ประเภทประวัติสุขภาพ</h2>
                    <button onclick="addLookup('medical_record_types', 'ประเภทประวัติสุขภาพ', false)"
                        class="btn btn-xs btn-ghost text-primary"><i data-lucide="plus" class="size-3.5"></i></button>
                </div>
                <ul class="menu menu-xs p-2 w-full">
                    <?php foreach ($medical_record_types as $item): ?>
                        <li class="flex flex-row items-center justify-between p-1.5 hover:bg-base-200/50 rounded">
                            <span class="<?php echo $item['is_active'] ? '' : 'opacity-50 line-through'; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </span>
                            <div class="flex gap-1 shrink-0">
                                <button
                                    onclick="editLookup('medical_record_types', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', null, false)"
                                    class="btn btn-xs btn-square btn-ghost"><i data-lucide="pencil"
                                        class="size-3.5 text-base-content/50"></i></button>
                                <?php if ($item['is_active']): ?>
                                    <button onclick="toggleLookup('medical_record_types', <?php echo $item['id']; ?>, 0)"
                                        class="btn btn-xs btn-square btn-ghost text-warning"><i data-lucide="pause-circle"
                                            class="size-3.5"></i></button>
                                <?php else: ?>
                                    <button onclick="toggleLookup('medical_record_types', <?php echo $item['id']; ?>, 1)"
                                        class="btn btn-xs btn-square btn-ghost text-success"><i data-lucide="play-circle"
                                            class="size-3.5"></i></button>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>

        <dialog id="modal_lookup" class="modal">
            <div class="modal-box max-w-sm">
                <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4" id="lookup_title">เพิ่มข้อมูล</h3>
                <form method="POST" action="?action=settings" class="space-y-4">
                    <input type="hidden" name="sub_action" id="lookup_sub_action" value="add_lookup">
                    <input type="hidden" name="active_tab" value="lookup_tables">
                    <input type="hidden" name="table_name" id="lookup_table">
                    <input type="hidden" name="lookup_id" id="lookup_id">

                    <div class="form-control"><label class="label"><span class="label-text">ชื่อเรียก *</span></label><input
                            type="text" name="name" id="lookup_name" class="input input-sm input-bordered" required></div>

                    <div class="form-control" id="lookup_icon_wrap" style="display:none;">
                        <label class="label"><span class="label-text">พิมพ์ชื่อ Lucide icon <a
                                    href="https://lucide.dev/icons/" target="_blank"
                                    class="link text-xs text-primary">(ดูได้ที่นี่)</a></span></label>
                        <input type="text" name="icon_class" id="lookup_icon" placeholder="เช่น pill, camera"
                            class="input input-sm input-bordered">
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4">บันทึก</button>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>

        <script>
            function addLookup(table, title, showIcon) {
                document.getElementById('lookup_sub_action').value = 'add_lookup';
                document.getElementById('lookup_table').value = table;
                document.getElementById('lookup_id').value = '';
                document.getElementById('lookup_name').value = '';
                document.getElementById('lookup_icon').value = '';
                document.getElementById('lookup_title').textContent = 'เพิ่ม ' + title;
                document.getElementById('lookup_icon_wrap').style.display = showIcon ? 'block' : 'none';
                document.getElementById('modal_lookup').showModal();
            }
            function editLookup(table, id, name, icon, showIcon) {
                document.getElementById('lookup_sub_action').value = 'edit_lookup';
                document.getElementById('lookup_table').value = table;
                document.getElementById('lookup_id').value = id;
                document.getElementById('lookup_name').value = name;
                document.getElementById('lookup_icon').value = icon || '';
                document.getElementById('lookup_title').textContent = 'แก้ไขข้อมูล';
                document.getElementById('lookup_icon_wrap').style.display = showIcon ? 'block' : 'none';
                document.getElementById('modal_lookup').showModal();
            }
            function toggleLookup(table, id, st) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = '?action=settings';
                form.innerHTML = `<input type="hidden" name="sub_action" value="toggle_lookup"><input type="hidden" name="active_tab" value="lookup_tables"><input type="hidden" name="table_name" value="${table}"><input type="hidden" name="lookup_id" value="${id}"><input type="hidden" name="new_status" value="${st}">`;
                document.body.appendChild(form); form.submit();
            }
        </script>

    <?php endif; ?>

</div>