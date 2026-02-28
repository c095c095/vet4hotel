<?php
// ═══════════════════════════════════════════════════════════
// PET TRANSPORTATION PROCESSOR - VET4 HOTEL ADMIN
// Handles assigning drivers and updating taxi status
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_action = $_POST['sub_action'] ?? '';

    if ($sub_action === 'update_status') {
        $transport_id = intval($_POST['transport_id']);
        $status = trim($_POST['status']);

        $allowed_statuses = ['pending', 'assigned', 'in_transit', 'completed', 'cancelled'];

        if (in_array($status, $allowed_statuses)) {
            try {
                // If updating to assigned, we might also get driver info
                $driver_name = $_POST['driver_name'] ?? null;
                $driver_phone = $_POST['driver_phone'] ?? null;

                $query = "UPDATE pet_transportation SET status = ?";
                $params = [$status];

                if ($status === 'assigned' && $driver_name) {
                    $query .= ", driver_name = ?, driver_phone = ?";
                    $params[] = $driver_name;
                    $params[] = $driver_phone;
                }

                $query .= " WHERE id = ?";
                $params[] = $transport_id;

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);

                $_SESSION['msg_success'] = "อัปเดตสถานะการรับ-ส่งสำเร็จ";
            } catch (PDOException $e) {
                $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        } else {
            $_SESSION['msg_error'] = "สถานะไม่ถูกต้อง";
        }
    } elseif ($sub_action === 'assign_driver') {
        $transport_id = intval($_POST['transport_id']);
        $driver_name = trim($_POST['driver_name']);
        $driver_phone = trim($_POST['driver_phone']);

        try {
            $stmt = $pdo->prepare("UPDATE pet_transportation SET driver_name = ?, driver_phone = ?, status = 'assigned' WHERE id = ?");
            $stmt->execute([$driver_name, $driver_phone, $transport_id]);
            $_SESSION['msg_success'] = "มอบหมายคนขับสำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }

    header("Location: ?page=pet_transportation");
    exit();
}
