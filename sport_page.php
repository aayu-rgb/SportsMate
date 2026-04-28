<?php
session_start();

// User must be logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

// SPORTS ARRAY (static)
$sports = [
    ["⚽", "Football"],
    ["🏀", "Basketball"],
    ["🎾", "Tennis"],
    ["🏏", "Cricket"],
    ["🏐", "Volleyball"],
    ["🏓", "Table Tennis"],
    ["🥊", "Boxing"],
    ["🏃", "Running"],
    ["🏊", "Swimming"],
    ["⚡", "Badminton"]
];

// Get selected sport name
$sportName = $_GET['sport'] ?? null;

// Find sport from list
$selectedSport = null;
foreach ($sports as $s) {
    if ($s[1] == $sportName) {
        $selectedSport = $s;
        break;
    }
}

// If sport invalid → redirect
if (!$selectedSport) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $sportName ?> - SportsMate</title>

<style>
:root{
    --neon:#06f6c8;
}

body {
    margin:0;
    background:linear-gradient(180deg,#07080a 0%, #0f1724 100%);
    color:white;
    font-family:Arial, sans-serif;
}

.container {
    max-width: 700px;
    margin: 40px auto;
    padding: 20px;
}

/* back button */
.back {
    text-decoration:none;
    color:var(--neon);
    font-size:18px;
    display:inline-block;
    margin-bottom:20px;
}

/* main card */
.card {
    background:rgba(255,255,255,0.04);
    padding:30px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,0.08);
    box-shadow:0 6px 20px rgba(0,0,0,0.5);
}

/* heading */
.card h1 {
    margin-top:0;
    font-size:32px;
    color:var(--neon);
}

/* button */
button {
    margin-top:20px;
    padding:12px 20px;
    background:linear-gradient(90deg,var(--neon),#07b3ff);
    color:#052023;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:18px;
    font-weight:600;
    transition:0.3s;
}

button:hover {
    opacity:0.85;
}
</style>

</head>
<body>

<div class="container">

    <a class="back" href="dashboard.php">⬅ Back to Dashboard</a>

    <div class="card">

        <h1><?= $selectedSport[0] ?> <?= $selectedSport[1] ?></h1>

        <p>
            Soon you will be able to explore everything about <strong><?= $selectedSport[1] ?></strong>:
        </p>

        <ul>
            <li>Join training sessions</li>
            <li>Find teammates nearby</li>
            <li>Join tournaments and events</li>
            <li>Track your sports progress</li>
        </ul>

        <button>Coming Soon 🚀</button>

    </div>

</div>

</body>
</html>
