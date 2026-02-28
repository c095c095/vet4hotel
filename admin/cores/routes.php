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

    // --- Authentication ---
    "login" => ["title" => "เข้าสู่ระบบ", "file" => "pages/login.php"],
    "logout" => ["title" => "ออกจากระบบ", "file" => "cores/logout.php"],

    // --- Payments ---
    "payments" => ["title" => "การชำระเงิน", "file" => "pages/payments.php"],

    // --- Services ---
    "services" => ["title" => "บริการเสริม", "file" => "pages/services.php"],

    // --- Setup ---
    "setup" => ["title" => "ตั้งค่าเริ่มต้น", "file" => "pages/setup.php"],

    // --- Error Pages ---
    "404" => ["title" => "ไม่พบหน้าเว็บ", "file" => "pages/404.php"],
];