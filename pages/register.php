<?php
// ═══════════════════════════════════════════════════════════
// REGISTER PAGE — VET4 HOTEL
// Clean, welcoming registration interface for new pet parents
// ═══════════════════════════════════════════════════════════

// If user is already logged in, redirect them (optional if session exists)
if (isset($_SESSION['customer_id'])) {
    $redirect = $_GET['redirect'] ?? '?page=profile';
    if (empty($redirect))
        $redirect = '?page=profile';
    header("Location: " . $redirect);
    exit();
}

$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['error_msg']);

$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<section
    class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-base-200/40 relative overflow-hidden">
    <!-- Decorative floating elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="floating-paw absolute top-[10%] right-[10%] opacity-10 text-primary">
            <i data-lucide="paw-print" class="size-16"></i>
        </div>
        <div class="floating-paw absolute bottom-[15%] left-[8%] opacity-10 text-primary" style="animation-delay: 2s;">
            <i data-lucide="heart" class="size-12"></i>
        </div>
    </div>

    <div class="max-w-2xl w-full mx-auto relative z-10">
        <!-- Back to login -->
        <?php
        $login_url = '?page=login';
        if (!empty($_GET['redirect'])) {
            $login_url .= '&redirect=' . urlencode($_GET['redirect']);
        }
        ?>
        <a href="<?php echo sanitize($login_url); ?>"
            class="inline-flex items-center gap-2 text-sm text-base-content/60 hover:text-primary transition-colors mb-4">
            <i data-lucide="arrow-left" class="size-4"></i>
            กลับสู่หน้าล็อกอิน
        </a>
        <div
            class="md:bg-base-100 md:rounded-3xl md:shadow-xl md:border md:border-base-200 p-8 sm:p-10 lg:p-12 flex flex-col justify-center">
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-12 h-12 bg-primary/10 text-primary rounded-xl mb-4">
                    <i data-lucide="user-plus" class="size-6"></i>
                </div>
                <h1 class="text-2xl font-bold text-base-content">สมัครสมาชิกครอบครัว VET4</h1>
                <p class="text-base-content/60 text-sm mt-2">
                    กรอกข้อมูลด้านล่างเพื่อเริ่มต้นการใช้งานระบบโรงแรมสัตว์เลี้ยง</p>
            </div>

            <?php if (!empty($error) || isset($_GET['error'])): ?>
                <div class="alert alert-error text-sm rounded-xl mb-6 py-3">
                    <i data-lucide="alert-circle" class="size-4"></i>
                    <span>
                        <?php echo sanitize($error ?: ($_GET['error'] ?? 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง')); ?>
                    </span>
                </div>
            <?php endif; ?>

            <form action="?action=register" method="POST" class="space-y-4">
                <?php if (!empty($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo sanitize($_GET['redirect']); ?>">
                <?php endif; ?>
                <!-- Name Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label pt-0" for="first_name">
                            <span class="label-text font-medium text-base-content/80">ชื่อจริง</span>
                            <span class="text-error text-xs">*</span>
                        </label>
                        <label
                            class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50">
                            <i data-lucide="user" class="size-4 text-base-content/40"></i>
                            <input type="text" id="first_name" name="first_name" class="grow" placeholder="ชื่อ"
                                value="<?php echo sanitize($form_data['first_name'] ?? ''); ?>" required />
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label pt-0" for="last_name">
                            <span class="label-text font-medium text-base-content/80">นามสกุล</span>
                            <span class="text-error text-xs">*</span>
                        </label>
                        <label
                            class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50">
                            <input type="text" id="last_name" name="last_name" class="grow" placeholder="นามสกุล"
                                value="<?php echo sanitize($form_data['last_name'] ?? ''); ?>" required />
                        </label>
                    </div>
                </div>

                <!-- Phone Row -->
                <div class="form-control">
                    <label class="label pt-0" for="phone">
                        <span class="label-text font-medium text-base-content/80">เบอร์โทรศัพท์</span>
                        <span class="text-error text-xs">*</span>
                    </label>
                    <label
                        class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50">
                        <i data-lucide="phone" class="size-4 text-base-content/40"></i>
                        <input type="tel" id="phone" name="phone" class="grow" placeholder="08xxxxxxxx" required
                            pattern="[0-9]{9,10}" title="กรุณากรอกเบอร์โทรศัพท์ 9-10 หลัก"
                            value="<?php echo sanitize($form_data['phone'] ?? ''); ?>" />
                    </label>
                </div>

                <!-- Email Row -->
                <div class="form-control">
                    <label class="label pt-0" for="email">
                        <span class="label-text font-medium text-base-content/80">อีเมล</span>
                        <span class="text-error text-xs">*</span>
                    </label>
                    <label
                        class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50">
                        <i data-lucide="mail" class="size-4 text-base-content/40"></i>
                        <input type="email" id="email" name="email" class="grow" placeholder="your@email.com" required
                            autocomplete="email" value="<?php echo sanitize($form_data['email'] ?? ''); ?>" />
                    </label>
                </div>

                <!-- Password Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="form-control">
                        <label class="label pt-0" for="password">
                            <span class="label-text font-medium text-base-content/80">รหัสผ่าน</span>
                            <span class="text-error text-xs">*</span>
                        </label>
                        <label
                            class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 relative">
                            <i data-lucide="lock" class="size-4 text-base-content/40"></i>
                            <input type="password" id="password" name="password" class="grow pr-10"
                                placeholder="••••••••" required minlength="6" />
                            <button type="button"
                                class="absolute right-3 hover:text-primary transition-colors text-base-content/40"
                                onclick="togglePassword('password', 'eye-icon-1')">
                                <i data-lucide="eye" class="size-4" id="eye-icon-1"></i>
                            </button>
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label pt-0" for="confirm_password">
                            <span class="label-text font-medium text-base-content/80">ยืนยันรหัสผ่าน</span>
                            <span class="text-error text-xs">*</span>
                        </label>
                        <label
                            class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 relative">
                            <i data-lucide="lock-keyhole" class="size-4 text-base-content/40"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="grow pr-10"
                                placeholder="••••••••" required minlength="6" />
                            <button type="button"
                                class="absolute right-3 hover:text-primary transition-colors text-base-content/40"
                                onclick="togglePassword('confirm_password', 'eye-icon-2')">
                                <i data-lucide="eye" class="size-4" id="eye-icon-2"></i>
                            </button>
                        </label>
                    </div>
                </div>

                <!-- Terms Checkbox -->
                <div class="form-control mt-4">
                    <label class="label cursor-pointer justify-start gap-3 ps-0 items-start">
                        <input type="checkbox" required
                            class="checkbox checkbox-sm checkbox-primary mt-1 shrink-0 rounded-md" />
                        <span class="label-text text-base-content/70 leading-relaxed text-wrap wrap-break-word">
                            ฉันยอมรับ <a href="#" class="text-primary hover:underline">เงื่อนไขการให้บริการ</a>
                            และ <a href="#" class="text-primary hover:underline">นโยบายความเป็นส่วนตัว</a> ของ
                            VET4 Hotel
                        </span>
                    </label>
                </div>

                <button type="submit"
                    class="btn btn-primary w-full rounded-xl gap-2 font-medium text-base shadow-sm hover:shadow-md hover:shadow-primary/20 transition-all hover:scale-[1.01] mt-2">
                    สมัครสมาชิก
                    <i data-lucide="check" class="size-5"></i>
                </button>
            </form>

            <!-- Divider -->
            <div class="divider text-xs text-base-content/40 my-6">หรือ</div>

            <!-- Login Link -->
            <p class="text-center text-sm text-base-content/70">
                มีบัญชีอยู่แล้วใช่หรือไม่?
                <a href="<?php echo sanitize($login_url); ?>"
                    class="text-primary font-bold hover:underline">เข้าสู่ระบบที่นี่</a>
            </p>
        </div>
    </div>
</section>

<script>
    function togglePassword(inputId, iconId) {
        const pwdInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(iconId);

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