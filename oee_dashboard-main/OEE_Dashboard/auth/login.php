<?php
session_start();
require_once("../api/db.php");

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "SELECT id, username, password, role FROM users WHERE username = ?";
$stmt = sqlsrv_query($conn, $sql, [$username]);

if ($stmt && $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  if (password_verify($password, $user['password'])) {
    // ✅ Store user info and role in session
    $_SESSION['user'] = [
      'id' => $user['id'],
      'username' => $user['username'],
      'role' => $user['role'] ?? 'operator' // fallback if role is null
    ];
    $_SESSION['last_activity'] = time(); // track session timeout
    header("Location: ../page/OEE_Dashboard.php");
    exit;
  }
}

// ❌ Invalid login
echo "<script>alert('Invalid username or password'); window.location='login_form.php';</script>";
?>
