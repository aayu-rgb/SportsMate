<?php session_start(); require 'config.php';
if(!isset($_SESSION['is_admin'])){ header('Location: admin_login.php'); exit; }
if(isset($_GET['del'])){ $id=intval($_GET['del']); $conn->query("DELETE FROM events WHERE id=$id"); header('Location: admin_events.php'); exit;}
$events = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
?>
<!DOCTYPE html><html><head><title>Manage Events</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container"><h2>Events</h2>
<?php while($e=$events->fetch_assoc()){ ?>
<div class="card"><strong><?=htmlspecialchars($e['name'] ?? ($e['title'] ?? 'Event'))?></strong><br>
<small class="small"><?=$e['sport']?> • <?=$e['event_date']?></small>
<div style="margin-top:8px"><a class="btn-outline" href="admin_events.php?del=<?=$e['id']?>">Delete</a></div>
</div>
<?php } ?>
</div></body></html>
