<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$matches = [];
$matchError = '';
$availableCols = [];

try {
    $colsResult = $conn->query("SHOW COLUMNS FROM matches");
    if ($colsResult) {
        while ($col = $colsResult->fetch_assoc()) {
            $field = (string)($col['Field'] ?? '');
            if ($field !== '') {
                $availableCols[$field] = true;
            }
        }
    }
} catch (Throwable $e) {
    $matchError = "Direct booking list is unavailable right now.";
}

if ($matchError === '') {
    if (!isset($availableCols['id'])) {
        $matchError = "Direct booking list is unavailable right now.";
    } else {
        $selectCols = ['id'];
        foreach (['match_name', 'sport', 'venue', 'district', 'match_date', 'match_time', 'ticket_url'] as $field) {
            if (isset($availableCols[$field])) {
                $selectCols[] = $field;
            }
        }

        $orderParts = [];
        if (isset($availableCols['match_date'])) {
            $orderParts[] = "CASE WHEN match_date IS NULL OR match_date = '' THEN 1 ELSE 0 END";
            $orderParts[] = "match_date ASC";
        }
        if (isset($availableCols['match_time'])) {
            $orderParts[] = "match_time ASC";
        }
        $orderParts[] = "id DESC";

        $query = "SELECT " . implode(", ", $selectCols) . " FROM matches";
        if (!empty($orderParts)) {
            $query .= " ORDER BY " . implode(", ", $orderParts);
        }
        $query .= " LIMIT 24";

        try {
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $matches[] = $row;
                }
            } else {
                $matchError = "Direct booking list is unavailable right now.";
            }
        } catch (Throwable $e) {
            $matchError = "Direct booking list is unavailable right now.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Match Tickets</title>
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
      linear-gradient(140deg, rgba(4,12,28,.9), rgba(5,23,48,.82)),
      url('https://images.unsplash.com/photo-1471295253337-3ceaaedca402?auto=format&fit=crop&w=1500&q=80') center/cover no-repeat fixed;
}
.wrap{width:min(940px,92vw);margin:26px auto}
.panel{
    border:1px solid rgba(117,180,240,.45);
    background:linear-gradient(160deg, rgba(8,24,45,.82), rgba(8,28,54,.66));
    border-radius:22px;
    padding:24px;
    backdrop-filter:blur(4px);
}
.title{margin:0;font-family:"Teko",sans-serif;font-size:52px;letter-spacing:.8px;line-height:.9}
.sub{margin:6px 0 18px;color:#bad6f3}
.section-title{
    margin:16px 0 10px;
    font-family:"Teko",sans-serif;
    font-size:36px;
    letter-spacing:.6px;
    line-height:.9;
}
.section-note{margin:0 0 12px;color:#c0dbf4;font-size:14px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
.match-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}
.match-card{
    border:1px solid rgba(126,190,244,.5);
    border-radius:14px;
    padding:14px;
    background:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
}
.match-card h3{margin:0 0 8px;font-size:20px;color:#eff8ff}
.meta{display:flex;gap:8px;align-items:flex-start;color:#a9caeb;font-size:14px;margin-bottom:6px}
.meta i{width:16px;color:#2be0ff;margin-top:2px}
.row{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:10px}
.pill{
    border-radius:999px;
    font-size:12px;
    padding:6px 10px;
    border:1px solid rgba(121,204,246,.45);
    background:rgba(92,209,255,.16);
    color:#d6f2ff;
}
.book-btn{
    display:inline-flex;
    align-items:center;
    gap:6px;
    text-decoration:none;
    font-weight:700;
    font-size:14px;
    border-radius:10px;
    padding:8px 12px;
    color:#052334;
    background:linear-gradient(120deg,#21ddff,#54ffb2);
}
.empty{
    border:1px dashed rgba(121,204,246,.45);
    border-radius:12px;
    padding:12px;
    color:#c4def6;
}
.ticket-link{
    display:block;
    text-decoration:none;
    border:1px solid rgba(126,190,244,.5);
    border-radius:14px;
    padding:16px;
    color:#e9f7ff;
    background:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
    transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;
}
.ticket-link i{font-size:22px;color:#2be0ff;display:block;margin-bottom:10px}
.ticket-link strong{font-size:20px;display:block}
.ticket-link span{color:#a9caeb;font-size:14px}
.ticket-link:hover{transform:translateY(-3px);border-color:#54dcff;box-shadow:0 14px 24px rgba(0,142,204,.26)}
.back{display:inline-flex;align-items:center;gap:8px;margin-top:16px;color:#9fe9ff;text-decoration:none;font-weight:700}
.back:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <h1 class="title">MATCH TICKET BOOKING</h1>
        <p class="sub">Book directly from listed matches or use ticket platforms below.</p>

        <h2 class="section-title">DIRECT BOOK SPOTS</h2>
        <p class="section-note">One click booking. No district search needed if your match is listed.</p>

        <?php if ($matchError): ?>
            <p class="empty"><?= htmlspecialchars($matchError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (!empty($matches)): ?>
            <div class="match-grid">
                <?php foreach ($matches as $m): ?>
                    <?php
                        $id = (int)($m['id'] ?? 0);
                        $name = trim((string)($m['match_name'] ?? 'Match'));
                        $sport = trim((string)($m['sport'] ?? 'Sports'));
                        $venue = trim((string)($m['venue'] ?? 'Venue TBA'));
                        $district = trim((string)($m['district'] ?? ''));
                        $date = trim((string)($m['match_date'] ?? ''));
                        $time = trim((string)($m['match_time'] ?? ''));
                        $when = trim($date . ($time ? " | " . $time : ""));
                    ?>
                    <article class="match-card">
                        <h3><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="meta"><i class="fa-solid fa-futbol"></i><span><?= htmlspecialchars($sport, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="meta"><i class="fa-solid fa-location-dot"></i><span><?= htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') ?><?= $district !== '' ? ' - ' . htmlspecialchars($district, ENT_QUOTES, 'UTF-8') : '' ?></span></div>
                        <div class="meta"><i class="fa-solid fa-calendar-days"></i><span><?= htmlspecialchars($when !== '' ? $when : 'Schedule TBA', ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="row">
                            <span class="pill">Match #<?= $id ?></span>
                            <a class="book-btn" href="book_ticket.php?id=<?= $id ?>"><i class="fa-solid fa-ticket"></i>Book Spot</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">No direct matches found yet. Use platforms below as backup.</p>
        <?php endif; ?>

        <h2 class="section-title">PLATFORM BOOKING</h2>
        <p class="section-note">If the match is not listed above, book via official ticket providers.</p>

        <div class="grid">
            <a class="ticket-link" href="https://in.bookmyshow.com" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-ticket"></i>
                <strong>Cricket Matches</strong>
                <span>Book domestic and league cricket fixtures.</span>
            </a>
            <a class="ticket-link" href="https://insider.in" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-futbol"></i>
                <strong>Football Matches</strong>
                <span>Find stadium games and football events.</span>
            </a>
            <a class="ticket-link" href="https://www.stubhub.com" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-earth-asia"></i>
                <strong>International Sports</strong>
                <span>Explore global events and premium seats.</span>
            </a>
        </div>

        <a class="back" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i>Back to Dashboard</a>
    </section>
</div>
</body>
</html>
