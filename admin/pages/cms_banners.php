<?php
// ═══════════════════════════════════════════════════════════
// CMS BANNERS UI - VET4 HOTEL ADMIN
// Manage homepage banners
// ═══════════════════════════════════════════════════════════

$stmt = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC, created_at DESC");
$banners = $stmt->fetchAll();

?>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="image" class="size-5 text-primary"></i>
                </div>
                จัดการแบนเนอร์
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">เพิ่ม ลบ หรือแก้ไขภาพแบนเนอร์สไลด์ในหน้าแรกของเว็บไซต์
            </p>
        </div>
        <button onclick="document.getElementById('modal_add_banner').showModal()" class="btn btn-primary gap-2">
            <i data-lucide="plus" class="size-4"></i> เพิ่มแบนเนอร์ใหม่
        </button>
    </div>

    <!-- Banners Grid -->
    <?php if (empty($banners)): ?>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-12 text-center text-base-content/50">
                <i data-lucide="image-off" class="size-16 mx-auto mb-4 opacity-20"></i>
                <h3 class="text-lg font-bold">ยังไม่มีข้อมูลแบนเนอร์</h3>
                <p>คลิกปุ่ม "เพิ่มแบนเนอร์ใหม่" เพื่อเริ่มต้น</p>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($banners as $banner): ?>
                <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden group">
                    <figure class="relative aspect-video bg-base-200">
                        <img src="../<?php echo htmlspecialchars($banner['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner'); ?>"
                            class="w-full h-full object-cover">

                        <!-- Status Overlay -->
                        <div class="absolute top-3 right-3 flex gap-2">
                            <div
                                class="badge <?php echo $banner['is_active'] ? 'badge-success' : 'badge-neutral opacity-80'; ?> shadow-sm">
                                <?php echo $banner['is_active'] ? 'กำลังแสดงผล' : 'ซ่อน'; ?>
                            </div>
                        </div>

                        <!-- Action Overlay (Hover) -->
                        <div
                            class="absolute inset-0 bg-base-300/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-sm">
                            <button onclick='editBanner(<?php echo json_encode($banner, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                class="btn btn-circle btn-primary btn-sm tooltip" data-tip="แก้ไขข้อมูล">
                                <i data-lucide="pencil" class="size-4"></i>
                            </button>

                            <?php if ($banner['is_active']): ?>
                                <button onclick="toggleStatus(<?php echo $banner['id']; ?>, 0)"
                                    class="btn btn-circle btn-warning btn-sm tooltip" data-tip="ซ่อนแบนเนอร์">
                                    <i data-lucide="eye-off" class="size-4"></i>
                                </button>
                            <?php else: ?>
                                <button onclick="toggleStatus(<?php echo $banner['id']; ?>, 1)"
                                    class="btn btn-circle btn-success btn-sm tooltip" data-tip="แสดงรูปภาพ">
                                    <i data-lucide="eye" class="size-4 text-success-content"></i>
                                </button>
                            <?php endif; ?>

                            <button onclick="deleteBanner(<?php echo $banner['id']; ?>)"
                                class="btn btn-circle btn-error btn-sm tooltip" data-tip="ลบข้อมูล">
                                <i data-lucide="trash-2" class="size-4"></i>
                            </button>
                        </div>
                    </figure>
                    <div class="card-body p-4">
                        <h2 class="card-title text-base">
                            <?php echo $banner['title'] ? htmlspecialchars($banner['title']) : '<span class="text-base-content/40 italic">ไม่มีชื่อ</span>'; ?>
                        </h2>
                        <?php if ($banner['target_url']): ?>
                            <a href="<?php echo htmlspecialchars($banner['target_url']); ?>" target="_blank"
                                class="text-xs text-primary hover:underline truncate flex items-center gap-1 mt-1">
                                <i data-lucide="link" class="size-3"></i>
                                <?php echo htmlspecialchars($banner['target_url']); ?>
                            </a>
                        <?php endif; ?>
                        <div class="text-xs text-base-content/50 mt-2 flex justify-between items-center">
                            <span>ลำดับที่: <strong class="text-base-content">
                                    <?php echo $banner['display_order']; ?>
                                </strong></span>
                            <span>อัปโหลดเมื่อ:
                                <?php echo date('d/m/Y', strtotime($banner['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Banner Modal -->
<dialog id="modal_add_banner" class="modal">
    <div class="modal-box max-w-md">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
            <i data-lucide="image-plus" class="size-5 text-primary"></i>
            เพิ่มแบนเนอร์ใหม่
        </h3>

        <form method="POST" action="?action=banners" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="sub_action" value="add_banner">

            <div class="form-control">
                <label class="label"><span class="label-text">รูปภาพแบนเนอร์ * <span class="text-error">(แนะนำขนาด
                            1920x600 px)</span></span></label>
                <input type="file" name="banner_image" accept="image/jpeg, image/png, image/webp"
                    class="file-input file-input-bordered file-input-primary file-input-sm w-full" required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text">หัวข้อ (Title)</span></label>
                <input type="text" name="title" class="input input-sm input-bordered"
                    placeholder="ข้อความแสดงผลบนรูปภาพ (ถ้ามี)">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text">ลิงก์ปลายทาง (Target URL)</span></label>
                <input type="url" name="target_url" class="input input-sm input-bordered" placeholder="https://...">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text">ลำดับการแสดงผล</span></label>
                <input type="number" name="display_order" value="0" class="input input-sm input-bordered w-32">
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">อัปโหลดแบนเนอร์</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Edit Banner Modal -->
<dialog id="modal_edit_banner" class="modal">
    <div class="modal-box max-w-md">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button></form>
        <h3 class="font-bold text-lg mb-4">แก้ไขข้อมูลแบนเนอร์</h3>

        <form method="POST" action="?action=banners" class="space-y-4">
            <input type="hidden" name="sub_action" value="edit_banner">
            <input type="hidden" name="banner_id" id="edit_banner_id">

            <div class="form-control">
                <label class="label"><span class="label-text">หัวข้อ (Title)</span></label>
                <input type="text" name="title" id="edit_title" class="input input-sm input-bordered">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text">ลิงก์ปลายทาง (Target URL)</span></label>
                <input type="url" name="target_url" id="edit_target_url" class="input input-sm input-bordered">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text">ลำดับการแสดงผล</span></label>
                <input type="number" name="display_order" id="edit_display_order"
                    class="input input-sm input-bordered w-32">
            </div>

            <button type="submit" class="btn btn-warning w-full mt-4">บันทึกการแก้ไข</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Hidden forms for row actions -->
<form id="status-form" method="POST" action="?action=banners" style="display: none;">
    <input type="hidden" name="sub_action" value="toggle_banner">
    <input type="hidden" name="banner_id" id="status_banner_id">
    <input type="hidden" name="new_status" id="status_val">
</form>

<form id="delete-form" method="POST" action="?action=banners" style="display: none;">
    <input type="hidden" name="sub_action" value="delete_banner">
    <input type="hidden" name="banner_id" id="delete_banner_id">
</form>

<script>
    function editBanner(banner) {
        document.getElementById('edit_banner_id').value = banner.id;
        document.getElementById('edit_title').value = banner.title || '';
        document.getElementById('edit_target_url').value = banner.target_url || '';
        document.getElementById('edit_display_order').value = banner.display_order;
        document.getElementById('modal_edit_banner').showModal();
    }

    function toggleStatus(id, newStatus) {
        document.getElementById('status_banner_id').value = id;
        document.getElementById('status_val').value = newStatus;
        document.getElementById('status-form').submit();
    }

    function deleteBanner(id) {
        if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบแบนเนอร์นี้? ข้อมูลรูปภาพจะหายไปอย่างถาวร')) {
            document.getElementById('delete_banner_id').value = id;
            document.getElementById('delete-form').submit();
        }
    }
</script>