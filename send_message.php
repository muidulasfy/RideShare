<?php

session_start();

include "db.php";

header("Content-Type: application/json");


/* =========================================
   1. LOGIN CHECK
========================================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


$sender_id =
    (int) $_SESSION['user_id'];


/* =========================================
   2. INPUT CHECK
========================================= */

if (
    !isset($_POST['receiver_id']) ||
    !isset($_POST['message'])
) {

    echo json_encode([
        "success" => false,
        "message" => "Missing information."
    ]);

    exit;
}


$receiver_id =
    (int) $_POST['receiver_id'];


$message =
    trim($_POST['message']);


/* =========================================
   3. BASIC VALIDATION
========================================= */

if ($receiver_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid receiver."
    ]);

    exit;
}


if ($message === "") {

    echo json_encode([
        "success" => false,
        "message" => "Message cannot be empty."
    ]);

    exit;
}


if ($sender_id === $receiver_id) {

    echo json_encode([
        "success" => false,
        "message" => "You cannot message yourself."
    ]);

    exit;
}


/* =========================================
   4. GET CURRENT USER ROLE
========================================= */

$current_role =
    $_SESSION['role'] ?? "";


/* =========================================
   5. CHECK RECEIVER
========================================= */

$sql = "
    SELECT
        id,
        role
    FROM users
    WHERE id = ?
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    exit;
}


$stmt->bind_param(
    "i",
    $receiver_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$receiver =
    $result->fetch_assoc();


$stmt->close();


/* Receiver does not exist */

if (!$receiver) {

    echo json_encode([
        "success" => false,
        "message" => "Receiver not found."
    ]);

    $conn->close();

    exit;
}


$receiver_role =
    $receiver['role'];


/* =========================================
   6. ROLE AUTHORIZATION
========================================= */

/*
    Student -> Driver
    Driver  -> Student
*/

if ($current_role === "user") {

    if ($receiver_role !== "driver") {

        echo json_encode([
            "success" => false,
            "message" => "You can only message drivers."
        ]);

        $conn->close();

        exit;
    }

}

else if ($current_role === "driver") {

    if ($receiver_role !== "user") {

        echo json_encode([
            "success" => false,
            "message" => "You can only message students."
        ]);

        $conn->close();

        exit;
    }

}

else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid user role."
    ]);

    $conn->close();

    exit;
}


/* =========================================
   7. INSERT MESSAGE
========================================= */

$sql = "
    INSERT INTO messages
    (
        sender_id,
        receiver_id,
        message
    )

    VALUES (?, ?, ?)
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    $conn->close();

    exit;
}


$stmt->bind_param(
    "iis",

    $sender_id,

    $receiver_id,

    $message
);


if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Message sent."
    ]);

}

else {

    echo json_encode([
        "success" => false,
        "message" => "Failed to send message."
    ]);

}


$stmt->close();

$conn->close();

?>