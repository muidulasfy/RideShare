<?php

session_start();

header("Content-Type: application/json");


// ==============================
// NOT LOGGED IN
// ==============================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "loggedIn" => false,
        "role" => null,
        "gender" => null
    ]);

    exit;
}


// ==============================
// LOGGED IN
// ==============================

echo json_encode([

    "loggedIn" => true,

    "role" =>
        strtolower(
            trim($_SESSION['role'] ?? "")
        ),

    "gender" =>
        strtolower(
            trim($_SESSION['gender'] ?? "")
        )

]);

?>