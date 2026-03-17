<?php
require "db.php";

$sql = "
    SELECT 
        product_id,
        ROUND(AVG(rating), 1) AS avg_rating,
        COUNT(review_id) AS total_reviews
    FROM product_review
    GROUP BY product_id
";

$result = mysqli_query($conn, $sql);

$ratings = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ratings[$row['product_id']] = [
            "avg" => $row['avg_rating'],
            "count" => $row['total_reviews']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($ratings);
?>