<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$videos = [];

try {
    $dbVideos = $conn->query("SELECT title, youtube_id, sport FROM sports_videos ORDER BY id DESC LIMIT 40");
    if ($dbVideos) {
        while ($row = $dbVideos->fetch_assoc()) {
            $title = trim((string)($row['title'] ?? 'Untitled Video'));
            $youtubeId = trim((string)($row['youtube_id'] ?? ''));
            if ($youtubeId !== '') {
                $videos[] = [$title, "https://www.youtube.com/watch?v=" . $youtubeId, (string)($row['sport'] ?? '')];
            }
        }
    }
} catch (Throwable $e) {
}

if (empty($videos) && is_file(__DIR__ . "/videos_data.php")) {
    require __DIR__ . "/videos_data.php";
    if (!isset($videos) || !is_array($videos)) {
        $videos = [];
    }
}

if (empty($videos)) {
    $videos = [
        ["Football Highlights", "https://www.youtube.com/watch?v=4WQZqY7b5nE"],
        ["Cricket Match Highlights", "https://www.youtube.com/watch?v=2O6duDDkhis"]
    ];
}

function youtube_id_from_url(string $url): string
{
    $parts = parse_url($url);
    if (!$parts) {
        return '';
    }

    $host = strtolower((string)($parts['host'] ?? ''));
    $path = (string)($parts['path'] ?? '');

    if (strpos($host, 'youtu.be') !== false) {
        return trim($path, '/');
    }

    if (strpos($host, 'youtube.com') !== false) {
        $query = [];
        parse_str((string)($parts['query'] ?? ''), $query);
        if (!empty($query['v'])) {
            return (string)$query['v'];
        }
        if (strpos($path, '/embed/') === 0) {
            return basename($path);
        }
    }

    return '';
}

function detect_sport(string $title): string
{
    $text = strtolower($title);
    $map = [
        'Football' => ['football', 'soccer'],
        'Cricket' => ['cricket'],
        'Basketball' => ['basketball', 'nba'],
        'Tennis' => ['tennis'],
        'Volleyball' => ['volleyball'],
        'Badminton' => ['badminton'],
        'Boxing' => ['boxing', 'mma', 'ufc'],
        'Running' => ['running', 'marathon'],
        'Swimming' => ['swimming'],
    ];
    foreach ($map as $sport => $tokens) {
        foreach ($tokens as $token) {
            if (strpos($text, $token) !== false) {
                return $sport;
            }
        }
    }
    return 'Sports';
}

$items = [];
$sportsSet = [];
foreach ($videos as $entry) {
    $title = trim((string)($entry[0] ?? 'Untitled Video'));
    $url = trim((string)($entry[1] ?? ''));
    $id = youtube_id_from_url($url);
    if ($id === '') {
        continue;
    }
    $sport = trim((string)($entry[2] ?? ''));
    if ($sport === '') {
        $sport = detect_sport($title);
    }
    $sportsSet[$sport] = true;
    $items[] = [
        'title' => $title,
        'url' => $url,
        'id' => $id,
        'sport' => $sport,
    ];
}

$sports = array_keys($sportsSet);
sort($sports);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Match Videos - SportsMate</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{
    --bg:#060d1a;
    --panel:rgba(7, 24, 47, .72);
    --panel-solid:#0b2344;
    --card:#0d2a4f;
    --line:rgba(122, 198, 255, .36);
    --text:#e9f6ff;
    --muted:#a7c9ea;
    --accent:#21ddff;
    --accent2:#54ffb2;
    --hot:#ff5f7d;
}
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:"Barlow",sans-serif;
    color:var(--text);
    background:
      radial-gradient(circle at 10% 8%, rgba(33,221,255,.18), transparent 36%),
      radial-gradient(circle at 90% 84%, rgba(84,255,178,.14), transparent 34%),
      linear-gradient(140deg, rgba(3,12,28,.92), rgba(5,22,48,.86)),
      url('https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=1500&q=80') center/cover no-repeat fixed;
}
.wrap{width:min(1100px,94vw);margin:22px auto}
.panel{
    border:1px solid var(--line);
    background:linear-gradient(160deg, var(--panel), rgba(8,28,54,.58));
    border-radius:24px;
    padding:24px;
    backdrop-filter:blur(6px);
    box-shadow:0 24px 48px rgba(3, 11, 24, .45);
    position:relative;
    overflow:hidden;
}
.panel::before{
    content:"";
    position:absolute;
    width:280px;
    height:280px;
    right:-110px;
    top:-110px;
    border-radius:50%;
    background:radial-gradient(circle, rgba(33,221,255,.2), rgba(33,221,255,0) 70%);
    pointer-events:none;
}
.title{
    margin:0;
    font-family:"Teko",sans-serif;
    font-size:58px;
    letter-spacing:1px;
    line-height:.9;
    text-shadow:0 6px 24px rgba(23,188,255,.25);
}
.sub{margin:10px 0 16px;color:var(--muted);font-size:15px}
.top-tools{
    display:grid;
    grid-template-columns:1fr auto;
    gap:10px;
    align-items:center;
}
.search{
    width:100%;
    padding:13px 14px;
    border-radius:12px;
    border:1px solid rgba(126,190,244,.7);
    background:#eef8ff;
    color:#16395d;
    font-size:15px;
    outline:none;
}
.search:focus{box-shadow:0 0 0 3px rgba(33,221,255,.25)}
.yt-search{
    display:inline-flex;
    align-items:center;
    gap:7px;
    text-decoration:none;
    font-weight:800;
    color:#052334;
    background:linear-gradient(120deg,var(--accent),var(--accent2));
    padding:11px 13px;
    border-radius:11px;
    white-space:nowrap;
    transition:transform .18s ease, box-shadow .18s ease;
}
.yt-search:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(20,170,210,.32)}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.chip{
    border:1px solid rgba(137,200,247,.55);
    background:rgba(255,255,255,.1);
    color:#d7eeff;
    border-radius:999px;
    padding:8px 12px;
    font-weight:700;
    font-size:13px;
    cursor:pointer;
    transition:all .18s ease;
}
.chip:hover{border-color:rgba(160,219,255,.8);transform:translateY(-1px)}
.chip.active{background:linear-gradient(120deg,var(--accent),var(--accent2));color:#052334;border-color:transparent}
.hero{
    margin-top:16px;
    border:1px solid rgba(126,190,244,.5);
    border-radius:16px;
    padding:12px;
    background:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
    display:none;
    animation:lift .28s ease;
}
.hero-media{
    position:relative;
    width:100%;
    height:380px;
    border-radius:11px;
    overflow:hidden;
    background:#061a31;
}
.hero-media::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg, rgba(5,20,39,0) 52%, rgba(5,20,39,.42) 100%);
    pointer-events:none;
}
.hero-media img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}
.hero-media iframe{
    width:100%;
    height:100%;
    border:0;
    display:none;
    position:relative;
    z-index:2;
}
.hero-play{
    position:absolute;
    inset:auto auto 14px 14px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    border:none;
    border-radius:999px;
    padding:10px 14px;
    font-weight:800;
    cursor:pointer;
    color:#052334;
    background:linear-gradient(120deg,var(--accent),var(--accent2));
    z-index:3;
    box-shadow:0 10px 22px rgba(20,170,210,.34);
}
.hero-row{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px}
.hero-title{margin:0;font-size:24px}
.watch{
    display:inline-flex;align-items:center;gap:7px;text-decoration:none;font-weight:800;
    color:#052334;background:linear-gradient(120deg,var(--accent),var(--accent2));padding:10px 12px;border-radius:10px;
}
.video-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:12px;
    margin-top:16px;
}
.video{
    border:1px solid rgba(126,190,244,.5);
    background:linear-gradient(145deg, rgba(12,39,72,.93), rgba(10,33,60,.88));
    border-radius:15px;
    padding:10px;
    transition:transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    animation:fadeCard .3s ease both;
}
.video:hover{
    transform:translateY(-4px);
    border-color:#66d9ff;
    box-shadow:0 16px 28px rgba(7,27,47,.45);
}
.video-thumb{
    position:relative;
    border-radius:10px;
    overflow:hidden;
    height:160px;
    background:#071a30;
}
.video-thumb img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .25s ease}
.video:hover .video-thumb img{transform:scale(1.05)}
.video-thumb i{
    position:absolute;
    right:10px;
    bottom:10px;
    width:34px;
    height:34px;
    border-radius:50%;
    display:grid;
    place-items:center;
    color:#052334;
    background:linear-gradient(120deg,var(--accent),var(--accent2));
    box-shadow:0 8px 14px rgba(20,170,210,.34);
}
.video h3{margin:10px 0 6px;font-size:17px}
.meta{display:flex;justify-content:space-between;align-items:center;gap:8px}
.badge{
    font-size:12px;
    padding:6px 10px;
    border-radius:999px;
    background:rgba(110,220,255,.18);
    color:#ccf3ff;
    border:1px solid rgba(125,206,246,.46);
}
.video-btn{
    text-decoration:none;
    color:#052334;
    background:linear-gradient(120deg,var(--accent),var(--accent2));
    border-radius:8px;
    font-weight:800;
    font-size:13px;
    padding:7px 10px;
}
.empty{
    margin-top:12px;
    border:1px dashed rgba(137,200,247,.45);
    padding:12px;
    border-radius:12px;
    color:#c3dff8;
    background:rgba(9,33,58,.35);
}
.back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-top:16px;
    color:#9fe9ff;
    text-decoration:none;
    font-weight:700;
}
.back:hover{color:#d4f8ff}
@keyframes fadeCard{
    from{opacity:0;transform:translateY(8px)}
    to{opacity:1;transform:translateY(0)}
}
@keyframes lift{
    from{opacity:0;transform:translateY(10px)}
    to{opacity:1;transform:translateY(0)}
}
@media (max-width:900px){
    .top-tools{grid-template-columns:1fr}
}
@media (max-width:720px){
    .title{font-size:46px}
    .hero-media{height:240px}
}
</style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <h1 class="title">LIVE & MATCH HIGHLIGHTS</h1>
        <p class="sub">Type your search and only matching videos will be shown.</p>

        <div class="top-tools">
            <input type="text" id="searchInput" class="search" placeholder="Type match or topic (example: cricket today)">
            <a id="ytSearchBtn" class="yt-search" href="https://www.youtube.com/results" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-magnifying-glass"></i>Search on YouTube</a>
            <div class="chips" id="sportFilters">
                <button class="chip active" type="button" data-sport="all">All</button>
                <?php foreach ($sports as $sport): ?>
                    <button class="chip" type="button" data-sport="<?= htmlspecialchars(strtolower($sport), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sport, ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($items)): ?>
            <?php $featured = $items[0]; ?>
            <div class="hero" id="heroSection">
                <div class="hero-media">
                    <img id="heroThumb" src="https://i.ytimg.com/vi/<?= htmlspecialchars($featured['id'], ENT_QUOTES, 'UTF-8') ?>/hqdefault.jpg" alt="<?= htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <iframe id="heroFrame" allowfullscreen></iframe>
                    <button id="heroPlay" class="hero-play" type="button"><i class="fa-solid fa-play"></i>Play</button>
                </div>
                <div class="hero-row">
                    <h2 id="heroTitle" class="hero-title"><?= htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <a id="heroLink" class="watch" href="<?= htmlspecialchars($featured['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-play"></i>Watch on YouTube</a>
                </div>
            </div>

            <div id="videoGrid" class="video-grid">
                <?php foreach ($items as $item): ?>
                    <article class="video" data-title="<?= htmlspecialchars(strtolower($item['title']), ENT_QUOTES, 'UTF-8') ?>" data-sport="<?= htmlspecialchars(strtolower($item['sport']), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="video-thumb">
                            <img src="https://i.ytimg.com/vi/<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>/mqdefault.jpg" alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-play"></i>
                        </div>
                        <h3><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="meta">
                            <span class="badge"><?= htmlspecialchars($item['sport'], ENT_QUOTES, 'UTF-8') ?></span>
                            <a class="video-btn play-btn"
                               href="#"
                               data-id="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>"
                               data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
                               data-url="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>">Play Here</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p id="emptyState" class="empty">Type in search box to see matching videos.</p>
        <?php else: ?>
            <p class="empty">No valid videos found in `videos_data.php`.</p>
        <?php endif; ?>

        <a class="back" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i>Back to Dashboard</a>
    </section>
</div>
<script>
(function(){
    var searchInput = document.getElementById("searchInput");
    var filterWrap = document.getElementById("sportFilters");
    var cards = Array.prototype.slice.call(document.querySelectorAll("#videoGrid .video"));
    var empty = document.getElementById("emptyState");
    var heroSection = document.getElementById("heroSection");
    var ytSearchBtn = document.getElementById("ytSearchBtn");
    var heroThumb = document.getElementById("heroThumb");
    var heroPlay = document.getElementById("heroPlay");
    var heroFrame = document.getElementById("heroFrame");
    var heroTitle = document.getElementById("heroTitle");
    var heroLink = document.getElementById("heroLink");
    var activeVideoId = "<?= isset($featured['id']) ? htmlspecialchars($featured['id'], ENT_QUOTES, 'UTF-8') : '' ?>";
    var activeSport = "all";

    function mountHero(id, title, url, autoplay) {
        if (!heroFrame || !heroTitle || !heroLink || !heroThumb) return;
        if (heroSection) {
            heroSection.style.display = "block";
        }
        activeVideoId = id || "";
        heroTitle.textContent = title || "Video";
        heroLink.href = url || "#";
        heroThumb.src = "https://i.ytimg.com/vi/" + encodeURIComponent(activeVideoId) + "/hqdefault.jpg";
        heroThumb.style.display = "block";
        heroFrame.style.display = "none";
        heroFrame.src = "";
        if (autoplay && activeVideoId) {
            heroFrame.src = "https://www.youtube-nocookie.com/embed/" + encodeURIComponent(activeVideoId) + "?autoplay=1&rel=0&modestbranding=1&iv_load_policy=3";
            heroFrame.style.display = "block";
            heroThumb.style.display = "none";
        }
    }

    function applyFilters() {
        if (!cards.length) return;
        var q = (searchInput ? searchInput.value : "").trim().toLowerCase();
        var shown = 0;
        cards.forEach(function(card){
            var t = card.getAttribute("data-title") || "";
            var s = card.getAttribute("data-sport") || "";
        var byText = q === "" || t.indexOf(q) !== -1;
            var bySport = activeSport === "all" || s === activeSport;
            var ok = byText && bySport;
            card.style.display = ok ? "block" : "none";
            if (ok) shown++;
        });
        if (empty) {
            if (q === "") {
                empty.textContent = shown > 0 ? "" : "No videos available right now.";
                empty.style.display = shown > 0 ? "none" : "block";
            } else if (shown === 0) {
                empty.textContent = "No videos found for your search. Use Search on YouTube.";
                empty.style.display = "block";
            } else {
                empty.style.display = "none";
            }
        }
        if (heroSection && q === "") {
            heroSection.style.display = "none";
        }
    }

    if (searchInput) {
        searchInput.addEventListener("input", applyFilters);
    }
    if (heroPlay) {
        heroPlay.addEventListener("click", function(){
            if (!activeVideoId) return;
            heroFrame.src = "https://www.youtube-nocookie.com/embed/" + encodeURIComponent(activeVideoId) + "?autoplay=1&rel=0&modestbranding=1&iv_load_policy=3";
            heroFrame.style.display = "block";
            if (heroThumb) heroThumb.style.display = "none";
        });
    }
    if (ytSearchBtn && searchInput) {
        ytSearchBtn.addEventListener("click", function(){
            var q = (searchInput.value || "").trim();
            ytSearchBtn.href = "https://www.youtube.com/results?search_query=" + encodeURIComponent(q || "sports highlights");
        });
    }

    if (filterWrap) {
        filterWrap.addEventListener("click", function(e){
            var btn = e.target.closest(".chip");
            if (!btn) return;
            var chips = filterWrap.querySelectorAll(".chip");
            chips.forEach(function(c){ c.classList.remove("active"); });
            btn.classList.add("active");
            activeSport = btn.getAttribute("data-sport") || "all";
            applyFilters();
        });
    }

    document.addEventListener("click", function(e){
        var btn = e.target.closest(".play-btn");
        if (!btn) return;
        e.preventDefault();
        if (!heroFrame || !heroTitle || !heroLink) return;
        var id = btn.getAttribute("data-id") || "";
        var title = btn.getAttribute("data-title") || "Video";
        var url = btn.getAttribute("data-url") || "#";
        mountHero(id, title, url, true);
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    applyFilters();
})();
</script>
</body>
</html>
