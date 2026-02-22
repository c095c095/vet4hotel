<?php
if (!isset($pdo)) {
    exit('No direct access allowed.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ?page=login");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$redirect_back = '?page=login';
if (!empty($_POST['redirect'])) {
    $redirect_back .= '&redirect=' . urlencode($_POST['redirect']);
}

if (empty($email) || empty($password)) {
    $_SESSION['error_msg'] = "กรุณากรอกอีเมลและรหัสผ่าน";
    header("Location: " . $redirect_back);
    exit();
}

try {
    // First check employees table
    $stmt = $pdo->prepare("SELECT id, email, password_hash, role, first_name, last_name, is_active FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee && password_verify($password, $employee['password_hash'])) {
        if ($employee['is_active']) {
            $_SESSION['employee_id'] = $employee['id'];
            $_SESSION['employee_role'] = $employee['role'];
            $_SESSION['user_name'] = $employee['first_name'] . ' ' . $employee['last_name'];

            header("Location: admin/");
            exit();
        } else {
            $_SESSION['error_msg'] = "บัญชีพนักงานนี้ถูกระงับการใช้งาน";
            header("Location: " . $redirect_back);
            exit();
        }
    } else {
        // If not employee, check customers table
        $stmt = $pdo->prepare("SELECT id, email, password_hash, first_name, last_name, is_active FROM customers WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer && password_verify($password, $customer['password_hash'])) {
            if ($customer['is_active']) {
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['user_name'] = $customer['first_name'] . ' ' . $customer['last_name'];

                // Redirect to the originally intended page or just home/profile
                $redirect = $_POST['redirect'] ?? '?page=home';
                if (empty($redirect)) {
                    $redirect = '?page=home';
                }

                header("Location: " . $redirect);
                exit();
            } else {
                $_SESSION['error_msg'] = "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อสต๊าฟ";
                header("Location: " . $redirect_back);
                exit();
            }
        } else {
            $_SESSION['error_msg'] = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
            header("Location: " . $redirect_back);
            exit();
        }
    }
} catch (PDOException $e) {
    $_SESSION['error_msg'] = "เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่อีกครั้ง";
    // error_log($e->getMessage());
    header("Location: " . $redirect_back);
    exit();
}
