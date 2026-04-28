<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all other users except me
$users_sql = "SELECT id, fullname, email FROM users WHERE id != $user_id";
$users_res = mysqli_query($conn, $users_sql);

// When form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $friend_id = intval($_POST['friend_id']);
    $note = trim($_POST['note']);

    if ($friend_id > 0) {
        // Insert invite
        $insert_sql = "INSERT INTO invites (sender_id, receiver_id, note, created_at) 
                       VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iis", $user_id, $friend_id, $note);
        $stmt->execute();
        $stmt->close();

        // Create Notification (Style C)
        $notif_msg = "📩 You received an invite!";
        $notif_sql = "INSERT INTO notifications (user_id, message, seen, created_at)
                      VALUES (?, ?, 0, NOW())";
        $nstmt = $conn->prepare($notif_sql);
        $nstmt->bind_param("is", $friend_id, $notif_msg);
        $nstmt->execute();
        $nstmt->close();

        echo "<script>alert('Invite sent successfully!'); window.location='dashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('Please select a friend.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Invite Friends</title>
<style>
body {
    background:#0b0b0b;
    color:white;
    font-family:Arial;
    padding:20px;
}
.container {
    max-width:500px;
    margin:auto;
    background:#111;
    padding:20px;
    border-radius:10px;
}
select, textarea, button {
    width:100%;
    margin-top:10px;
    padding:10px;
    border-radius:8px;
    border:none;
}
button {
    background:#00eaff;
    color:black;
    font-weight:bold;
    cursor:pointer;
    margin-top:20px;
}
</style>
</head>

<body>

<div class="container">
    <h2>Invite a Friend</h2>

    <form method="POST">

        <label>Select Friend</label>
        <select name="friend_id" required>
            <option value="">-- Choose friend --</option>
            <?php while($u = mysqli_fetch_assoc($users_res)) { ?>
                <option value="<?= $u['id'] ?>">
                    <?= $u['fullname'] ?> (<?= $u['email'] ?>)
                </option>
            <?php } ?>
        </select>

        <label>Message (optional)</label>
        <textarea name="note" placeholder="Hey! Join me in SportsMate!" rows="3"></textarea>

        <button type="submit">Send Invite</button>
    </form>

</div>

</body>
</html>
