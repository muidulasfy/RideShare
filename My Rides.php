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


$user_id =
    (int) $_SESSION['user_id'];


// ==========================================
// GET BOOKINGS
// ==========================================

$sql = $conn->prepare("

    SELECT

        b.id AS booking_id,

        b.ride_id,

        b.seats_booked,

        b.booking_status,

        b.booked_at,


        r.from_location,

        r.to_location,

        r.ride_date,

        r.ride_time,

        r.vehicle_type,

        r.status AS ride_status,


        u.id AS driver_id,

        u.name AS driver_name,

        u.phone AS driver_phone,


        b.tip_amount,


        dr.id AS rating_id,

        dr.rating AS driver_rating,

        dr.review AS driver_review


    FROM bookings b


    INNER JOIN rides r

        ON b.ride_id = r.id


    INNER JOIN users u

        ON r.driver_id = u.id


    LEFT JOIN driver_ratings dr

        ON dr.booking_id = b.id


    WHERE b.user_id = ?


    ORDER BY

        r.ride_date ASC,

        r.ride_time ASC

");


if (!$sql) {

    echo json_encode([
        "success" => false,
        "message" => "Database query failed."
    ]);

    exit;
}


$sql->bind_param(
    "i",
    $user_id
);


if (!$sql->execute()) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to load bookings."
    ]);

    exit;
}


$result =
    $sql->get_result();


$bookings = [];


while (
    $row =
    $result->fetch_assoc()
) {

    $bookings[] =
        $row;
}


// ==========================================
// RESPONSE
// ==========================================

echo json_encode([

    "success" => true,

    "bookings" => $bookings

]);


$sql->close();

$conn->close();

?>