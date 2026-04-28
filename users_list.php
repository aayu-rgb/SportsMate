<?php
session_start();
require "config.php";
require_once "notification_utils.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user = (int)$_SESSION['user_id'];
$flash = '';
$me = null;

$meStmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
if ($meStmt) {
    $meStmt->bind_param("i", $current_user);
    $meStmt->execute();
    $meRes = $meStmt->get_result();
    $me = $meRes ? $meRes->fetch_assoc() : null;
    $meStmt->close();
}

if (isset($_GET['add'])) {
    $receiver = (int)$_GET['add'];
    if ($receiver > 0 && $receiver !== $current_user) {
        $checkStmt = $conn->prepare("SELECT id FROM friends WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("iiii", $current_user, $receiver, $receiver, $current_user);
            $checkStmt->execute();
            $checkStmt->store_result();
            $exists = $checkStmt->num_rows > 0;
            $checkStmt->close();

            if (!$exists) {
                $addStmt = $conn->prepare("INSERT INTO friends (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
                if ($addStmt) {
                    $addStmt->bind_param("ii", $current_user, $receiver);
                    $addStmt->execute();
                    $addStmt->close();
                    push_notification($conn, $receiver, "You received a friend request.");
                    $flash = 'Friend request sent.';
                }
            } else {
                $flash = 'Friend request already exists.';
            }
        }
    }
}

if (isset($_GET['accept'])) {
    $sender = (int)$_GET['accept'];
    if ($sender > 0 && $sender !== $current_user) {
        $acceptStmt = $conn->prepare("UPDATE friends SET status='accepted' WHERE sender_id=? AND receiver_id=? AND status='pending'");
        if ($acceptStmt) {
            $acceptStmt->bind_param("ii", $sender, $current_user);
            $acceptStmt->execute();
            if ($acceptStmt->affected_rows > 0) {
                push_notification($conn, $sender, "Your friend request was accepted.");
                $flash = 'Friend request accepted.';
            }
            $acceptStmt->close();
        }
    }
}

if (isset($_GET['decline'])) {
    $sender = (int)$_GET['decline'];
    if ($sender > 0 && $sender !== $current_user) {
        $declineStmt = $conn->prepare("DELETE FROM friends WHERE sender_id=? AND receiver_id=? AND status='pending'");
        if ($declineStmt) {
            $declineStmt->bind_param("ii", $sender, $current_user);
            $declineStmt->execute();
            $declineStmt->close();
            $flash = 'Friend request declined.';
        }
    }
}

if (isset($_GET['remove'])) {
    $friend = (int)$_GET['remove'];
    if ($friend > 0 && $friend !== $current_user) {
        $removeStmt = $conn->prepare("DELETE FROM friends WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)");
        if ($removeStmt) {
            $removeStmt->bind_param("iiii", $current_user, $friend, $friend, $current_user);
            $removeStmt->execute();
            $removeStmt->close();
            $flash = 'Friend removed.';
        }
    }
}

$usersStmt = $conn->prepare("SELECT id, name, email, created_at FROM users WHERE id != ? ORDER BY created_at DESC, id DESC");
$users = null;
if ($usersStmt) {
    $usersStmt->bind_param("i", $current_user);
    $usersStmt->execute();
    $users = $usersStmt->get_result();
}

$snapshotCount = 0;
$snapshotLatestId = 0;
$snapshotStmt = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(MAX(id), 0) AS latest_id FROM users WHERE id != ?");
if ($snapshotStmt) {
    $snapshotStmt->bind_param("i", $current_user);
    $snapshotStmt->execute();
    $snapshotRes = $snapshotStmt->get_result();
    $snapshot = $snapshotRes ? $snapshotRes->fetch_assoc() : null;
    $snapshotCount = (int)($snapshot['total'] ?? 0);
    $snapshotLatestId = (int)($snapshot['latest_id'] ?? 0);
    $snapshotStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SportsMate | Friends</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<style>
:root {
    --bg-1:#061127;
    --bg-2:#0b2342;
    --panel:#0d2747;
    --panel-2:#11335b;
    --line:#2f6ba0;
    --text:#e9f5ff;
    --muted:#9dc3e2;
    --ok:#47ffb1;
    --warn:#ffd36a;
    --danger:#ff6b86;
    --accent:#22d8ff;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    min-height: 100vh;
    font-family: "Barlow", sans-serif;
    color: var(--text);
    background:
        radial-gradient(circle at 10% 16%, rgba(34,216,255,.24) 0, rgba(34,216,255,0) 34%),
        radial-gradient(circle at 88% 84%, rgba(71,255,177,.14) 0, rgba(71,255,177,0) 30%),
        linear-gradient(155deg, var(--bg-1), var(--bg-2));
    padding: 18px;
}
.wrap { width: min(980px, 96vw); margin: 0 auto; }
.header {
    border: 1px solid rgba(122,171,220,.55);
    border-radius: 18px;
    background: linear-gradient(165deg, rgba(12,34,63,.9), rgba(10,29,54,.82));
    box-shadow: 0 18px 44px rgba(4,12,28,.34);
    padding: 16px;
}
.top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.title {
    margin: 0;
    font-family: "Teko", sans-serif;
    font-size: 44px;
    line-height: .9;
    letter-spacing: .7px;
}
.sub { color: var(--muted); font-size: 14px; }
.back {
    text-decoration: none;
    color: #032437;
    background: linear-gradient(120deg, var(--accent), var(--ok));
    border-radius: 10px;
    font-weight: 700;
    padding: 9px 12px;
}
.tools {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    margin-top: 14px;
}
.search {
    width: 100%;
    border: 1px solid #3b79af;
    background: #0a223f;
    color: #e7f4ff;
    border-radius: 11px;
    padding: 11px 12px;
    font-size: 15px;
    outline: none;
}
.filters { display: flex; gap: 8px; flex-wrap: wrap; }
.fbtn {
    border: 1px solid rgba(96,157,212,.55);
    background: #0b2a4d;
    color: #d4edff;
    padding: 9px 12px;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
}
.fbtn.active { background: linear-gradient(120deg, #1578b5, #1ba67f); border-color: transparent; }
.flash {
    margin-top: 10px;
    border: 1px solid rgba(71,255,177,.5);
    background: rgba(71,255,177,.12);
    color: #d8fff0;
    border-radius: 10px;
    padding: 10px 12px;
}
.list { margin-top: 14px; display: grid; gap: 10px; }
.card {
    border: 1px solid rgba(122,171,220,.45);
    border-radius: 14px;
    background: linear-gradient(155deg, rgba(12,34,63,.9), rgba(10,29,54,.82));
    padding: 14px;
}
.card.self { border-color: rgba(53,134,200,.8); }
.row { display: flex; justify-content: space-between; gap: 10px; align-items: center; flex-wrap: wrap; }
.name { font-weight: 700; font-size: 18px; }
.small { color: var(--muted); font-size: 13px; margin-top: 4px; }
.status { font-size: 13px; font-weight: 700; }
.status.pending { color: var(--warn); }
.status.friend { color: var(--ok); }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn {
    text-decoration: none;
    border-radius: 9px;
    padding: 8px 11px;
    font-size: 13px;
    font-weight: 700;
    color: #032437;
    background: linear-gradient(120deg, var(--accent), var(--ok));
}
.btn.alt { background: #1b4b73; color: #d8efff; }
.btn.warn { background: #ffb341; color: #2c1800; }
.btn.danger { background: var(--danger); color: #2f0610; }
.empty {
    margin-top: 10px;
    color: var(--muted);
    border: 1px dashed rgba(122,171,220,.4);
    border-radius: 10px;
    padding: 14px;
}
.live-toast {
    position: fixed;
    right: 16px;
    bottom: 16px;
    background: #0f2b48;
    color: #c8ecff;
    border: 1px solid #33b9ff;
    border-radius: 10px;
    padding: 10px 12px;
    display: none;
    font-size: 14px;
    z-index: 9999;
}
@media (max-width:720px){
    .title { font-size: 36px; }
    .tools { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="wrap">
    <section class="header">
        <div class="top">
            <div>
                <h1 class="title">FRIENDS HUB</h1>
                <div class="sub">Discover newly registered users, send requests, and start chatting after acceptance.</div>
            </div>
            <a class="back" href="dashboard.php">Back</a>
        </div>

        <div class="tools">
            <input id="searchInput" class="search" type="text" placeholder="Search by name or email...">
            <div class="filters">
                <button class="fbtn active" data-filter="all" type="button">All</button>
                <button class="fbtn" data-filter="requests" type="button">Requests</button>
                <button class="fbtn" data-filter="friends" type="button">Friends</button>
                <button class="fbtn" data-filter="discover" type="button">Discover</button>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="flash"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </section>

    <section class="list" id="friendList">
        <?php if ($me): ?>
            <article class="card self" data-name="<?= htmlspecialchars(strtolower((string)($me['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars(strtolower((string)($me['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-state="self">
                <div class="row">
                    <div>
                        <div class="name"><?= htmlspecialchars(($me['name'] ?? 'User') . ' (You)', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!empty($me['email'])): ?><div class="small"><?= htmlspecialchars($me['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        <div class="small">Your own account is excluded from add-friend actions.</div>
                    </div>
                    <span class="status">Self</span>
                </div>
            </article>
        <?php endif; ?>

        <?php $hasRows = false; ?>
        <?php if ($users): ?>
            <?php while($user = $users->fetch_assoc()): ?>
                <?php
                    $uid = (int)$user['id'];
                    $friendStmt = $conn->prepare("SELECT sender_id, receiver_id, status FROM friends WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) LIMIT 1");
                    $friendCheck = null;
                    if ($friendStmt) {
                        $friendStmt->bind_param("iiii", $current_user, $uid, $uid, $current_user);
                        $friendStmt->execute();
                        $friendRes = $friendStmt->get_result();
                        $friendCheck = $friendRes ? $friendRes->fetch_assoc() : null;
                        $friendStmt->close();
                    }

                    $state = 'discover';
                    $statusText = 'Discover';
                    $statusClass = '';
                    if ($friendCheck && ($friendCheck['status'] ?? '') === 'pending' && (int)$friendCheck['receiver_id'] === $current_user) {
                        $state = 'requests';
                        $statusText = 'Incoming Request';
                        $statusClass = 'pending';
                    } elseif ($friendCheck && ($friendCheck['status'] ?? '') === 'pending') {
                        $state = 'pending';
                        $statusText = 'Request Sent';
                        $statusClass = 'pending';
                    } elseif ($friendCheck && ($friendCheck['status'] ?? '') === 'accepted') {
                        $state = 'friends';
                        $statusText = 'Friends';
                        $statusClass = 'friend';
                    }
                    $hasRows = true;
                ?>
                <article class="card" data-name="<?= htmlspecialchars(strtolower((string)($user['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars(strtolower((string)($user['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-state="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row">
                        <div>
                            <div class="name"><?= htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($user['email'])): ?><div class="small"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            <div class="small">Joined: <?= htmlspecialchars($user['created_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <span class="status <?= $statusClass ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="actions">
                        <?php if (!$friendCheck): ?>
                            <a href="users_list.php?add=<?= $uid ?>" class="btn">Add Friend</a>
                        <?php elseif (($friendCheck['status'] ?? '') === 'pending' && (int)$friendCheck['receiver_id'] === $current_user): ?>
                            <a href="users_list.php?accept=<?= $uid ?>" class="btn">Accept</a>
                            <a href="users_list.php?decline=<?= $uid ?>" class="btn danger">Decline</a>
                        <?php elseif (($friendCheck['status'] ?? '') === 'pending'): ?>
                            <a href="#" class="btn warn" onclick="return false;">Request Sent</a>
                        <?php else: ?>
                            <a href="chat.php?id=<?= $uid ?>" class="btn">Chat</a>
                            <a href="users_list.php?remove=<?= $uid ?>" class="btn danger">Remove</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php endif; ?>

        <?php if (!$hasRows): ?>
            <div class="empty">No users found yet.</div>
        <?php endif; ?>
    </section>
</div>

<div id="liveToast" class="live-toast">New friend available. Updating list...</div>

<script>
(function () {
    var baselineCount = <?= (int)$snapshotCount ?>;
    var baselineLatestId = <?= (int)$snapshotLatestId ?>;
    var toast = document.getElementById("liveToast");
    var reloading = false;

    var input = document.getElementById("searchInput");
    var filterButtons = document.querySelectorAll(".fbtn");
    var cards = document.querySelectorAll("#friendList .card");
    var activeFilter = "all";

    function applyFilters() {
        var q = (input.value || "").trim().toLowerCase();

        cards.forEach(function (card) {
            var state = card.dataset.state || "";
            var n = card.dataset.name || "";
            var e = card.dataset.email || "";

            var matchText = q === "" || n.indexOf(q) !== -1 || e.indexOf(q) !== -1;
            var matchState = activeFilter === "all" || state === activeFilter || (activeFilter === "friends" && state === "friends") || (activeFilter === "discover" && state === "discover") || (activeFilter === "requests" && state === "requests");

            if (state === "self" && activeFilter !== "all") {
                card.style.display = "none";
            } else {
                card.style.display = (matchText && matchState) ? "block" : "none";
            }
        });
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener("click", function () {
            filterButtons.forEach(function (b) { b.classList.remove("active"); });
            btn.classList.add("active");
            activeFilter = btn.dataset.filter || "all";
            applyFilters();
        });
    });

    input.addEventListener("input", applyFilters);
    applyFilters();

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.style.display = "block";
    }

    function checkNewUsers() {
        if (reloading) return;
        fetch("friend_live_status.php", { credentials: "same-origin" })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.ok) return;

                var total = Number(data.total || 0);
                var latestId = Number(data.latest_id || 0);

                if (total > baselineCount || latestId > baselineLatestId) {
                    reloading = true;
                    showToast("New friend available. Updating list...");
                    setTimeout(function () { window.location.reload(); }, 900);
                    return;
                }

                baselineCount = total;
                baselineLatestId = latestId;
            })
            .catch(function () {});
    }

    setInterval(checkNewUsers, 5000);
})();
</script>
</body>
</html>
