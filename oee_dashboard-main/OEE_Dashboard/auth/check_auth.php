// ===== /auth/check_auth.php =====
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: ../auth/login_form.php");
  exit;
}