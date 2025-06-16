<?php
require_once("../api/db.php");

$users = [
    ['verymaron01', 'numthong01'],
    ['SncToolbox', 'SncToolbox@2025'],
    ['Assembly', 'Assembly@2025'],
    ['Spot', 'Spot@2025'],
    ['Bend', 'Bend@2025'],
    ['Laser', 'Laser@2025'],
    ['Paint', 'Paint@2025'],
    ['Press', 'Press@2025'],
];

foreach ($users as $user) {
    $username = $user[0];
    $password = password_hash($user[1], PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = sqlsrv_query($conn, $sql, [$username, $password]);
    if ($stmt) {
        echo "User {$username} created.<br>";
    } else {
        echo "Error creating {$username}: " . print_r(sqlsrv_errors(), true) . "<br>";
    }
}
?>