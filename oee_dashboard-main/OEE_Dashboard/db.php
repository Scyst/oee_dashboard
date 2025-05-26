<?php
$serverName = "localhost";  // or use "localhost\SQLEXPRESS" if using SQLEXPRESS
$connectionOptions = array(
    "Database" => "oee_project_db",   // your database name
    "Uid" => "sa",                    // your SQL Server username
    "PWD" => "your_password",         // your SQL Server password
    "CharacterSet" => "UTF-8"
);

// Establish connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
?>
