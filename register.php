<?php
session_start();

$conn = new mysqli("localhost", "root", "", "ecotrack");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $account_type = $_POST["account_type"] ?? "";
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($account_type === "" || $email === "" || $phone === "" || $password === "") {
        die("Required fields are missing.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    if (strlen($password) < 6) {
        die("Password must be at least 6 characters.");
    }

    if ($account_type !== "individual" && $account_type !== "company") {
        die("Invalid account type.");
    }

    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        die("This email is already registered.");
    }

    $password_hash = hash("sha256", $password);

    $conn->begin_transaction();

    try {
        $sql = "
            INSERT INTO users (email, password, phone_number, total_point)
            VALUES (?, ?, ?, 0)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $password_hash, $phone);
        $stmt->execute();

        $user_id = $conn->insert_id;

        if ($account_type === "individual") {
            $first_name = trim($_POST["first_name"] ?? "");
            $last_name = trim($_POST["last_name"] ?? "");

            if ($first_name === "" || $last_name === "") {
                throw new Exception("First name and last name are required.");
            }

            $sql2 = "
                INSERT INTO individual (user_id, name, last_name)
                VALUES (?, ?, ?)
            ";

            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("iss", $user_id, $first_name, $last_name);
            $stmt2->execute();
        }

        if ($account_type === "company") {
            $company_name = trim($_POST["company_name"] ?? "");
            $tax_no = trim($_POST["tax_no"] ?? "");
            $contact_person = trim($_POST["contact_person"] ?? "");

            if ($company_name === "" || $tax_no === "" || $contact_person === "") {
                throw new Exception("Company fields are required.");
            }

            $sql2 = "
                INSERT INTO company (user_id, company_name, tax_no, contact_person)
                VALUES (?, ?, ?, ?)
            ";

            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("isss", $user_id, $company_name, $tax_no, $contact_person);
            $stmt2->execute();
        }

        $conn->commit();

        $_SESSION["user_id"] = $user_id;
        $_SESSION["email"] = $email;

        header("Location: dashboard.html");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Register error: " . $e->getMessage());
    }
}
?>