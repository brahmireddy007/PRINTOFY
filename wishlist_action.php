<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["wishlist"])) {
    $_SESSION["wishlist"] = [];
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    echo json_encode([
        "success" => true,
        "wishlist" => array_values($_SESSION["wishlist"]),
        "count" => count($_SESSION["wishlist"])
    ]);
    exit();
}

if ($method === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);

    $product_id = isset($input["product_id"]) ? (int)$input["product_id"] : 0;
    $action = $input["action"] ?? "";

    if ($product_id <= 0) {
        echo json_encode(["success" => false, "message" => "invalid product id"]);
        exit();
    }

    if ($action === "add") {
        if (!in_array($product_id, $_SESSION["wishlist"])) {
            $_SESSION["wishlist"][] = $product_id;
        }

        echo json_encode([
            "success" => true,
            "message" => "added to wishlist",
            "wishlist" => array_values($_SESSION["wishlist"]),
            "count" => count($_SESSION["wishlist"])
        ]);
        exit();
    }

    if ($action === "remove") {
        $_SESSION["wishlist"] = array_values(array_filter($_SESSION["wishlist"], function($id) use ($product_id) {
            return (int)$id !== $product_id;
        }));

        echo json_encode([
            "success" => true,
            "message" => "removed from wishlist",
            "wishlist" => array_values($_SESSION["wishlist"]),
            "count" => count($_SESSION["wishlist"])
        ]);
        exit();
    }

    echo json_encode(["success" => false, "message" => "invalid action"]);
    exit();
}

echo json_encode(["success" => false, "message" => "invalid request"]);
?>