<?php
require_once __DIR__.'/../middleware/auth_required.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id=? AND user_id=?");
$stmt->execute([$booking_id, $_SESSION['user']['id']]);
$bk = $stmt->fetch();
if (!$bk) { echo "<div class='alert alert-danger'>ไม่พบการจอง</div>"; require_once __DIR__.'/../includes/footer.php'; exit; }

$items = $pdo->prepare("SELECT bi.*, f.flight_no, ao.iata_code origin, ad.iata_code dest, fi.departure_at, fi.arrival_at
  FROM booking_items bi
  JOIN flight_instances fi ON bi.flight_instance_id=fi.id
  JOIN flights f ON fi.flight_id=f.id
  JOIN airports ao ON f.origin_airport_id=ao.id
  JOIN airports ad ON f.dest_airport_id=ad.id
  WHERE bi.booking_id=?");
$items->execute([$booking_id]);
?>
<div class="stepper">
  <div class="step done"><span class="dot">1</span><span class="label">ค้นหา</span></div>
  <div class="step done"><span class="dot">2<i class="bi bi-check"></i></span><span class="label">เลือกเที่ยวบิน</span></div>
  <div class="step done"><span class="dot">3<i class="bi bi-check"></i></span><span class="label">ผู้โดยสาร</span></div>
  <div class="step cur"><span class="dot">4</span><span class="label">ชำระเงิน</span></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">ชำระเงินการจอง #<?= $bk['id'] ?></h5>
        <ul class="list-group list-group-flush">
          <?php foreach ($items as $it): ?>
            <li class="list-group-item d-flex justify-content-between">
              <div>
                <div class="fw-semibold"><?= $it['flight_no'] ?> · <?= $it['origin'] ?> → <?= $it['dest'] ?></div>
                <div class="small text-muted">ออก: <?= date('d M Y, H:i', strtotime($it['departure_at'])) ?> · ถึง: <?= date('d M Y, H:i', strtotime($it['arrival_at'])) ?></div>
              </div>
              <div class="fw-bold"><?= number_format($it['price'],2) ?> THB</div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between"><span>ยอดสุทธิ</span><span class="fs-5 fw-bold"><?= number_format($bk['total_amount'],2) ?> THB</span></div>
        <a class="btn btn-success w-100 mt-3" href="../actions/confirm_payment.php?booking_id=<?= $booking_id ?>">
          ชำระเงินทันที
        </a>
        <div class="form-text mt-2">* ระบบจำลองจะออก PNR ทันทีเมื่อชำระสำเร็จ</div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
