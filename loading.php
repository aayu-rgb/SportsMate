<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportsMate | Launching</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg-a:#050d1f;
            --bg-b:#0b2342;
            --bg-c:#0f3a63;
            --line:#2f6da1;
            --panel:rgba(8,24,46,.74);
            --accent:#22d8ff;
            --accent-2:#47ffb1;
            --hot:#ff5f7d;
            --text:#e9f5ff;
            --muted:#9fc7e7;
            --shadow:rgba(4,10,22,.52);
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            min-height:100vh;
            font-family:"Barlow",sans-serif;
            color:var(--text);
            background:
                radial-gradient(circle at 8% 15%, rgba(34,216,255,.32) 0, rgba(34,216,255,0) 26%),
                radial-gradient(circle at 90% 82%, rgba(71,255,177,.20) 0, rgba(71,255,177,0) 30%),
                linear-gradient(140deg, var(--bg-a), var(--bg-b) 44%, var(--bg-c));
            overflow:hidden;
            display:grid;
            place-items:center;
            padding:20px;
        }
        .ambient{
            position:fixed;
            inset:0;
            pointer-events:none;
            overflow:hidden;
        }
        .orb{
            position:relative;
            border-radius:999px;
            filter:blur(0.5px);
            animation:float 9s ease-in-out infinite;
        }
        .orb.a{
            width:280px;
            height:280px;
            left:-70px;
            top:-70px;
            background:radial-gradient(circle, rgba(34,216,255,.34), rgba(34,216,255,0) 70%);
        }
        .orb.b{
            width:360px;
            height:360px;
            right:-90px;
            top:52%;
            background:radial-gradient(circle, rgba(71,255,177,.25), rgba(71,255,177,0) 72%);
            animation-delay:1.2s;
        }
        .orb.c{
            width:250px;
            height:250px;
            left:44%;
            bottom:-95px;
            background:radial-gradient(circle, rgba(255,95,125,.22), rgba(255,95,125,0) 72%);
            animation-delay:2.2s;
        }
        .loader-card{
            width:min(760px,96vw);
            border-radius:28px;
            border:1px solid rgba(110,178,232,.55);
            background:
                linear-gradient(140deg, rgba(11,30,55,.78), rgba(8,25,46,.67)),
                linear-gradient(170deg, rgba(34,216,255,.11), rgba(71,255,177,.06));
            box-shadow:0 26px 70px var(--shadow);
            padding:30px;
            position:relative;
            overflow:hidden;
            backdrop-filter:blur(7px);
        }
        .loader-card::before{
            content:"";
            position:absolute;
            inset:-160% -25%;
            background:conic-gradient(from 120deg, rgba(34,216,255,0), rgba(34,216,255,.22), rgba(71,255,177,0), rgba(255,95,125,.24), rgba(34,216,255,0));
            animation:spin 10s linear infinite;
            pointer-events:none;
            opacity:.85;
        }
        .loader-card::after{
            content:"";
            position:absolute;
            inset:0;
            border-radius:inherit;
            border:1px solid rgba(182,221,255,.14);
            pointer-events:none;
        }
        .loader-content{
            position:relative;
            z-index:2;
        }
        .top{
            display:flex;
            justify-content:space-between;
            gap:16px;
            align-items:flex-start;
            flex-wrap:wrap;
        }
        .tag{
            display:inline-block;
            background:linear-gradient(120deg,var(--hot),#ff9b3c);
            color:#fefeff;
            padding:7px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:700;
            letter-spacing:.6px;
            margin-bottom:14px;
            box-shadow:0 6px 18px rgba(255,95,125,.24);
        }
        h1{
            margin:0;
            font-family:"Teko",sans-serif;
            font-size:66px;
            letter-spacing:1px;
            line-height:.88;
            text-shadow:0 8px 18px rgba(12,22,52,.55);
        }
        .sub{
            margin:8px 0 0;
            color:var(--muted);
            font-size:15px;
            max-width:470px;
            line-height:1.5;
        }
        .counter{
            border:1px solid rgba(112,190,245,.42);
            background:rgba(7,27,51,.56);
            border-radius:16px;
            min-width:142px;
            padding:10px 12px;
            text-align:right;
        }
        .counter b{
            display:block;
            font-size:38px;
            line-height:.95;
            font-family:"Teko",sans-serif;
            letter-spacing:1px;
            color:#e9fbff;
        }
        .counter span{
            color:#a8d4f5;
            font-size:12px;
            letter-spacing:.4px;
            text-transform:uppercase;
        }
        .stage{
            margin-top:22px;
            display:grid;
            grid-template-columns:1fr auto;
            gap:12px;
            align-items:center;
        }
        .track{
            width:100%;
            height:14px;
            border-radius:999px;
            background:rgba(7,31,56,.7);
            border:1px solid var(--line);
            overflow:hidden;
            position:relative;
        }
        .track::before{
            content:"";
            position:absolute;
            inset:0;
            background:repeating-linear-gradient(90deg, rgba(255,255,255,.08), rgba(255,255,255,.08) 10px, transparent 10px, transparent 20px);
            pointer-events:none;
            opacity:.5;
        }
        .bar{
            width:0%;
            height:100%;
            border-radius:999px;
            background:linear-gradient(120deg,var(--accent),var(--accent-2));
            box-shadow:0 0 24px rgba(34,216,255,.45);
            transition:width .22s ease;
            position:relative;
        }
        .bar::after{
            content:"";
            position:absolute;
            top:1px;
            right:2px;
            width:52px;
            height:10px;
            border-radius:999px;
            background:rgba(255,255,255,.45);
            filter:blur(2px);
        }
        .status{
            margin-top:12px;
            font-size:14px;
            color:var(--muted);
            letter-spacing:.3px;
            min-height:20px;
        }
        .tips{
            margin-top:16px;
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }
        .tip{
            border:1px solid rgba(119,184,236,.34);
            background:rgba(8,30,56,.52);
            color:#bfe1ff;
            border-radius:999px;
            padding:7px 10px;
            font-size:12px;
            white-space:nowrap;
        }
        .brand-mark{
            position:absolute;
            right:16px;
            bottom:14px;
            color:rgba(191,225,254,.66);
            font-size:12px;
            letter-spacing:1.2px;
            font-weight:700;
        }
        @keyframes float{
            0%,100%{transform:translateY(0px)}
            50%{transform:translateY(12px)}
        }
        @keyframes spin{
            from{transform:rotate(0deg)}
            to{transform:rotate(360deg)}
        }
        @media (max-width:780px){
            .loader-card{padding:24px 18px 30px}
            h1{font-size:52px}
            .counter{min-width:126px}
            .counter b{font-size:33px}
            .stage{grid-template-columns:1fr}
        }
        @media (prefers-reduced-motion:reduce){
            .loader-card::before,.orb{animation:none}
            .bar{transition:none}
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var bar = document.getElementById("bar");
            var percentEl = document.getElementById("percent");
            var statusEl = document.getElementById("status");

            var statusSteps = [
                "Scanning live events...",
                "Syncing ticket channels...",
                "Loading kits and highlights...",
                "Warming up AI assistant...",
                "Finalizing your SportsMate hub..."
            ];
            var statusIndex = 0;
            var progress = 0;
            var redirectTriggered = false;

            function updateStatus() {
                statusEl.textContent = statusSteps[statusIndex] || statusSteps[statusSteps.length - 1];
                statusIndex = Math.min(statusIndex + 1, statusSteps.length - 1);
            }

            function goNext() {
                if (!redirectTriggered) {
                    redirectTriggered = true;
                    window.location.href = "index.php";
                }
            }

            updateStatus();
            var statusTimer = setInterval(updateStatus, 900);
            var progressTimer = setInterval(function () {
                var step = progress < 70 ? 7 : (progress < 90 ? 4 : 2);
                progress = Math.min(progress + step, 100);
                bar.style.width = progress + "%";
                percentEl.textContent = progress + "%";

                if (progress >= 100) {
                    clearInterval(progressTimer);
                    clearInterval(statusTimer);
                    statusEl.textContent = "Ready. Entering SportsMate...";
                    setTimeout(goNext, 420);
                }
            }, 180);

            setTimeout(goNext, 5000);
        });
    </script>
</head>
<body>
    <div class="ambient" aria-hidden="true">
        <div class="orb a"></div>
        <div class="orb b"></div>
        <div class="orb c"></div>
    </div>
    <div class="loader-card">
        <div class="loader-content">
            <div class="top">
                <div>
                    <div class="tag">MATCHDAY EXPERIENCE</div>
                    <h1>SPORTSMATE</h1>
                    <p class="sub">Preparing your all-in-one sports network with events, tickets, community chat, AI insights, and match highlights.</p>
                </div>
                <div class="counter">
                    <b id="percent">0%</b>
                    <span>System Boot</span>
                </div>
            </div>

            <div class="stage">
                <div class="track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="Loading progress">
                    <div class="bar" id="bar"></div>
                </div>
            </div>
            <div class="status" id="status">Initializing...</div>

            <div class="tips">
                <div class="tip">Live Event Discovery</div>
                <div class="tip">Faster Ticket Booking</div>
                <div class="tip">AI Sports Assistant</div>
                <div class="tip">Team Community Chat</div>
            </div>
            <div class="brand-mark">SPORTSMATE</div>
        </div>
    </div>
</body>
</html>
