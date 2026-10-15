<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// =====================================================
// 1. LOGIN CHECK
// =====================================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


// =====================================================
// 2. DRIVER CHECK
// =====================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== "driver"
) {

    echo json_encode([
        "success" => false,
        "message" => "Only drivers can post rides."
    ]);

    exit;
}


$driver_id = (int) $_SESSION['user_id'];


// =====================================================
// 3. GET FORM DATA
// =====================================================

$pickup =
    trim($_POST['pickup'] ?? '');

$destination =
    trim($_POST['destination'] ?? '');

$pickup_checkpoint =
    trim($_POST['pickup_checkpoint'] ?? '');

$drop_checkpoint =
    trim($_POST['drop_checkpoint'] ?? '');

$date =
    trim($_POST['date'] ?? '');

$time =
    trim($_POST['time'] ?? '');

$seats =
    intval($_POST['seats'] ?? 0);

$vehicle =
    trim($_POST['vehicle'] ?? '');

$price =
    floatval($_POST['price'] ?? -1);

$female_only =
    intval($_POST['female_only'] ?? 0);

$recurring =
    intval($_POST['recurring'] ?? 0);


// =====================================================
// 4. REQUIRED FIELD CHECK
// =====================================================

if (
    $pickup === '' ||
    $destination === '' ||
    $date === '' ||
    $time === '' ||
    $seats <= 0 ||
    $vehicle === '' ||
    $price < 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Please fill all required fields correctly."
    ]);

    exit;
}


// =====================================================
// 5. DATE VALIDATION
// =====================================================

$dateObject =
    DateTime::createFromFormat(
        "Y-m-d",
        $date
    );

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $date
) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid ride date."
    ]);

    exit;
}


// =====================================================
// 6. TIME VALIDATION
// =====================================================

// HTML <input type="time"> normally sends HH:MM.
// Example: 09:31

if (
    !preg_match(
        '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
        $time
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid ride time."
    ]);

    exit;
}


// =====================================================
// 7. PREVENT PAST RIDE
// =====================================================

$rideDateTime =
    new DateTime(
        $date . " " . $time
    );

$currentDateTime =
    new DateTime();

if (
    $rideDateTime <=
    $currentDateTime
) {

    echo json_encode([
        "success" => false,
        "message" => "Ride date and time must be in the future."
    ]);

    exit;
}


// =====================================================
// 8. FEMALE-ONLY VALIDATION
// =====================================================

if (
    $female_only !== 0 &&
    $female_only !== 1
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid female-only option."
    ]);

    exit;
}


// =====================================================
// 9. RECURRING VALIDATION
// =====================================================

if (
    $recurring !== 0 &&
    $recurring !== 1
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid recurring ride option."
    ]);

    exit;
}


// =====================================================
// 10. CHECK DRIVER VEHICLE
// =====================================================

$vehicleCheck =
    $conn->prepare(
        "SELECT vehicle_type
         FROM driver_details
         WHERE user_id = ?
         AND vehicle_number = ?
         LIMIT 1"
    );


if (!$vehicleCheck) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Vehicle verification query failed: "
            . $conn->error
    ]);

    exit;
}


$vehicleCheck->bind_param(
    "is",
    $driver_id,
    $vehicle
);


if (!$vehicleCheck->execute()) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Vehicle verification failed: "
            . $vehicleCheck->error
    ]);

    $vehicleCheck->close();

    exit;
}


$vehicleResult =
    $vehicleCheck->get_result();


if (
    $vehicleResult->num_rows === 0
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "This vehicle is not registered under your driver account."
    ]);

    $vehicleCheck->close();

    exit;
}


$vehicleData =
    $vehicleResult->fetch_assoc();


$vehicleType =
    $vehicleData['vehicle_type'];


$vehicleCheck->close();


// =====================================================
// 11. RIDE STATUS
// =====================================================

$status = "available";


// =====================================================
// 12. INSERT RIDE
// =====================================================

// Your rides table uses price_per_seat.

$sql = "
    INSERT INTO rides
    (
        driver_id,
        from_location,
        to_location,
        pickup_checkpoint,
        drop_checkpoint,
        ride_date,
        ride_time,
        available_seats,
        price_per_seat,
        vehicle_type,
        female_only,
        recurring,
        status
    )

    VALUES
    (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" =>
            "SQL prepare failed: "
            . $conn->error
    ]);

    $conn->close();

    exit;
}


// =====================================================
// 13. BIND
// =====================================================

$stmt->bind_param(
    "issssssidsiis",

    $driver_id,

    $pickup,

    $destination,

    $pickup_checkpoint,

    $drop_checkpoint,

    $date,

    $time,

    $seats,

    $price,

    $vehicleType,

    $female_only,

    $recurring,

    $status
);


// =====================================================
// 14. EXECUTE
// =====================================================

if ($stmt->execute()) {

    echo json_encode([

        "success" => true,

        "message" =>
            "Ride posted successfully!",

        "ride_id" =>
            $stmt->insert_id

    ]);

}

else {

    echo json_encode([

        "success" => false,

        "message" =>
            "Database error: "
            . $stmt->error

    ]);
}


// =====================================================
// 15. CLOSE
// =====================================================

$stmt->close();

$conn->close();

?>