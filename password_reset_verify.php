<?php
$conn = new mysqli("localhost", "root", "", "printofy_db");
if ($conn->connect_error) { die("DB failed: " . $conn->connect_error); }

$email = trim($_POST["email"] ?? "");
$code  = trim($_POST["code"] ?? "");
$new_password = $_POST["new_password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";

if ($email === "" || $code === "" || $new_password === "" || $confirm_password === "") {
  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("all fields required"));
  exit();
}
if ($new_password !== $confirm_password) {
  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("passwords do not match"));
  exit();
}
if (strlen($new_password) < 6) {
  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("password min 6 chars"));
  exit();
}
if (!preg_match('/^\d{6}$/', $code)) {
  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("code must be 6 digits"));
  exit();
}

/* find user */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("invalid email or code"));
  exit();
}

$user_id = (int)$user["id"];

/* latest unused reset */
$q = $conn->prepare("
  SELECT id, code_hash, expires_at
  FROM password_resets
  WHERE user_id = ? AND used = 0
  ORDER BY id DESC
  LIMIT 1
");
$q->bind_param("i", $user_id);
$q->execute();
$reset = $q->get_result()->fetch_assoc();

if (!$reset) {
  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("no active reset. send code again"));
  exit();
}

/* expiry */
$now = new DateTime("now");
$exp = new DateTime($reset["expires_at"]);
if ($now > $exp) {
  $rid = (int)$reset["id"];
  $u = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
  $u->bind_param("i", $rid);
  $u->execute();

  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("code expired. send again"));
  exit();
}

/* verify */
if (!password_verify($code, $reset["code_hash"])) {
  header("Location: forgot_reset.html?email=" . urlencode($email) . "&msg=" . urlencode("invalid code"));
  exit();
}

/* mark used */
$rid = (int)$reset["id"];
$u = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
$u->bind_param("i", $rid);
$u->execute();

/* update password */
$hash = password_hash($new_password, PASSWORD_DEFAULT);
$up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$up->bind_param("si", $hash, $user_id);
$up->execute();

/* done */
header("Location: forgot_reset.html?ok=1&email=" . urlencode($email));
exit();
