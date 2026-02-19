<?php
$is_logged_in = isset($_SESSION['customer_id']);
$customer_name = $is_logged_in ? ($_SESSION['customer_name'] ?? 'ผู้ใช้') : '';

// Navigation items
$nav_items = [
    ['page' => 'home', 'label' => 'หน้าแรก', 'icon' => 'house'],
    ['page' => 'rooms', 'label' => 'ห้องพัก', 'icon' => 'paw-print'],
    ['page' => 'features', 'label' => 'บริการ', 'icon' => 'heart-handshake'],
    ['page' => 'contact', 'label' => 'ติดต่อเรา', 'icon' => 'mail'],
];
?>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- NAVBAR — Sticky top navbar with glassmorphism                     -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="bg-base-100/80 backdrop-blur-lg border-b border-base-200 sticky top-0 z-50 shadow-sm">
    <nav class="navbar container mx-auto px-4 lg:px-8">
        <!-- Mobile hamburger -->
        <div class="flex-none lg:hidden">
            <label for="mobile-drawer" aria-label="เปิดเมนู" class="btn btn-square btn-ghost">
                <i data-lucide="menu" class="size-5"></i>
            </label>
        </div>

        <!-- Logo -->
        <a href="?page=home" class="btn btn-ghost text-xl font-bold flex items-center gap-2">
            <img src="assets/favicon/logo.png" alt="<?php echo SITE_NAME; ?>" class="h-8 w-8 object-contain">
            <div class="hidden lg:block">
                <span class="text-primary">VET4</span> Hotel
            </div>
        </a>

        <!-- Desktop nav links (hidden on mobile) -->
        <div class="hidden lg:flex flex-1 justify-center">
            <ul class="menu menu-horizontal gap-1 px-1 font-medium text-sm">
                <?php foreach ($nav_items as $item): ?>
                    <?php $is_active = ($current_page === $item['page']); ?>
                    <li>
                        <a href="?page=<?php echo $item['page']; ?>" class="rounded-lg px-4 py-2 transition-all duration-200
                                  <?php echo $is_active
                                      ? 'bg-primary/10 text-primary'
                                      : 'hover:bg-base-200 hover:text-primary'; ?>">
                            <i data-lucide="<?php echo $item['icon']; ?>" class="size-4"></i>
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Vertical divider + Theme toggle -->
        <div class="flex items-center ml-auto lg:ml-0">
            <label class="swap swap-rotate btn btn-ghost btn-circle btn-sm">
                <input type="checkbox" data-toggle-theme="dark,light" data-act-class="ACTIVECLASS" />
                <i data-lucide="sun" class="swap-off size-5"></i>
                <i data-lucide="moon" class="swap-on size-5"></i>
            </label>
        </div>
        <div class="flex divider divider-horizontal mx-0 py-2"></div>

        <!-- Right-side auth section -->
        <div class="flex-none flex items-center gap-2">

            <?php if ($is_logged_in): ?>
                <!-- ── Logged-in: Profile dropdown ── -->
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder online">
                        <div class="bg-primary text-primary-content rounded-full w-10">
                            <span class="text-sm font-bold">
                                <?php echo mb_substr($customer_name, 0, 1); ?>
                            </span>
                        </div>
                    </div>
                    <ul tabindex="0"
                        class="dropdown-content menu bg-base-100 rounded-box z-60 mt-3 w-60 p-2 shadow-xl border border-base-200 space-y-1">

                        <!-- User info header -->
                        <li class="menu-title">
                            <div class="flex items-center gap-3 px-1 py-2">
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded-full w-10">
                                        <span class="text-sm font-bold">
                                            <?php echo mb_substr($customer_name, 0, 1); ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-semibold text-base-content text-sm">
                                        <?php echo htmlspecialchars($customer_name); ?>
                                    </p>
                                    <p class="text-xs text-base-content/60">สมาชิก</p>
                                </div>
                            </div>
                        </li>

                        <div class="divider my-0"></div>

                        <li>
                            <a href="?page=profile" class="flex items-center gap-2 rounded-lg">
                                <i data-lucide="user" class="size-4"></i>
                                ข้อมูลส่วนตัว
                            </a>
                        </li>
                        <li>
                            <a href="?page=my_pets" class="flex items-center gap-2 rounded-lg">
                                <i data-lucide="paw-print" class="size-4"></i>
                                สัตว์เลี้ยงของฉัน
                            </a>
                        </li>
                        <li>
                            <a href="?page=booking_history" class="flex items-center gap-2 rounded-lg">
                                <i data-lucide="calendar-clock" class="size-4"></i>
                                ประวัติการจอง
                            </a>
                        </li>

                        <div class="divider my-0"></div>

                        <li>
                            <a href="?page=logout" class="flex items-center gap-2 rounded-lg text-error hover:bg-error/10">
                                <i data-lucide="log-out" class="size-4"></i>
                                ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>

            <?php else: ?>
                <a href="?page=login" class="btn btn-ghost btn-sm gap-2 font-medium text-sm">
                    เข้าสู่ระบบ
                </a>
            <?php endif; ?>
        </div>
    </nav>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MOBILE DRAWER SIDEBAR                                             -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="drawer lg:hidden">
    <input id="mobile-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-side z-60">
        <label for="mobile-drawer" aria-label="ปิดเมนู" class="drawer-overlay"></label>

        <aside class="bg-base-100 min-h-full w-72 flex flex-col">

            <!-- Drawer header -->
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-base-200">
                <span class="font-bold text-lg tracking-tight">
                    <span class="text-primary">VET4</span> Hotel
                </span>
                
                <label for="mobile-drawer" class="btn btn-ghost btn-sm btn-circle">
                    <i data-lucide="x" class="size-4"></i>
                </label>
            </div>

            <!-- Drawer nav links -->
            <ul class="menu px-3 py-4 gap-1 flex-1 w-full">
                <?php foreach ($nav_items as $item): ?>
                    <?php $is_active = ($current_page === $item['page']); ?>
                    <li>
                        <a href="?page=<?php echo $item['page']; ?>" class="rounded-lg text-base font-medium transition-all duration-200
                                  <?php echo $is_active
                                      ? 'bg-primary/10 text-primary font-semibold'
                                      : 'hover:bg-base-200'; ?>">
                            <i data-lucide="<?php echo $item['icon']; ?>" class="size-5"></i>
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Drawer footer — auth section -->
            <div class="border-t border-base-200 p-4 space-y-2">
                <?php if ($is_logged_in): ?>
                    <!-- User info -->
                    <div class="flex items-center gap-3 px-2 pb-3">
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-full w-10">
                                <span class="text-sm font-bold">
                                    <?php echo mb_substr($customer_name, 0, 1); ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold text-sm"><?php echo htmlspecialchars($customer_name); ?></p>
                            <p class="text-xs text-base-content/50">สมาชิก</p>
                        </div>
                    </div>

                    <a href="?page=profile" class="btn btn-ghost btn-block btn-sm justify-start gap-2">
                        <i data-lucide="user" class="size-4"></i> ข้อมูลส่วนตัว
                    </a>
                    <a href="?page=my_pets" class="btn btn-ghost btn-block btn-sm justify-start gap-2">
                        <i data-lucide="paw-print" class="size-4"></i> สัตว์เลี้ยงของฉัน
                    </a>
                    <a href="?page=booking_history" class="btn btn-ghost btn-block btn-sm justify-start gap-2">
                        <i data-lucide="calendar-clock" class="size-4"></i> ประวัติการจอง
                    </a>
                    <div class="divider my-1"></div>
                    <a href="?page=logout" class="btn btn-error btn-outline btn-block btn-sm justify-start gap-2">
                        <i data-lucide="log-out" class="size-4"></i> ออกจากระบบ
                    </a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>