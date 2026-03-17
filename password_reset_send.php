<?php
$conn = new mysqli("localhost", "root", "", "printofy_db");
if ($conn->connect_error) { die("DB failed: " . $conn->connect_error); }

$email = trim($_POST["email"] ?? "");
if ($email === "") {
  header("Location: forgot_reset.html?msg=" . urlencode("email required"));
  exit();
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* don't reveal if user exists */
if (!$user) {
  header("Location: forgot_reset.html?sent=1&email=" . urlencode($email));
  exit();
}

$user_id = (int)$user["id"];

$code = (string)random_int(100000, 999999);
$code_hash = password_hash($code, PASSWORD_DEFAULT);

$expires_at = (new DateTime("now"))->add(new DateInterval("PT10M"))->format("Y-m-d H:i:s");

/* invalidate previous */
$kill = $conn->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
$kill->bind_param("i", $user_id);
$kill->execute();

/* insert new */
$ins = $conn->prepare("INSERT INTO password_resets (user_id, code_hash, expires_at, used) VALUES (?, ?, ?, 0)");
$ins->bind_param("iss", $user_id, $code_hash, $expires_at);
$ins->execute();

/* email send (simple) */
$subject = "PRINTOFY Password Reset Code";
$message = "your PRINTOFY reset code is: $code\n\nexpires in 10 minutes.";
$headers = "From: no-reply@printofy.local\r\n";
echo "YOUR RESET CODE IS: " . $code;
exit();

header("Location: forgot_reset.html?sent=1&email=" . urlencode($email));
exit();
