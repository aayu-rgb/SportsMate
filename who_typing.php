<?php
session_start();
require "config.php";
require_once "friend_utils.php";

if (!isset($_SESSION['user_id'])) {
    exit;
}

$receiver = (int)$_SESSION['user_id'];
$sender = isset($_GET['receiver']) ? (int)$_GET['receiver'] : 0;

if ($sender <= 0 || !are_friends($conn, $receiver, $sender)) {
    exit;
}

$stmt = $conn->prepare("SELECT typing_to FROM users WHERE id=? LIMIT 1");
if (!$stmt) {
    exit;
}

$stmt->bind_param("i", $sender);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if ($row && (int)$row['typing_to'] === $receiver) {
    echo "Typing...";
}
?>
