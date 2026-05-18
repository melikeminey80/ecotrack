<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

$conn = new mysqli("localhost", "root", "", "ecotrack");

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);
    exit;
}

$conn->set_charset("utf8mb4");

// Test için
$user_id = $_SESSION["user_id"] ?? 3;

$reward_name = $_POST["reward_name"] ?? "";
$cost = $_POST["cost"] ?? 0;

if ($reward_name === "" || $cost <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid reward data."
    ]);
    exit;
}

$conn->begin_transaction();

try {

    // Kullanıcı puanı
    $user_sql = "SELECT total_point FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();

    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        throw new Exception("User not found.");
    }

    $user = $user_result->fetch_assoc();

    if ($user["total_point"] < $cost) {
        throw new Exception("Not enough points.");
    }

    // Reward id bul
    $reward_sql = "SELECT reward_id FROM rewards WHERE required_points = ? LIMIT 1";
    $reward_stmt = $conn->prepare($reward_sql);
    $reward_stmt->bind_param("i", $cost);
    $reward_stmt->execute();

    $reward_result = $reward_stmt->get_result();

    if ($reward_result->num_rows === 0) {
        throw new Exception("Reward not found.");
    }

    $reward = $reward_result->fetch_assoc();
    $reward_id = $reward["reward_id"];

    // users_rewards insert
    $insert_sql = "
        INSERT INTO users_rewards
        (user_id, reward_id, purchase_date)
        VALUES (?, ?, CURDATE())
    ";

    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ii", $user_id, $reward_id);
    $insert_stmt->execute();

    // puan düş
    $update_sql = "
        UPDATE users
        SET total_point = total_point - ?
        WHERE user_id = ?
    ";

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $cost, $user_id);
    $update_stmt->execute();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Reward redeemed successfully."
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>