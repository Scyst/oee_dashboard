<?php
session_start();
session_destroy();
$redirect = isset($_GET['redirect']) ? '../page/OEE_Dashboard.php' : 'login_form.php';
header("Location: $redirect");
exit;
