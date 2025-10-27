<?php
session_start();

// Destroy all session data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to customer login page
header("Location: login_superadmin.php");
exit();
?>

