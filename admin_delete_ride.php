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
        "message" => "Ride ID missing."
    ]);

    exit;
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("
    DELETE FROM rides
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to delete ride."
    ]);
}

$stmt->close();
$conn->close();

?>