<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PROFILE PAGE — VET4 HOTEL
// Allows admin/staff to view and edit personal information
// and change their password.
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

$employee_id = (int) $_SESSION['employee_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT email, first_name, last_name, role FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['msg_error'] = "ไม่พบข้อมูลผู้ใช้";
    header("Location: ?page=home");
    exit();
}

$role_label = $user['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน';
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1200px] mx-auto">
    <!-- ═══════════ HEADER ═══════════ -->
    <div class="flex items-center gap-3">
        <div class="h-12 w-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <i data-lucide="user" class="size-6 text-primary"></i>
        </div>
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content">ข้อมูลส่วนตัว</h1>
            <p class="text-base-content/60 text-sm mt-1">
                จัดการข้อมูลส่วนตัวและรหัสผ่านของคุณ
            </p>
        </div>
    </div>

    <!-- ═══════════ TWO COLUMNS LAYOUT ═══════════ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- 1. PERSONAL INFO FORM -->
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="contact" class="size-5 text-primary"></i>
                    <h2 class="card-title text-lg font-bold">รายละเอียดบัญชี</h2>
                </div>

                <form action="?action=profile" method="POST" class="space-y-4">
                    <input type="hidden" name="sub_action" value="update_profile">

                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">บทบาท (Role)</span></label>
                        <input type="text" class="input input-bordered w-full bg-base-200 font-semibold"
                            value="<?php echo sanitize($role_label); ?>" disabled readonly>
                        <label class="label">
                            <span
                                class="label-text-alt text-base-content/50">คุณไม่สามารถเปลี่ยนบทบาทของตัวเองได้</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium">ชื่อ <span
                                        class="text-error">*</span></span></label>
                            <input type="text" name="first_name" class="input input-bordered w-full"
                                value="<?php echo sanitize($user['first_name']); ?>" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium">นามสกุล <span
                                        class="text-error">*</span></span></label>
                            <input type="text" name="last_name" class="input input-bordered w-full"
                                value="<?php echo sanitize($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">อีเมล <span
                                    class="text-error">*</span></span></label>
                        <input type="email" name="email" class="input input-bordered w-full"
                            value="<?php echo sanitize($user['email']); ?>" required>
                    </div>

                    <div class="card-actions justify-end mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save" class="size-4"></i>
                            บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CHANGE PASSWORD FORM -->
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-6">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="shield-check" class="size-5 text-warning"></i>
                    <h2 class="card-title text-lg font-bold">เปลี่ยนรหัสผ่าน</h2>
                </div>

                <form action="?action=profile" method="POST" class="space-y-4">
                    <input type="hidden" name="sub_action" value="change_password">

                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">รหัสผ่านปัจจุบัน <span
                                    class="text-error">*</span></span></label>
                        <input type="password" name="current_password" class="input input-bordered w-full" required>
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">รหัสผ่านใหม่ <span
                                    class="text-error">*</span></span></label>
                        <input type="password" name="new_password" class="input input-bordered w-full" minlength="8"
                            required>
                        <label class="label">
                            <span class="label-text-alt text-base-content/50">อย่างน้อย 8 ตัวอักษร</span>
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">ยืนยันรหัสผ่านใหม่ <span
                                    class="text-error">*</span></span></label>
                        <input type="password" name="confirm_password" class="input input-bordered w-full" minlength="8"
                            required>
                    </div>

                    <div class="card-actions justify-end mt-6">
                        <button type="submit" class="btn btn-warning">
                            <i data-lucide="key-round" class="size-4"></i>
                            เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>