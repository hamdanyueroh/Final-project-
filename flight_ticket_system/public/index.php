<?php
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/csrf.php';
?>

<?php if (!current_user()): ?>
  <!-- ========= WELCOME (ยังไม่ล็อกอิน) ========= -->
  <section class="hero-bg py-5 mt-2">
    <div class="container position-relative">
      <div class="row align-items-center">
        <div class="col-lg-7 text-center text-lg-start">
          <span class="hero-badge mb-3">
            <i class="bi bi-airplane"></i> FlightSys · Booking made simple
          </span>
          <h1 class="hero-title display-5 fw-bold mb-3">จองตั๋วเครื่องบิน <span class="text-warning">ง่าย ครบ</span> จบในที่เดียว</h1>
          <p class="hero-sub fs-5 mb-4">เข้าสู่ระบบเพื่อเริ่มต้นค้นหา เปรียบเทียบราคา และล็อกที่นั่งแบบเรียลไทม์</p>
        </div>

        <!-- Login Card -->
        <div class="col-lg-5 mt-4 mt-lg-0">
          <div class="glass-card p-4 p-lg-5">
            <div class="d-flex align-items-center mb-3">
              <div class="rounded-circle bg-primary-subtle p-2 me-2"><i class="bi bi-person fs-5 text-primary"></i></div>
              <h3 class="m-0">เข้าสู่ระบบ</h3>
            </div>

            <form method="post" action="../actions/do_login.php">
              <?= csrf_field() ?>
              <input type="hidden" name="redirect" value="<?= htmlspecialchars(BASE_URL) ?>">

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control form-control-lg" placeholder="you@example.com" required>
              </div>

              <div class="mb-2">
                <label class="form-label">Password</label>
                <div class="position-relative">
                  <input type="password" name="password" id="loginPass"
                         class="form-control form-control-lg pe-5" placeholder="••••••••" required>
                  <button type="button" class="btn-eye" data-target="#loginPass" aria-label="แสดง/ซ่อนรหัสผ่าน" title="แสดง/ซ่อนรหัสผ่าน">
                    <i class="bi bi-eye-slash"></i>
                  </button>
                </div>
                <div class="form-text form-text-hint">* ข้อมูลจะถูกปกป้องตามมาตรฐาน PHP</div>
              </div>

              <button class="btn btn-gradient btn-lg w-100 mt-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ
              </button>
            </form>

            <hr class="my-4">
            <div class="text-center">
              ยังไม่มีบัญชี? <a href="register.php" class="fw-semibold">สมัครสมาชิก</a>
            </div>
          </div>
        </div>
      </div>

      <!-- เครื่องบินลอย + Wave -->
      <svg class="hero-plane" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M240 120l-96-16-40-72-24 8 24 72-64 16v16l64 16-24 72 24 8 40-72 96-16z" fill="white" opacity=".9"/>
      </svg>
      <svg class="hero-wave" viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
        <path d="M0,96L80,96C160,96,320,96,480,90.7C640,85,800,75,960,74.7C1120,75,1280,85,1360,90.7L1440,96L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z" fill="#fff"/>
      </svg>
    </div>
  </section>

  <script>
    // toggle eye for password
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.btn-eye'); if(!btn) return;
      const sel = btn.getAttribute('data-target'); const input = document.querySelector(sel);
      if(!input) return;
      const show = input.type === 'password'; input.type = show ? 'text' : 'password';
      const i = btn.querySelector('i'); if(i){ i.classList.toggle('bi-eye', show); i.classList.toggle('bi-eye-slash', !show); }
    });
  </script>

<?php else: $me = current_user(); ?>
  <!-- ========= HOME (ล็อกอินแล้ว) ========= -->
  <section class="hero-bg py-5 mt-2">
    <div class="container position-relative">
      <div class="row align-items-center">
        <div class="col-lg-7 text-center text-lg-start">
          <span class="hero-badge mb-3">👋 สวัสดี <?= htmlspecialchars($me['full_name']) ?></span>
          <h1 class="hero-title display-5 fw-bold mb-3">พร้อมจองเที่ยวบินแล้ว</h1>
          <p class="hero-sub fs-5 mb-4">ค้นหาเปรียบเทียบราคา แล้วล็อกที่นั่งได้ทันที</p>
        </div>
        <div class="col-lg-5 mt-4 mt-lg-0">
          <div class="glass-card p-4 p-lg-5">
            <h3 class="mb-3">ค้นหาเที่ยวบิน</h3>
            <form class="row g-3" action="<?= BASE_URL ?>search_results.php" method="get">
              <div class="col-md-6">
                <label class="form-label">ต้นทาง (IATA)</label>
                <input name="origin" class="form-control" placeholder="BKK" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">ปลายทาง (IATA)</label>
                <input name="dest" class="form-control" placeholder="HKT" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">วันเดินทาง</label>
                <input type="date" name="date" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">ผู้โดยสาร</label>
                <input type="number" name="pax" min="1" value="1" class="form-control" required>
              </div>
              <div class="col-12 text-end">
                <button type="submit" class="btn btn-gradient btn-lg">ค้นหา</button>
              </div>
            </form>

          </div>
        </div>
      </div>

      <svg class="hero-wave" viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
        <path d="M0,96L80,96C160,96,320,96,480,90.7C640,85,800,75,960,74.7C1120,75,1280,85,1360,90.7L1440,96L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z" fill="#fff"/>
      </svg>
    </div>
  </section>
<?php endif; ?>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
