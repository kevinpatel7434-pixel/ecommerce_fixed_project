<?php
header('Content-Type: application/json');

require '../db.php';

$response = [
    'revenue' => 0,
    'orders' => 0
];

$query = $conn->query(
    "SELECT SUM(total_amount) AS revenue, COUNT(*) AS orders FROM orders"
);

if($query){
    $data = $query->fetch_assoc();

    $response['revenue'] = $data['revenue'] ?? 0;
    $response['orders'] = $data['orders'] ?? 0;
}

echo json_encode($response);
?>
