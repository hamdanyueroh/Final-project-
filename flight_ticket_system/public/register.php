<?php require_once __DIR__.'/../includes/header.php'; require_once __DIR__.'/../includes/csrf.php'; ?>
<h3>สมัครสมาชิก</h3>
<form method="post" action="../actions/do_register.php" class="mt-3">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label>ชื่อ-นามสกุล</label>
    <input type="text" name="full_name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Password</label>
    <input type="password" name="password" class="form-control" required minlength="6">
  </div>
  <button class="btn btn-success">สมัครสมาชิก</button>
</form>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
