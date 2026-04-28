<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>SportsMate</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*{box-sizing:border-box}
body{
margin:0;
font-family:"Barlow",sans-serif;
color:#eaf5ff;
min-height:100vh;
background:
linear-gradient(115deg, rgba(7,11,26,.88), rgba(8,25,51,.78)),
url('https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1800&q=80') center/cover fixed no-repeat;
}
.wrap{
width:min(1100px,94vw);
margin:0 auto;
padding:18px 0 34px;
}
.header{
padding:14px 16px;
display:flex;
justify-content:space-between;
align-items:center;
border:1px solid rgba(130,186,235,.35);
border-radius:16px;
background:linear-gradient(130deg,rgba(13,25,48,.84),rgba(10,34,66,.62));
backdrop-filter:blur(6px);
}
.logo{
font-family:"Teko",sans-serif;
font-size:40px;
letter-spacing:.8px;
line-height:1;
display:flex;
align-items:center;
gap:10px;
}
.logo i{
font-size:28px;
color:#1bd8ff;
}
.btn{
background:linear-gradient(120deg,#1cd9ff,#4cffa7);
color:#042536;
padding:10px 18px;
border-radius:10px;
text-decoration:none;
font-weight:700;
}
.header-actions{
display:flex;
align-items:center;
gap:10px;
}
.btn.alt{
background:rgba(255,255,255,.08);
color:#d7eafc;
border:1px solid rgba(164,204,239,.42);
}
.hero{
margin-top:16px;
padding:46px 24px 40px;
border-radius:20px;
border:1px solid rgba(130,186,235,.35);
background:
linear-gradient(135deg, rgba(255,95,120,.16), rgba(27,216,255,.15)),
url('https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat;
position:relative;
overflow:hidden;
}
.hero::before{
content:"";
position:absolute;
inset:0;
background:linear-gradient(145deg, rgba(5,17,35,.73), rgba(5,27,50,.58));
}
.hero-inner{
position:relative;
z-index:2;
}
.hero h1{
font-family:"Teko",sans-serif;
font-size:66px;
line-height:.9;
letter-spacing:.9px;
margin:0 0 10px;
}
.hero p{
color:#c7def4;
font-size:18px;
max-width:640px;
margin:0;
}
.hero-actions{
margin-top:18px;
display:flex;
gap:10px;
flex-wrap:wrap;
}
.ghost{
background:rgba(255,255,255,.08);
color:#d7eafc;
border:1px solid rgba(164,204,239,.42);
}
.sport-strip{
margin:14px 0 0;
display:grid;
grid-template-columns:repeat(5,minmax(0,1fr));
gap:10px;
}
.sport-pill{
display:flex;
align-items:center;
justify-content:center;
gap:8px;
padding:9px 10px;
border-radius:999px;
background:rgba(9,31,59,.62);
border:1px solid rgba(112,170,227,.36);
font-size:13px;
font-weight:600;
}
.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:14px;
margin-top:14px;
}
.card{
background:linear-gradient(145deg,rgba(15,38,67,.88),rgba(8,29,52,.73));
border:1px solid rgba(112,170,227,.36);
padding:18px 16px;
border-radius:15px;
}
.card .ico{
width:44px;
height:44px;
border-radius:10px;
display:grid;
place-items:center;
font-size:18px;
background:linear-gradient(135deg, rgba(28,217,255,.26), rgba(76,255,167,.22));
border:1px solid rgba(119,233,255,.45);
margin-bottom:10px;
}
.card h3{
margin:0 0 6px;
font-size:19px;
}
.card p{
margin:0;
font-size:14px;
color:#b4d1ed;
}
@media (max-width:760px){
.hero h1{font-size:48px}
.sport-strip{grid-template-columns:repeat(2,minmax(0,1fr))}
}
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="logo"><i class="fa-solid fa-trophy"></i>SPORTSMATE</div>
        <?php if(!isset($_SESSION['user_id'])): ?>
        <div class="header-actions">
            <a class="btn alt" href="login.php">Login</a>
            <a class="btn" href="register.php">Register</a>
        </div>
        <?php else: ?>
        <a class="btn" href="dashboard.php">Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="hero">
        <div class="hero-inner">
            <h1>PLAY CONNECT COMPETE</h1>
            <p>Join events, track matches, chat with fans, and discover sports moments with a modern all-in-one hub.</p>
            <div class="hero-actions">
                <?php if(!isset($_SESSION['user_id'])): ?>
                <a class="btn" href="register.php">Get Started</a>
                <?php endif; ?>
                <a class="btn ghost" href="events.php">Explore Events</a>
            </div>
            <div class="sport-strip">
                <div class="sport-pill"><i class="fa-solid fa-futbol"></i>Football</div>
                <div class="sport-pill"><i class="fa-solid fa-basketball"></i>Basketball</div>
                <div class="sport-pill"><i class="fa-solid fa-baseball-bat-ball"></i>Cricket</div>
                <div class="sport-pill"><i class="fa-solid fa-football"></i>Rugby</div>
                <div class="sport-pill"><i class="fa-solid fa-volleyball"></i>Volleyball</div>
            </div>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="ico"><i class="fa-solid fa-ranking-star"></i></div>
            <h3>All Sports</h3>
            <p>Discover trending sports and active communities.</p>
        </div>
        <div class="card">
            <div class="ico"><i class="fa-solid fa-calendar-days"></i></div>
            <h3>Events</h3>
            <p>Find tournaments, local games, and match schedules.</p>
        </div>
        <div class="card">
            <div class="ico"><i class="fa-solid fa-comments"></i></div>
            <h3>Chat</h3>
            <p>Connect instantly with players and sports fans.</p>
        </div>
        <div class="card">
            <div class="ico"><i class="fa-solid fa-video"></i></div>
            <h3>Match Videos</h3>
            <p>Watch highlights and relive match-winning moments.</p>
        </div>
    </div>
</div>
</body>
</html>
