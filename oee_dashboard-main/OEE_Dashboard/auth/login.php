// ===== /auth/login.php =====
session_start();
require_once("../api/db.php");

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "SELECT * FROM users WHERE username = ?";
$stmt = sqlsrv_query($conn, $sql, [$username]);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
  $_SESSION['user'] = $user['username'];
  header("Location: ../page/OEE_Dashboard.php");
  exit;
} else {
  echo "<script>alert('Invalid username or password'); window.location='login_form.php';</script>";
}
