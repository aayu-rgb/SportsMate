<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SportsMate AI Assistant</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{
    --bg-a:#ecf8ff;
    --bg-b:#d8efff;
    --panel:#ffffff;
    --panel-2:#f0f8ff;
    --line:#b8d8f4;
    --accent:#18d3ff;
    --accent-2:#27e7be;
    --text:#0c2e52;
    --muted:#4d6f96;
    --chip:#e6f5ff;
}
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:"Barlow",sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 18% 8%, rgba(16,180,255,.30) 0, rgba(16,180,255,0) 36%),
        radial-gradient(circle at 84% 78%, rgba(39,231,190,.22) 0, rgba(39,231,190,0) 36%),
        linear-gradient(160deg, var(--bg-a), var(--bg-b));
    padding:26px 16px;
}
.container{
    width:min(1020px,95vw);
    margin:0 auto;
    background:linear-gradient(165deg, rgba(255,255,255,.97), rgba(241,249,255,.98));
    border:1px solid rgba(110,162,218,.45);
    border-radius:22px;
    padding:18px;
    display:flex;
    flex-direction:column;
    height:90vh;
    box-shadow:0 22px 50px rgba(55,124,187,.20);
}
.header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    padding:4px 2px 10px 2px;
}
.title{
    font-family:"Teko",sans-serif;
    letter-spacing:.8px;
    font-size:42px;
    line-height:1;
}
.subtitle{
    color:var(--muted);
    margin-top:4px;
    font-size:14px;
}
.stats{
    display:flex;
    gap:8px;
    margin-top:10px;
    flex-wrap:wrap;
}
.stat-chip{
    display:flex;
    align-items:center;
    gap:7px;
    border:1px solid #b4d5f1;
    background:var(--chip);
    color:#235283;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    padding:6px 12px;
}
.live-badge{
    background:linear-gradient(120deg,#ff3f62,#ff7a32);
    color:#fff;
    font-weight:700;
    letter-spacing:.6px;
    font-size:12px;
    border-radius:30px;
    padding:8px 14px;
    box-shadow:0 0 0 0 rgba(255,80,90,.45);
    animation:pulse 1.8s infinite;
}
@keyframes pulse{
    0%{box-shadow:0 0 0 0 rgba(255,80,90,.45)}
    70%{box-shadow:0 0 0 11px rgba(255,80,90,0)}
    100%{box-shadow:0 0 0 0 rgba(255,80,90,0)}
}
.sport-strip{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:10px;
    margin:4px 0 12px 0;
}
.sport-card{
    border:1px solid #bcd9f2;
    background:linear-gradient(150deg,#f4fbff,#e8f6ff);
    border-radius:12px;
    padding:10px 8px;
    text-align:center;
    color:#1f4f82;
    font-weight:600;
    font-size:12px;
}
.sport-card i{
    display:block;
    font-size:18px;
    margin-bottom:6px;
    color:#147ac5;
}
.chat-box{
    flex:1;
    overflow-y:auto;
    padding:12px 8px;
    display:flex;
    flex-direction:column;
    gap:11px;
    scroll-behavior:smooth;
    border:1px solid rgba(120,167,214,.36);
    border-radius:14px;
    background:rgba(255,255,255,.58);
}
.msg-row{
    display:flex;
    gap:8px;
    align-items:flex-end;
}
.msg-row.user-row{justify-content:flex-end}
.avatar{
    width:30px;
    height:30px;
    border-radius:50%;
    display:grid;
    place-items:center;
    font-weight:700;
    font-size:12px;
    background:#dff1ff;
    color:#235283;
    border:1px solid #9bc6ea;
    flex-shrink:0;
}
.msg{
    padding:11px 14px;
    border-radius:14px;
    max-width:min(76%,680px);
    line-height:1.4;
    word-wrap:break-word;
    border:1px solid transparent;
}
.msg.user{
    background:linear-gradient(135deg, var(--accent), #74efff);
    color:#053a46;
    border-color:#b6edff;
    font-weight:600;
}
.msg.bot{
    background:linear-gradient(145deg, #eff8ff, #e6f4ff);
    border-color:#b9d8f3;
    color:#163b64;
}
.typing{
    color:var(--muted);
    font-size:13px;
    padding:8px 4px 2px 6px;
}
.input-area{
    display:flex;
    gap:10px;
    margin-top:8px;
    padding-top:12px;
    border-top:1px solid rgba(67,101,156,.5);
}
input{
    flex:1;
    padding:13px 14px;
    border-radius:11px;
    border:1px solid #9ec6e9;
    outline:none;
    font-size:16px;
    color:#163a62;
    background:#ffffff;
}
input::placeholder{color:#6f91b9}
input:focus{
    border-color:#17c9db;
    box-shadow:0 0 0 3px rgba(23,201,219,.18);
}
button{
    padding:0 20px;
    min-width:108px;
    border:none;
    border-radius:11px;
    cursor:pointer;
    font-size:15px;
    font-weight:700;
    color:#0b4a5a;
    background:linear-gradient(135deg, #4dd9ff, #62f1cf);
    transition:transform .15s ease, box-shadow .15s ease;
    box-shadow:0 10px 24px rgba(0,187,180,.28);
}
button i{margin-right:6px}
button:hover{transform:translateY(-1px)}
button:active{transform:translateY(0)}
@media (max-width:900px){
    .sport-strip{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media (max-width:700px){
    .container{height:92vh;padding:14px}
    .title{font-size:34px}
    .msg{max-width:88%}
    button{min-width:84px;padding:0 14px}
    .header{align-items:center}
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <div class="title">SPORTSMATE AI ASSISTANT</div>
            <div class="subtitle">Match-day help for tickets, kits, schedules and sports tips</div>
            <div class="stats">
                <div class="stat-chip"><i class="fa-solid fa-ticket"></i> Ticket Help</div>
                <div class="stat-chip"><i class="fa-solid fa-shirt"></i> Kit Finder</div>
                <div class="stat-chip"><i class="fa-solid fa-calendar-days"></i> Live Schedules</div>
            </div>
        </div>
        <div class="live-badge">LIVE</div>
    </div>

    <div class="sport-strip">
        <div class="sport-card"><i class="fa-solid fa-futbol"></i>Football</div>
        <div class="sport-card"><i class="fa-solid fa-basketball"></i>Basketball</div>
        <div class="sport-card"><i class="fa-solid fa-baseball-bat-ball"></i>Cricket</div>
        <div class="sport-card"><i class="fa-solid fa-football"></i>Rugby</div>
        <div class="sport-card"><i class="fa-solid fa-table-tennis-paddle-ball"></i>Tennis</div>
        <div class="sport-card"><i class="fa-solid fa-volleyball"></i>Volleyball</div>
    </div>

    <div class="chat-box" id="chatBox"></div>
    <div class="typing" id="typingHint" style="display:none;">Assistant is thinking...</div>

    <div class="input-area">
        <input type="text" id="message" placeholder="Ask about sports, tickets, kits...">
        <button type="button" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i>Send</button>
    </div>
</div>

<script>
function appendMessage(role, text) {
    const chatBox = document.getElementById("chatBox");
    const row = document.createElement("div");
    row.className = "msg-row " + (role === "user" ? "user-row" : "bot-row");

    if (role !== "user") {
        const avatar = document.createElement("div");
        avatar.className = "avatar";
        avatar.textContent = "AI";
        row.appendChild(avatar);
    }

    const bubble = document.createElement("div");
    bubble.className = "msg " + role;
    bubble.textContent = text;
    row.appendChild(bubble);

    if (role === "user") {
        const avatar = document.createElement("div");
        avatar.className = "avatar";
        avatar.textContent = "YOU";
        row.appendChild(avatar);
    }

    chatBox.appendChild(row);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function sendMessage() {
    const msgInput = document.getElementById("message");
    const typingHint = document.getElementById("typingHint");
    const message = msgInput.value.trim();
    if (!message) return;

    appendMessage("user", message);
    msgInput.value = "";
    typingHint.style.display = "block";

    fetch("ai_response.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "message=" + encodeURIComponent(message)
    })
    .then(res => res.json())
    .then(data => {
        appendMessage("bot", data.reply || "No reply available.");
    })
    .catch(() => {
        appendMessage("bot", "Server error.");
    })
    .finally(() => {
        typingHint.style.display = "none";
    });
}

document.getElementById("message").addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
        sendMessage();
    }
});
</script>
</body>
</html>

