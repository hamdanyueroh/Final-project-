<?php
require_once __DIR__.'/../middleware/auth_required.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/csrf.php';
require_once __DIR__.'/../config/db.php';

$fi   = (int)($_GET['fi'] ?? 0);
$cls  = $_GET['class'] ?? 'ECONOMY';
$pax  = max(1,(int)($_GET['pax'] ?? 1));

// รับ seat_ids ที่มาจากหน้า select_seats.php (ถ้ามี)
$seat_ids = (isset($_GET['seat_ids']) && is_array($_GET['seat_ids']))
  ? array_values(array_map('intval', $_GET['seat_ids']))
  : [];

$fare = $pdo->prepare("SELECT price FROM fares WHERE flight_instance_id=? AND class=?");
$fare->execute([$fi,$cls]);
$fareRow = $fare->fetch();
if (!$fareRow) { echo "<div class='alert alert-danger'>ไม่พบราคา/คลาส</div>"; require_once __DIR__.'/../includes/footer.php'; exit; }
$unit  = (float)$fareRow['price'];
$total = $unit*$pax;

// ดึงเลขที่นั่งไว้โชว์ (เฉพาะกรณีมี seat_ids)
$seatNos = [];
if ($seat_ids) {
  $in = implode(',', array_fill(0, count($seat_ids), '?'));
  $q  = $pdo->prepare("SELECT seat_no FROM seat_inventory WHERE id IN ($in)");
  $q->execute($seat_ids);
  $seatNos = array_column($q->fetchAll(), 'seat_no');
}
?>
<div class="stepper">
  <div class="step done"><span class="dot">1</span><span class="label">ค้นหา</span></div>
  <div class="step done"><span class="dot">2<i class="bi bi-check"></i></span><span class="label">เลือกเที่ยวบิน</span></div>
  <div class="step done"><span class="dot">3<i class="bi bi-check"></i></span><span class="label">เลือกชั้นโดยสาร</span></div>
  <div class="step <?= $seat_ids ? 'done' : 'todo' ?>"><span class="dot">4<?= $seat_ids ? '<i class="bi bi-check"></i>' : '4' ?></span><span class="label">เลือกที่นั่ง</span></div>
  <div class="step cur"><span class="dot"><?= $seat_ids ? '5' : '4' ?></span><span class="label">ผู้โดยสาร</span></div>
  <div class="step todo"><span class="dot"><?= $seat_ids ? '6' : '5' ?></span><span class="label">ชำระเงิน</span></div>
</div>

<?php if ($seat_ids && count($seat_ids) !== $pax): ?>
  <div class="alert alert-warning">คุณเลือกที่นั่งไว้ <b><?= count($seat_ids) ?></b> จากที่ต้องการ <b><?= $pax ?></b> ที่นั่ง – ระบบจะจัดที่นั่งส่วนที่เหลือให้อัตโนมัติ</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">ข้อมูลผู้โดยสาร (<?= $pax ?> คน)</h5>

        <form method="post" action="../actions/add_to_booking.php" id="passengerForm">
          <?= csrf_field() ?>
          <input type="hidden" name="fi" value="<?= $fi ?>">
          <input type="hidden" name="class" value="<?= htmlspecialchars($cls) ?>">
          <input type="hidden" name="pax" value="<?= $pax ?>">
          <input type="hidden" name="unit_price" value="<?= $unit ?>">

          <?php // ส่ง seat_ids กลับไปยัง action (ถ้ามี)
          foreach ($seat_ids as $sid): ?>
            <input type="hidden" name="seat_ids[]" value="<?= (int)$sid ?>">
          <?php endforeach; ?>

          <?php for ($i=1;$i<=$pax;$i++): ?>
            <div class="row g-2 align-items-end mb-2">
              <div class="col-sm-6">
                <label class="form-label">ชื่อจริง</label>
                <input name="fname[]" class="form-control" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label">นามสกุล</label>
                <input name="lname[]" class="form-control" required>
              </div>
            </div>
          <?php endfor; ?>

          <div class="form-text text-muted">* ชื่อ-นามสกุลควรตรงกับหนังสือเดินทาง</div>
          <button class="btn btn-primary mt-3">ไปชำระเงิน</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h6 class="fw-bold mb-2">สรุปค่าโดยสาร</h6>

        <div class="d-flex justify-content-between">
          <span><?= htmlspecialchars($cls) ?> x <?= $pax ?></span>
          <span><?= number_format($unit,2) ?> THB</span>
        </div>

        <?php if ($seatNos): ?>
          <div class="mt-2 small text-muted">
            ที่นั่งที่เลือก:
            <div class="mt-1">
              <?php foreach ($seatNos as $no): ?>
                <span class="badge text-bg-secondary me-1"><?= htmlspecialchars($no) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <hr>
        <div class="d-flex justify-content-between fw-bold">
          <span>รวมทั้งสิ้น</span>
          <span><?= number_format($total,2) ?> THB</span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
