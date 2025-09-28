<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../middleware/admin_only.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/csrf.php';

$mode='create'; $edit=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $a = $_POST['action'] ?? '';
  if ($a==='create') {
    $pdo->prepare("INSERT INTO aircrafts(model,registration,seat_rows,seats_per_row,business_rows) VALUES (?,?,?,?,?)")
        ->execute([trim($_POST['model']), trim($_POST['registration']), (int)$_POST['seat_rows'], (int)$_POST['seats_per_row'], (int)$_POST['business_rows']]);
  } elseif ($a==='update') {
    $pdo->prepare("UPDATE aircrafts SET model=?, registration=?, seat_rows=?, seats_per_row=?, business_rows=? WHERE id=?")
        ->execute([trim($_POST['model']), trim($_POST['registration']), (int)$_POST['seat_rows'], (int)$_POST['seats_per_row'], (int)$_POST['business_rows'], (int)$_POST['id']]);
  } elseif ($a==='delete') {
    $pdo->prepare("DELETE FROM aircrafts WHERE id=?")->execute([(int)$_POST['id']]);
  }
  header('Location: aircrafts.php'); exit;
}
if (isset($_GET['edit'])) {
  $mode='update';
  $q=$pdo->prepare("SELECT * FROM aircrafts WHERE id=?"); $q->execute([(int)$_GET['edit']]); $edit=$q->fetch();
}
$rows=$pdo->query("SELECT * FROM aircrafts ORDER BY id DESC")->fetchAll();
require_once __DIR__.'/../includes/header.php';
?>
<h3>จัดการเครื่องบิน</h3>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <h5><?= $mode==='create'?'เพิ่มเครื่องบิน':'แก้ไขเครื่องบิน' ?></h5>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $mode ?>">
        <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        <div class="mb-2"><label>รุ่น (เช่น Airbus A320)</label><input class="form-control" name="model" required value="<?= $edit['model'] ?? '' ?>"></div>
        <div class="mb-2"><label>ทะเบียน</label><input class="form-control" name="registration" value="<?= $edit['registration'] ?? '' ?>"></div>
        <div class="mb-2"><label>จำนวนแถวทั้งหมด</label><input type="number" class="form-control" name="seat_rows" required value="<?= $edit['seat_rows'] ?? 30 ?>"></div>
        <div class="mb-2"><label>ที่นั่งต่อแถว</label><input type="number" class="form-control" name="seats_per_row" required value="<?= $edit['seats_per_row'] ?? 6 ?>"></div>
        <div class="mb-2"><label>แถว Business แรก ๆ</label><input type="number" class="form-control" name="business_rows" value="<?= $edit['business_rows'] ?? 4 ?>"></div>
        <button class="btn btn-primary"><?= $mode==='create'?'เพิ่ม':'บันทึก' ?></button>
        <?php if($mode==='update'): ?><a href="aircrafts.php" class="btn btn-outline-secondary">ยกเลิก</a><?php endif; ?>
      </form>
    </div></div>
  </div>
  <div class="col-md-8">
    <div class="card"><div class="card-body">
      <h5>รายการเครื่องบิน</h5>
      <table class="table table-sm align-middle">
        <thead><tr><th>#</th><th>รุ่น</th><th>ทะเบียน</th><th>ผัง</th><th></th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['model']) ?></td>
            <td><?= htmlspecialchars($r['registration']) ?></td>
            <td><?= $r['seat_rows'] ?> x <?= $r['seats_per_row'] ?> (Biz <?= (int)$r['business_rows'] ?> แถว)</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="?edit=<?= $r['id'] ?>">แก้ไข</a>
              <form method="post" class="d-inline" onsubmit="return confirm('ลบเครื่องบินนี้?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
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
