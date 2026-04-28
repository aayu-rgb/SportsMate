<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function parse_price_value($raw): ?float
{
    if ($raw === null) {
        return null;
    }
    $text = trim((string)$raw);
    if ($text === '') {
        return null;
    }
    $clean = preg_replace('/[^0-9.]/', '', $text);
    if ($clean === '' || !is_numeric($clean)) {
        return null;
    }
    return (float)$clean;
}

function inr(float $value): string
{
    return "INR " . number_format($value, 2);
}

$items = [];
$sourceNote = "Live comparison from your kit catalog.";

try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'sports_kits'");
    $hasSportsKits = $tableCheck && $tableCheck->num_rows > 0;

    if ($hasSportsKits) {
        $cols = [];
        $colsRes = $conn->query("SHOW COLUMNS FROM sports_kits");
        while ($colsRes && $c = $colsRes->fetch_assoc()) {
            $field = (string)($c['Field'] ?? '');
            if ($field !== '') {
                $cols[$field] = true;
            }
        }

        $pick = function (array $choices) use ($cols): string {
            foreach ($choices as $c) {
                if (isset($cols[$c])) {
                    return $c;
                }
            }
            return '';
        };

        $nameCol = $pick(['kit_name', 'name', 'title', 'product_name']);
        $sportCol = $pick(['sport', 'category']);
        $priceCol = $pick(['sale_price', 'price', 'kit_price', 'mrp', 'amount', 'cost']);
        $storeCol = $pick(['store', 'seller', 'brand', 'shop', 'source']);
        $linkCol = $pick(['buy_link', 'product_url', 'url', 'link']);

        if ($nameCol !== '' && $priceCol !== '') {
            $selectCols = [$nameCol . " AS kit_name", $priceCol . " AS price"];
            if ($sportCol !== '') {
                $selectCols[] = $sportCol . " AS sport";
            }
            if ($storeCol !== '') {
                $selectCols[] = $storeCol . " AS store";
            }
            if ($linkCol !== '') {
                $selectCols[] = $linkCol . " AS buy_link";
            }

            $sql = "SELECT " . implode(", ", $selectCols) . " FROM sports_kits ORDER BY " . $nameCol . " ASC LIMIT 120";
            $res = $conn->query($sql);
            while ($res && $row = $res->fetch_assoc()) {
                $name = trim((string)($row['kit_name'] ?? 'Kit'));
                $price = parse_price_value($row['price'] ?? null);
                if ($price === null) {
                    continue;
                }
                $items[] = [
                    'name' => $name !== '' ? $name : 'Kit',
                    'sport' => trim((string)($row['sport'] ?? 'Sports')),
                    'store' => trim((string)($row['store'] ?? 'Store')),
                    'price' => $price,
                    'buy_link' => trim((string)($row['buy_link'] ?? ''))
                ];
            }
        }
    }
} catch (Throwable $e) {
    $items = [];
}

if (empty($items)) {
    $sourceNote = "Showing starter comparison data. Add prices in `sports_kits` for live results.";
    $items = [
        ['name' => 'Football Shoes', 'sport' => 'Football', 'store' => 'Nike', 'price' => 4999.00, 'buy_link' => ''],
        ['name' => 'Football Shoes', 'sport' => 'Football', 'store' => 'Adidas', 'price' => 4599.00, 'buy_link' => ''],
        ['name' => 'Cricket Bat', 'sport' => 'Cricket', 'store' => 'MRF', 'price' => 6999.00, 'buy_link' => ''],
        ['name' => 'Cricket Bat', 'sport' => 'Cricket', 'store' => 'SS', 'price' => 6499.00, 'buy_link' => ''],
        ['name' => 'Basketball Jersey', 'sport' => 'Basketball', 'store' => 'Puma', 'price' => 2299.00, 'buy_link' => ''],
        ['name' => 'Basketball Jersey', 'sport' => 'Basketball', 'store' => 'Under Armour', 'price' => 2499.00, 'buy_link' => '']
    ];
}

$sportsSet = [];
$minPrice = null;
$maxPrice = null;
$minName = '';
$maxName = '';
$minStore = '';
$maxStore = '';

foreach ($items as $it) {
    $sp = trim((string)($it['sport'] ?? 'Sports'));
    $sportsSet[$sp !== '' ? $sp : 'Sports'] = true;
    $price = (float)$it['price'];
    if ($minPrice === null || $price < $minPrice) {
        $minPrice = $price;
        $minName = (string)$it['name'];
        $minStore = (string)$it['store'];
    }
    if ($maxPrice === null || $price > $maxPrice) {
        $maxPrice = $price;
        $maxName = (string)$it['name'];
        $maxStore = (string)$it['store'];
    }
}

$sports = array_keys($sportsSet);
sort($sports);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kit Price Comparison - SportsMate</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Teko:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:"Barlow",sans-serif;
    color:#e8f3ff;
    background:
      linear-gradient(140deg, rgba(3,12,28,.9), rgba(5,22,48,.84)),
      url('https://images.unsplash.com/photo-1511886929837-354d827aae26?auto=format&fit=crop&w=1500&q=80') center/cover no-repeat fixed;
}
.wrap{width:min(1060px,94vw);margin:24px auto}
.panel{
    border:1px solid rgba(117,180,240,.45);
    background:linear-gradient(160deg, rgba(8,24,45,.82), rgba(8,28,54,.66));
    border-radius:22px;
    padding:24px;
    backdrop-filter:blur(4px);
}
.title{margin:0;font-family:"Teko",sans-serif;font-size:52px;letter-spacing:.8px;line-height:.9}
.sub{margin:8px 0 10px;color:#bdd8f2}
.note{font-size:13px;color:#a6c9ea;margin-bottom:14px}
.search-box{display:flex;gap:8px;align-items:center;margin-bottom:10px}
.input{
    width:100%;padding:12px 13px;border-radius:11px;border:1px solid rgba(126,190,244,.6);
    background:#f8fcff;color:#16395d;font-size:15px;outline:none;
}
.input:focus{border-color:#33dbff;box-shadow:0 0 0 3px rgba(51,219,255,.2)}
.btn{
    padding:12px 14px;border:none;border-radius:11px;font-weight:700;cursor:pointer;
    color:#042236;background:linear-gradient(120deg,#23ddff,#4effab);
    white-space:nowrap;
}
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin:10px 0 14px}
.sum{
    border:1px solid rgba(126,190,244,.5);
    border-radius:12px;
    padding:12px;
    background:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
}
.sum h3{margin:0;font-size:14px;color:#b7d8f5}
.sum strong{display:block;margin-top:6px;font-size:21px;color:#ecf8ff}
.sum small{color:#a9caeb}
.tools{display:grid;grid-template-columns:1fr auto;gap:10px;margin:8px 0 10px}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{
    border:1px solid rgba(137,200,247,.55);
    background:rgba(255,255,255,.1);
    color:#d7eeff;
    border-radius:999px;
    padding:8px 12px;
    font-weight:700;
    font-size:13px;
    cursor:pointer;
}
.chip.active{background:linear-gradient(120deg,#21ddff,#54ffb2);color:#052334;border-color:transparent}
.sort{
    border:1px solid rgba(126,190,244,.6);
    border-radius:10px;
    padding:10px;
    background:#0a2748;
    color:#e8f3ff;
}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}
.card{
    border:1px solid rgba(126,190,244,.5);
    border-radius:14px;
    padding:12px;
    background:linear-gradient(135deg, rgba(12,39,72,.95), rgba(10,33,60,.9));
}
.name{font-size:19px;font-weight:700}
.meta{color:#a9caeb;font-size:14px;margin-top:5px}
.row{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:10px}
.price{font-size:24px;font-weight:800;color:#d6fff1}
.tag{
    font-size:11px;
    border-radius:999px;
    padding:6px 10px;
    font-weight:700;
}
.tag.low{background:rgba(84,255,178,.18);border:1px solid rgba(84,255,178,.44);color:#ceffe9}
.tag.high{background:rgba(255,90,111,.18);border:1px solid rgba(255,90,111,.5);color:#ffd8df}
.buy{
    text-decoration:none;
    color:#052334;
    background:linear-gradient(120deg,#21ddff,#54ffb2);
    padding:8px 10px;
    border-radius:8px;
    font-weight:700;
    font-size:13px;
}
.empty{
    border:1px dashed rgba(126,190,244,.5);
    border-radius:12px;
    padding:14px;
    color:#bfd9f3;
}
.back{display:inline-flex;align-items:center;gap:8px;margin-top:16px;color:#9fe9ff;text-decoration:none;font-weight:700}
@media (max-width:760px){
    .tools{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <h1 class="title">SPORTS KIT PRICE COMPARISON</h1>
        <p class="sub">Compare kit prices quickly and pick the best value.</p>
        <div class="note"><?= htmlspecialchars($sourceNote, ENT_QUOTES, 'UTF-8') ?></div>

        <form action="https://www.google.com/search" method="GET" target="_blank">
            <div class="search-box">
                <input class="input" type="text" name="q" placeholder="Search kits online (e.g. football shoes, cricket bat)" required>
                <input type="hidden" name="tbm" value="shop">
                <button class="btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search Web</button>
            </div>
        </form>

        <section class="summary">
            <article class="sum">
                <h3>Lowest Price</h3>
                <strong><?= $minPrice !== null ? htmlspecialchars(inr((float)$minPrice), ENT_QUOTES, 'UTF-8') : 'N/A' ?></strong>
                <small><?= htmlspecialchars(trim($minName . ($minStore !== '' ? " - " . $minStore : '')), ENT_QUOTES, 'UTF-8') ?></small>
            </article>
            <article class="sum">
                <h3>Highest Price</h3>
                <strong><?= $maxPrice !== null ? htmlspecialchars(inr((float)$maxPrice), ENT_QUOTES, 'UTF-8') : 'N/A' ?></strong>
                <small><?= htmlspecialchars(trim($maxName . ($maxStore !== '' ? " - " . $maxStore : '')), ENT_QUOTES, 'UTF-8') ?></small>
            </article>
            <article class="sum">
                <h3>Total Listed</h3>
                <strong><?= (int)count($items) ?> Kits</strong>
                <small>Filtered list updates instantly</small>
            </article>
        </section>

        <div class="tools">
            <div class="chips" id="sportFilters">
                <button class="chip active" type="button" data-sport="all">All Sports</button>
                <?php foreach ($sports as $sport): ?>
                    <button class="chip" type="button" data-sport="<?= htmlspecialchars(strtolower($sport), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sport, ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
            </div>
            <select id="sortSelect" class="sort">
                <option value="low">Price: Low to High</option>
                <option value="high">Price: High to Low</option>
                <option value="name">Name: A to Z</option>
            </select>
        </div>

        <?php if (!empty($items)): ?>
            <div id="kitsGrid" class="grid">
                <?php foreach ($items as $it): ?>
                    <?php
                        $priceVal = (float)$it['price'];
                        $isLow = $minPrice !== null && abs($priceVal - (float)$minPrice) < 0.0001;
                        $isHigh = $maxPrice !== null && abs($priceVal - (float)$maxPrice) < 0.0001;
                    ?>
                    <article class="card"
                        data-sport="<?= htmlspecialchars(strtolower((string)$it['sport']), ENT_QUOTES, 'UTF-8') ?>"
                        data-name="<?= htmlspecialchars(strtolower((string)$it['name']), ENT_QUOTES, 'UTF-8') ?>"
                        data-price="<?= htmlspecialchars((string)$priceVal, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="name"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="meta"><?= htmlspecialchars((string)$it['sport'], ENT_QUOTES, 'UTF-8') ?><?= !empty($it['store']) ? ' | ' . htmlspecialchars((string)$it['store'], ENT_QUOTES, 'UTF-8') : '' ?></div>
                        <div class="row">
                            <div class="price"><?= htmlspecialchars(inr($priceVal), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($isLow): ?><span class="tag low">Cheapest</span><?php endif; ?>
                            <?php if ($isHigh): ?><span class="tag high">Highest</span><?php endif; ?>
                        </div>
                        <?php if (!empty($it['buy_link'])): ?>
                            <div class="row">
                                <span></span>
                                <a class="buy" href="<?= htmlspecialchars((string)$it['buy_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Buy</a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <p id="emptyState" class="empty" style="display:none">No kits found for this sport.</p>
        <?php else: ?>
            <p class="empty">No priced kits available yet.</p>
        <?php endif; ?>

        <a class="back" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i>Back to Dashboard</a>
    </section>
</div>
<script>
(function(){
    var wrap = document.getElementById("kitsGrid");
    if (!wrap) return;

    var cards = Array.prototype.slice.call(wrap.querySelectorAll(".card"));
    var chips = document.getElementById("sportFilters");
    var sort = document.getElementById("sortSelect");
    var empty = document.getElementById("emptyState");
    var activeSport = "all";

    function applyView() {
        cards.forEach(function(card){
            var sport = card.getAttribute("data-sport") || "";
            var show = activeSport === "all" || sport === activeSport;
            card.style.display = show ? "block" : "none";
        });

        var visible = cards.filter(function(card){ return card.style.display !== "none"; });
        var mode = sort ? sort.value : "low";

        visible.sort(function(a, b){
            var ap = Number(a.getAttribute("data-price") || 0);
            var bp = Number(b.getAttribute("data-price") || 0);
            var an = a.getAttribute("data-name") || "";
            var bn = b.getAttribute("data-name") || "";
            if (mode === "high") return bp - ap;
            if (mode === "name") return an.localeCompare(bn);
            return ap - bp;
        });

        visible.forEach(function(card){ wrap.appendChild(card); });

        if (empty) {
            empty.style.display = visible.length ? "none" : "block";
        }
    }

    if (chips) {
        chips.addEventListener("click", function(e){
            var btn = e.target.closest(".chip");
            if (!btn) return;
            chips.querySelectorAll(".chip").forEach(function(x){ x.classList.remove("active"); });
            btn.classList.add("active");
            activeSport = btn.getAttribute("data-sport") || "all";
            applyView();
        });
    }

    if (sort) {
        sort.addEventListener("change", applyView);
    }

    applyView();
})();
</script>
</body>
</html>
