<?php
session_start(); require 'config.php';
if(!isset($_SESSION['is_admin'])){ header('Location: admin_login.php'); exit; }
if(isset($_GET['del'])){ $id=intval($_GET['del']); $conn->query("DELETE FROM users WHERE id=$id"); header('Location: admin_users.php'); exit;}
$users = $conn->query("SELECT id,name,email,phone,created_at FROM users ORDER BY id DESC");
?>
<!DOCTYPE html><html><head><title>Manage Users</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container"><h2>Users</h2>
<?php while($r=$users->fetch_assoc()){ ?>
  <div class="card fade-in" style="margin-bottom:10px">
    <strong><?=htmlspecialchars($r['name'])?> (<?=htmlspecialchars($r['email'])?>)</strong><br>
    <small class="small">Phone: <?=htmlspecialchars($r['phone'])?> • Joined: <?=$r['created_at']?></small>
    <div style="margin-top:8px"><a class="btn-outline" href="admin_users.php?del=<?=$r['id']?>">Delete</a></div>
  </div>
<?php } ?>
</div>
</body></html>
