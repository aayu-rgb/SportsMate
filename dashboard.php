<?php
session_start();
if (!isset($_SESSION["user_name"])) {
    header("Location: login.php");
    exit;
}
$userName = $_SESSION["user_name"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SportsMate Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
    :root{
        --text:#e8f3ff;
        --muted:#bdd8f2;
        --line:rgba(117,180,240,.45);
        --panel:linear-gradient(160deg, rgba(8,24,45,.82), rgba(8,28,54,.66));
        --card:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
        --accent:#21ddff;
        --accent-2:#54ffb2;
        --hot:#ff5a6f;
    }
    *{box-sizing:border-box}
    body{
        margin:0;
        min-height:100vh;
        font-family:"Barlow",sans-serif;
        color:var(--text);
        background:
            linear-gradient(140deg, rgba(3,12,28,.9), rgba(5,22,48,.84)),
            url('https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=1500&q=80') center/cover no-repeat fixed;
    }
    .shell{
        width:min(1120px,95vw);
        margin:20px auto 28px;
    }
    .navbar{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        padding:14px 18px;
        background:var(--panel);
        border:1px solid var(--line);
        border-radius:16px;
        backdrop-filter:blur(4px);
    }
    .brand{
        display:flex;
        align-items:center;
        gap:10px;
        font-family:"Teko",sans-serif;
        font-size:34px;
        line-height:1;
        letter-spacing:.8px;
    }
    .dot{
        width:11px;
        height:11px;
        border-radius:50%;
        background:var(--hot);
        box-shadow:0 0 0 0 rgba(255,90,111,.5);
        animation:pulse 1.8s infinite;
    }
    .menu{
        display:flex;
        align-items:center;
        gap:14px;
        color:var(--muted);
        font-size:14px;
    }
    .menu a{
        color:#d7eeff;
        text-decoration:none;
        padding:8px 12px;
        border-radius:10px;
        border:1px solid transparent;
    }
    .menu a:hover{
        border-color:rgba(137,200,247,.55);
        background:rgba(255,255,255,.1);
    }
    .notif-link{
        position:relative;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:42px;
        min-height:38px;
    }
    .notif-badge{
        position:absolute;
        top:-6px;
        right:-6px;
        min-width:19px;
        height:19px;
        padding:0 5px;
        border-radius:999px;
        display:none;
        align-items:center;
        justify-content:center;
        font-size:11px;
        font-weight:700;
        color:#fff;
        background:linear-gradient(120deg,#ff4f69,#ff8d39);
        box-shadow:0 6px 14px rgba(255,79,105,.35);
    }
    .hero{
        margin-top:14px;
        border:1px solid rgba(126,190,244,.5);
        border-radius:22px;
        background:
            linear-gradient(130deg, rgba(6,20,44,.72), rgba(6,20,44,.58)),
            url('https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat,
            linear-gradient(120deg, rgba(255,90,111,.16), rgba(24,230,166,.12) 32%, rgba(17,216,255,.18));
        padding:28px 24px;
        position:relative;
        overflow:hidden;
    }
    .hero::before{
        content:"";
        position:absolute;
        right:-120px;
        top:-110px;
        width:300px;
        height:300px;
        border-radius:50%;
        background:radial-gradient(circle, rgba(17,216,255,.28), rgba(17,216,255,0) 70%);
        pointer-events:none;
    }
    .hero h1{
        margin:0;
        font-family:"Teko",sans-serif;
        font-size:56px;
        letter-spacing:.8px;
        line-height:.9;
    }
    .hero p{
        margin:8px 0 0;
        color:#eaf6ff;
        font-size:16px;
        max-width:620px;
    }
    .live{
        margin-top:16px;
        display:inline-block;
        border-radius:999px;
        padding:8px 13px;
        font-size:12px;
        font-weight:700;
        letter-spacing:.7px;
        background:linear-gradient(120deg,#ff4f69,#ff8d39);
    }
    .sports-mini{
        margin-top:14px;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }
    .sports-mini span{
        border:1px solid rgba(137,200,247,.55);
        border-radius:999px;
        padding:7px 10px;
        color:#d7eeff;
        background:rgba(255,255,255,.1);
        font-size:12px;
        font-weight:700;
    }
    .sports-mini i{
        margin-right:5px;
        color:#30e0ff;
    }
    .section{
        margin-top:18px;
        padding:14px;
        border-radius:20px;
        background:var(--panel);
        border:1px solid var(--line);
    }
    .section-head{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        margin-bottom:14px;
        padding:0 2px;
    }
    .section-head h2{
        margin:0;
        font-size:32px;
        font-family:"Teko",sans-serif;
        letter-spacing:.8px;
        color:#ecf8ff;
    }
    .section-head span{
        color:var(--muted);
        font-size:14px;
        font-weight:600;
    }
    .grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
        gap:14px;
    }
    .card{
        border:1px solid rgba(126,190,244,.5);
        border-radius:18px;
        background:var(--card);
        padding:18px;
        text-decoration:none;
        color:inherit;
        transition:transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        min-height:144px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        position:relative;
        overflow:hidden;
    }
    .card:hover{
        transform:translateY(-6px);
        border-color:#66d9ff;
        box-shadow:0 16px 28px rgba(11,35,60,.42);
    }
    .card::after{
        content:"";
        position:absolute;
        left:0;
        right:0;
        top:0;
        height:4px;
        background:linear-gradient(90deg, #24d9ff, #1fe8bc);
        opacity:.85;
    }
    .icon{
        width:44px;
        height:44px;
        border-radius:12px;
        background:linear-gradient(135deg, rgba(33,221,255,.28), rgba(84,255,178,.24));
        border:1px solid rgba(86,214,255,.62);
        display:grid;
        place-items:center;
        font-size:17px;
        color:#d7eeff;
        box-shadow:0 8px 16px rgba(14,54,84,.35);
    }
    .card h3{
        margin:12px 0 5px;
        font-size:20px;
        color:#ecf8ff;
    }
    .card p{
        margin:0;
        color:#a7caeb;
        font-size:14px;
        line-height:1.4;
    }
    .featured{
        margin-top:16px;
        border:1px solid var(--line);
        border-radius:16px;
        background:linear-gradient(140deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
        padding:18px;
    }
    .featured h3{
        margin:0 0 6px;
        font-size:25px;
        font-family:"Teko",sans-serif;
        letter-spacing:.5px;
    }
    .featured p{
        margin:0;
        color:#bdd8f2;
        line-height:1.5;
    }
    @keyframes pulse{
        0%{box-shadow:0 0 0 0 rgba(255,90,111,.5)}
        70%{box-shadow:0 0 0 9px rgba(255,90,111,0)}
        100%{box-shadow:0 0 0 0 rgba(255,90,111,0)}
    }
    @media (max-width:780px){
        .shell{width:94vw}
        .navbar{flex-direction:column;align-items:flex-start}
        .menu{flex-wrap:wrap}
        .hero h1{font-size:42px}
    }
</style>
</head>
<body>
    <div class="shell">
        <header class="navbar">
            <div class="brand">
                <span class="dot"></span>
                <span>SPORTSMATE</span>
            </div>
            <nav class="menu">
                <span>Hi, <strong><?= htmlspecialchars($userName, ENT_QUOTES, "UTF-8") ?></strong></span>
                <a href="notifications.php" class="notif-link" aria-label="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span id="notifBadge" class="notif-badge">0</span>
                </a>
                <a href="users_list.php">Friends</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <section class="hero">
            <h1>WELCOME <?= strtoupper(htmlspecialchars($userName, ENT_QUOTES, "UTF-8")) ?></h1>
            <p>Book tickets, discover events, shop kits, watch highlights, and chat with the AI assistant in one sports-first experience.</p>
            <span class="live">LIVE SPORTS HUB</span>
            <div class="sports-mini">
                <span><i class="fa-solid fa-futbol"></i>Football</span>
                <span><i class="fa-solid fa-basketball"></i>Basketball</span>
                <span><i class="fa-solid fa-volleyball"></i>Volleyball</span>
                <span><i class="fa-solid fa-baseball-bat-ball"></i>Cricket</span>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <h2>EXPLORE SPORTSMATE</h2>
                <span>Fast actions for your next matchday</span>
            </div>

            <div class="grid">
                <a class="card" href="events.php">
                    <div class="icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3>Explore Events</h3>
                    <p>Find upcoming matches and venue details.</p>
                </a>

                <a class="card" href="create_event.php">
                    <div class="icon"><i class="fa-solid fa-plus"></i></div>
                    <h3>Create Event</h3>
                    <p>Host your own sports event and invite players.</p>
                </a>

                <a class="card" href="tickets.php">
                    <div class="icon"><i class="fa-solid fa-ticket"></i></div>
                    <h3>Book Tickets</h3>
                    <p>Reserve your seat and manage bookings.</p>
                </a>

                <a class="card" href="kits.php">
                    <div class="icon"><i class="fa-solid fa-shirt"></i></div>
                    <h3>Sports Kits</h3>
                    <p>Browse equipment and training essentials.</p>
                </a>

                <a class="card" href="videos.php">
                    <div class="icon"><i class="fa-solid fa-video"></i></div>
                    <h3>Match Videos</h3>
                    <p>Watch highlights and key game moments.</p>
                </a>

                <a class="card" href="chat.php">
                    <div class="icon"><i class="fa-solid fa-comments"></i></div>
                    <h3>Player Chat</h3>
                    <p>Connect with players and sports friends.</p>
                </a>

                <a class="card" href="ai_chat.php">
                    <div class="icon"><i class="fa-solid fa-robot"></i></div>
                    <h3>AI Assistant</h3>
                    <p>Ask sports questions and get instant help.</p>
                </a>
            </div>
        </section>

        <section class="featured">
            <h3>FEEL THE ENERGY OF SPORT</h3>
            <p>Every click brings you closer to live action, smarter planning, and a better fan experience.</p>
        </section>
    </div>
<script>
(function(){
    var badge = document.getElementById("notifBadge");
    if (!badge) return;

    function refreshNotifCount() {
        fetch("fetch_notifications.php", { credentials: "same-origin" })
            .then(function(res){ return res.json(); })
            .then(function(data){
                var unread = Number(data.unread || 0);
                if (unread > 0) {
                    badge.style.display = "inline-flex";
                    badge.textContent = unread > 99 ? "99+" : String(unread);
                } else {
                    badge.style.display = "none";
                }
            })
            .catch(function(){});
    }

    refreshNotifCount();
    setInterval(refreshNotifCount, 12000);
})();
</script>
</body>
</html>
