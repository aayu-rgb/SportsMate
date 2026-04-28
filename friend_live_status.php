<?php
session_start();
require "config.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["ok" => false, "message" => "Not logged in"]);
    exit;
}

$current_user = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(MAX(id), 0) AS latest_id FROM users WHERE id != ?");
if (!$stmt) {
    echo json_encode(["ok" => false, "message" => "DB error"]);
    exit;
}

$stmt->bind_param("i", $current_user);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

echo json_encode([
    "ok" => true,
    "total" => (int)($row['total'] ?? 0),
    "latest_id" => (int)($row['latest_id'] ?? 0)
]);
