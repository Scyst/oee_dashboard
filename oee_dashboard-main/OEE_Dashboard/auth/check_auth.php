<?php
session_start();

$timeout = 300; // 5 minutes

// Redirect to OEE_Dashboard.php if session expired
if (!isset($_SESSION['user'])) {
  header("Location: ../page/OEE_Dashboard.php");
  exit;
}

if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
  session_unset();
  session_destroy();
  header("Location: ../page/OEE_Dashboard.php");
  exit;
}

$_SESSION['last_activity'] = time(); // update activity time
?>