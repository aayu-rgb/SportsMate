<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("
SELECT events.* FROM event_participants 
JOIN events ON event_participants.event_id = events.id
WHERE event_participants.user_id = ?
ORDER BY events.event_date ASC, events.event_time ASC
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Joined Events</title>
    <style>
        body {
            background: black;
            color: white;
            font-family: Arial;
            padding: 20px;
        }
        .event-box {
            background: #1f1f1f;
            padding: 15px;
            margin: 15px 0;
            border-radius: 12px;
        }
        a {
            color: #00eaff;
            text-decoration: none;
            font-size: 18px;
        }
        .back {
            display: inline-block;
            margin-top: 20px;
            color: white;
            background: #00eaff;
            padding: 10px 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<h2>My Joined Events</h2>

<?php if ($result->num_rows > 0) { ?>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <div class="event-box">
            <h3><?= htmlspecialchars($row['name'] ?? ($row['title'] ?? 'Event'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><strong>Sport:</strong> <?= $row['sport'] ?></p>
            <p><strong>Location:</strong> <?= $row['location'] ?></p>
            <p><strong>Date:</strong> <?= $row['event_date'] ?></p>
        </div>
    <?php } ?>
<?php } else { ?>
    <p>You have not joined any events yet 😊</p>
<?php } ?>

<a class="back" href="dashboard.php">⬅ Back to Dashboard</a>

</body>
</html>
