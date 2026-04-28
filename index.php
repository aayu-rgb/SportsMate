<?php
session_start();

// Always show loading first
if (!isset($_SESSION['seen_loading'])) {
    $_SESSION['seen_loading'] = true;
    header("Location: loading.php");
    exit();
}

// If logged in → dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Otherwise → home
header("Location: home.php");
exit();
