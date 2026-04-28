<?php
session_start();
require "config.php";
require_once "notification_utils.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
$messageClass = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sport = trim($_POST['sport'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date = trim($_POST['event_date'] ?? '');
    $time = trim($_POST['event_time'] ?? '');

    if ($sport && $name && $location && $date && $time) {
        $stmt = $conn->prepare("
            INSERT INTO events (user_id, sport, name, location, event_date, event_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {
            $stmt->bind_param("isssss", $user_id, $sport, $name, $location, $date, $time);
            if ($stmt->execute()) {
                $eventText = "New event: " . $name . " (" . $sport . ") at " . $location . " on " . $date . ".";
                push_notification_to_all_except($conn, $user_id, $eventText);
                header("Location: events.php?created=1");
                exit;
            } else {
                $message = "Unable to create event right now.";
                $messageClass = "alert error";
            }
            $stmt->close();
        } else {
            $message = "Unable to create event right now.";
            $messageClass = "alert error";
        }
    } else {
        $message = "All fields are required.";
        $messageClass = "alert error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Event</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:"Barlow",sans-serif;
    color:#e8f3ff;
    background:
      linear-gradient(140deg, rgba(3,12,28,.9), rgba(5,22,48,.84)),
      url('https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=1500&q=80') center/cover no-repeat fixed;
}
.wrap{width:min(820px,94vw);margin:24px auto}
.panel{
    border:1px solid rgba(117,180,240,.45);
    background:linear-gradient(160deg, rgba(8,24,45,.82), rgba(8,28,54,.66));
    border-radius:22px;
    padding:24px;
    backdrop-filter:blur(4px);
}
.head{display:flex;justify-content:space-between;gap:12px;align-items:end;margin-bottom:16px;flex-wrap:wrap}
.title{margin:0;font-family:"Teko",sans-serif;font-size:52px;letter-spacing:.8px;line-height:.9}
.note{color:#bdd8f2;font-size:14px}
.head-actions{display:flex;gap:8px;flex-wrap:wrap}
.link-btn{
    text-decoration:none;
    font-weight:700;
    font-size:14px;
    border-radius:999px;
    padding:9px 14px;
    color:#052334;
    background:linear-gradient(120deg,#21ddff,#54ffb2);
}
.link-btn.alt{
    color:#d5ecff;
    background:rgba(255,255,255,.1);
    border:1px solid rgba(164,204,239,.42);
}
.form{
    border:1px solid rgba(126,190,244,.5);
    border-radius:14px;
    padding:16px;
    background:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
}
.label{
    display:block;
    margin:10px 0 6px;
    color:#cde8ff;
    font-size:14px;
    font-weight:600;
}
input,select{
    width:100%;
    border:1px solid rgba(126,190,244,.5);
    border-radius:10px;
    background:#0a2748;
    color:#e8f3ff;
    padding:11px 12px;
    font-size:15px;
}
input::placeholder{color:#9fc3e6}
.btn{
    border:none;
    border-radius:10px;
    padding:11px 14px;
    font-weight:700;
    cursor:pointer;
    margin-top:14px;
    color:#052334;
    background:linear-gradient(120deg,#21ddff,#54ffb2);
}
.alert{
    margin-bottom:12px;
    padding:10px 12px;
    border-radius:10px;
    font-size:14px;
    background:rgba(255,90,111,.18);
    border:1px solid rgba(255,90,111,.5);
    color:#ffd8df;
}
.back{display:inline-flex;align-items:center;gap:8px;margin-top:16px;color:#9fe9ff;text-decoration:none;font-weight:700}
.back:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <div class="head">
            <div>
                <h1 class="title">CREATE EVENT</h1>
                <div class="note">Simple form matching your existing SportsMate pages</div>
            </div>
            <div class="head-actions">
                <a class="link-btn alt" href="events.php"><i class="fa-solid fa-calendar-days"></i> Explore Events</a>
                <a class="link-btn" href="dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="<?= htmlspecialchars($messageClass ?: 'alert', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form class="form" method="POST">
            <label class="label" for="sport">Sport</label>
            <select id="sport" name="sport" required>
                <option value="">Select sport</option>
                <option value="Football">Football</option>
                <option value="Cricket">Cricket</option>
                <option value="Basketball">Basketball</option>
                <option value="Tennis">Tennis</option>
                <option value="Volleyball">Volleyball</option>
            </select>

            <label class="label" for="name">Event Name</label>
            <input id="name" type="text" name="name" placeholder="Enter event name" required>

            <label class="label" for="location">Location</label>
            <input id="location" type="text" name="location" placeholder="Enter location" required>

            <label class="label" for="event_date">Event Date</label>
            <input id="event_date" type="date" name="event_date" required>

            <label class="label" for="event_time">Event Time</label>
            <input id="event_time" type="time" name="event_time" required>

            <button class="btn" type="submit">Create Event</button>
        </form>

        <a class="back" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i>Back to Dashboard</a>
    </section>
</div>
</body>
</html>
