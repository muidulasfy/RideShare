<?php

session_start();

header("Content-Type: application/json");


// ==========================================
// NOT LOGGED IN
// ==========================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "loggedIn" => false
    ]);

    exit;
}


// ==========================================
// LOGGED IN
// ==========================================

echo json_encode([

    "loggedIn" => true,

    "user_id" => $_SESSION['user_id'],

    "name" => $_SESSION['name'] ?? "",

    "role" => strtolower(
        trim($_SESSION['role'] ?? "user")
    ),

    "gender" => strtolower(
        trim($_SESSION['gender'] ?? "")
    )

]);

?>