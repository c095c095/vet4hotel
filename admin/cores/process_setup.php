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
$init_data = isset($_POST['init_data']) ? true : false;

// Validation
$errors = [];

if (empty($first_name)) {
    $errors[] = "กรุณากรอกชื่อ";
}

if (empty($last_name)) {
    $errors[] = "กรุณากรอกนามสกุล";
}

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

if ($password !== $confirm_password) {
    $errors[] = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
}

if (empty($db_host)) {
    $errors[] = "กรุณากรอกโฮสต์ฐานข้อมูล";
}

if (empty($db_name)) {
    $errors[] = "กรุณากรอกชื่อฐานข้อมูล";
}

if (empty($db_user)) {
    $errors[] = "กรุณากรอกชื่อผู้ใช้ฐานข้อมูล";
}

// If validation errors, redirect back
if (!empty($errors)) {
    $_SESSION['error_msg'] = implode("<br>", $errors);
    header("Location: ?page=setup");
    exit();
}

try {
    // Test database connection with provided credentials
    $test_pdo = new PDO("mysql:host=" . $db_host . ";dbname=" . $db_name, $db_user, $db_pass);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $test_pdo->exec("set names utf8");

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

    // Initialize basic data if requested
    if ($init_data) {
        initBasicData($test_pdo);
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

/**
 * Initialize basic data for the system
 */
function initBasicData($pdo) {
    // Species
    $species = [
        ['name' => 'แมว'],
        ['name' => 'สุนัข'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO species (name) VALUES (?)");
    foreach ($species as $s) {
        $stmt->execute([$s['name']]);
    }

    // Breeds for Dogs
    $dog_breeds = [
        ['species_id' => 1, 'name' => 'โกลเด้น รีทรีฟเวอร์'],
        ['species_id' => 1, 'name' => 'ลาบราดอร์ รีทรีฟเวอร์'],
        ['species_id' => 1, 'name' => 'พุดเดิ้ล'],
        ['species_id' => 1, 'name' => 'ชิวาวา'],
        ['species_id' => 1, 'name' => 'ปอมเมอเรเนียน'],
        ['species_id' => 1, 'name' => 'ไซบีเรียน ฮัสกี้'],
        ['species_id' => 1, 'name' => 'บีเกิ้ล'],
        ['species_id' => 1, 'name' => 'ไทยหลังอาน'],
        ['species_id' => 1, 'name' => 'บางแก้ว'],
        ['species_id' => 1, 'name' => 'ร็อตไวเลอร์'],
        ['species_id' => 1, 'name' => 'เยอรมันเชพเพิร์ด'],
        ['species_id' => 1, 'name' => 'พันธุ์ผสม/ไม่ทราบสายพันธุ์']
    ];

    // Breeds for Cats
    $cat_breeds = [
        ['species_id' => 2, 'name' => 'เปอร์เซีย'],
        ['species_id' => 2, 'name' => 'สก็อตติช โฟลด์'],
        ['species_id' => 2, 'name' => 'บริติช ชอร์ตแฮร์'],
        ['species_id' => 2, 'name' => 'อเมริกัน ชอร์ตแฮร์'],
        ['species_id' => 2, 'name' => 'เมนคูน'],
        ['species_id' => 2, 'name' => 'รากดอล'],
        ['species_id' => 2, 'name' => 'เบงกอล'],
        ['species_id' => 2, 'name' => 'สยาม'],
        ['species_id' => 2, 'name' => 'วิเชียรมาศ'],
        ['species_id' => 2, 'name' => 'พันธุ์ผสม/ไม่ทราบสายพันธุ์']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO breeds (species_id, name) VALUES (?, ?)");
    foreach (array_merge($dog_breeds, $cat_breeds) as $b) {
        $stmt->execute([$b['species_id'], $b['name']]);
    }

    // Vaccine Types for Dogs (species_id = 2)
    $dog_vaccines = [
        ['species_id' => 2, 'name' => 'วัคซีนรวม 5 โรค (DHPPi)'],
        ['species_id' => 2, 'name' => 'วัคซีนพิษสุนัขบ้า'],
        ['species_id' => 2, 'name' => 'วัคซีนลำไส้อักเสบ (Corona)'],
        ['species_id' => 2, 'name' => 'วัคซีนไข้หัดสุนัข']
    ];

    // Vaccine Types for Cats (species_id = 1)
    $cat_vaccines = [
        ['species_id' => 1, 'name' => 'วัคซีนรวม 3 โรค (FVRCP)'],
        ['species_id' => 1, 'name' => 'วัคซีนพิษแมวบ้า'],
        ['species_id' => 1, 'name' => 'วัคซีนลูคีเมีย (FeLV)'],
        ['species_id' => 1, 'name' => 'วัคซีนขี้เรื้อน (FIP)']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO vaccine_types (species_id, name) VALUES (?, ?)");
    foreach (array_merge($dog_vaccines, $cat_vaccines) as $v) {
        $stmt->execute([$v['species_id'], $v['name']]);
    }

    // Medical Record Types
    $medical_types = [
        ['name' => 'โรคประจำตัว'],
        ['name' => 'อาการแพ้'],
        ['name' => 'ยาที่กินอยู่ประจำ'],
        ['name' => 'ประวัติการผ่าตัด'],
        ['name' => 'อาหารพิเศษ']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO medical_record_types (name) VALUES (?)");
    foreach ($medical_types as $m) {
        $stmt->execute([$m['name']]);
    }

    // Daily Update Types
    $update_types = [
        ['name' => 'อาหาร', 'icon_class' => 'utensils'],
        ['name' => 'เข้าห้องน้ำ', 'icon_class' => 'droplet'],
        ['name' => 'เล่น/กิจกรรม', 'icon_class' => 'gamepad-2'],
        ['name' => 'รูปถ่าย', 'icon_class' => 'camera'],
        ['name' => 'ปัญหาสุขภาพ', 'icon_class' => 'alert-triangle'],
        ['name' => 'พฤติกรรม', 'icon_class' => 'smile'],
        ['name' => 'การนอนหลับ', 'icon_class' => 'moon']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO daily_update_types (name, icon_class) VALUES (?, ?)");
    foreach ($update_types as $u) {
        $stmt->execute([$u['name'], $u['icon_class']]);
    }

    // Care Task Types
    $care_types = [
        ['name' => 'ป้อนยา'],
        ['name' => 'ให้อาหาร'],
        ['name' => 'พาเที่ยวเล่น'],
        ['name' => 'ทำความสะอาด'],
        ['name' => 'ตรวจสุขภาพ'],
        ['name' => 'ดูแลพิเศษ']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO care_task_types (name) VALUES (?)");
    foreach ($care_types as $c) {
        $stmt->execute([$c['name']]);
    }

    // Room Types
    $room_types = [
        [
            'name' => 'ห้องมาตรฐาน',
            'description' => 'ห้องพักขนาดมาตรฐานสำหรับสัตว์เลี้ยง 1-2 ตัว พร้อมระบบปรับอากาศ',
            'base_price_per_night' => 350.00,
            'max_pets' => 2,
            'size_sqm' => 4.00
        ],
        [
            'name' => 'ห้องดีลักซ์',
            'description' => 'ห้องพักขนาดใหญ่พิเศษ พร้อมมุมเล่นและวิวสวน รองรับสัตว์เลี้ยงได้ 1-3 ตัว',
            'base_price_per_night' => 550.00,
            'max_pets' => 3,
            'size_sqm' => 6.00
        ],
        [
            'name' => 'ห้องวีไอพี',
            'description' => 'ห้องพักหรูหราสุดพิเศษ พร้อมกล้องวงจรปิดส่วนตัว 24 ชม. รองรับสัตว์เลี้ยงได้ 1-4 ตัว',
            'base_price_per_night' => 850.00,
            'max_pets' => 4,
            'size_sqm' => 10.00
        ],
        [
            'name' => 'ห้องแมวโดยเฉพาะ',
            'description' => 'ห้องพักออกแบบพิเศษสำหรับแมว มีที่ปีนและของเล่น รองรับแมวได้ 1-3 ตัว',
            'base_price_per_night' => 400.00,
            'max_pets' => 3,
            'size_sqm' => 5.00
        ]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO room_types (name, description, base_price_per_night, max_pets, size_sqm) VALUES (?, ?, ?, ?, ?)");
    foreach ($room_types as $r) {
        $stmt->execute([$r['name'], $r['description'], $r['base_price_per_night'], $r['max_pets'], $r['size_sqm']]);
    }

    // Amenities
    $amenities = [
        ['name' => 'เครื่องปรับอากาศ', 'icon_class' => 'wind'],
        ['name' => 'กล้องวงจรปิด', 'icon_class' => 'video'],
        ['name' => 'พัดลม', 'icon_class' => 'fan'],
        ['name' => 'ที่นอนนุ่มพิเศษ', 'icon_class' => 'bed'],
        ['name' => 'ของเล่น', 'icon_class' => 'gamepad-2'],
        ['name' => 'ห้องน้ำในตัว', 'icon_class' => 'bath'],
        ['name' => 'วิวสวน', 'icon_class' => 'trees'],
        ['name' => 'เครื่องกรองอากาศ', 'icon_class' => 'filter']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO amenities (name, icon_class) VALUES (?, ?)");
    foreach ($amenities as $a) {
        $stmt->execute([$a['name'], $a['icon_class']]);
    }

    // Room Type Amenities Mapping
    // 1=Standard, 2=Deluxe, 3=VIP, 4=Cat Room
    // 1=AC, 2=CCTV, 3=Fan, 4=Bed, 5=Toys, 6=Bathroom, 7=Garden View, 8=Air Purifier
    $room_type_amenities = [
        // Standard Room: AC, Fan, Bed, Toys, Bathroom
        ['room_type_id' => 1, 'amenity_id' => 1],
        ['room_type_id' => 1, 'amenity_id' => 3],
        ['room_type_id' => 1, 'amenity_id' => 4],
        ['room_type_id' => 1, 'amenity_id' => 5],
        ['room_type_id' => 1, 'amenity_id' => 6],
        // Deluxe Room: AC, CCTV, Fan, Bed, Toys, Bathroom, Garden View
        ['room_type_id' => 2, 'amenity_id' => 1],
        ['room_type_id' => 2, 'amenity_id' => 2],
        ['room_type_id' => 2, 'amenity_id' => 3],
        ['room_type_id' => 2, 'amenity_id' => 4],
        ['room_type_id' => 2, 'amenity_id' => 5],
        ['room_type_id' => 2, 'amenity_id' => 6],
        ['room_type_id' => 2, 'amenity_id' => 7],
        // VIP Room: All amenities
        ['room_type_id' => 3, 'amenity_id' => 1],
        ['room_type_id' => 3, 'amenity_id' => 2],
        ['room_type_id' => 3, 'amenity_id' => 3],
        ['room_type_id' => 3, 'amenity_id' => 4],
        ['room_type_id' => 3, 'amenity_id' => 5],
        ['room_type_id' => 3, 'amenity_id' => 6],
        ['room_type_id' => 3, 'amenity_id' => 7],
        ['room_type_id' => 3, 'amenity_id' => 8],
        // Cat Room: AC, CCTV, Bed, Toys, Bathroom, Air Purifier
        ['room_type_id' => 4, 'amenity_id' => 1],
        ['room_type_id' => 4, 'amenity_id' => 2],
        ['room_type_id' => 4, 'amenity_id' => 4],
        ['room_type_id' => 4, 'amenity_id' => 5],
        ['room_type_id' => 4, 'amenity_id' => 6],
        ['room_type_id' => 4, 'amenity_id' => 8],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO room_type_amenities (room_type_id, amenity_id) VALUES (?, ?)");
    foreach ($room_type_amenities as $ra) {
        $stmt->execute([$ra['room_type_id'], $ra['amenity_id']]);
    }

    // Room Type Images (Default placeholder images)
    $room_type_images = [
        // Standard Room
        ['room_type_id' => 1, 'image_url' => 'assets/images/rooms/standard-1.jpg', 'is_primary' => 1],
        ['room_type_id' => 1, 'image_url' => 'assets/images/rooms/standard-2.jpg', 'is_primary' => 0],
        // Deluxe Room
        ['room_type_id' => 2, 'image_url' => 'assets/images/rooms/deluxe-1.jpg', 'is_primary' => 1],
        ['room_type_id' => 2, 'image_url' => 'assets/images/rooms/deluxe-2.jpg', 'is_primary' => 0],
        ['room_type_id' => 2, 'image_url' => 'assets/images/rooms/deluxe-3.jpg', 'is_primary' => 0],
        // VIP Room
        ['room_type_id' => 3, 'image_url' => 'assets/images/rooms/vip-1.jpg', 'is_primary' => 1],
        ['room_type_id' => 3, 'image_url' => 'assets/images/rooms/vip-2.jpg', 'is_primary' => 0],
        ['room_type_id' => 3, 'image_url' => 'assets/images/rooms/vip-3.jpg', 'is_primary' => 0],
        // Cat Room
        ['room_type_id' => 4, 'image_url' => 'assets/images/rooms/cat-1.jpg', 'is_primary' => 1],
        ['room_type_id' => 4, 'image_url' => 'assets/images/rooms/cat-2.jpg', 'is_primary' => 0],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO room_type_images (room_type_id, image_url, is_primary) VALUES (?, ?, ?)");
    foreach ($room_type_images as $img) {
        $stmt->execute([$img['room_type_id'], $img['image_url'], $img['is_primary']]);
    }

    // Physical Rooms (Actual rooms available for booking)
    $rooms = [
        // Standard Rooms (Room Type 1) - 6 rooms
        ['room_type_id' => 1, 'room_number' => 'S101', 'floor_level' => '1', 'status' => 'active'],
        ['room_type_id' => 1, 'room_number' => 'S102', 'floor_level' => '1', 'status' => 'active'],
        ['room_type_id' => 1, 'room_number' => 'S103', 'floor_level' => '1', 'status' => 'active'],
        ['room_type_id' => 1, 'room_number' => 'S201', 'floor_level' => '2', 'status' => 'active'],
        ['room_type_id' => 1, 'room_number' => 'S202', 'floor_level' => '2', 'status' => 'active'],
        ['room_type_id' => 1, 'room_number' => 'S203', 'floor_level' => '2', 'status' => 'active'],
        // Deluxe Rooms (Room Type 2) - 4 rooms
        ['room_type_id' => 2, 'room_number' => 'D101', 'floor_level' => '1', 'status' => 'active'],
        ['room_type_id' => 2, 'room_number' => 'D102', 'floor_level' => '1', 'status' => 'active'],
        ['room_type_id' => 2, 'room_number' => 'D201', 'floor_level' => '2', 'status' => 'active'],
        ['room_type_id' => 2, 'room_number' => 'D202', 'floor_level' => '2', 'status' => 'active'],
        // VIP Rooms (Room Type 3) - 2 rooms with CCTV
        ['room_type_id' => 3, 'room_number' => 'V301', 'floor_level' => '3', 'status' => 'active', 'cctv_url' => 'https://cctv.vet4hotel.local/v301'],
        ['room_type_id' => 3, 'room_number' => 'V302', 'floor_level' => '3', 'status' => 'active', 'cctv_url' => 'https://cctv.vet4hotel.local/v302'],
        // Cat Rooms (Room Type 4) - 4 rooms
        ['room_type_id' => 4, 'room_number' => 'C101', 'floor_level' => '1', 'status' => 'active'],
        ['room_type_id' => 4, 'room_number' => 'C102', 'floor_level' => '1', 'status' => 'active'],
        ['room_type_id' => 4, 'room_number' => 'C201', 'floor_level' => '2', 'status' => 'active'],
        ['room_type_id' => 4, 'room_number' => 'C202', 'floor_level' => '2', 'status' => 'active'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO rooms (room_type_id, room_number, floor_level, status, cctv_url) VALUES (?, ?, ?, ?, ?)");
    foreach ($rooms as $r) {
        $cctv = $r['cctv_url'] ?? null;
        $stmt->execute([$r['room_type_id'], $r['room_number'], $r['floor_level'], $r['status'], $cctv]);
    }

    // Services
    $services = [
        ['name' => 'อาบน้ำและตัดขน', 'description' => 'บริการอาบน้ำและตัดแต่งขนสัตว์เลี้ยง', 'price' => 300.00, 'charge_type' => 'per_pet'],
        ['name' => 'ตัดเล็บ', 'description' => 'บริการตัดเล็บและตะไบเล็บ', 'price' => 100.00, 'charge_type' => 'per_pet'],
        ['name' => 'นวดผ่อนคลาย', 'description' => 'บริการนวดผ่อนคลายสำหรับสัตว์เลี้ยง', 'price' => 200.00, 'charge_type' => 'per_pet'],
        ['name' => 'พาเที่ยวเล่นเพิ่ม', 'description' => 'พาสัตว์เลี้ยงออกกำลังกายนอกห้องเพิ่มเติม', 'price' => 150.00, 'charge_type' => 'per_night'],
        ['name' => 'อาหารพิเศษ', 'description' => 'ให้อาหารพิเศษตามที่เจ้าของต้องการ', 'price' => 100.00, 'charge_type' => 'per_night'],
        ['name' => 'อัพเดตรูปภาพพิเศษ', 'description' => 'ส่งรูปภาพและวิดีโออัพเดตสถานะสัตว์เลี้ยงทุกวัน', 'price' => 50.00, 'charge_type' => 'per_stay']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO services (name, description, price, charge_type) VALUES (?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute([$s['name'], $s['description'], $s['price'], $s['charge_type']]);
    }

    // Payment Channels
    $payment_channels = [
        ['type' => 'qr_promptpay', 'name' => 'พร้อมเพย์', 'bank_name' => null, 'account_name' => null, 'account_number' => null, 'icon_class' => 'qr-code', 'fee_percent' => 0.00],
        ['type' => 'bank_transfer', 'name' => 'โอนเงินผ่านธนาคาร', 'bank_name' => 'ธนาคารกสิกรไทย', 'account_name' => 'บริษัท เว็ทโฟร์ จำกัด', 'account_number' => '123-4-56789-0', 'icon_class' => 'landmark', 'fee_percent' => 0.00],
        ['type' => 'bank_transfer', 'name' => 'โอนเงินผ่านธนาคาร', 'bank_name' => 'ธนาคารไทยพาณิชย์', 'account_name' => 'บริษัท เว็ทโฟร์ จำกัด', 'account_number' => '987-6-54321-0', 'icon_class' => 'landmark', 'fee_percent' => 0.00],
        ['type' => 'cash', 'name' => 'เงินสด', 'bank_name' => null, 'account_name' => null, 'account_number' => null, 'icon_class' => 'banknote', 'fee_percent' => 0.00]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO payment_channels (type, name, bank_name, account_name, account_number, icon_class, fee_percent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($payment_channels as $p) {
        $stmt->execute([$p['type'], $p['name'], $p['bank_name'], $p['account_name'], $p['account_number'], $p['icon_class'], $p['fee_percent']]);
    }
}
