<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// ==========================================
// CHECK LOGIN
// ==========================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


// ==========================================
// ONLY STUDENT CAN BOOK
// ==========================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== "user"
) {

    echo json_encode([
        "success" => false,
        "message" => "Only students can book rides."
    ]);

    exit;
}


// ==========================================
// GET DATA
// ==========================================

$user_id =
    (int) $_SESSION['user_id'];

$ride_id =
    (int) ($_POST['ride_id'] ?? 0);

$seats =
    (int) ($_POST['seats'] ?? 1);

$tip_amount =
    (float) ($_POST['tip_amount'] ?? 0);


// ==========================================
// VALIDATE RIDE ID
// ==========================================

if ($ride_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid ride."
    ]);

    exit;
}


// ==========================================
// VALIDATE SEATS
// ==========================================

if ($seats <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid number of seats."
    ]);

    exit;
}


// ==========================================
// VALIDATE TIP
// ==========================================

if (
    !is_finite($tip_amount) ||
    $tip_amount < 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid tip amount."
    ]);

    exit;
}


// Round to 2 decimal places
$tip_amount =
    round($tip_amount, 2);


// ==========================================
// GET RIDE
// ==========================================

$sql = "
    SELECT
        id,
        driver_id,
        available_seats,
        status,
        ride_date,
        ride_time,
        female_only

    FROM rides

    WHERE id = ?
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    error_log(
        "Book ride prepare failed: " .
        $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    exit;
}


$stmt->bind_param(
    "i",
    $ride_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    echo json_encode([
        "success" => false,
        "message" => "Ride not found."
    ]);

    exit;
}


$ride =
    $result->fetch_assoc();


$stmt->close();


// ==========================================
// CHECK RIDE DATE/TIME
// ==========================================

$rideDateTime =
    $ride['ride_date'] .
    ' ' .
    $ride['ride_time'];


$currentDateTime =
    date("Y-m-d H:i:s");


if (
    strtotime($rideDateTime) <=
    strtotime($currentDateTime)
) {

    echo json_encode([
        "success" => false,
        "message" => "This ride has already started or passed."
    ]);

    exit;
}


// ==========================================
// CHECK OWN RIDE
// ==========================================

if (
    (int)$ride['driver_id'] ===
    $user_id
) {

    echo json_encode([
        "success" => false,
        "message" => "You cannot book your own ride."
    ]);

    exit;
}


// ==========================================
// CHECK FEMALE-ONLY RIDE
// ==========================================

if (
    (int)$ride['female_only'] === 1 &&
    (
        !isset($_SESSION['gender']) ||
        strtolower($_SESSION['gender']) !== "female"
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "This is a female-only ride."
    ]);

    exit;
}


// ==========================================
// CHECK STATUS
// ==========================================

if (
    $ride['status'] !== "available"
) {

    echo json_encode([
        "success" => false,
        "message" => "This ride is not available."
    ]);

    exit;
}


// ==========================================
// CHECK AVAILABLE SEATS
// ==========================================

if (
    (int)$ride['available_seats'] <
    $seats
) {

    echo json_encode([
        "success" => false,
        "message" => "Not enough seats available."
    ]);

    exit;
}


// ==========================================
// CHECK DUPLICATE BOOKING
// ==========================================

$sql = "
    SELECT id

    FROM bookings

    WHERE user_id = ?
    AND ride_id = ?

    AND booking_status
    IN ('pending', 'confirmed')

    LIMIT 1
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    exit;
}


$stmt->bind_param(
    "ii",
    $user_id,
    $ride_id
);


$stmt->execute();


$result =
    $stmt->get_result();


if ($result->num_rows > 0) {

    $stmt->close();

    echo json_encode([
        "success" => false,
        "message" => "You have already booked this ride."
    ]);

    exit;
}


$stmt->close();


// ==========================================
// START TRANSACTION
// ==========================================

$conn->begin_transaction();


try {

    // ======================================
    // LOCK RIDE
    // ======================================

    $sql = "
        SELECT
            id,
            driver_id,
            available_seats,
            status,
            ride_date,
            ride_time,
            female_only

        FROM rides

        WHERE id = ?

        FOR UPDATE
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        throw new Exception(
            "Could not lock ride."
        );
    }


    $stmt->bind_param(
        "i",
        $ride_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    if ($result->num_rows === 0) {

        throw new Exception(
            "Ride not found."
        );
    }


    $lockedRide =
        $result->fetch_assoc();


    $stmt->close();


    // ======================================
    // RECHECK RIDE TIME
    // ======================================

    $lockedRideDateTime =
        $lockedRide['ride_date'] .
        ' ' .
        $lockedRide['ride_time'];


    if (
        strtotime($lockedRideDateTime) <=
        strtotime(date("Y-m-d H:i:s"))
    ) {

        throw new Exception(
            "This ride has already started or passed."
        );
    }


    // ======================================
    // RECHECK STATUS
    // ======================================

    if (
        $lockedRide['status'] !==
        "available"
    ) {

        throw new Exception(
            "This ride is no longer available."
        );
    }


    // ======================================
    // RECHECK SEATS
    // ======================================

    if (
        (int)$lockedRide['available_seats'] <
        $seats
    ) {

        throw new Exception(
            "Not enough seats available."
        );
    }


    // ======================================
    // INSERT BOOKING
    // ======================================

    $sql = "
        INSERT INTO bookings
        (
            user_id,
            ride_id,
            seats_booked,
            booking_status,
            tip_amount
        )

        VALUES
        (
            ?,
            ?,
            ?,
            'confirmed',
            ?
        )
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        throw new Exception(
            "Could not prepare booking."
        );
    }


    $stmt->bind_param(
        "iiid",
        $user_id,
        $ride_id,
        $seats,
        $tip_amount
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Booking failed."
        );
    }


    $stmt->close();


    // ======================================
    // UPDATE AVAILABLE SEATS
    // ======================================

    $new_seats =
        (int)$lockedRide['available_seats']
        - $seats;


    if ($new_seats === 0) {

        $newStatus =
            "full";


        $sql = "
            UPDATE rides

            SET
                available_seats = ?,
                status = ?

            WHERE id = ?
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            throw new Exception(
                "Could not update ride."
            );
        }


        $stmt->bind_param(
            "isi",
            $new_seats,
            $newStatus,
            $ride_id
        );

    }

    else {

        $sql = "
            UPDATE rides

            SET
                available_seats = ?

            WHERE id = ?
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            throw new Exception(
                "Could not update ride."
            );
        }


        $stmt->bind_param(
            "ii",
            $new_seats,
            $ride_id
        );
    }


    if (!$stmt->execute()) {

        throw new Exception(
            "Seat update failed."
        );
    }


    $stmt->close();


    // ======================================
    // COMMIT
    // ======================================

    $conn->commit();


    $message =
        "Ride booked successfully!";


    if ($tip_amount > 0) {

        $message .=
            " Extra tip: ৳" .
            number_format(
                $tip_amount,
                2
            );
    }


    echo json_encode([
        "success" => true,
        "message" => $message,
        "tip_amount" => $tip_amount
    ]);

}

catch (Exception $e) {

    $conn->rollback();


    error_log(
        "Book ride error: " .
        $e->getMessage()
    );


    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}


// ==========================================
// CLOSE
// ==========================================

$conn->close();

?>