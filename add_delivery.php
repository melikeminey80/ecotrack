<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

// Login session yoksa test için son kullanıcı/örnek kullanıcı kullanılır.
// Login tamamen bağlanınca bu satırı sadece $_SESSION["user_id"] olarak bırakabilirsiniz.
$user_id = $_SESSION["user_id"] ?? 3;

$conn = new mysqli("localhost", "root", "", "ecotrack");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

$conn->set_charset("utf8mb4");

$waste_type_id = $_POST["waste_type_id"] ?? "";
$waste_point_id = $_POST["waste_point_id"] ?? "";
$amount = $_POST["amount"] ?? "";

if ($waste_type_id === "" || $waste_point_id === "" || $amount === "") {
    echo json_encode(["success" => false, "message" => "Required fields are missing."]);
    exit;
}

if (!is_numeric($amount) || $amount <= 0) {
    echo json_encode(["success" => false, "message" => "Amount must be greater than zero."]);
    exit;
}

$conn->begin_transaction();

try {
    $point_sql = "SELECT point FROM waste_types WHERE waste_type_id = ?";
    $point_stmt = $conn->prepare($point_sql);
    $point_stmt->bind_param("i", $waste_type_id);
    $point_stmt->execute();

    $point_result = $point_stmt->get_result();

    if ($point_result->num_rows === 0) {
        throw new Exception("Waste type not found.");
    }

    $waste_type = $point_result->fetch_assoc();
    $point_per_kg = $waste_type["point"];
    $earned_point = $amount * $point_per_kg;
    $qr_code = "QR" . time();

    $delivery_sql = "
        INSERT INTO deliveries 
        (user_id, waste_type_id, waste_point_id, amount, delivery_date, qr_code)
        VALUES (?, ?, ?, ?, CURDATE(), ?)
    ";

    $delivery_stmt = $conn->prepare($delivery_sql);
    $delivery_stmt->bind_param(
        "iiids",
        $user_id,
        $waste_type_id,
        $waste_point_id,
        $amount,
        $qr_code
    );
    $delivery_stmt->execute();

    $delivery_id = $conn->insert_id;

    $points_sql = "
        INSERT INTO points 
        (user_id, delivery_id, point_amount, point_date)
        VALUES (?, ?, ?, CURDATE())
    ";

    $points_stmt = $conn->prepare($points_sql);
    $points_stmt->bind_param("iid", $user_id, $delivery_id, $earned_point);
    $points_stmt->execute();

    $update_sql = "
        UPDATE users
        SET total_point = total_point + ?
        WHERE user_id = ?
    ";

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("di", $earned_point, $user_id);
    $update_stmt->execute();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Delivery added successfully.",
        "earned_point" => $earned_point,
        "qr_code" => $qr_code
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