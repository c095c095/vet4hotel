<?php
// ═══════════════════════════════════════════════════════════
// ADMIN LOGIN PAGE — VET4 HOTEL DASHBOARD
// Secure staff/employee authentication interface
// ═══════════════════════════════════════════════════════════

// If employee is already logged in, redirect to dashboard
if (isset($_SESSION['employee_id'])) {
    header("Location: ?page=home");
    exit();
}

$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['error_msg']);
$success = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// check admin is exist if not redirect to setup page
$stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE role='admin'");
$employee_count = $stmt->fetchColumn();

if ($employee_count == 0) {
    header("Location: ?page=setup");
    exit();
}
?>

<section class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden bg-base-100 md:bg-base-200">
    <!-- Decorative background elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 left-0 w-full h-full bg-linear-to-br from-primary/5 via-transparent to-secondary/5"></div>
        <div class="absolute top-[15%] left-[15%] opacity-5 text-primary">
            <i data-lucide="shield" class="size-24"></i>
        </div>
        <div class="absolute bottom-[15%] right-[15%] opacity-5 text-primary" style="animation-delay: 2s;">
            <i data-lucide="settings" class="size-20"></i>
        </div>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10">
        <!-- Back to website -->
        <a href="../" class="inline-flex items-center gap-2 text-sm text-base-content/60 hover:text-primary transition-colors mb-4">
            <i data-lucide="arrow-left" class="size-4"></i>
            กลับสู่หน้าเว็บไซต์
        </a>

        <div class="md:bg-base-100 rounded-2xl md:shadow-xl md:border border-base-200 overflow-hidden">
            <div class="p-8 sm:p-10">
                <!-- Logo & Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 text-primary rounded-2xl mb-4">
                        <i data-lucide="shield-check" class="size-8"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-base-content">พนักงาน / ผู้ดูแลระบบ</h1>
                    <p class="text-base-content/60 text-sm mt-2">เข้าสู่ระบบจัดการหลังบ้าน</p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($error) || isset($_GET['error'])): ?>
                    <div class="alert alert-error text-sm rounded-xl mb-6 py-3">
                        <i data-lucide="alert-circle" class="size-4"></i>
                        <span><?php echo sanitize($error ?: ($_GET['error'] ?? 'เกิดข้อผิดพลาด')); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Success Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success text-sm rounded-xl mb-6 py-3">
                        <i data-lucide="check-circle" class="size-4"></i>
                        <span><?php echo sanitize($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="?action=login" method="POST" class="space-y-5">
                    <?php if (!empty($_GET['redirect'])): ?>
                        <input type="hidden" name="redirect" value="<?php echo sanitize($_GET['redirect']); ?>">
                    <?php endif; ?>

                    <!-- Email Input -->
                    <div class="form-control">
                        <label class="label pt-0" for="email">
                            <span class="label-text font-medium text-base-content/80">อีเมล</span>
                        </label>
                        <label class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 w-full">
                            <i data-lucide="mail" class="size-4 text-base-content/40"></i>
                            <input type="email" id="email" name="email" class="grow" placeholder="employee@vet4hotel.com"
                                required autocomplete="email"
                                value="<?php echo sanitize($_POST['email'] ?? ''); ?>" />
                        </label>
                    </div>

                    <!-- Password Input -->
                    <div class="form-control">
                        <div class="label pt-0">
                            <span class="label-text font-medium text-base-content/80" for="password">รหัสผ่าน</span>
                        </div>
                        <label class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 relative w-full">
                            <i data-lucide="lock" class="size-4 text-base-content/40"></i>
                            <input type="password" id="password" name="password" class="grow pr-10"
                                placeholder="••••••••" required autocomplete="current-password" />
                            <button type="button"
                                class="absolute right-3 hover:text-primary transition-colors text-base-content/40 cursor-pointer"
                                onclick="togglePassword()">
                                <i data-lucide="eye" class="size-4" id="eye-icon"></i>
                            </button>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="btn btn-primary w-full rounded-xl gap-2 font-medium text-base shadow-sm hover:shadow-md hover:shadow-primary/20 transition-all hover:scale-[1.02]">
                        <i data-lucide="log-in" class="size-4"></i>
                        เข้าสู่ระบบ
                    </button>
                </form>

                <!-- Security Notice -->
                <div class="mt-6 p-4 bg-base-200/50 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="size-4 text-base-content/50 mt-0.5 shrink-0"></i>
                        <p class="text-xs text-base-content/60">
                            ระบบนี้สงวนสิทธิ์เฉพาะพนักงานและผู้ดูแลระบบเท่านั้น 
                            หากคุณเป็นลูกค้า กรุณา<a href="../?page=login" class="text-primary hover:underline">เข้าสู่ระบบที่นี่</a>
                        </p>
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
    function togglePassword() {
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.setAttribute('data-lucide', 'eye-off');
            lucide.createIcons();
        } else {
            pwdInput.type = 'password';
            eyeIcon.setAttribute('data-lucide', 'eye');
            lucide.createIcons();
        }
    }
</script>
