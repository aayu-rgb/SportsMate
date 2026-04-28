<?php
session_start();
require 'config.php';
require_once 'notification_utils.php';
require_once 'email_verification_utils.php';

ensure_email_verification_schema($conn);

function email_domain_has_dns(string $email): bool
{
    $atPos = strrpos($email, '@');
    if ($atPos === false) {
        return false;
    }

    $domain = trim(substr($email, $atPos + 1));
    if ($domain === '') {
        return false;
    }

    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
    }

    if (function_exists('dns_get_record')) {
        $mx = @dns_get_record($domain, DNS_MX);
        $a = @dns_get_record($domain, DNS_A);
        $aaaa = @dns_get_record($domain, DNS_AAAA);
        return !empty($mx) || !empty($a) || !empty($aaaa);
    }

    // Strict mode: if DNS lookup functions are unavailable, treat as invalid.
    return false;
}

function is_strong_alnum_password(string $password): bool
{
    if (strlen($password) < 8) {
        return false;
    }

    if (!preg_match('/^[A-Za-z0-9]+$/', $password)) {
        return false;
    }

    // Require at least one letter and one number.
    return preg_match('/[A-Za-z]/', $password) === 1 && preg_match('/[0-9]/', $password) === 1;
}

$message = '';
$ok = false;

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (!$name || !$email || !$pass) {
        $message = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (!email_domain_has_dns($email)) {
        $message = 'Email domain looks invalid. Please check and try again.';
    } elseif ($phone !== '' && normalize_phone_number($phone) === '') {
        $message = 'Please enter a valid phone number.';
    } elseif (!is_strong_alnum_password($pass)) {
        $message = 'Password must be at least 8 characters and use letters + numbers only (no symbols).';
    } elseif ($pass !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, fullname, email_verified FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingUser = $result ? $result->fetch_assoc() : null;

            if ($existingUser) {
                $existingUserId = (int)$existingUser['id'];
                $existingName = $existingUser['name'] ?? ($existingUser['fullname'] ?? $name);
                $isVerified = (int)($existingUser['email_verified'] ?? 0) === 1;

                if ($isVerified) {
                    $message = 'Account already exists with this email.';
                } else {
                    $emailOtp = generate_email_otp();
                    $emailOtpSaved = set_user_otp($conn, $existingUserId, $emailOtp, 'email');
                    $emailSend = $emailOtpSaved
                        ? send_email_otp_with_status($email, $existingName, $emailOtp)
                        : ['ok' => false, 'message' => 'Could not save email OTP.'];

                    $_SESSION['verify_email'] = $email;
                    $ok = true;
                    $message = $emailSend['ok']
                        ? 'Email exists but is not verified. New OTP sent. Complete verification to activate login.'
                        : ('Email exists but is not verified. OTP failed: ' . $emailSend['message']);
                }
            }
            $stmt->close();
        } else {
            $message = 'Unable to validate account. Try again.';
        }

        if (!$message) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $role = 'user';
            $inserted = false;
            $newUserId = 0;
            $normalizedPhone = $phone !== '' ? normalize_phone_number($phone) : '';
            $phoneToStore = $normalizedPhone !== '' ? $normalizedPhone : $phone;

            $stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            if ($stmt) {
                $stmt->bind_param('sssss', $name, $email, $phoneToStore, $hash, $role);
                $inserted = $stmt->execute();
                if ($inserted) {
                    $newUserId = (int)$conn->insert_id;
                }
                $stmt->close();
            }
            if (!$inserted) {
                $stmt = $conn->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sssss', $name, $email, $phoneToStore, $hash, $role);
                    $inserted = $stmt->execute();
                    if ($inserted) {
                        $newUserId = (int)$conn->insert_id;
                    }
                    $stmt->close();
                }
            }
            if (!$inserted) {
                $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
                if ($stmt) {
                    $stmt->bind_param('ssss', $name, $email, $hash, $role);
                    $inserted = $stmt->execute();
                    if ($inserted) {
                        $newUserId = (int)$conn->insert_id;
                    }
                    $stmt->close();
                }
            }
            if (!$inserted) {
                $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('ssss', $name, $email, $hash, $role);
                    $inserted = $stmt->execute();
                    if ($inserted) {
                        $newUserId = (int)$conn->insert_id;
                    }
                    $stmt->close();
                }
            }
            if (!$inserted) {
                $stmt = $conn->prepare('INSERT INTO users (fullname, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                if ($stmt) {
                    $stmt->bind_param('sssss', $name, $email, $phoneToStore, $hash, $role);
                    $inserted = $stmt->execute();
                    if ($inserted) {
                        $newUserId = (int)$conn->insert_id;
                    }
                    $stmt->close();
                }
            }

            if ($inserted && $newUserId > 0) {
                $phoneProvided = $phoneToStore !== '';
                $phoneVerified = $phoneProvided ? 0 : 1;
                $setVerifyStmt = $conn->prepare('UPDATE users SET email_verified = 0, phone_verified = ? WHERE id = ?');
                if ($setVerifyStmt) {
                    $setVerifyStmt->bind_param('ii', $phoneVerified, $newUserId);
                    $setVerifyStmt->execute();
                    $setVerifyStmt->close();
                }

                $emailOtp = generate_email_otp();
                $emailOtpSaved = set_user_otp($conn, $newUserId, $emailOtp, 'email');
                $emailSend = $emailOtpSaved ? send_email_otp_with_status($email, $name, $emailOtp) : ['ok' => false, 'message' => 'Could not save email OTP.'];
                $phoneSend = null;

                if ($phoneProvided) {
                    $phoneOtp = generate_phone_otp();
                    $phoneOtpSaved = set_user_otp($conn, $newUserId, $phoneOtp, 'phone');
                    $phoneSend = $phoneOtpSaved
                        ? send_phone_otp_with_status($phoneToStore, $name, $phoneOtp)
                        : ['ok' => false, 'message' => 'Could not save phone OTP.'];
                    $_SESSION['verify_phone'] = $phoneToStore;
                }

                $_SESSION['verify_email'] = $email;

                $ok = true;
                $message = 'Registration created. Complete OTP verification to activate login. ';
                $message .= $emailSend['ok'] ? 'Email OTP sent. ' : ('Email OTP failed: ' . $emailSend['message'] . ' ');
                if ($phoneProvided) {
                    $message .= $phoneSend && $phoneSend['ok']
                        ? 'Phone OTP sent. '
                        : ('Phone OTP failed: ' . (($phoneSend['message'] ?? 'Unknown SMS error.')) . ' ');
                }

                push_notification_to_all_except($conn, $newUserId, $name . ' joined SportsMate. Send a friend request.');
            } else {
                $message = 'Registration failed. Please check database table fields.';
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
<title>Register - SportsMate</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<style>
    :root{
        --bg-1:#061127;
        --bg-2:#0b2342;
        --panel:#0d2747;
        --line:#2f6ba0;
        --text:#e9f5ff;
        --muted:#9dc3e2;
        --accent:#22d8ff;
        --accent-2:#47ffb1;
        --danger:#ff6b86;
        --success:#27d69e;
    }
    *{box-sizing:border-box}
    body{
        margin:0;
        min-height:100vh;
        font-family:"Barlow",sans-serif;
        color:var(--text);
        background:
            radial-gradient(circle at 8% 18%, rgba(34,216,255,.26) 0, rgba(34,216,255,0) 36%),
            radial-gradient(circle at 92% 84%, rgba(71,255,177,.16) 0, rgba(71,255,177,0) 34%),
            linear-gradient(155deg,var(--bg-1),var(--bg-2));
        display:grid;
        place-items:center;
        padding:20px;
    }
    .card{
        width:min(520px,100%);
        border:1px solid rgba(122,171,220,.55);
        border-radius:24px;
        background:linear-gradient(165deg, rgba(12,34,63,.9), rgba(10,29,54,.82));
        padding:24px;
        box-shadow:0 18px 44px rgba(4,12,28,.34);
        backdrop-filter:blur(5px);
    }
    .title{
        margin:0;
        font-family:"Teko",sans-serif;
        font-size:44px;
        letter-spacing:.8px;
        line-height:.95;
    }
    .sub{
        margin:4px 0 14px;
        color:var(--muted);
        font-size:15px;
    }
    .alert{
        border:1px solid rgba(255,93,115,.45);
        background:rgba(255,107,134,.16);
        color:#ffe0e7;
        border-radius:10px;
        padding:10px 12px;
        margin-bottom:12px;
        font-size:14px;
        white-space:pre-wrap;
    }
    .ok{
        border-color:rgba(39,214,158,.45);
        background:rgba(39,214,158,.14);
        color:#d8fff0;
    }
    .input{
        width:100%;
        border:1px solid #3b79af;
        background:#0a223f;
        color:#e7f4ff;
        border-radius:11px;
        padding:12px;
        margin-bottom:10px;
        font-size:15px;
        outline:none;
        transition:border-color .2s ease, box-shadow .2s ease;
    }
    .input:focus{
        border-color:#22d8ff;
        box-shadow:0 0 0 3px rgba(34,216,255,.18);
    }
    .btn{
        width:100%;
        border:none;
        border-radius:11px;
        margin-top:2px;
        padding:12px;
        font-weight:700;
        font-size:15px;
        color:#032437;
        background:linear-gradient(135deg,var(--accent),var(--accent-2));
        cursor:pointer;
        text-decoration:none;
        display:inline-block;
        text-align:center;
    }
    .meta{
        margin-top:10px;
        font-size:14px;
        color:var(--muted);
    }
    .meta a{
        color:#8eeeff;
        font-weight:700;
        text-decoration:none;
    }
</style>
</head>
<body>
    <section class="card">
        <h1 class="title">CREATE ACCOUNT</h1>
        <p class="sub">Sign up to use SportsMate.</p>

        <?php if ($message): ?>
            <div class="alert <?= $ok ? 'ok' : '' ?>"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <?php if ($ok): ?>
            <a class="btn" href="verify_email.php?email=<?= urlencode($_SESSION['verify_email'] ?? '') ?>">Verify Email OTP</a>
            <?php if (!empty($_SESSION['verify_phone'])): ?>
                <a class="btn" style="margin-top:10px" href="verify_phone.php?phone=<?= urlencode($_SESSION['verify_phone']) ?>">Verify Phone OTP</a>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST" action="">
                <input class="input" type="text" name="name" placeholder="Full name" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                <input class="input" type="email" name="email" placeholder="Email address" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                <input class="input" type="text" name="phone" placeholder="Phone number (with country code preferred)" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input class="input" type="password" name="password" placeholder="Password (min 8, letters+numbers only)" pattern="[A-Za-z0-9]{8,}" title="Use at least 8 characters with letters and numbers only. No symbols." required>
                <input class="input" type="password" name="confirm_password" placeholder="Confirm password" required>
                <button class="btn" type="submit">Register</button>
            </form>
        <?php endif; ?>

        <p class="meta">Already have an account? <a href="login.php">Login</a></p>
    </section>
</body>
</html>
