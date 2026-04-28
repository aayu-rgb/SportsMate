<?php
session_start();
$conn = new mysqli("localhost", "root", "", "sportsmate_db");

$user = $_SESSION['user_id'];

$invites = $conn->query("
SELECT event_invites.*, events.title 
FROM event_invites 
JOIN events ON event_invites.event_id = events.id
WHERE receiver_id=$user AND status='pending'
");

if (isset($_GET['accept'])) {
    $id = $_GET['accept'];
    $conn->query("UPDATE event_invites SET status='accepted' WHERE id=$id");
    $conn->query("INSERT INTO event_users (event_id, user_email) VALUES ($id, '$user')");
}
if (isset($_GET['decline'])) {
    $id = $_GET['decline'];
    $conn->query("UPDATE event_invites SET status='declined' WHERE id=$id");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Event Invitations</title>
<style>
body { background:black; color:white; font-family:Arial; padding:20px; }
.box { background:#1f1f1f; padding:12px; margin:10px 0; border-radius:10px; }
.btn { background:#00eaff; padding:6px 10px; border-radius:6px; text-decoration:none; color:black; margin-left:10px; }
.decline { background:red; }
</style>
</head>
<body>

<h2>Event Invitations 🔔</h2>

<?php
if ($invites->num_rows > 0) {
    while($i = $invites->fetch_assoc()) {
        echo "<div class='box'>
                <strong>{$i['title']}</strong><br>
                <a class='btn' href='event_notifications.php?accept={$i['id']}'>Accept</a>
                <a class='btn decline' href='event_notifications.php?decline={$i['id']}'>Decline</a>
              </div>";
    }
} else {
    echo "<p>No event invitations yet.</p>";
}
?>

<a href="dashboard.php" style="color:#00eaff;">⬅ Back</a>
</body>
</html>
