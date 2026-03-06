<?php
// ═══════════════════════════════════════════════════════════
// SETUP PROCESSOR — VET4 HOTEL ADMIN
// Handles first-time system initialization
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=setup");
    exit();
}

// Check if admin already exists
$stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE role='admin'");
if ($stmt->fetchColumn() > 0) {
    $_SESSION['error_msg'] = "ระบบได้รับการตั้งค่าแล้ว ไม่สามารถตั้งค่าซ้ำได้";
    header("Location: ?page=login");
    exit();
}

// Get form data
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$db_host = trim($_POST['db_host'] ?? '');
$db_name = trim($_POST['db_name'] ?? '');
$db_user = trim($_POST['db_user'] ?? '');
$db_pass = $_POST['db_pass'] ?? '';
$init_data = isset($_POST['init_data']);
$mock_users = isset($_POST['mock_users']);

// Validation
$errors = [];
if (empty($first_name))
    $errors[] = "กรุณากรอกชื่อ";
if (empty($last_name))
    $errors[] = "กรุณากรอกนามสกุล";
if (empty($email)) {
    $errors[] = "กรุณากรอกอีเมล";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
}
if (empty($password)) {
    $errors[] = "กรุณากรอกรหัสผ่าน";
} elseif (strlen($password) < 8) {
    $errors[] = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
}
if ($password !== $confirm_password)
    $errors[] = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
if (empty($db_host))
    $errors[] = "กรุณากรอกโฮสต์ฐานข้อมูล";
if (empty($db_name))
    $errors[] = "กรุณากรอกชื่อฐานข้อมูล";
if (empty($db_user))
    $errors[] = "กรุณากรอกชื่อผู้ใช้ฐานข้อมูล";

if (!empty($errors)) {
    $_SESSION['error_msg'] = implode("<br>", $errors);
    header("Location: ?page=setup");
    exit();
}

try {
    $test_pdo = new PDO("mysql:host=" . $db_host . ";dbname=" . $db_name, $db_user, $db_pass);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $test_pdo->exec("set names utf8mb4");

    // Update config file
    $config_content = "<?php\n\n";
    $config_content .= "define('SITE_NAME', 'VET 4 HOTEL');\n";
    $config_content .= "define('SITE_URL', 'http://localhost/vet4');\n\n";
    $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
    $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
    $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
    $config_content .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n";

    $config_file = __DIR__ . '/../../cores/config.php';
    if (!file_put_contents($config_file, $config_content)) {
        throw new Exception("ไม่สามารถเขียนไฟล์การตั้งค่าได้ กรุณาตรวจสอบสิทธิ์การเขียนไฟล์");
    }

    // Create admin user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $test_pdo->prepare("INSERT INTO employees (email, password_hash, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
    $stmt->execute([$email, $password_hash, $first_name, $last_name]);

    if ($init_data) {
        initBasicData($test_pdo);
    }

    if ($mock_users && $init_data) {
        initMockUsers($test_pdo);
    }

    $_SESSION['success_msg'] = "ตั้งค่าระบบสำเร็จแล้ว! กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบที่สร้างไว้";
    header("Location: ?page=login");
    exit();

} catch (PDOException $e) {
    $_SESSION['error_msg'] = "ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage();
    header("Location: ?page=setup");
    exit();
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
    header("Location: ?page=setup");
    exit();
}

// ═══════════════════════════════════════════════════════════
// FUNCTION: initBasicData — ข้อมูลพื้นฐานทั้งหมด
// ═══════════════════════════════════════════════════════════
function initBasicData($pdo)
{

    // ─── Species ───
    $pdo->exec("INSERT IGNORE INTO species (name) VALUES ('แมว'), ('สุนัข')");

    // ─── Breeds ───
    $cat_breeds = ['เปอร์เซีย', 'สก็อตติช โฟลด์', 'บริติช ชอร์ตแฮร์', 'อเมริกัน ชอร์ตแฮร์', 'เมนคูน', 'รากดอล', 'เบงกอล', 'สยาม', 'วิเชียรมาศ', 'เอ็กโซติก ชอร์ตแฮร์', 'รัสเซียน บลู', 'พันธุ์ผสม/ไม่ทราบสายพันธุ์'];
    $dog_breeds = ['โกลเด้น รีทรีฟเวอร์', 'ลาบราดอร์ รีทรีฟเวอร์', 'พุดเดิ้ล', 'ชิวาวา', 'ปอมเมอเรเนียน', 'ไซบีเรียน ฮัสกี้', 'บีเกิ้ล', 'ไทยหลังอาน', 'บางแก้ว', 'ร็อตไวเลอร์', 'เยอรมันเชพเพิร์ด', 'ชิห์สุ', 'คอร์กี้', 'บูลด็อก ฝรั่งเศส', 'ดัชชุน', 'มัลทีส', 'พันธุ์ผสม/ไม่ทราบสายพันธุ์'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO breeds (species_id, name) VALUES (?, ?)");
    foreach ($cat_breeds as $b)
        $stmt->execute([1, $b]);
    foreach ($dog_breeds as $b)
        $stmt->execute([2, $b]);

    // ─── Vaccine Types ───
    $vaccines = [
        [1, 'วัคซีนรวม 3 โรค (FVRCP)'],
        [1, 'วัคซีนพิษสุนัขบ้า (แมว)'],
        [1, 'วัคซีนลูคีเมีย (FeLV)'],
        [1, 'วัคซีน FIP'],
        [2, 'วัคซีนรวม 5 โรค (DHPPi)'],
        [2, 'วัคซีนพิษสุนัขบ้า'],
        [2, 'วัคซีนลำไส้อักเสบ (Corona)'],
        [2, 'วัคซีนไข้หัดสุนัข'],
        [2, 'วัคซีนเคนเนลคัฟ (Bordetella)']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO vaccine_types (species_id, name) VALUES (?, ?)");
    foreach ($vaccines as $v)
        $stmt->execute($v);

    // ─── Medical Record Types ───
    $stmt = $pdo->prepare("INSERT IGNORE INTO medical_record_types (name) VALUES (?)");
    foreach (['โรคประจำตัว', 'อาการแพ้', 'ยาที่กินอยู่ประจำ', 'ประวัติการผ่าตัด', 'อาหารพิเศษ', 'ข้อจำกัดด้านสุขภาพ'] as $m)
        $stmt->execute([$m]);

    // ─── Daily Update Types ───
    $update_types = [['อาหาร', 'utensils'], ['เข้าห้องน้ำ', 'droplet'], ['เล่น/กิจกรรม', 'gamepad-2'], ['รูปถ่าย', 'camera'], ['ปัญหาสุขภาพ', 'alert-triangle'], ['พฤติกรรม', 'smile'], ['การนอนหลับ', 'moon'], ['อาบน้ำ/ทำความสะอาด', 'bath']];
    $stmt = $pdo->prepare("INSERT IGNORE INTO daily_update_types (name, icon_class) VALUES (?, ?)");
    foreach ($update_types as $u)
        $stmt->execute($u);

    // ─── Care Task Types ───
    $stmt = $pdo->prepare("INSERT IGNORE INTO care_task_types (name) VALUES (?)");
    foreach (['ป้อนยา', 'ให้อาหาร', 'พาเที่ยวเล่น', 'ทำความสะอาด', 'ตรวจสุขภาพ', 'ดูแลพิเศษ', 'อาบน้ำ'] as $c)
        $stmt->execute([$c]);

    // ─── Room Types (Standard / Deluxe / VIP) ───
    $room_types = [
        ['ห้องมาตรฐาน (Standard)', 'ห้องพักขนาดสบายสำหรับสัตว์เลี้ยง 1-2 ตัว ติดเครื่องปรับอากาศ พร้อมที่นอนนุ่มพิเศษ ห้องน้ำส่วนตัว และของเล่นมาตรฐาน เหมาะสำหรับทุกสายพันธุ์', 500.00, 2, 4.00],
        ['ห้องดีลักซ์ (Deluxe)', 'ห้องพักขนาดใหญ่พิเศษ รองรับสัตว์เลี้ยง 1-3 ตัว มีมุมเล่นส่วนตัว วิวสวน ระบบเครื่องฟอกอากาศ กล้องวงจรปิดให้เจ้าของดูแบบ Real-time เหมาะสำหรับครอบครัวหลายตัว', 800.00, 3, 8.00],
        ['ห้องวีไอพี (VIP Suite)', 'ห้องพักหรูหราระดับสูงสุดพร้อมสิ่งอำนวยความสะดวกครบครัน กล้อง CCTV ส่วนตัว 24 ชม. เครื่องฟอกอากาศ มุมเล่นขนาดใหญ่ ที่นอน Premium รองรับสัตว์เลี้ยงได้สูงสุด 4 ตัว', 1200.00, 4, 14.00]
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO room_types (name, description, base_price_per_night, max_pets, size_sqm) VALUES (?, ?, ?, ?, ?)");
    foreach ($room_types as $r)
        $stmt->execute($r);

    // ─── Amenities ───
    $amenities = [['เครื่องปรับอากาศ', 'air-vent'], ['กล้องวงจรปิด 24 ชม.', 'video'], ['พัดลมระบายอากาศ', 'fan'], ['ที่นอนนุ่มพิเศษ', 'bed-double'], ['ของเล่นสัตว์เลี้ยง', 'gamepad-2'], ['ห้องน้ำส่วนตัว', 'bath'], ['วิวสวน', 'trees'], ['เครื่องฟอกอากาศ', 'wind'], ['มุมเล่นส่วนตัว', 'fence']];
    $stmt = $pdo->prepare("INSERT IGNORE INTO amenities (name, icon_class) VALUES (?, ?)");
    foreach ($amenities as $a)
        $stmt->execute($a);

    // ─── Room Type ↔ Amenities ───
    $mapping = [[1, 1], [1, 3], [1, 4], [1, 5], [1, 6], [2, 1], [2, 2], [2, 3], [2, 4], [2, 5], [2, 6], [2, 7], [2, 8], [3, 1], [3, 2], [3, 3], [3, 4], [3, 5], [3, 6], [3, 7], [3, 8], [3, 9]];
    $stmt = $pdo->prepare("INSERT IGNORE INTO room_type_amenities (room_type_id, amenity_id) VALUES (?, ?)");
    foreach ($mapping as $m)
        $stmt->execute($m);

    // ─── Room Type Images ───
    $images = [[1, 'assets/images/rooms/DEFAULT_ROOM_STANDARD_1.jpg', 1], [1, 'assets/images/rooms/DEFAULT_ROOM_STANDARD_2.jpg', 0], [2, 'assets/images/rooms/DEFAULT_ROOM_DELUXE_1.jpg', 1], [2, 'assets/images/rooms/DEFAULT_ROOM_DELUXE_2.jpg', 0], [2, 'assets/images/rooms/DEFAULT_ROOM_DELUXE_3.jpg', 0], [3, 'assets/images/rooms/DEFAULT_ROOM_VIP_1.jpg', 1], [3, 'assets/images/rooms/DEFAULT_ROOM_VIP_2.jpg', 0], [3, 'assets/images/rooms/DEFAULT_ROOM_VIP_3.jpg', 0]];
    $stmt = $pdo->prepare("INSERT IGNORE INTO room_type_images (room_type_id, image_url, is_primary) VALUES (?, ?, ?)");
    foreach ($images as $img)
        $stmt->execute($img);

    // ─── Physical Rooms ───
    $rooms = [
        [1, 'S101', '1', 'active', null],
        [1, 'S102', '1', 'active', null],
        [1, 'S103', '1', 'active', null],
        [1, 'S201', '2', 'active', null],
        [1, 'S202', '2', 'active', null],
        [1, 'S203', '2', 'active', null],
        [2, 'D101', '1', 'active', null],
        [2, 'D102', '1', 'active', null],
        [2, 'D201', '2', 'active', null],
        [2, 'D202', '2', 'active', null],
        [3, 'V301', '3', 'active', 'https://cctv.vet4hotel.local/v301'],
        [3, 'V302', '3', 'active', 'https://cctv.vet4hotel.local/v302'],
        [3, 'V303', '3', 'active', 'https://cctv.vet4hotel.local/v303'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO rooms (room_type_id, room_number, floor_level, status, cctv_url) VALUES (?, ?, ?, ?, ?)");
    foreach ($rooms as $r)
        $stmt->execute($r);

    // ─── Services ───
    $services = [
        ['อาบน้ำและตัดขน', 'บริการอาบน้ำ เป่าขน ตัดแต่งขนรอบตัว ทำความสะอาดหู ตัดเล็บ โดยช่างผู้เชี่ยวชาญ', 350.00, 'per_pet'],
        ['ตัดเล็บ', 'บริการตัดเล็บและตะไบเล็บอย่างปลอดภัย', 100.00, 'per_pet'],
        ['สปาผ่อนคลาย', 'บริการนวดผ่อนคลายกล้ามเนื้อ อาบน้ำสมุนไพร บำรุงขนเป็นพิเศษ', 250.00, 'per_pet'],
        ['พาเที่ยวเล่นเพิ่มเติม', 'พาสัตว์เลี้ยงออกกำลังกายนอกห้องเพิ่มวันละ 30 นาที ในสวนปิดปลอดภัย', 150.00, 'per_night'],
        ['อาหารพรีเมียม', 'ให้อาหารระดับ Premium ตามสายพันธุ์ ทดแทนอาหารปกติ', 120.00, 'per_night'],
        ['อัพเดตรูปภาพ VIP', 'ส่งรูปภาพและวิดีโออัพเดตสถานะสัตว์เลี้ยงตลอดทั้งวัน อย่างน้อย 5 รูป/วัน', 80.00, 'per_stay']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO services (name, description, price, charge_type) VALUES (?, ?, ?, ?)");
    foreach ($services as $s)
        $stmt->execute($s);

    // ─── Payment Channels ───
    $channels = [
        ['qr_promptpay', 'พร้อมเพย์ QR Code', null, 'บจก. เว็ทโฟร์ โฮเทล', '0812345678', 'qr-code', 0.00],
        ['bank_transfer', 'ธนาคารกสิกรไทย', 'ธนาคารกสิกรไทย', 'บจก. เว็ทโฟร์ โฮเทล', '123-4-56789-0', 'landmark', 0.00],
        ['bank_transfer', 'ธนาคารไทยพาณิชย์', 'ธนาคารไทยพาณิชย์', 'บจก. เว็ทโฟร์ โฮเทล', '987-6-54321-0', 'landmark', 0.00],
        ['cash', 'ชำระเงินสดหน้าเคาน์เตอร์', null, null, null, 'banknote', 0.00]
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO payment_channels (type, name, bank_name, account_name, account_number, icon_class, fee_percent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($channels as $p)
        $stmt->execute($p);

    // ─── Seasonal Pricings ───
    $seasonals = [
        ['สงกรานต์ 2026', '2026-04-10', '2026-04-17', 20.00],
        ['ปีใหม่ 2027', '2026-12-28', '2027-01-03', 25.00],
        ['วันหยุดยาวตุลาคม 2026', '2026-10-22', '2026-10-26', 15.00],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO seasonal_pricings (season_name, start_date, end_date, price_multiplier_percent) VALUES (?, ?, ?, ?)");
    foreach ($seasonals as $sp)
        $stmt->execute($sp);

    // ─── Promotions ───
    $promos = [
        ['WELCOME10', 'สมาชิกใหม่ลด 10%', 'percentage', 10.00, 500.00, 1000.00, 100, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59'],
        ['VET500', 'ลดทันที 500 บาท', 'fixed_amount', 500.00, null, 2000.00, 50, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO promotions (code, title, discount_type, discount_value, max_discount_amount, min_booking_amount, usage_limit, used_count, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($promos as $p)
        $stmt->execute($p);

    // ─── Staff Employees ───
    $staff_pwd = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO employees (email, password_hash, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, 'staff', 1)");
    $stmt->execute(['staff@vet4hotel.com', $staff_pwd, 'สมหญิง', 'ดูแลดี']);
    $stmt->execute(['staff2@vet4hotel.com', $staff_pwd, 'สมชาย', 'รักสัตว์']);
}

// ═══════════════════════════════════════════════════════════
// FUNCTION: initMockUsers — ข้อมูลจำลองครบทุก case
// ═══════════════════════════════════════════════════════════
function initMockUsers($pdo)
{
    $pwd = password_hash('password', PASSWORD_DEFAULT);

    // Get staff ID
    $staff_id = (int) $pdo->query("SELECT id FROM employees WHERE role='staff' ORDER BY id LIMIT 1")->fetchColumn();
    if (!$staff_id)
        $staff_id = (int) $pdo->query("SELECT id FROM employees ORDER BY id LIMIT 1")->fetchColumn();

    // ═══ CUSTOMER 1: คุณวิภา (แมว 2 + สุนัข 1) ═══
    $pdo->prepare("INSERT INTO customers (email, password_hash, first_name, last_name, phone, address, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute(['wipa@example.com', $pwd, 'วิภา', 'รักสัตว์มาก', '0891234567', '123/45 ซ.สุขุมวิท 24 คลองตัน คลองเตย กรุงเทพฯ 10110', 'สมศักดิ์ รักสัตว์มาก', '0899876543']);
    $c1 = (int) $pdo->lastInsertId();

    $pet_stmt = $pdo->prepare("INSERT INTO pets (customer_id, species_id, breed_id, name, dob, weight_kg, gender, vet_name, vet_phone, is_aggressive, behavior_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $pet_stmt->execute([$c1, 1, 1, 'มิลค์', '2023-03-15', 4.50, 'spayed', 'คลินิกรักแมว', '0212345678', 0, 'ขี้อาย ใช้เวลาปรับตัว 1-2 ชม.']);
    $pet_milk = (int) $pdo->lastInsertId();
    $pet_stmt->execute([$c1, 1, 3, 'โมจิ', '2024-01-10', 3.80, 'female', 'คลินิกรักแมว', '0212345678', 0, 'ร่าเริง ชอบเล่นลูกบอล']);
    $pet_moji = (int) $pdo->lastInsertId();

    $dog_breed = (int) $pdo->query("SELECT id FROM breeds WHERE species_id=2 AND name LIKE '%โกลเด้น%' LIMIT 1")->fetchColumn() ?: null;
    $pet_stmt->execute([$c1, 2, $dog_breed, 'ข้าวจ้าว', '2022-06-20', 28.00, 'neutered', 'รพ.สัตว์รวมใจ', '0298765432', 0, 'เป็นมิตร ชอบเล่นน้ำ']);
    $pet_kaojao = (int) $pdo->lastInsertId();

    // ═══ CUSTOMER 2: คุณธนา (สุนัข 2) ═══
    $pdo->prepare("INSERT INTO customers (email, password_hash, first_name, last_name, phone, address, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute(['thana@example.com', $pwd, 'ธนา', 'ใจกว้าง', '0867654321', '789/10 หมู่บ้านเพอร์เฟค ถ.ราชพฤกษ์ ปากเกร็ด นนทบุรี 11120', 'พิมพ์ชนก ใจกว้าง', '0876543210']);
    $c2 = (int) $pdo->lastInsertId();

    $pom_breed = (int) $pdo->query("SELECT id FROM breeds WHERE species_id=2 AND name LIKE '%ปอม%' LIMIT 1")->fetchColumn() ?: null;
    $pet_stmt->execute([$c2, 2, $pom_breed, 'ลัคกี้', '2024-05-01', 3.20, 'male', 'คลินิกหมอสัตว์', '0234567890', 0, 'ขี้เล่น ชอบเห่าเรียกร้องความสนใจ']);
    $pet_lucky = (int) $pdo->lastInsertId();

    $rott_breed = (int) $pdo->query("SELECT id FROM breeds WHERE species_id=2 AND name LIKE '%ร็อต%' LIMIT 1")->fetchColumn() ?: null;
    $pet_stmt->execute([$c2, 2, $rott_breed, 'แม็กซ์', '2021-11-22', 42.00, 'male', 'รพ.สัตว์รวมใจ', '0298765432', 1, '⚠️ ดุกับคนแปลกหน้า ต้องระวังเป็นพิเศษ ห้ามจับตัวกะทันหัน']);
    $pet_max = (int) $pdo->lastInsertId();

    // ─── Vaccinations ───
    $vac_stmt = $pdo->prepare("INSERT INTO pet_vaccinations (pet_id, vaccine_type_id, administered_date, expiry_date, is_verified) VALUES (?, ?, ?, ?, ?)");
    $vac_stmt->execute([$pet_milk, 1, '2025-06-15', '2026-06-15', 1]);
    $vac_stmt->execute([$pet_milk, 2, '2025-06-15', '2026-06-15', 1]);
    $vac_stmt->execute([$pet_moji, 1, '2025-08-20', '2026-08-20', 1]);
    $vac_stmt->execute([$pet_moji, 2, '2025-08-20', '2026-08-20', 1]);
    $vac_stmt->execute([$pet_kaojao, 5, '2025-07-10', '2026-07-10', 1]);
    $vac_stmt->execute([$pet_kaojao, 6, '2025-07-10', '2026-07-10', 1]);
    $vac_stmt->execute([$pet_lucky, 5, '2025-09-01', '2026-09-01', 1]);
    $vac_stmt->execute([$pet_lucky, 6, '2025-09-01', '2026-09-01', 1]);
    $vac_stmt->execute([$pet_max, 5, '2025-10-05', '2026-10-05', 1]);
    $vac_stmt->execute([$pet_max, 6, '2025-10-05', '2026-10-05', 1]);

    // ─── Medical Records ───
    $mr_stmt = $pdo->prepare("INSERT INTO pet_medical_records (pet_id, record_type_id, description) VALUES (?, ?, ?)");
    $mr_stmt->execute([$pet_milk, 2, 'แพ้อาหารเม็ดยี่ห้อ X ทำให้ท้องเสีย']);
    $mr_stmt->execute([$pet_kaojao, 1, 'โรคผิวหนังอักเสบบริเวณท้อง ต้องทายาสม่ำเสมอ']);
    $mr_stmt->execute([$pet_kaojao, 3, 'ยาแก้แพ้ Apoquel 5.4mg วันละ 1 เม็ดหลังอาหารเช้า']);
    $mr_stmt->execute([$pet_max, 5, 'อาหารพิเศษสำหรับสุนัขพันธุ์ใหญ่ โปรตีนสูง']);

    // ─── Get Room IDs ───
    $room_id = function ($num) use ($pdo) {
        return (int) $pdo->query("SELECT id FROM rooms WHERE room_number='$num'")->fetchColumn();
    };

    // ─── Prepared Statements ───
    $bk = $pdo->prepare("INSERT INTO bookings (booking_ref, customer_id, subtotal_amount, promotion_id, discount_amount, net_amount, status, special_requests, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $bi = $pdo->prepare("INSERT INTO booking_items (booking_id, room_id, check_in_date, check_out_date, locked_unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $bp = $pdo->prepare("INSERT INTO booking_item_pets (booking_item_id, pet_id) VALUES (?, ?)");
    $bs = $pdo->prepare("INSERT INTO booking_services (booking_id, booking_item_id, service_id, quantity, locked_unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    $py = $pdo->prepare("INSERT INTO payments (booking_id, payment_channel_id, payment_type, amount, proof_image_url, status, verified_by_employee_id, paid_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $du = $pdo->prepare("INSERT INTO daily_updates (booking_item_id, pet_id, employee_id, update_type_id, message, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $ct = $pdo->prepare("INSERT INTO daily_care_tasks (booking_item_id, pet_id, task_date, task_type_id, description, status, completed_at, completed_by_employee_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    // ════════════════════════════════════════════════════════
    // BOOKING 1 (C1): checked_out — แมว 2 ตัว ห้อง Deluxe
    // ════════════════════════════════════════════════════════
    $bk->execute(['BK-20260201-001', $c1, 3200.00, null, 0, 3200.00, 'checked_out', 'มิลค์ขี้อายหน่อย ขอให้ค่อยๆ ปรับตัวนะคะ', '2026-01-28 10:30:00']);
    $b1 = (int) $pdo->lastInsertId();
    $bi->execute([$b1, $room_id('D101'), '2026-02-01', '2026-02-05', 800.00, 3200.00]);
    $bi1 = (int) $pdo->lastInsertId();
    $bp->execute([$bi1, $pet_milk]);
    $bp->execute([$bi1, $pet_moji]);
    $bs->execute([$b1, $bi1, 1, 2, 350.00, 700.00]);
    $py->execute([$b1, 1, 'full_payment', 3200.00, 'assets/images/payments/DEFAULT_PAYMENT_PROOF_1.jpg', 'verified', $staff_id, '2026-01-28 11:00:00', '2026-01-28 11:00:00']);

    // Review
    $pdo->prepare("INSERT INTO reviews (booking_id, customer_id, rating, comment, staff_reply, staff_reply_at, is_published) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$b1, $c1, 5, 'บริการดีมากค่ะ พนักงานดูแลน้องแมวอย่างดี มิลค์ที่ปกติขี้อายกลับมาบ้านนิสัยดีขึ้นเลย!', 'ขอบคุณมากค่ะ ยินดีต้อนรับน้องมิลค์และน้องโมจิเสมอนะคะ', '2026-02-06 09:00:00', 1]);

    // Daily updates + care tasks
    $du->execute([$bi1, $pet_milk, $staff_id, 1, 'น้องมิลค์กินอาหารเช้าหมดจานค่ะ เริ่มปรับตัวได้ดี', '2026-02-01 08:30:00']);
    $du->execute([$bi1, $pet_moji, $staff_id, 3, 'น้องโมจิเล่นลูกบอลสนุกมาก วิ่งเล่นในมุมเล่น', '2026-02-01 14:00:00']);
    $du->execute([$bi1, $pet_milk, $staff_id, 7, 'น้องมิลค์นอนหลับสบาย ไม่มีอาการผิดปกติ', '2026-02-01 21:00:00']);
    $ct->execute([$bi1, $pet_milk, '2026-02-01', 2, 'ให้อาหารเช้า (Royal Canin)', 'completed', '2026-02-01 08:00:00', $staff_id]);
    $ct->execute([$bi1, $pet_milk, '2026-02-01', 2, 'ให้อาหารเย็น', 'completed', '2026-02-01 17:00:00', $staff_id]);
    $ct->execute([$bi1, $pet_moji, '2026-02-01', 2, 'ให้อาหารเช้า', 'completed', '2026-02-01 08:00:00', $staff_id]);
    $ct->execute([$bi1, $pet_moji, '2026-02-01', 3, 'พาเล่นมุมเล่นส่วนตัว 30 นาที', 'completed', '2026-02-01 14:00:00', $staff_id]);

    // ════════════════════════════════════════════════════════
    // BOOKING 2 (C1): checked_in — สุนัข 1 ตัว ห้อง Standard (ปัจจุบัน)
    // ════════════════════════════════════════════════════════
    $bk->execute(['BK-20260305-001', $c1, 2500.00, null, 0, 2500.00, 'checked_in', 'ข้าวจ้าวต้องกินยาแก้แพ้ทุกเช้าค่ะ', '2026-03-04 14:00:00']);
    $b2 = (int) $pdo->lastInsertId();
    $bi->execute([$b2, $room_id('S101'), '2026-03-05', '2026-03-10', 500.00, 2500.00]);
    $bi2 = (int) $pdo->lastInsertId();
    $bp->execute([$bi2, $pet_kaojao]);
    $bs->execute([$b2, $bi2, 4, 5, 150.00, 750.00]);
    $py->execute([$b2, 2, 'full_payment', 2500.00, 'assets/images/payments/DEFAULT_PAYMENT_PROOF_2.jpg', 'verified', $staff_id, '2026-03-04 14:30:00', '2026-03-04 14:30:00']);

    $ct->execute([$bi2, $pet_kaojao, '2026-03-05', 1, 'ป้อนยาแก้แพ้ Apoquel 5.4mg หลังอาหารเช้า', 'completed', '2026-03-05 08:15:00', $staff_id]);
    $ct->execute([$bi2, $pet_kaojao, '2026-03-05', 2, 'ให้อาหารเช้า', 'completed', '2026-03-05 08:00:00', $staff_id]);
    $ct->execute([$bi2, $pet_kaojao, '2026-03-06', 1, 'ป้อนยาแก้แพ้ Apoquel 5.4mg หลังอาหารเช้า', 'completed', '2026-03-06 08:10:00', $staff_id]);
    $ct->execute([$bi2, $pet_kaojao, '2026-03-06', 2, 'ให้อาหารเช้า', 'completed', '2026-03-06 07:50:00', $staff_id]);
    $ct->execute([$bi2, $pet_kaojao, '2026-03-07', 1, 'ป้อนยาแก้แพ้ Apoquel 5.4mg หลังอาหารเช้า', 'pending', null, null]);
    $ct->execute([$bi2, $pet_kaojao, '2026-03-07', 2, 'ให้อาหารเช้า', 'pending', null, null]);
    $ct->execute([$bi2, $pet_kaojao, '2026-03-07', 2, 'ให้อาหารเย็น', 'pending', null, null]);

    $du->execute([$bi2, $pet_kaojao, $staff_id, 1, 'น้องข้าวจ้าวกินอาหารเช้าเรียบร้อย กินยาแก้แพ้แล้วค่ะ', '2026-03-05 08:30:00']);
    $du->execute([$bi2, $pet_kaojao, $staff_id, 3, 'ข้าวจ้าวเดินเล่นในสวนสนุกมาก วิ่งเล่นน้ำจากสปริงเกอร์', '2026-03-05 10:30:00']);
    $du->execute([$bi2, $pet_kaojao, $staff_id, 6, 'ข้าวจ้าววันนี้อารมณ์ดี หางกระดิกตลอด ไม่มีอาการแพ้', '2026-03-06 16:00:00']);

    // ════════════════════════════════════════════════════════
    // BOOKING 3 (C1): confirmed — แมว 2 ตัว ห้อง Deluxe (อนาคต + ใช้โปรโมชัน)
    // ════════════════════════════════════════════════════════
    $bk->execute(['BK-20260315-001', $c1, 4800.00, 1, 480.00, 4320.00, 'confirmed', null, '2026-03-06 18:00:00']);
    $b3 = (int) $pdo->lastInsertId();
    $bi->execute([$b3, $room_id('D201'), '2026-03-15', '2026-03-21', 800.00, 4800.00]);
    $bi3 = (int) $pdo->lastInsertId();
    $bp->execute([$bi3, $pet_milk]);
    $bp->execute([$bi3, $pet_moji]);
    $py->execute([$b3, 1, 'full_payment', 4320.00, 'assets/images/payments/DEFAULT_PAYMENT_PROOF_3.jpg', 'verified', $staff_id, '2026-03-06 18:30:00', '2026-03-06 18:30:00']);
    $pdo->exec("UPDATE promotions SET used_count = used_count + 1 WHERE id = 1");

    // ════════════════════════════════════════════════════════
    // BOOKING 4 (C2): pending_payment — สุนัข 1 ตัว ห้อง Standard
    // ════════════════════════════════════════════════════════
    $bk->execute(['BK-20260320-001', $c2, 1500.00, null, 0, 1500.00, 'pending_payment', 'ลัคกี้ชอบเห่า รบกวนดูแลด้วยนะครับ', '2026-03-07 09:00:00']);
    $b4 = (int) $pdo->lastInsertId();
    $bi->execute([$b4, $room_id('S102'), '2026-03-20', '2026-03-23', 500.00, 1500.00]);
    $bi4 = (int) $pdo->lastInsertId();
    $bp->execute([$bi4, $pet_lucky]);

    // ════════════════════════════════════════════════════════
    // BOOKING 5 (C2): verifying_payment — สุนัข VIP (ดุ)
    // ════════════════════════════════════════════════════════
    $bk->execute(['BK-20260325-001', $c2, 6000.00, null, 0, 6000.00, 'verifying_payment', 'แม็กซ์ดุ ต้องระวังเป็นพิเศษ ห้ามจับตัวกะทันหัน', '2026-03-06 20:00:00']);
    $b5 = (int) $pdo->lastInsertId();
    $bi->execute([$b5, $room_id('V301'), '2026-03-25', '2026-03-30', 1200.00, 6000.00]);
    $bi5 = (int) $pdo->lastInsertId();
    $bp->execute([$bi5, $pet_max]);
    $bs->execute([$b5, $bi5, 5, 5, 120.00, 600.00]);
    $py->execute([$b5, 2, 'full_payment', 6000.00, 'assets/images/payments/DEFAULT_PAYMENT_PROOF_1.jpg', 'pending', null, '2026-03-06 20:30:00', '2026-03-06 20:30:00']);

    // ════════════════════════════════════════════════════════
    // BOOKING 6 (C2): cancelled + refund
    // ════════════════════════════════════════════════════════
    $bk->execute(['BK-20260210-001', $c2, 1000.00, null, 0, 1000.00, 'cancelled', null, '2026-02-08 12:00:00']);
    $b6 = (int) $pdo->lastInsertId();
    $bi->execute([$b6, $room_id('S201'), '2026-02-10', '2026-02-12', 500.00, 1000.00]);
    $bi6 = (int) $pdo->lastInsertId();
    $bp->execute([$bi6, $pet_lucky]);
    $py->execute([$b6, 3, 'full_payment', 1000.00, 'assets/images/payments/DEFAULT_PAYMENT_PROOF_2.jpg', 'refunded', $staff_id, '2026-02-08 12:30:00', '2026-02-08 12:30:00']);
    $pay6_id = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO refunds (payment_id, booking_id, refund_amount, refund_type, reason, status, processed_by_employee_id) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$pay6_id, $b6, 1000.00, 'cash', 'ลูกค้าขอยกเลิกเนื่องจากมีธุระด่วน คืนเงินเต็มจำนวน', 'processed', $staff_id]);
}
