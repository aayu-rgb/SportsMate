<?php
session_start();
require "config.php";
require_once "friend_utils.php";

if (!isset($_SESSION['user_id'])) {
    exit;
}

$sender = (int)$_SESSION['user_id'];
$receiver = isset($_POST['receiver']) ? (int)$_POST['receiver'] : 0;

if ($receiver <= 0 || !are_friends($conn, $sender, $receiver)) {
    exit;
}

$stmt = $conn->prepare("UPDATE users SET typing_to=? WHERE id=?");
if ($stmt) {
    $stmt->bind_param("ii", $receiver, $sender);
    $stmt->execute();
    $stmt->close();
}

echo "ok";
?>
