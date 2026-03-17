<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (empty($email) || empty($password)) {
        die("all fields required");
    }

    if (!isset($_POST["terms"])) {
        die("login blocked: please accept the terms and conditions before logging in.");
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["show_festival_popup"] = true;

            header("Location: index.php");
            exit();
        } else {
            die("wrong password");
        }
    } else {
        die("user not found");
    }
} else {
    header("Location: login.html");
    exit();
}
?>
