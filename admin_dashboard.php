<?php
session_start(); require 'config.php';
if(!isset($_SESSION['is_admin'])){ header('Location: admin_login.php'); exit; }
$uCount = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$eCount = $conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'];
?>
<!DOCTYPE html><html><head><title>Admin</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container">
<div class="top-row"><h2>Admin Panel</h2><a href="admin_users.php" class="btn">Manage Users</a></div>
<div class="grid">
  <div class="card">Users: <strong><?=$uCount?></strong></div>
  <div class="card">Events: <strong><?=$eCount?></strong></div>
</div>
</div>
</body></html>
