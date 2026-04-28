<?php
session_start();
include "config.php";

$user_id = $_SESSION['user_id'];

$sql = "SELECT COUNT(*) AS unread FROM notifications WHERE user_id = $user_id AND seen = 0";
$res = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($res);

echo json_encode($data);
?>

