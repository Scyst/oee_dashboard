<?php
$serverName = getenv('DB_SERVER') ?: "LAPTOP-E0M0G0I9";
$connectionOptions = array(
    "Database" => getenv('DB_NAME') ?: "oee_db",
    "Uid" => getenv('DB_USER') ?: "verymaron01",
    "PWD" => getenv('DB_PASS') ?: "numthong01",
    "CharacterSet" => "UTF-8"
);

// Establish connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    // Log error to a file (do not display sensitive info to users)
    error_log(print_r(sqlsrv_errors(), true));
    die("Database connection failed.");
}
?>
