<?php
session_start();
if (isset($_SESSION['user'])) {
  header("Location: ../page/OEE_Dashboard.php");
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
  <style>
    body {
      background-color: #111;
      color: white;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-box {
      background: #222;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 0 10px #000;
      width: 100%;
      max-width: 400px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h3 class="text-center mb-4">Login</h3>
    
    <?php if (isset($_GET['timeout'])): ?>
      <div class="alert alert-warning">Session expired due to inactivity.</div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
  </div>
</body>
</html>
