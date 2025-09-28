<?php
require_once __DIR__.'/../middleware/auth_required.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

$fi   = (int)($_GET['fi'] ?? 0);
$cls  = strtoupper($_GET['class'] ?? 'ECONOMY');
$pax  = max(1,(int)($_GET['pax'] ?? 1));

/* ดึงคอนฟิกเครื่องบิน */
$cfgQ = $pdo->prepare("
  SELECT ac.seat_rows, ac.seats_per_row, ac.first_rows, ac.business_rows
  FROM flight_instances fi
  JOIN flights f   ON fi.flight_id=f.id
  JOIN aircrafts ac ON f.aircraft_id=ac.id
  WHERE fi.id=?
");
$cfgQ->execute([$fi]);
$cfg = $cfgQ->fetch();

if (!$cfg) {
  echo "<div class='alert alert-danger'>ไม่พบผังที่นั่ง</div>";
  require_once __DIR__.'/../includes/footer.php'; exit;
}

/* ตัวอักษรที่นั่งตามคอลัมน์ */
$letters = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 1);
$perRow  = max(1, (int)$cfg['seats_per_row']);
$letters = array_slice($letters, 0, $perRow);

/* ดึงรายการที่นั่งของไฟลท์นี้ตามคลาส */
$seatQ = $pdo->prepare("
  SELECT id, seat_no, class, is_booked
  FROM seat_inventory
  WHERE flight_instance_id=? AND class=?
  ORDER BY seat_no
");
$seatQ->execute([$fi, $cls]);
$seats = $seatQ->fetchAll();

if (!$seats) {
  echo "<div class='alert alert-warning'>
          ไม่มีที่นั่งของคลาส <b>".htmlspecialchars($cls)."</b> สำหรับเที่ยวบินนี้
        </div>
        <a href='javascript:history.back()' class='btn btn-outline-secondary'>ย้อนกลับ</a>";
  require_once __DIR__.'/../includes/footer.php'; exit;
}

/* map เป็น row => letter => seat */
$map = [];
foreach ($seats as $s) {
  if (!preg_match('/^(\d+)([A-Z])$/', $s['seat_no'], $m)) continue;
  $r=(int)$m[1]; $ch=$m[2];
  $map[$r][$ch] = $s;
}

/* ค่าช่วงแถวของแต่ละคลาสจากคอนฟิกเครื่อง */
$firstRows = (int)$cfg['first_rows'];
$bizRows   = (int)$cfg['business_rows'];
$totalRows = (int)$cfg['seat_rows'];

function rowBelongsToClass(int $row, string $cls, int $firstRows, int $bizRows): bool {
  if ($cls==='FIRST')    return $row >= 1 && $row <= $firstRows;
  if ($cls==='BUSINESS') return $row > $firstRows && $row <= ($firstRows + $bizRows);
  /* ECONOMY */
  return $row > ($firstRows + $bizRows);
}
?>
<div class="stepper">
  <div class="step done"><span class="dot">1</span><span class="label">ค้นหา</span></div>
  <div class="step done"><span class="dot"><i class="bi bi-check"></i></span><span class="label">เลือกเที่ยวบิน</span></div>
  <div class="step cur"><span class="dot">3</span><span class="label">เลือกที่นั่ง</span></div>
  <div class="step todo"><span class="dot">4</span><span class="label">ผู้โดยสาร</span></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="mb-2">เลือกที่นั่ง · <?= htmlspecialchars($cls) ?> · ต้องเลือก <?= $pax ?> ที่นั่ง</h5>

        <!-- legend -->
        <div class="d-flex align-items-center gap-3 mb-3 small">
          <span class="d-inline-flex align-items-center gap-1"><span class="seat sample available"></span> ว่าง</span>
          <span class="d-inline-flex align-items-center gap-1"><span class="seat sample selected"></span> ที่เลือก</span>
          <span class="d-inline-flex align-items-center gap-1"><span class="seat sample booked"></span> ไม่ว่าง</span>
        </div>

        <form id="seatForm" action="checkout.php" method="get">
          <input type="hidden" name="fi" value="<?= $fi ?>">
          <input type="hidden" name="class" value="<?= htmlspecialchars($cls) ?>">
          <input type="hidden" name="pax" value="<?= $pax ?>">

          <div class="seat-wrapper">
            <?php for($r=1; $r <= $totalRows; $r++): ?>
              <?php if (!rowBelongsToClass($r, $cls, $firstRows, $bizRows)) continue; ?>
              <div class="seat-row">
                <div class="row-label"><?= $r ?></div>
                <div class="row-seats">
                  <?php for($i=0; $i < $perRow; $i++):
                    $ch = $letters[$i] ?? null;
                    if ($ch===null) continue;
                    $s  = $map[$r][$ch] ?? null;
                    if (!$s) { echo '<span class="seat void"></span>'; continue; }
                    $clsSeat = ((int)$s['is_booked']===1) ? 'booked' : 'available';
                  ?>
                    <button type="button"
                            class="seat <?= $clsSeat ?>"
                            data-id="<?= (int)$s['id'] ?>"
                            data-no="<?= htmlspecialchars($s['seat_no']) ?>"
                            <?= $clsSeat==='booked'?'disabled':'' ?>>
                      <?= $ch ?>
                    </button>
                  <?php endfor; ?>
                </div>
              </div>
            <?php endfor; ?>
          </div>

          <div class="mt-3 d-flex justify-content-between align-items-center">
            <div class="small text-muted">เลือกแล้ว: <span id="pickedList">-</span></div>
            <div>
              <a href="javascript:history.back()" class="btn btn-outline-secondary">ย้อนกลับ</a>
              <button class="btn btn-primary" id="submitBtn" disabled>ต่อไป</button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <div class="fw-bold mb-2">คำแนะนำ</div>
        <ul class="small text-muted mb-0">
          <li>คลิกที่นั่งเพื่อเลือก/ยกเลิก</li>
          <li>เลือกให้ครบ <?= $pax ?> ที่นั่งจึงจะกด “ต่อไป” ได้</li>
          <li>โซนแถว: FIRST (1–<?= $firstRows ?>), BUSINESS (<?= $firstRows+1 ?>–<?= $firstRows+$bizRows ?>), ส่วนที่เหลือเป็น ECONOMY</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
// handle seat select
(function(){
  const pax = <?= $pax ?>;
  const form = document.getElementById('seatForm');
  const picked = new Map();
  const pickedList = document.getElementById('pickedList');
  const submitBtn = document.getElementById('submitBtn');

  function render(){
    const arr = Array.from(picked.values());
    pickedList.textContent = arr.length ? arr.map(x=>x.no).join(', ') : '-';
    submitBtn.disabled = (arr.length !== pax);

    // clear existing hidden
    [...form.querySelectorAll('input[name="seat_ids[]"]')].forEach(e=>e.remove());
    arr.forEach(x=>{
      const h=document.createElement('input');
      h.type='hidden'; h.name='seat_ids[]'; h.value=x.id;
      form.appendChild(h);
    });
  }

  document.querySelectorAll('.seat.available, .seat.selected').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.dataset.id, no = btn.dataset.no;
      if (btn.classList.contains('selected')) {
        btn.classList.remove('selected'); btn.classList.add('available');
        picked.delete(id);
      } else {
        if (picked.size >= pax) return; // limit
        btn.classList.remove('available'); btn.classList.add('selected');
        picked.set(id, {id, no});
      }
      render();
    });
  });

  render();
})();
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
