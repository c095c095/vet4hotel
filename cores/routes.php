<?php

$pages = [
    // --- Public Pages ---
    "home" => ["title" => "หน้าแรก", "file" => "pages/home.php", "auth_required" => false],
    "rooms" => ["title" => "ห้องพักของเรา", "file" => "pages/rooms.php", "auth_required" => false],
    "room_details" => ["title" => "รายละเอียดห้องพัก", "file" => "pages/room_details.php", "auth_required" => false],
    "features" => ["title" => "บริการและสิ่งอำนวยความสะดวก", "file" => "pages/features.php", "auth_required" => false],
    "contact" => ["title" => "ติดต่อเรา", "file" => "pages/contact.php", "auth_required" => false],

    // --- Authentication ---
    "login" => ["title" => "เข้าสู่ระบบ", "file" => "pages/login.php", "auth_required" => false],
    "register" => ["title" => "สมัครสมาชิก", "file" => "pages/register.php", "auth_required" => false],
    "logout" => ["title" => "ออกจากระบบ", "file" => "cores/logout.php", "auth_required" => true],

    // --- Customer Portal (Auth Required) ---
    "profile" => ["title" => "ข้อมูลส่วนตัว", "file" => "pages/profile.php", "auth_required" => true],
    "my_pets" => ["title" => "สัตว์เลี้ยงของฉัน", "file" => "pages/my_pets.php", "auth_required" => true],
    "booking_history" => ["title" => "ประวัติการจอง", "file" => "pages/booking_history.php", "auth_required" => true],
    "booking_detail" => ["title" => "รายละเอียดการจอง", "file" => "pages/booking_detail.php", "auth_required" => true],
    "active_stay" => ["title" => "ติดตามสถานะเข้าพัก (Live)", "file" => "pages/active_stay.php", "auth_required" => true],

    // --- Booking Engine ---
    "booking" => ["title" => "จองห้องพัก", "file" => "pages/booking.php", "auth_required" => true],
    "cart" => ["title" => "ตะกร้าของฉัน", "file" => "pages/cart.php", "auth_required" => true],
    "payment" => ["title" => "ชำระเงิน", "file" => "pages/payment.php", "auth_required" => true],

    // --- Error Pages ---
    "404" => ["title" => "ไม่พบหน้าเว็บ", "file" => "pages/404.php", "auth_required" => false]
];
