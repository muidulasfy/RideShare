<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// =====================================================
// 1. LOGIN CHECK
// =====================================================

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


$user_id =
    (int) $_SESSION["user_id"];


// =====================================================
// 2. GET DATA
// =====================================================

$booking_id =
    (int) ($_POST["booking_id"] ?? 0);

$rating =
    (int) ($_POST["rating"] ?? 0);

$review =
    trim($_POST["review"] ?? "");


// =====================================================
// 3. VALIDATE BOOKING ID
// =====================================================

if ($booking_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid booking."
    ]);

    exit;
}


// =====================================================
// 4. VALIDATE RATING
// =====================================================

if ($rating < 1 || $rating > 5) {

    echo json_encode([
        "success" => false,
        "message" => "Please select a rating from 1 to 5."
    ]);

    exit;
}


// =====================================================
// 5. REVIEW LENGTH
// =====================================================

if (strlen($review) > 1000) {

    echo json_encode([
        "success" => false,
        "message" => "Review is too long."
    ]);

    exit;
}


// =====================================================
// 6. GET BOOKING INFORMATION
// =====================================================

$sql = "
    SELECT

        b.id AS booking_id,
        b.user_id,
        b.ride_id,
        b.booking_status,

        r.driver_id,
        r.ride_date,
        r.ride_time,
        r.status AS ride_status

    FROM bookings b

    INNER JOIN rides r
        ON b.ride_id = r.id

    WHERE b.id = ?
    AND b.user_id = ?

    LIMIT 1
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    error_log(
        "Rating booking query failed: " .
        $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    exit;
}


$stmt->bind_param(
    "ii",
    $booking_id,
    $user_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Booking not found."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


$booking =
    $result->fetch_assoc();


$stmt->close();


// =====================================================
// 7. CHECK BOOKING STATUS
// =====================================================

if (
    $booking["booking_status"] ===
    "cancelled"
) {

    echo json_encode([
        "success" => false,
        "message" => "You cannot rate a cancelled booking."
    ]);

    $conn->close();

    exit;
}


// =====================================================
// 8. CHECK RIDE IS COMPLETED / PAST
// =====================================================

$rideDateTime =
    $booking["ride_date"] .
    " " .
    $booking["ride_time"];


if (
    strtotime($rideDateTime) >
    time()
) {

    echo json_encode([
        "success" => false,
        "message" => "You can rate the driver after the ride."
    ]);

    $conn->close();

    exit;
}


// =====================================================
// 9. CHECK EXISTING RATING
// =====================================================

$checkSql = "
    SELECT id

    FROM driver_ratings

    WHERE booking_id = ?

    LIMIT 1
";


$checkStmt =
    $conn->prepare($checkSql);


if (!$checkStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    $conn->close();

    exit;
}


$checkStmt->bind_param(
    "i",
    $booking_id
);


$checkStmt->execute();


$checkResult =
    $checkStmt->get_result();


if ($checkResult->num_rows > 0) {

    echo json_encode([
        "success" => false,
        "message" => "You have already rated this driver for this ride."
    ]);

    $checkStmt->close();
    $conn->close();

    exit;
}


$checkStmt->close();


// =====================================================
// 10. INSERT RATING
// =====================================================

$insertSql = "
    INSERT INTO driver_ratings
    (
        booking_id,
        user_id,
        driver_id,
        ride_id,
        rating,
        review
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
";


$insertStmt =
    $conn->prepare($insertSql);


if (!$insertStmt) {

    error_log(
        "Rating insert prepare failed: " .
        $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Unable to submit rating."
    ]);

    $conn->close();

    exit;
}


$insertStmt->bind_param(
    "iiiiis",
    $booking_id,
    $user_id,
    $booking["driver_id"],
    $booking["ride_id"],
    $rating,
    $review
);


if (!$insertStmt->execute()) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to submit rating."
    ]);

    $insertStmt->close();
    $conn->close();

    exit;
}


$insertStmt->close();


// =====================================================
// 11. SUCCESS
// =====================================================

echo json_encode([

    "success" => true,

    "message" =>
        "Thank you! Your rating and review have been submitted."

]);


$conn->close();

?>