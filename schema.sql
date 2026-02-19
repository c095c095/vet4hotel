SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- ==========================================
-- 1. SYSTEM USERS & CUSTOMERS
-- ==========================================

-- พนักงานและผู้ดูแลระบบ
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin', 'staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ลูกค้า (เพิ่ม Contact ฉุกเฉิน)
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 2. PETS MANAGEMENT & HEALTH
-- ==========================================

-- ชนิดสัตว์ (หมา, แมว, นก ฯลฯ)
CREATE TABLE `species` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL, -- e.g., Dog, Cat
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- สายพันธุ์
CREATE TABLE `breeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `species_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL, -- e.g., Golden Retriever, Persian
  PRIMARY KEY (`id`),
  FOREIGN KEY (`species_id`) REFERENCES `species`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ข้อมูลสัตว์เลี้ยง
CREATE TABLE `pets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `species_id` int(11) NOT NULL,
  `breed_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `gender` enum('male', 'female', 'spayed', 'neutered', 'unknown') DEFAULT 'unknown',
  `vet_name` varchar(100) DEFAULT NULL, -- คลินิก/หมอประจำตัว
  `vet_phone` varchar(20) DEFAULT NULL, -- เบอร์คลินิก
  `is_aggressive` tinyint(1) DEFAULT 0, -- ดุ/กัดไหม (1=ดุ, 0=ไม่ดุ)
  `behavior_note` text DEFAULT NULL, -- หมายเหตุพฤติกรรม (เช่น กลัวฟ้าร้อง)
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`species_id`) REFERENCES `species`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`breed_id`) REFERENCES `breeds`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ข้อมูลวัคซีน
CREATE TABLE `pet_vaccinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `administered_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL, -- [CRITICAL] ใช้เช็คตอนกดจอง
  `document_url` varchar(255) DEFAULT NULL, -- รูปถ่ายสมุดวัคซีน
  `is_verified` tinyint(1) DEFAULT 0, -- พนักงานตรวจหรือยัง
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ประวัติสุขภาพอื่นๆ (โรคประจำตัว, อาการแพ้)
CREATE TABLE `pet_medical_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `record_type` enum('allergy', 'medication', 'chronic_disease', 'surgery', 'other') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. FACILITY & ROOMS (INVENTORY)
-- ==========================================

-- ประเภทห้องพัก
CREATE TABLE `room_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price_per_night` decimal(10,2) NOT NULL,
  `max_pets` int(2) NOT NULL DEFAULT 1,
  `size_sqm` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- รูปภาพประเภทห้องพัก (1 ประเภทมีได้หลายรูป)
CREATE TABLE `room_type_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_type_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- สิ่งอำนวยความสะดวก
CREATE TABLE `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon_class` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ความสัมพันธ์ระหว่างประเภทห้องและสิ่งอำนวยความสะดวก
CREATE TABLE `room_type_amenities` (
  `room_type_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  PRIMARY KEY (`room_type_id`, `amenity_id`),
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`amenity_id`) REFERENCES `amenities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ห้องพักจริงๆ (Physical Rooms)
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_type_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `floor_level` varchar(10) DEFAULT '1',
  `status` enum('active', 'maintenance', 'out_of_service') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 4. BOOKINGS & SERVICES (TRANSACTIONS)
-- ==========================================

-- การจองหลัก (Cart/Header)
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(20) NOT NULL, -- e.g., BK-20231024-001 (สำหรับให้ลูกค้าดู)
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending_payment', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending_payment',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_ref` (`booking_ref`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- รายละเอียดการจองระดับห้องพัก (1 การจอง มีได้หลายห้อง)
CREATE TABLE `booking_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `locked_unit_price` decimal(10,2) NOT NULL, -- เก็บราคา ณ วันที่จอง
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_availability` (`room_id`, `check_in_date`, `check_out_date`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ระบุว่าสัตว์เลี้ยงตัวไหน อยู่ห้องไหนในการจองนี้
CREATE TABLE `booking_item_pets` (
  `booking_item_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  PRIMARY KEY (`booking_item_id`, `pet_id`),
  FOREIGN KEY (`booking_item_id`) REFERENCES `booking_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- บริการเสริม (Add-ons)
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `charge_type` enum('per_stay', 'per_night', 'per_pet') DEFAULT 'per_stay',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- การซื้อบริการเสริมในการจอง
CREATE TABLE `booking_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL, -- ระบุได้ว่าบริการนี้ทำกับสัตว์ตัวไหน (เช่น อาบน้ำ)
  `quantity` int(11) NOT NULL DEFAULT 1,
  `locked_unit_price` decimal(10,2) NOT NULL, -- เก็บราคา ณ วันที่จอง
  `total_price` decimal(10,2) NOT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางอัปเดตสถานะรายวัน (จุดขายของโปรเจกต์)
CREATE TABLE `daily_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_item_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `update_type` enum('meal', 'potty', 'playtime', 'general_photo', 'health_issue') NOT NULL,
  `message` text,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`booking_item_id`) REFERENCES `booking_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 5. BILLING & PAYMENTS
-- ==========================================

-- ช่องทางการรับชำระเงินของโรงแรม
CREATE TABLE `payment_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(100) NOT NULL, -- e.g., KBank, PromptPay, Stripe
  `account_details` text NOT NULL, -- เก็บเลขบัญชี หรือ API Key ชั่วคราว (เข้ารหัส)
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ประวัติการชำระเงิน
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `payment_channel_id` int(11) DEFAULT NULL,
  `payment_type` enum('deposit', 'full_payment', 'balance_due', 'extra_charge') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL, -- เลขที่อ้างอิงจากธนาคาร
  `proof_image_url` varchar(255) DEFAULT NULL, -- สลิปโอนเงิน
  `status` enum('pending', 'verified', 'rejected', 'refunded') DEFAULT 'pending',
  `verified_by_employee_id` int(11) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`payment_channel_id`) REFERENCES `payment_channels`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`verified_by_employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 6. FRONTEND CMS
-- ==========================================

CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `target_url` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;