<?php

session_start();
include "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {

    echo json_encode([
        "success" => false,
        "message" => "Access denied."
    ]);

    exit;
}

if (!isset($_GET['id'])) {

    echo json_encode([
        "success" => false,
        "message" => "User ID missing."
    ]);

    exit;
}

$id = (int) $_GET['id'];

if ($id == $_SESSION['user_id']) {

    echo json_encode([
        "success" => false,
        "message" => "You cannot delete yourself."
    ]);

    exit;
}

$stmt = $conn->prepare("
    DELETE FROM users
    WHERE id = ?
    AND role != 'admin'
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to delete user."
    ]);
}

$stmt->close();
$conn->close();

?>