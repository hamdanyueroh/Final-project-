<?php
$dsn = 'mysql:host=localhost;dbname=flight_ticket_system;charset=utf8mb4';
$user = 'root';
$pass = ''; // XAMPP ค่าเริ่มต้นว่าง
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  die('DB Connection failed: ' . $e->getMessage());
}
