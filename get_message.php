<?php

session_start();

include "db.php";

header("Content-Type: application/json");


/* =========================================
   1. LOGIN CHECK
========================================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([]);

    exit;
}


$current_user =
    (int) $_SESSION['user_id'];


/* =========================================
   2. RECEIVER CHECK
========================================= */

if (!isset($_GET['receiver_id'])) {

    echo json_encode([]);

    exit;
}


$receiver_id =
    (int) $_GET['receiver_id'];


if ($receiver_id <= 0 || $receiver_id === $current_user) {

    echo json_encode([]);

    exit;
}


/* =========================================
   3. GET CURRENT USER ROLE
========================================= */

$current_role = $_SESSION['role'] ?? "";


/* =========================================
   4. CHECK RECEIVER
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

    echo json_encode([]);

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

    echo json_encode([]);

    $conn->close();

    exit;
}


$receiver_role =
    $receiver['role'];


/* =========================================
   5. ROLE CHECK
========================================= */

/*
    Student -> Driver
    Driver  -> Student
*/

if ($current_role === "user") {

    if ($receiver_role !== "driver") {

        echo json_encode([]);

        $conn->close();

        exit;
    }

}

else if ($current_role === "driver") {

    if ($receiver_role !== "user") {

        echo json_encode([]);

        $conn->close();

        exit;
    }

}

else {

    echo json_encode([]);

    $conn->close();

    exit;
}


/* =========================================
   6. LOAD CONVERSATION
========================================= */

$sql = "
    SELECT

        m.id,

        m.sender_id,

        m.receiver_id,

        m.message,

        m.sent_at,

        u.name AS sender_name

    FROM messages m

    INNER JOIN users u
        ON m.sender_id = u.id

    WHERE

        (
            m.sender_id = ?
            AND
            m.receiver_id = ?
        )

        OR

        (
            m.sender_id = ?
            AND
            m.receiver_id = ?
        )

    ORDER BY m.sent_at ASC
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([]);

    $conn->close();

    exit;
}


$stmt->bind_param(
    "iiii",

    $current_user,

    $receiver_id,

    $receiver_id,

    $current_user
);


$stmt->execute();


$result =
    $stmt->get_result();


$messages = [];


while (
    $row =
    $result->fetch_assoc()
) {

    $messages[] = $row;

}


echo json_encode($messages);


$stmt->close();

$conn->close();

?>