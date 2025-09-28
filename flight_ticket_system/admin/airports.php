<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../middleware/admin_only.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/csrf.php';

$mode = 'create';
$edit = null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $action = $_POST['action'] ?? '';
  if ($action==='create') {
    $stm = $pdo->prepare("INSERT INTO airports(iata_code,name,city,country,timezone) VALUES (?,?,?,?,?)");
    $stm->execute([strtoupper(trim($_POST['iata_code'])), trim($_POST['name']), trim($_POST['city']), trim($_POST['country']), trim($_POST['timezone'])]);
  } elseif ($action==='update') {
    $stm = $pdo->prepare("UPDATE airports SET iata_code=?, name=?, city=?, country=?, timezone=? WHERE id=?");
    $stm->execute([strtoupper(trim($_POST['iata_code'])), trim($_POST['name']), trim($_POST['city']), trim($_POST['country']), trim($_POST['timezone']), (int)$_POST['id']]);
  } elseif ($action==='delete') {
    $stm = $pdo->prepare("DELETE FROM airports WHERE id=?");
    $stm->execute([(int)$_POST['id']]);
  }
  header('Location: airports.php'); exit;
}

if (isset($_GET['edit'])) {
  $mode = 'update';
  $q = $pdo->prepare("SELECT * FROM airports WHERE id=?");
  $q->execute([(int)$_GET['edit']]);
  $edit = $q->fetch();
}

$rows = $pdo->query("SELECT * FROM airports ORDER BY iata_code")->fetchAll();

require_once __DIR__.'/../includes/header.php';
?>
<h3>จัดการสนามบิน</h3>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <h5><?= $mode==='create' ? 'เพิ่มสนามบิน' : 'แก้ไขสนามบิน' ?></h5>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $mode ?>">
          <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
          <div class="mb-2"><label>IATA (3 ตัว)</label>
            <input class="form-control" name="iata_code" maxlength="3" required value="<?= $edit['iata_code'] ?? '' ?>"></div>
          <div class="mb-2"><label>ชื่อสนามบิน</label>
            <input class="form-control" name="name" required value="<?= $edit['name'] ?? '' ?>"></div>
          <div class="mb-2"><label>เมือง</label>
            <input class="form-control" name="city" value="<?= $edit['city'] ?? '' ?>"></div>
          <div class="mb-2"><label>ประเทศ</label>
            <input class="form-control" name="country" value="<?= $edit['country'] ?? '' ?>"></div>
          <div class="mb-2"><label>Timezone</label>
            <input class="form-control" name="timezone" value="<?= $edit['timezone'] ?? 'Asia/Bangkok' ?>"></div>
          <button class="btn btn-primary"><?= $mode==='create' ? 'เพิ่ม' : 'บันทึก' ?></button>
          <?php if($mode==='update'): ?><a href="airports.php" class="btn btn-outline-secondary">ยกเลิก</a><?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <h5>รายการสนามบิน</h5>
        <table class="table table-sm align-middle">
          <thead><tr><th>IATA</th><th>ชื่อ</th><th>เมือง</th><th>ประเทศ</th><th>TZ</th><th></th></tr></thead>
          <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['iata_code']) ?></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['city']) ?></td>
              <td><?= htmlspecialchars($r['country']) ?></td>
              <td><?= htmlspecialchars($r['timezone']) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= $r['id'] ?>">แก้ไข</a>
                <form method="post" class="d-inline" onsubmit="return confirm('ลบสนามบินนี้?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">ลบ</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
