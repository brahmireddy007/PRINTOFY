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

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$message = "";
$message_type = "";

/* ================= HELPER ================= */
function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* ================= FETCH CURRENT USER ================= */
$currentUserName = "User";

$userStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
if ($userRow = $userResult->fetch_assoc()) {
    $currentUserName = $userRow['name'];
}
$userStmt->close();

/* ================= ADD REVIEW ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add_review") {
    $product_id  = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : 0;
    $rating      = isset($_POST["rating"]) ? (int)$_POST["rating"] : 0;
    $review_text = trim($_POST["review_text"] ?? "");

    if ($product_id <= 0 || $rating < 1 || $rating > 5 || $review_text === "") {
        $message = "please fill all fields properly.";
        $message_type = "error";
    } else {
        $checkProduct = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
        $checkProduct->bind_param("i", $product_id);
        $checkProduct->execute();
        $checkProductResult = $checkProduct->get_result();

        if ($checkProductResult->num_rows === 0) {
            $message = "selected product does not exist.";
            $message_type = "error";
        } else {
            $insertStmt = $conn->prepare("
                INSERT INTO product_review (product_id, user_id, rating, review_text)
                VALUES (?, ?, ?, ?)
            ");
            $insertStmt->bind_param("iiis", $product_id, $user_id, $rating, $review_text);

            if ($insertStmt->execute()) {
                $message = "review posted successfully.";
                $message_type = "success";
            } else {
                $message = "failed to post review.";
                $message_type = "error";
            }
            $insertStmt->close();
        }
        $checkProduct->close();
    }
}

/* ================= DELETE MY REVIEWS ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_my_reviews") {
    $deleteStmt = $conn->prepare("DELETE FROM product_review WHERE user_id = ?");
    $deleteStmt->bind_param("i", $user_id);

    if ($deleteStmt->execute()) {
        $message = "your reviews were cleared.";
        $message_type = "success";
    } else {
        $message = "failed to clear your reviews.";
        $message_type = "error";
    }

    $deleteStmt->close();
}

/* ================= FETCH PRODUCTS ================= */
$products = [];
$productQuery = $conn->query("SELECT product_id, product_name FROM products ORDER BY product_name ASC");
if ($productQuery) {
    while ($row = $productQuery->fetch_assoc()) {
        $products[] = $row;
    }
}

/* ================= FETCH REVIEWS ================= */
$reviews = [];
$reviewQuery = $conn->query("
    SELECT 
        pr.review_id,
        pr.rating,
        pr.review_text,
        pr.review_date,
        u.name AS user_name,
        p.product_name
    FROM product_review pr
    JOIN users u ON pr.user_id = u.id
    JOIN products p ON pr.product_id = p.product_id
    ORDER BY pr.review_date DESC
");

if ($reviewQuery) {
    while ($row = $reviewQuery->fetch_assoc()) {
        $reviews[] = $row;
    }
}

/* ================= STATS ================= */
$total_reviews = 0;
$avg_rating = "0.0";
$five_star_percentage = 0;

$statsQuery = $conn->query("
    SELECT 
        COUNT(*) AS total_reviews,
        ROUND(AVG(rating), 1) AS avg_rating,
        ROUND((SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) / COUNT(*)) * 100) AS five_star_percentage
    FROM product_review
");

if ($statsQuery && $statsRow = $statsQuery->fetch_assoc()) {
    $total_reviews = (int)($statsRow['total_reviews'] ?? 0);
    $avg_rating = $statsRow['avg_rating'] !== null ? $statsRow['avg_rating'] : "0.0";
    $five_star_percentage = $total_reviews > 0 ? (int)$statsRow['five_star_percentage'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Reviews — PRINTOFY</title>

<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,700;0,900;1,400;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root{
  --ink:#2A2A2A;
  --cream:#F9F5ED;

  --red:#902124;
  --blue:#8EB9FF;
  --wine:#4D0011;
  --blush:#C2858C;

  --lavender:var(--blue);
  --mint:rgba(142,185,255,0.35);
  --gold:var(--blush);

  --success:#1e8e5a;
  --error:#d93025;
}

*, *::before, *::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}

body{
  font-family:'DM Sans',sans-serif;
  color:var(--ink);
  overflow-x:hidden;
  background:
    radial-gradient(950px 650px at 14% 18%, rgba(142,185,255,0.90), transparent 60%),
    radial-gradient(980px 700px at 88% 22%, rgba(194,133,140,0.82), transparent 62%),
    radial-gradient(1050px 750px at 55% 95%, rgba(144,33,36,0.65), transparent 60%),
    linear-gradient(135deg, rgba(77,0,17,0.60) 0%, rgba(249,245,237,1) 48%, rgba(142,185,255,0.55) 100%);
  background-attachment:fixed;
}

body::before{
  content:'';
  position:fixed;
  inset:0;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.035'/%3E%3C/svg%3E");
  pointer-events:none;
  z-index:9999;
}

/* navbar */
.navbar{
  position:sticky;
  top:0;
  z-index:1000;
  background:rgba(249,245,237,0.78);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border-bottom:1px solid rgba(26,16,37,0.07);
}

.nav-container{
  max-width:1280px;
  margin:0 auto;
  padding:14px 32px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  position:relative;
}

.logo-mark{
  font-family:'Fraunces',serif;
  font-weight:900;
  font-size:22px;
  letter-spacing:-0.5px;
  color:var(--ink);
  text-decoration:none;
  display:flex;
  align-items:center;
  gap:8px;
}

.logo-dot{
  width:10px;height:10px;border-radius:50%;
  background:linear-gradient(135deg,var(--blue),var(--red));
  display:inline-block;
}

.nav-menu{
  display:flex;
  align-items:center;
  gap:18px;
}

.nav-link{
  font-weight:500;
  font-size:14px;
  color:var(--ink);
  opacity:0.72;
  transition:.2s;
  text-decoration:none;
}

.nav-link:hover{opacity:1}

.cart-link{
  display:flex;
  align-items:center;
  gap:6px;
  font-weight:500;
  font-size:14px;
  color:var(--ink);
  text-decoration:none;
  opacity:.9;
}

.cart-badge{
  background:var(--red);
  color:#fff;
  width:18px;height:18px;border-radius:50%;
  font-size:10px;font-weight:700;
  display:flex;align-items:center;justify-content:center;
}

.btn-login{
  background:linear-gradient(135deg,var(--wine),var(--red));
  color:var(--cream);
  font-size:13px;
  font-weight:600;
  padding:8px 22px;
  border-radius:100px;
  text-decoration:none;
  transition:.2s;
}

.btn-login:hover{
  background:linear-gradient(135deg,var(--red),var(--wine));
  transform:translateY(-1px);
}

.nav-toggle{
  display:none;
  font-size:22px;
  background:#fff;
  border:1.5px solid rgba(26,16,37,0.1);
  border-radius:14px;
  padding:6px 12px;
  cursor:pointer;
}

/* hero */
.hero{
  position:relative;
  overflow:hidden;
  background:transparent;
  padding:110px 30px 90px;
  text-align:center;
}

.hero::before,
.hero::after{
  content:'';
  position:absolute;
  border-radius:50%;
  filter:blur(95px);
  opacity:.22;
}

.hero::before{
  width:400px;height:400px;
  background:rgba(142,185,255,0.95);
  top:-140px;right:-80px;
}

.hero::after{
  width:310px;height:310px;
  background:rgba(194,133,140,0.85);
  left:-70px;bottom:-120px;
}

.hero-inner{
  max-width:860px;
  margin:0 auto;
  position:relative;
  z-index:1;
}

.hero-eyebrow{
  display:inline-block;
  margin-bottom:16px;
  font-size:11px;
  font-weight:700;
  letter-spacing:.14em;
  text-transform:uppercase;
  color:var(--blue);
}

.hero h1{
  font-family:'Fraunces',serif;
  font-size:clamp(50px,6vw,82px);
  font-weight:900;
  color:var(--ink);
  line-height:1.08;
  letter-spacing:-1.2px;
  max-width:760px;
  margin:0 auto;
}

.hero h1 em{
  color:var(--red);
  font-style:italic;
}

.hero p{
  margin-top:22px;
  color:rgba(26,16,37,0.58);
  font-size:15px;
  line-height:1.7;
  max-width:560px;
  margin-left:auto;
  margin-right:auto;
}

/* stats */
.review-stats{
  max-width:1200px;
  margin:-42px auto 48px;
  padding:0 30px;
  position:relative;
  z-index:2;
}

.stats-card{
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,0.45);
  border-radius:26px;
  padding:24px 28px;
  box-shadow:0 14px 38px rgba(26,16,37,0.08);
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:18px;
}

.stat-box{
  text-align:center;
  padding:12px 10px;
}

.stat-box h3{
  font-family:'Fraunces',serif;
  font-size:30px;
  margin-bottom:8px;
  color:var(--wine);
}

.stat-box p{
  font-size:13px;
  color:rgba(26,16,37,0.5);
  line-height:1.5;
}

/* alerts */
.alert-wrap{
  max-width:920px;
  margin:16px auto 0;
  padding:0 30px;
}

.alert{
  border-radius:16px;
  padding:14px 18px;
  font-size:14px;
  font-weight:600;
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
}

.alert.success{
  color:var(--success);
  border:1px solid rgba(30,142,90,0.18);
}

.alert.error{
  color:var(--error);
  border:1px solid rgba(217,48,37,0.15);
}

/* form */
.review-form-wrap{
  max-width:920px;
  margin:72px auto 56px;
  padding:0 30px;
}

.review-form-card{
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,0.45);
  border-radius:26px;
  padding:30px;
  box-shadow:0 12px 35px rgba(26,16,37,0.06);
}

.review-form-card h2{
  font-family:'Fraunces',serif;
  font-size:32px;
  margin-bottom:12px;
  line-height:1.15;
}

.review-form-card p{
  font-size:14px;
  color:rgba(26,16,37,0.55);
  margin-bottom:24px;
  line-height:1.7;
}

.review-form{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
}

.form-group{
  display:flex;
  flex-direction:column;
  gap:9px;
}

.form-group.full{
  grid-column:1/-1;
}

.form-group label{
  font-size:13px;
  font-weight:600;
}

.form-group input,
.form-group textarea,
.form-group select{
  width:100%;
  padding:13px 15px;
  border:1px solid rgba(26,16,37,0.12);
  border-radius:14px;
  font-family:'DM Sans',sans-serif;
  font-size:14px;
  outline:none;
  transition:.2s;
  background:rgba(255,255,255,0.95);
  color:var(--ink);
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus{
  border-color:var(--blue);
  box-shadow:0 0 0 4px rgba(142,185,255,0.18);
}

.form-group textarea{
  min-height:130px;
  resize:vertical;
  line-height:1.6;
}

.rating-row{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

.star-option{
  flex:1;
  min-width:70px;
}

.star-option input{
  display:none;
}

.star-option label{
  display:block;
  text-align:center;
  padding:11px 12px;
  border-radius:14px;
  border:1px solid rgba(26,16,37,0.12);
  cursor:pointer;
  background:rgba(255,255,255,0.95);
  transition:.2s;
  font-size:14px;
}

.star-option input:checked + label{
  background:linear-gradient(135deg,var(--wine),var(--red));
  color:#fff;
  border-color:transparent;
}

.submit-row{
  grid-column:1/-1;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  flex-wrap:wrap;
  margin-top:10px;
}

.form-note{
  font-size:12px;
  color:rgba(26,16,37,0.45);
  line-height:1.6;
}

.submit-btn{
  background:linear-gradient(135deg,var(--wine),var(--red));
  color:#fff;
  border:none;
  padding:12px 24px;
  border-radius:100px;
  font-size:14px;
  font-weight:700;
  cursor:pointer;
  transition:.2s;
}

.submit-btn:hover{
  background:linear-gradient(135deg,var(--red),var(--wine));
  transform:translateY(-2px);
}

/* reviews */
.reviews-wrap{
  max-width:1200px;
  margin:auto;
  padding:64px 30px 95px;
}

.review-topbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  margin-bottom:28px;
  flex-wrap:wrap;
}

.review-topbar h3{
  font-family:'Fraunces',serif;
  font-size:32px;
  line-height:1.2;
}

.clear-btn{
  background:rgba(255,255,255,0.78);
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
  border:1px solid rgba(26,16,37,0.12);
  padding:10px 16px;
  border-radius:100px;
  font-size:13px;
  font-weight:600;
  cursor:pointer;
  color:rgba(26,16,37,0.65);
  transition:.2s;
}

.clear-btn:hover{
  border-color:var(--red);
  color:var(--red);
}

.review-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
  gap:24px;
}

.review-card{
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,0.45);
  border-radius:22px;
  padding:28px;
  box-shadow:0 10px 30px rgba(26,16,37,0.05);
  transition:.25s;
  display:flex;
  flex-direction:column;
  gap:10px;
}

.review-card:hover{
  transform:translateY(-6px);
  box-shadow:0 20px 45px rgba(26,16,37,0.08);
}

.stars{
  color:var(--gold);
  font-size:14px;
  margin-bottom:2px;
  letter-spacing:1px;
}

.review-text{
  font-size:14px;
  line-height:1.75;
  color:rgba(26,16,37,0.75);
  margin-bottom:4px;
}

.review-user{
  font-weight:600;
  font-size:14px;
  margin-top:2px;
  color:var(--wine);
}

.review-product{
  font-size:12px;
  color:rgba(26,16,37,0.5);
  margin-top:2px;
}

.review-badge{
  display:inline-block;
  margin-top:12px;
  background:rgba(142,185,255,0.18);
  color:var(--ink);
  font-size:11px;
  font-weight:700;
  padding:5px 10px;
  border-radius:100px;
  width:fit-content;
}

.empty-msg{
  grid-column:1/-1;
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,0.45);
  padding:42px;
  text-align:center;
  border-radius:20px;
  font-size:15px;
  color:rgba(26,16,37,0.5);
  box-shadow:0 10px 30px rgba(0,0,0,0.04);
  line-height:1.7;
}

/* footer */
.footer-wrap{
  margin-top:80px;
}

footer{
  max-width:1280px;
  margin:0 auto;
  padding:40px 32px;
  border-top:1px solid rgba(26,16,37,0.08);

  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-wrap:wrap;
  gap:14px;
}

.footer-logo{
  font-family:'Fraunces',serif;
  font-size:20px;
  font-weight:900;
  color:var(--ink);
  letter-spacing:-0.3px;
}

.footer-copy{
  font-size:13px;
  color:rgba(26,16,37,0.45);
}

.footer-right{
  display:flex;
  gap:16px;
  align-items:center;
}

@media(max-width:900px){
  .stats-card{
    grid-template-columns:1fr;
  }
}

@media(max-width:768px){
  .nav-toggle{display:block}

  .nav-menu{
    position:absolute;
    top:64px;
    left:16px;
    right:16px;
    background:rgba(249,245,237,0.95);
    backdrop-filter:blur(18px);
    border:1px solid rgba(26,16,37,0.08);
    border-radius:18px;
    padding:18px;
    display:none;
    flex-direction:column;
    align-items:flex-start;
    gap:14px;
    box-shadow:0 20px 50px rgba(0,0,0,0.08);
  }

  .nav-menu.open{display:flex}

  .btn-login{width:100%;text-align:center}

  .review-form{
    grid-template-columns:1fr;
  }

  .submit-row{
    align-items:flex-start;
  }

  .hero{
    padding:95px 22px 82px;
  }

  .review-stats,
  .review-form-wrap,
  .reviews-wrap,
  .alert-wrap{
    padding-left:20px;
    padding-right:20px;
  }
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="logo-mark" href="index.php">
      <span class="logo-dot"></span>
      PRINTOFY
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navMenu">
      <ul class="navbar-nav align-items-center gap-1">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="product.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="customize.php">Customize</a></li>
        <li class="nav-item"><a class="nav-link" href="reviews.php" style="opacity:1;font-weight:700;">Review</a></li>
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

<section class="hero">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>

  <div class="container">
    <div class="hero-inner reveal visible">
      <div class="pill-tag">✦ <span>real talk</span> — customer reviews that passed the vibe check</div>
      <h1>
        what people <em>printed.</em>
      </h1>
      <p class="hero-sub">
        real customers. real designs. real opinions. tell the world whether your custom print absolutely ate or needed a reprint arc.
      </p>
    </div>
  </div>
</section>

<section class="review-stats">
  <div class="container">
    <div class="stats-card reveal visible">
      <div class="stat-box">
        <h3><?php echo safe($avg_rating); ?>★</h3>
        <p>average customer rating</p>
      </div>
      <div class="stat-box">
        <h3><?php echo safe($total_reviews); ?>+</h3>
        <p>reviews posted by customers</p>
      </div>
      <div class="stat-box">
        <h3><?php echo safe($five_star_percentage); ?>%</h3>
        <p>five star reviews</p>
      </div>
    </div>
  </div>
</section>

<?php if ($message !== ""): ?>
<section class="alert-wrap">
  <div class="container">
    <div class="alert-box <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
      <?php echo safe($message); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="review-form-wrap">
  <div class="container">
    <div class="review-form-card reveal reveal-delay-1">
      <h2>drop your review.</h2>
      <p>go on. be honest. did your product slay, serve, sparkle... or betray you under bad lighting?</p>

      <form class="review-form" method="POST" action="">
        <input type="hidden" name="action" value="add_review">

        <div class="form-group">
          <label>your name</label>
          <input type="text" value="<?php echo safe($currentUserName); ?>" readonly>
        </div>

        <div class="form-group">
          <label for="product_id">product</label>
          <select id="product_id" name="product_id" required>
            <option value="">select a product</option>
            <?php foreach ($products as $product): ?>
              <option value="<?php echo (int)$product['product_id']; ?>">
                <?php echo safe($product['product_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group full">
          <label>rating</label>
          <div class="rating-row">
            <div class="star-option">
              <input type="radio" name="rating" id="star5" value="5" checked>
              <label for="star5">5 ★</label>
            </div>
            <div class="star-option">
              <input type="radio" name="rating" id="star4" value="4">
              <label for="star4">4 ★</label>
            </div>
            <div class="star-option">
              <input type="radio" name="rating" id="star3" value="3">
              <label for="star3">3 ★</label>
            </div>
            <div class="star-option">
              <input type="radio" name="rating" id="star2" value="2">
              <label for="star2">2 ★</label>
            </div>
            <div class="star-option">
              <input type="radio" name="rating" id="star1" value="1">
              <label for="star1">1 ★</label>
            </div>
          </div>
        </div>

        <div class="form-group full">
          <label for="review_text">your review</label>
          <textarea id="review_text" name="review_text" required placeholder="spill the tea about the product..."></textarea>
        </div>

        <div class="submit-row">
          <span class="form-note">reviews are saved in the database now, not trapped in one browser like a dusty secret.</span>
          <button type="submit" class="submit-btn">post review</button>
        </div>
      </form>
    </div>
  </div>
</section>

<section class="reviews-wrap">
  <div class="container">
    <div class="review-topbar reveal reveal-delay-1">
      <h3>customer love.</h3>

      <form method="POST" action="" onsubmit="return confirm('clear all reviews added by you?');">
        <input type="hidden" name="action" value="delete_my_reviews">
        <button type="submit" class="clear-btn">clear my added reviews</button>
      </form>
    </div>

    <div class="review-grid">
      <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $index => $review): ?>
          <div class="review-card reveal <?php echo $index % 3 === 0 ? 'reveal-delay-1' : ($index % 3 === 1 ? 'reveal-delay-2' : ''); ?>">
            <div class="stars">
              <?php echo str_repeat("★", (int)$review['rating']) . str_repeat("☆", 5 - (int)$review['rating']); ?>
            </div>

            <p class="review-text"><?php echo safe($review['review_text']); ?></p>
            <div class="review-user"><?php echo safe($review['user_name']); ?></div>
            <div class="review-product"><?php echo safe($review['product_name']); ?></div>
            <div class="review-badge"><?php echo date("d M Y", strtotime($review['review_date'])); ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-msg reveal visible">no reviews yet. be the dramatic first one.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="footer-wrap">
<footer>

<span class="footer-logo">PRINTOFY</span>

<span class="footer-copy">© 2026 PRINTOFY · print your vibe ✦</span>

<div class="footer-right">
<span class="footer-copy">Made with ❤️ in India</span>
</div>

</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function refreshCartCount() {
  try {
    const res = await fetch("cart.php?action=count", { credentials: "same-origin" });
    const count = await res.text();
    document.getElementById("cart-count").textContent = (count || "0").trim();
  } catch (e) {
    document.getElementById("cart-count").textContent = "0";
  }
}
refreshCartCount();

const revealEls = document.querySelectorAll(".reveal");
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add("visible");
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

revealEls.forEach(el => {
  if (!el.classList.contains("visible")) observer.observe(el);
});
</script>

</body>
</html>
