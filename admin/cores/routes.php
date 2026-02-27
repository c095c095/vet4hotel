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

    // --- Authentication ---
    "login" => ["title" => "เข้าสู่ระบบ", "file" => "pages/login.php"],
    "logout" => ["title" => "ออกจากระบบ", "file" => "cores/logout.php"],

    // --- Setup ---
    "setup" => ["title" => "ตั้งค่าเริ่มต้น", "file" => "pages/setup.php"],

    // --- Error Pages ---
    "404" => ["title" => "ไม่พบหน้าเว็บ", "file" => "pages/404.php"],
];