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
// DRIVER CHECK
// ==========================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== "driver"
) {

    echo json_encode([
        "success" => false,
        "message" => "Only drivers can complete rides."
    ]);

    exit;
}


$driver_id = (int) $_SESSION['user_id'];

$ride_id = (int) ($_POST['ride_id'] ?? 0);


// ==========================================
// RIDE ID CHECK
// ==========================================

if ($ride_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid ride."
    ]);

    exit;
}


// ==========================================
// TRANSACTION
// ==========================================

$conn->begin_transaction();


try {

    // ==========================================
    // GET RIDE
    // ==========================================

    $stmt = $conn->prepare("
        SELECT
            id,
            driver_id,
            ride_date,
            ride_time,
            status
        FROM rides
        WHERE id = ?
        AND driver_id = ?
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception("Database query failed.");
    }

    $stmt->bind_param(
        "ii",
        $ride_id,
        $driver_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $ride = $result->fetch_assoc();

    $stmt->close();


    // ==========================================
    // RIDE NOT FOUND
    // ==========================================

    if (!$ride) {

        throw new Exception(
            "Ride not found or you are not the driver."
        );
    }


    // ==========================================
    // ALREADY COMPLETED
    // ==========================================

    if ($ride['status'] === 'completed') {

        throw new Exception(
            "This ride is already completed."
        );
    }


    // ==========================================
    // CANCELLED RIDE
    // ==========================================

    if ($ride['status'] === 'cancelled') {

        throw new Exception(
            "Cancelled ride cannot be completed."
        );
    }


    // ==========================================
    // CHECK RIDE TIME
    // Driver cannot complete before scheduled time
    // ==========================================

    $rideDateTime =
        $ride['ride_date'] . " " . $ride['ride_time'];

    $rideTimestamp =
        strtotime($rideDateTime);

    $currentTimestamp =
        time();


    if ($rideTimestamp === false) {

        throw new Exception(
            "Invalid ride date or time."
        );
    }


    if ($rideTimestamp > $currentTimestamp) {

        throw new Exception(
            "You cannot complete the ride before its scheduled time."
        );
    }


    // ==========================================
    // MARK RIDE COMPLETED
    // ==========================================

    $stmt = $conn->prepare("
        UPDATE rides
        SET status = 'completed'
        WHERE id = ?
        AND driver_id = ?
        AND status NOT IN ('completed', 'cancelled')
    ");

    if (!$stmt) {
        throw new Exception(
            "Could not update ride status."
        );
    }

    $stmt->bind_param(
        "ii",
        $ride_id,
        $driver_id
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "Could not complete ride."
        );
    }


    if ($stmt->affected_rows !== 1) {

        throw new Exception(
            "Ride could not be completed."
        );
    }

    $stmt->close();


    // ==========================================
    // MARK PASSENGER BOOKINGS COMPLETED
    // ==========================================

    $stmt = $conn->prepare("
        UPDATE bookings
        SET booking_status = 'completed'
        WHERE ride_id = ?
        AND booking_status IN ('confirmed', 'pending')
    ");

    if (!$stmt) {
        throw new Exception(
            "Could not update passenger bookings."
        );
    }

    $stmt->bind_param(
        "i",
        $ride_id
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "Could not update passenger bookings."
        );
    }

    $stmt->close();


    // ==========================================
    // COMMIT
    // ==========================================

    $conn->commit();


    echo json_encode([
        "success" => true,
        "message" => "Ride completed successfully."
    ]);

}
catch (Exception $e) {

    $conn->rollback();

    error_log(
        "Complete Ride Error: " . $e->getMessage()
    );

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}


$conn->close();

?>