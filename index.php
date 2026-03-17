<?php
session_start();

$showPopup = false;
if (isset($_SESSION["show_festival_popup"]) && $_SESSION["show_festival_popup"] === true) {
    $showPopup = true;
    unset($_SESSION["show_festival_popup"]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PRINTOFY | Print Your Vibe</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,700;0,900;1,400;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
  --ink: #2A2A2A;
  --cream: #F9F5ED;
  --red: #902124;
  --blue: #8EB9FF;
  --wine: #4D0011;
  --blush: #C2858C;
  --lavender: var(--blue);
  --mint: rgba(142,185,255,0.35);
  --gold: var(--blush);
  --soft-white: rgba(255,255,255,0.92);
}

*, *::before, *::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

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
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.035'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 9999;
}

/* popup */
body.popup-open {
  overflow: hidden;
}

.popup-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.68);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 20000;
  padding: 20px;
}

.popup-overlay.show {
  display: flex;
}

.popup-box {
  width: min(1100px, 96vw);
  height: min(85vh, 800px);
  background: #fff;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
}

.popup-header {
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  padding: 14px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.popup-header h3 {
  margin: 0;
  font-family: 'Fraunces', serif;
  font-size: 22px;
  font-weight: 800;
}

.popup-btns {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.popup-btns button {
  border: none;
  border-radius: 999px;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.popup-tab {
  background: rgba(255,255,255,0.18);
  color: white;
}

.popup-tab.active {
  background: white;
  color: var(--wine);
}

.popup-close {
  background: white;
  color: var(--red);
}

.popup-frame {
  flex: 1;
  width: 100%;
  border: none;
  background: white;
}

/* navbar */
.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(249,245,237,0.78);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-bottom: 1px solid rgba(26,16,37,0.07);
  padding: 14px 0;
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

.nav-link {
  font-weight: 500;
  font-size: 14px;
  color: var(--ink) !important;
  opacity: 0.7;
  transition: opacity 0.2s;
  letter-spacing: 0.02em;
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
  color: var(--cream) !important;
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

.hero {
  min-height: 92vh;
  display: flex;
  align-items: center;
  position: relative;
  overflow: hidden;
}

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  opacity: 0.33;
  pointer-events: none;
}

.blob-1 {
  width: 600px;
  height: 600px;
  background: rgba(142,185,255,0.95);
  top: -180px;
  right: -160px;
  animation: drift 8s ease-in-out infinite alternate;
}

.blob-2 {
  width: 420px;
  height: 420px;
  background: rgba(194,133,140,0.85);
  bottom: -100px;
  left: -100px;
  animation: drift 10s ease-in-out infinite alternate-reverse;
}

.blob-3 {
  width: 300px;
  height: 300px;
  background: rgba(144,33,36,0.70);
  top: 40%;
  left: 38%;
  animation: drift 12s ease-in-out infinite alternate;
  opacity: 0.20;
}

@keyframes drift {
  from { transform: translate(0,0) scale(1); }
  to   { transform: translate(30px, 20px) scale(1.08); }
}

.hero-inner {
  position: relative;
  z-index: 2;
  max-width: 680px;
}

.pill-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: white;
  border: 1px solid rgba(26,16,37,0.1);
  padding: 6px 14px;
  border-radius: 100px;
  font-size: 12px;
  font-weight: 600;
  color: var(--ink);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 28px;
  opacity: 0;
  animation: fadeUp 0.6s 0.1s forwards;
}

.pill-tag span { color: var(--blue); }

.hero h1 {
  font-family: 'Fraunces', serif;
  font-weight: 900;
  font-size: clamp(52px, 8vw, 88px);
  line-height: 1.20;
  letter-spacing: -2px;
  color: var(--ink);
  opacity: 0;
  animation: fadeUp 0.7s 0.2s forwards;
}

.hero h1 em {
  font-style: italic;
  color: var(--blue);
}

.hero h1 .underline-word {
  position: relative;
  display: inline-block;
}

.hero h1 .underline-word::after {
  content: '';
  position: absolute;
  bottom: 2px;
  left: 0;
  width: 100%;
  height: 6px;
  background: #902124;
  border-radius: 3px;
  z-index: -1;
  transform: scaleX(0);
  transform-origin: left;
  animation: scaleIn 0.6s 0.9s forwards;
}

@keyframes scaleIn {
  to { transform: scaleX(1); }
}

.hero-sub {
  font-size: 17px;
  color: rgba(26,16,37,0.55);
  margin-top: 22px;
  line-height: 1.6;
  max-width: 440px;
  font-weight: 400;
  opacity: 0;
  animation: fadeUp 0.7s 0.4s forwards;
}

.hero-actions {
  display: flex;
  gap: 14px;
  margin-top: 36px;
  flex-wrap: wrap;
  opacity: 0;
  animation: fadeUp 0.7s 0.55s forwards;
}

.btn-primary-custom {
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  border: none;
  padding: 14px 32px;
  border-radius: 100px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.18s, box-shadow 0.18s;
  letter-spacing: 0.01em;
}

.btn-primary-custom:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(26,16,37,0.2);
}

.btn-ghost {
  background: transparent;
  color: var(--ink);
  border: 1.5px solid rgba(26,16,37,0.2);
  padding: 14px 32px;
  border-radius: 100px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: border-color 0.18s, transform 0.18s;
}

.btn-ghost:hover {
  border-color: var(--ink);
  transform: translateY(-2px);
}

.hero-float {
  position: absolute;
  right: 5%;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  display: flex;
  flex-direction: column;
  gap: 16px;
  opacity: 0;
  animation: fadeUp 0.8s 0.7s forwards;
}

.float-card {
  background: white;
  border-radius: 18px;
  padding: 16px 20px;
  box-shadow: 0 8px 32px rgba(26,16,37,0.1);
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 13px;
  font-weight: 600;
  min-width: 200px;
}

.float-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.float-card:nth-child(1) .float-icon { background: rgba(142,185,255,0.35); }
.float-card:nth-child(2) .float-icon { background: rgba(194,133,140,0.35); }
.float-card:nth-child(3) .float-icon { background: rgba(249,245,237,0.55); }

.float-card small {
  display: block;
  font-weight: 400;
  color: rgba(26,16,37,0.45);
  font-size: 11px;
}

.stats-band {
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  padding: 22px 0;
  overflow: hidden;
}

.stats-scroll {
  display: flex;
  gap: 60px;
  animation: marquee 18s linear infinite;
  width: max-content;
}

.stats-scroll span {
  font-family: 'Fraunces', serif;
  font-size: 15px;
  font-weight: 400;
  white-space: nowrap;
  opacity: 0.85;
}

.stats-scroll span b {
  color: var(--blue);
  font-weight: 700;
}

.stats-sep { color: rgba(255,255,255,0.6); }

@keyframes marquee {
  from { transform: translateX(0); }
  to   { transform: translateX(-50%); }
}

.products-section {
  padding: 100px 0 80px;
}

.section-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--blue);
  margin-bottom: 10px;
}

.section-title {
  font-family: 'Fraunces', serif;
  font-size: clamp(32px, 4vw, 48px);
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -1px;
}

.section-sub {
  font-size: 16px;
  color: rgba(26,16,37,0.5);
  margin-top: 10px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-top: 50px;
}

.product-card {
  background: white;
  border-radius: 24px;
  overflow: hidden;
  transition: transform 0.28s cubic-bezier(0.22,1,0.36,1), box-shadow 0.28s;
  position: relative;
}

.product-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 24px 60px rgba(26,16,37,0.12);
}

.product-img-wrap {
  height: 240px;
  overflow: hidden;
  position: relative;
}

.product-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s cubic-bezier(0.22,1,0.36,1);
}

.product-card:hover .product-img-wrap img {
  transform: scale(1.06);
}

.product-card:nth-child(1) .product-img-wrap {
  background: linear-gradient(135deg, rgba(142,185,255,0.75), rgba(194,133,140,0.55));
}

.product-card:nth-child(2) .product-img-wrap {
  background: linear-gradient(135deg, rgba(194,133,140,0.75), rgba(249,245,237,0.55));
}

.product-card:nth-child(3) .product-img-wrap {
  background: linear-gradient(135deg, rgba(144,33,36,0.75), rgba(142,185,255,0.45));
}

.product-tag {
  position: absolute;
  top: 14px;
  left: 14px;
  background: white;
  padding: 4px 10px;
  border-radius: 100px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--ink);
}

.product-body {
  padding: 20px 22px 24px;
}

.product-name {
  font-family: 'Fraunces', serif;
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.3px;
}

.product-desc {
  font-size: 13px;
  color: rgba(26,16,37,0.5);
  margin-top: 4px;
  line-height: 1.5;
}

.product-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 18px;
}

.product-price {
  font-family: 'Fraunces', serif;
  font-size: 24px;
  font-weight: 800;
  color: var(--ink);
}

.btn-cart {
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 100px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s, transform 0.15s;
  letter-spacing: 0.01em;
}

.btn-cart:hover {
  background: var(--blue);
  color: var(--wine);
  transform: scale(1.04);
}

.btn-cart.added {
  background: rgba(142,185,255,0.9);
  color: var(--ink);
}

.trust-strip {
  background: white;
  border-radius: 28px;
  padding: 40px;
  margin: 0 0 80px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 30px;
  text-align: center;
}

.trust-item .t-icon {
  font-size: 28px;
  margin-bottom: 10px;
  display: block;
}

.trust-item h5 {
  font-family: 'Fraunces', serif;
  font-size: 15px;
  font-weight: 700;
}

.trust-item p {
  font-size: 12px;
  color: rgba(26,16,37,0.45);
  margin-top: 3px;
}

.cta-section {
  background: linear-gradient(135deg, var(--wine) 0%, var(--red) 100%);
  border-radius: 32px;
  padding: 70px 50px;
  text-align: center;
  color: white;
  margin-bottom: 80px;
  position: relative;
  overflow: hidden;
}

.cta-section::before {
  content: '';
  position: absolute;
  width: 400px;
  height: 400px;
  background: rgba(142,185,255,0.7);
  border-radius: 50%;
  top: -180px;
  right: -80px;
  opacity: 0.15;
  filter: blur(60px);
}

.cta-section::after {
  content: '';
  position: absolute;
  width: 300px;
  height: 300px;
  background: rgba(194,133,140,0.7);
  border-radius: 50%;
  bottom: -120px;
  left: -60px;
  opacity: 0.15;
  filter: blur(60px);
}

.cta-section h2 {
  font-family: 'Fraunces', serif;
  font-size: clamp(28px, 4vw, 46px);
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: -1px;
  position: relative;
  z-index: 1;
}

.cta-section h2 em {
  color: var(--blue);
  font-style: italic;
}

.cta-section p {
  color: rgba(255,255,255,0.75);
  font-size: 16px;
  margin-top: 12px;
  position: relative;
  z-index: 1;
}

.cta-section .btn-primary-custom {
  background: var(--blue);
  color: var(--wine);
  margin-top: 28px;
  position: relative;
  z-index: 1;
  font-size: 16px;
  padding: 16px 38px;
}

.cta-section .btn-primary-custom:hover {
  box-shadow: 0 12px 30px rgba(142,185,255,0.35);
}

footer {
  padding: 40px 0;
  border-top: 1px solid rgba(26,16,37,0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.footer-logo {
  font-family: 'Fraunces', serif;
  font-size: 18px;
  font-weight: 800;
  color: var(--ink);
}

.footer-copy {
  font-size: 13px;
  color: rgba(26,16,37,0.4);
}

.toast-msg {
  position: fixed;
  bottom: 30px;
  right: 30px;
  background: linear-gradient(135deg, var(--wine), var(--red));
  color: white;
  padding: 14px 24px;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 600;
  z-index: 9000;
  transform: translateY(80px);
  opacity: 0;
  transition: all 0.35s cubic-bezier(0.22,1,0.36,1);
  pointer-events: none;
}

.toast-msg.show {
  transform: translateY(0);
  opacity: 1;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.7s cubic-bezier(0.22,1,0.36,1), transform 0.7s cubic-bezier(0.22,1,0.36,1);
}

.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}

.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }

@media(max-width: 768px) {
  .hero-float { display: none; }
  .hero { padding: 60px 0; min-height: auto; }
  .cta-section { padding: 50px 28px; }
  footer { flex-direction: column; gap: 12px; text-align: center; }

  .popup-box {
    width: 100%;
    height: 92vh;
    border-radius: 20px;
  }

  .popup-header h3 {
    font-size: 18px;
  }
}
</style>
</head>
<body class="<?php echo $showPopup ? 'popup-open' : ''; ?>">
<!-- popup -->
<div class="popup-overlay <?php echo $showPopup ? 'show' : ''; ?>" id="festivalPopup">
  <div class="popup-box">
    <div class="popup-header">
      <h3>special offers ✨</h3>
      <div class="popup-btns">
        <button class="popup-tab active" onclick="switchPopupPage('womensday.html', this)">women's day</button>
        <button class="popup-tab" onclick="switchPopupPage('holi.html', this)">holi</button>
        <button class="popup-close" onclick="closeFestivalPopup()">cancel</button>
      </div>
    </div>
    <iframe src="womensday.html" id="festivalFrame" class="popup-frame"></iframe>
  </div>
</div>

<!-- navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="logo-mark" href="#">
      <span class="logo-dot"></span>
      PRINTOFY
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
      <ul class="navbar-nav align-items-center gap-1">
        <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="product.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="customize.php">Customize</a></li>
        <li class="nav-item"><a class="nav-link" href="reviews.php">Review</a></li>
        <li class="nav-item ms-2">
          <a class="cart-link nav-link" href="cart.php">
            🛍️ Cart
            <span class="cart-badge" id="cart-count">0</span>
          </a>
        </li>
        <li class="nav-item ms-2">
          <a href="logout.php" class="btn-login">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- hero -->
<section class="hero">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>

  <div class="container">
    <div class="hero-inner">
      <div class="pill-tag">✦ <span>New</span> — Polaroid mini prints just dropped</div>
      <h1>
        print<br>
        your <i style="color:#902124;">vibe</i>,<br>
        <span class="underline-word">boldly.</span>
      </h1>
      <p class="hero-sub">
        Crafty custom prints made for bold creators. Upload a design, pick a product, and wear your story.
      </p>
      <div class="hero-actions">
        <button class="btn-primary-custom" onclick="scrollToProducts()">Explore Products</button>
        <button class="btn-ghost" onclick="scrollToProducts()">See How It Works →</button>
      </div>
    </div>
  </div>

  <div class="hero-float">
    <div class="float-card">
      <div class="float-icon">🎨</div>
      <div>
        <span>Upload any design</span>
        <small>PNG, JPG, SVG welcome</small>
      </div>
    </div>
    <div class="float-card">
      <div class="float-icon">📦</div>
      <div>
        <span>Ships in 3–5 days</span>
        <small>Pan-India delivery</small>
      </div>
    </div>
    <div class="float-card">
      <div class="float-icon">✨</div>
      <div>
        <span>1,200+ happy orders</span>
        <small>and counting!</small>
      </div>
    </div>
  </div>
</section>

<div class="stats-band">
  <div class="stats-scroll" id="statsScroll">
    <span>🎨 <b>1,200+</b> prints delivered</span>
    <span class="stats-sep">✦</span>
    <span>⭐ Rated <b>4.9/5</b> by customers</span>
    <span class="stats-sep">✦</span>
    <span>🚀 Ships in <b>3–5 days</b></span>
    <span class="stats-sep">✦</span>
    <span>💌 <b>100%</b> satisfaction guarantee</span>
    <span class="stats-sep">✦</span>
    <span>🎨 <b>1,200+</b> prints delivered</span>
    <span class="stats-sep">✦</span>
    <span>⭐ Rated <b>4.9/5</b> by customers</span>
    <span class="stats-sep">✦</span>
    <span>🚀 Ships in <b>3–5 days</b></span>
    <span class="stats-sep">✦</span>
    <span>💌 <b>100%</b> satisfaction guarantee</span>
    <span class="stats-sep">✦</span>
  </div>
</div>

<section class="products-section" id="products">
  <div class="container">
    <div class="reveal">
      <p class="section-label">Our Collection</p>
      <h2 class="section-title">Made for bold creators.</h2>
      <p class="section-sub">Design it. Print it. Flex it. Every piece is one-of-a-kind — just like you.</p>
    </div>

    <div class="product-grid">
      <div class="product-card reveal reveal-delay-1">
        <div class="product-img-wrap">
          <img src="image4.png" alt="Custom T-Shirt" onerror="this.style.display='none'">
          <span class="product-tag">🔥 Best Seller</span>
        </div>
        <div class="product-body">
          <h3 class="product-name">Custom T-Shirts</h3>
          <p class="product-desc">Your photo, your design — printed on premium cotton that feels like a hug.</p>
          <div class="product-footer">
            <span class="product-price">₹499</span>
            <button class="btn-cart" onclick="addToCart('Custom T-Shirts', 499, this)">+ Add to Cart</button>
          </div>
        </div>
      </div>

      <div class="product-card reveal reveal-delay-2">
        <div class="product-img-wrap">
          <img src="image3.jpg" alt="Polaroid Prints" onerror="this.style.display='none'">
          <span class="product-tag">✨ New Drop</span>
        </div>
        <div class="product-body">
          <h3 class="product-name">Polaroid Prints</h3>
          <p class="product-desc">Retro-style prints for your walls, memories, and that vintage aesthetic.</p>
          <div class="product-footer">
            <span class="product-price">₹199</span>
            <button class="btn-cart" onclick="addToCart('Polaroid Prints', 199, this)">+ Add to Cart</button>
          </div>
        </div>
      </div>

      <div class="product-card reveal reveal-delay-3">
        <div class="product-img-wrap">
          <img src="image1.png" alt="Keychains" onerror="this.style.display='none'">
          <span class="product-tag">💫 Fan Fave</span>
        </div>
        <div class="product-body">
          <h3 class="product-name">Keychains</h3>
          <p class="product-desc">Carry your vibe everywhere. Custom printed keychains that spark joy.</p>
          <div class="product-footer">
            <span class="product-price">₹299</span>
            <button class="btn-cart" onclick="addToCart('Keychains', 299, this)">+ Add to Cart</button>
          </div>
        </div>
      </div>
    </div>

    <div class="trust-strip reveal" style="margin-top: 60px;">
      <div class="trust-item">
        <span class="t-icon">🎨</span>
        <h5>Easy Customization</h5>
        <p>Upload any image and we handle the rest</p>
      </div>
      <div class="trust-item">
        <span class="t-icon">📦</span>
        <h5>Fast Delivery</h5>
        <p>Ships pan-India within 3–5 business days</p>
      </div>
      <div class="trust-item">
        <span class="t-icon">💎</span>
        <h5>Premium Quality</h5>
        <p>Vivid colors that last wash after wash</p>
      </div>
      <div class="trust-item">
        <span class="t-icon">🤝</span>
        <h5>100% Guarantee</h5>
        <p>Love it or we'll reprint it, free</p>
      </div>
    </div>
  </div>
</section>

<div class="container">
  <div class="cta-section reveal">
    <h2>Ready to print<br>your <em>story</em>?</h2>
    <p>Join 1,200+ creators who've already made their mark.</p>
    <button class="btn-primary-custom" onclick="scrollToProducts()">Start Creating Now →</button>
  </div>
</div>

<div class="container">
  <footer>
    <span class="footer-logo">PRINTOFY</span>
    <span class="footer-copy">©️ 2026 PRINTOFY · print your vibe ✦</span>
    <span class="footer-copy">Made with ❤️ in India</span>
  </footer>
</div>

<div class="toast-msg" id="toast">✓ Added to cart!</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let cart = JSON.parse(localStorage.getItem("cart")) || [];
document.getElementById("cart-count").textContent = cart.length;

async function addToCart(name, price, btn) {
  try {
    const res = await fetch("cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ name, price })
    });

    const text = await res.text();

    if (!res.ok) {
      showToast(text || "failed to add");
      return;
    }

    const orig = btn.textContent;
    btn.textContent = "✓ Added!";
    btn.classList.add("added");

    setTimeout(() => {
      btn.textContent = orig;
      btn.classList.remove("added");
    }, 1800);

    await refreshCartCount();
    showToast(`✓ ${name} added to cart!`);
  } catch (e) {
    showToast("server error 💀 try again");
  }
}

async function refreshCartCount() {
  try {
    const res = await fetch("cart.php?action=count", { credentials: "same-origin" });
    const count = await res.text();
    document.getElementById("cart-count").textContent = count || "0";
  } catch (e) {
    document.getElementById("cart-count").textContent = "0";
  }
}

function showToast(msg) {
  const toast = document.getElementById("toast");
  toast.textContent = msg;
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 2600);
}

function scrollToProducts() {
  document.getElementById("products").scrollIntoView({ behavior: "smooth" });
}

const revealEls = document.querySelectorAll(".reveal");
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add("visible");
      observer.unobserve(e.target);
    }
  });
}, { threshold: 0.12 });
revealEls.forEach(el => observer.observe(el));

const festivalPopup = document.getElementById("festivalPopup");
const festivalFrame = document.getElementById("festivalFrame");

function switchPopupPage(page, btn) {
  festivalFrame.src = page;
  document.querySelectorAll(".popup-tab").forEach(tab => tab.classList.remove("active"));
  btn.classList.add("active");
}

function closeFestivalPopup() {
  festivalPopup.classList.remove("show");
  document.body.classList.remove("popup-open");
}

window.addEventListener("load", () => {
  refreshCartCount();

  if (festivalPopup.classList.contains("show")) {
    document.body.classList.add("popup-open");
  }
});
</script>

</body>
</html>
