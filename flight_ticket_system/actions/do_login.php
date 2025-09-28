<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/csrf.php'; csrf_verify();
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/db.php';

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password_hash'])) {
  die('อีเมลหรือรหัสผ่านไม่ถูกต้อง');
}

$_SESSION['user'] = [
  'id'=>$user['id'],
  'full_name'=>$user['full_name'],
  'email'=>$user['email'],
  'role'=>$user['role']
];

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
if ($redirect) {
  header('Location: '.$redirect);
} else {
  header('Location: '.BASE_URL);
} 