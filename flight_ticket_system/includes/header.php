<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/session.php';

/* ถ้า BASE_URL = http://localhost/flight_ticket_system/public/
   ROOT_URL จะเป็น  http://localhost/flight_ticket_system/  */
$ROOT_URL  = rtrim(str_replace('/public/','/', BASE_URL),'/').'/';
$ADMIN_URL = rtrim(str_replace('/public/','/admin/', BASE_URL),'/').'/';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Flight Ticket System</title>

<!-- Bootstrap: CDN หลัก + CDN สำรอง + โลคอล (เผื่อเน็ตบล็อค CDN) -->
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      integrity="sha384-8Cflg3wqzj2X9d3r1c9mX2P2m6o8lYV4d3D6Yy8QOeGf1Grs7m1gD5J5n7E4mX2R"
      crossorigin="anonymous">
<link rel="stylesheet"
      href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      media="print" onload="this.media='all'"><noscript>
  <link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- Google Fonts: Noto Sans Thai + Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

</noscript>
<!-- โลคอลสำรองสุดท้าย (วางไฟล์ข้างล่างนี้) -->
<link rel="stylesheet" href="<?= $ROOT_URL ?>assets/vendor/bootstrap.min.css">

<!-- สไตล์ของโปรเจ็กต์ (อ้างผ่าน ROOT_URL ให้โดนที่อยู่จริง) -->
<link rel="stylesheet" href="<?= $ROOT_URL ?>assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark nav-gradient">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>">✈ FlightSys</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topnav">
      <ul class="navbar-nav ms-auto">
        <?php if (current_user() && is_admin()): ?>
          <!-- โหมดแอดมิน: แสดงเฉพาะ Admin + ออกจากระบบ -->
          <li class="nav-item"><a class="nav-link" href="<?= $ADMIN_URL ?>">Admin</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>logout.php">ออกจากระบบ</a></li>

        <?php elseif (current_user()): ?>
          <!-- ผู้ใช้ทั่วไป: แสดงเมนูผู้ใช้ -->
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>my_bookings.php">การจองของฉัน</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>logout.php">ออกจากระบบ</a></li>

        <?php else: ?>
          <!-- ยังไม่ล็อกอิน: ไม่ต้องมีปุ่มเข้าสู่ระบบ (ใช้ฟอร์มหน้าแรกแทน) -->
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>


<div class="container py-4">
