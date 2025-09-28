<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/csrf.php'; csrf_verify();
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/db.php';

$full = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if (!$full || !$email || strlen($pass)<6) {
  die('ข้อมูลไม่ครบ');
}

$exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
$exists->execute([$email]);
if ($exists->fetch()) { die('อีเมลนี้ถูกใช้แล้ว'); }

$hash = password_hash($pass, PASSWORD_DEFAULT);
$ins = $pdo->prepare("INSERT INTO users(full_name,email,password_hash,role) VALUES (?,?,?,'customer')");
$ins->execute([$full,$email,$hash]);

// auto login
$user_id = $pdo->lastInsertId();
$_SESSION['user'] = ['id'=>$user_id,'full_name'=>$full,'email'=>$email,'role'=>'customer'];

header('Location: '.BASE_URL);
