<?php
// ═══════════════════════════════════════════════════════════
// FEATURES PAGE — VET4 HOTEL
// Best Practice: UI Only, Grouped, Actionable, Brand Consistent
// ═══════════════════════════════════════════════════════════
?>


<section class="py-16 md:py-24 bg-base-100">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Intro & Unique Selling Points -->
        <div class="text-center mb-12 md:mb-16 relative min-h-[160px]">
            <h1 class="text-3xl md:text-4xl font-bold text-base-content mb-4">
                โรงแรมสัตว์เลี้ยงที่ดูแลครบทุกด้าน <span class="text-primary">เพื่อความสุขของน้อง ๆ และเจ้าของ</span>
            </h1>
            <p class="text-base-content/60 max-w-2xl mx-auto text-base">
                เราให้บริการด้วยทีมสัตวแพทย์มืออาชีพ พร้อมสิ่งอำนวยความสะดวกและบริการเสริมที่ตอบโจทย์ทุกความต้องการ<br>
                <span class="font-semibold text-primary">ปลอดภัย อุ่นใจ ดูแลใกล้ชิด</span>
            </p>

            <!-- Decorative paw prints -->
            <div class="absolute left-0 top-0 md:left-[-60px] md:-top-10 pointer-events-none z-10 floating-paw">
                <i data-lucide="paw-print" class="size-10 text-primary rotate-12"></i>
            </div>
            <div class="absolute right-0 top-0 md:right-[-60px] md:-top-10 pointer-events-none z-10 floating-paw"
                style="animation-delay: 1s;">
                <i data-lucide="paw-print" class="size-10 text-primary -rotate-12"></i>
            </div>
            <!-- Extra paw prints -->
            <div class="absolute left-10 bottom-0 md:left-24 md:-bottom-5 pointer-events-none z-10 floating-paw"
                style="animation-delay: 1.5s;">
                <i data-lucide="paw-print" class="size-8 text-primary rotate-6"></i>
            </div>
            <div class="absolute right-10 bottom-0 md:right-24 md:-bottom-5 pointer-events-none z-10 floating-paw"
                style="animation-delay: 0.3s;">
                <i data-lucide="paw-print" class="size-8 text-primary -rotate-6"></i>
            </div>
            <!-- Pastel blob shapes -->
            <div
                class="absolute -left-10 top-24 md:-left-20 md:top-32 w-32 h-16 bg-primary rounded-full blur-[2px] opacity-60 z-0">
            </div>
            <div
                class="absolute -right-10 top-32 md:-right-20 md:top-40 w-32 h-16 bg-primary rounded-full blur-[2px] opacity-60 z-0">
            </div>
            <div
                class="absolute left-1/2 top-[-30px] -translate-x-1/2 w-24 h-8 bg-primary rounded-full blur-[2px] opacity-70 z-0">
            </div>
        </div>

        <!-- Grouped Features: Core Services & Owner Benefits -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <!-- Core Service Cards -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body items-center text-center">
                    <div class="avatar mb-4">
                        <div class="bg-primary text-primary-content rounded-full w-14 flex items-center justify-center">
                            <i data-lucide="stethoscope" class="size-7" aria-label="สัตวแพทย์"></i>
                        </div>
                    </div>
                    <h3 class="card-title text-base-content">ดูแลโดยสัตวแพทย์ 24 ชั่วโมง</h3>
                    <p class="text-base-content/70 text-sm">ทีมสัตวแพทย์และเจ้าหน้าที่พร้อมดูแลน้อง ๆ ตลอดเวลา</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body items-center text-center">
                    <div class="avatar mb-4">
                        <div class="bg-primary text-primary-content rounded-full w-14 flex items-center justify-center">
                            <i data-lucide="video" class="size-7" aria-label="CCTV"></i>
                        </div>
                    </div>
                    <h3 class="card-title text-base-content">กล้องวงจรปิดส่วนตัว</h3>
                    <p class="text-base-content/70 text-sm">ดูน้องผ่านกล้อง CCTV ได้แบบ Real-time ตลอดการเข้าพัก</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body items-center text-center">
                    <div class="avatar mb-4">
                        <div class="bg-primary text-primary-content rounded-full w-14 flex items-center justify-center">
                            <i data-lucide="image" class="size-7" aria-label="Daily Updates"></i>
                        </div>
                    </div>
                    <h3 class="card-title text-base-content">อัปเดตรูปและข้อความทุกวัน</h3>
                    <p class="text-base-content/70 text-sm">ส่งภาพและข้อความอัปเดตให้เจ้าของทุกวันผ่านระบบสมุดพกดิจิทัล
                    </p>
                </div>
            </div>
            <!-- Owner Benefit Cards -->
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body items-center text-center">
                    <div class="avatar mb-4">
                        <div class="bg-primary text-primary-content rounded-full w-14 flex items-center justify-center">
                            <i data-lucide="utensils" class="size-7" aria-label="อาหารเฉพาะ"></i>
                        </div>
                    </div>
                    <h3 class="card-title text-base-content">อาหารเฉพาะสำหรับแต่ละน้อง</h3>
                    <p class="text-base-content/70 text-sm">เลือกเมนูอาหารตามสุขภาพและความต้องการของแต่ละตัว</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body items-center text-center">
                    <div class="avatar mb-4">
                        <div class="bg-primary text-primary-content rounded-full w-14 flex items-center justify-center">
                            <i data-lucide="bed" class="size-7" aria-label="ห้องพัก"></i>
                        </div>
                    </div>
                    <h3 class="card-title text-base-content">ห้องพักปรับอุณหภูมิ</h3>
                    <p class="text-base-content/70 text-sm">ห้องพักสะอาด ปลอดภัย
                        พร้อมระบบปรับอุณหภูมิและสิ่งอำนวยความสะดวกครบ</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl border border-base-200">
                <div class="card-body items-center text-center">
                    <div class="avatar mb-4">
                        <div class="bg-primary text-primary-content rounded-full w-14 flex items-center justify-center">
                            <i data-lucide="check-square" class="size-7" aria-label="เช็คลิสต์"></i>
                        </div>
                    </div>
                    <h3 class="card-title text-base-content">เช็คลิสต์การดูแลรายวัน</h3>
                    <p class="text-base-content/70 text-sm">ระบบ To-do list สำหรับพนักงาน ป้อนยา อาหาร และกิจกรรมต่าง ๆ
                    </p>
                </div>
            </div>
        </div>

        <!-- Booking Steps & CTA -->
        <div class="mt-16 flex flex-col items-center justify-center">
            <div class="text-center mb-8">
                <h3 class="text-xl font-bold text-primary mb-2">ขั้นตอนการใช้บริการ</h3>
                <p class="text-base-content/60 text-sm">ง่าย สะดวก ปลอดภัยทุกขั้นตอน</p>
            </div>
            <ul class="steps steps-vertical md:steps-horizontal max-w-3xl mx-auto mb-8">
                <li class="step step-primary">เลือกห้องพักและบริการเสริม</li>
                <li class="step step-primary">กรอกข้อมูลสัตว์เลี้ยงและเจ้าของ</li>
                <li class="step step-primary">ชำระเงินและยืนยันการจอง</li>
                <li class="step step-primary">เข้าพักและรับอัปเดตทุกวัน</li>
            </ul>
        </div>
    </div>
</section>

<section class="py-16 bg-primary/5 border-t border-primary/10 text-center">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-base-content mb-4">พร้อมพาน้อง ๆ
            มาเป็นครอบครัวเดียวกับเราหรือยัง?</h2>
        <p class="text-base-content/60 mb-8 max-w-xl mx-auto">จองห้องพักออนไลน์ได้ง่าย ๆ ผ่านระบบจองของเรา
            หรือดูรายละเอียดห้องพักทั้งหมดก่อนตัดสินใจ</p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="?page=booking" class="btn btn-primary rounded-xl gap-2 shadow-lg hover:shadow-primary/30">
                <i data-lucide="calendar-check" class="size-5"></i>
                จองห้องพักเลย
            </a>
            <a href="?page=rooms"
                class="btn btn-outline border-base-300 hover:border-base-content/20 bg-base-100 rounded-xl gap-2">
                <i data-lucide="layout-grid" class="size-5"></i>
                ดูห้องพักทั้งหมด
            </a>
        </div>
    </div>
</section>