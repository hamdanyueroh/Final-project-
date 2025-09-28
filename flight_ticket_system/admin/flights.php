<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../middleware/admin_only.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/csrf.php';

$airports = $pdo->query("SELECT id,iata_code,name FROM airports ORDER BY iata_code")->fetchAll();
$aircrafts = $pdo->query("SELECT id,model,registration FROM aircrafts ORDER BY id DESC")->fetchAll();

$mode='create'; $edit=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $a=$_POST['action'] ?? '';
  if ($a==='create') {
    $pdo->prepare("INSERT INTO flights(flight_no,airline,origin_airport_id,dest_airport_id,base_duration_min,aircraft_id)
                   VALUES (?,?,?,?,?,?)")
        ->execute([trim($_POST['flight_no']), trim($_POST['airline']), (int)$_POST['origin'], (int)$_POST['dest'], (int)$_POST['duration'], (int)$_POST['aircraft_id']]);
  } elseif ($a==='update') {
    $pdo->prepare("UPDATE flights SET flight_no=?, airline=?, origin_airport_id=?, dest_airport_id=?, base_duration_min=?, aircraft_id=? WHERE id=?")
        ->execute([trim($_POST['flight_no']), trim($_POST['airline']), (int)$_POST['origin'], (int)$_POST['dest'], (int)$_POST['duration'], (int)$_POST['aircraft_id'], (int)$_POST['id']]);
  } elseif ($a==='delete') {
    $pdo->prepare("DELETE FROM flights WHERE id=?")->execute([(int)$_POST['id']]);
  }
  header('Location: flights.php'); exit;
}

if (isset($_GET['edit'])) {
  $mode='update';
  $q=$pdo->prepare("SELECT * FROM flights WHERE id=?"); $q->execute([(int)$_GET['edit']]); $edit=$q->fetch();
}

$rows=$pdo->query("SELECT f.*, ao.iata_code ao, ad.iata_code ad, ac.model
  FROM flights f
  JOIN airports ao ON f.origin_airport_id=ao.id
  JOIN airports ad ON f.dest_airport_id=ad.id
  JOIN aircrafts ac ON f.aircraft_id=ac.id
  ORDER BY f.id DESC")->fetchAll();

require_once __DIR__.'/../includes/header.php';
?>
<h3>จัดการเที่ยวบิน (เส้นทางพื้นฐาน)</h3>
<div class="row g-3">
  <div class="col-md-5">
    <div class="card"><div class="card-body">
      <h5><?= $mode==='create'?'เพิ่มเที่ยวบิน':'แก้ไขเที่ยวบิน' ?></h5>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $mode ?>">
        <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        <div class="mb-2"><label>Flight No.</label><input class="form-control" name="flight_no" required value="<?= $edit['flight_no'] ?? '' ?>"></div>
        <div class="mb-2"><label>สายการบิน</label><input class="form-control" name="airline" required value="<?= $edit['airline'] ?? '' ?>"></div>
        <div class="mb-2"><label>ต้นทาง</label>
          <select class="form-select" name="origin" required>
            <option value="">- เลือก -</option>
            <?php foreach($airports as $a): ?>
              <option value="<?= $a['id'] ?>" <?= isset($edit['origin_airport_id']) && $edit['origin_airport_id']==$a['id']?'selected':'' ?>>
                <?= $a['iata_code'] ?> - <?= htmlspecialchars($a['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label>ปลายทาง</label>
          <select class="form-select" name="dest" required>
            <option value="">- เลือก -</option>
            <?php foreach($airports as $a): ?>
              <option value="<?= $a['id'] ?>" <?= isset($edit['dest_airport_id']) && $edit['dest_airport_id']==$a['id']?'selected':'' ?>>
                <?= $a['iata_code'] ?> - <?= htmlspecialchars($a['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label>เวลาโดยประมาณ (นาที)</label>
          <input type="number" class="form-control" name="duration" required value="<?= $edit['base_duration_min'] ?? 60 ?>">
        </div>
        <div class="mb-2"><label>เครื่องบิน</label>
          <select class="form-select" name="aircraft_id" required>
            <option value="">- เลือก -</option>
            <?php foreach($aircrafts as $ac): ?>
              <option value="<?= $ac['id'] ?>" <?= isset($edit['aircraft_id']) && $edit['aircraft_id']==$ac['id']?'selected':'' ?>>
                <?= htmlspecialchars($ac['model']) ?> (<?= htmlspecialchars($ac['registration']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary"><?= $mode==='create'?'เพิ่ม':'บันทึก' ?></button>
        <?php if($mode==='update'): ?><a class="btn btn-outline-secondary" href="flights.php">ยกเลิก</a><?php endif; ?>
      </form>
    </div></div>
  </div>
  <div class="col-md-7">
    <div class="card"><div class="card-body">
      <h5>รายการเที่ยวบิน</h5>
      <table class="table table-sm align-middle">
        <thead><tr><th>#</th><th>Flight</th><th>เส้นทาง</th><th>เครื่องบิน</th><th>เวลา (นาที)</th><th></th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['flight_no']) ?> · <?= htmlspecialchars($r['airline']) ?></td>
            <td><?= $r['ao'] ?> → <?= $r['ad'] ?></td>
            <td><?= htmlspecialchars($r['model']) ?></td>
            <td><?= $r['base_duration_min'] ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="?edit=<?= $r['id'] ?>">แก้ไข</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ลบเส้นทางนี้?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">ลบ</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
