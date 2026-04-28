<?php
session_start();
require "config.php";
require_once "friend_utils.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sender = (int)$_SESSION['user_id'];
$receiver = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($receiver <= 0 || $receiver === $sender) {
    header("Location: users_list.php");
    exit;
}

$nameStmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
$receiverName = '';
if ($nameStmt) {
    $nameStmt->bind_param("i", $receiver);
    $nameStmt->execute();
    $nameRes = $nameStmt->get_result();
    $nameRow = $nameRes ? $nameRes->fetch_assoc() : null;
    $receiverName = $nameRow['name'] ?? '';
    $nameStmt->close();
}

if ($receiverName === '' || !are_friends($conn, $sender, $receiver)) {
    header("Location: users_list.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    background:#0b0f14;
    color:white;
    margin:0;
    padding:0;
    font-family: Inter, Arial, sans-serif;
    display:flex;
    flex-direction:column;
    height:100vh;
}
.header {
    background:#0f1720;
    padding:14px;
    font-size:18px;
    font-weight:700;
    border-bottom:1px solid rgba(255,255,255,0.08);
    display:flex;
    align-items:center;
    gap:10px;
}
.header a {
    color:#00eaff;
    text-decoration:none;
    font-size:20px;
}
.chat-box {
    flex:1;
    overflow-y:auto;
    padding:12px;
    display:flex;
    flex-direction:column;
}
.msg {
    max-width:70%;
    padding:10px 14px;
    background:#1b2733;
    margin-bottom:10px;
    border-radius:12px;
    font-size:15px;
    line-height:1.4;
    position:relative;
}
.self {
    background:linear-gradient(90deg,#00eaff,#06f6c8);
    color:#042226;
    align-self:flex-end;
}
.msg small {
    font-size:11px;
    color:#9fb4c6;
    display:block;
    margin-top:4px;
}
.input-area {
    padding:12px;
    background:#0f1720;
    display:flex;
    gap:10px;
    border-top:1px solid rgba(255,255,255,0.08);
}
textarea {
    flex:1;
    height:50px;
    border:none;
    border-radius:10px;
    padding:10px;
    resize:none;
    background:#07111a;
    color:white;
    font-size:15px;
}
button {
    background:#00eaff;
    border:none;
    padding:0 18px;
    border-radius:10px;
    font-weight:800;
    color:#042226;
    cursor:pointer;
    font-size:16px;
}
#typing {
    color:#06f6c8;
    margin:5px 12px;
    font-style:italic;
    font-size:13px;
}
@media(max-width:480px){
    .msg { max-width:88%; }
}
</style>

</head>
<body>

<div class="header">
    <a href="users_list.php">Back</a>
    Chat with <?= htmlspecialchars($receiverName, ENT_QUOTES, 'UTF-8') ?>
</div>

<div class="chat-box" id="chatArea"></div>

<p id="typing"></p>

<div class="input-area">
    <textarea id="message" placeholder="Type your message..."></textarea>
    <button onclick="sendMsg()">Send</button>
</div>

<script>
setInterval(loadMessages, 1000);

function loadMessages() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_messages.php?receiver=<?= $receiver ?>", true);
    xhr.onload = function() {
        let chatArea = document.getElementById("chatArea");
        chatArea.innerHTML = this.responseText;
        chatArea.scrollTop = chatArea.scrollHeight;
    }
    xhr.send();
}

function sendMsg() {
    let msg = document.getElementById("message").value;
    if(msg.trim() === "") return;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "send_message.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("receiver=<?= $receiver ?>&message=" + encodeURIComponent(msg));

    document.getElementById("message").value = "";
    loadMessages();
}

document.getElementById("message").addEventListener("input", () => {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "typing_status.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("receiver=<?= $receiver ?>");
});

setInterval(() => {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "who_typing.php?receiver=<?= $receiver ?>", true);
    xhr.onload = function () {
        document.getElementById("typing").innerHTML = this.responseText;
    };
    xhr.send();
}, 1000);
</script>

</body>
</html>
