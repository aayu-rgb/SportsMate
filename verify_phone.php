<?php
session_start();
require 'config.php';
require_once 'email_verification_utils.php';

ensure_email_verification_schema($conn);

$message = '';
$success = false;
$phone = trim($_GET['phone'] ?? ($_SESSION['verify_phone'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $action = $_POST['action'] ?? '';
    $normalizedPhone = normalize_phone_number($phone);
    $user = null;

    if ($normalizedPhone !== '') {
        $stmt = $conn->prepare('SELECT id, name, fullname, phone, phone_verified, phone_otp, phone_otp_expires_at FROM users WHERE phone = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $normalizedPhone);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }
    }

    if (!$user) {
        $message = 'No account found for provided phone.';
    } else {
        $uid = (int)$user['id'];
        $name = $user['name'] ?? ($user['fullname'] ?? 'User');
        $dbPhone = (string)($user['phone'] ?? '');

        if ($action === 'resend_phone') {
            $otp = generate_phone_otp();
            $saved = set_user_otp($conn, $uid, $otp, 'phone');
            $delivery = $saved ? send_phone_otp_with_status($dbPhone, $name, $otp) : ['ok' => false, 'message' => 'Could not save phone OTP.'];
            $success = (bool)$delivery['ok'];
            $message = $delivery['ok'] ? ('Phone OTP sent to ' . mask_phone($dbPhone) . '.') : ('Phone OTP failed: ' . $delivery['message']);
        } elseif ($action === 'verify_phone') {
            $otpInput = trim($_POST['phone_otp'] ?? '');
            if ((int)($user['phone_verified'] ?? 0) === 1) {
                $success = true;
                $message = 'Phone already verified.';
            } elseif (!verification_matches($user, $otpInput, 'phone')) {
                $message = 'Invalid/expired phone OTP. Please resend and try again.';
            } elseif (clear_verified_channel($conn, $uid, 'phone')) {
                $success = true;
                $message = 'Phone verified successfully.';
            } else {
                $message = 'Could not verify phone now.';
            }
        } else {
            $message = 'Invalid action.';
        }

        if ($success) {
            $refresh = $conn->prepare('SELECT phone_verified FROM users WHERE id = ? LIMIT 1');
            if ($refresh) {
                $refresh->bind_param('i', $uid);
                $refresh->execute();
                $res = $refresh->get_result();
                $state = $res ? $res->fetch_assoc() : null;
                $refresh->close();

                if ($state && (int)$state['phone_verified'] === 1) {
                    unset($_SESSION['verify_phone']);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Phone OTP Verification - SportsMate</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<style>
    :root{
        --bg-1:#061127;
        --bg-2:#0b2342;
        --text:#e9f5ff;
        --muted:#9dc3e2;
        --accent:#22d8ff;
        --accent-2:#47ffb1;
        --danger:#ff6b86;
        --success:#27d69e;
    }
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;font-family:"Barlow",sans-serif;color:var(--text);background:linear-gradient(155deg,var(--bg-1),var(--bg-2));display:grid;place-items:center;padding:20px}
    .card{width:min(560px,100%);border:1px solid rgba(122,171,220,.55);border-radius:20px;background:linear-gradient(165deg, rgba(12,34,63,.9), rgba(10,29,54,.82));padding:22px;box-shadow:0 18px 44px rgba(4,12,28,.34)}
    h1{margin:0;font-family:"Teko",sans-serif;font-size:42px;line-height:1}
    p{margin:8px 0 14px;color:var(--muted);font-size:14px}
    .alert{border:1px solid rgba(255,93,115,.45);background:rgba(255,107,134,.16);color:#ffe0e7;border-radius:10px;padding:10px 12px;margin-bottom:12px;font-size:14px;white-space:pre-wrap}
    .ok{border-color:rgba(39,214,158,.45);background:rgba(39,214,158,.14);color:#d8fff0}
    .input{width:100%;border:1px solid #3b79af;background:#0a223f;color:#e7f4ff;border-radius:11px;padding:12px;margin-bottom:10px;font-size:15px;outline:none}
    .box{border:1px solid rgba(76,129,176,.5);border-radius:12px;padding:12px;background:rgba(9,33,60,.55)}
    .title{font-size:14px;font-weight:700;color:#cbe8ff;margin:0 0 8px}
    .btn{width:100%;border:none;border-radius:11px;padding:11px;font-weight:700;font-size:14px;color:#032437;background:linear-gradient(135deg,var(--accent),var(--accent-2));cursor:pointer;margin-top:4px}
    .btn.secondary{background:linear-gradient(135deg,#88a8c8,#b4d4ee)}
    .link{margin-top:10px;font-size:14px;color:var(--muted)}
    .link a{color:#8eeeff;text-decoration:none;font-weight:700}
</style>
</head>
<body>
    <section class="card">
        <h1>VERIFY PHONE</h1>
        <p>Enter the OTP sent to your phone to complete verification.</p>

        <?php if ($message): ?>
            <div class="alert <?= $success ? 'ok' : '' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post">
            <input class="input" type="text" name="phone" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" placeholder="Phone (+countrycode...)" required>

            <div class="box">
                <p class="title">Phone OTP</p>
                <input class="input" type="text" name="phone_otp" placeholder="6-digit phone OTP" pattern="[0-9]{6}" maxlength="6">
                <button class="btn" type="submit" name="action" value="verify_phone">Verify Phone OTP</button>
                <button class="btn secondary" type="submit" name="action" value="resend_phone">Resend Phone OTP</button>
            </div>
        </form>

        <div class="link">Back to <a href="login.php">Login</a></div>
    </section>
</body>
</html>
