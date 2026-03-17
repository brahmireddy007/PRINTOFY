<?php
session_start();
$_SESSION = [];
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logged Out | PRINTOFY</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
  --gold:var(--blush);
}

*{ box-sizing:border-box; }

body{
  margin:0;
  font-family:'DM Sans',sans-serif;
  color:var(--ink);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:90px 18px 40px;
  overflow-x:hidden;

  background:
    radial-gradient(950px 650px at 14% 18%, rgba(142,185,255,0.90), transparent 60%),
    radial-gradient(980px 700px at 88% 22%, rgba(194,133,140,0.82), transparent 62%),
    radial-gradient(1050px 750px at 55% 95%, rgba(144,33,36,0.65), transparent 60%),
    linear-gradient(135deg, rgba(77,0,17,0.60) 0%, rgba(249,245,237,1) 48%, rgba(142,185,255,0.55) 100%);
}

/* noise */
body::before{
  content:'';
  position:fixed;
  inset:0;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.035'/%3E%3C/svg%3E");
  pointer-events:none;
  z-index:9999;
}

/* blobs */
.blob{
  position:fixed;
  border-radius:50%;
  filter:blur(90px);
  pointer-events:none;
  z-index:0;
}

.blob-1{
  width:520px;
  height:520px;
  background:rgba(142,185,255,0.95);
  top:-220px;
  right:-170px;
  opacity:.22;
}

.blob-2{
  width:380px;
  height:380px;
  background:rgba(194,133,140,0.85);
  bottom:-140px;
  left:-120px;
  opacity:.16;
}

.blob-3{
  width:280px;
  height:280px;
  background:rgba(144,33,36,0.65);
  top:45%;
  left:40%;
  opacity:.12;
  filter:blur(110px);
}

/* navbar */
.navbar{
  position:fixed;
  top:0;left:0;right:0;
  z-index:100;
  background:rgba(249,245,237,0.78);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border-bottom:1px solid rgba(26,16,37,0.07);
  padding:14px 0;
}

.logo-mark{
  font-family:'Fraunces',serif;
  font-weight:900;
  font-size:20px;
  color:var(--ink);
  text-decoration:none;
  display:flex;
  align-items:center;
  gap:8px;
}

.logo-dot{
  width:9px;
  height:9px;
  border-radius:50%;
  background:linear-gradient(135deg,var(--blue),var(--red));
}

/* card */
.wrap{
  position:relative;
  z-index:1;
  width:min(520px,100%);
}

.cardx{
  background:rgba(255,255,255,0.72);
  border:1px solid rgba(26,16,37,0.08);
  border-radius:28px;
  padding:38px 32px;
  box-shadow:0 18px 55px rgba(26,16,37,0.10);
  backdrop-filter:blur(16px);
  text-align:center;
}

h1{
  font-family:'Fraunces',serif;
  font-weight:900;
  letter-spacing:-1px;
  font-size:34px;
  margin:0;
}

h1 em{
  color:var(--red);
  font-style:italic;
}

p{
  margin:14px auto 0;
  color:rgba(26,16,37,0.55);
  font-size:15px;
  line-height:1.6;
  max-width:420px;
}

/* buttons */

.btn-main{
  border:none;
  border-radius:16px;
  padding:12px 18px;
  font-weight:700;
  background:linear-gradient(135deg,var(--wine),var(--red));
  color:#fff;
  transition:transform .18s, box-shadow .18s, background .18s;
  text-decoration:none;
  display:inline-block;
  margin-top:22px;
}

.btn-main:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 30px rgba(26,16,37,0.18);
  background:linear-gradient(135deg,var(--red),var(--wine));
}

.btn-ghost{
  border:1.5px solid rgba(26,16,37,0.18);
  border-radius:16px;
  padding:11px 18px;
  font-weight:700;
  background:transparent;
  color:rgba(26,16,37,0.75);
  text-decoration:none;
  display:inline-block;
  margin-top:12px;
  transition:.18s;
}

.btn-ghost:hover{
  border-color:var(--ink);
  color:var(--ink);
}

.small{
  font-size:12px;
  margin-top:18px;
  color:rgba(26,16,37,0.45);
}
</style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<nav class="navbar">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="index.php" class="logo-mark">
      <span class="logo-dot"></span>
      PRINTOFY
    </a>
  </div>
</nav>

<div class="wrap">
  <div class="cardx">
    <h1>you’re <em>logged out</em>.</h1>
    <p>
      session ended. cart vibe cleared. go live your best life.
      (and come back when you feel like shopping again 😌)
    </p>

    <a class="btn-main" href="login.html">go to login →</a><br>
    <a class="btn-ghost" href="index.php">back to homepage</a>

    <div class="small">© 2026 printofy ✦</div>
  </div>
</div>

<script>
  // optional: clear local storage cart on logout page
  // so user doesn't come back with old cart items
  localStorage.removeItem("cart");
</script>

</body>
</html>
