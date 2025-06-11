<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user'])) {
  header("Location: ../auth/login_form.php");
  exit;
}

// Optional: 5-minute inactivity timeout
$timeout = 300; // 5 minutes
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
  session_unset();
  session_destroy();
  header("Location: ../page/OEE_Dashboard.php");
  exit;
}
$_SESSION['last_activity'] = time(); // update last activity timestamp
?>