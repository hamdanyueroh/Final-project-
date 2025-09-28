<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../middleware/admin_only.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/csrf.php';

/* รายชื่อไฟลท์ (สำหรับดรอปดาวน์) */
$flights = $pdo->query("SELECT f.id, f.flight_no, ao.iata_code ao, ad.iata_code ad, ac.model
  FROM flights f
  JOIN airports ao ON f.origin_airport_id=ao.id
  JOIN airports ad ON f.dest_airport_id=ad.id
  JOIN aircrafts ac ON f.aircraft_id=ac.id
  ORDER BY f.id DESC")->fetchAll();

$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $a = $_POST['action'] ?? '';

  if ($a==='create_instance') {
    $flight_id  = (int)$_POST['flight_id'];
    $dep        = $_POST['departure_at']; // 'YYYY-MM-DDTHH:MM'
    $arr        = $_POST['arrival_at'];

    // Economy (จำเป็น)
    $eco_price  = (float)$_POST['eco_price'];
    $eco_seats  = (int)$_POST['eco_seats'];

    // Business (ถ้าเว้นว่าง/ศูนย์ = ไม่สร้าง)
    $biz_price  = isset($_POST['biz_price'])  ? (float)$_POST['biz_price']  : 0;
    $biz_seats  = isset($_POST['biz_seats'])  ? (int)$_POST['biz_seats']    : 0;

    // First (ถ้าเว้นว่าง/ศูนย์ = ไม่สร้าง)
    $fst_price  = isset($_POST['fst_price'])  ? (float)$_POST['fst_price']  : 0;
    $fst_seats  = isset($_POST['fst_seats'])  ? (int)$_POST['fst_seats']    : 0;

    if (!$flight_id || !$dep || !$arr || $eco_price<=0 || $eco_seats<=0) {
      $msg='ข้อมูลไม่ครบ (ต้องมี ECO)';
    } else {
      $pdo->beginTransaction();
      try {
        /* 1) สร้าง flight_instance */
        $pdo->prepare("INSERT INTO flight_instances(flight_id,departure_at,arrival_at,status) VALUES (?,?,?,'scheduled')")
            ->execute([$flight_id, str_replace('T',' ',$dep), str_replace('T',' ',$arr)]);
        $fi = (int)$pdo->lastInsertId();

        /* 2) สร้างแถวราคาต่อคลาส */
        $insFare = $pdo->prepare("INSERT INTO fares(flight_instance_id,class,fare_code,price,currency,remaining_seats) VALUES (?,?,?,?,?,?)");
        $insFare->execute([$fi,'ECONOMY','ECOSAVER',$eco_price,'THB',$eco_seats]);

        if ($biz_price>0 && $biz_seats>0) {
          $insFare->execute([$fi,'BUSINESS','BIZFLEX',$biz_price,'THB',$biz_seats]);
        }
        if ($fst_price>0 && $fst_seats>0) {
          $insFare->execute([$fi,'FIRST','FIRST',$fst_price,'THB',$fst_seats]);
        }

        /* 3) ดึงคอนฟิกเครื่อง เพื่อ seed ผังที่นั่ง (รองรับ FIRST/BUSINESS/ECONOMY) */
        $air = $pdo->prepare("SELECT ac.seat_rows, ac.seats_per_row, ac.first_rows, ac.business_rows
                              FROM aircrafts ac
                              JOIN flights f ON f.aircraft_id=ac.id
                              WHERE f.id=?");
        $air->execute([$flight_id]); 
        $cfg = $air->fetch();
        if (!$cfg) { throw new Exception('ไม่พบคอนฟิกเครื่องบิน'); }

        $seat_rows    = (int)$cfg['seat_rows'];
        $per_row      = (int)$cfg['seats_per_row'];
        $first_rows   = (int)$cfg['first_rows'];
        $biz_rows     = (int)$cfg['business_rows'];
        $letters      = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $insSeat = $pdo->prepare("INSERT INTO seat_inventory(flight_instance_id, seat_no, class, is_booked) VALUES (?,?,?,0)");

        for ($r=1; $r<=$seat_rows; $r++) {
          // จัดโซนแถว 1..first_rows = FIRST, ถัดไป biz_rows = BUSINESS, ที่เหลือ = ECONOMY
          $class = 'ECONOMY';
          if ($r <= $first_rows) $class = 'FIRST';
          elseif ($r <= $first_rows + $biz_rows) $class = 'BUSINESS';

          for ($i=0; $i<$per_row; $i++) {
            $seat = $r . substr($letters,$i,1);
            $insSeat->execute([$fi, $seat, $class]);
          }
        }

        $pdo->commit();
        $msg = 'สร้างตารางบินเรียบร้อย #' . $fi;
      } catch(Exception $e) {
        $pdo->rollBack(); 
        $msg = 'ผิดพลาด: '.$e->getMessage();
      }
    }

  } elseif ($a==='cancel') {
    $pdo->prepare("UPDATE flight_instances SET status='cancelled' WHERE id=?")->execute([(int)$_POST['id']]);
    $msg = 'ยกเลิกไฟลท์แล้ว';
  }
}

/* รายการ instance + ราคาต่ำสุด */
$rows = $pdo->query("SELECT fi.*, f.flight_no, ao.iata_code ao, ad.iata_code ad,
  (SELECT MIN(price) FROM fares fr WHERE fr.flight_instance_id=fi.id) min_price
  FROM flight_instances fi
  JOIN flights f ON fi.flight_id=f.id
  JOIN airports ao ON f.origin_airport_id=ao.id
  JOIN airports ad ON f.dest_airport_id=ad.id
  ORDER BY fi.departure_at DESC")->fetchAll();

require_once __DIR__.'/../includes/header.php';
?>
<h3>วางตารางบิน (Schedules)</h3>
<?php if($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card mb-4"><div class="card-body">
  <h5>สร้างเที่ยวบินตามวันที่</h5>
  <form method="post" class="row g-2">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create_instance">

    <div class="col-md-3">
      <label class="form-label">เที่ยวบิน</label>
      <select class="form-select" name="flight_id" required>
        <option value="">- เลือก -</option>
        <?php foreach($flights as $f): ?>
          <option value="<?= $f['id'] ?>"><?= $f['flight_no'] ?> (<?= $f['ao'] ?>→<?= $f['ad'] ?>) · <?= htmlspecialchars($f['model']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">ออกเดินทาง</label>
      <input type="datetime-local" class="form-control" name="departure_at" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">ถึงที่หมาย</label>
      <input type="datetime-local" class="form-control" name="arrival_at" required>
    </div>

    <!-- FIRST -->
    <div class="col-md-1">
      <label class="form-label">ราคา FIRST</label>
      <input type="number" step="0.01" class="form-control" name="fst_price" placeholder="เช่น 6990">
    </div>
    <div class="col-md-1">
      <label class="form-label">ที่นั่ง FIRST</label>
      <input type="number" class="form-control" name="fst_seats" placeholder="เช่น 8">
    </div>

    <!-- BUSINESS -->
    <div class="col-md-1">
      <label class="form-label">ราคา BIZ</label>
      <input type="number" step="0.01" class="form-control" name="biz_price" placeholder="เช่น 2990">
    </div>
    <div class="col-md-1">
      <label class="form-label">ที่นั่ง BIZ</label>
      <input type="number" class="form-control" name="biz_seats" placeholder="เช่น 24">
    </div>

    <!-- ECONOMY (ต้องมี) -->
    <div class="col-md-1">
      <label class="form-label">ราคา ECO</label>
      <input type="number" step="0.01" class="form-control" name="eco_price" value="1290" required>
    </div>
    <div class="col-md-1">
      <label class="form-label">ที่นั่ง ECO</label>
      <input type="number" class="form-control" name="eco_seats" value="150" required>
    </div>

    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100">สร้าง</button>
    </div>
  </form>
  <div class="form-text mt-2">
    * ถ้าไม่กรอกราคา/ที่นั่งของ FIRST/BIZ ระบบจะไม่สร้างคลาสนั้น<br>
    * การจัดวางแถว: FIRST (1..first_rows), BUSINESS (ถัดไป biz_rows), ที่เหลือเป็น ECONOMY — ตั้งค่าได้ที่ Admin → Aircrafts
  </div>
</div></div>

<div class="card"><div class="card-body">
  <h5>รายการตารางบิน</h5>
  <table class="table table-sm align-middle">
    <thead>
      <tr>
        <th>#</th><th>Flight</th><th>เส้นทาง</th><th>เวลาออก</th><th>เวลาเข้า</th>
        <th>สถานะ</th><th>เริ่ม</th><th class="text-end">จัดการ</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['flight_no']) ?></td>
        <td><?= $r['ao'] ?> → <?= $r['ad'] ?></td>
        <td><?= $r['departure_at'] ?></td>
        <td><?= $r['arrival_at'] ?></td>
        <td>
          <span class="badge <?= $r['status']==='scheduled'?'text-bg-success':($r['status']==='cancelled'?'text-bg-danger':'text-bg-secondary') ?>">
            <?= $r['status'] ?>
          </span>
        </td>
        <td><?= $r['min_price'] ? number_format($r['min_price'],2) : '-' ?></td>
        <td class="text-end">
          <!-- ราคาต่อคลาส -->
          <a class="btn btn-sm btn-outline-primary" href="fares.php?fi=<?= $r['id'] ?>">ราคาต่อคลาส</a>

          <?php if ($r['status']==='scheduled'): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('ยกเลิกไฟลท์นี้?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">ยกเลิก</button>
            </form>
          <?php endif; ?>
        </td>

      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
