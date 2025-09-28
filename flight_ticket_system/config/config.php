<?php
define('BASE_URL', 'http://localhost/flight_ticket_system/public/');

// URL หน้าแอดมิน (อนุพันธ์จาก BASE_URL)
if (!defined('ADMIN_URL')) {
  define('ADMIN_URL', preg_replace('#/public/?$#', '/admin/', BASE_URL));
}
// เผื่อโค้ดบางหน้าอ้าง $ADMIN_URL เป็นตัวแปร
$ADMIN_URL = ADMIN_URL;
