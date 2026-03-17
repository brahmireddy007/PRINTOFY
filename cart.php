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

/* ================= PROMO (SESSION-BASED) ================= */
if (!isset($_SESSION["discount"])) $_SESSION["discount"] = 0;
if (!isset($_SESSION["promo_code"])) $_SESSION["promo_code"] = "";

/* apply promo */
if (isset($_GET["action"]) && $_GET["action"] === "promo" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $code = strtoupper(trim($_POST["promo_code"] ?? ""));

    $_SESSION["discount"] = 0;
    $_SESSION["promo_code"] = "";

    if ($code === "") {
        header("Location: cart.php");
        exit();
    }

    if ($code === "VIBE10") {
        $_SESSION["promo_code"] = "VIBE10";
    }
    if ($code === "QUEEN20") {
        $_SESSION["promo_code"] = "QUEEN20";
    }
    if ($code === "HOLI25") {
        $_SESSION["promo_code"] = "HOLI25";
    }
    else {
        $_SESSION["promo_error"] = "Invalid promo code";
    }

    header("Location: cart.php");
    exit();
}

/* remove promo */
if (isset($_GET["action"]) && $_GET["action"] === "promo_clear") {
    $_SESSION["discount"] = 0;
    $_SESSION["promo_code"] = "";
    header("Location: cart.php");
    exit();
}

/* ================= CART COUNT ================= */
if (isset($_GET["action"]) && $_GET["action"] === "count") {
    if (!isset($_SESSION["user_id"])) {
        echo "0";
        exit();
    }

    $uid = (int)$_SESSION["user_id"];
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    echo $row["c"] ?? "0";
    exit();
}

/* ================= ADD TO CART ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && (!isset($_GET["action"]) || $_GET["action"] === "")) {
    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);
        echo "Not logged in";
        exit();
    }

    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    $name = "";
    $price = 0;

    if (stripos($contentType, "application/json") !== false) {
        $data = json_decode(file_get_contents("php://input"), true);
        $name  = $data["name"] ?? "";
        $price = $data["price"] ?? 0;
    } else {
        $name  = $_POST["name"] ?? "";
        $price = $_POST["price"] ?? 0;
    }

    $name = trim($name);
    $price = (int)$price;

    if ($name === "" || $price <= 0) {
        http_response_code(400);
        echo "Invalid product";
        exit();
    }

    $uid = (int)$_SESSION["user_id"];

    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_name, price) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $uid, $name, $price);

    if ($stmt->execute()) {
        echo "added";
    } else {
        http_response_code(500);
        echo "DB error";
    }

    exit();
}

/* ================= REMOVE ONE ITEM ================= */
if (isset($_GET["action"]) && $_GET["action"] === "remove" && $_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.html");
        exit();
    }

    $uid = (int)$_SESSION["user_id"];
    $id  = (int)($_POST["id"] ?? 0);

    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();

    header("Location: cart.php");
    exit();
}

/* ================= CLEAR CART ================= */
if (isset($_GET["action"]) && $_GET["action"] === "clear") {
    if (isset($_SESSION["user_id"])) {
        $uid = (int)$_SESSION["user_id"];
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
    }

    $_SESSION["discount"] = 0;
    $_SESSION["promo_code"] = "";

    header("Location: cart.php");
    exit();
}

/* ================= CHECKOUT ================= */
$checkout_msg = "";

if (isset($_GET["action"]) && $_GET["action"] === "checkout") {
    if (isset($_SESSION["user_id"])) {
        $uid = (int)$_SESSION["user_id"];
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $checkout_msg = "Checkout successful 🎉";
    }

    $_SESSION["discount"] = 0;
    $_SESSION["promo_code"] = "";
}

/* ================= FETCH CART ================= */
$cart_items = [];
$total = 0;

if (isset($_SESSION["user_id"])) {
    $uid = (int)$_SESSION["user_id"];
    $stmt = $conn->prepare("SELECT id, product_name, price FROM cart WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total += (int)$row["price"];
    }
}

/* ================= APPLY PROMO DISCOUNT ================= */
$subtotal = (int)$total;
$discount = 0;

if (!empty($_SESSION["promo_code"]) && $_SESSION["promo_code"] === "VIBE10") {
    $discount = (int)round($subtotal * 0.10);
}
if (!empty($_SESSION["promo_code"]) && $_SESSION["promo_code"] === "QUEEN20") {
    $discount = (int)round($subtotal * 0.20);
}
if (!empty($_SESSION["promo_code"]) && $_SESSION["promo_code"] === "HOLI25") {
    $discount = (int)round($subtotal * 0.25);
}
$_SESSION["discount"] = $discount;

$final_total = max(0, $subtotal - $discount);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart | PRINTOFY</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,700;0,900;1,400;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
  --ink:#2A2A2A;
  --cream:#F9F5ED;

  --red:#902124;
  --blue:#8EB9FF;
  --wine:#4D0011;
  --blush:#C2858C;

  --lavender:var(--blue);
  --mint:rgba(142,185,255,0.35);
  --gold:var(--blush);
}

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  color: var(--ink);
  min-height: 100vh;
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
  pointer-events: none; z-index: 9999;
}

.blob {
  position: fixed;
  border-radius: 50%;
  filter: blur(90px);
  pointer-events: none;
  z-index: 0;
}
.blob-1 { width: 500px; height: 500px; background: rgba(142,185,255,0.95); top: -200px; right: -150px; opacity: 0.2; }
.blob-2 { width: 350px; height: 350px; background: rgba(194,133,140,0.85); bottom: -100px; left: -80px; opacity: 0.15; }

.navbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(249,245,237,0.78);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-bottom: 1px solid rgba(26,16,37,0.07);
  padding: 14px 0;
}
.logo-mark {
  font-family: 'Fraunces', serif;
  font-weight: 900; font-size: 20px;
  color: var(--ink); text-decoration: none;
  display: flex; align-items: center; gap: 8px;
}
.logo-dot {
  width: 9px; height: 9px; border-radius: 50%;
  background: linear-gradient(135deg, var(--blue), var(--red));
}
.back-btn {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 13px; font-weight: 600;
  color: rgba(26,16,37,0.55);
  text-decoration: none;
  padding: 7px 16px;
  border: 1.5px solid rgba(26,16,37,0.12);
  border-radius: 100px;
  transition: all 0.2s;
}
.back-btn:hover { color: var(--ink); border-color: var(--ink); }

.cart-page {
  position: relative; z-index: 1;
  padding: 60px 0 100px;
}

.page-label {
  font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.12em;
  color: var(--blue); margin-bottom: 8px;
}
.page-title {
  font-family: 'Fraunces', serif;
  font-size: clamp(36px, 5vw, 54px);
  font-weight: 900; line-height: 1;
  letter-spacing: -1.5px;
}
.page-title em { font-style: italic; color: var(--red); }

.cart-panel {
  background: rgba(255,255,255,0.82);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-radius: 28px;
  overflow: hidden;
  box-shadow: 0 4px 40px rgba(26,16,37,0.07);
  border: 1px solid rgba(255,255,255,0.45);
}

.empty-state {
  padding: 80px 40px;
  text-align: center;
}
.empty-icon {
  font-size: 64px;
  display: block;
  margin-bottom: 20px;
  animation: float 3s ease-in-out infinite;
}
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
.empty-state h3 {
  font-family: 'Fraunces', serif;
  font-size: 26px; font-weight: 800;
  letter-spacing: -0.5px;
}
.empty-state p {
  color: rgba(26,16,37,0.45);
  margin-top: 8px; font-size: 15px;
}
.empty-state .btn-shop {
  display: inline-block;
  margin-top: 26px;
  background: linear-gradient(135deg,var(--wine),var(--red));
  color: white;
  padding: 12px 30px; border-radius: 100px;
  font-size: 14px; font-weight: 600;
  text-decoration: none;
  transition: transform 0.18s, box-shadow 0.18s;
}
.empty-state .btn-shop:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(26,16,37,0.18);
}

.cart-items-list { padding: 0; margin: 0; list-style: none; }
.cart-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 28px;
  border-bottom: 1px solid rgba(26,16,37,0.06);
  transition: background 0.18s;
  animation: fadeSlide 0.4s ease forwards;
  opacity: 0;
}
@keyframes fadeSlide {
  from { opacity: 0; transform: translateX(-12px); }
  to   { opacity: 1; transform: translateX(0); }
}
.cart-item:last-child { border-bottom: none; }
.cart-item:hover { background: rgba(249,245,237,0.55); }

.item-left { display: flex; align-items: center; gap: 16px; }
.item-emoji {
  width: 50px; height: 50px;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
  background: linear-gradient(135deg, rgba(142,185,255,0.45), rgba(194,133,140,0.45));
}
.item-name {
  font-family: 'Fraunces', serif;
  font-size: 16px; font-weight: 700;
  letter-spacing: -0.2px;
}
.item-sub {
  font-size: 12px;
  color: rgba(26,16,37,0.4);
  margin-top: 2px;
}
.item-right { display: flex; align-items: center; gap: 16px; }
.item-price {
  font-family: 'Fraunces', serif;
  font-size: 18px; font-weight: 800;
  color: var(--wine);
}
.remove-btn {
  background: none; border: none;
  width: 32px; height: 32px;
  border-radius: 10px;
  cursor: pointer;
  color: rgba(26,16,37,0.3);
  font-size: 16px;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.18s, color 0.18s;
}
.remove-btn:hover { background: #fff0ef; color: var(--red); }

.order-summary {
  background: rgba(255,255,255,0.82);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-radius: 28px;
  padding: 30px;
  box-shadow: 0 4px 40px rgba(26,16,37,0.07);
  position: sticky;
  top: 90px;
  border: 1px solid rgba(255,255,255,0.45);
}
.summary-title {
  font-family: 'Fraunces', serif;
  font-size: 20px; font-weight: 800;
  letter-spacing: -0.3px;
  margin-bottom: 22px;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: rgba(26,16,37,0.55);
  margin-bottom: 12px;
}
.summary-row.total {
  font-family: 'Fraunces', serif;
  font-size: 22px; font-weight: 900;
  color: var(--ink);
  margin-top: 18px; padding-top: 18px;
  border-top: 1.5px solid rgba(26,16,37,0.08);
}
.summary-row.total span:last-child { color: var(--red); }

.promo-box { display: flex; gap: 8px; margin: 20px 0; }
.promo-input {
  flex: 1;
  border: 1.5px solid rgba(26,16,37,0.12);
  border-radius: 12px;
  padding: 10px 14px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px; font-weight: 500;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: rgba(249,245,237,0.85);
}
.promo-input:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 4px rgba(142,185,255,0.18);
}
.promo-apply {
  background: linear-gradient(135deg,var(--wine),var(--red));
  color: white;
  border: none; padding: 10px 16px;
  border-radius: 12px; font-size: 13px;
  font-weight: 600; cursor: pointer;
  transition: background 0.18s;
  white-space: nowrap;
}
.promo-apply:hover { background: linear-gradient(135deg,var(--red),var(--wine)); }

.btn-checkout {
  width: 100%;
  background: linear-gradient(135deg,var(--wine),var(--red));
  color: white;
  border: none; padding: 16px;
  border-radius: 16px;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px; font-weight: 700;
  cursor: pointer;
  letter-spacing: 0.01em;
  transition: transform 0.18s, box-shadow 0.18s;
  margin-top: 6px;
  display: block; text-align: center; text-decoration: none;
}
.btn-checkout:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(26,16,37,0.2);
  color: white;
}
.btn-clear {
  width: 100%;
  background: transparent; color: rgba(26,16,37,0.4);
  border: 1.5px solid rgba(26,16,37,0.1);
  padding: 12px;
  border-radius: 16px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px; font-weight: 600;
  cursor: pointer;
  margin-top: 10px;
  transition: all 0.18s;
  display: block; text-align: center; text-decoration: none;
}
.btn-clear:hover { border-color: var(--red); color: var(--red); }

.trust-row {
  display: flex; flex-direction: column; gap: 10px;
  margin-top: 22px;
  padding-top: 22px;
  border-top: 1px solid rgba(26,16,37,0.06);
}
.trust-item {
  display: flex; align-items: center; gap: 10px;
  font-size: 12px; color: rgba(26,16,37,0.45); font-weight: 500;
}
.trust-item span:first-child { font-size: 16px; }

@media(max-width:768px){
  .order-summary { position: static; margin-top: 24px; }
}
</style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<nav class="navbar">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="index.php" class="logo-mark">
      <span class="logo-dot"></span>
      PRINTOFY
    </a>
    <a href="product.php" class="back-btn">← Back to Shop</a>
  </div>
</nav>

<div class="cart-page">
  <div class="container">

    <div class="mb-4">
      <p class="page-label">Review & Checkout</p>
      <h1 class="page-title">Your <em>cart</em>.</h1>

      <?php if ($checkout_msg !== ""): ?>
        <div class="alert alert-success mt-3 mb-0"><?= htmlspecialchars($checkout_msg) ?></div>
      <?php endif; ?>

      <?php if (!empty($_SESSION["promo_code"])): ?>
        <div class="alert alert-info mt-3 mb-0">
          promo applied: <b><?= htmlspecialchars($_SESSION["promo_code"]) ?></b>
        </div>
      <?php endif; ?>
    </div>

    <div class="row g-4 align-items-start">

      <div class="col-lg-7">
        <div class="cart-panel">

          <?php if (!isset($_SESSION["user_id"])): ?>
            <div class="empty-state">
              <span class="empty-icon">🔒</span>
              <h3>Please login</h3>
              <p>You need to login to view your cart.</p>
              <a href="login.html" class="btn-shop">Login →</a>
            </div>

          <?php elseif (count($cart_items) === 0): ?>
            <div class="empty-state">
              <span class="empty-icon">🛍️</span>
              <h3>Your cart is empty</h3>
              <p>go add something.</p>
              <a href="product.php" class="btn-shop">Browse Products →</a>
            </div>

          <?php else: ?>
            <ul class="cart-items-list">
              <?php foreach ($cart_items as $i => $item): ?>
                <li class="cart-item" style="animation-delay:<?= $i * 0.07 ?>s">
                  <div class="item-left">
                    <div class="item-emoji">🎨</div>
                    <div>
                      <div class="item-name"><?= htmlspecialchars($item["product_name"]) ?></div>
                      <div class="item-sub">Custom print · 1 item</div>
                    </div>
                  </div>
                  <div class="item-right">
                    <span class="item-price">₹<?= (int)$item["price"] ?></span>
                    <form method="POST" action="cart.php?action=remove" style="margin:0;">
                      <input type="hidden" name="id" value="<?= (int)$item["id"] ?>">
                      <button class="remove-btn" title="Remove">✕</button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

        </div>
      </div>

      <div class="col-lg-5">
        <div class="order-summary">
          <h3 class="summary-title">Order Summary</h3>

          <div class="summary-row">
            <span>Subtotal (<?= count($cart_items) ?> items)</span>
            <span>₹<?= $subtotal ?></span>
          </div>
          <div class="summary-row">
            <span>Delivery</span>
            <span style="color:var(--blue);font-weight:600;">FREE</span>
          </div>
          <div class="summary-row">
            <span>Discount</span>
            <span style="color:var(--blue);font-weight:600;">
              <?= $discount > 0 ? "−₹".$discount : "—" ?>
            </span>
          </div>
          <div class="summary-row total">
            <span>Total</span>
            <span>₹<?= $final_total ?></span>
          </div>

          <form class="promo-box" method="POST" action="cart.php?action=promo">
            <input class="promo-input" type="text" name="promo_code"
                   value="<?= htmlspecialchars($_SESSION["promo_code"] ?? "") ?>"
                   placeholder="Promo code (try VIBE10)">
            <button class="promo-apply" type="submit">Apply</button>
          </form>

          <?php if (!empty($_SESSION["promo_code"])): ?>
            <a href="cart.php?action=promo_clear"
               style="display:inline-block;margin-top:8px;font-size:12px;color:var(--red);text-decoration:none;font-weight:600;">
              remove promo ✕
            </a>
          <?php endif; ?>

          <?php if (!empty($_SESSION["promo_error"])): ?>
            <div class="alert alert-danger mt-3 mb-0">
              <?= htmlspecialchars($_SESSION["promo_error"]) ?>
            </div>
            <?php unset($_SESSION["promo_error"]); ?>
          <?php endif; ?>

          <a class="btn-checkout" href="cart.php?action=checkout">Proceed to Checkout →</a>
          <a class="btn-clear" href="cart.php?action=clear">🗑️ Clear Cart</a>

          <div class="trust-row">
            <div class="trust-item"><span>🔒</span> Secure encrypted checkout</div>
            <div class="trust-item"><span>🚚</span> Free delivery on all orders</div>
            <div class="trust-item"><span>✅</span> 100% satisfaction guarantee</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
