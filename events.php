<?php
session_start();
require "config.php";
require_once "notification_utils.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_event_id'])) {
    $eventId = (int)$_POST['join_event_id'];
    if ($eventId <= 0) {
        $message = 'Invalid event selected.';
        $messageClass = 'alert error';
    } else {
        $existsStmt = $conn->prepare("SELECT id FROM events WHERE id = ? LIMIT 1");
        if ($existsStmt) {
            $existsStmt->bind_param("i", $eventId);
            $existsStmt->execute();
            $existsStmt->store_result();
            $eventExists = $existsStmt->num_rows > 0;
            $existsStmt->close();
        } else {
            $eventExists = false;
        }

        if (!$eventExists) {
            $message = 'Event not found.';
            $messageClass = 'alert error';
        } else {
            $checkStmt = $conn->prepare("SELECT id FROM event_participants WHERE event_id = ? AND user_id = ? LIMIT 1");
            if ($checkStmt) {
                $checkStmt->bind_param("ii", $eventId, $userId);
                $checkStmt->execute();
                $checkStmt->store_result();
                $alreadyJoined = $checkStmt->num_rows > 0;
                $checkStmt->close();

                if (!$alreadyJoined) {
                    $joinStmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)");
                    if ($joinStmt) {
                        $joinStmt->bind_param("ii", $eventId, $userId);
                        if ($joinStmt->execute()) {
                            $message = 'Event registration successful.';
                            $messageClass = 'alert ok';

                            $ownerStmt = $conn->prepare("SELECT user_id, name FROM events WHERE id = ? LIMIT 1");
                            if ($ownerStmt) {
                                $ownerStmt->bind_param("i", $eventId);
                                $ownerStmt->execute();
                                $ownerRes = $ownerStmt->get_result();
                                $eventRow = $ownerRes ? $ownerRes->fetch_assoc() : null;
                                $ownerStmt->close();

                                if ($eventRow) {
                                    $ownerId = (int)$eventRow['user_id'];
                                    $eventTitle = $eventRow['name'] ?? 'your event';
                                    if ($ownerId > 0 && $ownerId !== $userId) {
                                        $actorName = $_SESSION['user_name'] ?? 'A user';
                                        push_notification($conn, $ownerId, $actorName . " registered for " . $eventTitle . ".");
                                    }
                                }
                            }
                        } else {
                            $message = 'Unable to register right now: ' . $conn->error;
                            $messageClass = 'alert error';
                        }
                        $joinStmt->close();
                    } else {
                        $message = 'Unable to register right now: ' . $conn->error;
                        $messageClass = 'alert error';
                    }
                } else {
                    $message = 'You are already registered for this event.';
                    $messageClass = 'alert info';
                }
            } else {
                $message = 'Unable to process registration right now: ' . $conn->error;
                $messageClass = 'alert error';
            }
        }
    }
}

$eventsResult = $conn->query("SELECT * FROM events ORDER BY event_date ASC, event_time ASC");

$joinedIds = [];
$joinedStmt = $conn->prepare("SELECT event_id FROM event_participants WHERE user_id = ?");
if ($joinedStmt) {
    $joinedStmt->bind_param("i", $userId);
    $joinedStmt->execute();
    $joinedResult = $joinedStmt->get_result();
    while ($joinedResult && $row = $joinedResult->fetch_assoc()) {
        $joinedIds[(int)$row['event_id']] = true;
    }
    $joinedStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Explore Events</title>
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
.wrap{width:min(1040px,94vw);margin:24px auto}
.panel{
    border:1px solid rgba(117,180,240,.45);
    background:linear-gradient(160deg, rgba(8,24,45,.82), rgba(8,28,54,.66));
    border-radius:22px;
    padding:24px;
    backdrop-filter:blur(4px);
}
.head{display:flex;justify-content:space-between;gap:12px;align-items:end;margin-bottom:14px;flex-wrap:wrap}
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
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}
.card{
    border:1px solid rgba(126,190,244,.5);
    border-radius:14px;
    padding:14px;
    background:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
}
.card h3{margin:0 0 8px;font-size:21px;color:#ecf8ff}
.meta{display:flex;gap:8px;align-items:flex-start;color:#a7caeb;font-size:14px;margin-bottom:6px}
.meta i{width:16px;color:#30e0ff;margin-top:2px}
.row{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-top:10px}
.badge{font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(110,220,255,.18);color:#ccf3ff;border:1px solid rgba(125,206,246,.46)}
.btn{
    border:none;
    border-radius:10px;
    padding:9px 12px;
    font-weight:700;
    cursor:pointer;
    color:#052334;
    background:linear-gradient(120deg,#21ddff,#54ffb2);
}
.btn.disabled{
    background:#8aa6bf;
    color:#10324d;
    cursor:not-allowed;
}
.alert{margin-bottom:12px;padding:10px 12px;border-radius:10px;font-size:14px;background:rgba(76,204,255,.15);border:1px solid rgba(136,209,246,.45);color:#d4f3ff}
.alert.ok{background:rgba(84,255,178,.16);border-color:rgba(84,255,178,.45);color:#cffff0}
.alert.error{background:rgba(255,90,111,.18);border-color:rgba(255,90,111,.5);color:#ffd8df}
.alert.info{background:rgba(76,204,255,.15);border-color:rgba(136,209,246,.45);color:#d4f3ff}
.empty{color:#bfd9f3;padding:6px 0}
.back{display:inline-flex;align-items:center;gap:8px;margin-top:16px;color:#9fe9ff;text-decoration:none;font-weight:700}
.back:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <div class="head">
            <div>
                <h1 class="title">UPCOMING EVENTS</h1>
                <div class="note">Explore and register for events instantly</div>
            </div>
            <div class="head-actions">
                <a class="link-btn" href="create_event.php"><i class="fa-solid fa-plus"></i> Create Event</a>
                <a class="link-btn alt" href="my_events.php"><i class="fa-solid fa-calendar-check"></i> My Registrations</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="<?= htmlspecialchars($messageClass ?: 'alert info', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($eventsResult && $eventsResult->num_rows > 0): ?>
            <div class="grid">
                <?php while($e = $eventsResult->fetch_assoc()): ?>
                    <?php
                        $eventId = (int)$e['id'];
                        $eventName = $e['name'] ?? ($e['title'] ?? 'Untitled Event');
                        $eventTime = $e['event_time'] ?? '';
                        $isJoined = isset($joinedIds[$eventId]);
                    ?>
                    <article class="card">
                        <h3><?= htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="meta"><i class="fa-solid fa-basketball"></i><span>Sport: <?= htmlspecialchars($e['sport'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="meta"><i class="fa-solid fa-location-dot"></i><span>Location: <?= htmlspecialchars($e['location'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="meta"><i class="fa-solid fa-calendar-days"></i><span>Date: <?= htmlspecialchars($e['event_date'] ?? '', ENT_QUOTES, 'UTF-8') ?><?= $eventTime ? ' | Time: ' . htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8') : '' ?></span></div>

                        <div class="row">
                            <span class="badge">Event #<?= $eventId ?></span>
                            <?php if ($isJoined): ?>
                                <button class="btn disabled" type="button" disabled>Registered</button>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="join_event_id" value="<?= $eventId ?>">
                                    <button class="btn" type="submit">Register</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="empty">No events available right now. You can create one.</p>
        <?php endif; ?>

        <a class="back" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i>Back to Dashboard</a>
    </section>
</div>
</body>
</html>
