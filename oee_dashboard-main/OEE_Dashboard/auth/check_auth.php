<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user'])) {
  header("Location: ../auth/login_form.php");
  exit;
}

$timeout = 1200; 
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
  session_unset();
  session_destroy();
  header("Location: ../page/OEE_Dashboard.php");
  exit;
}
$_SESSION['last_activity'] = time(); // update last activity timestamp

function hasRole($requiredRole) {
  return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $requiredRole;
}

// ✅ Check if the user has any of the roles in the allowed list
function allowRoles(array $allowedRoles) {
  return isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], $allowedRoles);
}

?>