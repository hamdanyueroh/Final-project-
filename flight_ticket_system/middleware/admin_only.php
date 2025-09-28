<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../config/config.php';
if (!is_admin()) {
  header('Location: '.BASE_URL.'public/');
  exit;
}
