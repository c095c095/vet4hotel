<?php
// ═══════════════════════════════════════════════════════════
// ROOMS PAGE — VET4 HOTEL
// แคตตาล็อกประเภทห้องพัก พร้อมสิ่งอำนวยความสะดวก
// ═══════════════════════════════════════════════════════════

$room_types = [];
try {
    // ดึงข้อมูลประเภทห้องพัก + รูปหน้าปก
    $stmt = $pdo->prepare("
        SELECT rt.*, 
            rti.image_url AS primary_image
        FROM room_types rt
        LEFT JOIN room_type_images rti 
            ON rti.room_type_id = rt.id AND rti.is_primary = 1
        WHERE rt.is_active = 1
        ORDER BY rt.base_price_per_night ASC
    ");
    $stmt->execute();
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงสิ่งอำนวยความสะดวกทั้งหมดของแต่ละห้อง (amenities)
    $amenities_map = [];
    if ($room_types) {
        $room_type_ids = array_column($room_types, 'id');
        $in_clause = implode(',', array_fill(0, count($room_type_ids), '?'));
        $stmt2 = $pdo->prepare("
            SELECT rta.room_type_id, a.name, a.icon_class
            FROM room_type_amenities rta
            INNER JOIN amenities a ON rta.amenity_id = a.id
            WHERE rta.room_type_id IN ($in_clause)
            ORDER BY a.name ASC
        ");
        $stmt2->execute($room_type_ids);
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $amenities_map[$row['room_type_id']][] = [
                'name' => $row['name'],
                'icon_class' => $row['icon_class']
            ];
        }
    }
} catch (PDOException $e) {
    $room_types = [];
}
?>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SECTION: ROOMS CATALOG                                            -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<section class="relative min-h-[80vh] flex items-center overflow-hidden bg-base-100">
    <!-- Decorative floating elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
        <div class="floating-paw absolute top-[10%] left-[8%] opacity-10 text-primary">
            <i data-lucide="paw-print" class="size-16"></i>
        </div>
        <div class="floating-paw absolute top-[25%] right-[12%] opacity-10 text-primary" style="animation-delay: .5s;">
            <i data-lucide="heart" class="size-12"></i>
        </div>
        <div class="floating-paw absolute bottom-[20%] left-[15%] opacity-10 text-primary" style="animation-delay: .3s;">
            <i data-lucide="paw-print" class="size-10"></i>
        </div>
        <div class="floating-paw absolute top-[60%] right-[6%] opacity-10 text-primary" style="animation-delay: .7s;">
            <i data-lucide="paw-print" class="size-20"></i>
        </div>
        <div class="floating-paw absolute bottom-[8%] right-[30%] opacity-10 text-primary" style="animation-delay: .2s;">
            <i data-lucide="heart" class="size-14"></i>
        </div>
    </div>

    <div class="container mx-auto px-4 lg:px-8 relative z-10 py-16 md:py-24 w-full">
        <!-- Header Section -->
        <div class="text-center mb-12 md:mb-16">
            <div class="inline-flex items-center gap-2 bg-primary/10 text-primary rounded-full px-4 py-1.5 text-sm font-semibold mb-4">
                <i data-lucide="bed-double" class="size-4"></i>
                ห้องพักของเด็กๆ
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-base-content mb-4">
                ประเภทห้องพักของเรา
            </h1>
            <p class="text-base-content/70 text-lg max-w-2xl mx-auto">
                เลือกห้องพักที่เหมาะกับสัตว์เลี้ยงของคุณ ทุกห้องปลอดภัย สะอาด พร้อมสิ่งอำนวยความสะดวกครบครัน
            </p>
        </div>

        <?php if (!empty($room_types)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($room_types as $room): ?>
                    <div class="card bg-base-100 shadow-xl border border-base-200 hover:shadow-2xl hover:border-primary/30 transition-all duration-300 group">
                        <?php if (!empty($room['primary_image'])): ?>
                            <figure class="h-56 bg-base-200 overflow-hidden relative">
                                <img src="<?php echo htmlspecialchars($room['primary_image']); ?>"
                                    alt="<?php echo htmlspecialchars($room['name']); ?>"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                <div class="absolute top-4 right-4 bg-base-100/90 backdrop-blur-sm rounded-xl px-3 py-1.5 shadow-lg">
                                    <span class="text-primary font-bold text-lg">฿<?php echo number_format($room['base_price_per_night']); ?></span>
                                    <span class="text-base-content/50 text-xs">/คืน</span>
                                </div>
                            </figure>
                        <?php else: ?>
                            <figure class="h-56 bg-base-200 flex items-center justify-center relative">
                                <i data-lucide="bed-double" class="size-16 text-primary/30"></i>
                                <div class="absolute top-4 right-4 bg-base-100/90 backdrop-blur-sm rounded-xl px-3 py-1.5 shadow-lg">
                                    <span class="text-primary font-bold text-lg">฿<?php echo number_format($room['base_price_per_night']); ?></span>
                                    <span class="text-base-content/50 text-xs">/คืน</span>
                                </div>
                            </figure>
                        <?php endif; ?>
                        <div class="card-body flex flex-col">
                            <h2 class="card-title text-base-content mb-2 group-hover:text-primary transition-colors">
                                <?php echo htmlspecialchars($room['name']); ?>
                            </h2>
                            <p class="text-base-content/70 text-sm mb-4 line-clamp-2">
                                <?php echo htmlspecialchars($room['description'] ?? 'ห้องพักสะอาด ปลอดภัย พร้อมสิ่งอำนวยความสะดวกครบครัน'); ?>
                            </p>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php if (!empty($room['size_sqm'])): ?>
                                    <span class="inline-flex items-center gap-1 bg-base-200/60 rounded-lg px-3 py-1 text-xs text-base-content/70">
                                        <i data-lucide="ruler" class="size-4"></i>
                                        <?php echo htmlspecialchars($room['size_sqm']); ?> ตร.ม.
                                    </span>
                                <?php endif; ?>
                                <span class="inline-flex items-center gap-1 bg-base-200/60 rounded-lg px-3 py-1 text-xs text-base-content/70">
                                    <i data-lucide="paw-print" class="size-4"></i>
                                    สูงสุด <?php echo (int)$room['max_pets']; ?> ตัว
                                </span>
                            </div>
                            <?php
                                $amenities = $amenities_map[$room['id']] ?? [];
                            ?>
                            <?php if ($amenities): ?>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php foreach ($amenities as $am): ?>
                                        <span class="inline-flex items-center gap-1 bg-base-200/80 rounded-lg px-2.5 py-1 text-xs text-base-content/80">
                                            <?php
                                                $icon = $am['icon_class'] ?? '';
                                                if (str_starts_with($icon, 'lucide-')) {
                                                    $icon = substr($icon, 7);
                                                }
                                            ?>
                                            <?php if (!empty($icon)): ?>
                                                <i data-lucide="<?php echo htmlspecialchars($icon); ?>" class="size-4"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($am['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="card-actions mt-auto">
                                <a href="?page=booking&amp;room_type_id=<?php echo (int)$room['id']; ?>"
                                   class="btn btn-primary w-full rounded-xl gap-2 font-medium text-base shadow-sm hover:shadow-md hover:shadow-primary/20 transition-all hover:scale-[1.01]">
                                    เลือกห้องนี้
                                    <i data-lucide="arrow-right" class="size-5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="bed-double" class="size-12 text-primary/40"></i>
                </div>
                <h3 class="text-xl font-bold text-base-content mb-2">ขออภัย ไม่พบข้อมูลประเภทห้องพัก</h3>
                <p class="text-base-content/60 max-w-md mx-auto mb-6">
                    กรุณาติดต่อเจ้าหน้าที่เพื่อสอบถามรายละเอียดเพิ่มเติม
                </p>
                <a href="?page=contact" class="btn btn-primary rounded-xl gap-2">
                    <i data-lucide="mail" class="size-4"></i>
                    ติดต่อสอบถาม
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>