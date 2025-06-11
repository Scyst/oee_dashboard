<?php
require_once("../api/db.php");

$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password) VALUES (?, ?)";
$stmt = sqlsrv_query($conn, $sql, [$username, $password]);

echo $stmt ? "User created." : "Error: " . print_r(sqlsrv_errors(), true);
?>