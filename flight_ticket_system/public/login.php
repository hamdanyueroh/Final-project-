<?php require_once __DIR__.'/../includes/header.php'; require_once __DIR__.'/../includes/csrf.php'; ?>
<h3>เข้าสู่ระบบ</h3>
<form method="post" action="../actions/do_login.php" class="mt-3">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label>Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Password</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <button class="btn btn-primary">เข้าสู่ระบบ</button>
  <a href="register.php" class="btn btn-link">สมัครสมาชิก</a>
</form>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
