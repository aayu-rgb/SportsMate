<?php
session_start();
require 'config.php';
$err = '';
if($_SERVER['REQUEST_METHOD']=='POST'){
  $u = trim($_POST['username']); $p = $_POST['password'];
  // set a simple admin credential (change in production)
  if($u=='admin' && $p=='Admin@123'){ $_SESSION['is_admin']=true; header('Location: admin_dashboard.php'); exit;}
  $err='Invalid credentials';
}
?>
<!DOCTYPE html><html><head><title>Admin Login</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container"><div class="card" style="max-width:420px;margin:auto">
<h2>Admin Login</h2>
<?php if($err) echo "<div class='small' style='color:#ff9b9b'>$err</div>"; ?>
<form method="post">
<input class="input" name="username" placeholder="username"><br><br>
<input class="input" name="password" type="password" placeholder="password"><br><br>
<button class="btn" type="submit">Login</button>
</form>
</div></div>
</body></html>
