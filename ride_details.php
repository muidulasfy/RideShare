<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// =========================================
// 1. LOGIN CHECK
// =========================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


$user_id =
    (int) $_SESSION['user_id'];


// =========================================
// 2. GET USER
// =========================================

$userSql = "
    SELECT
        gender,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
";


$userStmt =
    $conn->prepare(
        $userSql
    );


if (!$userStmt) {

    error_log(
        "Ride details user prepare failed: " .
        $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Something went wrong."
    ]);

    exit;
}


$userStmt->bind_param(
    "i",
    $user_id
);


if (!$userStmt->execute()) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify user."
    ]);

    $userStmt->close();
    $conn->close();

    exit;
}


$userResult =
    $userStmt->get_result();


if (
    $userResult->num_rows === 0
) {

    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);

    $userStmt->close();
    $conn->close();

    exit;
}


$user =
    $userResult->fetch_assoc();


$userGender =
    strtolower(
        trim(
            $user['gender'] ?? ""
        )
    );


$userRole =
    strtolower(
        trim(
            $user['role'] ?? ""
        )
    );


$userStmt->close();


// =========================================
// 3. ONLY STUDENTS CAN VIEW RIDE DETAILS
// =========================================

if ($userRole !== "user") {

    echo json_encode([
        "success" => false,
        "message" =>
            "Only students can view ride details."
    ]);

    $conn->close();

    exit;
}


// =========================================
// 4. GET RIDE ID
// =========================================

$ride_id =
    (int) (
        $_GET['id'] ?? 0
    );


if ($ride_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid ride ID."
    ]);

    $conn->close();

    exit;
}


// =========================================
// 5. GET RIDE
// =========================================

$sql = "

    SELECT

        r.id,

        r.driver_id,

        r.from_location,

        r.to_location,

        r.pickup_checkpoint,

        r.drop_checkpoint,

        r.ride_date,

        r.ride_time,

        r.available_seats,

        r.price_per_seat,

        r.vehicle_type,

        r.female_only,

        r.recurring,

        r.status,


        u.name AS driver_name,

        u.phone AS driver_phone,

        u.gender AS driver_gender,


        (
            SELECT
                AVG(dr.rating)
            FROM driver_ratings dr
            WHERE dr.driver_id = r.driver_id
        ) AS driver_rating,


        (
            SELECT
                COUNT(*)
            FROM driver_ratings dr
            WHERE dr.driver_id = r.driver_id
        ) AS rating_count


    FROM rides r


    INNER JOIN users u

        ON r.driver_id = u.id


    WHERE r.id = ?

    LIMIT 1

";


$stmt =
    $conn->prepare(
        $sql
    );


if (!$stmt) {

    error_log(
        "Ride details prepare failed: " .
        $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" =>
            "Database query failed."
    ]);

    $conn->close();

    exit;
}


$stmt->bind_param(
    "i",
    $ride_id
);


if (!$stmt->execute()) {

    error_log(
        "Ride details execute failed: " .
        $stmt->error
    );

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to load ride."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


$result =
    $stmt->get_result();


// =========================================
// 6. RIDE NOT FOUND
// =========================================

if (
    $result->num_rows === 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Ride not found."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


$ride =
    $result->fetch_assoc();


// =========================================
// 7. FEMALE ONLY CHECK
// =========================================

if (
    (int) $ride['female_only'] === 1 &&
    $userGender !== "female"
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "This ride is available for female passengers only."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// =========================================
// 8. STATUS CHECK
// =========================================

if (
    $ride['status'] !== "available"
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "This ride is no longer available."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// =========================================
// 9. SEAT CHECK
// =========================================

if (
    (int) $ride['available_seats'] <= 0
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "No seats are available for this ride."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// =========================================
// 10. FUTURE RIDE CHECK
// =========================================

$rideDate =
    $ride['ride_date'];

$rideTime =
    $ride['ride_time'];


$currentDate =
    date("Y-m-d");

$currentTime =
    date("H:i:s");


if (
    $rideDate < $currentDate ||

    (
        $rideDate === $currentDate &&
        $rideTime <= $currentTime
    )
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "This ride has already passed."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// =========================================
// 11. RATING FORMAT
// =========================================

if (
    $ride['driver_rating'] !== null
) {

    $ride['driver_rating'] =
        round(
            (float)
            $ride['driver_rating'],
            1
        );

}

else {

    $ride['driver_rating'] =
        null;

}


$ride['rating_count'] =
    (int) (
        $ride['rating_count'] ?? 0
    );


// =========================================
// 12. RETURN DATA
// =========================================

echo json_encode([

    "success" => true,

    "ride" => $ride

]);


$stmt->close();

$conn->close();

?>