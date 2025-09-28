<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../middleware/admin_only.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

$fi = (int)($_GET['fi'] ?? 0);
if (!$fi){ echo "<div class='alert alert-warning'>กรุณาระบุ flight_instance (fi)</div>"; require_once __DIR__.'/../includes/footer.php'; exit; }

if ($_SERVER['REQUEST_METHOD']==='POST'){
  $class = $_POST['class'] ?? '';
  $price = (float)($_POST['price'] ?? 0);
  $remain = (int)($_POST['remaining_seats'] ?? 0);

  $st = $pdo->prepare("SELECT id FROM fares WHERE flight_instance_id=? AND class=?");
  $st->execute([$fi,$class]);
  if ($id = $st->fetchColumn()){
    $pdo->prepare("UPDATE fares SET price=?, remaining_seats=? WHERE id=?")
        ->execute([$price,$remain,$id]);
  }else{
    $pdo->prepare("INSERT INTO fares(flight_instance_id,class,fare_code,price,remaining_seats)
                   VALUES (?,?,?,?,?)")
        ->execute([$fi,$class,'STD',$price,$remain]);
  }
  echo '<div class="alert alert-success">บันทึกแล้ว</div>';
}

// ดึงสรุปที่นั่งว่างจริงต่อคลาส (เช็คกับ seat_inventory)
$real = $pdo->prepare("SELECT class, COUNT(*) c FROM seat_inventory WHERE flight_instance_id=? AND is_booked=0 GROUP BY class");
$real->execute([$fi]);
$realMap = []; foreach ($real as $r) $realMap[$r['class']] = (int)$r['c'];

$fares = $pdo->prepare("SELECT * FROM fares WHERE flight_instance_id=? ORDER BY FIELD(class,'FIRST','BUSINESS','ECONOMY')");
$fares->execute([$fi]);
$rows = $fares->fetchAll();
?>
<h3 class="mb-3">จัดการราคา/โควตา · FI #<?= $fi ?></h3>

<table class="table align-middle">
  <thead><tr><th>Class</th><th>Price (THB)</th><th>Remaining Seats</th><th>ที่นั่งว่างจริง</th><th></th></tr></thead>
  <tbody>
  <?php
    $classes = ['FIRST','BUSINESS','ECONOMY'];
    foreach ($classes as $cls):
      $row = null; foreach ($rows as $r) if ($r['class']===$cls) {$row=$r; break;}
  ?>
    <tr>
      <form method="post">
        <input type="hidden" name="class" value="<?= $cls ?>">
        <td><span class="badge <?= $cls==='FIRST'?'text-bg-danger':($cls==='BUSINESS'?'text-bg-warning text-dark':'text-bg-secondary') ?>"><?= $cls ?></span></td>
        <td style="max-width:180px"><input type="number" step="0.01" name="price" class="form-control" value="<?= $row['price'] ?? '' ?>"></td>
        <td style="max-width:160px"><input type="number" name="remaining_seats" class="form-control" value="<?= $row['remaining_seats'] ?? ($realMap[$cls] ?? 0) ?>"></td>
        <td><?= $realMap[$cls] ?? 0 ?></td>
        <td><button class="btn btn-sm btn-primary">บันทึก</button></td>
      </form>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<a href="schedules.php" class="btn btn-outline-secondary">ย้อนกลับ</a>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
