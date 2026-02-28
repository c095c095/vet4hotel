<?php
// ═══════════════════════════════════════════════════════════
// ADMIN URL ROUTES — VET4 HOTEL
// ═══════════════════════════════════════════════════════════

$pages = [
    "home" => ["title" => "หน้าแรก", "file" => "pages/home.php"],

    // --- Bookings ---
    "bookings" => ["title" => "จัดการการจอง", "file" => "pages/bookings.php"],
    "booking_create" => ["title" => "สร้างการจองใหม่", "file" => "pages/booking_create.php"],
    "booking_detail" => ["title" => "รายละเอียดการจอง", "file" => "pages/booking_detail.php"],

    // --- Rooms ---
    "rooms" => ["title" => "จัดการห้องพัก", "file" => "pages/rooms.php"],

    // --- Customers ---
    "customers" => ["title" => "จัดการลูกค้า", "file" => "pages/customers.php"],

    // --- Pets ---
    "pets" => ["title" => "จัดการสัตว์เลี้ยง", "file" => "pages/pets.php"],

    // --- Authentication ---
    "login" => ["title" => "เข้าสู่ระบบ", "file" => "pages/login.php"],
    "logout" => ["title" => "ออกจากระบบ", "file" => "cores/logout.php"],

    // --- Payments ---
    "payments" => ["title" => "การชำระเงิน", "file" => "pages/payments.php"],

    // --- Services ---
    "services" => ["title" => "บริการเสริม", "file" => "pages/services.php"],

    // --- Daily Care Tasks ---
    "care_tasks" => ["title" => "งานดูแลรายวัน", "file" => "pages/care_tasks.php"],

    // --- Settings ---
    "settings" => ["title" => "ตั้งค่าระบบ", "file" => "pages/settings.php"],

    // --- Setup ---
    "setup" => ["title" => "ตั้งค่าเริ่มต้น", "file" => "pages/setup.php"],

    // --- Promotions ---
    "promotions" => ["title" => "จัดการโปรโมชัน", "file" => "pages/promotions.php"],

    // --- Room Types ---
    "room_types" => ["title" => "ประเภทห้องพัก", "file" => "pages/room_types.php"],

    // --- Error Pages ---
    "404" => ["title" => "ไม่พบหน้าเว็บ", "file" => "pages/404.php"],
];