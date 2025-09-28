<?php
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../middleware/auth_required.php';

$pnr = $_GET['pnr'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE pnr_code=? AND user_id=?");
$stmt->execute([$pnr, $_SESSION['user']['id']]);
$bk = $stmt->fetch();
if (!$bk) { echo "<div class='alert alert-danger'>ไม่พบข้อมูลตั๋ว</div>"; require_once __DIR__.'/../includes/footer.php'; exit; }
?>
<div class="text-center my-5">
  <h2 class="mb-3">ชำระเงินสำเร็จ 🎉</h2>
  <p>รหัสการจอง (PNR): <span class="badge text-bg-success fs-5 px-3 py-2"><?= htmlspecialchars($bk['pnr_code']) ?></span></p>
  <p class="mb-4">ยอดรวม: <b><?= number_format($bk['total_amount'],2) ?> THB</b></p>
  <a class="btn btn-primary" href="my_bookings.php">ดูการจองของฉัน</a>
  <a class="btn btn-outline-secondary ms-2" href="<?= BASE_URL ?>">กลับหน้าแรก</a>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
