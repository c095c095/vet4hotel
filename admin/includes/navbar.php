<?php
// ═══════════════════════════════════════════════════════════
// ADMIN NAVBAR (MOBILE TOPTBAR)
// ═══════════════════════════════════════════════════════════
$employee_name = $_SESSION['user_name'] ?? 'พนักงาน';
?>
<!-- Mobile Navbar -->
<div class="navbar bg-base-100 border-b border-base-200 lg:hidden sticky top-0 z-40">
    <div class="flex-none">
        <label for="admin-drawer" class="btn btn-square btn-ghost drawer-button">
            <i data-lucide="menu" class="size-5"></i>
        </label>
    </div>
    <div class="flex-1">
        <a class="flex items-center gap-2 font-bold text-lg text-primary" href="?page=home">
            <i data-lucide="paw-print" class="size-5"></i>
            <span class="font-[Outfit]">
                <?php echo SITE_NAME; ?>
            </span>
        </a>
    </div>
    <div class="flex-none">
        <div class="dropdown dropdown-end">
            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
                <div class="bg-primary text-primary-content rounded-full w-10 flex items-center justify-center">
                    <span class="text-sm font-semibold">
                        <?php echo mb_substr($employee_name, 0, 1); ?>
                    </span>
                </div>
            </div>
            <ul tabindex="0"
                class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow-lg border border-base-200">
                <li class="menu-title px-2 pt-1 pb-2">
                    <span class="text-base-content/70 text-xs">
                        <?php echo sanitize($employee_name); ?>
                    </span>
                </li>
                <li><a href="?page=home"><i data-lucide="layout-dashboard" class="size-4"></i>แดชบอร์ด</a></li>
                <li class="my-1 border-t border-base-200"></li>
                <li><a href="?page=profile"><i data-lucide="user" class="size-4"></i>ข้อมูลส่วนตัว</a></li>
                <li><a href="?action=logout" class="text-error"><i data-lucide="log-out"
                            class="size-4"></i>ออกจากระบบ</a></li>
            </ul>
        </div>
    </div>
</div>