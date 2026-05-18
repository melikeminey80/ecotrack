<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$conn = new mysqli("localhost", "root", "", "ecotrack");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

$conn->set_charset("utf8mb4");

$user_id = $_SESSION["user_id"] ?? 3;

$sql = "
SELECT 
    wt.type_name,
    d.amount,
    d.delivery_date,
    wp.point_name,
    p.point_amount
FROM deliveries d
JOIN waste_types wt ON d.waste_type_id = wt.waste_type_id
JOIN waste_points wp ON d.waste_point_id = wp.point_id
JOIN points p ON d.delivery_id = p.delivery_id
WHERE d.user_id = ?
ORDER BY d.delivery_id DESC
LIMIT 5
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$activities = [];

while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

echo json_encode([
    "success" => true,
    "activities" => $activities
]);

$conn->close();
?>