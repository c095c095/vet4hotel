<?php
// ═══════════════════════════════════════════════════════════
// ADMIN SIDEBAR — VET4 HOTEL DASHBOARD
// Persistent drawer-style navigation for all admin pages
// ═══════════════════════════════════════════════════════════

$nav_items = [
    ['page' => 'home', 'label' => 'แดชบอร์ด', 'icon' => 'layout-dashboard'],
    ['page' => 'bookings', 'label' => 'การจอง', 'icon' => 'calendar-range'],
    ['page' => 'rooms', 'label' => 'ห้องพัก', 'icon' => 'door-open'],
    ['page' => 'customers', 'label' => 'ลูกค้า', 'icon' => 'users'],
    ['page' => 'pets', 'label' => 'สัตว์เลี้ยง', 'icon' => 'paw-print'],
    ['page' => 'care_tasks', 'label' => 'งานดูแลรายวัน', 'icon' => 'clipboard-check'],
    ['page' => 'payments', 'label' => 'การชำระเงิน', 'icon' => 'credit-card'],
    ['page' => 'services', 'label' => 'บริการเสริม', 'icon' => 'sparkles'],
    ['page' => 'room_types', 'label' => 'ประเภทห้องพัก', 'icon' => 'bed-double'],
    ['page' => 'promotions', 'label' => 'โปรโมชัน', 'icon' => 'tag'],
];

$employee_name = $_SESSION['user_name'] ?? 'พนักงาน';
$employee_role = $_SESSION['employee_role'] ?? 'staff';
$role_label = $employee_role === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน';
?>

<!-- Sidebar Drawer Content -->
<div class="drawer-side z-50">
    <label for="admin-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
    <aside class="bg-base-100 border-r border-base-200 min-h-screen w-72 flex flex-col">
        <!-- Brand Header -->
        <div class="px-6 py-5 border-b border-base-200">
            <a href="?page=home" class="flex items-center gap-3 group">
                <div
                    class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                    <i data-lucide="paw-print" class="size-5 text-primary"></i>
                </div>
                <div>
                    <h1 class="font-[Outfit] font-bold text-lg text-primary leading-tight"><?php echo SITE_NAME; ?></h1>
                    <p class="text-[10px] text-base-content/40 uppercase tracking-widest">Admin Panel</p>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-4">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-base-content/40 px-3 mb-2">เมนูหลัก</p>
            <ul class="menu menu-md gap-0.5 p-0 w-full">
                <?php foreach ($nav_items as $item): ?>
                    <?php
                    $is_active = ($current_page === $item['page']);
                    $active_class = $is_active
                        ? 'bg-primary/10 text-primary font-semibold border-l-3 border-primary rounded-l-none'
                        : 'text-base-content/70 hover:bg-base-200/70 hover:text-base-content';
                    ?>
                    <li>
                        <a href="?page=<?php echo $item['page']; ?>"
                            class="<?php echo $active_class; ?> rounded-lg transition-all duration-200 px-3 py-2.5">
                            <i data-lucide="<?php echo $item['icon']; ?>" class="size-[18px]"></i>
                            <span><?php echo $item['label']; ?></span>
                            <?php if ($is_active): ?>
                                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($employee_role === 'admin'): ?>
                <div class="divider my-3 px-3 text-[10px] font-semibold uppercase tracking-wider text-base-content/40">
                    ตั้งค่า</div>
                <ul class="menu menu-md gap-0.5 p-0 w-full">
                    <li>
                        <a href="?page=settings"
                            class="<?php echo ($current_page === 'settings') ? 'bg-primary/10 text-primary font-semibold border-l-3 border-primary rounded-l-none' : 'text-base-content/70 hover:bg-base-200/70 hover:text-base-content'; ?> rounded-lg transition-all duration-200 px-3 py-2.5">
                            <i data-lucide="settings" class="size-[18px]"></i>
                            <span>ตั้งค่าระบบ</span>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </nav>

        <!-- Employee Info Footer -->
        <div class="px-4 py-4 border-t border-base-200">
            <div class="flex items-center gap-3 p-2">
                <div class="avatar placeholder">
                    <div class="bg-primary/10 text-primary rounded-xl w-10 h-10 items-center justify-center flex">
                        <span class="text-sm font-bold"><?php echo mb_substr($employee_name, 0, 1); ?></span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-base-content truncate"><?php echo sanitize($employee_name); ?>
                    </p>
                    <span
                        class="badge badge-xs <?php echo $employee_role === 'admin' ? 'badge-primary' : 'badge-ghost'; ?> mt-0.5">
                        <?php echo $role_label; ?>
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    <a href="?page=profile"
                        class="btn btn-ghost btn-sm btn-square text-base-content/50 hover:text-primary tooltip tooltip-left"
                        data-tip="ข้อมูลส่วนตัว">
                        <i data-lucide="settings" class="size-4"></i>
                    </a>
                    <a href="?page=logout"
                        class="btn btn-ghost btn-sm btn-square text-base-content/50 hover:text-error tooltip tooltip-left"
                        data-tip="ออกจากระบบ">
                        <i data-lucide="log-out" class="size-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>
</div>