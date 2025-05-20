<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "oee_project_db"; // Make sure this matches your MySQL DB name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
