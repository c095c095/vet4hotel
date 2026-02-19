<footer class="bg-neutral text-neutral-content">
    <div class="container mx-auto px-4">
        <!-- Main footer content -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 py-12 md:py-16">
            <!-- Brand column -->
            <aside class="max-w-xs">
                <a href="?page=home" class="flex items-center gap-2 mb-4">
                    <img src="assets/favicon/logo.png" alt="<?php echo SITE_NAME; ?>" class="h-10 w-10 object-contain">
                    <span class="text-xl font-bold">
                        <span class="text-primary">VET4</span> Hotel
                    </span>
                </a>
                <p class="text-sm text-neutral-content/60 leading-relaxed">
                    โรงแรมแมว & รับฝากเลี้ยงสุนัข โดยโรงพยาบาลสัตว์ สัตวแพทย์ 4
                    มีเจ้าหน้าที่และคุณหมอคอยดูแลตลอด 24 ชม.
                </p>
                <!-- Social icons -->
                <div class="flex gap-3 mt-5">
                    <a href="https://line.me/R/ti/p/@vet4" target="_blank"
                        class="btn btn-ghost btn-circle btn-sm hover:bg-primary/20 hover:text-primary transition-colors">
                        <i data-lucide="message-circle" class="size-4"></i>
                    </a>
                    <a href="tel:029538085"
                        class="btn btn-ghost btn-circle btn-sm hover:bg-primary/20 hover:text-primary transition-colors">
                        <i data-lucide="phone" class="size-4"></i>
                    </a>
                </div>
            </aside>

            <!-- Quick links -->
            <nav class="flex flex-col gap-2">
                <h6 class="text-sm font-semibold uppercase tracking-wider opacity-60 mb-2">บริการ</h6>
                <a href="?page=rooms"
                    class="link link-hover text-sm opacity-80 hover:opacity-100 hover:text-primary transition-all">โรงแรมแมว</a>
                <a href="?page=rooms"
                    class="link link-hover text-sm opacity-80 hover:opacity-100 hover:text-primary transition-all">โรงแรมสุนัข</a>
                <a href="?page=booking"
                    class="link link-hover text-sm opacity-80 hover:opacity-100 hover:text-primary transition-all">จองห้องพัก</a>
            </nav>

            <!-- Support links -->
            <nav class="flex flex-col gap-2">
                <h6 class="text-sm font-semibold uppercase tracking-wider opacity-60 mb-2">ลิงก์</h6>
                <a href="?page=home"
                    class="link link-hover text-sm opacity-80 hover:opacity-100 hover:text-primary transition-all">หน้าแรก</a>
                <a href="?page=contact"
                    class="link link-hover text-sm opacity-80 hover:opacity-100 hover:text-primary transition-all">ติดต่อเรา</a>
                <a href="?page=login"
                    class="link link-hover text-sm opacity-80 hover:opacity-100 hover:text-primary transition-all">เข้าสู่ระบบ</a>
                <a href="?page=register"
                    class="link link-hover text-sm opacity-80 hover:opacity-100 hover:text-primary transition-all">สมัครสมาชิก</a>
            </nav>

            <!-- Contact info -->
            <nav class="flex flex-col gap-2">
                <h6 class="text-sm font-semibold uppercase tracking-wider opacity-60 mb-2">ติดต่อเรา</h6>
                <div class="flex items-center gap-2 text-sm opacity-80 mb-2">
                    <i data-lucide="phone" class="size-4 shrink-0"></i>
                    <span>02-953-8085</span>
                </div>
                <div class="flex items-center gap-2 text-sm opacity-80 mb-2">
                    <i data-lucide="message-circle" class="size-4 shrink-0"></i>
                    <span>Line: @vet4</span>
                </div>
                <div class="flex items-center gap-2 text-sm opacity-80">
                    <i data-lucide="clock" class="size-4 shrink-0"></i>
                    <span>ดูแล 24 ชั่วโมง</span>
                </div>
            </nav>
        </div>

        <!-- Copyright bar -->
        <div class="border-t border-neutral-content/10 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-3 text-xs opacity-50">
                <p>©
                    <?php echo date('Y'); ?>
                    <?php echo SITE_NAME; ?>. สงวนลิขสิทธิ์ทุกประการ.
                </p>
                <p>พัฒนาด้วย <i data-lucide="heart" class="size-3 inline text-red-400"></i> เพื่อสัตว์เลี้ยงที่คุณรัก
                </p>
            </div>
        </div>
    </div>
</footer>