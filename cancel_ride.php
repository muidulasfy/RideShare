<?php

session_start();
include "db.php";

header("Content-Type: application/json");

function failResponse($message) {
    echo json_encode([
        "success" => false,
        "message" => $message
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    failResponse("Please login first.");
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "driver") {
    failResponse("Only drivers can cancel rides.");
}

$driver_id = (int) $_SESSION['user_id'];
$ride_id = (int) ($_POST['ride_id'] ?? 0);

if ($ride_id <= 0) {
    failResponse("Invalid ride.");
}

$conn->begin_transaction();

try {

    $rideStmt = $conn->prepare("
        SELECT
            id,
            driver_id,
            ride_date,
            ride_time,
            status
        FROM rides
        WHERE id = ?
        FOR UPDATE
    ");

    if (!$rideStmt) {
        throw new Exception("Ride query failed.");
    }

    $rideStmt->bind_param("i", $ride_id);
    $rideStmt->execute();

    $ride = $rideStmt->get_result()->fetch_assoc();
    $rideStmt->close();

    if (!$ride) {
        throw new Exception("Ride not found.");
    }

    if ((int) $ride['driver_id'] !== $driver_id) {
        throw new Exception("You can only cancel your own rides.");
    }

    if ($ride['status'] === 'cancelled') {
        throw new Exception("This ride is already cancelled.");
    }

    $rideDateTime = new DateTime(
        $ride['ride_date'] . " " . $ride['ride_time']
    );

    if ($rideDateTime <= new DateTime()) {
        throw new Exception(
            "This ride has already started or completed and cannot be cancelled."
        );
    }

    /*
     * Cancel the ride first. Then cancel all passenger bookings
     * belonging to this ride. Since the ride is cancelled, those
     * bookings should not remain confirmed.
     */
    $updateRide = $conn->prepare("
        UPDATE rides
        SET status = 'cancelled'
        WHERE id = ?
          AND driver_id = ?
          AND status != 'cancelled'
    ");

    if (!$updateRide) {
        throw new Exception("Ride cancellation query failed.");
    }

    $updateRide->bind_param("ii", $ride_id, $driver_id);

    if (!$updateRide->execute() || $updateRide->affected_rows !== 1) {
        $updateRide->close();
        throw new Exception("Ride could not be cancelled.");
    }

    $updateRide->close();

    $updateBookings = $conn->prepare("
        UPDATE bookings
        SET booking_status = 'cancelled'
        WHERE ride_id = ?
          AND booking_status IN ('pending', 'confirmed')
    ");

    if (!$updateBookings) {
        throw new Exception("Passenger booking update failed.");
    }

    $updateBookings->bind_param("i", $ride_id);

    if (!$updateBookings->execute()) {
        $updateBookings->close();
        throw new Exception("Passenger bookings could not be cancelled.");
    }

    $cancelledBookings = $updateBookings->affected_rows;
    $updateBookings->close();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => $cancelledBookings > 0
            ? "Ride cancelled. Passenger bookings were also cancelled."
            : "Ride cancelled successfully."
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    error_log("Cancel ride failed: " . $e->getMessage());

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>
