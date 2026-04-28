<?php
session_start();
require_once "config.php"; // keep this if you have DB; if not, it's ok

// ensure correct session
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// If form submitted -> update profile (if DB available)
$update_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!empty($name) && !empty($email)) {
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
                if ($stmt->execute()) {
                    $update_msg = "Profile updated successfully.";
                    $_SESSION['user_name'] = $name; // update session copy
                } else {
                    $update_msg = "Update failed (DB).";
                }
                $stmt->close();
            } else {
                $update_msg = "Update failed (prepare).";
            }
        } else {
            // No DB connection available — update only session (frontend demo)
            $_SESSION['user_name'] = $name;
            $update_msg = "Profile updated in session (demo mode).";
        }
    } else {
        $update_msg = "Please provide name and email.";
    }
}

// Fetch user from DB if available
$user = [
    'name' => $user_name,
    'email' => $_SESSION['user_email'] ?? '',
    'phone' => $_SESSION['user_phone'] ?? ''
];

if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            // keep session name in sync
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Profile - SportsMate</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    :root { --neon:#06f6c8; --bg1:#07080a; --bg2:#0f1724; --panel:rgba(255,255,255,0.04); }
    body { margin:0; font-family:Inter, Arial, sans-serif; background: linear-gradient(180deg,var(--bg1),var(--bg2)); color:#e6eef6; }
    .nav { display:flex; justify-content:space-between; align-items:center; padding:14px 22px; background:rgba(0,0,0,0.2); border-bottom:1px solid rgba(255,255,255,0.03); }
    .brand { display:flex; gap:12px; align-items:center; }
    .logo { width:44px;height:44px;border-radius:10px;background:linear-gradient(90deg,var(--neon),#07b3ff);display:flex;align-items:center;justify-content:center;color:#032; font-weight:800; }
    .nav a { color:#dff7f1; text-decoration:none; margin-left:14px; font-weight:600; }
    .container { max-width:980px; margin:28px auto; padding:0 18px; }

    .grid { display:grid; grid-template-columns: 280px 1fr; gap:18px; align-items:start; }
    .card { background:var(--panel); padding:18px; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.45); border:1px solid rgba(255,255,255,0.02); }

    .avatar { width:100%; display:flex; justify-content:center; padding-bottom:12px; }
    .avatar .bubble { width:110px;height:110px;border-radius:18px;background:linear-gradient(90deg,var(--neon),#07b3ff);display:flex;align-items:center;justify-content:center;color:#022; font-size:36px; font-weight:800; }

    .meta { color:#9fb4c6; font-size:14px; margin-top:8px; text-align:center; }
    .info-list { margin-top:12px; display:flex; flex-direction:column; gap:10px; }
    .info-list .row { display:flex; justify-content:space-between; padding:10px; background:rgba(0,0,0,0.15); border-radius:8px; font-weight:600; }

    .panel { padding:18px; border-radius:12px; background:var(--panel); }
    h1 { margin:0 0 8px 0; color:var(--neon); }
    p.lead { margin:0 0 12px 0; color:#9fb4c6; }

    form label { display:block; font-size:13px; color:#9fb4c6; margin-top:10px; }
    input[type="text"], input[type="email"], input[type="tel"] {
      width:100%; padding:10px 12px; margin-top:6px; border-radius:8px; border:1px solid rgba(255,255,255,0.04); background:#07111a; color:#e6eef6;
    }
    .actions { margin-top:14px; display:flex; gap:10px; }
    .btn { padding:10px 14px; border-radius:8px; border:none; cursor:pointer; font-weight:700; }
    .btn-primary { background:linear-gradient(90deg,var(--neon),#07b3ff); color:#032; }
    .btn-ghost { background:transparent; color:#9fb4c6; border:1px solid rgba(255,255,255,0.03); }

    .msg { margin-top:12px; padding:10px; border-radius:8px; background:rgba(0,0,0,0.25); color:#cfeee6; font-weight:600; }

    @media(max-width:840px) {
      .grid { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

  <header class="nav">
    <div class="brand">
      <div class="logo">SM</div>
      <div>
        <div style="font-weight:800">SportsMate</div>
        <div style="font-size:13px;color:#9fb4c6">Your profile</div>
      </div>
    </div>
    <div>
      <a href="dashboard.php">Home</a>
      <a href="logout.php" style="color:#ff8b8b">Logout</a>
    </div>
  </header>

  <div class="container">
    <div class="grid">

      <!-- left: avatar / quick info -->
      <div class="card">
        <div class="avatar">
          <div class="bubble"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U',0,1)) ?></div>
        </div>

        <div style="text-align:center;">
          <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
          <div class="meta"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
        </div>

        <div class="info-list">
          <div class="row"><span style="color:#9fb4c6">Name</span><span><?= htmlspecialchars($_SESSION['user_name'] ?? '-') ?></span></div>
          <div class="row"><span style="color:#9fb4c6">Email</span><span><?= htmlspecialchars($_SESSION['user_email'] ?? '-') ?></span></div>
          <div class="row"><span style="color:#9fb4c6">Phone</span><span><?= htmlspecialchars($_SESSION['user_phone'] ?? '-') ?></span></div>
        </div>

      </div>

      <!-- right: edit panel -->
      <div class="panel card">
        <h1>Profile</h1>
        <p class="lead">View and update your profile information.</p>

        <?php if ($update_msg): ?>
          <div class="msg"><?= htmlspecialchars($update_msg) ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <input type="hidden" name="action" value="update" />
          <label>Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? '')) ?>" required />

          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? ($_SESSION['user_email'] ?? '')) ?>" required />

          <label>Phone</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ($_SESSION['user_phone'] ?? '')) ?>" />

          <div class="actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="dashboard.php" class="btn btn-ghost" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;padding:10px 14px;border-radius:8px;">Back</a>
          </div>
        </form>

      </div>

    </div>
  </div>

</body>
</html>
