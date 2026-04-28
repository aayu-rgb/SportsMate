<?php
session_start();
$conn = new mysqli("localhost", "root", "", "sportsmate_db");

$user = $_SESSION['user_id'];

// Get unread notifications
$q = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE user_id=$user AND seen=0");
$data = $q->fetch_assoc();

echo $data['total'];
?>
