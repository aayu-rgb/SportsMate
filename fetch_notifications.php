<?php
session_start();
require "config.php";
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["unread" => 0]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$total = 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND seen = 0");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $row = $res->fetch_assoc();
        $total = (int)($row['total'] ?? 0);
    }
    $stmt->close();
}

echo json_encode([
    "unread" => $total
]);
