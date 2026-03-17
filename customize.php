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

/* ================= DEFAULT PRODUCT ================= */
$product = $_GET['product'] ?? 'T-Shirt';

/* ================= PRODUCT PRICES ================= */
$product_prices = [
    "T-Shirt"    => 499,
    "Hoodie"     => 899,
    "Tote Bag"   => 349,
    "Mug"        => 299,
    "Phone Case" => 399,
    "Pillow"     => 449
];

/* ================= ADD TO CART ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.html");
        exit();
    }

    $selected_product = trim($_POST["selected_product"] ?? "T-Shirt");
    $custom_text      = trim($_POST["custom_text"] ?? "");
    $text_color       = trim($_POST["text_color"] ?? "#000000");

    if (!array_key_exists($selected_product, $product_prices)) {
        $selected_product = "T-Shirt";
    }

    $price = $product_prices[$selected_product];
    $user_id = (int)$_SESSION["user_id"];

    $uploaded_file_name = "";

    if (isset($_FILES["design_image"]) && $_FILES["design_image"]["error"] === 0) {
        $upload_dir = "uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $original_name = basename($_FILES["design_image"]["name"]);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "gif", "webp"];

        if (in_array($ext, $allowed)) {
            $new_name = time() . "_" . rand(1000, 9999) . "." . $ext;
            $target_file = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES["design_image"]["tmp_name"], $target_file)) {
                $uploaded_file_name = $new_name;
            }
        }
    }

    $product_name = $selected_product;

    if ($custom_text !== "") {
        $product_name .= " | Text: " . $custom_text;
    }

    if ($text_color !== "") {
        $product_name .= " | Color: " . $text_color;
    }

    if ($uploaded_file_name !== "") {
        $product_name .= " | Image: " . $uploaded_file_name;
    }

    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_name, price) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $user_id, $product_name, $price);
    $stmt->execute();

    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customize — PRINTOFY</title>

<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,700;0,900;1,400;1,700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
  transition:opacity .2s;
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
  padding:110px 30px 95px;
  text-align:center;
}

.hero::before,
.hero::after{
  content:'';
  position:absolute;
  border-radius:50%;
  filter:blur(95px);
  opacity:.18;
}

.hero::before{
  width:420px;height:420px;
  background:rgba(142,185,255,0.95);
  top:-150px;right:-80px;
}

.hero::after{
  width:320px;height:320px;
  background:rgba(194,133,140,0.85);
  left:-80px;bottom:-120px;
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
  font-size:clamp(52px,7vw,90px);
  font-weight:900;
  color:var(--ink);
  line-height:1.04;
  letter-spacing:-2px;
  max-width:900px;
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

/* products */
.products{
  max-width:1200px;
  margin:68px auto 0;
  padding:0 30px;
}

.section-label{
  font-size:11px;
  font-weight:700;
  letter-spacing:.14em;
  text-transform:uppercase;
  color:var(--blue);
  margin-bottom:12px;
}

.products h2{
  font-family:'Fraunces',serif;
  font-size:34px;
  margin-bottom:26px;
  line-height:1.15;
}

.product-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
  gap:20px;
}

.p-item{
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(16px);
  -webkit-backdrop-filter:blur(16px);
  border-radius:22px;
  padding:30px;
  text-align:center;
  cursor:pointer;
  transition:.25s;
  font-size:28px;
  border:2px solid transparent;
  box-shadow:0 10px 30px rgba(26,16,37,0.04);
}

.p-item span{
  display:block;
  font-size:14px;
  margin-top:10px;
}

.p-item:hover{
  transform:translateY(-6px);
  box-shadow:0 18px 40px rgba(26,16,37,0.08);
}

.p-item.active{
  border-color:var(--red);
  box-shadow:0 18px 40px rgba(26,16,37,0.09);
  background:rgba(255,255,255,0.94);
}

/* studio */
.studio{
  max-width:1200px;
  margin:0 auto;
  padding:58px 30px 90px;
  display:grid;
  grid-template-columns:320px 1fr;
  gap:34px;
}

.toolbar,
.preview{
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border-radius:28px;
  box-shadow:0 14px 38px rgba(26,16,37,0.06);
  border:1px solid rgba(255,255,255,0.45);
}

.toolbar{
  padding:24px;
  height:fit-content;
}

.toolbar h3{
  font-family:'Fraunces',serif;
  font-size:26px;
  margin-bottom:8px;
}

.toolbar-sub{
  font-size:14px;
  color:rgba(26,16,37,0.55);
  line-height:1.7;
  margin-bottom:22px;
}

.tool{
  margin-bottom:18px;
}

.tool label{
  font-size:13px;
  font-weight:600;
  display:block;
  margin-bottom:7px;
}

.tool input,
.tool textarea{
  width:100%;
  padding:12px 14px;
  border-radius:14px;
  border:1px solid rgba(26,16,37,0.11);
  font-family:'DM Sans',sans-serif;
  font-size:14px;
  outline:none;
  transition:.2s;
  background:rgba(255,255,255,0.95);
  color:var(--ink);
}

.tool input:focus,
.tool textarea:focus{
  border-color:var(--blue);
  box-shadow:0 0 0 4px rgba(142,185,255,0.18);
}

.selected-product{
  margin-top:8px;
  font-size:14px;
  color:rgba(26,16,37,0.72);
  line-height:1.6;
}

.price-tag{
  margin-top:10px;
  font-size:15px;
  font-weight:700;
  color:var(--red);
}

.btn-add{
  margin-top:22px;
  background:linear-gradient(135deg,var(--wine),var(--red));
  color:white;
  border:none;
  padding:13px 24px;
  border-radius:100px;
  cursor:pointer;
  width:100%;
  font-weight:700;
  font-size:14px;
  transition:.2s;
}

.btn-add:hover{
  background:linear-gradient(135deg,var(--red),var(--wine));
  transform:translateY(-2px);
}

.preview{
  min-height:500px;
  padding:40px;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  overflow:hidden;
}

.preview::before{
  content:'';
  position:absolute;
  inset:22px;
  border-radius:24px;
  background:linear-gradient(135deg, rgba(142,185,255,0.16), rgba(194,133,140,0.14));
}

.product-mock{
  width:320px;
  height:380px;
  background:linear-gradient(145deg,#f6f2ec,#e8e3dc);
  border-radius:24px;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  overflow:hidden;
  z-index:1;
  box-shadow:0 20px 50px rgba(26,16,37,0.08);
}

.design-image{
  max-width:80%;
  max-height:58%;
  position:absolute;
  display:none;
  object-fit:contain;
}

.design-text{
  position:absolute;
  bottom:28px;
  font-weight:700;
  font-size:21px;
  text-align:center;
  padding:0 12px;
  line-height:1.3;
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
  .studio{
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

  .hero{
    padding:95px 22px 82px;
  }

  .products,
  .studio{
    padding-left:20px;
    padding-right:20px;
  }

  .preview{
    min-height:420px;
  }

  .product-mock{
    width:280px;
    height:340px;
  }
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

    <button class="nav-toggle" type="button" aria-label="Toggle menu" onclick="toggleNav()">☰</button>

    <div class="nav-menu" id="navMenu">
      <a class="nav-link" href="index.php">Home</a>
      <a class="nav-link" href="product.php">Products</a>
      <a class="nav-link" href="customize.php" style="opacity:1;font-weight:600;">Customize</a>
      <a class="nav-link" href="reviews.php">Review</a>

      <a class="cart-link" href="cart.php">
        🛍️ Cart
        <span class="cart-badge" id="cart-count">0</span>
      </a>

      <a href="logout.php" class="btn-login">Logout</a>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="hero-inner">
    <span class="hero-eyebrow">✦ design studio</span>
    <h1>print your vibe <em>boldly.</em></h1>
    <p>upload your image, add your text, pick your product, and make something that actually feels like you.</p>
  </div>
</section>

<section class="products">
  <div class="section-label">our collection</div>
  <h2>choose the base for your custom print.</h2>

  <div class="product-grid">
    <div class="p-item <?= $product === 'T-Shirt' ? 'active' : '' ?>" data-product="T-Shirt" data-price="499">👕<span>T-Shirt</span></div>
    <div class="p-item <?= $product === 'Hoodie' ? 'active' : '' ?>" data-product="Hoodie" data-price="899">🧥<span>Hoodie</span></div>
    <div class="p-item <?= $product === 'Tote Bag' ? 'active' : '' ?>" data-product="Tote Bag" data-price="349">👜<span>Tote Bag</span></div>
    <div class="p-item <?= $product === 'Mug' ? 'active' : '' ?>" data-product="Mug" data-price="299">☕<span>Mug</span></div>
    <div class="p-item <?= $product === 'Phone Case' ? 'active' : '' ?>" data-product="Phone Case" data-price="399">📱<span>Phone Case</span></div>
    <div class="p-item <?= $product === 'Pillow' ? 'active' : '' ?>" data-product="Pillow" data-price="449">🛋<span>Pillow</span></div>
  </div>
</section>

<section class="studio">
  <form class="toolbar" method="POST" enctype="multipart/form-data">
    <h3>customize it.</h3>
    <p class="toolbar-sub">add your image, type your text, tweak the color, and send it straight to cart.</p>

    <input type="hidden" name="selected_product" id="selectedProduct" value="<?= htmlspecialchars($product) ?>">

    <div class="tool">
      <label>Upload Image</label>
      <input type="file" id="upload" name="design_image" accept="image/*">
    </div>

    <div class="tool">
      <label>Add Text</label>
      <textarea id="textInput" name="custom_text" rows="3"></textarea>
    </div>

    <div class="tool">
      <label>Text Color</label>
      <input type="color" id="textColor" name="text_color" value="#000000">
    </div>

    <div class="selected-product">
      selected: <span id="selectedProductText"><?= htmlspecialchars($product) ?></span>
    </div>

    <div class="price-tag">
      price: ₹<span id="selectedPrice"><?php echo $product_prices[$product] ?? 499; ?></span>
    </div>

    <button type="submit" class="btn-add">Add To Cart</button>
  </form>

  <div class="preview">
    <div class="product-mock">
      <img id="imgPreview" class="design-image" alt="Preview">
      <div id="textPreview" class="design-text"></div>
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

<script>
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
refreshCartCount();

const upload = document.getElementById("upload");
const imgPreview = document.getElementById("imgPreview");
const textInput = document.getElementById("textInput");
const textPreview = document.getElementById("textPreview");
const textColor = document.getElementById("textColor");
const productCards = document.querySelectorAll(".p-item");
const selectedProductInput = document.getElementById("selectedProduct");
const selectedProductText = document.getElementById("selectedProductText");
const selectedPrice = document.getElementById("selectedPrice");

upload.addEventListener("change", function(e){
  const file = e.target.files[0];
  if(file){
    const reader = new FileReader();
    reader.onload = function(ev){
      imgPreview.src = ev.target.result;
      imgPreview.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
});

textInput.addEventListener("input", function(){
  textPreview.textContent = textInput.value;
});

textColor.addEventListener("input", function(){
  textPreview.style.color = textColor.value;
});

productCards.forEach(card => {
  card.addEventListener("click", function(){
    productCards.forEach(c => c.classList.remove("active"));
    this.classList.add("active");

    const product = this.getAttribute("data-product");
    const price = this.getAttribute("data-price");

    selectedProductInput.value = product;
    selectedProductText.textContent = product;
    selectedPrice.textContent = price;
  });
});
</script>

</body>
</html>
