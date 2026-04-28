<?php
session_start();

// Prevent loading page from showing again after logout
unset($_SESSION['loaded_once']);

// Destroy all session data
session_destroy();

// Redirect to home page
header("Location: home.php");
exit();
?>
