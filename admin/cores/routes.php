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

    // --- Pet Transportation ---
    "pet_transportation" => ["title" => "บริการรับ-ส่งสัตว์เลี้ยง", "file" => "pages/pet_transportation.php"],

    // --- Customers ---
    "customers" => ["title" => "จัดการลูกค้า", "file" => "pages/customers.php"],

    // --- Pets ---
    "pets" => ["title" => "จัดการสัตว์เลี้ยง", "file" => "pages/pets.php"],

    // --- Profile ---
    "profile" => ["title" => "ข้อมูลส่วนตัว", "file" => "pages/profile.php"],

    // --- Authentication ---
    "login" => ["title" => "เข้าสู่ระบบ", "file" => "pages/login.php"],
    "logout" => ["title" => "ออกจากระบบ", "file" => "cores/logout.php"],

    // --- Payments & Refunds ---
    "payments" => ["title" => "การชำระเงิน", "file" => "pages/payments.php"],
    "refunds" => ["title" => "จัดการคืนเงิน", "file" => "pages/refunds.php"],

    // --- Services ---
    "services" => ["title" => "บริการเสริม", "file" => "pages/services.php"],

    // --- Daily Care Tasks & Updates ---
    "care_tasks" => ["title" => "งานดูแลรายวัน", "file" => "pages/care_tasks.php"],
    "daily_updates" => ["title" => "สมุดพกสัตว์เลี้ยง / อัปเดตรายวัน", "file" => "pages/daily_updates.php"],

    // --- Settings ---
    "settings" => ["title" => "ตั้งค่าระบบ", "file" => "pages/settings.php"],

    // --- Setup ---
    "setup" => ["title" => "ตั้งค่าเริ่มต้น", "file" => "pages/setup.php"],

    // --- Promotions & CMS ---
    "promotions" => ["title" => "จัดการโปรโมชัน", "file" => "pages/promotions.php"],
    "cms_banners" => ["title" => "จัดการแบนเนอร์ภาพ", "file" => "pages/cms_banners.php"],
    "cms_reviews" => ["title" => "รีวิวจากลูกค้า", "file" => "pages/cms_reviews.php"],

    // --- Room Types ---
    "room_types" => ["title" => "ประเภทห้องพัก", "file" => "pages/room_types.php"],

    // --- Error Pages ---
    "404" => ["title" => "ไม่พบหน้าเว็บ", "file" => "pages/404.php"],
];