<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../middleware/auth_required.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);

// ตรวจสอบความเป็นเจ้าของ
$bk = $pdo->prepare("SELECT * FROM bookings WHERE id=? AND user_id=?");
$bk->execute([$booking_id, $_SESSION['user']['id']]);
$booking = $bk->fetch();
if (!$booking || $booking['status']!=='pending') die('ไม่พบการจองหรือสถานะไม่ถูกต้อง');

$pdo->beginTransaction();
try {
  // อัปเดต payment
  $pdo->prepare("UPDATE payments SET status='paid', paid_at=NOW(), ref_code=UUID() WHERE booking_id=?")
      ->execute([$booking_id]);

  // สร้าง PNR 6 ตัวอักษร
  $pnr = strtoupper(substr(bin2hex(random_bytes(4)),0,6));

  // อัปเดต booking
  $pdo->prepare("UPDATE bookings SET status='confirmed', pnr_code=? WHERE id=?")
      ->execute([$pnr,$booking_id]);

  $pdo->commit();
  header('Location: '.BASE_URL.'success.php?pnr='.$pnr);
} catch (Exception $e) {
  $pdo->rollBack();
  die('ชำระเงินไม่สำเร็จ: '.$e->getMessage());
}
