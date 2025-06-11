<?php
session_start();
session_destroy();
header("Location: ../page/OEE_Dashboard.php");
exit;
