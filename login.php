<?php

session_start();

include "db.php";

header("Content-Type: application/json");


$email =
    trim($_POST['email'] ?? '');

$password =
    $_POST['password'] ?? '';


if (
    $email === '' ||
    $password === ''
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Please enter email and password."
    ]);

    exit;
}


$sql = "
    SELECT *
    FROM users
    WHERE email = ?
    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Database query error."
    ]);

    exit;
}


$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows !== 1) {

    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);

    exit;
}


$user =
    $result->fetch_assoc();


if (
    !password_verify(
        $password,
        $user['password']
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "Incorrect password."
    ]);

    exit;
}


// ==========================================
// USER DATA
// ==========================================

$role =
    strtolower(
        trim($user['role'] ?? "")
    );

$gender =
    strtolower(
        trim($user['gender'] ?? "")
    );


// ==========================================
// SESSION
// ==========================================

$_SESSION['user_id'] =
    $user['id'];

$_SESSION['name'] =
    $user['name'];

$_SESSION['email'] =
    $user['email'];

$_SESSION['role'] =
    $role;

$_SESSION['gender'] =
    $gender;


// ==========================================
// RESPONSE
// ==========================================

echo json_encode([

    "success" => true,

    "message" =>
        "Login successful",

    "id" =>
        $user['id'],

    "name" =>
        $user['name'],

    "role" =>
        $role,

    "gender" =>
        $gender

]);


$stmt->close();

$conn->close();

?>