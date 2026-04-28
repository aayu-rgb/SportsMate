<?php
session_start();
require "config.php";
require_once "friend_utils.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$sender = (int)$_SESSION['user_id'];
$receiver = isset($_POST['receiver']) ? (int)$_POST['receiver'] : 0;
$message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';

if ($receiver <= 0 || $message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

if (!are_friends($conn, $sender, $receiver)) {
    echo json_encode(['status' => 'error', 'message' => 'You can chat only with accepted friends']);
    exit;
}

$insertMsgSql = "INSERT INTO messages (sender_id, receiver_id, message, status, timestamp) VALUES (?, ?, ?, 'sent', NOW())";
$stmt = $conn->prepare($insertMsgSql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iis", $sender, $receiver, $message);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $err]);
    exit;
}

$message_id = $stmt->insert_id;
$stmt->close();

$senderName = $_SESSION['user_name'] ?? 'A friend';
$notifText = $senderName . " sent you a message.";
$notifSql = "INSERT INTO notifications (user_id, message, seen, created_at) VALUES (?, ?, 0, NOW())";
$nstmt = $conn->prepare($notifSql);
if ($nstmt) {
    $nstmt->bind_param("is", $receiver, $notifText);
    $nstmt->execute();
    $nstmt->close();
}

echo json_encode(['status' => 'ok', 'message' => 'Message sent', 'message_id' => $message_id]);
exit;
?>
