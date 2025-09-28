<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../middleware/admin_only.php';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

$me = $_SESSION['user'] ?? null;

$stats = [
  'airports'   => (int)$pdo->query("SELECT COUNT(*) FROM airports")->fetchColumn(),
  'aircrafts'  => (int)$pdo->query("SELECT COUNT(*) FROM aircrafts")->fetchColumn(),
  'flights'    => (int)$pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn(),
  'instances'  => (int)$pdo->query("SELECT COUNT(*) FROM flight_instances WHERE status='scheduled'")->fetchColumn(),
  'bookings'   => (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
  'revenue'    => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn(),
];
?>
<section class="admin-hero mb-4 mt-2">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-8 text-white">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white/20 backdrop-blur">
          <i class="bi bi-shield-lock"></i>
          <span class="fw-semibold">Admin</span>
        </div>
        <h1 class="display-6 fw-bold mt-3 mb-2">สวัสดี <?= htmlspecialchars($me['full_name'] ?? 'Admin') ?></h1>
        <p class="mb-0 opacity-90">จัดการข้อมูลสนามบิน เครื่องบิน เที่ยวบิน และตารางบิน พร้อมดูสรุปภาพรวมของระบบ</p>
      </div>
      <div class="col-lg-4">
        <div class="glass-card p-4">
          <div class="d-flex align-items-center">
            <div class="stat-icon me-3"><i class="bi bi-currency-exchange"></i></div>
            <div>
              <div class="text-muted small">Revenue (paid)</div>
              <div class="h4 fw-bold mb-0"><?= number_format($stats['revenue'],2) ?> THB</div>
            </div>
          </div>
          <hr>
          <div class="d-flex align-items-center">
            <div class="stat-icon me-3"><i class="bi bi-receipt-cutoff"></i></div>
            <div>
              <div class="text-muted small">Total Bookings</div>
              <div class="h4 fw-bold mb-0"><?= number_format($stats['bookings']) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container">

  <!-- Stat cards -->
  <div class="row g-4">
    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-card-head">
          <span class="stat-badge bg-airport"><i class="bi bi-geo-alt"></i></span>
          <span class="label">Airports</span>
        </div>
        <div class="value"><?= number_format($stats['airports']) ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-card-head">
          <span class="stat-badge bg-aircraft"><i class="bi bi-airplane"></i></span>
          <span class="label">Aircrafts</span>
        </div>
        <div class="value"><?= number_format($stats['aircrafts']) ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-card-head">
          <span class="stat-badge bg-flight"><i class="bi bi-signpost-2"></i></span>
          <span class="label">Flights</span>
        </div>
        <div class="value"><?= number_format($stats['flights']) ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-card-head">
          <span class="stat-badge bg-schedule"><i class="bi bi-calendar2-week"></i></span>
          <span class="label">Scheduled Instances</span>
        </div>
        <div class="value"><?= number_format($stats['instances']) ?></div>
      </div>
    </div>
  </div>

  <!-- Quick actions -->
  <div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
      <div class="fw-bold mb-3">เมนูลัด</div>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= $ADMIN_URL ?>airports.php"   class="btn btn-gradient"><i class="bi bi-geo-alt me-1"></i> จัดการสนามบิน</a>
        <a href="<?= $ADMIN_URL ?>aircrafts.php"  class="btn btn-gradient"><i class="bi bi-airplane me-1"></i> จัดการเครื่องบิน</a>
        <a href="<?= $ADMIN_URL ?>flights.php"    class="btn btn-gradient"><i class="bi bi-signpost-2 me-1"></i> จัดการเที่ยวบิน</a>
        <a href="<?= $ADMIN_URL ?>schedules.php"  class="btn btn-gradient"><i class="bi bi-calendar2-week me-1"></i> วางตารางบิน</a>
      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
