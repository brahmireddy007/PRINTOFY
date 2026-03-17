<?php
session_start();

/* ================= DB CONNECTION ================= */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "printofy_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB failed: " . $conn->connect_error);
}

/* ================= GET LOGGED IN USER ID ================= */
function getSessionUserId() {
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return intval($_SESSION['user_id']);
    }
    if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
        return intval($_SESSION['id']);
    }
    return 0;
}

/* ================= AJAX ACTIONS ================= */
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'ratings') {
        $sql = "
            SELECT product_id, ROUND(AVG(rating),1) AS avg, COUNT(*) AS count
            FROM product_review
            GROUP BY product_id
        ";
        $result = $conn->query($sql);

        $ratings = [];
        while ($row = $result->fetch_assoc()) {
            $ratings[$row['product_id']] = [
                "avg" => $row['avg'],
                "count" => $row['count']
            ];
        }

        echo json_encode($ratings);
        exit;
    }

    if ($action === 'reviews') {
        $product_id = intval($_GET['product_id'] ?? 0);

        $stmt = $conn->prepare("
            SELECT pr.review_id, pr.product_id, pr.user_id, u.name, pr.rating, pr.review_text, pr.review_date
            FROM product_review pr
            LEFT JOIN users u ON pr.user_id = u.id
            WHERE pr.product_id = ?
            ORDER BY pr.review_date DESC
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        echo json_encode($reviews);
        exit;
    }

    if ($action === 'add_review') {
        $user_id = getSessionUserId();

        if ($user_id <= 0) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "login required"]);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $product_id = intval($data['product_id'] ?? 0);
        $rating = intval($data['rating'] ?? 0);
        $review_text = trim($data['review_text'] ?? '');

        if ($product_id <= 0 || $rating < 1 || $rating > 5 || $review_text === '') {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "invalid input"]);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO product_review (product_id, user_id, rating, review_text, review_date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $review_text);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "db insert failed"]);
        }
        exit;
    }

    echo json_encode(["status" => "error", "message" => "invalid action"]);
    exit;
}

/* ================= HELPERS FOR PRODUCTS ================= */
function getCategory($name) {
    $name = strtolower($name);

    if (strpos($name, 'tee') !== false || strpos($name, 'shirt') !== false || strpos($name, 'polo') !== false) return 'tshirt';
    if (strpos($name, 'hoodie') !== false) return 'hoodie';
    if (strpos($name, 'tote') !== false || strpos($name, 'bag') !== false) return 'tote';
    if (strpos($name, 'cap') !== false || strpos($name, 'hat') !== false || strpos($name, 'beanie') !== false) return 'cap';
    if (strpos($name, 'mug') !== false || strpos($name, 'tumbler') !== false) return 'mug';
    if (strpos($name, 'phone') !== false || strpos($name, 'case') !== false) return 'phone';
    if (strpos($name, 'pillow') !== false) return 'pillow';
    if (strpos($name, 'notebook') !== false || strpos($name, 'journal') !== false || strpos($name, 'sketchbook') !== false) return 'notebook';
    if (strpos($name, 'poster') !== false || strpos($name, 'print') !== false || strpos($name, 'polaroid') !== false) return 'poster';
    if (strpos($name, 'sticker') !== false) return 'sticker';

    return 'other';
}

function getEmoji($cat) {
    $map = [
        'tshirt' => '👕',
        'hoodie' => '🧥',
        'tote' => '👜',
        'cap' => '🧢',
        'mug' => '☕',
        'phone' => '📱',
        'pillow' => '🛋️',
        'notebook' => '📓',
        'poster' => '🖼️',
        'sticker' => '🌟',
        'other' => '✨'
    ];
    return $map[$cat] ?? '✨';
}

function getDescMap() {
    return [
        'Classic Crew Tee' => 'Everyday essential, your art front & center.',
        'Oversized Drop Tee' => 'Loose fit, loud statement. 100% cotton.',
        'Crop Graphic Tee' => 'Cropped, comfy, and completely custom.',
        'Acid Wash Tee' => 'Vintage texture meets your custom print.',
        'Polo Custom Print' => 'Smart-casual with a personalised twist.',

        'Pullover Hoodie' => 'Warm, cosy, and all yours. 340 GSM fleece.',
        'Zip-Up Hoodie' => 'Layer it up with your custom front print.',
        'Cropped Hoodie' => 'Streetwear cropped silhouette, vivid prints.',
        'Oversized Hoodie' => 'The big hug hoodie. Boxy, bold, comfortable.',

        'Canvas Tote Bag' => 'Carry your art to every coffee shop.',
        'Large Beach Tote' => 'Sun, sand, and your custom design.',
        'Mini Zip Tote' => 'Compact and cute with a big personality.',
        'Rope Handle Tote' => 'Aesthetic rope handles + your artwork = perfection.',

        'Classic Dad Cap' => 'Six-panel unstructured cap, embroidered logo.',
        'Snapback Cap' => 'Flat-brim snapback with front patch print.',
        'Bucket Hat' => 'Festival-ready bucket hat, print all over.',
        'Beanie' => 'Cosy knit beanie with patch or print label.',

        'Ceramic Coffee Mug' => 'Start every morning with your own art.',
        'Magic Colour-Change Mug' => 'Reveal your design with hot liquid.',
        'Travel Tumbler' => 'Double-walled steel tumbler, your print stays forever.',
        'Tall Latte Mug' => 'Tall, elegant, and totally personalised.',

        'iPhone Tough Case' => 'Dual-layer protection + your custom print.',
        'Android Slim Case' => 'Sleek and scratch-resistant custom case.',
        'Clear Print Case' => 'Show off the case and your design simultaneously.',
        'Grip & Stand Case' => 'Custom print + built-in kickstand. Practical flex.',

        'Square Throw Pillow' => 'Turn any corner of your room into a gallery.',
        'Long Bolster Pillow' => 'Couch-ready with full custom print wrap.',
        'Photo Memory Pillow' => 'A soft keepsake you\'ll actually cuddle.',

        'Hardcover Notebook' => 'A5 dot-grid, 200 pages, your cover art.',
        'Spiral Sketchbook' => 'Perfect bound, lay-flat pages for artists.',
        'Mini Pocket Journal' => 'Carry your thoughts and your art together.',

        'A3 Matte Poster' => 'Gallery-quality print for your favourite wall.',
        'A2 Gloss Poster' => 'Big, bold, and brilliant. Makes a statement.',
        'Framed Wall Print' => 'Ready to hang — frame + custom print combo.',
        'Retro Polaroid Set (9)' => 'Nine polaroid-style mini prints for aesthetic walls.',

        'Die-Cut Sticker Pack' => 'Weatherproof vinyl stickers, any shape.',
        'Holographic Stickers' => 'Rainbow shimmer + your artwork = magic.',
        'Circle Sticker Sheet' => '12 matching circle stickers per sheet.',
        'Laptop Sticker Bundle' => '5 custom stickers perfect for your MacBook lid.'
    ];
}

function getOldPriceMap() {
    return [
        'Oversized Drop Tee' => 799,
        'Zip-Up Hoodie' => 1799,
        'Rope Handle Tote' => 649,
        'Bucket Hat' => 899,
        'Travel Tumbler' => 749,
        'Clear Print Case' => 499,
        'Long Bolster Pillow' => 999,
        'Mini Pocket Journal' => 399,
        'Framed Wall Print' => 1099,
        'Circle Sticker Sheet' => 169
    ];
}

function getBadgeMap() {
    return [
        'Classic Crew Tee' => ['hot', '🔥 Best Seller'],
        'Oversized Drop Tee' => ['sale', '🏷️ Sale'],
        'Crop Graphic Tee' => ['new', '✨ New'],

        'Pullover Hoodie' => ['hot', '🔥 Trending'],
        'Zip-Up Hoodie' => ['sale', '🏷️ Sale'],
        'Cropped Hoodie' => ['new', '✨ New'],

        'Canvas Tote Bag' => ['hot', '🔥 Fan Fave'],
        'Large Beach Tote' => ['new', '✨ New'],
        'Rope Handle Tote' => ['sale', '🏷️ Sale'],

        'Classic Dad Cap' => ['hot', '🔥 Bestseller'],
        'Snapback Cap' => ['new', '✨ New'],
        'Bucket Hat' => ['sale', '🏷️ Sale'],

        'Ceramic Coffee Mug' => ['hot', '🔥 Popular'],
        'Magic Colour-Change Mug' => ['new', '✨ New'],
        'Travel Tumbler' => ['sale', '🏷️ Sale'],

        'iPhone Tough Case' => ['hot', '🔥 Top Pick'],
        'Android Slim Case' => ['new', '✨ New'],
        'Clear Print Case' => ['sale', '🏷️ Sale'],

        'Square Throw Pillow' => ['new', '✨ New'],
        'Long Bolster Pillow' => ['sale', '🏷️ Sale'],
        'Photo Memory Pillow' => ['hot', '🔥 Gift Idea'],

        'Hardcover Notebook' => ['hot', '🔥 Creator Fave'],
        'Spiral Sketchbook' => ['new', '✨ New'],
        'Mini Pocket Journal' => ['sale', '🏷️ Sale'],

        'A3 Matte Poster' => ['hot', '🔥 Popular'],
        'A2 Gloss Poster' => ['new', '✨ New'],
        'Framed Wall Print' => ['sale', '🏷️ Sale'],

        'Die-Cut Sticker Pack' => ['hot', '🔥 Fan Fave'],
        'Holographic Stickers' => ['new', '✨ New'],
        'Circle Sticker Sheet' => ['sale', '🏷️ Sale']
    ];
}

/* ================= FETCH PRODUCTS ================= */
$descMap = getDescMap();
$oldPriceMap = getOldPriceMap();
$badgeMap = getBadgeMap();

$productSql = "SELECT product_id, product_name, price, image, stock FROM products ORDER BY product_id ASC";
$productResult = $conn->query($productSql);

$products_js = [];

while ($row = $productResult->fetch_assoc()) {
    $name = $row['product_name'];
    $cat = getCategory($name);
    $badge = null;
    $badgeText = null;
    $oldPrice = $oldPriceMap[$name] ?? null;

    if (isset($badgeMap[$name])) {
        $badge = $badgeMap[$name][0];
        $badgeText = $badgeMap[$name][1];
    }

    $products_js[] = [
        "id" => (int)$row['product_id'],
        "cat" => $cat,
        "name" => $name,
        "desc" => $descMap[$name] ?? 'Custom product made for your style.',
        "price" => (int)$row['price'],
        "oldPrice" => $oldPrice,
        "emoji" => getEmoji($cat),
        "badge" => $badge,
        "badgeText" => $badgeText,
        "img" => $row['image'],
        "stock" => (int)$row['stock']
    ];
}

$user_id = getSessionUserId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — PRINTOFY</title>

<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,700;0,900;1,400;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --ink: #2A2A2A;
  --cream: #F9F5ED;
  --red: #902124;
  --blue: #8EB9FF;
  --wine: #4D0011;
  --blush: #C2858C;
  --white: #ffffff;
}

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  color: var(--ink);
  overflow-x: hidden;
  background:
    radial-gradient(950px 650px at 14% 18%, rgba(142,185,255,0.90), transparent 60%),
    radial-gradient(980px 700px at 88% 22%, rgba(194,133,140,0.82), transparent 62%),
    radial-gradient(1050px 750px at 55% 95%, rgba(144,33,36,0.65), transparent 60%),
    linear-gradient(135deg, rgba(77,0,17,0.60) 0%, rgba(249,245,237,1) 48%, rgba(142,185,255,0.55) 100%);
  background-attachment: fixed;
}

body::before {
  content: '';
  position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.035'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 9999;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(249,245,237,0.78);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-bottom: 1px solid rgba(26,16,37,0.07);
}

.nav-container{
  max-width: 1280px;
  margin: 0 auto;
  padding: 14px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

.logo-mark {
  font-family: 'Fraunces', serif;
  font-weight: 900;
  font-size: 22px;
  letter-spacing: -0.5px;
  color: var(--ink);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--blue), var(--red));
  display: inline-block;
}

.nav-menu{
  display:flex;
  align-items:center;
  gap:18px;
}

.nav-link {
  font-weight: 500;
  font-size: 14px;
  color: var(--ink);
  opacity: 0.7;
  transition: opacity 0.2s;
  letter-spacing: 0.02em;
  text-decoration: none;
}

.nav-link:hover { opacity: 1; }

.cart-link {
  position: relative;
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
  font-size: 14px;
  color: var(--ink);
  text-decoration: none;
  opacity: 0.85;
}

.cart-badge {
  background: var(--red);
  color: white;
  font-size: 10px;
  font-weight: 700;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-login {
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: var(--cream);
  font-size: 13px;
  font-weight: 600;
  padding: 8px 22px;
  border-radius: 100px;
  text-decoration: none;
  transition: background 0.2s, transform 0.15s;
  letter-spacing: 0.02em;
}

.btn-login:hover {
  background: linear-gradient(135deg, var(--red), var(--wine));
  transform: translateY(-1px);
}

.nav-toggle{
  display:none;
  font-size:22px;
  background:white;
  border:1.5px solid rgba(26,16,37,0.1);
  border-radius:14px;
  padding:6px 12px;
  cursor:pointer;
}

@media(max-width:768px){
  .nav-toggle{ display:block; }

  .nav-menu{
    position:absolute;
    top:64px;
    left:16px;
    right:16px;
    background: rgba(249,245,237,0.95);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border:1px solid rgba(26,16,37,0.08);
    border-radius:18px;
    padding:18px;
    display:none;
    flex-direction:column;
    align-items:flex-start;
    gap:14px;
    box-shadow:0 20px 50px rgba(0,0,0,0.08);
  }

  .nav-menu.open{ display:flex; }

  .btn-login{
    width:100%;
    text-align:center;
  }
}

.hero-strip {
  background: transparent;
  padding: 80px 32px 60px;
  position: relative;
  overflow: hidden;
  text-align: center;
}

.hero-strip .blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  pointer-events: none;
}

.hs-blob1 {
  width: 500px;
  height: 500px;
  background: rgba(142,185,255,0.95);
  top: -200px;
  right: -100px;
  opacity: 0.18;
}

.hs-blob2 {
  width: 350px;
  height: 350px;
  background: rgba(194,133,140,0.85);
  bottom: -120px;
  left: -80px;
  opacity: 0.14;
}

.hero-strip-inner {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
}

.hero-strip .eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--blue);
  margin-bottom: 14px;
  display: block;
}

.hero-strip h1 {
  font-family: 'Fraunces', serif;
  font-size: clamp(44px, 6vw, 72px);
  font-weight: 900;
  line-height: 0.95;
  letter-spacing: -2px;
  color: var(--ink);
}

.hero-strip h1 em {
  font-style: italic;
  color: var(--red);
}

.hero-strip p {
  font-size: 16px;
  color: rgba(26,16,37,0.55);
  margin-top: 16px;
  line-height: 1.6;
}

.filter-bar {
  max-width: 1280px;
  margin: 0 auto;
  padding: 36px 32px 0;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-btn {
  background: rgba(255,255,255,0.82);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1.5px solid rgba(26,16,37,0.1);
  color: rgba(26,16,37,0.6);
  padding: 9px 20px;
  border-radius: 100px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 7px;
}

.filter-btn:hover {
  border-color: var(--ink);
  color: var(--ink);
}

.filter-btn.active {
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  border-color: transparent;
}

.filter-count {
  background: rgba(255,255,255,0.2);
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 100px;
  font-weight: 700;
}

.filter-btn:not(.active) .filter-count {
  background: rgba(26,16,37,0.08);
  color: rgba(26,16,37,0.5);
}

.sort-select {
  margin-left: auto;
  background: rgba(255,255,255,0.82);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1.5px solid rgba(26,16,37,0.1);
  color: var(--ink);
  padding: 9px 16px;
  border-radius: 100px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  outline: none;
}

.products-wrap {
  max-width: 1280px;
  margin: 0 auto;
  padding: 32px 32px 80px;
}

.results-info {
  font-size: 13px;
  color: rgba(26,16,37,0.4);
  margin-bottom: 24px;
  font-weight: 500;
}

.results-info b { color: var(--ink); }

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 22px;
}

.p-card {
  background: rgba(255,255,255,0.82);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid rgba(255,255,255,0.45);
  border-radius: 22px;
  overflow: hidden;
  transition: transform 0.3s cubic-bezier(0.22,1,0.36,1), box-shadow 0.3s;
  position: relative;
}

.p-card:hover {
  transform: translateY(-7px);
  box-shadow: 0 20px 50px rgba(26,16,37,0.13);
}

.card-img {
  height: 230px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-size: 72px;
  user-select: none;
}

.card-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.5s cubic-bezier(0.22,1,0.36,1);
}

.p-card:hover .card-img img { transform: scale(1.06); }

.cat-tshirt { background: linear-gradient(145deg, rgba(142,185,255,0.70), rgba(194,133,140,0.45)); }
.cat-hoodie { background: linear-gradient(145deg, rgba(194,133,140,0.70), rgba(249,245,237,0.55)); }
.cat-tote { background: linear-gradient(145deg, rgba(142,185,255,0.50), rgba(77,0,17,0.22)); }
.cat-cap { background: linear-gradient(145deg, rgba(194,133,140,0.75), rgba(142,185,255,0.35)); }
.cat-mug { background: linear-gradient(145deg, rgba(77,0,17,0.55), rgba(194,133,140,0.55)); }
.cat-phone { background: linear-gradient(145deg, rgba(142,185,255,0.75), rgba(77,0,17,0.20)); }
.cat-pillow { background: linear-gradient(145deg, rgba(194,133,140,0.72), rgba(144,33,36,0.42)); }
.cat-notebook { background: linear-gradient(145deg, rgba(142,185,255,0.72), rgba(249,245,237,0.42)); }
.cat-poster { background: linear-gradient(145deg, rgba(194,133,140,0.68), rgba(249,245,237,0.48)); }
.cat-sticker { background: linear-gradient(145deg, rgba(142,185,255,0.78), rgba(194,133,140,0.58)); }
.cat-other { background: linear-gradient(145deg, rgba(142,185,255,0.5), rgba(194,133,140,0.5)); }

.card-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  background: white;
  color: var(--ink);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  padding: 4px 10px;
  border-radius: 100px;
  box-shadow: 0 2px 10px rgba(26,16,37,0.1);
}

.badge-new { background: var(--blue); color: var(--wine); }
.badge-hot { background: var(--red); color: white; }
.badge-sale { background: rgba(142,185,255,0.9); color: var(--ink); }

.wishlist-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 34px;
  height: 34px;
  background: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 10px rgba(26,16,37,0.1);
}

.card-body { padding: 18px 20px 20px; }

.card-cat {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(26,16,37,0.35);
  margin-bottom: 5px;
}

.card-name {
  font-family: 'Fraunces', serif;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.3px;
  line-height: 1.2;
}

.card-desc {
  font-size: 12px;
  color: rgba(26,16,37,0.45);
  margin-top: 4px;
  line-height: 1.5;
}

.product-rating{
  font-size:13px;
  margin-top:8px;
  color:#f5a623;
  display:flex;
  align-items:center;
  gap:6px;
  flex-wrap:wrap;
}

.product-rating span{
  color:rgba(26,16,37,0.45);
  font-size:12px;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 16px;
}

.card-price {
  font-family: 'Fraunces', serif;
  font-size: 22px;
  font-weight: 900;
  color: var(--wine);
}

.card-price .old-price {
  font-size: 13px;
  font-weight: 400;
  color: rgba(26,16,37,0.3);
  text-decoration: line-through;
  font-family: 'DM Sans', sans-serif;
  margin-left: 4px;
}

.btn-add {
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  border: none;
  padding: 9px 18px;
  border-radius: 100px;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.btn-add.added {
  background: rgba(142,185,255,0.9);
  color: var(--ink);
}

.review-actions {
  display: flex;
  gap: 8px;
  margin-top: 14px;
  flex-wrap: wrap;
}

.btn-small {
  border: none;
  border-radius: 999px;
  padding: 8px 12px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

.btn-view {
  background: rgba(142,185,255,0.22);
  color: var(--ink);
}

.btn-review {
  background: rgba(194,133,140,0.22);
  color: var(--ink);
}

.reviews-box, .review-form-box {
  margin-top: 14px;
  background: rgba(255,255,255,0.72);
  border: 1px solid rgba(26,16,37,0.08);
  border-radius: 16px;
  padding: 12px;
}

.hidden { display: none; }

.review-item {
  padding: 10px 0;
  border-bottom: 1px solid rgba(26,16,37,0.08);
}

.review-item:last-child {
  border-bottom: none;
}

.review-user {
  font-weight: 700;
  font-size: 12px;
  color: var(--ink);
}

.review-text {
  font-size: 12px;
  color: rgba(26,16,37,0.75);
  margin-top: 4px;
  line-height: 1.5;
}

.review-date {
  font-size: 11px;
  color: rgba(26,16,37,0.45);
  margin-top: 4px;
}

.review-form-box select,
.review-form-box textarea {
  width: 100%;
  border: 1px solid rgba(26,16,37,0.12);
  border-radius: 12px;
  padding: 10px 12px;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  margin-top: 8px;
  outline: none;
}

.review-form-box textarea {
  min-height: 90px;
  resize: vertical;
}

.review-submit {
  margin-top: 10px;
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  border: none;
  padding: 10px 14px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.toast-msg {
  position: fixed;
  bottom: 28px;
  right: 28px;
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  padding: 13px 22px;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  z-index: 9000;
  pointer-events: none;
  transform: translateY(70px);
  opacity: 0;
  transition: all 0.35s cubic-bezier(0.22,1,0.36,1);
}

.toast-msg.show {
  transform: translateY(0);
  opacity: 1;
}

footer {
  border-top: 1px solid rgba(26,16,37,0.08);
  padding: 36px 32px;
  max-width: 1280px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.footer-logo {
  font-family:'Fraunces',serif;
  font-size:18px;
  font-weight:900;
  color: var(--ink);
}

.footer-copy {
  font-size:13px;
  color:rgba(26,16,37,0.4);
}

.empty-state {
  grid-column: 1/-1;
  text-align: center;
  padding: 80px 20px;
}

.empty-state .big-emoji {
  font-size: 60px;
  display: block;
  margin-bottom: 18px;
}

@media(max-width:700px){
  .hero-strip { padding: 60px 20px 48px; }
  .filter-bar { padding: 24px 20px 0; }
  .products-wrap { padding: 24px 20px 60px; }
  .sort-select { margin-left: 0; }
  footer { flex-direction:column; text-align:center; }
}
</style>
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <a class="logo-mark" href="index.php">
      <span class="logo-dot"></span>
      PRINTOFY
    </a>

    <button class="nav-toggle" type="button" onclick="toggleNav()">☰</button>

    <div class="nav-menu" id="navMenu">
      <a class="nav-link" href="index.php">Home</a>
      <a class="nav-link" href="product.php">Products</a>
      <a class="nav-link" href="customize.php">Customize</a>
      <a class="nav-link" href="reviews.php">Review</a>

      <a class="cart-link" href="cart.php">
        🛍️ Cart
        <span class="cart-badge" id="cart-count">0</span>
      </a>

      <a href="logout.php" class="btn-login">Logout</a>
    </div>
  </div>
</nav>

<div class="hero-strip">
  <div class="blob hs-blob1"></div>
  <div class="blob hs-blob2"></div>
  <div class="hero-strip-inner">
    <span class="eyebrow">✦ The Full Collection</span>
    <h1>everything<br>you can <em>print.</em></h1>
    <p>from tees to stickers — upload your vibe and slap it on basically anything.</p>
  </div>
</div>

<div class="filter-bar">
  <button class="filter-btn active" data-cat="all" onclick="filterCat('all',this)">All <span class="filter-count" id="count-all">0</span></button>
  <button class="filter-btn" data-cat="tshirt" onclick="filterCat('tshirt',this)">👕 T-Shirts <span class="filter-count" id="count-tshirt">0</span></button>
  <button class="filter-btn" data-cat="hoodie" onclick="filterCat('hoodie',this)">🧥 Hoodies <span class="filter-count" id="count-hoodie">0</span></button>
  <button class="filter-btn" data-cat="tote" onclick="filterCat('tote',this)">👜 Tote Bags <span class="filter-count" id="count-tote">0</span></button>
  <button class="filter-btn" data-cat="cap" onclick="filterCat('cap',this)">🧢 Caps <span class="filter-count" id="count-cap">0</span></button>
  <button class="filter-btn" data-cat="mug" onclick="filterCat('mug',this)">☕ Mugs <span class="filter-count" id="count-mug">0</span></button>
  <button class="filter-btn" data-cat="phone" onclick="filterCat('phone',this)">📱 Phone Cases <span class="filter-count" id="count-phone">0</span></button>
  <button class="filter-btn" data-cat="pillow" onclick="filterCat('pillow',this)">🛋️ Pillows <span class="filter-count" id="count-pillow">0</span></button>
  <button class="filter-btn" data-cat="notebook" onclick="filterCat('notebook',this)">📓 Notebooks <span class="filter-count" id="count-notebook">0</span></button>
  <button class="filter-btn" data-cat="poster" onclick="filterCat('poster',this)">🖼️ Posters <span class="filter-count" id="count-poster">0</span></button>
  <button class="filter-btn" data-cat="sticker" onclick="filterCat('sticker',this)">🌟 Stickers <span class="filter-count" id="count-sticker">0</span></button>

  <select class="sort-select" id="sortSelect" onchange="sortProducts()">
    <option value="default">Sort: Featured</option>
    <option value="low">Price: Low → High</option>
    <option value="high">Price: High → Low</option>
    <option value="name">Name A–Z</option>
  </select>
</div>

<div class="products-wrap">
  <p class="results-info" id="resultsInfo">Showing <b>0</b> products</p>
  <div class="grid" id="productGrid"></div>
</div>

<footer>
  <span class="footer-logo">PRINTOFY</span>
  <span class="footer-copy">© 2026 PRINTOFY · print your vibe ✦</span>
  <span class="footer-copy">made with drama and gradients</span>
</footer>

<div class="toast-msg" id="toast"></div>

<script>
const products = <?php echo json_encode($products_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const loggedInUserId = <?php echo (int)$user_id; ?>;

let currentCat = 'all';
let currentSort = 'default';
let wishlist = new Set();
let ratings = {};

function toggleNav(){
  document.getElementById("navMenu").classList.toggle("open");
}

document.addEventListener("click", (e) => {
  const menu = document.getElementById("navMenu");
  const navbar = document.querySelector(".navbar");
  if (!menu || !navbar) return;
  if (!navbar.contains(e.target)) menu.classList.remove("open");
});

async function refreshCartCount() {
  try {
    const res = await fetch("cart.php?action=count", { credentials: "include" });
    const count = await res.text();
    document.getElementById("cart-count").textContent = (count || "0").trim();
  } catch (e) {
    document.getElementById("cart-count").textContent = "0";
  }
}

async function loadRatings() {
  try {
    const res = await fetch("product.php?action=ratings");
    ratings = await res.json();
  } catch (e) {
    ratings = {};
  }
}

function getStars(id) {
  if (!ratings[id]) return `☆☆☆☆☆ <span>(0 reviews)</span>`;

  const avg = parseFloat(ratings[id].avg);
  const count = parseInt(ratings[id].count, 10);
  let stars = "";
  const full = Math.round(avg);

  for (let i = 0; i < 5; i++) {
    stars += i < full ? "★" : "☆";
  }

  return `${stars} <span>${avg} (${count} review${count !== 1 ? 's' : ''})</span>`;
}

function catLabel(cat) {
  const map = {
    tshirt:'T-Shirts',
    hoodie:'Hoodies',
    tote:'Tote Bags',
    cap:'Caps',
    mug:'Mugs',
    phone:'Phone Cases',
    pillow:'Pillows',
    notebook:'Notebooks',
    poster:'Posters',
    sticker:'Stickers',
    other:'Products'
  };
  return map[cat] || cat;
}

function escapeQuotes(s) {
  return String(s).replace(/'/g, "\\'");
}

function updateCounts() {
  const cats = ['tshirt','hoodie','tote','cap','mug','phone','pillow','notebook','poster','sticker'];
  document.getElementById('count-all').textContent = products.length;
  cats.forEach(c => {
    const el = document.getElementById('count-' + c);
    if (el) el.textContent = products.filter(p => p.cat === c).length;
  });
}

function renderProducts(list) {
  const grid = document.getElementById('productGrid');
  document.getElementById('resultsInfo').innerHTML = `Showing <b>${list.length}</b> product${list.length !== 1 ? 's' : ''}`;

  if (list.length === 0) {
    grid.innerHTML = `
      <div class="empty-state">
        <span class="big-emoji">🔍</span>
        <h3>nothing here yet</h3>
        <p>try a different category bestie</p>
      </div>`;
    return;
  }

  grid.innerHTML = list.map((p) => `
    <div class="p-card" data-cat="${p.cat}">
      <div class="card-img cat-${p.cat}">
        ${p.img ? `<img src="${p.img}" alt="${p.name}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">` : ''}
        <span class="emoji-fallback" style="display:${p.img ? 'none' : 'flex'};align-items:center;justify-content:center;width:100%;height:100%;font-size:72px;">${p.emoji}</span>
        ${p.badge ? `<span class="card-badge badge-${p.badge}">${p.badgeText}</span>` : ''}
        <button class="wishlist-btn ${wishlist.has(p.id) ? 'liked' : ''}" onclick="toggleWish(event, ${p.id})">
          ${wishlist.has(p.id) ? '❤️' : '🤍'}
        </button>
      </div>

      <div class="card-body">
        <p class="card-cat">${catLabel(p.cat)}</p>
        <h3 class="card-name">${p.name}</h3>
        <p class="card-desc">${p.desc}</p>

        <div class="product-rating">${getStars(p.id)}</div>

        <div class="card-footer">
          <span class="card-price">
            ₹${p.price}
            ${p.oldPrice ? `<span class="old-price">₹${p.oldPrice}</span>` : ''}
          </span>
          <button class="btn-add" id="btn-${p.id}" onclick="addToCart(event, ${p.id}, '${escapeQuotes(p.name)}', ${p.price})">
            + Add
          </button>
        </div>

        <div class="review-actions">
          <button class="btn-small btn-view" onclick="toggleReviews(${p.id})">view reviews</button>
          <button class="btn-small btn-review" onclick="toggleReviewForm(${p.id})">write review</button>
        </div>

        <div class="reviews-box hidden" id="reviews-box-${p.id}">
          <div id="reviews-content-${p.id}">loading...</div>
        </div>

        <div class="review-form-box hidden" id="review-form-${p.id}">
          <select id="rating-${p.id}">
            <option value="">select rating</option>
            <option value="5">5 - amazing</option>
            <option value="4">4 - really good</option>
            <option value="3">3 - okay</option>
            <option value="2">2 - meh</option>
            <option value="1">1 - nope</option>
          </select>
          <textarea id="comment-${p.id}" placeholder="write your review here..."></textarea>
          <button class="review-submit" onclick="submitReview(${p.id})">submit review</button>
        </div>
      </div>
    </div>
  `).join('');

  updateCounts();
}

function filterCat(cat, btn) {
  currentCat = cat;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

function sortProducts() {
  currentSort = document.getElementById('sortSelect').value;
  applyFilters();
}

function applyFilters() {
  let list = currentCat === 'all' ? [...products] : products.filter(p => p.cat === currentCat);

  if (currentSort === 'low') list.sort((a,b) => a.price - b.price);
  if (currentSort === 'high') list.sort((a,b) => b.price - a.price);
  if (currentSort === 'name') list.sort((a,b) => a.name.localeCompare(b.name));

  renderProducts(list);
}

async function addToCart(e, id, name, price) {
  e.stopPropagation();

  try {
    const res = await fetch("cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ product_id: id, name, price })
    });

    const text = await res.text();

    if (res.status === 401) {
      showToast("login first bestie 🔒");
      setTimeout(() => window.location.href = "login.html", 900);
      return;
    }

    if (!res.ok) {
      showToast("couldn't add to cart 💀");
      return;
    }

    const btn = document.getElementById('btn-' + id);
    if (btn) {
      btn.textContent = '✓ Added';
      btn.classList.add('added');
      setTimeout(() => {
        btn.textContent = '+ Add';
        btn.classList.remove('added');
      }, 1800);
    }

    showToast(`${name} added to cart`);
    refreshCartCount();
  } catch (err) {
    showToast("network error. xampp is acting expensive");
  }
}

function toggleWish(e, id) {
  e.stopPropagation();
  const btn = e.currentTarget;

  if (wishlist.has(id)) {
    wishlist.delete(id);
    btn.textContent = '🤍';
    btn.classList.remove('liked');
    showToast('removed from wishlist');
  } else {
    wishlist.add(id);
    btn.textContent = '❤️';
    btn.classList.add('liked');
    showToast('added to wishlist');
  }
}

async function toggleReviews(productId) {
  const box = document.getElementById(`reviews-box-${productId}`);
  const content = document.getElementById(`reviews-content-${productId}`);

  if (!box.classList.contains('hidden')) {
    box.classList.add('hidden');
    return;
  }

  box.classList.remove('hidden');
  content.innerHTML = "loading...";

  try {
    const res = await fetch(`product.php?action=reviews&product_id=${productId}`);
    const reviews = await res.json();

    if (!reviews.length) {
      content.innerHTML = `<p style="font-size:12px;color:rgba(26,16,37,0.5);">no reviews yet</p>`;
      return;
    }

    content.innerHTML = reviews.map(r => `
      <div class="review-item">
        <div class="review-user">${r.name ? r.name : 'user #' + r.user_id} · ${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</div>
        <div class="review-text">${escapeHtml(r.review_text)}</div>
        <div class="review-date">${r.review_date}</div>
      </div>
    `).join('');
  } catch (e) {
    content.innerHTML = `<p style="font-size:12px;color:#902124;">could not load reviews</p>`;
  }
}

function toggleReviewForm(productId) {
  const box = document.getElementById(`review-form-${productId}`);
  box.classList.toggle('hidden');
}

async function submitReview(productId) {
  if (!loggedInUserId) {
    showToast("login first to review");
    setTimeout(() => window.location.href = "login.html", 900);
    return;
  }

  const rating = document.getElementById(`rating-${productId}`).value;
  const comment = document.getElementById(`comment-${productId}`).value.trim();

  if (!rating) {
    showToast("select a rating first");
    return;
  }

  if (!comment) {
    showToast("write a review first");
    return;
  }

  try {
    const res = await fetch("product.php?action=add_review", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        product_id: productId,
        rating: parseInt(rating),
        review_text: comment
      })
    });

    const data = await res.json();

    if (!res.ok || data.status !== "success") {
      showToast(data.message || "couldn't submit review");
      return;
    }

    document.getElementById(`rating-${productId}`).value = "";
    document.getElementById(`comment-${productId}`).value = "";
    document.getElementById(`review-form-${productId}`).classList.add('hidden');

    showToast("review submitted ✨");

    await loadRatings();
    applyFilters();

    setTimeout(() => {
      toggleReviews(productId);
      setTimeout(() => toggleReviews(productId), 150);
    }, 200);

  } catch (e) {
    showToast("review submit failed");
  }
}

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(() => t.classList.remove('show'), 2500);
}

async function initPage() {
  await refreshCartCount();
  await loadRatings();
  applyFilters();
}

initPage();
</script>
</body>
</html>
