<?php
// ═══════════════════════════════════════════════════════════
// RECEIPT — VET4 HOTEL (Admin/Staff)
// ═══════════════════════════════════════════════════════════

if (!isset($_SESSION['employee_id'])) {
    header("Location: ?page=login");
    exit();
}

$booking_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($booking_id <= 0) {
    header("Location: ?page=bookings");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, c.first_name, c.last_name, c.email, c.phone, c.address,
               p.code AS promo_code
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        LEFT JOIN promotions p ON b.promotion_id = p.id
        WHERE b.id = ? LIMIT 1
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) {
        $_SESSION['msg_error'] = 'ไม่พบข้อมูลการจอง';
        header("Location: ?page=bookings");
        exit();
    }

    $stmt = $pdo->prepare("SELECT bi.*, r.room_number, rt.name AS room_type_name FROM booking_items bi JOIN rooms r ON bi.room_id = r.id JOIN room_types rt ON r.room_type_id = rt.id WHERE bi.booking_id = ? ORDER BY bi.check_in_date ASC");
    $stmt->execute([$booking_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $item_pets_map = [];
    $ids = array_column($items, 'id');
    if (!empty($ids)) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT bip.booking_item_id, pet.name AS pet_name, sp.name AS species_name FROM booking_item_pets bip JOIN pets pet ON bip.pet_id = pet.id JOIN species sp ON pet.species_id = sp.id WHERE bip.booking_item_id IN ($ph)");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r)
            $item_pets_map[$r['booking_item_id']][] = $r;
    }

    $stmt = $pdo->prepare("SELECT bs.*, s.name AS service_name, s.charge_type, pet.name AS pet_name FROM booking_services bs JOIN services s ON bs.service_id = s.id LEFT JOIN pets pet ON bs.pet_id = pet.id WHERE bs.booking_id = ? ORDER BY bs.id ASC");
    $stmt->execute([$booking_id]);
    $all_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $item_services_map = [];
    $general_services = [];
    foreach ($all_services as $svc) {
        if ($svc['booking_item_id'])
            $item_services_map[$svc['booking_item_id']][] = $svc;
        else
            $general_services[] = $svc;
    }

    $stmt = $pdo->prepare("SELECT pay.*, pc.name AS channel_name, e.first_name AS verifier_name FROM payments pay LEFT JOIN payment_channels pc ON pay.payment_channel_id = pc.id LEFT JOIN employees e ON pay.verified_by_employee_id = e.id WHERE pay.booking_id = ? AND pay.status = 'verified' ORDER BY pay.created_at ASC");
    $stmt->execute([$booking_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['msg_error'] = 'เกิดข้อผิดพลาด';
    header("Location: ?page=bookings");
    exit();
}

// Helpers
function adm_rcpt_thai_date($d)
{
    if (!$d)
        return '-';
    $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $t = strtotime($d);
    return (int) date('j', $t) . ' ' . $m[(int) date('n', $t)] . ' ' . ((int) date('Y', $t) + 543);
}
function adm_rcpt_nights($a, $b)
{
    return max(1, (int) ((strtotime($b) - strtotime($a)) / 86400));
}

$total_room = 0;
$total_svc = 0;
$total_paid = 0;
foreach ($items as $i)
    $total_room += (float) $i['subtotal'];
foreach ($item_services_map as $ss)
    foreach ($ss as $s)
        $total_svc += (float) $s['total_price'];
foreach ($general_services as $s)
    $total_svc += (float) $s['total_price'];
foreach ($payments as $p)
    $total_paid += (float) $p['amount'];
$earliest_cin = !empty($items) ? min(array_column($items, 'check_in_date')) : null;
$latest_cout = !empty($items) ? max(array_column($items, 'check_out_date')) : null;
$line_no = 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ใบเสร็จรับเงิน <?php echo sanitize($booking['booking_ref']); ?> | Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Sarabun', sans-serif; background: #ddd; color: #111; font-size: 13px; line-height: 1.55; }

.no-print { text-align: center; padding: 14px; background: #333; }
.no-print button, .no-print a { display: inline-flex; align-items: center; gap: 6px; padding: 8px 20px; border: none; border-radius: 6px; font: 600 13px 'Sarabun', sans-serif; cursor: pointer; text-decoration: none; color: #333; background: #fff; margin: 0 4px; }
.no-print .bp { background: #2563eb; color: #fff; }

.page { width: 210mm; margin: 20px auto; padding: 16mm 20mm; background: #fff; box-shadow: 0 1px 12px rgba(0,0,0,.12); }

.hdr { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 14px; }
.hdr h1 { font-size: 20px; font-weight: 700; margin: 0; }
.hdr .en { font-size: 11px; color: #666; }
.hdr .doc { font-size: 17px; font-weight: 700; margin-top: 6px; letter-spacing: 2px; }
.hdr .doc-en { font-size: 10px; color: #888; letter-spacing: 1px; }

.info-tbl { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 12.5px; }
.info-tbl td { padding: 3px 0; vertical-align: top; }
.info-tbl .lbl { color: #666; width: 120px; }

table.tbl { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-bottom: 0; }
table.tbl th { background: #111; color: #fff; padding: 6px 8px; text-align: left; font-weight: 600; font-size: 11px; letter-spacing: .3px; }
table.tbl th.r { text-align: right; }
table.tbl th.c { text-align: center; }
table.tbl td { padding: 6px 8px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
table.tbl td.r { text-align: right; }
table.tbl td.c { text-align: center; }
table.tbl tbody tr:last-child td { border-bottom: 2px solid #111; }
.sub { font-size: 11px; color: #888; }

.sum-wrap { display: flex; margin-top: 0; }
.sum-wrap .notes { flex: 1; font-size: 11px; color: #888; padding-top: 8px; }
.sum-wrap .sbox { width: 240px; }
.sr { display: flex; justify-content: space-between; padding: 4px 8px; font-size: 12.5px; }
.sr.bt { border-top: 1px solid #ccc; }
.sr.gt { background: #111; color: #fff; font-weight: 700; font-size: 14px; padding: 6px 8px; margin-top: 2px; }
.sr.paid { color: #16a34a; font-weight: 600; }
.sr.owe { color: #dc2626; font-weight: 600; }

.pay { margin-top: 18px; }
.pay-hd { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #666; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 4px; }
table.ptbl { width: 100%; border-collapse: collapse; font-size: 12px; }
table.ptbl th { background: #f5f5f5; padding: 5px 8px; text-align: left; font-weight: 600; font-size: 11px; border-bottom: 1px solid #ddd; }
table.ptbl th.r { text-align: right; }
table.ptbl th.c { text-align: center; }
table.ptbl td { padding: 5px 8px; border-bottom: 1px solid #eee; }
table.ptbl td.r { text-align: right; }
table.ptbl td.c { text-align: center; }

.ftr { margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
.ftr .ty { font-size: 12px; color: #888; }
.sign { text-align: center; width: 180px; }
.sign-line { border-top: 1px solid #333; margin-top: 48px; padding-top: 3px; font-size: 11px; color: #666; }
.wm { text-align: center; margin-top: 16px; padding-top: 10px; border-top: 1px dashed #ccc; font-size: 10px; color: #bbb; }

@media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .page { box-shadow: none; margin: 0; width: 100%; padding: 8mm 14mm; }
    table.tbl th { background: #111 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sr.gt { background: #111 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    table.ptbl th { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { margin: 6mm; size: A4; }
}
@media (max-width: 800px) {
    .page { width: 100%; padding: 14px; min-height: auto; }
    .sum-wrap { flex-direction: column; }
    .sum-wrap .sbox { width: 100%; }
}
</style>
</head>
<body>

<div class="no-print">
    <a href="?page=booking_detail&id=<?php echo $booking_id; ?>">กลับ</a>
    <button class="bp" onclick="window.print();">พิมพ์ / บันทึก PDF</button>
</div>

<div class="page">

    <!-- Header -->
    <div class="hdr">
        <h1><?php echo SITE_NAME; ?></h1>
        <div class="en">VET 4 PET HOTEL</div>
        <div class="doc">ใบเสร็จรับเงิน</div>
        <div class="doc-en">RECEIPT</div>
    </div>

    <!-- Info -->
    <table class="info-tbl">
        <tr>
            <td class="lbl">เลขที่ / No.</td>
            <td><strong><?php echo sanitize($booking['booking_ref']); ?></strong></td>
            <td class="lbl" style="width:100px;">วันที่ออก</td>
            <td><?php echo adm_rcpt_thai_date(date('Y-m-d')); ?></td>
        </tr>
        <tr>
            <td class="lbl">ชื่อลูกค้า</td>
            <td><?php echo sanitize($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
            <td class="lbl">โทร</td>
            <td><?php echo sanitize($booking['phone']); ?></td>
        </tr>
        <?php if ($booking['address']): ?>
            <tr>
                <td class="lbl">ที่อยู่</td>
                <td colspan="3"><?php echo sanitize($booking['address']); ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="lbl">เช็คอิน</td>
            <td><?php echo adm_rcpt_thai_date($earliest_cin); ?></td>
            <td class="lbl">เช็คเอาท์</td>
            <td><?php echo adm_rcpt_thai_date($latest_cout); ?></td>
        </tr>
    </table>

    <!-- Items -->
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:32px">ลำดับ</th>
                <th>รายการ</th>
                <th class="c" style="width:55px">จำนวน</th>
                <th class="r" style="width:80px">ราคา/หน่วย</th>
                <th class="r" style="width:90px">จำนวนเงิน</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item):
                $line_no++;
                $nights = adm_rcpt_nights($item['check_in_date'], $item['check_out_date']);
                $pets = $item_pets_map[$item['id']] ?? [];
                ?>
                <tr>
                    <td class="c"><?php echo $line_no; ?></td>
                    <td>
                        ค่าห้องพัก <?php echo sanitize($item['room_type_name']); ?> (ห้อง <?php echo sanitize($item['room_number']); ?>)
                        <div class="sub"><?php echo adm_rcpt_thai_date($item['check_in_date']); ?> - <?php echo adm_rcpt_thai_date($item['check_out_date']); ?>
                        <?php if (!empty($pets)): ?> | <?php echo implode(', ', array_map(fn($p) => sanitize($p['pet_name']), $pets)); ?><?php endif; ?></div>
                    </td>
                    <td class="c"><?php echo $nights; ?> คืน</td>
                    <td class="r"><?php echo number_format($item['locked_unit_price'], 2); ?></td>
                    <td class="r"><?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>

            <?php
            $all_svc = [];
            foreach ($item_services_map as $ss)
                foreach ($ss as $s)
                    $all_svc[] = $s;
            foreach ($general_services as $s)
                $all_svc[] = $s;
            foreach ($all_svc as $svc):
                $line_no++;
                ?>
                <tr>
                    <td class="c"><?php echo $line_no; ?></td>
                    <td>
                        <?php echo sanitize($svc['service_name']); ?>
                        <?php if ($svc['pet_name']): ?><span class="sub"> (<?php echo sanitize($svc['pet_name']); ?>)</span><?php endif; ?>
                    </td>
                    <td class="c"><?php echo $svc['quantity']; ?></td>
                    <td class="r"><?php echo number_format($svc['locked_unit_price'], 2); ?></td>
                    <td class="r"><?php echo number_format($svc['total_price'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="sum-wrap">
        <div class="notes">
            จำนวน <?php echo $line_no; ?> รายการ
            <?php if (!empty($booking['special_requests'])): ?>
                    <br>หมายเหตุ: <?php echo sanitize($booking['special_requests']); ?>
            <?php endif; ?>
        </div>
        <div class="sbox">
            <?php if ($total_svc > 0): ?>
                <div class="sr"><span>รวมค่าห้องพัก</span><span><?php echo number_format($total_room, 2); ?></span></div>
                <div class="sr"><span>รวมบริการเสริม</span><span><?php echo number_format($total_svc, 2); ?></span></div>
            <?php endif; ?>
            <div class="sr bt"><span>ยอดรวม</span><span><?php echo number_format($booking['subtotal_amount'], 2); ?></span></div>
            <?php if ((float) $booking['discount_amount'] > 0): ?>
                <div class="sr" style="color:#16a34a"><span>ส่วนลด<?php if ($booking['promo_code']): ?> (<?php echo sanitize($booking['promo_code']); ?>)<?php endif; ?></span><span>-<?php echo number_format($booking['discount_amount'], 2); ?></span></div>
            <?php endif; ?>
            <div class="sr gt"><span>ยอดสุทธิ</span><span><?php echo number_format($booking['net_amount'], 2); ?> บาท</span></div>
            <?php if ($total_paid > 0): ?>
                <div class="sr paid"><span>ชำระแล้ว</span><span><?php echo number_format($total_paid, 2); ?></span></div>
                <?php $bal = (float) $booking['net_amount'] - $total_paid;
                if ($bal > 0.01): ?>
                    <div class="sr owe"><span>ค้างชำระ</span><span><?php echo number_format($bal, 2); ?></span></div>
                <?php endif; endif; ?>
        </div>
    </div>

    <!-- Payments -->
    <?php if (!empty($payments)): ?>
        <div class="pay">
            <div class="pay-hd">รายการชำระเงิน</div>
            <table class="ptbl">
                <thead><tr><th>#</th><th>ประเภท</th><th>ช่องทาง</th><th class="c">วันที่</th><th class="r">จำนวนเงิน</th><th>ผู้ตรวจสอบ</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $pi => $pay): ?>
                    <tr>
                        <td><?php echo $pi + 1; ?></td>
                        <td><?php echo match ($pay['payment_type']) { 'deposit' => 'มัดจำ', 'full_payment' => 'ชำระเต็มจำนวน', 'balance_due' => 'ชำระส่วนที่เหลือ', 'extra_charge' => 'ค่าบริการเพิ่มเติม', default => $pay['payment_type']}; ?></td>
                        <td><?php echo $pay['channel_name'] ? sanitize($pay['channel_name']) : '-'; ?></td>
                        <td class="c"><?php echo adm_rcpt_thai_date($pay['paid_at'] ?? $pay['created_at']); ?></td>
                        <td class="r"><?php echo number_format($pay['amount'], 2); ?></td>
                        <td><?php echo $pay['verifier_name'] ? sanitize($pay['verifier_name']) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="ftr">
        <div class="ty">ขอบคุณที่ใช้บริการ <?php echo SITE_NAME; ?></div>
        <div class="sign">
            <div class="sign-line">ผู้รับเงิน / Received by</div>
        </div>
    </div>

    <div class="wm">เอกสารออกโดยระบบอัตโนมัติ | <?php echo SITE_NAME; ?> | <?php echo adm_rcpt_thai_date(date('Y-m-d')); ?> | Staff #<?php echo $_SESSION['employee_id']; ?></div>
</div>

</body>
</html>