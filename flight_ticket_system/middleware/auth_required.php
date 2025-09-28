<?php
require_once __DIR__.'/../includes/session.php';
if (!current_user()) {
  header('Location: '.BASE_URL.'public/login.php?redirect='.urlencode($_SERVER['REQUEST_URI']));
  exit;
}
