<?php

include "db.php";

header("Content-Type: application/json");


// =====================================================
// 1. REQUEST METHOD
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    exit;
}


// =====================================================
// 2. GET DATA
// =====================================================

$email =
    trim($_POST["email"] ?? "");

$newPassword =
    $_POST["new_password"] ?? "";


// =====================================================
// 3. EMPTY CHECK
// =====================================================

if (
    $email === "" ||
    $newPassword === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all fields."
    ]);

    exit;
}


// =====================================================
// 4. EMAIL VALIDATION
// =====================================================

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    exit;
}


// =====================================================
// 5. PASSWORD LENGTH
// =====================================================

if (
    strlen($newPassword) < 6
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Password must be at least 6 characters."
    ]);

    exit;
}


// =====================================================
// 6. CHECK USER
// =====================================================

$stmt =
    $conn->prepare(
        "SELECT id
         FROM users
         WHERE email = ?
         LIMIT 1"
    );


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Database error."
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


if (
    $result->num_rows === 0
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "No account found with this email."
    ]);

    $stmt->close();

    $conn->close();

    exit;
}


$user =
    $result->fetch_assoc();


$stmt->close();


// =====================================================
// 7. HASH NEW PASSWORD
// =====================================================

$hashedPassword =
    password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );


// =====================================================
// 8. UPDATE PASSWORD
// =====================================================

$update =
    $conn->prepare(
        "UPDATE users
         SET password = ?
         WHERE id = ?"
    );


if (!$update) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to update password."
    ]);

    $conn->close();

    exit;
}


$update->bind_param(
    "si",
    $hashedPassword,
    $user["id"]
);


if (
    $update->execute()
) {

    echo json_encode([

        "success" => true,

        "message" =>
            "Password reset successfully. Please login again."

    ]);

}

else {

    echo json_encode([

        "success" => false,

        "message" =>
            "Password reset failed."

    ]);

}


$update->close();

$conn->close();

?>