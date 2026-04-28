<?php
session_start();
require "config.php";

if (!isset($_GET['id'])) {
    die("Invalid event.");
}

$id = (int)$_GET['id'];
$userId = $_SESSION['user_id'] ?? null;
$joined = false;
$message = '';

$eventStmt = $conn->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
$eventStmt->bind_param("i", $id);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$event = $eventResult->fetch_assoc();
$eventStmt->close();

if (!$event) {
    die("Event not found.");
}

if ($userId) {
    $checkStmt = $conn->prepare("SELECT id FROM event_participants WHERE event_id = ? AND user_id = ? LIMIT 1");
    $checkStmt->bind_param("ii", $id, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $joined = $checkResult && $checkResult->num_rows > 0;
    $checkStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join'])) {
    if (!$userId) {
        header("Location: login.php");
        exit;
    }

    if (!$joined) {
        $joinStmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)");
        $joinStmt->bind_param("ii", $id, $userId);
        if ($joinStmt->execute()) {
            $joined = true;
            $message = 'Successfully registered for this event.';
        } else {
            $message = 'Unable to register right now.';
        }
        $joinStmt->close();
    } else {
        $message = 'You already joined this event.';
    }
}

$eventName = $event['name'] ?? ($event['title'] ?? 'Event');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(155deg, #eef9ff, #d9efff);
            color: #0d2f54;
            font-family: "Barlow", sans-serif;
            display: grid;
            place-items: center;
            padding: 20px;
        }
        .box {
            width: min(560px, 100%);
            background: linear-gradient(165deg, rgba(255,255,255,.97), rgba(242,249,255,.98));
            border: 1px solid rgba(122,171,220,.55);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 18px 44px rgba(75,132,187,.22);
        }
        h2 {
            margin: 0 0 10px;
            font-family: "Teko", sans-serif;
            font-size: 40px;
            letter-spacing: .8px;
            line-height: .95;
        }
        .meta { color:#4f7398; margin: 6px 0; }
        .btn {
            border: none;
            border-radius: 10px;
            padding: 11px 14px;
            font-weight: 700;
            cursor: pointer;
            color: #032437;
            background: linear-gradient(135deg,#0fd9ff,#14e6a8);
            margin-top: 14px;
        }
        .btn[disabled] {
            background: #9bb8d4;
            color: #234f78;
            cursor: not-allowed;
        }
        .msg { margin: 8px 0; color: #236591; }
        a { color:#1473a8; text-decoration:none; font-weight:700; display:inline-block; margin-top:16px; }
    </style>
</head>
<body>

<div class="box">
    <h2><?= htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <p class="meta"><strong>Sport:</strong> <?= htmlspecialchars($event['sport'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <p class="meta"><strong>Location:</strong> <?= htmlspecialchars($event['location'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <p class="meta"><strong>Date:</strong> <?= htmlspecialchars($event['event_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (!empty($event['event_time'])): ?>
        <p class="meta"><strong>Time:</strong> <?= htmlspecialchars($event['event_time'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($joined): ?>
        <button class="btn" type="button" disabled>Registered</button>
    <?php else: ?>
        <form method="post">
            <button class="btn" type="submit" name="join">Register for Event</button>
        </form>
    <?php endif; ?>

    <a href="dashboard.php">Back to Dashboard</a>
</div>

</body>
</html>
