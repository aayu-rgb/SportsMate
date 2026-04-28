<?php
session_start();
include "config.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$events = mysqli_query($conn,"SELECT * FROM events ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Events</title>
<style>
body {
    background:#0a192f;
    color:white;
    font-family:Arial;
    padding:20px;
}
.event {
    background:#112240;
    padding:15px;
    border-radius:10px;
    margin-bottom:10px;
}
button {
    padding:10px;
    background:#64ffda;
    border:none;
    border-radius:8px;
    cursor:pointer;
}
a {color:#64ffda;text-decoration:none;}
</style>
</head>
<body>

<h2>Available Events</h2>

<?php while($e=mysqli_fetch_assoc($events)){ ?>
<div class="event">
    <b>Sport:</b> <?= $e['sport'] ?> <br>
    <b>Date:</b> <?= $e['event_date'] ?> <br>
    <b>Location:</b> <?= $e['location'] ?> <br><br>
    <button>Join</button>
</div>
<?php } ?>

<a href="dashboard.php">⬅ Back</a>

</body>
</html>
