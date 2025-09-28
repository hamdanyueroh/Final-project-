<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function current_user() { return $_SESSION['user'] ?? null; }
function is_admin() { return isset($_SESSION['user']) && $_SESSION['user']['role']==='admin'; }
