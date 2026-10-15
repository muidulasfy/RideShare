<?php

session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: admin_login.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email == "" || $password == "") {
    die("Please enter email and password.");
}

$stmt = $conn->prepare("
    SELECT id, name, email, password, role
    FROM users
    WHERE email = ?
    AND role = 'admin'
    LIMIT 1
");

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Admin account not found.");
}

$admin = $result->fetch_assoc();

if (!password_verify($password, $admin["password"])) {
    die("Invalid admin email or password.");
}

$_SESSION["user_id"] = $admin["id"];
$_SESSION["name"] = $admin["name"];
$_SESSION["email"] = $admin["email"];
$_SESSION["role"] = "admin";


$stmt->close();
$conn->close();


header("Location: admin_dashboard.php");
exit;

?>