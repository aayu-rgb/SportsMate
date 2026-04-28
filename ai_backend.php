<?php
header("Content-Type: application/json");

$input = strtolower(trim($_POST['message'] ?? ''));

$response = "🤖 I am your SportsMate AI. Ask me about sports, tickets, kits or matches!";

if (strpos($input, "cricket") !== false) {
    $response = "🏏 Cricket is played between two teams of 11 players. Popular formats are T20, ODI and Test.";
}
elseif (strpos($input, "football") !== false) {
    $response = "⚽ Football is played with 11 players on each team. The FIFA World Cup is the biggest tournament.";
}
elseif (strpos($input, "hockey") !== false) {
    $response = "🏑 Hockey teams have 11 players including goalkeeper.";
}
elseif (strpos($input, "ticket") !== false) {
    $response = "🎟 You can book tickets from the Events section in SportsMate.";
}
elseif (strpos($input, "helmet") !== false) {
    $response = "🛒 You can explore cricket helmets in the Sports Kits section.";
}
elseif (strpos($input, "hello") !== false) {
    $response = "👋 Hello! How can I help you today?";
}

echo json_encode(["reply" => $response]);
