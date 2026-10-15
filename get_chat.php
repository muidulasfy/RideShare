<?php

session_start();

include "db.php";

header("Content-Type: application/json");


if (!isset($_SESSION['user_id'])) {

    echo json_encode([]);

    exit;
}


$current_user =
    (int) $_SESSION['user_id'];


$role =
    $_SESSION['role'] ?? "";


$users = [];


if ($role === "user") {

    $sql = "
        SELECT
            u.id,
            u.name,
            u.gender,
            u.role
        FROM users u

        INNER JOIN driver_details d
            ON u.id = d.user_id

        WHERE u.role = 'driver'

        AND u.id != ?

        ORDER BY u.name
    ";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "i",
        $current_user
    );

}


else if ($role === "driver") {

    $sql = "
        SELECT
            id,
            name,
            gender,
            role
        FROM users

        WHERE role = 'user'

        AND id != ?

        ORDER BY name
    ";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "i",
        $current_user
    );

}



else {

    echo json_encode([]);

    exit;
}



$stmt->execute();


$result =
    $stmt->get_result();


while (
    $row =
    $result->fetch_assoc()
) {

    $users[] = $row;

}


echo json_encode($users);


$stmt->close();

$conn->close();

?>