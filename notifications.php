<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$markStmt = $conn->prepare("UPDATE notifications SET seen = 1 WHERE user_id = ?");
if ($markStmt) {
    $markStmt->bind_param("i", $user_id);
    $markStmt->execute();
    $markStmt->close();
}

$notifs = [];
$listStmt = $conn->prepare("SELECT id, message, seen, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
if ($listStmt) {
    $listStmt->bind_param("i", $user_id);
    $listStmt->execute();
    $res = $listStmt->get_result();
    while ($res && $row = $res->fetch_assoc()) {
        $notifs[] = $row;
    }
    $listStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:"Barlow",sans-serif;
    color:#e9f5ff;
    background:
      radial-gradient(circle at 8% 15%, rgba(34,216,255,.24) 0, rgba(34,216,255,0) 28%),
      radial-gradient(circle at 90% 82%, rgba(71,255,177,.15) 0, rgba(71,255,177,0) 32%),
      linear-gradient(145deg, #051023, #0b2342);
}
.wrap{width:min(900px,94vw);margin:24px auto}
.panel{
    border:1px solid rgba(124,189,241,.45);
    border-radius:20px;
    padding:20px;
    background:linear-gradient(160deg, rgba(10,30,56,.88), rgba(8,24,45,.78));
    box-shadow:0 18px 44px rgba(2,8,21,.36);
}
.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px}
.title{margin:0;font-family:"Teko",sans-serif;font-size:44px;line-height:.95;letter-spacing:.7px}
.sub{color:#a8cde9;font-size:14px}
.box{
    background:linear-gradient(145deg, rgba(11,36,66,.92), rgba(8,29,55,.88));
    border:1px solid rgba(108,173,228,.42);
    padding:14px;
    margin-bottom:10px;
    border-radius:12px;
}
.time{font-size:12px;color:#9ec5e3;margin-top:6px}
.empty{color:#bbd8f2}
.back{
    color:#7eeeff;
    text-decoration:none;
    font-weight:700;
    border:1px solid rgba(126,220,255,.48);
    border-radius:10px;
    padding:8px 12px;
}
.back:hover{background:rgba(38,214,255,.12)}
</style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <div class="head">
            <div>
                <h2 class="title">NOTIFICATIONS</h2>
                <div class="sub">Latest updates from events, friends, and activity</div>
            </div>
            <a class="back" href="dashboard.php">Back to Dashboard</a>
        </div>

        <?php if (count($notifs) === 0): ?>
            <p class="empty">No notifications yet.</p>
        <?php else: ?>
            <?php foreach ($notifs as $n): ?>
                <div class="box">
                    <?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8') ?>
                    <div class="time"><?= htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

</body>
</html>
