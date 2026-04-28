<?php
session_start();
require "config.php";

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$match_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'] ?? null;

$res = $conn->query("SELECT * FROM matches WHERE id=$match_id");
$match = $res->fetch_assoc();

if (!$match) die("Match not found");

// Track click
if ($user_id) {
    $stmt = $conn->prepare(
        "INSERT INTO ticket_clicks (user_id, match_name, provider)
         VALUES (?, ?, ?)"
    );
    $provider = "BookMyShow";
    $stmt->bind_param("iss", $user_id, $match['match_name'], $provider);
    $stmt->execute();
}

// Redirect to official site
header("Location: " . $match['ticket_url']);
exit;
