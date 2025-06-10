<?php
$serverName = "LAPTOP-E0M0G0I9";  // or use "localhost\SQLEXPRESS" if using SQLEXPRESS
$connectionOptions = array(
    "Database" => "oee_db",   // database name
    "Uid" => "verymaron01",   // SQL Server username
    "PWD" => "numthong01",    // SQL Server password
    "CharacterSet" => "UTF-8"
);

// Establish connectiongit
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
?>
