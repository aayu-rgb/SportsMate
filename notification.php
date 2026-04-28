<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* MARK ALL NOTIFICATIONS AS READ */
$update = "UPDATE notifications SET seen = 1 WHERE user_id = $user_id AND seen = 0";
mysqli_query($conn, $update);

/* FETCH ALL NOTIFICATIONS */
$query = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <a href="dashboard.php" class="back-btn">⬅ Back</a>
    <h2>Notifications</h2>
.noti-card {
    padding: 15px;
    border-radius: 10px;
    background: #1a1a1a;
    margin: 12px;
}
@media(max-width:480px) {
    .noti-card {
        margin: 10px;
        font-size: 15px;
    }
}

</div>

<div class="noti-list">
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <div class="noti-card <?= $row['status'] == 'unread' ? 'unread' : '' ?>">
            <p><?= $row['message'] ?></p>
            <span class="time"><?= $row['created_at'] ?></span>
        </div>
    <?php } ?>
</div>

</body>
</html>
