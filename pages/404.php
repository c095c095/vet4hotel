<?php
// ═══════════════════════════════════════════════════════════
// 404 ERROR PAGE — VET4 HOTEL
// Displayed when a customer page is not found
// ═══════════════════════════════════════════════════════════
?>

<div class="p-4 lg:p-8 flex items-center justify-center min-h-screen max-w-[1600px] mx-auto">
    <div class="text-center max-w-lg mx-auto">
        <!-- Icon / Illustration -->
        <div class="mb-8 flex justify-center">
            <div class="relative group">
                <div class="absolute inset-0 bg-primary/20 blur-2xl rounded-full animate-pulse"></div>
                <div
                    class="w-32 h-32 bg-base-100 rounded-4xl shadow-xl flex items-center justify-center relative border border-base-200 rotate-12 group-hover:rotate-0 transition-transform duration-500 ease-out">
                    <i data-lucide="file-question" class="size-16 text-primary"></i>
                </div>
            </div>
        </div>

        <!-- Text Content -->
        <h1 class="text-7xl lg:text-8xl font-black text-base-content mb-2 tracking-tighter">
            4<span class="text-primary">0</span>4
        </h1>
        <h2 class="text-2xl font-bold text-base-content mb-4 tracking-tight">
            ไม่พบหน้าที่คุณต้องการ (Page Not Found)
        </h2>
        <p class="text-base-content/60 mb-8 leading-relaxed">
            หน้าที่คุณกำลังพยายามเข้าถึงอาจถูกลบ ย้าย หรือไม่มีอยู่จริงกรุณาตรวจสอบ URL อีกครั้ง หรือกลับไปยังหน้าหลัก
        </p>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="?page=home" class="btn btn-primary shadow-lg shadow-primary/30 w-full sm:w-auto gap-2">
                <i data-lucide="home" class="size-4"></i>
                กลับสู่หน้าหลัก
            </a>
            <button onclick="window.history.back()" class="btn btn-outline bg-base-100 w-full sm:w-auto gap-2">
                <i data-lucide="arrow-left" class="size-4"></i>
                ย้อนกลับ
            </button>
        </div>
    </div>
</div>