<?php
session_start();
require "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$users = $conn->query("SELECT id, name, fullname, email, phone, role, created_at FROM users ORDER BY created_at DESC, id DESC");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { background:black; color:white; font-family:Arial; padding:20px; }
table { width:100%; border-collapse:collapse; }
td,th { border:1px solid #333; padding:10px; text-align:left; }
th { background:#111; }
</style>
</head>
<body>

<h2>Admin Panel</h2>
<p>Total Registered Users: <strong><?= $users ? (int)$users->num_rows : 0 ?></strong></p>

<table>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th></tr>
<?php if ($users): ?>
<?php while($u = $users->fetch_assoc()): ?>
<?php $displayName = $u['name'] ?: ($u['fullname'] ?: 'User'); ?>
<tr>
<td><?= (int)$u['id'] ?></td>
<td><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(($u['phone'] ?? '') !== '' ? $u['phone'] : '-', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($u['role'] ?? 'user', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($u['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</table>

</body>
</html>
