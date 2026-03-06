<?php
// ═══════════════════════════════════════════════════════════
// CMS REVIEWS UI - VET4 HOTEL ADMIN
// Manage and moderate customer reviews with staff reply
// ═══════════════════════════════════════════════════════════

// Filter
$status_filter = $_GET['status'] ?? 'all';

$query = "SELECT 
            r.*,
            c.first_name,
            c.last_name,
            bk.booking_ref
          FROM reviews r
          JOIN customers c ON r.customer_id = c.id
          JOIN bookings bk ON r.booking_id = bk.id
          WHERE 1=1";

$params = [];
if ($status_filter === 'published') {
    $query .= " AND r.is_published = 1";
} elseif ($status_filter === 'hidden') {
    $query .= " AND r.is_published = 0";
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Stats
$stmt_stats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published,
    SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as hidden,
    AVG(rating) as avg_rating
    FROM reviews");
$stats = $stmt_stats->fetch();

// Helper
function renderStars($rating)
{
    $html = '<div class="flex items-center gap-0.5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i data-lucide="star" class="size-4 text-warning fill-warning"></i>';
        } else {
            $html .= '<i data-lucide="star" class="size-4 text-base-content/20"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}
?>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="message-square-quote" class="size-5 text-primary"></i>
                </div>
                รีวิวจากลูกค้า
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">ตรวจสอบและอนุมัติการแสดงผลรีวิวบนหน้าเว็บไซต์</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">รีวิวทั้งหมด</p>
                <p class="text-2xl font-bold">
                    <?php echo $stats['total'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 flex flex-row items-center justify-between">
                <div>
                    <p class="text-xs text-base-content/50 font-medium uppercase">คะแนนเฉลี่ย</p>
                    <p class="text-2xl font-bold text-warning">
                        <?php echo number_format($stats['avg_rating'] ?? 0, 1); ?>
                    </p>
                </div>
                <i data-lucide="star" class="size-8 text-warning/20 fill-warning/20"></i>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">อนุมัติแล้ว (ขึ้นเว็บ)</p>
                <p class="text-2xl font-bold text-success">
                    <?php echo $stats['published'] ?? 0; ?>
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase">ซ่อนอยู่</p>
                <p class="text-2xl font-bold text-base-content/30">
                    <?php echo $stats['hidden'] ?? 0; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Filter and Actions -->
    <div class="flex justify-between items-center mb-4">
        <div role="tablist" class="tabs tabs-boxed bg-base-100/50 p-1 font-medium shadow-sm">
            <a href="?page=cms_reviews&status=all" role="tab"
                class="tab <?php echo $status_filter === 'all' ? 'tab-active bg-primary text-primary-content' : ''; ?>">ทั้งหมด</a>
            <a href="?page=cms_reviews&status=published" role="tab"
                class="tab <?php echo $status_filter === 'published' ? 'tab-active bg-primary text-primary-content' : ''; ?>">อนุมัติแล้ว</a>
            <a href="?page=cms_reviews&status=hidden" role="tab"
                class="tab <?php echo $status_filter === 'hidden' ? 'tab-active bg-primary text-primary-content' : ''; ?>">ยังไม่อนุมัติ
                (ซ่อน)</a>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php if (empty($reviews)): ?>
            <div
                class="col-span-full card text-center py-12 text-base-content/40 bg-base-100 border border-base-200 shadow-sm">
                ไม่พบข้อมูลรีวิวในระบบ
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div
                    class="card bg-base-100 shadow-sm border <?php echo $review['is_published'] ? 'border-base-200' : 'border-warning/30 bg-warning/5'; ?>">
                    <div class="card-body p-5">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                    <div class="bg-neutral text-neutral-content rounded-full w-10 flex items-center justify-center">
                                        <span class="text-xs">
                                            <?php echo mb_substr($review['first_name'], 0, 1); ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm">
                                        <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                    </h3>
                                    <p class="text-[10px] text-base-content/50 font-mono"><a
                                            href="?page=booking_detail&id=<?php echo $review['booking_id']; ?>"
                                            class="hover:underline">อ้างอิง:
                                            <?php echo htmlspecialchars($review['booking_ref']); ?>
                                        </a></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <?php echo renderStars($review['rating']); ?>
                                <p class="text-[10px] text-base-content/40 mt-1">
                                    <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="bg-base-200/50 p-3 rounded-lg flex-1 min-h-20 my-2 text-sm">
                            "<?php echo nl2br(htmlspecialchars($review['comment'] ?? 'ไม่มีข้อความอธิบาย')); ?>"
                        </div>

                        <!-- Staff Reply Display -->
                        <?php if ($review['staff_reply']): ?>
                            <div class="bg-primary/5 border border-primary/15 rounded-lg px-3 py-2.5 mb-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <i data-lucide="reply" class="size-3 text-primary"></i>
                                    <span class="text-[11px] font-semibold text-primary">ตอบกลับแล้ว</span>
                                    <span class="text-[10px] text-base-content/40">
                                        <?php echo date('d/m/Y H:i', strtotime($review['staff_reply_at'])); ?>
                                    </span>
                                </div>
                                <p class="text-xs text-base-content/70">
                                    <?php echo nl2br(htmlspecialchars($review['staff_reply'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="card-actions justify-between items-center mt-2 border-t border-base-200 pt-3">
                            <div class="flex items-center gap-2">
                                <?php if ($review['is_published']): ?>
                                    <span class="badge badge-success badge-sm badge-outline gap-1"><i data-lucide="check"
                                            class="size-3"></i> อนุมัติ (แสดงผล)</span>
                                <?php else: ?>
                                    <span class="badge badge-ghost badge-sm gap-1"><i data-lucide="eye-off" class="size-3"></i>
                                        ซ่อนบัญชีดำ</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-1">
                                <!-- Reply button -->
                                <button
                                    onclick="openReplyModal(<?php echo $review['id']; ?>, <?php echo htmlspecialchars(json_encode($review['staff_reply'] ?? ''), ENT_QUOTES); ?>)"
                                    class="btn btn-xs btn-outline btn-primary gap-1" title="ตอบกลับ">
                                    <i data-lucide="reply" class="size-3.5"></i>
                                    <?php echo $review['staff_reply'] ? 'แก้ไขคำตอบ' : 'ตอบกลับ'; ?>
                                </button>
                                <?php if ($review['is_published']): ?>
                                    <button onclick="toggleReview(<?php echo $review['id']; ?>, 0)"
                                        class="btn btn-xs btn-outline btn-warning">ซ่อน (นำออก)</button>
                                <?php else: ?>
                                    <button onclick="toggleReview(<?php echo $review['id']; ?>, 1)"
                                        class="btn btn-xs btn-primary">แสดงผลหน้าเว็บ</button>
                                <?php endif; ?>
                                <button onclick="deleteReview(<?php echo $review['id']; ?>)"
                                    class="btn btn-xs btn-square btn-ghost text-error" title="ลบทิ้ง"><i data-lucide="trash-2"
                                        class="size-4"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden forms -->
<form id="status-form" method="POST" action="?action=reviews" style="display: none;">
    <input type="hidden" name="sub_action" value="toggle_review">
    <input type="hidden" name="review_id" id="status_review_id">
    <input type="hidden" name="new_status" id="status_val">
</form>

<form id="delete-form" method="POST" action="?action=reviews" style="display: none;">
    <input type="hidden" name="sub_action" value="delete_review">
    <input type="hidden" name="review_id" id="delete_review_id">
</form>

<!-- Reply Modal -->
<dialog id="modal-reply" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box bg-base-100 rounded-t-3xl sm:rounded-3xl p-0 overflow-hidden shadow-2xl max-w-md">
        <div class="p-6 border-b border-base-200 flex items-center gap-3 bg-base-100/50">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                <i data-lucide="reply" class="size-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg text-base-content leading-tight">ตอบกลับรีวิว</h3>
                <p class="text-sm text-base-content/60 mt-0.5">เขียนข้อความตอบกลับให้ลูกค้า</p>
            </div>
            <form method="dialog" class="ml-auto">
                <button
                    class="btn btn-sm btn-circle btn-ghost text-base-content/50 hover:text-base-content hover:bg-base-200">
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </form>
        </div>

        <form action="?action=reviews" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="sub_action" value="reply_review">
            <input type="hidden" name="review_id" id="reply_review_id">

            <div class="form-control">
                <label class="label pt-0"><span class="label-text font-medium">ข้อความตอบกลับ <span
                            class="text-error">*</span></span></label>
                <textarea name="staff_reply" id="reply_textarea"
                    class="textarea textarea-bordered h-28 rounded-xl focus:outline-primary/50 focus:border-primary transition-colors w-full"
                    placeholder="ขอบคุณสำหรับรีวิวค่ะ..." required></textarea>
            </div>

            <div class="modal-action mt-6">
                <button type="button" class="btn btn-ghost rounded-xl font-medium"
                    onclick="document.getElementById('modal-reply').close()">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-xl font-medium gap-2 shadow-sm">
                    <i data-lucide="send" class="size-4"></i> ส่งคำตอบ
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<script>
    function toggleReview(id, newStatus) {
        document.getElementById('status_review_id').value = id;
        document.getElementById('status_val').value = newStatus;
        document.getElementById('status-form').submit();
    }

    function deleteReview(id) {
        if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรีวิวนี้ทิ้งเลย? ข้อมูลจะถูกลบถาวร')) {
            document.getElementById('delete_review_id').value = id;
            document.getElementById('delete-form').submit();
        }
    }

    function openReplyModal(id, existingReply) {
        document.getElementById('reply_review_id').value = id;
        document.getElementById('reply_textarea').value = existingReply || '';
        document.getElementById('modal-reply').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
</script>