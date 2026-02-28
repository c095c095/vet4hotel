<?php
// ═══════════════════════════════════════════════════════════
// ADMIN CREATE BOOKING UI — VET4 HOTEL
// ═══════════════════════════════════════════════════════════

require_once __DIR__ . '/../cores/dashboard_data.php';

// Fetch all active services to display in the form
$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

$min_date = date('Y-m-d');
?>

<div class="p-4 lg:p-8 space-y-6 max-w-5xl mx-auto">

    <div class="flex items-center gap-4 mb-6">
        <a href="?page=bookings" class="btn btn-ghost btn-sm btn-square">
            <i data-lucide="arrow-left" class="size-5"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
                <i data-lucide="calendar-plus" class="size-6 text-primary"></i>
                สร้างการจองใหม่
            </h1>
            <p class="text-sm text-base-content/60">ทำรายการจองห้องพักให้ลูกค้าผ่านระบบหลังบ้าน</p>
        </div>
    </div>

    <!-- MAIN FORM -->
    <form id="adminBookingForm" action="?action=booking_single" method="POST" class="space-y-6">

        <!-- STEP 1: Customer Selection -->
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-6">
                <h2 class="card-title text-lg border-b border-base-200 pb-2 mb-4">
                    <span
                        class="bg-primary text-primary-content w-6 h-6 rounded-full flex items-center justify-center text-sm">1</span>
                    เลือกลูกค้า
                </h2>

                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">ค้นหาลูกค้า (ชื่อ, อีเมล,
                            เบอร์โทร)</span></label>
                    <div class="relative flex gap-2">
                        <input type="text" id="customerSearch" class="input input-bordered w-full"
                            placeholder="พิมพ์เพื่อค้นหา..." autocomplete="off">
                        <button type="button" id="btnSearchCustomer" class="btn btn-primary btn-square"><i
                                data-lucide="search" class="size-5"></i></button>
                    </div>
                </div>

                <!-- Customer Search Results -->
                <div id="customerResults" class="mt-2 flex flex-col gap-2 max-h-60 overflow-y-auto hidden"></div>

                <!-- Selected Customer Display -->
                <div id="selectedCustomerInfo"
                    class="mt-4 p-4 bg-primary/10 border border-primary/20 rounded-xl flex items-center justify-between hidden">
                    <div class="flex items-center gap-3">
                        <div class="avatar placeholder">
                            <div
                                class="bg-primary text-primary-content w-10 rounded-full flex items-center justify-center">
                                <span class="text-lg" id="scInitials">C</span>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-primary" id="scName">Customer Name</div>
                            <div class="text-sm text-base-content/70" id="scDetails">080-000-0000 | email@example.com
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btnChangeCustomer"
                        class="btn btn-ghost btn-sm text-base-content/60">เปลี่ยน</button>
                    <input type="hidden" name="customer_id" id="inputCustomerId" required>
                </div>
            </div>
        </div>

        <!-- STEP 2: Dates & Pets -->
        <div id="step2Container"
            class="card bg-base-100 border border-base-200 shadow-sm opacity-50 pointer-events-none transition-opacity">
            <div class="card-body p-6">
                <h2 class="card-title text-lg border-b border-base-200 pb-2 mb-4">
                    <span
                        class="bg-primary text-primary-content w-6 h-6 rounded-full flex items-center justify-center text-sm">2</span>
                    วันเข้าพัก & สัตว์เลี้ยง
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">วันที่เช็คอิน</span></label>
                        <input type="date" id="checkInDate" name="check_in_date" class="input input-bordered w-full"
                            min="<?php echo $min_date; ?>" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">วันที่เช็คเอาท์</span></label>
                        <input type="date" id="checkOutDate" name="check_out_date" class="input input-bordered w-full"
                            min="<?php echo $min_date; ?>" required>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span
                            class="label-text font-medium">สัตว์เลี้ยงของลูกค้าที่ต้องการเข้าพัก</span></label>
                    <div id="petsContainer" class="flex flex-wrap gap-3">
                        <span class="text-sm text-base-content/50 italic py-2">กรุณาเลือกลูกค้าก่อน</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Room Selection -->
        <div id="step3Container"
            class="card bg-base-100 border border-base-200 shadow-sm opacity-50 pointer-events-none transition-opacity">
            <div class="card-body p-6">
                <div class="flex items-center justify-between border-b border-base-200 pb-2 mb-4">
                    <h2 class="card-title text-lg">
                        <span
                            class="bg-primary text-primary-content w-6 h-6 rounded-full flex items-center justify-center text-sm">3</span>
                        เลือกประเภทห้องพัก
                    </h2>
                    <button type="button" id="btnCheckRooms" class="btn btn-sm btn-outline btn-primary gap-1 hidden">
                        <i data-lucide="refresh-cw" class="size-3"></i> เช็คห้องว่าง
                    </button>
                </div>

                <div id="roomsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <span
                        class="text-sm text-base-content/50 italic py-2">กรุณาเลือกวันเข้าพักและสัตว์เลี้ยงให้ครบถ้วนก่อน</span>
                </div>
            </div>
        </div>

        <!-- STEP 4: Services & Note -->
        <div id="step4Container"
            class="card bg-base-100 border border-base-200 shadow-sm opacity-50 pointer-events-none transition-opacity">
            <div class="card-body p-6">
                <h2 class="card-title text-lg border-b border-base-200 pb-2 mb-4">
                    <span
                        class="bg-primary text-primary-content w-6 h-6 rounded-full flex items-center justify-center text-sm">4</span>
                    บริการเสริม & หมายเหตุ
                </h2>

                <div class="mb-6">
                    <label class="label"><span class="label-text font-medium">บริการรับ-ส่ง, อาบน้ำ, ฯลฯ
                            (ถ้ามี)</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($services as $sv): ?>
                            <label
                                class="cursor-pointer border border-base-200 rounded-lg p-3 flex items-start gap-3 hover:border-primary transition-colors">
                                <input type="checkbox" name="service_ids[]" value="<?php echo $sv['id']; ?>"
                                    class="checkbox checkbox-sm checkbox-primary mt-0.5">
                                <div>
                                    <div class="font-medium text-sm">


                                        <?php echo htmlspecialchars($sv['name']); ?> (+฿
                                        <?php echo number_format($sv['price']); ?>)
                                    </div>
                                    <div class="text-xs text-base-content/50">
                                        <?php echo htmlspecialchars($sv['charge_type']); ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-control mb-6">
                    <label class="label"><span class="label-text font-medium">รหัสโปรโมชัน (ถ้ามี)</span></label>
                    <div class="relative flex gap-2 w-full sm:w-1/2">
                        <input type="text" name="promotion_code" class="input input-bordered w-full uppercase"
                            placeholder="เช่น VET2026..." />
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">คำขอพิเศษ / หมายเหตุการจอง
                            (ถ้ามี)</span></label>
                    <textarea name="special_requests" class="textarea textarea-bordered h-24"
                        placeholder="เช่น รายละเอียดการให้อาหารเพิ่มเติม..."></textarea>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <a href="?page=bookings" class="btn btn-ghost">ยกเลิก</a>
                    <button type="submit" id="btnSubmitBooking" class="btn btn-primary px-8 gap-2">
                        <i data-lucide="save" class="size-4"></i> บันทึกการจอง
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
    // ADMIN BOOKING LOGIC
    document.addEventListener('DOMContentLoaded', () => {

        const API_CUSTOMERS = 'cores/api_customers.php';
        const API_PETS = 'cores/api_pets.php';
        const API_ROOMS = 'cores/api_rooms.php';

        // Elements
        const elSearchCust = document.getElementById('customerSearch');
        const btnSearchCust = document.getElementById('btnSearchCustomer');
        const elCustResults = document.getElementById('customerResults');
        const elSelectedCustInfo = document.getElementById('selectedCustomerInfo');
        const hiddenCid = document.getElementById('inputCustomerId');
        const btnChangeCust = document.getElementById('btnChangeCustomer');

        const secStep2 = document.getElementById('step2Container');
        const elCheckIn = document.getElementById('checkInDate');
        const elCheckOut = document.getElementById('checkOutDate');
        const elPets = document.getElementById('petsContainer');

        const secStep3 = document.getElementById('step3Container');
        const elRooms = document.getElementById('roomsContainer');
        const btnCheckRooms = document.getElementById('btnCheckRooms');

        const secStep4 = document.getElementById('step4Container');

        // 1. Customer Search
        const searchCustomer = async () => {
            const q = elSearchCust.value.trim();
            if (!q) return;

            btnSearchCust.innerHTML = '<span class="loading loading-spinner size-4"></span>';
            try {
                const res = await fetch(`${API_CUSTOMERS}?q=${encodeURIComponent(q)}`);
                const json = await res.json();

                elCustResults.innerHTML = '';
                elCustResults.classList.remove('hidden');

                if (json.success && json.data.length > 0) {
                    json.data.forEach(c => {
                        const div = document.createElement('div');
                        div.className = 'p-3 bg-base-200/50 hover:bg-base-200 rounded-lg cursor-pointer flex justify-between items-center transition-colors';
                        div.innerHTML = `
                        <div>
                            <div class="font-bold">${c.first_name} ${c.last_name}</div>
                            <div class="text-xs text-base-content/60">${c.phone} | ${c.email}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost text-primary">เลือก</button>
                    `;
                        div.onclick = () => selectCustomer(c);
                        elCustResults.appendChild(div);
                    });
                } else {
                    elCustResults.innerHTML = '<div class="p-3 text-sm text-base-content/50 text-center">ไม่พบข้อมูลลูกค้า</div>';
                }
            } catch (e) {
                console.error(e);
            }
            btnSearchCust.innerHTML = '<i data-lucide="search" class="size-5"></i>';
            lucide.createIcons();
        };

        btnSearchCust.onclick = searchCustomer;
        elSearchCust.addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); searchCustomer(); } });

        const selectCustomer = async (c) => {
            elCustResults.innerHTML = '';
            elCustResults.classList.add('hidden');
            elSearchCust.parentElement.parentElement.classList.add('hidden'); // Hide search input

            document.getElementById('scInitials').innerText = c.first_name.charAt(0);
            document.getElementById('scName').innerText = `${c.first_name} ${c.last_name}`;
            document.getElementById('scDetails').innerText = `${c.phone} | ${c.email}`;

            elSelectedCustInfo.classList.remove('hidden');
            hiddenCid.value = c.id;

            // Unlock Step 2
            secStep2.classList.remove('opacity-50', 'pointer-events-none');

            // Fetch Pets
            fetchPets(c.id);
        };

        btnChangeCust.onclick = () => {
            hiddenCid.value = '';
            elSelectedCustInfo.classList.add('hidden');
            elSearchCust.parentElement.parentElement.classList.remove('hidden');
            elSearchCust.value = '';
            secStep2.classList.add('opacity-50', 'pointer-events-none');
            secStep3.classList.add('opacity-50', 'pointer-events-none');
            secStep4.classList.add('opacity-50', 'pointer-events-none');
            elPets.innerHTML = '<span class="text-sm text-base-content/50">กรุณาเลือกลูกค้าก่อน</span>';
        };

        // 2. Fetch Pets
        const fetchPets = async (cid) => {
            elPets.innerHTML = '<span class="loading loading-spinner text-primary"></span>';
            try {
                const res = await fetch(`${API_PETS}?customer_id=${cid}`);
                const json = await res.json();

                if (json.success && json.data.length > 0) {
                    elPets.innerHTML = '';
                    json.data.forEach(p => {
                        const badge = p.is_aggressive == 1 ? '<span class="badge badge-xs badge-error ml-1">ดุร้าย</span>' : '';
                        const lbl = document.createElement('label');
                        lbl.className = 'cursor-pointer border border-base-200 rounded-lg p-3 flex items-center gap-3 hover:border-primary w-full sm:w-auto bg-base-100';
                        lbl.innerHTML = `
                        <input type="checkbox" name="pet_ids[]" value="${p.id}" class="checkbox checkbox-primary checkbox-sm pet-checkbox">
                        <div>
                            <div class="font-bold text-sm text-base-content">${p.name} ${badge}</div>
                            <div class="text-[10px] text-base-content/60">${p.species} ${p.breed ? `(${p.breed})` : ''}</div>
                        </div>
                    `;
                        elPets.appendChild(lbl);
                    });

                    // Add listeners to lock/unlock Step 3
                    document.querySelectorAll('.pet-checkbox').forEach(cb => {
                        cb.addEventListener('change', checkStep2Completion);
                    });
                } else {
                    elPets.innerHTML = `
                    <div class="alert alert-warning text-sm py-2">
                        <i data-lucide="alert-triangle" class="size-4"></i>
                        <span>ลูกค้าท่านนี้ยังไม่มีข้อมูลสัตว์เลี้ยง ไม่สามารถจองได้</span>
                    </div>
                `;
                }
                lucide.createIcons();
            } catch (e) {
                console.error(e);
            }
        };

        // 3. Dates & Pets Logic
        const checkStep2Completion = () => {
            const cin = elCheckIn.value;
            const cout = elCheckOut.value;
            const hasPet = document.querySelector('.pet-checkbox:checked');

            if (cin && cout && cin < cout) {
                elCheckOut.classList.remove('border-error');
            } else if (cin && cout && cin >= cout) {
                elCheckOut.classList.add('border-error');
            }

            if (cin && cout && cin < cout && hasPet) {
                secStep3.classList.remove('opacity-50', 'pointer-events-none');
                btnCheckRooms.classList.remove('hidden');
                fetchRooms(); // Auto fetch rooms
            } else {
                secStep3.classList.add('opacity-50', 'pointer-events-none');
                secStep4.classList.add('opacity-50', 'pointer-events-none');
                btnCheckRooms.classList.add('hidden');
            }
        };

        elCheckIn.addEventListener('change', () => {
            if (elCheckIn.value) {
                let d = new Date(elCheckIn.value);
                d.setDate(d.getDate() + 1);
                let nextDay = d.toISOString().split('T')[0];
                elCheckOut.min = nextDay;
                if (elCheckOut.value && elCheckOut.value <= elCheckIn.value) {
                    elCheckOut.value = nextDay;
                }
            }
            checkStep2Completion();
        });
        elCheckOut.addEventListener('change', checkStep2Completion);

        // 4. Fetch Rooms
        const fetchRooms = async () => {
            const cin = elCheckIn.value;
            const cout = elCheckOut.value;
            if (!cin || !cout) return;

            // Count selected pets to filter Max Pets
            const selectedPetsCount = document.querySelectorAll('.pet-checkbox:checked').length;

            elRooms.innerHTML = '<div class="col-span-full text-center py-4"><span class="loading loading-spinner text-primary"></span> กำลังดึงข้อมูลห้องว่าง...</div>';

            try {
                const res = await fetch(`${API_ROOMS}?check_in=${cin}&check_out=${cout}`);
                const json = await res.json();

                elRooms.innerHTML = '';
                let validRoomsFound = false;

                if (json.success && json.data.length > 0) {
                    json.data.forEach(r => {
                        // Filter by max pets if desired (for Admin, maybe we allow override, but let's strictly enforce GUI)
                        if (r.max_pets < selectedPetsCount) return;

                        validRoomsFound = true;
                        const lbl = document.createElement('label');
                        lbl.className = 'cursor-pointer relative block';
                        lbl.innerHTML = `
                        <input type="radio" name="room_type_id" value="${r.id}" class="peer absolute opacity-0 w-0 h-0" required>
                        <div class="card bg-base-100 border-2 border-base-200 transition-all hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/5">
                            <div class="card-body p-4">
                                <div class="flex justify-between items-start">
                                    <div class="font-bold text-base-content peer-checked:text-primary">${r.name}</div>
                                    <div class="w-4 h-4 rounded-full border border-base-300 flex justify-center items-center mt-0.5 peer-checked:border-primary">
                                        <div class="w-2 h-2 rounded-full bg-primary opacity-0 peer-checked:opacity-100"></div>
                                    </div>
                                </div>
                                <div class="flex gap-2 text-xs text-base-content/60 mt-1 mb-2">
                                    <span class="bg-base-200 px-1.5 py-0.5 rounded">สูงสุด ${r.max_pets} ตัว</span>
                                    <span class="bg-success/10 text-success px-1.5 py-0.5 rounded">ว่าง ${r.available_rooms} ห้อง</span>
                                </div>
                                <div class="font-bold text-primary text-lg mt-auto">
                                    ฿${parseInt(r.base_price_per_night).toLocaleString()} <span class="text-xs font-normal text-base-content/50">/คืน</span>
                                </div>
                            </div>
                        </div>
                    `;
                        elRooms.appendChild(lbl);

                        // Add listener to unlock step 4
                        lbl.querySelector('input').addEventListener('change', () => {
                            secStep4.classList.remove('opacity-50', 'pointer-events-none');
                        });
                    });
                }

                if (!validRoomsFound) {
                    elRooms.innerHTML = `
                    <div class="col-span-full alert alert-error bg-error/10 text-error text-sm">
                        <i data-lucide="alert-circle" class="size-4"></i>
                        <span>ไม่มีห้องพักที่รองรับสัตว์เลี้ยงจำนวน ${selectedPetsCount} ตัว ในช่วงวันที่ท่านเลือก</span>
                    </div>
                `;
                }

            } catch (e) {
                console.error(e);
                elRooms.innerHTML = '<div class="col-span-full text-error text-sm">ข้อผิดพลาดในการโหลดข้อมูลห้องพัก</div>';
            }
        };

        btnCheckRooms.onclick = fetchRooms;

    });
</script>