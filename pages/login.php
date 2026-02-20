<?php
// ═══════════════════════════════════════════════════════════
// LOGIN PAGE — VET4 HOTEL
// Clean, trust-building authentication interface
// ═══════════════════════════════════════════════════════════

// If user is already logged in, redirect them (optional if session exists)
if (isset($_SESSION['customer_id'])) {
    header("Location: ?page=profile");
    exit();
}
?>

<section
    class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-base-200/40 relative overflow-hidden">
    <!-- Decorative floating elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="floating-paw absolute top-[10%] left-[10%] opacity-10 text-primary">
            <i data-lucide="paw-print" class="size-16"></i>
        </div>
        <div class="floating-paw absolute bottom-[20%] right-[10%] opacity-10 text-primary"
            style="animation-delay: 2s;">
            <i data-lucide="heart" class="size-12"></i>
        </div>
    </div>


    <div class="max-w-4xl w-full mx-auto relative z-10">
        <!-- Back to home -->
        <a href="?page=home"
            class="inline-flex items-center gap-2 text-sm text-base-content/60 hover:text-primary transition-colors">
            <i data-lucide="arrow-left" class="size-4"></i>
            กลับสู่หน้าหลัก
        </a>
        <div class="bg-base-100 rounded-3xl shadow-xl overflow-hidden border border-base-200">
            <div class="grid grid-cols-1 md:grid-cols-2">

                <!-- Left: Illustration / Image -->
                <div class="relative hidden md:block group">
                    <img src="assets/images/487456352_9682552058431752_5798845638060029487_n.jpg" alt="VET4 Pets"
                        class="absolute inset-0 w-full h-full object-cover">
                    <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/40 to-transparent"></div>

                    <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                        <h2 class="text-3xl font-bold mb-3">บ้านหลังที่สอง<br>ของลูกรักคุณ</h2>
                        <p class="text-white/80 text-sm">เข้าสู่ระบบเพื่อจัดการการจอง ติดตามสถานะเข้าพัก
                            และดูรายงานสุขภาพของน้องได้ตลอด 24 ชั่วโมง</p>
                    </div>
                </div>

                <!-- Right: Form -->
                <div class="p-8 sm:p-12 lg:p-14 flex flex-col justify-center">
                    <div class="text-center mb-8">
                        <div
                            class="inline-flex items-center justify-center w-12 h-12 bg-primary/10 text-primary rounded-xl mb-4">
                            <i data-lucide="user" class="size-6"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-base-content">ยินดีต้อนรับกลับมา</h1>
                        <p class="text-base-content/60 text-sm mt-2">กรุณาเข้าสู่ระบบเพื่อจัดการบัญชีของคุณ</p>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-error text-sm rounded-xl mb-6 py-3">
                            <i data-lucide="alert-circle" class="size-4"></i>
                            <span>อีเมลหรือรหัสผ่านไม่ถูกต้อง</span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert alert-success text-sm rounded-xl mb-6 py-3">
                            <i data-lucide="check-circle" class="size-4"></i>
                            <span>สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ</span>
                        </div>
                    <?php endif; ?>

                    <form action="?page=login" method="POST" class="space-y-5">
                        <!-- Email Input -->
                        <div class="form-control">
                            <label class="label pt-0" for="email">
                                <span class="label-text font-medium text-base-content/80">อีเมล</span>
                            </label>
                            <label
                                class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50">
                                <i data-lucide="mail" class="size-4 text-base-content/40"></i>
                                <input type="email" id="email" name="email" class="grow" placeholder="your@email.com"
                                    required autocomplete="email" />
                            </label>
                        </div>

                        <!-- Password Input -->
                        <div class="form-control">
                            <div class="label pt-0">
                                <span class="label-text font-medium text-base-content/80" for="password">รหัสผ่าน</span>
                                <a href="#"
                                    class="label-text-alt text-primary hover:underline font-medium">ลืมรหัสผ่าน?</a>
                            </div>
                            <label
                                class="input input-bordered flex items-center gap-3 rounded-xl focus-within:outline-primary/50 focus-within:border-primary transition-colors bg-base-100/50 relative">
                                <i data-lucide="lock" class="size-4 text-base-content/40"></i>
                                <input type="password" id="password" name="password" class="grow pr-10"
                                    placeholder="••••••••" required autocomplete="current-password" />
                                <button type="button"
                                    class="absolute right-3 hover:text-primary transition-colors text-base-content/40"
                                    onclick="togglePassword()">
                                    <i data-lucide="eye" class="size-4" id="eye-icon"></i>
                                </button>
                            </label>
                        </div>

                        <!-- Remember & Submit -->
                        <div class="flex items-center justify-between">
                            <label class="label cursor-pointer gap-2 justify-start ps-0">
                                <input type="checkbox" name="remember"
                                    class="checkbox checkbox-sm checkbox-primary rounded-md" />
                                <span class="label-text text-base-content/70">จดจำการเข้าระบบ</span>
                            </label>
                        </div>

                        <button type="submit"
                            class="btn btn-primary w-full rounded-xl gap-2 font-medium text-base shadow-sm hover:shadow-md hover:shadow-primary/20 transition-all hover:scale-[1.02]">
                            เข้าสู่ระบบ
                            <i data-lucide="arrow-right" class="size-4"></i>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="divider text-xs text-base-content/40 my-6">หรือ</div>

                    <!-- Register Link -->
                    <p class="text-center text-sm text-base-content/70">
                        ยังไม่มีบัญชีใช่หรือไม่?
                        <a href="?page=register" class="text-primary font-bold hover:underline">สมัครสมาชิกที่นี่</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function togglePassword() {
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            // Need to recreate icon if changing data-lucide attribute, 
            // or just rely on changing class if custom SVG. With lucide:
            eyeIcon.setAttribute('data-lucide', 'eye-off');
            lucide.createIcons();
        } else {
            pwdInput.type = 'password';
            eyeIcon.setAttribute('data-lucide', 'eye');
            lucide.createIcons();
        }
    }
</script>