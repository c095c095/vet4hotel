# Project Overview: Pet Hotel Management System

## 1. About the Project

ระบบบริหารจัดการโรงแรมสัตว์เลี้ยง (Pet Hotel Management System) ครอบคลุมการจองห้องพัก, บริการเสริม, การดูแลรายวัน (Daily Care Tasks), และระบบรายงานสถานะสัตว์เลี้ยงแบบ Real-time สำหรับเจ้าของ

## 2. Tech Stack

- **Backend:** Pure PHP (Native/Vanilla PHP) ไม่ใช้ Framework ใดๆ
- **Database:** MySQL / MariaDB (รันผ่าน XAMPP)
- **Database Driver:** บังคับใช้ **PDO (PHP Data Objects)** พร้อม Prepared Statements เพื่อป้องกัน SQL Injection
- **Frontend Framework:** - **Tailwind CSS v4** (Engine หลัก)
- **DaisyUI:** (Component Plugin)
- **Icons:** Lucide Icons (เรียกใช้ผ่าน CDN และรัน lucide.createIcons() ใน index.php แล้ว)
- **Architecture:** ใช้ระบบ Front Controller (`index.php` เป็นตัวจัดการ Route) และแยกส่วน Logic กับ UI ออกจากกันอย่างชัดเจน
- **Compilation Command:** `.\tailwindcss-windows-x64.exe -i .\assets\input.css -o .\assets\output.css --watch`

## 3. Directory Structure (Best Practice)

เพื่อให้โค้ดเป็นระเบียบ ให้ยึดโครงสร้างโฟลเดอร์ดังนี้:

```text
/
├── index.php                       # Front Controller จัดการ Routing ทั้งหมด
├── tailwindcss-windows-x64.exe     # Tailwind Binary
├── /pages                          # เก็บไฟล์ UI / Views ของหน้าเว็บ (HTML + PHP นิดหน่อย)
├── /cores                          # เก็บไฟล์ Logic ล้วนๆ (Business Logic & Database Operations)
├── /includes                       # เก็บไฟล์ Component ที่ใช้ซ้ำ (header.php, footer.php, navbar.php)
├── /assets                         # CSS, JS, Images (เช่น /assets/css/style.css)
│   ├── input.css                   # Tailwind Source + DaisyUI Config
│   └── output.css                  # Compiled CSS (ใช้จริงในหน้าเว็บ)
└── /admin                          # โฟลเดอร์แยกสำหรับระบบหลังบ้าน (พนักงาน/ผู้ดูแลระบบ)

```

## 4. Routing System (Frontend Pages)

ระบบหน้าบ้านใช้ตัวแปร `$pages` ในการทำ Routing โดยหากต้องสร้างหน้าใหม่ ให้ยึดโครงสร้างนี้:

```php
<?php
// โครงสร้าง Routing พื้นฐาน (สามารถเพิ่ม middleware เช็คสิทธิ์ในอนาคตได้)
$pages = [
    // --- Public Pages ---
    "home" =>           ["title" => "หน้าแรก", "file" => "pages/home.php", "auth_required" => false],
    "rooms" =>          ["title" => "ห้องพักของเรา", "file" => "pages/rooms.php", "auth_required" => false],
    "room_details" =>   ["title" => "รายละเอียดห้องพัก", "file" => "pages/room_details.php", "auth_required" => false],
    "features" =>       ["title" => "บริการและสิ่งอำนวยความสะดวก", "file" => "pages/features.php", "auth_required" => false],
    "contact" =>        ["title" => "ติดต่อเรา", "file" => "pages/contact.php", "auth_required" => false],

    // --- Authentication ---
    "login" =>          ["title" => "เข้าสู่ระบบ", "file" => "pages/login.php", "auth_required" => false],
    "register" =>       ["title" => "สมัครสมาชิก", "file" => "pages/register.php", "auth_required" => false],
    "logout" =>         ["title" => "ออกจากระบบ", "file" => "cores/logout.php", "auth_required" => true],

    // --- Customer Portal (Auth Required) ---
    "profile" =>        ["title" => "ข้อมูลส่วนตัว", "file" => "pages/profile.php", "auth_required" => true],
    "my_pets" =>        ["title" => "สัตว์เลี้ยงของฉัน", "file" => "pages/my_pets.php", "auth_required" => true],
    "booking_history" =>["title" => "ประวัติการจอง", "file" => "pages/booking_history.php", "auth_required" => true],
    "active_stay" =>    ["title" => "ติดตามสถานะเข้าพัก (Live)", "file" => "pages/active_stay.php", "auth_required" => true],

    // --- Booking Engine ---
    "booking" =>        ["title" => "จองห้องพัก", "file" => "pages/booking.php", "auth_required" => true],
    "payment" =>        ["title" => "ชำระเงิน", "file" => "pages/payment.php", "auth_required" => true],

    // --- Error Pages ---
    "404" =>            ["title" => "ไม่พบหน้าเว็บ", "file" => "pages/404.php", "auth_required" => false]
];

```

## 5. Core Database Modules

โครงสร้างฐานข้อมูล (`utf8mb4`) แบ่งออกเป็น 6 ส่วนหลัก:

1. **Lookup Tables:** `medical_record_types`, `daily_update_types`, `care_task_types`
2. **Users & Customers:** `employees` (admin/staff) และ `customers`
3. **Pets Management:** `species`, `breeds`, `pets`, `pet_vaccinations`, `pet_medical_records`
4. **Facility & Rooms:** `room_types`, `room_type_images`, `amenities`, `room_type_amenities`, `rooms`, `seasonal_pricings`
5. **Bookings & Services:** `bookings`, `booking_items`, `booking_item_pets`, `services`, `booking_services`, `daily_updates`, `daily_care_tasks`
6. **Billing & Payments:** `payment_channels`, `payments`
7. **Frontend CMS:** `banners`

## 6. Key Features & Business Logic (จุดขายของระบบ)

เมื่อต้องเขียนโค้ดสำหรับฟีเจอร์เหล่านี้ ให้คำนึงถึง Business Logic ต่อไปนี้เสมอ:

### 6.1. สมุดพกสัตว์เลี้ยงดิจิทัล (Digital Pet Report Card)

- **Concept:** รวบรวมข้อมูลจาก `daily_updates` (ภาพและข้อความ) ของการจองทริปนั้นๆ
- **Trigger:** ทำงานเมื่อการจองเปลี่ยนสถานะเป็น `checked_out`
- **Output:** สร้างเป็นหน้าเว็บสรุปหรือ PDF ข้อมูลจะถูกบันทึก URL ไว้ที่ `booking_item_pets.report_card_url`

### 6.2. ระบบกล้องวงจรปิดส่วนตัว (Private CCTV Link)

- **Concept:** ให้ลูกค้าดูกล้องห้องพักของสัตว์เลี้ยงตัวเองได้แบบ Real-time (ในหน้า `active_stay`)
- **Security Check:** ตรวจสอบว่า `customer_id` ตรงกัน, สถานะการจอง `bookings.status = 'checked_in'` เท่านั้น
- **Action:** ดึง `cctv_url` จาก `rooms` มาแสดงผลผ่าน Iframe หากหมดสิทธิ์เข้าพัก ระบบต้องบล็อกลิงก์ทันที

### 6.3. ป้ายเตือนพฤติกรรมอันตราย (Behavioral Red Flags)

- **Logic:** เช็คจากตาราง `pets.is_aggressive = 1`
- **UI Element:** ในหน้า Dashboard หลังบ้านของพนักงาน ต้องมี Label สีแดงขนาดใหญ่แจ้งเตือนอย่างชัดเจนเพื่อป้องกันพนักงานบาดเจ็บ

### 6.4. แดชบอร์ดเช็คลิสต์การดูแลรายวัน (Daily Care & Meds Dashboard)

- **Concept:** To-do list สำหรับพนักงานในการป้อนยาหรือให้อาหาร
- **Logic:** ดึงข้อมูลจาก `daily_care_tasks` พนักงานกด "Completed" ระบบจะอัปเดตสถานะพร้อมบันทึก `completed_at`

### 6.5. อัลกอริทึมจัดการห้องพักร่วม (Same-Family Room Logic)

- **Concept:** สัตว์เลี้ยงที่แชร์ห้องเดียวกัน (ห้องที่ `max_pets > 1`) ต้องมาจากเจ้าของเดียวกัน
- **Validation:** ในหน้า `booking` Backend ต้องตรวจสอบว่าสัตว์เลี้ยงทั้งหมดอ้างอิงกลับไปหา `pets.customer_id` ที่เป็นคนเดียวกันทั้งหมด

### 6.6. ระบบปรับราคาตามเทศกาล (Dynamic Peak Season Pricing)

- **Logic:** ตอนคำนวณราคา หากวันที่เข้าพักคาบเกี่ยวช่วง Peak Season (อิงตามตาราง `seasonal_pricings`) ให้ปรับ `room_types.base_price_per_night` เพิ่มตาม `price_multiplier_percent`

## 7. Coding Guidelines for AI

1. **No Frameworks:** ใช้ PHP Native ล้วนๆ
2. **Security:** บังคับใช้ **PDO Prepared Statements** ทุกครั้ง ห้ามต่อ String ใน Query เด็ดขาด
3. **Security First:** - ใช้ `PDO` ควบคู่กับ `prepare()` และ `execute()` เสมอ
   - ทำ Password Hashing ด้วย `password_hash()`
   - ก่อนโหลดไฟล์ใน `$pages` ที่ `auth_required => true` ต้องเช็ค `$_SESSION['customer_id']` เสมอ
4. **File Separation:** ไฟล์ใน `/pages` ควรเน้น HTML/UI และรับค่าตัวแปรมาแสดงผล ส่วนการ Insert/Update/Delete Database ให้โยน (POST) ไปที่ไฟล์ใน `/cores` แล้วค่อย redirect กลับมา
5. **DaisyUI Usage:** พยายามใช้ Component สำเร็จรูปจาก DaisyUI (เช่น `card`, `modal`, `steps`, `timeline`) เพื่อความรวดเร็วและดีไซน์ที่สม่ำเสมอ
6. **Primary Palette:** ใช้ Class `btn-primary`, `text-primary`, `bg-primary` เพื่อคุมโทนสีม่วงของแบรนด์
7. **Timezone:** ระบบตั้งค่าเป็น `+07:00` (ประเทศไทย)
8. **Logic Separation:** ห้ามเขียน Logic ประมวลผลหนักๆ ในไฟล์ `/pages` ให้เขียนใน `/cores` แล้วเรียกใช้หรือส่งค่าผ่าน `$_SESSION` หรือ Redirect เท่านั้น
