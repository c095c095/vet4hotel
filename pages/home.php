<?php
// ═══════════════════════════════════════════════════════════
// HOME PAGE — VET4 HOTEL
// Emotional, trust-building landing page for pet parents
// ═══════════════════════════════════════════════════════════

// --- Fetch room types from DB ---
$room_types = [];
try {
    $stmt = $pdo->query("
        SELECT rt.*, 
               (SELECT rti.image_url FROM room_type_images rti 
                WHERE rti.room_type_id = rt.id AND rti.is_primary = 1 LIMIT 1) as primary_image
        FROM room_types rt 
        WHERE rt.is_active = 1 
        ORDER BY rt.base_price_per_night ASC
        LIMIT 6
    ");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently fail — page works without DB data
}

// --- Fetch published reviews from DB ---
$testimonials = [];
try {
    $stmt = $pdo->query("
        SELECT r.rating, r.comment, r.staff_reply, r.staff_reply_at,
               c.first_name, c.last_name
        FROM reviews r
        JOIN customers c ON r.customer_id = c.id
        WHERE r.is_published = 1
        ORDER BY r.created_at DESC
        LIMIT 6
    ");
    $db_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($db_reviews as $rv) {
        $testimonials[] = [
            'name' => 'คุณ' . $rv['first_name'],
            'pet' => '',
            'avatar' => mb_substr($rv['first_name'], 0, 1),
            'text' => $rv['comment'],
            'rating' => (int) $rv['rating'],
            'staff_reply' => $rv['staff_reply'],
            'staff_reply_at' => $rv['staff_reply_at'],
        ];
    }
} catch (PDOException $e) {
    // Silently fail
}

// Fallback to static data if no published reviews
if (empty($testimonials)) {
    $testimonials = [
        [
            'name' => 'คุณนุ่น',
            'pet' => 'น้องมิลค์ (โกลเด้น)',
            'avatar' => 'น',
            'text' => 'ฝากน้องมิลค์ครั้งแรกก็กังวลมาก แต่ได้รับอัปเดตรูปทุกวัน เห็นน้องวิ่งเล่นมีความสุข ใจชื้นขึ้นเลยค่ะ',
            'rating' => 5,
            'staff_reply' => null,
            'staff_reply_at' => null,
        ],
        [
            'name' => 'คุณเบนซ์',
            'pet' => 'น้องชีส (แมวสก็อตติช)',
            'avatar' => 'เ',
            'text' => 'น้อนขี้กลัวคนแปลกหน้ามากครับ แต่แปลกที่พี่ๆ เลี้ยงที่นี่เค้ามีวิธียังไงไม่รู้ แมวผมยอมให้ลูบหัวเฉยเลย กลับบ้านมาลูกก็ไม่ซึม ประทับใจจริงๆ ครับ',
            'rating' => 5,
            'staff_reply' => null,
            'staff_reply_at' => null,
        ],
        [
            'name' => 'คุณแม็ค',
            'pet' => 'น้องโมจิ (ปอม)',
            'avatar' => 'แ',
            'text' => 'ที่ชอบที่สุดคือมีหมออยู่ตลอดนี่แหละครับ ลูกผมท้องไส้ไม่ค่อยดี พอมาฝากที่นี่แล้วอุ่นใจมาก มีคนดูอาการให้ตลอด 24 ชม.',
            'rating' => 5,
            'staff_reply' => null,
            'staff_reply_at' => null,
        ],
    ];
}

// --- A Day in the Life data ---
$daily_timeline = [
    ['time' => '07:00', 'title' => 'เช้าสดใส', 'desc' => 'เดินเล่นเช้า สูดอากาศ แล้วทานอาหารเช้าตามเมนูเฉพาะของน้อง', 'icon' => 'sunrise'],
    ['time' => '09:30', 'title' => 'เวลาตรวจสุขภาพ', 'desc' => 'พี่หมอเช็คสุขภาพและป้อนยาสำหรับน้องที่ต้องกินยา', 'icon' => 'stethoscope'],
    ['time' => '11:00', 'title' => 'ปล่อยพลัง', 'desc' => 'กิจกรรมเล่นของเล่น ฝึกทักษะ หรือว่ายน้ำตามความเหมาะสม', 'icon' => 'smile'],
    ['time' => '13:00', 'title' => 'พักผ่อนยามบ่าย', 'desc' => 'นอนพักบนเตียงออร์โธปิดิกส์ในห้องปรับอุณหภูมิ อากาศเย็นสบาย', 'icon' => 'moon'],
    ['time' => '16:00', 'title' => 'ขนมและของว่าง', 'desc' => 'เวลาทานขนม พร้อมเล่นเบาๆ ก่อนอาหารเย็น', 'icon' => 'cookie'],
    ['time' => '18:00', 'title' => 'อัปเดตให้คุณพ่อคุณแม่', 'desc' => 'ส่งรูป วิดีโอ และรายงานสุขภาพวันนี้ให้คุณ ทุกเย็น', 'icon' => 'send'],
];
?>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION 1: EMOTIONAL HERO                                         -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative min-h-[92vh] flex items-center overflow-hidden">
    <!-- Hero background image -->
    <div class="absolute inset-0" aria-hidden="true">
        <img src="assets/images/487456352_9682552058431752_5798845638060029487_n.jpg" alt=""
            class="hero-kenburns w-full h-full object-cover">
        <div class="absolute inset-0 bg-linear-to-r from-black/65 via-black/45 to-black/55"></div>
    </div>

    <!-- Decorative floating elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="floating-paw absolute top-[10%] left-[8%] opacity-30 text-white">
            <i data-lucide="paw-print" class="size-16"></i>
        </div>
        <div class="floating-paw absolute top-[25%] right-[12%] opacity-24 text-white" style="animation-delay: 1.5s;">
            <i data-lucide="heart" class="size-12"></i>
        </div>
        <div class="floating-paw absolute bottom-[20%] left-[15%] opacity-18 text-white" style="animation-delay: 3s;">
            <i data-lucide="paw-print" class="size-10"></i>
        </div>
        <div class="floating-paw absolute top-[60%] right-[6%] opacity-30 text-white" style="animation-delay: 4.5s;">
            <i data-lucide="paw-print" class="size-20"></i>
        </div>
        <div class="floating-paw absolute bottom-[8%] right-[30%] opacity-15 text-white" style="animation-delay: 2s;">
            <i data-lucide="heart" class="size-14"></i>
        </div>
    </div>

    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <div class="max-w-3xl">
            <!-- Tagline pill -->
            <div
                class="animate-fade-in-up inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-5 py-2 mb-6 border border-white/20">
                <i data-lucide="sparkles" class="size-4 text-amber-300"></i>
                <span class="text-white/90 text-sm font-medium">โรงแรมสัตว์เลี้ยงแสนอบอุ่น ดูแลโดยทีมสัตวแพทย์</span>
            </div>

            <!-- Main headline -->
            <h1 class="animate-fade-in-up text-3xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mb-6"
                style="animation-delay: 0.15s;">
                เพราะเขาคือ<span class="text-amber-300">ครอบครัว</span><br>
                ให้เราเป็น<span class="text-amber-300">บ้านหลังที่สอง</span>
            </h1>

            <!-- Sub-copy -->
            <p class="animate-fade-in-up text-lg md:text-xl text-white/80 leading-relaxed mb-8 max-w-xl"
                style="animation-delay: 0.3s;">
                เราเข้าใจความกังวลของคุณพ่อคุณแม่ทุกคน ด้วยประสบการณ์กว่า 39 ปี ที่นี่เลยไม่ใช่แค่ที่ฝากเลี้ยง
                แต่เป็นเหมือนบ้านหลังที่สอง ที่น้องๆ ได้รับทั้งความรัก การดูแล และความใส่ใจตลอด 24 ชม.
                ให้คุณอุ่นใจแม้ไม่ได้อยู่ด้วยกัน
            </p>

            <!-- CTA buttons -->
            <div class="animate-fade-in-up flex flex-wrap gap-4 mb-12" style="animation-delay: 0.45s;">
                <a href="?page=booking"
                    class="cta-pulse btn btn-lg bg-white text-purple-700 hover:bg-amber-50 border-none shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 rounded-xl gap-2">
                    <i data-lucide="calendar-check" class="size-5"></i>
                    จองห้องพักเลย
                </a>
                <a href="?page=rooms"
                    class="btn btn-lg btn-outline border-white/40 text-white hover:bg-white/10 hover:border-white rounded-xl gap-2">
                    <i data-lucide="eye" class="size-5"></i>
                    ดูห้องพักทั้งหมด
                </a>
            </div>

            <!-- Trust badges -->
            <div class="animate-fade-in-up" style="animation-delay: 0.6s;">
                <div class="hidden lg:flex flex-wrap gap-3 md:gap-5">
                    <div
                        class="trust-badge flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/15 transition-all duration-300 hover:bg-white/20 hover:scale-105 cursor-default">
                        <span class="flex items-center justify-center w-8 h-8 bg-emerald-400/20 rounded-lg">
                            <i data-lucide="stethoscope" class="size-4 text-emerald-300"></i>
                        </span>
                        <span class="text-white/90 text-sm font-medium">สัตวแพทย์ดูแล 24 ชม.</span>
                    </div>
                    <div
                        class="trust-badge flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/15 transition-all duration-300 hover:bg-white/20 hover:scale-105 cursor-default">
                        <span class="flex items-center justify-center w-8 h-8 bg-blue-400/20 rounded-lg">
                            <i data-lucide="cctv" class="size-4 text-blue-300"></i>
                        </span>
                        <span class="text-white/90 text-sm font-medium">กล้อง CCTV ส่วนตัว</span>
                    </div>
                    <div
                        class="trust-badge flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/15 transition-all duration-300 hover:bg-white/20 hover:scale-105 cursor-default">
                        <span class="flex items-center justify-center w-8 h-8 bg-amber-400/20 rounded-lg">
                            <i data-lucide="award" class="size-4 text-amber-300"></i>
                        </span>
                        <span class="text-white/90 text-sm font-medium">ผู้ดูแลผ่านการรับรอง</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom wave -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
            <path d="M0,64 C360,120 720,0 1080,64 C1260,96 1380,80 1440,64 L1440,120 L0,120 Z" class="fill-base-100" />
        </svg>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION 2: OUR PHILOSOPHY / FOUNDER'S NOTE                        -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="py-16 md:py-24 bg-base-100 reveal-on-scroll">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Left: Story -->
            <div class="order-2 lg:order-1">
                <div
                    class="inline-flex items-center gap-2 bg-primary/10 text-primary rounded-full px-4 py-1.5 text-sm font-semibold mb-6">
                    <i data-lucide="heart-handshake" class="size-4"></i>
                    เรื่องราวความผูกพันกว่า 39 ปี
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-base-content mb-6 leading-snug">
                    ไม่ใช่แค่ <span class="text-primary">"คลินิกหรือที่ฝากเลี้ยง"</span><br class="hidden md:block">
                    แต่คือพื้นที่ปลอดภัยที่สร้างด้วยหัวใจ
                </h2>
                <div class="space-y-4 text-base-content/70 text-base leading-relaxed">
                    <p>
                        โรงพยาบาลสัตว์ สัตวแพทย์ 4 (VET4) เกิดขึ้นตั้งแต่ปี พ.ศ. 2529
                        จากความตั้งใจของคุณหมอและทีมงานที่รักสัตว์สุดหัวใจ เพราะเราเข้าใจดีว่าน้องๆ ไม่ใช่แค่สัตว์เลี้ยง
                        แต่คือ "สมาชิกคนสำคัญในครอบครัว" ของคุณ
                    </p>
                    <p>
                        ตลอด 39 ปีที่ผ่านมา เราจึงไม่เคยหยุดพัฒนานวัตกรรมการรักษาตามมาตรฐานสากล (TASHA)
                        ควบคู่ไปกับการดูแลด้วยความรัก ไม่ว่าจะเป็นการฝากพักผ่อนในโรงแรมที่แสนสบาย หรือการดูแลยามเจ็บป่วย
                        ทุกรายละเอียดถูกออกแบบมาเพื่อให้คุณพ่อคุณแม่คลายความกังวล เพราะที่นี่...
                        มีทีมสัตวแพทย์ผู้เชี่ยวชาญคอยดูแลลูกๆ ของคุณอย่างใกล้ชิดตลอด 24 ชั่วโมง
                    </p>
                </div>

                <!-- Founder quote -->
                <div class="mt-8 bg-base-200/50 rounded-2xl p-6 border-l-4 border-primary">
                    <p class="italic text-base-content/80 text-base mb-3">
                        "ตลอด 39 ปี เราตั้งใจดูแลสัตว์เลี้ยงให้ดีที่สุด ใช้ความเชี่ยวชาญที่มี ดูแลลูกๆ
                        ของคุณเหมือนลูกของเราเอง"
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="avatar placeholder">
                            <div
                                class="bg-primary text-primary-content rounded-full w-10 flex items-center justify-center">
                                <i data-lucide="user" class="size-5"></i>
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-base-content">แก๊งพ่อๆ แม่ๆ ทีมสัตวแพทย์</p>
                            <p class="text-xs text-base-content/50">ผู้ก่อตั้ง VET4 Hotel</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Photo placeholder & features -->
            <div class="order-1 lg:order-2">
                <div class="relative">
                    <!-- Main photo -->
                    <div class="rounded-3xl aspect-4/3 overflow-hidden">
                        <img src="assets/images/รวมฝากเลี้ยง.jpg" alt="ทีมงานและน้องๆ ที่ VET4 Hotel"
                            class="w-full h-full object-cover">
                    </div>

                    <!-- Floating stat card -->
                    <div
                        class="absolute -bottom-6 -left-4 md:-left-8 bg-base-100 rounded-2xl shadow-xl p-4 border border-base-200 animate-fade-in-up">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                                <i data-lucide="shield-check" class="size-6 text-emerald-600 dark:text-emerald-400"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-base-content">39 ปี</p>
                                <p class="text-xs text-base-content/50">ประสบการณ์ดูแลสัตว์</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating heart card -->
                    <div class="absolute -top-4 -right-4 md:-right-8 bg-base-100 rounded-2xl shadow-xl p-4 border border-base-200 animate-fade-in-up"
                        style="animation-delay: 0.3s;">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center">
                                <i data-lucide="heart" class="size-6 text-rose-500 dark:text-rose-400"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-base-content">24 ชม.</p>
                                <p class="text-xs text-base-content/50">มีสัตวแพทย์ดูแลใกล้ชิด</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION 3: A DAY IN THE LIFE — TIMELINE                           -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="py-16 md:py-24 bg-base-200/40 reveal-on-scroll">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-12 md:mb-16">
            <div
                class="inline-flex items-center gap-2 bg-primary/10 text-primary rounded-full px-4 py-1.5 text-sm font-semibold mb-4">
                <i data-lucide="clock" class="size-4"></i>
                กิจวัตรประจำวัน
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-base-content mb-4">
                แอบดู 1 วันของเด็กๆ ที่ <span class="text-primary">VET4</span>
            </h2>
            <p class="text-base-content/60 max-w-2xl mx-auto text-base">
                พ่อๆ แม่ๆ ชอบถามว่าน้องมาอยู่แล้วจะเหงาไหม... บอกเลยว่าคิวแน่นมากค่ะ หลับปุ๋ยแน่นอน
            </p>
        </div>

        <!-- Timeline -->
        <div class="max-w-4xl mx-auto">
            <ul class="timeline timeline-snap-icon timeline-vertical">
                <?php foreach ($daily_timeline as $i => $item): ?>
                    <?php $is_left = $i % 2 === 0; ?>
                    <li>
                        <?php if ($i > 0): ?>
                            <hr class="bg-primary/30" />
                        <?php endif; ?>

                        <div class="timeline-middle">
                            <div
                                class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center shadow-lg shadow-primary/20 transition-transform duration-300 hover:scale-110">
                                <i data-lucide="<?php echo $item['icon']; ?>" class="size-5 text-primary-content"></i>
                            </div>
                        </div>

                        <div class="<?php echo $is_left ? 'timeline-start md:text-end' : 'timeline-end'; ?> mb-10">
                            <div
                                class="bg-base-100 rounded-2xl p-5 shadow-sm border border-base-200/80 hover:shadow-md hover:border-primary/20 transition-all duration-300 group">
                                <time class="font-mono text-primary text-sm font-bold"><?php echo $item['time']; ?></time>
                                <div
                                    class="text-lg font-bold text-base-content mt-1 group-hover:text-primary transition-colors">
                                    <?php echo $item['title']; ?>
                                </div>
                                <p class="text-base-content/60 mt-1 text-sm leading-relaxed"><?php echo $item['desc']; ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($i < count($daily_timeline) - 1): ?>
                            <hr class="bg-primary/30" />
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION 4: ROOM SHOWCASE (DB-DRIVEN)                              -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="py-16 md:py-24 bg-base-100 reveal-on-scroll">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-12 md:mb-16">
            <div
                class="inline-flex items-center gap-2 bg-primary/10 text-primary rounded-full px-4 py-1.5 text-sm font-semibold mb-4">
                <i data-lucide="bed-double" class="size-4"></i>
                ห้องพักของเด็กๆ
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-base-content mb-4">
                ห้องพักสุดสบาย ดีไซน์เพื่อ<span class="text-primary">น้องๆ</span>โดยเฉพาะ
            </h2>
            <p class="text-base-content/60 max-w-2xl mx-auto text-base">
                ทุกห้องถูกออกแบบมาให้ปลอดภัย สะอาด และน่าอยู่ พร้อมสิ่งอำนวยความสะดวก
            </p>
        </div>

        <?php if (!empty($room_types)): ?>
            <!-- Room cards grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <?php foreach ($room_types as $room): ?>
                    <div
                        class="group bg-base-100 rounded-2xl border border-base-200 overflow-hidden hover:shadow-xl hover:border-primary/20 transition-all duration-500 hover:-translate-y-1">
                        <!-- Room image / placeholder -->
                        <div class="relative h-52 overflow-hidden">
                            <?php if (!empty($room['primary_image'])): ?>
                                <img src="<?php echo sanitize($room['primary_image']); ?>"
                                    alt="<?php echo sanitize($room['name']); ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <?php else: ?>
                                <div
                                    class="w-full h-full bg-linear-to-br from-primary/20 via-primary/10 to-secondary/10 flex items-center justify-center">
                                    <i data-lucide="bed-double" class="size-16 text-primary/20"></i>
                                </div>
                            <?php endif; ?>
                            <!-- Price badge -->
                            <div
                                class="absolute top-4 right-4 bg-base-100/90 backdrop-blur-sm rounded-xl px-3 py-1.5 shadow-lg">
                                <span
                                    class="text-primary font-bold text-lg">฿<?php echo number_format($room['base_price_per_night']); ?></span>
                                <span class="text-base-content/50 text-xs">/คืน</span>
                            </div>
                        </div>

                        <!-- Room info -->
                        <div class="p-5">
                            <h3 class="text-xl font-bold text-base-content group-hover:text-primary transition-colors mb-2">
                                <?php echo sanitize($room['name']); ?>
                            </h3>
                            <p class="text-base-content/60 text-sm leading-relaxed mb-4 line-clamp-2">
                                <?php echo sanitize($room['description'] ?? 'ห้องพักสะอาด ปลอดภัย พร้อมสิ่งอำนวยความสะดวกครบครัน'); ?>
                            </p>

                            <!-- Room features -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php if (!empty($room['size_sqm'])): ?>
                                    <span
                                        class="inline-flex items-center gap-1 bg-base-200/60 rounded-lg px-2.5 py-1 text-xs text-base-content/70">
                                        <i data-lucide="ruler" class="size-3"></i>
                                        <?php echo $room['size_sqm']; ?> ตร.ม.
                                    </span>
                                <?php endif; ?>
                                <span
                                    class="inline-flex items-center gap-1 bg-base-200/60 rounded-lg px-2.5 py-1 text-xs text-base-content/70">
                                    <i data-lucide="thermometer" class="size-3"></i>
                                    ปรับอุณหภูมิ
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 bg-base-200/60 rounded-lg px-2.5 py-1 text-xs text-base-content/70">
                                    <i data-lucide="paw-print" class="size-3"></i>
                                    สูงสุด <?php echo $room['max_pets']; ?> ตัว
                                </span>
                            </div>

                            <!-- CTA -->
                            <a href="?page=room_details&id=<?php echo $room['id']; ?>"
                                class="btn btn-primary btn-sm w-full rounded-xl gap-2 group-hover:shadow-lg group-hover:shadow-primary/20 transition-all">
                                <i data-lucide="eye" class="size-4"></i>
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- View all rooms -->
            <div class="text-center mt-10">
                <a href="?page=rooms"
                    class="btn btn-outline btn-primary rounded-xl gap-2 hover:scale-105 transition-transform">
                    <i data-lucide="layout-grid" class="size-5"></i>
                    ดูห้องพักทั้งหมด
                </a>
            </div>

        <?php else: ?>
            <!-- Empty state / fallback -->
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="bed-double" class="size-12 text-primary/40"></i>
                </div>
                <h3 class="text-xl font-bold text-base-content mb-2">กำลังเตรียมห้องพักให้น้องๆ</h3>
                <p class="text-base-content/50 max-w-md mx-auto mb-6">
                    เรากำลังจัดเตรียมห้องพักสุดพิเศษ เร็วๆ นี้ติดต่อเราเพื่อสอบถามรายละเอียดเพิ่มเติมได้เลยค่ะ
                </p>
                <a href="?page=contact" class="btn btn-primary rounded-xl gap-2">
                    <i data-lucide="mail" class="size-4"></i>
                    ติดต่อสอบถาม
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION 5: SOCIAL PROOF / TESTIMONIALS                            -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="py-16 md:py-24 bg-base-200/40 reveal-on-scroll">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-12 md:mb-16">
            <div
                class="inline-flex items-center gap-2 bg-primary/10 text-primary rounded-full px-4 py-1.5 text-sm font-semibold mb-4">
                <i data-lucide="message-circle-heart" class="size-4"></i>
                เสียงจากคุณพ่อคุณแม่
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-base-content mb-4">
                ความในใจจาก<span class="text-primary">ผู้ปกครอง</span>ที่เคยมาฝาก
            </h2>
            <p class="text-base-content/60 max-w-2xl mx-auto text-base">
                อ่านแล้วทีมงานยิ้มแก้มปริมีแรงทำงานต่อเลยค่ะ ขอบคุณที่ไว้วางใจให้เราดูแลเด็กๆ นะคะ 😊
            </p>
        </div>

        <!-- Testimonial cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-5xl mx-auto">
            <?php foreach ($testimonials as $i => $review): ?>
                <div
                    class="bg-base-100 rounded-2xl p-6 shadow-sm border border-base-200/80 hover:shadow-lg hover:border-primary/20 transition-all duration-300 flex flex-col">
                    <!-- Stars -->
                    <div class="flex gap-1 mb-4">
                        <?php for ($s = 0; $s < $review['rating']; $s++): ?>
                            <i data-lucide="star" class="size-4 fill-amber-400 text-amber-400"></i>
                        <?php endfor; ?>
                    </div>

                    <!-- Review text -->
                    <p class="text-base-content/70 text-sm leading-relaxed flex-1 mb-5">
                        "<?php echo htmlspecialchars($review['text']); ?>"
                    </p>

                    <!-- Staff reply (if exists) -->
                    <?php if (!empty($review['staff_reply'])): ?>
                        <div class="bg-primary/5 border border-primary/10 rounded-xl px-3 py-2.5 mb-4 -mt-2">
                            <div class="flex items-center gap-1.5 mb-1">
                                <i data-lucide="reply" class="size-3 text-primary"></i>
                                <span class="text-[11px] font-semibold text-primary">ตอบกลับจาก VET4</span>
                            </div>
                            <p class="text-xs text-base-content/60 leading-relaxed">
                                <?php echo htmlspecialchars($review['staff_reply']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Reviewer -->
                    <div class="flex items-center gap-3 pt-4 border-t border-base-200">
                        <div class="avatar placeholder">
                            <div class="bg-primary/10 text-primary rounded-full w-10 h-10 flex items-center justify-center">
                                <span class="text-sm font-bold"><?php echo $review['avatar']; ?></span>
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-base-content"><?php echo $review['name']; ?></p>
                            <p class="text-xs text-base-content/50"><?php echo $review['pet']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION 6: STATS COUNTER                                           -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="py-12 md:py-16 bg-base-100 border-y border-base-200/60 reveal-on-scroll">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 lg:gap-8">
            <div class="text-center group">
                <div
                    class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-primary/20 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="paw-print" class="size-7 text-primary"></i>
                </div>
                <p class="text-3xl md:text-4xl font-bold text-base-content">2,000+</p>
                <p class="text-sm text-base-content/50 mt-1">น้องๆ ที่โดนเราตกไปแล้ว</p>
            </div>
            <div class="text-center group">
                <div
                    class="w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-emerald-500/20 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="stethoscope" class="size-7 text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <p class="text-3xl md:text-4xl font-bold text-base-content">24/7</p>
                <p class="text-sm text-base-content/50 mt-1">สัตวแพทย์ประจำ</p>
            </div>
            <div class="text-center group">
                <div
                    class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-amber-500/20 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="smile" class="size-7 text-amber-600 dark:text-amber-400"></i>
                </div>
                <p class="text-3xl md:text-4xl font-bold text-base-content">99%</p>
                <p class="text-sm text-base-content/50 mt-1">ความพึงพอใจ</p>
            </div>
            <div class="text-center group">
                <div
                    class="w-14 h-14 bg-rose-500/10 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:bg-rose-500/20 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="heart" class="size-7 text-rose-500 dark:text-rose-400"></i>
                </div>
                <p class="text-3xl md:text-4xl font-bold text-base-content">39 ปี</p>
                <p class="text-sm text-base-content/50 mt-1">ประสบการณ์</p>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION 7: FINAL REASSURANCE CTA                                  -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative overflow-hidden reveal-on-scroll">
    <div class="cta-gradient py-16 md:py-24">
        <!-- Decorative elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="floating-paw absolute top-[15%] right-[10%] opacity-10 text-white" style="animation-delay: 2s;">
                <i data-lucide="paw-print" class="size-16"></i>
            </div>
            <div class="floating-paw absolute bottom-[20%] left-[8%] opacity-8 text-white" style="animation-delay: 4s;">
                <i data-lucide="heart" class="size-12"></i>
            </div>
        </div>

        <div class="container mx-auto px-4 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6 leading-snug">
                    มาเยี่ยมชมก่อนจองได้เลยค่ะ<br>
                    <span class="text-amber-300">เราพร้อมพาชมทุกวัน</span>
                </h2>

                <p class="text-white/70 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
                    เราเชื่อว่าการมาดูด้วยตาตัวเองจะทำให้คุณมั่นใจ
                    มาเจอทีมงาน ดูห้องพัก และสัมผัสบรรยากาศจริงๆ ได้ทุกวันค่ะ
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="?page=contact"
                        class="btn btn-lg bg-white text-purple-700 hover:bg-amber-50 border-none shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 rounded-xl gap-2">
                        <i data-lucide="map-pin" class="size-5"></i>
                        ดูแผนที่ / นัดเยี่ยมชม
                    </a>
                    <a href="tel:029538085"
                        class="btn btn-lg btn-outline border-white/40 text-white hover:bg-white/10 hover:border-white rounded-xl gap-2">
                        <i data-lucide="phone" class="size-5"></i>
                        โทร 02-953-8085
                    </a>
                </div>

                <!-- Contact info row -->
                <div class="flex flex-wrap justify-center gap-6 mt-10 text-white/50 text-sm">
                    <span class="flex items-center gap-2">
                        <i data-lucide="clock" class="size-4"></i>
                        เปิดให้เยี่ยมชม: ทุกวัน 9:00–18:00
                    </span>
                    <span class="flex items-center gap-2">
                        <i data-lucide="message-circle" class="size-4"></i>
                        Line: @vet4
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (() => {
        if (window.__vet4HomeFxInit) {
            return;
        }
        window.__vet4HomeFxInit = true;

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const revealTargets = document.querySelectorAll('.reveal-on-scroll');

        if (!prefersReducedMotion && 'IntersectionObserver' in window) {
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, {
                threshold: 0.18,
                rootMargin: '0px 0px -10% 0px'
            });

            revealTargets.forEach((section) => revealObserver.observe(section));
        } else {
            revealTargets.forEach((section) => section.classList.add('is-visible'));
        }

        const heroImage = document.querySelector('.hero-kenburns');
        if (!prefersReducedMotion && heroImage) {
            let rafId = null;
            const onScroll = () => {
                if (rafId) {
                    return;
                }
                rafId = window.requestAnimationFrame(() => {
                    const scrolled = Math.min(window.scrollY, 320);
                    heroImage.style.objectPosition = `center ${50 + scrolled * 0.03}%`;
                    rafId = null;
                });
            };

            window.addEventListener('scroll', onScroll, {
                passive: true
            });
            onScroll();
        }
    })();
</script>