<?php
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    if ($name === "" || $email === "" || $password === "" || $confirmPassword === "") {
        echo "<script>alert('all fields are required'); history.back();</script>";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('invalid email format'); history.back();</script>";
        exit();
    }

    if ($password !== $confirmPassword) {
        echo "<script>alert('passwords do not match'); history.back();</script>";
        exit();
    }

    $hasLength = strlen($password) >= 6;
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasDigit = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

    if (!($hasLength && $hasUpper && $hasLower && $hasDigit && $hasSpecial)) {
        echo "<script>alert('password must have minimum 6 characters, one capital letter, one lower case letter, one digit and one special character'); history.back();</script>";
        exit();
    }

    $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($checkStmt, "s", $email);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if (mysqli_num_rows($checkResult) > 0) {
        echo "<script>alert('email already registered'); history.back();</script>";
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insertStmt, "sss", $name, $email, $hashedPassword);

    if (mysqli_stmt_execute($insertStmt)) {
        echo "<script>
                alert('signup successful. please login');
                window.location.href = 'login.html';
              </script>";
        exit();
    } else {
        echo "<script>alert('registration failed'); history.back();</script>";
        exit();
    }
}
?>
