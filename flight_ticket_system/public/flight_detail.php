<?php
require_once __DIR__.'/../middleware/auth_required.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

$fi_id = (int)($_GET['id'] ?? 0);
$pax   = max(1,(int)($_GET['pax'] ?? 1));

$st = $pdo->prepare("SELECT fi.id, f.flight_no, f.airline, ao.iata_code origin, ad.iata_code dest,
        fi.departure_at, fi.arrival_at,
        TIMESTAMPDIFF(MINUTE, fi.departure_at, fi.arrival_at) AS dur_min
        FROM flight_instances fi
        JOIN flights f ON fi.flight_id=f.id
        JOIN airports ao ON f.origin_airport_id=ao.id
        JOIN airports ad ON f.dest_airport_id=ad.id
        WHERE fi.id=?");
$st->execute([$fi_id]);
$flight = $st->fetch();
if (!$flight) { echo "<div class='alert alert-danger'>ไม่พบเที่ยวบิน</div>"; require_once __DIR__.'/../includes/footer.php'; exit; }

$fares = $pdo->prepare("SELECT class,fare_code,price,remaining_seats FROM fares WHERE flight_instance_id=? AND remaining_seats>=?");
$fares->execute([$fi_id,$pax]);

$h=floor($flight['dur_min']/60); $m=$flight['dur_min']%60;
?>
<div class="stepper">
  <div class="step done"><span class="dot">1</span><span class="label">ค้นหา</span></div>
  <div class="step done"><span class="dot">2<i class="bi bi-check"></i></span><span class="label">เลือกเที่ยวบิน</span></div>
  <div class="step cur"><span class="dot">3</span><span class="label">เลือกชั้นโดยสาร</span></div>
  <div class="step todo"><span class="dot">4</span><span class="label">เลือกที่นั่ง</span></div>
</div>

<div class="card mb-3">
  <div class="card-body d-flex flex-wrap justify-content-between">
    <div>
      <div class="fw-bold fs-5"><?= htmlspecialchars($flight['flight_no']) ?> · <?= $flight['origin'] ?> → <?= $flight['dest'] ?></div>
      <div class="text-muted">ออก: <?= date('d M Y, H:i', strtotime($flight['departure_at'])) ?> · ถึง: <?= date('d M Y, H:i', strtotime($flight['arrival_at'])) ?> · ระยะเวลา ~ <?= $h ?>ชม <?= $m ?>นาที</div>
      <span class="badge badge-soft mt-2"><?= htmlspecialchars($flight['airline']) ?></span>
    </div>
    <div class="text-end">
      <div class="small text-muted">ผู้โดยสาร</div>
      <div class="fs-5 fw-bold"><?= $pax ?> คน</div>
    </div>
  </div>
</div>

<h5 class="mb-2">เลือกชั้นโดยสาร</h5>
<?php foreach ($fares as $fare): ?>
  <div class="card card-hover mb-2">
    <div class="card-body d-flex justify-content-between align-items-center">
      <div>
        <div class="fw-semibold"><?= $fare['class'] ?> <small class="text-muted">· <?= htmlspecialchars($fare['fare_code'] ?: 'STD') ?></small></div>
        <div class="text-muted small">ที่นั่งเหลือ ~ <?= (int)$fare['remaining_seats'] ?></div>
      </div>
      <div class="text-end">
        <div class="fs-5 fw-bold"><?= number_format($fare['price'],2) ?> THB</div>

        <!-- 👇 เพิ่มสองปุ่มตรงนี้ -->
        <a href="select_seats.php?fi=<?= $fi_id ?>&class=<?= $fare['class'] ?>&pax=<?= $pax ?>"
           class="btn btn-outline-primary mt-2">เลือกที่นั่ง</a>
        <a href="checkout.php?fi=<?= $fi_id ?>&class=<?= $fare['class'] ?>&pax=<?= $pax ?>"
           class="btn btn-primary mt-2">ข้ามการเลือกที่นั่ง</a>
      </div>
    </div>
  </div>
<?php endforeach; if ($fares->rowCount()==0) echo "<div class='alert alert-warning'>จำนวนที่นั่งไม่พอ</div>"; ?>

<a class="btn btn-outline-secondary mt-3" href="javascript:history.back()">ย้อนกลับ</a>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
