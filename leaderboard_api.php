<?php
header("Content-Type: application/json; charset=utf-8");

$conn = new mysqli("localhost", "root", "", "ecotrack");

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");

$sql = "
    SELECT u.user_id,
           COALESCE(CONCAT(i.name, ' ', i.last_name), c.company_name) AS display_name,
           u.total_point
    FROM users u
    LEFT JOIN individual i ON u.user_id = i.user_id
    LEFT JOIN company c ON u.user_id = c.user_id
    ORDER BY u.total_point DESC
    LIMIT 20
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Query error: " . $conn->error]);
    exit;
}

$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = [
        "display_name" => $row["display_name"],
        "total_point" => (int)$row["total_point"]
    ];
}

$conn->close();

echo json_encode(["leaderboard" => $rows]);
?>