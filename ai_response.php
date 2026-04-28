<?php
header("Content-Type: application/json");

$apiKey = getenv("OPENAI_API_KEY") ?: "PASTE_YOUR_OPENAI_API_KEY_HERE";

$rawBody = file_get_contents("php://input");
$input = json_decode($rawBody, true);
$userMessage = "";

if (is_array($input) && isset($input["message"])) {
    $userMessage = trim((string)$input["message"]);
} elseif (isset($_POST["message"])) {
    $userMessage = trim((string)$_POST["message"]);
}

if (!$userMessage) {
    echo json_encode(["reply" => "No message received."]);
    exit;
}

// If API key is not configured, use local fallback logic.
if ($apiKey === "PASTE_YOUR_OPENAI_API_KEY_HERE") {
    $text = strtolower($userMessage);
    $reply = "I am your SportsMate AI. Ask me about sports, tickets, kits or matches!";

    if (strpos($text, "cricket") !== false) {
        $reply = "Cricket is played between two teams of 11 players. Popular formats are T20, ODI and Test.";
    } elseif (strpos($text, "football") !== false) {
        $reply = "Football is played with 11 players on each team. The FIFA World Cup is the biggest tournament.";
    } elseif (strpos($text, "hockey") !== false) {
        $reply = "Hockey teams have 11 players including goalkeeper.";
    } elseif (strpos($text, "ticket") !== false) {
        $reply = "You can book tickets from the Events section in SportsMate.";
    } elseif (strpos($text, "helmet") !== false) {
        $reply = "You can explore cricket helmets in the Sports Kits section.";
    } elseif (strpos($text, "hello") !== false || strpos($text, "hi") !== false) {
        $reply = "Hello! How can I help you today?";
    }

    echo json_encode(["reply" => $reply]);
    exit;
}

$data = [
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "system", "content" => "You are a helpful sports assistant for SportsMate website."],
        ["role" => "user", "content" => $userMessage]
    ]
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

$result = json_decode($response, true);

$reply = $result["choices"][0]["message"]["content"] ?? "";
if ($reply === "") {
    $reply = "AI not responding.";
    if ($curlError) {
        $reply = "AI request failed: " . $curlError;
    } elseif (isset($result["error"]["message"])) {
        $reply = "AI error: " . $result["error"]["message"];
    }
}

echo json_encode(["reply" => $reply]);
