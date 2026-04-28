<?php
session_start();
require "config.php";
require_once 'email_verification_utils.php';

ensure_email_verification_schema($conn);

$message = "";
$needsEmailVerification = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $query = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            if ((int)($user["email_verified"] ?? 0) !== 1) {
                $_SESSION["verify_email"] = (string)($user["email"] ?? $email);
                $message = "Email is not verified. Verify OTP first, then login.";
                $needsEmailVerification = true;
            } else {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"] ?? ($user["fullname"] ?? "User");
                $_SESSION["role"] = $user["role"] ?? "user";

                header("Location: dashboard.php");
                exit;
            }
        } else {
            $message = "Incorrect password.";
        }
    } else {
        $message = "No account found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SportsMate</title>
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
    .layout{
        width:min(980px,100%);
        display:grid;
        grid-template-columns:1.15fr .85fr;
        gap:18px;
        align-items:stretch;
    }
    .hero{
        border:1px solid rgba(122,171,220,.55);
        border-radius:24px;
        background:linear-gradient(160deg, rgba(12,34,63,.88), rgba(10,30,56,.8));
        padding:26px;
        box-shadow:0 18px 44px rgba(4,12,28,.34);
        position:relative;
        overflow:hidden;
        backdrop-filter:blur(5px);
    }
    .hero::after{
        content:"";
        position:absolute;
        right:-60px;
        top:-80px;
        width:240px;
        height:240px;
        border-radius:50%;
        background:radial-gradient(circle, rgba(15,217,255,.32), rgba(15,217,255,0) 68%);
    }
    .kicker{
        display:inline-block;
        padding:8px 12px;
        border-radius:999px;
        background:linear-gradient(120deg,#ff4f69,#ff8e3c);
        font-size:12px;
        font-weight:700;
        letter-spacing:.7px;
    }
    .hero h1{
        font-family:"Teko",sans-serif;
        font-size:52px;
        letter-spacing:.8px;
        line-height:.95;
        margin:18px 0 10px;
    }
    .hero p{
        color:var(--muted);
        font-size:16px;
        line-height:1.45;
        margin:0;
        max-width:480px;
    }
    .points{
        margin-top:20px;
        display:grid;
        grid-template-columns:repeat(2,minmax(120px,1fr));
        gap:12px;
    }
    .point{
        border:1px solid rgba(100,157,208,.55);
        border-radius:12px;
        background:#0d325a;
        padding:10px 12px;
        color:#cbe8ff;
        font-size:14px;
    }
    .card{
        border:1px solid rgba(122,171,220,.55);
        border-radius:24px;
        background:linear-gradient(165deg, rgba(12,34,63,.9), rgba(10,29,54,.82));
        padding:24px;
        box-shadow:0 18px 44px rgba(4,12,28,.34);
        display:flex;
        flex-direction:column;
        justify-content:center;
        backdrop-filter:blur(5px);
    }
    .card h2{
        margin:0 0 6px;
        font-size:28px;
    }
    .card small{
        color:var(--muted);
        margin-bottom:16px;
        display:block;
    }
    .alert{
        border:1px solid rgba(255,93,115,.45);
        background:rgba(255,107,134,.16);
        color:#ffe0e7;
        border-radius:10px;
        padding:10px 12px;
        margin-bottom:14px;
        font-size:14px;
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
        margin-top:4px;
        padding:12px;
        font-weight:700;
        font-size:15px;
        color:#032437;
        background:linear-gradient(135deg,var(--accent),var(--accent-2));
        cursor:pointer;
        transition:transform .15s ease, filter .15s ease;
    }
    .btn:hover{
        transform:translateY(-1px);
        filter:saturate(1.05);
    }
    .meta{
        margin-top:12px;
        font-size:14px;
        color:var(--muted);
    }
    .meta a{
        color:#8eeeff;
        text-decoration:none;
        font-weight:600;
    }
    .meta a:hover{
        text-decoration:underline;
    }
    @media (max-width:860px){
        .layout{grid-template-columns:1fr}
        .hero h1{font-size:44px}
        .points{grid-template-columns:1fr}
    }
</style>
</head>
<body>
    <main class="layout">
        <section class="hero">
            <span class="kicker">SPORTS NETWORK</span>
            <h1>JOIN THE GAME WITH SPORTSMATE</h1>
            <p>Track events, reserve seats, discover kits, watch highlights, and stay connected with your sports community in one place.</p>
            <div class="points">
                <div class="point">Live event discovery</div>
                <div class="point">Faster ticket booking</div>
                <div class="point">AI sports assistant</div>
                <div class="point">Player community chat</div>
            </div>
        </section>

        <section class="card">
            <h2>Welcome Back</h2>
            <small>Login to continue your matchday journey</small>

            <?php if ($message): ?>
                <div class="alert"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
                <?php if ($needsEmailVerification): ?>
                    <p class="meta" style="margin-top:-6px;margin-bottom:12px;">
                        <a href="verify_email.php?email=<?= urlencode($_SESSION['verify_email'] ?? '') ?>">Verify Email OTP</a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <form action="" method="POST">
                <input class="input" type="email" name="email" placeholder="Email address" required>
                <input class="input" type="password" name="password" placeholder="Password" required>
                <button class="btn" type="submit">Login</button>
            </form>

            <p class="meta">
                New here? <a href="register.php">Create account</a>
            </p>
        </section>
    </main>
</body>
</html>
