<?php
// ═══════════════════════════════════════════════════════════
// ADMIN SETTINGS PROCESSOR — VET4 HOTEL
// Handles CRUD for Employees, Payment Channels, Seasonal
// Pricing, and Lookup Tables.
// ═══════════════════════════════════════════════════════════

if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=settings");
    exit();
}

if (!isset($_SESSION['employee_id']) || ($_SESSION['employee_role'] ?? '') !== 'admin') {
    $_SESSION['msg_error'] = "คุณไม่มีสิทธิ์ดำเนินการนี้";
    header("Location: ?page=home");
    exit();
}

$sub_action = trim($_POST['sub_action'] ?? '');
$active_tab = trim($_POST['active_tab'] ?? 'employees');

// Build return URL with active tab
$return_url = "?page=settings&tab=" . urlencode($active_tab);

// ═══════════════════════════════════════════════
// 1. EMPLOYEES
// ═══════════════════════════════════════════════

// ─── ADD EMPLOYEE ───
if ($sub_action === 'add_employee') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'staff');
    $password = $_POST['password'] ?? '';

    $errors = [];
    if (empty($first_name))
        $errors[] = "กรุณากรอกชื่อ";
    if (empty($last_name))
        $errors[] = "กรุณากรอกนามสกุล";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "อีเมลไม่ถูกต้อง";
    if (!in_array($role, ['admin', 'staff']))
        $errors[] = "บทบาทไม่ถูกต้อง";
    if (empty($password) || strlen($password) < 8)
        $errors[] = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0)
            $errors[] = "อีเมลนี้มีผู้ใช้งานแล้ว";
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO employees (email, password_hash, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$email, $hash, $first_name, $last_name, $role]);
            $_SESSION['msg_success'] = "เพิ่มพนักงานสำเร็จแล้ว";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
    header("Location: " . $return_url);
    exit();
}

// ─── EDIT EMPLOYEE ───
if ($sub_action === 'edit_employee') {
    $id = (int) ($_POST['employee_id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'staff');

    $errors = [];
    if ($id <= 0)
        $errors[] = "ไม่พบข้อมูลพนักงาน";
    if (empty($first_name))
        $errors[] = "กรุณากรอกชื่อ";
    if (empty($last_name))
        $errors[] = "กรุณากรอกนามสกุล";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "อีเมลไม่ถูกต้อง";
    if (!in_array($role, ['admin', 'staff']))
        $errors[] = "บทบาทไม่ถูกต้อง";

    // Prevent modifying self role if admin to avoid losing access
    if (empty($errors) && $id == $_SESSION['employee_id'] && $role !== 'admin') {
        $errors[] = "ไม่สามารถเปลี่ยนบทบาทตัวเองได้";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $id]);
        if ($stmt->fetchColumn() > 0)
            $errors[] = "อีเมลนี้มีผู้ใช้งานแล้ว";
    }

    if (!empty($errors)) {
        $_SESSION['msg_error'] = implode("<br>", $errors);
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $role, $id]);
            $_SESSION['msg_success'] = "แก้ไขข้อมูลพนักงานสำเร็จแล้ว";

            // If editing self, update session
            if ($id == $_SESSION['employee_id']) {
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            }
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
    header("Location: " . $return_url);
    exit();
}

// ─── TOGGLE ACTIVE EMPLOYEE ───
if ($sub_action === 'toggle_employee') {
    $id = (int) ($_POST['employee_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);

    if ($id <= 0 || !in_array($new_status, [0, 1])) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
    } elseif ($id == $_SESSION['employee_id']) {
        $_SESSION['msg_error'] = "ไม่สามารถปิดการใช้งานบัญชีตัวเองได้";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            $label = $new_status ? 'เปิดใช้งาน' : 'ระงับบัญชี';
            $_SESSION['msg_success'] = "{$label} เรียบร้อยแล้ว";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
    header("Location: " . $return_url);
    exit();
}

// ─── RESET PASSWORD ───
if ($sub_action === 'reset_password') {
    $id = (int) ($_POST['employee_id'] ?? 0);
    $password = $_POST['new_password'] ?? '';

    if ($id <= 0) {
        $_SESSION['msg_error'] = "ไม่พบข้อมูลพนักงาน";
    } elseif (empty($password) || strlen($password) < 8) {
        $_SESSION['msg_error'] = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE employees SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            $_SESSION['msg_success'] = "ตั้งรหัสผ่านใหม่สำเร็จแล้ว";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
    header("Location: " . $return_url);
    exit();
}

// ═══════════════════════════════════════════════
// 2. PAYMENT CHANNELS
// ═══════════════════════════════════════════════

// ─── ADD / EDIT PAYMENT CHANNEL ───
if (in_array($sub_action, ['add_payment_channel', 'edit_payment_channel'])) {
    $id = (int) ($_POST['channel_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $icon_class = trim($_POST['icon_class'] ?? '');
    $fee_percent = (float) ($_POST['fee_percent'] ?? 0);
    $sort_order = (int) ($_POST['sort_order'] ?? 0);

    $errors = [];
    if (empty($name))
        $errors[] = "กรุณากรอกชื่อช่องทาง";
    if (!in_array($type, ['qr_promptpay', 'bank_transfer', 'credit_card', 'cash']))
        $errors[] = "ประเภทช่องทางไม่ถูกต้อง";

    // Nullify empty banking fields
    $bank_name = $bank_name ?: null;
    $account_name = $account_name ?: null;
    $account_number = $account_number ?: null;
    $icon_class = $icon_class ?: null;

    if (empty($errors)) {
        try {
            if ($sub_action === 'add_payment_channel') {
                $stmt = $pdo->prepare("INSERT INTO payment_channels (type, name, bank_name, account_name, account_number, icon_class, fee_percent, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$type, $name, $bank_name, $account_name, $account_number, $icon_class, $fee_percent, $sort_order]);
                $_SESSION['msg_success'] = "เพิ่มช่องทางชำระเงินสำเร็จ";
            } else {
                if ($id <= 0)
                    throw new Exception("ID ไม่ถูกต้อง");
                $stmt = $pdo->prepare("UPDATE payment_channels SET type=?, name=?, bank_name=?, account_name=?, account_number=?, icon_class=?, fee_percent=?, sort_order=? WHERE id=?");
                $stmt->execute([$type, $name, $bank_name, $account_name, $account_number, $icon_class, $fee_percent, $sort_order, $id]);
                $_SESSION['msg_success'] = "แก้ไขช่องทางชำระเงินสำเร็จ";
            }
        } catch (Exception $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        }
    } else {
        $_SESSION['msg_error'] = implode("<br>", $errors);
    }
    header("Location: " . $return_url);
    exit();
}

// ─── TOGGLE ACTIVE PAYMENT CHANNEL ───
if ($sub_action === 'toggle_payment_channel') {
    $id = (int) ($_POST['channel_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE payment_channels SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $_SESSION['msg_success'] = "ปรับปรุงสถานะเรียบร้อยแล้ว";
    }
    header("Location: " . $return_url);
    exit();
}

// ─── DELETE PAYMENT CHANNEL ───
if ($sub_action === 'delete_payment_channel') {
    $id = (int) ($_POST['channel_id'] ?? 0);

    // Check if channel is used in payments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE payment_channel_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['msg_error'] = "ไม่สามารถลบช่องทางนี้ได้เนื่องจากถูกใช้งานในประวัติการชำระเงินแล้ว ให้ใช้วิธีปิดสถานะแทน";
    } else {
        $stmt = $pdo->prepare("DELETE FROM payment_channels WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['msg_success'] = "ลบช่องทางชำระเงินสำเร็จ";
    }
    header("Location: " . $return_url);
    exit();
}

// ═══════════════════════════════════════════════
// 3. SEASONAL PRICING
// ═══════════════════════════════════════════════

// ─── ADD / EDIT SEASONAL PRICING ───
if (in_array($sub_action, ['add_seasonal_pricing', 'edit_seasonal_pricing'])) {
    $id = (int) ($_POST['seasonal_id'] ?? 0);
    $season_name = trim($_POST['season_name'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $price_multiplier_percent = (float) ($_POST['price_multiplier_percent'] ?? 0);

    $errors = [];
    if (empty($season_name))
        $errors[] = "กรุณากรอกชื่อช่วงเทศกาล";
    if (empty($start_date) || empty($end_date))
        $errors[] = "กรุณาระบุวันที่เริ่มต้นและสิ้นสุด";
    elseif (strtotime($start_date) > strtotime($end_date))
        $errors[] = "วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มต้น";

    if (empty($errors)) {
        try {
            if ($sub_action === 'add_seasonal_pricing') {
                $stmt = $pdo->prepare("INSERT INTO seasonal_pricings (season_name, start_date, end_date, price_multiplier_percent, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$season_name, $start_date, $end_date, $price_multiplier_percent]);
                $_SESSION['msg_success'] = "เพิ่มช่วงเวลาเทศกาลสำเร็จ";
            } else {
                $stmt = $pdo->prepare("UPDATE seasonal_pricings SET season_name=?, start_date=?, end_date=?, price_multiplier_percent=? WHERE id=?");
                $stmt->execute([$season_name, $start_date, $end_date, $price_multiplier_percent, $id]);
                $_SESSION['msg_success'] = "แก้ไขช่วงเวลาเทศกาลสำเร็จ";
            }
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    } else {
        $_SESSION['msg_error'] = implode("<br>", $errors);
    }
    header("Location: " . $return_url);
    exit();
}

// ─── TOGGLE / DELETE SEASONAL PRICING ───
if ($sub_action === 'toggle_seasonal_pricing') {
    $id = (int) ($_POST['seasonal_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE seasonal_pricings SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $_SESSION['msg_success'] = "ปรับปรุงสถานะเรียบร้อยแล้ว";
    }
    header("Location: " . $return_url);
    exit();
}

if ($sub_action === 'delete_seasonal_pricing') {
    $id = (int) ($_POST['seasonal_id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM seasonal_pricings WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['msg_success'] = "ลบช่วงเวลาเทศกาลสำเร็จ";
    }
    header("Location: " . $return_url);
    exit();
}

// ═══════════════════════════════════════════════
// 4. LOOKUP TABLES
// ═══════════════════════════════════════════════

// ─── ADD / EDIT LOOKUP ───
if (in_array($sub_action, ['add_lookup', 'edit_lookup'])) {
    $id = (int) ($_POST['lookup_id'] ?? 0);
    $table = trim($_POST['table_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $icon_class = trim($_POST['icon_class'] ?? ''); // only for daily_update_types

    $valid_tables = ['medical_record_types', 'daily_update_types', 'care_task_types', 'species', 'breeds', 'vaccine_types', 'amenities'];

    if (!in_array($table, $valid_tables) || empty($name)) {
        $_SESSION['msg_error'] = "ข้อมูลไม่ถูกต้อง";
    } else {
        try {
            if ($sub_action === 'add_lookup') {
                if ($table === 'daily_update_types') {
                    $stmt = $pdo->prepare("INSERT INTO daily_update_types (name, icon_class, is_active) VALUES (?, ?, 1)");
                    $stmt->execute([$name, $icon_class ?: null]);
                } elseif ($table === 'breeds') {
                    $species_id = (int) ($_POST['species_id'] ?? 0);
                    $stmt = $pdo->prepare("INSERT INTO breeds (name, species_id) VALUES (?, ?)");
                    $stmt->execute([$name, $species_id]);
                } elseif ($table === 'vaccine_types') {
                    $species_id = (int) ($_POST['species_id'] ?? 0);
                    $stmt = $pdo->prepare("INSERT INTO vaccine_types (name, species_id, is_active) VALUES (?, ?, 1)");
                    $stmt->execute([$name, $species_id]);
                } elseif ($table === 'amenities') {
                    $stmt = $pdo->prepare("INSERT INTO amenities (name, icon_class) VALUES (?, ?)");
                    $stmt->execute([$name, $icon_class ?: null]);
                } elseif ($table === 'species') {
                    $stmt = $pdo->prepare("INSERT INTO species (name) VALUES (?)");
                    $stmt->execute([$name]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO {$table} (name, is_active) VALUES (?, 1)");
                    $stmt->execute([$name]);
                }
                $_SESSION['msg_success'] = "เพิ่มข้อมูลสำเร็จ";
            } else {
                if ($table === 'daily_update_types') {
                    $stmt = $pdo->prepare("UPDATE daily_update_types SET name=?, icon_class=? WHERE id=?");
                    $stmt->execute([$name, $icon_class ?: null, $id]);
                } elseif ($table === 'breeds') {
                    $species_id = (int) ($_POST['species_id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE breeds SET name=?, species_id=? WHERE id=?");
                    $stmt->execute([$name, $species_id, $id]);
                } elseif ($table === 'vaccine_types') {
                    $species_id = (int) ($_POST['species_id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE vaccine_types SET name=?, species_id=? WHERE id=?");
                    $stmt->execute([$name, $species_id, $id]);
                } elseif ($table === 'amenities') {
                    $stmt = $pdo->prepare("UPDATE amenities SET name=?, icon_class=? WHERE id=?");
                    $stmt->execute([$name, $icon_class ?: null, $id]);
                } elseif ($table === 'species') {
                    $stmt = $pdo->prepare("UPDATE species SET name=? WHERE id=?");
                    $stmt->execute([$name, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE {$table} SET name=? WHERE id=?");
                    $stmt->execute([$name, $id]);
                }
                $_SESSION['msg_success'] = "แก้ไขข้อมูลสำเร็จ";
            }
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
    header("Location: " . $return_url);
    exit();
}

// ─── TOGGLE LOOKUP ───
if ($sub_action === 'toggle_lookup') {
    $id = (int) ($_POST['lookup_id'] ?? 0);
    $table = trim($_POST['table_name'] ?? '');
    $new_status = (int) ($_POST['new_status'] ?? 0);

    $valid_tables = ['medical_record_types', 'daily_update_types', 'care_task_types', 'vaccine_types'];

    if (in_array($table, $valid_tables) && $id > 0) {
        $stmt = $pdo->prepare("UPDATE {$table} SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $_SESSION['msg_success'] = "ปรับปรุงสถานะเรียบร้อยแล้ว";
    } else {
        $_SESSION['msg_error'] = "ตารางนี้ไม่สามารถเปลี่ยนแปลงสถานะได้";
    }
    header("Location: " . $return_url);
    exit();
}

// Fallback
$_SESSION['msg_error'] = "คำสั่งไม่ถูกต้อง";
header("Location: ?page=settings");
exit();
