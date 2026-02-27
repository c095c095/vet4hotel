<?php
// ═══════════════════════════════════════════════════════════
// INITIAL SETUP PAGE — VET4 HOTEL ADMIN
// First-time setup for creating the initial admin user
// ═══════════════════════════════════════════════════════════

// If employee is already logged in, redirect to dashboard
if (isset($_SESSION['employee_id'])) {
    header("Location: ?page=home");
    exit();
}

// Check if admin already exists - if yes, redirect to login
$stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE role='admin'");
$admin_count = $stmt->fetchColumn();

if ($admin_count > 0) {
    header("Location: ?page=login");
    exit();
}

$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['error_msg']);
$success = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);
?>

<section class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden bg-base-100 md:bg-base-200">
    <!-- Decorative background elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 left-0 w-full h-full bg-linear-to-br from-primary/5 via-transparent to-secondary/5"></div>
        <div class="absolute top-[15%] left-[15%] opacity-5 text-primary">
            <i data-lucide="settings" class="size-24"></i>
        </div>
        <div class="absolute bottom-[15%] right-[15%] opacity-5 text-primary" style="animation-delay: 2s;">
            <i data-lucide="user-cog" class="size-20"></i>
        </div>
    </div>

    <div class="max-w-lg w-full mx-auto relative z-10">
        <!-- Back to website -->
        <a href="../" class="inline-flex items-center gap-2 text-sm text-base-content/60 hover:text-primary transition-colors mb-4">
            <i data-lucide="arrow-left" class="size-4"></i>
            กลับสู่หน้าเว็บไซต์
        </a>

        <div class="md:bg-base-100 rounded-2xl md:border border-base-200 overflow-hidden">
            <div class="p-8 sm:p-10">
                <!-- Logo & Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 text-primary rounded-2xl mb-4">
                        <i data-lucide="rocket" class="size-8"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-base-content">ตั้งค่าระบบครั้งแรก</h1>
                    <p class="text-base-content/60 text-sm mt-2">สร้างบัญชีผู้ดูแลระบบเริ่มต้น</p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error text-sm rounded-xl mb-6 py-3">
                        <i data-lucide="alert-circle" class="size-4"></i>
                        <span><?php echo sanitize($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Success Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success text-sm rounded-xl mb-6 py-3">
                        <i data-lucide="check-circle" class="size-4"></i>
                        <span><?php echo sanitize($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Setup Form -->
                <form action="?action=setup" method="POST" class="space-y-5">
                    <!-- Admin Info Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-base-content/70 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="user" class="size-4"></i>
                            ข้อมูลผู้ดูแลระบบ
                        </h3>
                        
                        <!-- First Name & Last Name -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label pt-0 pb-1" for="first_name">
                                    <span class="label-text font-medium text-base-content/80">ชื่อ <span class="text-error">*</span></span>
                                </label>
                                <input type="text" id="first_name" name="first_name" 
                                    class="input input-bordered rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                                    placeholder="สมชาย" required
                                    value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>" />
                            </div>
                            <div class="form-control">
                                <label class="label pt-0 pb-1" for="last_name">
                                    <span class="label-text font-medium text-base-content/80">นามสกุล <span class="text-error">*</span></span>
                                </label>
                                <input type="text" id="last_name" name="last_name" 
                                    class="input input-bordered rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                                    placeholder="ใจดี" required
                                    value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>" />
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="form-control">
                            <label class="label pt-0 pb-1" for="email">
                                <span class="label-text font-medium text-base-content/80">อีเมล <span class="text-error">*</span></span>
                            </label>
                            <label class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 w-full">
                                <i data-lucide="mail" class="size-4 text-base-content/40"></i>
                                <input type="email" id="email" name="email" class="grow"
                                    placeholder="admin@vet4hotel.com" required
                                    value="<?php echo sanitize($_POST['email'] ?? ''); ?>" />
                            </label>
                        </div>

                        <!-- Password -->
                        <div class="form-control">
                            <label class="label pt-0 pb-1" for="password">
                                <span class="label-text font-medium text-base-content/80">รหัสผ่าน <span class="text-error">*</span></span>
                            </label>
                            <label class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 relative w-full">
                                <i data-lucide="lock" class="size-4 text-base-content/40"></i>
                                <input type="password" id="password" name="password" class="grow pr-10"
                                    placeholder="••••••••" required minlength="8" />
                                <button type="button"
                                    class="absolute right-3 hover:text-primary transition-colors text-base-content/40 cursor-pointer"
                                    onclick="togglePassword('password', 'eye-icon-pwd')">
                                    <i data-lucide="eye" class="size-4" id="eye-icon-pwd"></i>
                                </button>
                            </label>
                            <label class="label pb-0">
                                <span class="label-text-alt text-base-content/50">รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร</span>
                            </label>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-control">
                            <label class="label pt-0 pb-1" for="confirm_password">
                                <span class="label-text font-medium text-base-content/80">ยืนยันรหัสผ่าน <span class="text-error">*</span></span>
                            </label>
                            <label class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 relative w-full">
                                <i data-lucide="lock" class="size-4 text-base-content/40"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="grow pr-10"
                                    placeholder="••••••••" required />
                                <button type="button"
                                    class="absolute right-3 hover:text-primary transition-colors text-base-content/40 cursor-pointer"
                                    onclick="togglePassword('confirm_password', 'eye-icon-confirm')">
                                    <i data-lucide="eye" class="size-4" id="eye-icon-confirm"></i>
                                </button>
                            </label>
                        </div>
                    </div>

                    <div class="divider my-2"></div>

                    <!-- Database Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-base-content/70 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="database" class="size-4"></i>
                            การเชื่อมต่อฐานข้อมูล
                        </h3>
                        
                        <!-- DB Host -->
                        <div class="form-control">
                            <label class="label pt-0 pb-1" for="db_host">
                                <span class="label-text font-medium text-base-content/80">โฮสต์ฐานข้อมูล <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="db_host" name="db_host" 
                                class="input input-bordered rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                                placeholder="localhost" required
                                value="<?php echo sanitize($_POST['db_host'] ?? 'localhost'); ?>" />
                        </div>

                        <!-- DB Name -->
                        <div class="form-control">
                            <label class="label pt-0 pb-1" for="db_name">
                                <span class="label-text font-medium text-base-content/80">ชื่อฐานข้อมูล <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="db_name" name="db_name" 
                                class="input input-bordered rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                                placeholder="vet4_db" required
                                value="<?php echo sanitize($_POST['db_name'] ?? 'vet4_db'); ?>" />
                        </div>

                        <!-- DB User -->
                        <div class="form-control">
                            <label class="label pt-0 pb-1" for="db_user">
                                <span class="label-text font-medium text-base-content/80">ชื่อผู้ใช้ฐานข้อมูล <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="db_user" name="db_user" 
                                class="input input-bordered rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                                placeholder="root" required
                                value="<?php echo sanitize($_POST['db_user'] ?? 'root'); ?>" />
                        </div>

                        <!-- DB Password -->
                        <div class="form-control">
                            <label class="label pt-0 pb-1" for="db_pass">
                                <span class="label-text font-medium text-base-content/80">รหัสผ่านฐานข้อมูล</span>
                            </label>
                            <label class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 relative w-full">
                                <i data-lucide="key" class="size-4 text-base-content/40"></i>
                                <input type="password" id="db_pass" name="db_pass" class="grow pr-10"
                                    placeholder="(เว้นว่างถ้าไม่มี)" />
                                <button type="button"
                                    class="absolute right-3 hover:text-primary transition-colors text-base-content/40 cursor-pointer"
                                    onclick="togglePassword('db_pass', 'eye-icon-db')">
                                    <i data-lucide="eye" class="size-4" id="eye-icon-db"></i>
                                </button>
                            </label>
                        </div>
                    </div>

                    <!-- Initialize Data Checkbox -->
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3 py-2">
                            <input type="checkbox" name="init_data" value="1" class="checkbox checkbox-primary" checked />
                            <span class="label-text">เตรียมข้อมูลพื้นฐาน (สายพันธุ์สัตว์, ประเภทห้อง, บริการ, ฯลฯ)</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="btn btn-primary w-full rounded-xl gap-2 font-medium text-base shadow-sm hover:shadow-md hover:shadow-primary/20 transition-all hover:scale-[1.02]">
                        <i data-lucide="check-circle" class="size-4"></i>
                        ตั้งค่าระบบและสร้างบัญชีผู้ดูแล
                    </button>
                </form>

                <!-- Info Notice -->
                <div class="mt-6 p-4 bg-info/10 rounded-xl border border-info/20">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="size-4 text-info mt-0.5 shrink-0"></i>
                        <div class="text-xs text-base-content/70 space-y-1">
                            <p>หน้านี้จะแสดงเฉพาะครั้งแรกที่ติดตั้งระบบเท่านั้น</p>
                            <p>เมื่อตั้งค่าเสร็จสิ้น คุณจะถูกนำไปยังหน้าเข้าสู่ระบบ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-base-content/40 mt-6">
            © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> — ระบบจัดการโรงแรมสัตว์เลี้ยง
        </p>
    </div>
</section>

<script>
    function togglePassword(inputId, iconId) {
        const pwdInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(iconId);

        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.setAttribute('data-lucide', 'eye-off');
        } else {
            pwdInput.type = 'password';
            eyeIcon.setAttribute('data-lucide', 'eye');
        }
        lucide.createIcons();
    }
</script>
