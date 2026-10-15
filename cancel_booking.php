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


$booking_id =
    (int) ($_POST['booking_id'] ?? 0);


// =========================================
// 2. BOOKING ID CHECK
// =========================================

if ($booking_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid booking."
    ]);

    exit;
}


// =========================================
// 3. START TRANSACTION
// =========================================

$conn->begin_transaction();


try {


    // =====================================
    // 4. GET BOOKING + LOCK ROW
    // =====================================

    $stmt = $conn->prepare("
        SELECT

            b.id,
            b.ride_id,
            b.seats_booked,
            b.booking_status,

            r.available_seats,
            r.ride_date,
            r.ride_time,
            r.status AS ride_status

        FROM bookings b

        INNER JOIN rides r
            ON b.ride_id = r.id

        WHERE b.id = ?
        AND b.user_id = ?

        FOR UPDATE
    ");


    if (!$stmt) {

        throw new Exception(
            "Could not verify booking."
        );
    }


    $stmt->bind_param(
        "ii",
        $booking_id,
        $user_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    $booking =
        $result->fetch_assoc();


    $stmt->close();


    // =====================================
    // 5. BOOKING NOT FOUND
    // =====================================

    if (!$booking) {

        throw new Exception(
            "Booking not found."
        );
    }


    // =====================================
    // 6. CHECK BOOKING STATUS
    // =====================================

    if (
        $booking['booking_status']
        === 'cancelled'
    ) {

        throw new Exception(
            "This booking is already cancelled."
        );
    }


    if (
        $booking['booking_status']
        === 'completed'
    ) {

        throw new Exception(
            "Completed booking cannot be cancelled."
        );
    }


    // =====================================
    // 7. CHECK RIDE DATE/TIME
    // =====================================

    $rideDateTime =
        new DateTime(
            $booking['ride_date']
            . " "
            . $booking['ride_time']
        );


    $currentDateTime =
        new DateTime();


    if (
        $rideDateTime <=
        $currentDateTime
    ) {

        throw new Exception(
            "This ride has already started or passed."
        );
    }


    // =====================================
    // 8. RESTORE SEATS
    // =====================================

    $new_seats =
        (int) $booking['available_seats']
        +
        (int) $booking['seats_booked'];


    $stmt = $conn->prepare("
        UPDATE rides

        SET
            available_seats = ?,
            status = 'available'

        WHERE id = ?
    ");


    if (!$stmt) {

        throw new Exception(
            "Could not update ride."
        );
    }


    $stmt->bind_param(
        "ii",
        $new_seats,
        $booking['ride_id']
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Could not restore available seats."
        );
    }


    $stmt->close();


    // =====================================
    // 9. CANCEL BOOKING
    // =====================================

    $stmt = $conn->prepare("
        UPDATE bookings

        SET booking_status = 'cancelled'

        WHERE id = ?
        AND user_id = ?
        AND booking_status IN
            ('pending', 'confirmed')
    ");


    if (!$stmt) {

        throw new Exception(
            "Could not cancel booking."
        );
    }


    $stmt->bind_param(
        "ii",
        $booking_id,
        $user_id
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Could not cancel booking."
        );
    }


    // Make sure exactly one booking was changed

    if ($stmt->affected_rows !== 1) {

        throw new Exception(
            "Booking could not be cancelled."
        );
    }


    $stmt->close();


    // =====================================
    // 10. COMMIT
    // =====================================

    $conn->commit();


    echo json_encode([
        "success" => true,
        "message" =>
            "Booking cancelled successfully."
    ]);


}


// =========================================
// 11. ERROR / ROLLBACK
// =========================================

catch (Exception $e) {

    $conn->rollback();


    error_log(
        "Cancel booking error: "
        . $e->getMessage()
    );


    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}


// =========================================
// 12. CLOSE CONNECTION
// =========================================

$conn->close();

?>