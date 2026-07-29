<?php
session_start();
$_SESSION = array(); // Clear data from current memory
session_destroy();   // Destroy data on server

header('Location: ./login.php');
exit();