<?php
session_start();
$conn = new mysqli("localhost","root","","sportsmate_db");
$user = $_SESSION['user_id'];

$result = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE user_id=$user AND seen=0");
$row = $result->fetch_assoc();

echo $row['total'];
?>
