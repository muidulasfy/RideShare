<?php

session_start();

header("Content-Type: application/json");


/* ==========================================
   NOT LOGGED IN
   ========================================== */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "loggedIn" => false,
        "role" => "",
        "gender" => ""
    ]);

    exit;
}


/* ==========================================
   GET SESSION DATA
   ========================================== */

$role =
    strtolower(
        trim(
            $_SESSION['role'] ?? ""
        )
    );


$gender =
    strtolower(
        trim(
            $_SESSION['gender'] ?? ""
        )
    );


/* ==========================================
   RESPONSE
   ========================================== */

echo json_encode([

    "loggedIn" => true,

    "role" => $role,

    "gender" => $gender

]);

?>