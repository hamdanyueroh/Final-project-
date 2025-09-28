<?php
require_once __DIR__.'/../middleware/auth_required.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

$origin = strtoupper(trim($_GET['origin'] ?? ''));
$dest   = strtoupper(trim($_GET['dest'] ?? ''));
$date   = $_GET['date'] ?? '';
$pax    = max(1, (int)($_GET['pax'] ?? 1));

function dt($s){ return date('d M Y, H:i', strtotime($s)); }

$sql = "
SELECT fi.id as fi_id, f.flight_no, f.airline,
       ao.iata_code as origin, ad.iata_code as dest,
       fi.departure_at, fi.arrival_at,
       TIMESTAMPDIFF(MINUTE, fi.departure_at, fi.arrival_at) AS dur_min,
       MIN(fr.price) as min_price
FROM flight_instances fi
JOIN flights f ON fi.flight_id = f.id
JOIN airports ao ON f.origin_airport_id = ao.id
JOIN airports ad ON f.dest_airport_id = ad.id
JOIN fares fr ON fr.flight_instance_id = fi.id AND fr.remaining_seats >= :pax
WHERE ao.iata_code = :origin
  AND ad.iata_code = :dest
  AND DATE(fi.departure_at) = :d
  AND fi.status = 'scheduled'
GROUP BY fi.id
ORDER BY fi.departure_at ASC";

$stm = $pdo->prepare($sql);
$stm->execute([':origin'=>$origin, ':dest'=>$dest, ':d'=>$date, ':pax'=>$pax]);
$rows = $stm->fetchAll();
?>
<div class="stepper">
  <div class="step done"><span class="dot">1</span><span class="label">ค้นหา</span></div>
  <div class="step cur"><span class="dot">2</span><span class="label">เลือกเที่ยวบิน</span></div>
  <div class="step todo"><span class="dot">3</span><span class="label">ผู้โดยสาร</span></div>
  <div class="step todo"><span class="dot">4</span><span class="label">ชำระเงิน</span></div>
</div>

<h4 class="mb-3">เที่ยวบิน: <span class="badge text-bg-primary"><?= htmlspecialchars($origin) ?> → <?= htmlspecialchars($dest) ?></span>
  <small class="text-muted ms-2"><?= htmlspecialchars($date) ?> · ผู้โดยสาร <?= $pax ?></small>
</h4>

<?php if (!$rows): ?>
  <div class="alert alert-warning mt-3">ไม่พบเที่ยวบินที่ตรงเงื่อนไข</div>
<?php else: foreach ($rows as $r): $h = floor($r['dur_min']/60); $m=$r['dur_min']%60; ?>
  <div class="card card-hover mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-3">
        <span class="badge badge-soft"><?= htmlspecialchars($r['airline']) ?></span>
        <div>
          <div class="fw-bold"><?= htmlspecialchars($r['flight_no']) ?> · <?= $r['origin'] ?> → <?= $r['dest'] ?></div>
          <div class="text-muted small">ออก: <?= dt($r['departure_at']) ?> · ถึง: <?= dt($r['arrival_at']) ?> · ระยะเวลา ~ <?= $h ?>ชม <?= $m ?>นาที</div>
        </div>
      </div>
      <div class="text-end">
        <a class="btn btn-primary mt-2" href="flight_detail.php?id=<?= $r['fi_id'] ?>&pax=<?= $pax ?>">ดูรายละเอียด</a>
      </div>
    </div>
  </div>
<?php endforeach; endif; ?>

<a class="btn btn-outline-secondary mt-3" href="<?= BASE_URL ?>">ย้อนกลับ</a>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
