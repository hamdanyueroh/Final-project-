<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/csrf.php'; csrf_verify();
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../middleware/auth_required.php';

$user_id = $_SESSION['user']['id'];
$fi   = (int)($_POST['fi'] ?? 0);
$cls  = $_POST['class'] ?? 'ECONOMY';
$pax  = max(1,(int)($_POST['pax'] ?? 1));
$unit = (float)($_POST['unit_price'] ?? 0);
$fnames = $_POST['fname'] ?? [];
$lnames = $_POST['lname'] ?? [];

// รับ seat_ids ที่ส่งมาจาก checkout (มาจาก select_seats.php)
$seat_ids = [];
if (isset($_POST['seat_ids']) && is_array($_POST['seat_ids'])) {
  // เก็บเป็น int และลบค่าซ้ำ
  $seat_ids = array_values(array_unique(array_map('intval', $_POST['seat_ids'])));
}

if (count($fnames)!==$pax || count($lnames)!==$pax) {
  die('จำนวนผู้โดยสารไม่ตรง');
}

$pdo->beginTransaction();
try {
  // ล็อกแถวราคาตามคลาส
  $fare = $pdo->prepare("SELECT id, remaining_seats, price
                         FROM fares
                         WHERE flight_instance_id=? AND class=? FOR UPDATE");
  $fare->execute([$fi,$cls]);
  $fr = $fare->fetch();
  if (!$fr || (int)$fr['remaining_seats'] < $pax) {
    throw new Exception('ที่นั่งไม่พอ');
  }

  // สร้าง booking
  $total = $pax * (float)$fr['price']; // ไม่เชื่อค่าจาก client
  $pdo->prepare("INSERT INTO bookings(user_id,total_amount,status) VALUES (?,?, 'pending')")
      ->execute([$user_id,$total]);
  $booking_id = (int)$pdo->lastInsertId();

  // เพิ่มผู้โดยสาร
  $ps = $pdo->prepare("INSERT INTO passengers(booking_id,first_name,last_name) VALUES (?,?,?)");
  for ($i=0;$i<$pax;$i++) {
    $ps->execute([$booking_id, trim($fnames[$i]), trim($lnames[$i])]);
  }

  // เพิ่มรายการไฟลท์
  $pdo->prepare("INSERT INTO booking_items(booking_id,flight_instance_id,class,price) VALUES (?,?,?,?)")
      ->execute([$booking_id,$fi,$cls,$total]);

  // ลดที่นั่งคงเหลือ (ในตาราง fares)
  $pdo->prepare("UPDATE fares SET remaining_seats = remaining_seats - ? WHERE id=?")
      ->execute([$pax,$fr['id']]);

  // ---------- จัดที่นั่งจริงใน seat_inventory ----------
  $locked = 0;

  if ($seat_ids) {
    if (count($seat_ids) > $pax) {
      throw new Exception('เลือกที่นั่งเกินจำนวนผู้โดยสาร');
    }

    // ล็อกที่นั่งที่เลือกทั้งหมด และตรวจสอบว่าอยู่ในไฟลท์/คลาสเดียวกันและยังไม่ถูกจอง
    $in = implode(',', array_fill(0, count($seat_ids), '?'));
    $chk = $pdo->prepare("SELECT id
                          FROM seat_inventory
                          WHERE id IN ($in)
                            AND flight_instance_id=?
                            AND class=?
                            AND is_booked=0
                          FOR UPDATE");
    $params = $seat_ids;
    $params[] = $fi;
    $params[] = $cls;
    $chk->execute($params);
    $rows = $chk->fetchAll(PDO::FETCH_COLUMN);

    if (count($rows) !== count($seat_ids)) {
      throw new Exception('มีที่นั่งที่เลือกบางตัวถูกจองแล้วหรือไม่อยู่ในคลาส/ไฟลท์นี้');
    }

    // จองที่นั่งตามที่เลือก
    $upd = $pdo->prepare("UPDATE seat_inventory SET is_booked=1, booking_id=? WHERE id=?");
    foreach ($rows as $sid) {
      $upd->execute([$booking_id, (int)$sid]);
    }
    $locked = count($rows);

    // ถ้าเลือกมาไม่ครบ ให้ระบบเลือกเพิ่มให้
    $need = $pax - $locked;
    if ($need > 0) {
      $sql = "SELECT id FROM seat_inventory
              WHERE flight_instance_id=? AND class=? AND is_booked=0";
      $params2 = [$fi, $cls];
      if ($locked > 0) {
        $in2 = implode(',', array_fill(0, count($rows), '?'));
        $sql .= " AND id NOT IN ($in2)";
        $params2 = array_merge($params2, array_map('intval',$rows));
      }
      $sql .= " LIMIT $need FOR UPDATE";
      $pick = $pdo->prepare($sql);
      $pick->execute($params2);
      $more = $pick->fetchAll(PDO::FETCH_COLUMN);
      if (count($more) < $need) {
        throw new Exception('ที่นั่งจริงไม่พอ');
      }
      foreach ($more as $sid) {
        $upd->execute([$booking_id, (int)$sid]);
      }
      $locked += count($more);
    }
  } else {
    // ไม่ได้เลือกที่นั่งมาเลย → ให้ระบบจัดให้ทั้งหมด
    $seatQ = $pdo->prepare("SELECT id
                            FROM seat_inventory
                            WHERE flight_instance_id=? AND class=? AND is_booked=0
                            LIMIT $pax FOR UPDATE");
    $seatQ->execute([$fi,$cls]);
    $seats = $seatQ->fetchAll(PDO::FETCH_COLUMN);
    if (count($seats) < $pax) {
      throw new Exception('ที่นั่งจริงไม่พอ');
    }
    $updSeat = $pdo->prepare("UPDATE seat_inventory SET is_booked=1, booking_id=? WHERE id=?");
    foreach ($seats as $sid) {
      $updSeat->execute([$booking_id,(int)$sid]);
    }
    $locked = count($seats);
  }

  if ($locked !== $pax) {
    throw new Exception('จองที่นั่งได้ไม่ครบ');
  }

  // สร้าง payment pending
  $pdo->prepare("INSERT INTO payments(booking_id,amount,status) VALUES (?,?, 'pending')")
      ->execute([$booking_id,$total]);

  $pdo->commit();
  header('Location: '.BASE_URL.'payment.php?booking_id='.$booking_id);
  exit;
} catch (Exception $e) {
  $pdo->rollBack();
  die('จองไม่สำเร็จ: '.$e->getMessage());
}
