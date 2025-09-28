<?php
require_once __DIR__.'/../middleware/auth_required.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$_SESSION['user']['id']]);
$bookings = $stmt->fetchAll();
?>
<h3 class="mb-3">การจองของฉัน</h3>
<?php if (!$bookings): ?>
  <div class="alert alert-info">ยังไม่มีการจอง</div>
<?php else: foreach ($bookings as $bk): ?>
  <div class="card card-hover mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div><b>PNR:</b> <span class="badge <?= $bk['pnr_code'] ? 'text-bg-success':'text-bg-secondary' ?>"><?= $bk['pnr_code'] ?: '-' ?></span>
            · <b>สถานะ:</b>
            <span class="badge <?= $bk['status']==='confirmed'?'text-bg-success':($bk['status']==='pending'?'text-bg-warning text-dark':'text-bg-danger') ?>">
              <?= $bk['status'] ?>
            </span>
          </div>
          <div class="text-muted small">จองเมื่อ: <?= $bk['created_at'] ?></div>
        </div>
        <div class="text-end">
          <div class="fw-bold"><?= number_format($bk['total_amount'],2) ?> THB</div>
          <?php if ($bk['status']==='pending'): ?>
            <a class="btn btn-primary btn-sm mt-2" href="payment.php?booking_id=<?= $bk['id'] ?>">ชำระเงิน</a>
          <?php endif; ?>
        </div>
      </div>
      <?php
        $items = $pdo->prepare("SELECT bi.*, f.flight_no, ao.iata_code origin, ad.iata_code dest, fi.departure_at
          FROM booking_items bi
          JOIN flight_instances fi ON bi.flight_instance_id=fi.id
          JOIN flights f ON fi.flight_id=f.id
          JOIN airports ao ON f.origin_airport_id=ao.id
          JOIN airports ad ON f.dest_airport_id=ad.id
          WHERE bi.booking_id=?");
        $items->execute([$bk['id']]);
      ?>
      <ul class="list-group mt-3">
        <?php foreach ($items as $it): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= $it['flight_no'] ?> (<?= $it['origin'] ?>→<?= $it['dest'] ?>) · <?= $it['class'] ?> · <?= date('d M Y, H:i', strtotime($it['departure_at'])) ?></span>
            <span><?= number_format($it['price'],2) ?> THB</span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endforeach; endif; ?>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
