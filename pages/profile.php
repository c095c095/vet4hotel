<?php
// ═══════════════════════════════════════════════════════════
// PROFILE PAGE — VET4 HOTEL
// หน้าข้อมูลส่วนตัว: แสดง/แก้ไขข้อมูลลูกค้า + เปลี่ยนรหัสผ่าน
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['customer_id'])) {
    header("Location: ?page=login");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// ═══════════════════════════════════════════════════════════
// FETCH CUSTOMER DATA
// ═══════════════════════════════════════════════════════════

$customer = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        session_destroy();
        header("Location: ?page=login");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['msg_error'] = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
}

// Stats
$stats = ['total_bookings' => 0, 'total_pets' => 0, 'member_since' => ''];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $stats['total_bookings'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE customer_id = ? AND deleted_at IS NULL");
    $stmt->execute([$customer_id]);
    $stats['total_pets'] = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    // silently fail
}

function thaiDateFull_profile($date)
{
    if (!$date)
        return '-';
    $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $ts = strtotime($date);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = (int) date('Y', $ts) + 543;
    return "$d {$months[$m]} $y";
}

$stats['member_since'] = thaiDateFull_profile($customer['created_at']);
$full_name = htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
$initials = mb_substr($customer['first_name'] ?? 'U', 0, 1);
?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- PROFILE PAGE UI                                            -->
<!-- ═══════════════════════════════════════════════════════════ -->

<section class="py-6 md:py-10 bg-base-200/50 min-h-[85vh] relative overflow-hidden">
    <!-- Decorative -->
    <div class="absolute top-0 right-0 -mt-24 -mr-24 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none">
    </div>
    <div
        class="absolute bottom-0 left-0 -mb-24 -ml-24 w-96 h-96 bg-secondary/5 rounded-full blur-3xl pointer-events-none">
    </div>
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        <div class="floating-paw absolute top-[8%] left-[5%] opacity-10 text-primary" style="animation-delay:1s;">
            <i data-lucide="user" class="size-14"></i>
        </div>
        <div class="floating-paw absolute bottom-[10%] right-[6%] opacity-10 text-secondary"
            style="animation-delay:2.5s;">
            <i data-lucide="paw-print" class="size-16"></i>
        </div>
    </div>

    <div class="w-full max-w-4xl mx-auto px-4 relative z-10">
        <!-- ═══ PROFILE HEADER CARD ═══ -->
        <div class="card bg-base-100 shadow-lg border border-base-200 overflow-hidden mb-6 animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
            style="animation-delay:0.05s;">
            <div class="card-body p-0">
                <!-- Banner -->
                <div class="hero-gradient h-24 relative">
                    <div class="absolute -bottom-8 left-6">
                        <div class="avatar placeholder">
                            <div
                                class="bg-primary text-primary-content rounded-full w-16 h-16 ring ring-base-100 ring-offset-0 flex items-center justify-center shadow-lg">
                                <span class="text-2xl font-black">
                                    <?php echo $initials; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-5 pt-12">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                        <div>

                            <h2 class="text-xl font-bold text-base-content">
                                <?php echo $full_name; ?>
                            </h2>
                            <div class="flex items-center gap-3 mt-1 text-sm text-base-content/50">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="mail" class="size-3.5"></i>
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="size-3.5"></i>
                                    สมาชิกตั้งแต่
                                    <?php echo $stats['member_since']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Quick stats -->
                <div class="grid grid-cols-3 divide-x divide-base-200 border-t border-base-200">
                    <div class="px-4 py-3 text-center">
                        <div class="text-lg font-bold text-primary">
                            <?php echo $stats['total_bookings']; ?>
                        </div>
                        <div class="text-xs text-base-content/50 fon
                            t-medium">การจอง</div>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <div class="text-lg font-bold text-primary">
                            <?php echo $stats['total_pets']; ?>
                        </div>
                        <div class="text-xs text-base-content/50 font-medium">สัตว์เลี้ยง</div>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <div class="text-lg font-bold text-success">
                            <i data-lucide="shield-check" class="size-5 inline"></i>
                        </div>
                        <div class="text-xs text-base-content/50 font-medium">ยืนยันแล้ว</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ FORMS GRID ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- ─── Personal Info Form ─── -->
            <div class="card bg-base-100 shadow-lg border border-base-200 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                style="animation-delay:0.1s;">
                <div class="card-body p-6">
                    <h3 class="font-bold text-base flex items-center gap-2 mb-5">
                        <div class="bg-primary/10 text-primary p-1.5 rounded-lg">
                            <i data-lucide="user-pen" class="size-5"></i>
                        </div>
                        ข้อมูลทั่วไป
                    </h3>

                    <form action="?action=profile" method="POST" id="profile-form">
                        <input type="hidden" name="update_type" value="personal_info">

                        <!-- Name -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="form-control">
                                <label class="label pt-0 pb-1">
                                    <span class="label-text text-sm font-medium">ชื่อจริง <span
                                            class="text-error">*</span></span>
                                </label>
                                <label
                                    class="input input-bordered flex items-center gap-2 focus-within:border-primary bg-base-100/50">
                                    <i data-lucide="user" class="size-4 text-base-content/40"></i>
                                    <input type="text" name="first_name" class="grow" required
                                        value="<?php echo htmlspecialchars($customer['first_name']); ?>"
                                        placeholder="ชื่อ">
                                </label>
                            </div>
                            <div class="form-control">
                                <label class="label pt-0 pb-1">
                                    <span class="label-text text-sm font-medium">นามสกุล <span
                                            class="text-error">*</span></span>
                                </label>
                                <label
                                    class="input input-bordered flex items-center gap-2 focus-within:border-primary bg-base-100/50">
                                    <input type="text" name="last_name" class="grow" required
                                        value="<?php echo htmlspecialchars($customer['last_name']); ?>"
                                        placeholder="นามสกุล">
                                </label>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="form-control mb-4">
                            <label class="label pt-0 pb-1">
                                <span class="label-text text-sm font-medium">เบอร์โทรศัพท์ <span
                                        class="text-error">*</span></span>
                            </label>
                            <label
                                class="input input-bordered flex items-center gap-2 w-full focus-within:border-primary bg-base-100/50">
                                <i data-lucide="phone" class="size-4 text-base-content/40"></i>
                                <input type="tel" name="phone" class="grow" required pattern="[0-9]{9,10}"
                                    value="<?php echo htmlspecialchars($customer['phone']); ?>"
                                    placeholder="08xxxxxxxx">
                            </label>
                        </div>

                        <!-- Email (read-only) -->
                        <div class="form-control mb-4">
                            <label class="label pt-0 pb-1">
                                <span class="label-text text-sm font-medium">อีเมล</span>
                                <span class="label-text-alt text-xs text-base-content/40">ไม่สามารถเปลี่ยนได้</span>
                            </label>
                            <label
                                class="input input-bordered flex items-center gap-2 w-full bg-base-200/50 cursor-not-allowed">
                                <i data-lucide="mail" class="size-4 text-base-content/30"></i>
                                <input type="email" class="grow cursor-not-allowed" disabled
                                    value="<?php echo htmlspecialchars($customer['email']); ?>">
                                <i data-lucide="lock" class="size-3.5 text-base-content/20"></i>
                            </label>
                        </div>

                        <!-- Address -->
                        <div class="form-control mb-5">
                            <label class="label pt-0 pb-1">
                                <span class="label-text text-sm font-medium">ที่อยู่</span>
                            </label>
                            <textarea name="address"
                                class="textarea textarea-bordered w-full focus:border-primary bg-base-100/50 resize-none"
                                rows="3"
                                placeholder="บ้านเลขที่ ซอย ถนน ตำบล อำเภอ จังหวัด รหัสไปรษณีย์"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit"
                            class="btn btn-primary w-full gap-2 font-bold shadow-md shadow-primary/20">
                            <i data-lucide="save" class="size-4"></i>
                            บันทึกข้อมูล
                        </button>
                    </form>
                </div>
            </div>

            <!-- ─── Right Column ─── -->
            <div class="space-y-6">

                <!-- Emergency Contact -->
                <div class="card bg-base-100 shadow-lg border border-base-200 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                    style="animation-delay:0.15s;">
                    <div class="card-body p-6">
                        <h3 class="font-bold text-base flex items-center gap-2 mb-5">
                            <div class="bg-error/10 text-error p-1.5 rounded-lg">
                                <i data-lucide="siren" class="size-5"></i>
                            </div>
                            ผู้ติดต่อฉุกเฉิน
                        </h3>

                        <form action="?action=profile" method="POST">
                            <input type="hidden" name="update_type" value="emergency_contact">

                            <div class="form-control mb-4">
                                <label class="label pt-0 pb-1">
                                    <span class="label-text text-sm font-medium">ชื่อผู้ติดต่อฉุกเฉิน</span>
                                </label>
                                <label
                                    class="input input-bordered flex items-center gap-2 w-full focus-within:border-primary bg-base-100/50">
                                    <i data-lucide="user-check" class="size-4 text-base-content/40"></i>
                                    <input type="text" name="emergency_contact_name" class="grow"
                                        value="<?php echo htmlspecialchars($customer['emergency_contact_name'] ?? ''); ?>"
                                        placeholder="ชื่อ-นามสกุล ผู้ที่ติดต่อได้">
                                </label>
                            </div>

                            <div class="form-control mb-5">
                                <label class="label pt-0 pb-1">
                                    <span class="label-text text-sm font-medium">เบอร์ผู้ติดต่อฉุกเฉิน</span>
                                </label>
                                <label
                                    class="input input-bordered flex items-center gap-2 w-full focus-within:border-primary bg-base-100/50">
                                    <i data-lucide="phone-call" class="size-4 text-base-content/40"></i>
                                    <input type="tel" name="emergency_contact_phone" class="grow" pattern="[0-9]{9,10}"
                                        value="<?php echo htmlspecialchars($customer['emergency_contact_phone'] ?? ''); ?>"
                                        placeholder="08xxxxxxxx">
                                </label>
                            </div>

                            <div class="flex items-start gap-2 text-xs text-base-content/40 mb-4">
                                <i data-lucide="info" class="size-3.5 shrink-0 mt-0.5 text-warning"></i>
                                <span>หากเกิดเหตุฉุกเฉินกับสัตว์เลี้ยงของคุณ เราจะติดต่อบุคคลนี้เป็นลำดับแรก</span>
                            </div>

                            <button type="submit" class="btn btn-outline btn-primary w-full gap-2 font-bold">
                                <i data-lucide="save" class="size-4"></i>
                                บันทึกผู้ติดต่อฉุกเฉิน
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card bg-base-100 shadow-lg border border-base-200 overflow-hidden animate-[fadeInUp_0.4s_ease_forwards] opacity-0"
                    style="animation-delay:0.2s;">
                    <div class="card-body p-6">
                        <h3 class="font-bold text-base flex items-center gap-2 mb-5">
                            <div class="bg-warning/10 text-warning p-1.5 rounded-lg">
                                <i data-lucide="lock" class="size-5"></i>
                            </div>
                            เปลี่ยนรหัสผ่าน
                        </h3>

                        <form action="?action=profile" method="POST" id="password-form">
                            <input type="hidden" name="update_type" value="change_password">

                            <div class="form-control mb-4">
                                <label class="label pt-0 pb-1">
                                    <span class="label-text text-sm font-medium">รหัสผ่านปัจจุบัน <span
                                            class="text-error">*</span></span>
                                </label>
                                <label
                                    class="input input-bordered flex items-center gap-2 w-full focus-within:border-primary bg-base-100/50 relative">
                                    <i data-lucide="key-round" class="size-4 text-base-content/40"></i>
                                    <input type="password" name="current_password" id="current_password"
                                        class="grow pr-10" placeholder="••••••••" required>
                                    <button type="button"
                                        class="absolute right-3 text-base-content/40 hover:text-primary transition-colors"
                                        onclick="togglePwd('current_password', 'eye-cur')">
                                        <i data-lucide="eye" class="size-4" id="eye-cur"></i>
                                    </button>
                                </label>
                            </div>

                            <div class="form-control mb-4">
                                <label class="label pt-0 pb-1">
                                    <span class="label-text text-sm font-medium">รหัสผ่านใหม่ <span
                                            class="text-error">*</span></span>
                                </label>
                                <label
                                    class="input input-bordered flex items-center gap-2 w-full focus-within:border-primary bg-base-100/50 relative">
                                    <i data-lucide="lock" class="size-4 text-base-content/40"></i>
                                    <input type="password" name="new_password" id="new_password" class="grow pr-10"
                                        placeholder="••••••••" required minlength="6">
                                    <button type="button"
                                        class="absolute right-3 text-base-content/40 hover:text-primary transition-colors"
                                        onclick="togglePwd('new_password', 'eye-new')">
                                        <i data-lucide="eye" class="size-4" id="eye-new"></i>
                                    </button>
                                </label>
                            </div>

                            <div class="form-control mb-5">
                                <label class="label pt-0 pb-1">
                                    <span class="label-text text-sm font-medium">ยืนยันรหัสผ่านใหม่ <span
                                            class="text-error">*</span></span>
                                </label>
                                <label
                                    class="input input-bordered flex items-center gap-2 w-full focus-within:border-primary bg-base-100/50 relative">
                                    <i data-lucide="lock-keyhole" class="size-4 text-base-content/40"></i>
                                    <input type="password" name="confirm_password" id="confirm_password"
                                        class="grow pr-10" placeholder="••••••••" required minlength="6">
                                    <button type="button"
                                        class="absolute right-3 text-base-content/40 hover:text-primary transition-colors"
                                        onclick="togglePwd('confirm_password', 'eye-conf')">
                                        <i data-lucide="eye" class="size-4" id="eye-conf"></i>
                                    </button>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-warning btn-outline w-full gap-2 font-bold">
                                <i data-lucide="refresh-cw" class="size-4"></i>
                                เปลี่ยนรหัสผ่าน
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SCRIPTS                                                    -->
<!-- ═══════════════════════════════════════════════════════════ -->

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    function togglePwd(inputId, iconId) {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        if (inp.type === 'password') {
            inp.type = 'text';
            ico.setAttribute('data-lucide', 'eye-off');
        } else {
            inp.type = 'password';
            ico.setAttribute('data-lucide', 'eye');
        }
        lucide.createIcons();
    }
</script>