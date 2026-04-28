<?php
session_start();
require "config.php";
require_once "friend_utils.php";

if (!isset($_SESSION['user_id'])) {
    exit;
}

$sender = (int)$_SESSION['user_id'];
$receiver = isset($_GET['receiver']) ? (int)$_GET['receiver'] : 0;

if ($receiver <= 0 || !are_friends($conn, $sender, $receiver)) {
    echo "<div class='msg'>Start by adding this user as a friend.</div>";
    exit;
}

$seenStmt = $conn->prepare("UPDATE messages SET status='seen' WHERE sender_id=? AND receiver_id=?");
if ($seenStmt) {
    $seenStmt->bind_param("ii", $receiver, $sender);
    $seenStmt->execute();
    $seenStmt->close();
}

$msgStmt = $conn->prepare("SELECT sender_id, message, status, timestamp FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY timestamp ASC");
if (!$msgStmt) {
    exit;
}

$msgStmt->bind_param("iiii", $sender, $receiver, $receiver, $sender);
$msgStmt->execute();
$res = $msgStmt->get_result();

while ($res && $m = $res->fetch_assoc()) {
    $class = ((int)$m['sender_id'] === $sender) ? "self" : "";

    $status = '';
    if ((int)$m['sender_id'] === $sender) {
        $status = (string)($m['status'] ?? 'sent');
    }

    $msg = htmlspecialchars((string)$m['message'], ENT_QUOTES, 'UTF-8');
    $time = date("h:i A", strtotime((string)$m['timestamp']));
    $meta = trim($time . ($status !== '' ? " | " . $status : ''));

    echo "<div class='msg {$class}'>{$msg}<small>{$meta}</small></div>";
}

$msgStmt->close();
?>
