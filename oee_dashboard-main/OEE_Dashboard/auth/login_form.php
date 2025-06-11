<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link rel="stylesheet" href="../style/style.css">
  <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
</head>
<body class="bg-dark text-white p-4">
  <div class="container">
    <h2>Login</h2>
    <form method="post" action="login.php">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
    </form>
  </div>
</body>
</html>