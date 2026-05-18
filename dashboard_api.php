<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$conn = new mysqli("localhost", "root", "", "ecotrack");

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$conn->set_charset("utf8mb4");

// Session varsa onu kullan, yoksa test için son kullanıcıyı kullan
$user_id = $_SESSION["user_id"] ?? null;

if ($user_id === null) {
    $last_user_sql = "SELECT user_id FROM users ORDER BY user_id DESC LIMIT 1";
    $last_user_result = $conn->query($last_user_sql);

    if (!$last_user_result || $last_user_result->num_rows === 0) {
        echo json_encode(["error" => "No user found"]);
        exit;
    }

    $last_user = $last_user_result->fetch_assoc();
    $user_id = $last_user["user_id"];
}

$sql = "
SELECT 
    u.user_id,
    COALESCE(CONCAT(i.name, ' ', i.last_name), c.company_name) AS display_name,
    u.total_point
FROM users u
LEFT JOIN individual i ON u.user_id = i.user_id
LEFT JOIN company c ON u.user_id = c.user_id
WHERE u.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "User not found"]);
    exit;
}

$user = $result->fetch_assoc();

$stats_sql = "
SELECT 
    COUNT(*) AS total_deliveries,
    IFNULL(SUM(amount), 0) AS total_kg
FROM deliveries
WHERE user_id = ?
";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$reward_sql = "
SELECT COUNT(*) AS rewards_redeemed
FROM users_rewards
WHERE user_id = ?
";

$reward_stmt = $conn->prepare($reward_sql);
$reward_stmt->bind_param("i", $user_id);
$reward_stmt->execute();
$reward_stats = $reward_stmt->get_result()->fetch_assoc();

$tree_sql = "
SELECT COUNT(*) AS trees_donated
FROM users_rewards ur
JOIN rewards r ON ur.reward_id = r.reward_id
WHERE ur.user_id = ?
AND r.reward_name = 'Tree Donation'
";

$tree_stmt = $conn->prepare($tree_sql);
$tree_stmt->bind_param("i", $user_id);
$tree_stmt->execute();
$tree_stats = $tree_stmt->get_result()->fetch_assoc();

echo json_encode([
    "name" => $user["display_name"],
    "total_point" => (int)$user["total_point"],
    "total_deliveries" => (int)$stats["total_deliveries"],
    "total_kg" => (float)$stats["total_kg"],
    "rewards_redeemed" => (int)$reward_stats["rewards_redeemed"],
    "trees_donated" => (int)$tree_stats["trees_donated"]
]);

$conn->close();
?>