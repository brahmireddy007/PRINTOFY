<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "printofy_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("database connection failed");
}
?>
