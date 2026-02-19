<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : 'home';
$is_logged_in = isset($_SESSION['customer_id']);
?>

<nav class="navbar bg-base-100 shadow-lg sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <!-- Mobile Menu Button -->
        <div class="flex-none lg:hidden">
            <label for="mobile-drawer" class="btn btn-square btn-ghost drawer-button">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </label>
        </div>

        <!-- Logo -->
        <div class="flex-1 lg:flex-none">
            <a href="?page=home" class="btn btn-ghost text-xl font-bold text-primary gap-2">
                <i data-lucide="paw-print" class="w-8 h-8"></i>
                <span class="hidden sm:inline"><?php echo SITE_NAME; ?></span>
            </a>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden lg:flex flex-1 justify-center">
            <ul class="menu menu-horizontal px-1 gap-1">
                <li>
                    <a href="?page=home" class="<?php echo $current_page === 'home' ? 'menu-active' : ''; ?>">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        หน้าแรก
                    </a>
                </li>
                <li>
                    <a href="?page=rooms" class="<?php echo $current_page === 'rooms' ? 'menu-active' : ''; ?>">
                        <i data-lucide="bed-double" class="w-4 h-4"></i>
                        ห้องพัก
                    </a>
                </li>
                <li>
                    <a href="?page=features" class="<?php echo $current_page === 'features' ? 'menu-active' : ''; ?>">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        บริการ
                    </a>
                </li>
                <li>
                    <a href="?page=contact" class="<?php echo $current_page === 'contact' ? 'menu-active' : ''; ?>">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                        ติดต่อเรา
                    </a>
                </li>
            </ul>
        </div>

        <!-- Right Side Actions -->
        <div class="flex-none gap-2">
            <!-- Theme Toggle -->
            <label class="btn btn-ghost btn-circle swap swap-rotate">
                <input type="checkbox" class="theme-controller" value="dark" />
                <i data-lucide="sun" class="swap-off w-5 h-5"></i>
                <i data-lucide="moon" class="swap-on w-5 h-5"></i>
            </label>

            <?php if ($is_logged_in): ?>
                <!-- Logged In: User Dropdown -->
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                        <div class="bg-primary text-primary-content rounded-full w-10">
                            <span class="text-lg">
                                <?php echo isset($_SESSION['customer_name']) ? mb_substr($_SESSION['customer_name'], 0, 1) : 'U'; ?>
                            </span>
                        </div>
                    </div>
                    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[60] w-64 p-2 shadow-lg mt-2">
                        <li class="menu-title px-4 py-2">
                            <span class="text-base-content font-semibold">
                                <?php echo isset($_SESSION['customer_name']) ? htmlspecialchars($_SESSION['customer_name']) : 'ผู้ใช้งาน'; ?>
                            </span>
                        </li>
                        <div class="divider my-0"></div>
                        <li>
                            <a href="?page=profile" class="<?php echo $current_page === 'profile' ? 'menu-active' : ''; ?>">
                                <i data-lucide="user" class="w-4 h-4"></i>
                                ข้อมูลส่วนตัว
                            </a>
                        </li>
                        <li>
                            <a href="?page=my_pets" class="<?php echo $current_page === 'my_pets' ? 'menu-active' : ''; ?>">
                                <i data-lucide="heart" class="w-4 h-4"></i>
                                สัตว์เลี้ยงของฉัน
                            </a>
                        </li>
                        <li>
                            <a href="?page=booking" class="<?php echo $current_page === 'booking' ? 'menu-active' : ''; ?>">
                                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                                จองห้องพัก
                            </a>
                        </li>
                        <li>
                            <a href="?page=booking_history" class="<?php echo $current_page === 'booking_history' ? 'menu-active' : ''; ?>">
                                <i data-lucide="history" class="w-4 h-4"></i>
                                ประวัติการจอง
                            </a>
                        </li>
                        <li>
                            <a href="?page=active_stay" class="<?php echo $current_page === 'active_stay' ? 'menu-active' : ''; ?>">
                                <i data-lucide="video" class="w-4 h-4"></i>
                                ติดตามสถานะ (Live)
                                <span class="badge badge-sm badge-success">LIVE</span>
                            </a>
                        </li>
                        <div class="divider my-0"></div>
                        <li>
                            <a href="?page=logout" class="text-error hover:bg-error hover:text-error-content">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Not Logged In: Login/Register Buttons -->
                <a href="?page=login" class="btn btn-ghost btn-sm hidden sm:flex">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    เข้าสู่ระบบ
                </a>
                <a href="?page=register" class="btn btn-primary btn-sm">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">สมัครสมาชิก</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Drawer -->
<div class="drawer lg:hidden">
    <input id="mobile-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-side z-[60]">
        <label for="mobile-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
        <div class="menu bg-base-100 min-h-full w-80 p-4">
            <!-- Mobile Menu Header -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-base-200">
                <a href="?page=home" class="flex items-center gap-2 text-xl font-bold text-primary">
                    <i data-lucide="paw-print" class="w-8 h-8"></i>
                    <?php echo SITE_NAME; ?>
                </a>
                <label for="mobile-drawer" class="btn btn-ghost btn-circle btn-sm">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </label>
            </div>

            <!-- Mobile Navigation Links -->
            <ul class="space-y-1">
                <li>
                    <a href="?page=home" class="<?php echo $current_page === 'home' ? 'menu-active' : ''; ?>">
                        <i data-lucide="home" class="w-5 h-5"></i>
                        หน้าแรก
                    </a>
                </li>
                <li>
                    <a href="?page=rooms" class="<?php echo $current_page === 'rooms' ? 'menu-active' : ''; ?>">
                        <i data-lucide="bed-double" class="w-5 h-5"></i>
                        ห้องพัก
                    </a>
                </li>
                <li>
                    <a href="?page=features" class="<?php echo $current_page === 'features' ? 'menu-active' : ''; ?>">
                        <i data-lucide="sparkles" class="w-5 h-5"></i>
                        บริการ
                    </a>
                </li>
                <li>
                    <a href="?page=contact" class="<?php echo $current_page === 'contact' ? 'menu-active' : ''; ?>">
                        <i data-lucide="phone" class="w-5 h-5"></i>
                        ติดต่อเรา
                    </a>
                </li>
            </ul>

            <?php if ($is_logged_in): ?>
                <div class="divider">บัญชีของฉัน</div>
                <ul class="space-y-1">
                    <li>
                        <a href="?page=profile" class="<?php echo $current_page === 'profile' ? 'menu-active' : ''; ?>">
                            <i data-lucide="user" class="w-5 h-5"></i>
                            ข้อมูลส่วนตัว
                        </a>
                    </li>
                    <li>
                        <a href="?page=my_pets" class="<?php echo $current_page === 'my_pets' ? 'menu-active' : ''; ?>">
                            <i data-lucide="heart" class="w-5 h-5"></i>
                            สัตว์เลี้ยงของฉัน
                        </a>
                    </li>
                    <li>
                        <a href="?page=booking" class="<?php echo $current_page === 'booking' ? 'menu-active' : ''; ?>">
                            <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                            จองห้องพัก
                        </a>
                    </li>
                    <li>
                        <a href="?page=booking_history" class="<?php echo $current_page === 'booking_history' ? 'menu-active' : ''; ?>">
                            <i data-lucide="history" class="w-5 h-5"></i>
                            ประวัติการจอง
                        </a>
                    </li>
                    <li>
                        <a href="?page=active_stay" class="<?php echo $current_page === 'active_stay' ? 'menu-active' : ''; ?>">
                            <i data-lucide="video" class="w-5 h-5"></i>
                            ติดตามสถานะ (Live)
                            <span class="badge badge-sm badge-success">LIVE</span>
                        </a>
                    </li>
                </ul>
                <div class="mt-auto pt-4">
                    <a href="?page=logout" class="btn btn-error btn-outline w-full">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        ออกจากระบบ
                    </a>
                </div>
            <?php else: ?>
                <div class="mt-auto pt-4 space-y-2">
                    <a href="?page=login" class="btn btn-outline w-full">
                        <i data-lucide="log-in" class="w-5 h-5"></i>
                        เข้าสู่ระบบ
                    </a>
                    <a href="?page=register" class="btn btn-primary w-full">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        สมัครสมาชิก
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>