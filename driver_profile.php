<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// ==========================================
// LOGIN CHECK
// ==========================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


// ==========================================
// GET DRIVER ID
// ==========================================

$driver_id =
    (int) ($_GET['id'] ?? 0);


if ($driver_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid driver."
    ]);

    exit;
}


// ==========================================
// GET DRIVER PROFILE
// ==========================================

$stmt = $conn->prepare("

    SELECT

        u.id,
        u.name,
        u.gender,
        u.role,

        d.vehicle_number,
        d.vehicle_type,
        d.vehicle_model,

        (
            SELECT COUNT(*)
            FROM rides r
            WHERE r.driver_id = u.id
            AND r.status = 'completed'
        ) AS completed_rides,

        (
            SELECT
                ROUND(
                    AVG(dr.rating),
                    1
                )
            FROM driver_ratings dr
            WHERE dr.driver_id = u.id
        ) AS average_rating,

        (
            SELECT COUNT(*)
            FROM driver_ratings dr
            WHERE dr.driver_id = u.id
        ) AS rating_count

    FROM users u

    LEFT JOIN driver_details d
        ON d.user_id = u.id

    WHERE u.id = ?

    AND u.role = 'driver'

    LIMIT 1

");


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database query failed."
    ]);

    exit;
}


$stmt->bind_param(
    "i",
    $driver_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$driver =
    $result->fetch_assoc();


$stmt->close();


if (!$driver) {

    echo json_encode([
        "success" => false,
        "message" => "Driver not found."
    ]);

    exit;
}


// ==========================================
// GET REVIEWS
// ==========================================

$reviewStmt = $conn->prepare("

    SELECT

        dr.rating,
        dr.review,
        dr.created_at,

        u.name AS reviewer_name

    FROM driver_ratings dr

    INNER JOIN users u
        ON dr.user_id = u.id

    WHERE dr.driver_id = ?

    ORDER BY
        dr.created_at DESC

");


$reviewStmt->bind_param(
    "i",
    $driver_id
);


$reviewStmt->execute();


$reviewResult =
    $reviewStmt->get_result();


$reviews = [];


while (
    $row =
    $reviewResult->fetch_assoc()
) {

    $reviews[] =
        $row;
}


$reviewStmt->close();


// ==========================================
// FORMAT RATING
// ==========================================

if (
    $driver['average_rating'] !== null
) {

    $driver['average_rating'] =
        (float) $driver['average_rating'];

}

else {

    $driver['average_rating'] =
        null;
}


// ==========================================
// RESPONSE
// ==========================================

echo json_encode([

    "success" => true,

    "driver" => $driver,

    "reviews" => $reviews

]);


$conn->close();

?>