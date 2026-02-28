<?php
// ═══════════════════════════════════════════════════════════
// ADMIN PETS PAGE UI — VET4 HOTEL
// Pets management: list, search, filter, edit, toggle aggressive, soft delete
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/pets_data.php';

// Helper: gender badge
function gender_badge($gender)
{
    $map = [
        'male' => ['ผู้', 'badge-info'],
        'female' => ['เมีย', 'badge-secondary'],
        'spayed' => ['ทำหมันแล้ว', 'badge-accent'],
        'neutered' => ['ทำหมันแล้ว', 'badge-accent'],
        'unknown' => ['ไม่ระบุ', 'badge-ghost'],
    ];
    $info = $map[$gender] ?? ['ไม่ทราบ', 'badge-ghost'];
    return '<span class="badge badge-sm ' . $info[1] . ' gap-1">' . $info[0] . '</span>';
}

// Helper: aggressive badge (prominent red for safety — AGENTS.md §6.3)
function aggressive_badge($is_aggressive)
{
    if ($is_aggressive) {
        return '<span class="badge badge-sm badge-error gap-1 font-bold animate-pulse"><i data-lucide="alert-triangle" class="size-3"></i> ดุ/ก้าวร้าว</span>';
    }
    return '<span class="badge badge-sm badge-success gap-1"><i data-lucide="smile" class="size-3"></i> ปกติ</span>';
}

// Helper: calculate age
function pet_age($dob)
{
    if (!$dob)
        return '<span class="text-base-content/30">—</span>';
    $birth = new DateTime($dob);
    $now = new DateTime();
    $diff = $now->diff($birth);
    if ($diff->y > 0) {
        return $diff->y . ' ปี' . ($diff->m > 0 ? ' ' . $diff->m . ' เดือน' : '');
    }
    return $diff->m . ' เดือน';
}
?>

<div class="p-4 lg:p-8 space-y-6 max-w-[1600px] mx-auto">

    <!-- ═══════════ HEADER & ACTIONS ═══════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-base-content flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i data-lucide="paw-print" class="size-5 text-primary"></i>
                </div>
                จัดการสัตว์เลี้ยง
            </h1>
            <p class="text-base-content/60 text-sm mt-1 ml-13">
                ดูข้อมูลสัตว์เลี้ยงทั้งหมด แก้ไขข้อมูล และจัดการสถานะพฤติกรรม
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openAddPetModal()" class="btn btn-primary gap-2 shadow-sm">
                <i data-lucide="plus" class="size-4"></i>
                เพิ่มสัตว์เลี้ยงใหม่
            </button>
        </div>
    </div>

    <!-- ═══════════ SUMMARY STAT CARDS ═══════════ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <!-- Total Pets -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">สัตว์เลี้ยงทั้งหมด
                        </p>
                        <p class="text-2xl font-bold text-base-content mt-1">
                            <?php echo $stats['total'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-base-200/80 flex items-center justify-center">
                        <i data-lucide="paw-print" class="size-5 text-base-content/40"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Aggressive Warning -->
        <div
            class="card bg-base-100 border <?php echo ($stats['aggressive_count'] ?? 0) > 0 ? 'border-error/30 ring-1 ring-error/20' : 'border-base-200'; ?> shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">ดุ/ก้าวร้าว</p>
                        <p class="text-2xl font-bold text-error mt-1">
                            <?php echo $stats['aggressive_count'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-error/10 flex items-center justify-center">
                        <i data-lucide="alert-triangle"
                            class="size-5 text-error <?php echo ($stats['aggressive_count'] ?? 0) > 0 ? 'animate-pulse' : ''; ?>"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Currently Checked In -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">เข้าพักอยู่ตอนนี้
                        </p>
                        <p class="text-2xl font-bold text-primary mt-1">
                            <?php echo $checked_in_pet_count; ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i data-lucide="home" class="size-5 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Species Breakdown -->
        <div
            class="card bg-base-100 border border-base-200 shadow-sm relative overflow-hidden group hover:border-primary/30 transition-colors">
            <!-- Subtle gradient background purely for premium feel -->
            <div
                class="absolute inset-0 bg-linear-to-br from-base-200/50 via-transparent to-primary/5 opacity-0 group-hover:opacity-100 transition-opacity">
            </div>

            <div class="card-body p-4 flex flex-col justify-center relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p
                        class="text-xs text-base-content/50 font-medium uppercase tracking-wide flex items-center gap-1.5">
                        <i data-lucide="pie-chart" class="size-3.5 opacity-70"></i>
                        สัดส่วนประเภทสัตว์
                    </p>
                    <div class="w-6 h-6 rounded-md bg-base-200/80 flex items-center justify-center tooltip tooltip-left"
                        data-tip="สัตว์เลี้ยงแยกตามสายพันธุ์">
                        <span
                            class="text-[10px] font-bold text-base-content/70"><?php echo count($species_stats); ?></span>
                    </div>
                </div>

                <?php if (!empty($species_stats)): ?>
                    <!-- Segmented Progress Bar -->
                    <div class="flex w-full h-2.5 rounded-full overflow-hidden gap-0.5 bg-base-200 mb-2">
                        <?php
                        $colors = ['bg-primary', 'bg-info', 'bg-secondary', 'bg-accent'];
                        $total_pets = $stats['total'] ?: 1;
                        foreach (array_slice($species_stats, 0, 4) as $index => $sp):
                            $percent = max(($sp['cnt'] / $total_pets) * 100, 2); // Set minimum 2% width so it's visible
                            $color_class = $colors[$index % count($colors)];
                            ?>
                            <div class="<?php echo $color_class; ?> hover:opacity-80 transition-opacity cursor-help"
                                style="width: <?php echo $percent; ?>%"
                                title="<?php echo htmlspecialchars($sp['name']) . ' (' . $sp['cnt'] . ' ตัว)'; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Clean Legend -->
                    <div class="flex gap-x-3 gap-y-1 mt-1 truncate">
                        <?php foreach (array_slice($species_stats, 0, 2) as $index => $sp):
                            $color_class = $colors[$index % count($colors)];
                            ?>
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0 <?php echo $color_class; ?> shadow-sm"></span>
                                <span class="text-xs font-medium text-base-content/80 truncate">
                                    <?php echo htmlspecialchars($sp['name']); ?>
                                </span>
                                <span class="text-[10px] text-base-content/40 font-semibold">(<?php echo $sp['cnt']; ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-2xl font-bold text-base-content/20 mt-1">—</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════ FILTERS & SEARCH ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form action="?page=pets" method="GET" class="flex flex-col xl:flex-row gap-4">
                <input type="hidden" name="page" value="pets">

                <!-- Search -->
                <div class="form-control flex-1">
                    <label class="label pt-0"><span class="label-text font-medium">ค้นหา</span></label>
                    <label class="input w-full">
                        <i data-lucide="search" class="h-[1em] opacity-50"></i>
                        <input type="search" name="search" placeholder="ชื่อสัตว์เลี้ยง, ชื่อเจ้าของ, สายพันธุ์..."
                            value="<?php echo htmlspecialchars($search); ?>" />
                    </label>
                </div>

                <!-- Species Filter -->
                <div class="form-control w-full xl:w-44">
                    <label class="label pt-0"><span class="label-text font-medium">ชนิดสัตว์</span></label>
                    <select name="species" class="select select-bordered w-full focus:select-primary transition-colors">
                        <option value="all">ทั้งหมด</option>
                        <?php foreach ($species_list as $sp): ?>
                            <option value="<?php echo $sp['id']; ?>" <?php echo $species_filter == $sp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sp['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Gender Filter -->
                <div class="form-control w-full xl:w-44">
                    <label class="label pt-0"><span class="label-text font-medium">เพศ</span></label>
                    <select name="gender" class="select select-bordered w-full focus:select-primary transition-colors">
                        <?php foreach ($gender_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>" <?php echo $gender_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $cfg['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Aggressive Filter -->
                <div class="form-control w-full xl:w-44">
                    <label class="label pt-0"><span class="label-text font-medium">พฤติกรรม</span></label>
                    <select name="aggressive"
                        class="select select-bordered w-full focus:select-primary transition-colors">
                        <?php foreach ($aggressive_config as $key => $cfg): ?>
                            <option value="<?php echo $key; ?>" <?php echo $aggressive_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $cfg['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2 mt-2 xl:mt-0">
                    <button type="submit" class="btn btn-primary gap-2 w-full sm:w-auto">
                        <i data-lucide="filter" class="size-4"></i>
                        กรองข้อมูล
                    </button>
                    <a href="?page=pets"
                        class="btn btn-ghost btn-square text-base-content/50 hover:text-base-content tooltip"
                        data-tip="ล้างตัวกรอง">
                        <i data-lucide="rotate-ccw" class="size-4"></i>
                    </a>
                </div>
            </form>

            <?php if (($stats['total'] ?? 0) > 0): ?>
                <!-- Quick Species Filter Badges -->
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-base-200">
                    <span class="text-sm text-base-content/60 mr-2 flex items-center">ตัวกรองด่วน:</span>
                    <?php foreach ($species_stats as $sp): ?>
                        <a href="?page=pets&species=<?php
                        // Find species id
                        foreach ($species_list as $sl) {
                            if ($sl['name'] === $sp['name']) {
                                echo $sl['id'];
                                break;
                            }
                        }
                        ?>" class="badge badge-sm badge-outline hover:scale-105 transition-transform cursor-pointer <?php
                        $sp_id = '';
                        foreach ($species_list as $sl) {
                            if ($sl['name'] === $sp['name']) {
                                $sp_id = $sl['id'];
                                break;
                            }
                        }
                        echo $species_filter == $sp_id ? 'ring-2 ring-offset-1 ring-primary' : 'opacity-80';
                        ?>">
                            <?php echo htmlspecialchars($sp['name']); ?> (<?php echo $sp['cnt']; ?>)
                        </a>
                    <?php endforeach; ?>
                    <?php if (($stats['aggressive_count'] ?? 0) > 0): ?>
                        <a href="?page=pets&aggressive=1"
                            class="badge badge-sm badge-error hover:scale-105 transition-transform cursor-pointer <?php echo $aggressive_filter === '1' ? 'ring-2 ring-offset-1 ring-error' : 'opacity-80'; ?>">
                            ⚠️ ดุ/ก้าวร้าว (<?php echo $stats['aggressive_count']; ?>)
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════ DATA TABLE ═══════════ -->
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-sm sm:table-md w-full">
                <thead class="bg-base-200/50 text-base-content/70">
                    <tr>
                        <th class="font-medium">สัตว์เลี้ยง</th>
                        <th class="font-medium">เจ้าของ</th>
                        <th class="font-medium text-center">ชนิด / สายพันธุ์</th>
                        <th class="font-medium text-center">เพศ</th>
                        <th class="font-medium text-center">อายุ</th>
                        <th class="font-medium text-center">น้ำหนัก</th>
                        <th class="font-medium text-center">พฤติกรรม</th>
                        <th class="font-medium text-center">วัคซีน</th>
                        <th class="font-medium text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pets)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-10 text-base-content/50">
                                <i data-lucide="search-x" class="size-10 mx-auto mb-3 opacity-30"></i>
                                ไม่มีข้อมูลสัตว์เลี้ยงที่ตรงกับเงื่อนไขการค้นหา
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pets as $pet): ?>
                            <tr class="hover group <?php echo $pet['is_aggressive'] ? 'bg-error/5' : ''; ?>">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-lg <?php echo $pet['is_aggressive'] ? 'bg-error/10' : 'bg-primary/10'; ?> flex items-center justify-center shrink-0">
                                            <i data-lucide="<?php echo $pet['is_aggressive'] ? 'alert-triangle' : 'paw-print'; ?>"
                                                class="size-4 <?php echo $pet['is_aggressive'] ? 'text-error' : 'text-primary'; ?>"></i>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-base-content">
                                                <?php echo htmlspecialchars($pet['name']); ?>
                                            </span>
                                            <?php if ($pet['active_booking_count'] > 0): ?>
                                                <span class="badge badge-xs badge-primary ml-1">เข้าพักอยู่</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium">
                                            <?php echo htmlspecialchars($pet['owner_first_name'] . ' ' . $pet['owner_last_name']); ?>
                                        </span>
                                        <span class="text-xs text-base-content/50">
                                            <?php echo htmlspecialchars($pet['owner_phone'] ?? ''); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="flex flex-col items-center">
                                        <span
                                            class="text-sm font-medium"><?php echo htmlspecialchars($pet['species_name'] ?? '—'); ?></span>
                                        <span
                                            class="text-xs text-base-content/50"><?php echo htmlspecialchars($pet['breed_name'] ?? '—'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php echo gender_badge($pet['gender']); ?>
                                </td>
                                <td class="text-center text-sm">
                                    <?php echo pet_age($pet['dob']); ?>
                                </td>
                                <td class="text-center text-sm">
                                    <?php echo $pet['weight_kg'] ? number_format($pet['weight_kg'], 1) . ' kg' : '<span class="text-base-content/30">—</span>'; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo aggressive_badge($pet['is_aggressive']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($pet['vaccination_count'] > 0): ?>
                                        <div class="flex flex-col items-center">
                                            <span class="badge badge-sm badge-outline badge-success gap-1">
                                                <i data-lucide="syringe" class="size-3"></i>
                                                <?php echo $pet['valid_vaccination_count']; ?>/<?php echo $pet['vaccination_count']; ?>
                                            </span>
                                            <span class="text-[10px] text-base-content/40 mt-0.5">valid/ทั้งหมด</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-base-content/30 text-xs">ยังไม่มี</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View Owner -->
                                        <a href="?page=customers&search=<?php echo urlencode($pet['owner_phone'] ?? ''); ?>"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-info tooltip"
                                            data-tip="ดูเจ้าของ">
                                            <i data-lucide="user" class="size-4"></i>
                                        </a>
                                        <!-- Edit Button -->
                                        <button onclick='openEditPetModal(<?php echo htmlspecialchars(json_encode($pet)); ?>)'
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-primary tooltip"
                                            data-tip="แก้ไข">
                                            <i data-lucide="pencil" class="size-4"></i>
                                        </button>
                                        <!-- Toggle Aggressive Button -->
                                        <?php if ($pet['is_aggressive']): ?>
                                            <button type="button"
                                                onclick="openAggressiveModal(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name'], ENT_QUOTES); ?>', 0)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-success tooltip"
                                                data-tip="เปลี่ยนเป็นปกติ">
                                                <i data-lucide="shield-check" class="size-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="openAggressiveModal(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name'], ENT_QUOTES); ?>', 1)"
                                                class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-error tooltip"
                                                data-tip="ทำเครื่องหมายดุ/ก้าวร้าว">
                                                <i data-lucide="alert-triangle" class="size-4"></i>
                                            </button>
                                        <?php endif; ?>
                                        <!-- Delete Button -->
                                        <button type="button"
                                            onclick="openDeletePetModal(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name'], ENT_QUOTES); ?>')"
                                            class="btn btn-sm btn-ghost btn-square text-base-content/50 hover:text-error tooltip"
                                            data-tip="ลบ">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="border-t border-base-200 p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-sm text-base-content/60">
                    แสดง
                    <?php echo ($offset + 1); ?> ถึง
                    <?php echo min($offset + $limit, $total_records); ?> จาก
                    <?php echo $total_records; ?> รายการ
                </span>
                <div class="join shadow-sm">
                    <?php
                    $query_string = $_GET;
                    unset($query_string['p']);
                    $base_url = '?' . http_build_query($query_string) . '&p=';

                    if ($page > 1): ?>
                        <a href="<?php echo $base_url . ($page - 1); ?>"
                            class="join-item btn btn-sm bg-base-100 hover:bg-base-200">«</a>
                    <?php else: ?>
                        <button class="join-item btn btn-sm bg-base-100 btn-disabled">«</button>
                    <?php endif;

                    for ($i = 1; $i <= $total_pages; $i++):
                        if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)):
                            $active = ($i == $page) ? 'btn-primary' : 'bg-base-100 hover:bg-base-200';
                            ?>
                            <a href="<?php echo $base_url . $i; ?>" class="join-item btn btn-sm <?php echo $active; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <button class="join-item btn btn-sm bg-base-100 btn-disabled">...</button>
                        <?php endif;
                    endfor;

                    if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url . ($page + 1); ?>"
                            class="join-item btn btn-sm bg-base-100 hover:bg-base-200">»</a>
                    <?php else: ?>
                        <button class="join-item btn btn-sm bg-base-100 btn-disabled">»</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ═══════════ EDIT PET MODAL ═══════════ -->
<dialog id="modal_edit_pet" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                <i data-lucide="pencil" class="size-4 text-warning"></i>
            </div>
            แก้ไขข้อมูลสัตว์เลี้ยง
        </h3>
        <form method="POST" action="?action=pets" id="edit_pet_form" class="space-y-4">
            <input type="hidden" name="sub_action" value="edit">
            <input type="hidden" name="pet_id" id="edit_pet_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อ <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="name" id="edit_pet_name"
                        class="input input-bordered w-full focus:input-primary" required />
                </div>

                <!-- Species -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชนิดสัตว์ <span
                                class="text-error">*</span></span></label>
                    <select name="species_id" id="edit_pet_species"
                        class="select select-bordered w-full focus:select-primary" required
                        onchange="updateBreedDropdown(this.value)">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($species_list as $sp): ?>
                            <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Breed -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">สายพันธุ์ <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <select name="breed_id" id="edit_pet_breed"
                        class="select select-bordered w-full focus:select-primary">
                        <option value="">-- เลือก --</option>
                    </select>
                </div>

                <!-- Gender -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">เพศ</span></label>
                    <select name="gender" id="edit_pet_gender"
                        class="select select-bordered w-full focus:select-primary">
                        <option value="male">ผู้ (Male)</option>
                        <option value="female">เมีย (Female)</option>
                        <option value="neutered">ทำหมันแล้ว — ผู้ (Neutered)</option>
                        <option value="spayed">ทำหมันแล้ว — เมีย (Spayed)</option>
                        <option value="unknown">ไม่ระบุ</option>
                    </select>
                </div>

                <!-- DOB -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">วันเกิด <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="date" name="dob" id="edit_pet_dob"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Weight -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">น้ำหนัก (kg) <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="number" name="weight_kg" id="edit_pet_weight" step="0.01" min="0"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Vet Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">คลินิก/หมอประจำตัว <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="text" name="vet_name" id="edit_pet_vet_name"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Vet Phone -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">เบอร์คลินิก <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="text" name="vet_phone" id="edit_pet_vet_phone"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <!-- Aggressive Checkbox -->
            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" name="is_aggressive" id="edit_pet_aggressive" class="checkbox checkbox-error"
                        value="1" />
                    <div>
                        <span class="label-text font-medium">ดุ/ก้าวร้าว</span>
                        <p class="text-xs text-base-content/50 mt-0.5">
                            ทำเครื่องหมายนี้เพื่อแจ้งเตือนพนักงานเรื่องความปลอดภัย</p>
                    </div>
                </label>
            </div>

            <!-- Behavior Note -->
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">หมายเหตุพฤติกรรม <span
                            class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                <textarea name="behavior_note" id="edit_pet_behavior_note"
                    class="textarea textarea-bordered w-full focus:textarea-primary h-20" rows="2"
                    placeholder="เช่น กลัวฟ้าร้อง, ชอบกัด, ต้องดูแลพิเศษ..."></textarea>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-warning gap-2">
                    <i data-lucide="save" class="size-4"></i>
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ CONFIRM TOGGLE AGGRESSIVE MODAL ═══════════ -->
<dialog id="modal_toggle_aggressive" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div id="aggressive_icon_wrap"
                class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-warning/10">
                <i data-lucide="alert-triangle" class="size-7 text-warning"></i>
            </div>
            <h3 class="font-bold text-lg mb-2">ยืนยันการเปลี่ยนสถานะพฤติกรรม</h3>
            <p class="text-base-content/60" id="aggressive_message">ต้องการเปลี่ยนสถานะพฤติกรรมสัตว์เลี้ยงนี้ใช่หรือไม่?
            </p>
        </div>
        <form method="POST" action="?action=pets" id="aggressive_form">
            <input type="hidden" name="sub_action" value="toggle_aggressive">
            <input type="hidden" name="pet_id" id="aggressive_pet_id">
            <input type="hidden" name="new_status" id="aggressive_new_status">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_toggle_aggressive').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" id="aggressive_submit_btn" class="btn btn-warning gap-2">
                    <i data-lucide="check" class="size-4"></i>
                    ยืนยัน
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ CONFIRM DELETE MODAL ═══════════ -->
<dialog id="modal_delete_pet" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <div class="text-center py-2">
            <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-error/10">
                <i data-lucide="trash-2" class="size-7 text-error"></i>
            </div>
            <h3 class="font-bold text-lg mb-2">ยืนยันการลบสัตว์เลี้ยง</h3>
            <p class="text-base-content/60" id="delete_pet_message">ต้องการลบสัตว์เลี้ยงนี้ใช่หรือไม่?</p>
            <p class="text-xs text-base-content/40 mt-2">การลบนี้สามารถกู้คืนได้โดยผู้ดูแลระบบ</p>
        </div>
        <form method="POST" action="?action=pets" id="delete_pet_form">
            <input type="hidden" name="sub_action" value="delete">
            <input type="hidden" name="pet_id" id="delete_pet_id">
            <div class="modal-action justify-center gap-3">
                <button type="button" onclick="document.getElementById('modal_delete_pet').close()"
                    class="btn btn-ghost">ยกเลิก</button>
                <button type="submit" class="btn btn-error gap-2">
                    <i data-lucide="trash-2" class="size-4"></i>
                    ลบสัตว์เลี้ยง
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<!-- ═══════════ ADD PET MODAL ═══════════ -->
<dialog id="modal_add_pet" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <i data-lucide="plus" class="size-4 text-primary"></i>
            </div>
            เพิ่มสัตว์เลี้ยงใหม่
        </h3>
        <form method="POST" action="?action=pets" id="add_pet_form" class="space-y-4">
            <input type="hidden" name="sub_action" value="add">
            <input type="hidden" name="origin_page" value="pets">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Customer (Owner) -->
                <div class="form-control sm:col-span-2">
                    <label class="label pt-0"><span class="label-text font-medium">เจ้าของสัตว์เลี้ยง <span
                                class="text-error">*</span></span></label>
                    <select name="customer_id" id="add_pet_customer"
                        class="select select-bordered w-full focus:select-primary" required>
                        <option value="">-- เลือกเจ้าของ... --</option>
                        <?php foreach ($customers_list as $cust): ?>
                            <option value="<?php echo $cust['id']; ?>">
                                <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?>
                                (<?php echo htmlspecialchars($cust['phone']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชื่อสัตว์เลี้ยง <span
                                class="text-error">*</span></span></label>
                    <input type="text" name="name" id="add_pet_name"
                        class="input input-bordered w-full focus:input-primary" placeholder="เช่น นิกกี้, มอมแมม"
                        required />
                </div>

                <!-- Species -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ชนิดสัตว์ <span
                                class="text-error">*</span></span></label>
                    <select name="species_id" id="add_pet_species"
                        class="select select-bordered w-full focus:select-primary" required
                        onchange="updateAddPetBreedDropdown(this.value)">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($species_list as $sp): ?>
                            <option value="<?php echo $sp['id']; ?>"><?php echo htmlspecialchars($sp['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Breed -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">สายพันธุ์ <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <select name="breed_id" id="add_pet_breed"
                        class="select select-bordered w-full focus:select-primary">
                        <option value="">-- เลือกชนิดสัตว์ก่อน --</option>
                    </select>
                </div>

                <!-- Gender -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">เพศ</span></label>
                    <select name="gender" id="add_pet_gender"
                        class="select select-bordered w-full focus:select-primary">
                        <option value="male">ผู้ (Male)</option>
                        <option value="female">เมีย (Female)</option>
                        <option value="neutered">ทำหมันแล้ว — ผู้ (Neutered)</option>
                        <option value="spayed">ทำหมันแล้ว — เมีย (Spayed)</option>
                        <option value="unknown" selected>ไม่ระบุ</option>
                    </select>
                </div>

                <!-- DOB -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">วันเกิด <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="date" name="dob" id="add_pet_dob"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Weight -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">น้ำหนัก (kg) <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="number" name="weight_kg" id="add_pet_weight" step="0.01" min="0" placeholder="เช่น 4.5"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Vet Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">คลินิก/หมอประจำตัว <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="text" name="vet_name" id="add_pet_vet_name"
                        class="input input-bordered w-full focus:input-primary" />
                </div>

                <!-- Vet Phone -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">เบอร์คลินิก <span
                                class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                    <input type="text" name="vet_phone" id="add_pet_vet_phone"
                        class="input input-bordered w-full focus:input-primary" />
                </div>
            </div>

            <!-- Aggressive Checkbox -->
            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" name="is_aggressive" id="add_pet_aggressive" class="checkbox checkbox-error"
                        value="1" />
                    <div>
                        <span class="label-text font-medium">ดุ/ก้าวร้าว</span>
                        <p class="text-xs text-base-content/50 mt-0.5">
                            ทำเครื่องหมายนี้เพื่อแจ้งเตือนพนักงานเรื่องความปลอดภัย</p>
                    </div>
                </label>
            </div>

            <!-- Behavior Note -->
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">หมายเหตุพฤติกรรม <span
                            class="text-base-content/40">(ไม่บังคับ)</span></span></label>
                <textarea name="behavior_note" id="add_pet_behavior_note"
                    class="textarea textarea-bordered w-full focus:textarea-primary h-20" rows="2"
                    placeholder="เช่น กลัวฟ้าร้อง, ชอบกัด, ต้องดูแลพิเศษ..."></textarea>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary gap-2">
                    <i data-lucide="save" class="size-4"></i>
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>ปิด</button></form>
</dialog>

<script>
    // Breeds data from PHP
    const breedsBySpecies = <?php echo json_encode($breeds_by_species); ?>;

    function updateBreedDropdown(speciesId) {
        const breedSelect = document.getElementById('edit_pet_breed');
        breedSelect.innerHTML = '<option value="">-- เลือก --</option>';

        if (speciesId && breedsBySpecies[speciesId]) {
            breedsBySpecies[speciesId].forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                breedSelect.appendChild(opt);
            });
        }
    }

    function updateAddPetBreedDropdown(speciesId) {
        const breedSelect = document.getElementById('add_pet_breed');
        breedSelect.innerHTML = '<option value="">-- เลือก --</option>';

        if (speciesId && breedsBySpecies[speciesId]) {
            breedsBySpecies[speciesId].forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                breedSelect.appendChild(opt);
            });
        }
    }

    function openAddPetModal() {
        document.getElementById('add_pet_form').reset();
        document.getElementById('add_pet_breed').innerHTML = '<option value="">-- เลือกชนิดสัตว์ก่อน --</option>';
        document.getElementById('modal_add_pet').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openEditPetModal(pet) {
        document.getElementById('edit_pet_id').value = pet.id;
        document.getElementById('edit_pet_name').value = pet.name;
        document.getElementById('edit_pet_species').value = pet.species_id;

        // Update breed dropdown first, then set value
        updateBreedDropdown(pet.species_id);
        setTimeout(() => {
            document.getElementById('edit_pet_breed').value = pet.breed_id || '';
        }, 50);

        document.getElementById('edit_pet_gender').value = pet.gender || 'unknown';
        document.getElementById('edit_pet_dob').value = pet.dob || '';
        document.getElementById('edit_pet_weight').value = pet.weight_kg || '';
        document.getElementById('edit_pet_vet_name').value = pet.vet_name || '';
        document.getElementById('edit_pet_vet_phone').value = pet.vet_phone || '';
        document.getElementById('edit_pet_aggressive').checked = pet.is_aggressive == 1;
        document.getElementById('edit_pet_behavior_note').value = pet.behavior_note || '';

        document.getElementById('modal_edit_pet').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openAggressiveModal(petId, petName, newStatus) {
        document.getElementById('aggressive_pet_id').value = petId;
        document.getElementById('aggressive_new_status').value = newStatus;

        const label = newStatus === 1 ? 'ดุ/ก้าวร้าว ⚠️' : 'ปกติ ✅';
        document.getElementById('aggressive_message').innerHTML =
            'ต้องการเปลี่ยนสถานะ <strong class="text-primary">"' + petName + '"</strong> เป็น <strong>' + label + '</strong> ใช่หรือไม่?';

        const btn = document.getElementById('aggressive_submit_btn');
        btn.className = 'btn gap-2';
        btn.classList.add(newStatus === 1 ? 'btn-error' : 'btn-success');

        const iconWrap = document.getElementById('aggressive_icon_wrap');
        iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4';
        iconWrap.classList.add(newStatus === 1 ? 'bg-error/10' : 'bg-success/10');

        document.getElementById('modal_toggle_aggressive').showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openDeletePetModal(petId, petName) {
        document.getElementById('delete_pet_id').value = petId;
        document.getElementById('delete_pet_message').innerHTML =
            'ต้องการลบ <strong class="text-primary">"' + petName + '"</strong> ออกจากระบบใช่หรือไม่?';
        document.getElementById('modal_delete_pet').showModal();
    }

    // Re-init lucide icons after page load
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>