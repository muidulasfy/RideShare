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
    failResponse("Only drivers can edit rides.");
}

$driver_id = (int) $_SESSION['user_id'];
$ride_id = (int) ($_POST['ride_id'] ?? 0);

$pickup = trim($_POST['pickup'] ?? "");
$destination = trim($_POST['destination'] ?? "");
$pickup_checkpoint = trim($_POST['pickup_checkpoint'] ?? "");
$drop_checkpoint = trim($_POST['drop_checkpoint'] ?? "");
$date = $_POST['date'] ?? "";
$time = $_POST['time'] ?? "";
$total_seats = (int) ($_POST['seats'] ?? 0);
$vehicle = trim($_POST['vehicle'] ?? "");
$price = (float) ($_POST['price'] ?? -1);
$female_only = (int) ($_POST['female_only'] ?? 0);
$recurring = (int) ($_POST['recurring'] ?? 0);

if ($ride_id <= 0) {
    failResponse("Invalid ride.");
}

if (
    $pickup === "" ||
    $destination === "" ||
    $date === "" ||
    $time === "" ||
    $vehicle === "" ||
    $total_seats <= 0 ||
    $price < 0
) {
    failResponse("Please fill all required fields correctly.");
}

if ($female_only !== 0 && $female_only !== 1) {
    failResponse("Invalid female-only option.");
}

if ($recurring !== 0 && $recurring !== 1) {
    failResponse("Invalid recurring option.");
}

$dateObject = DateTime::createFromFormat("Y-m-d", $date);
$timeObject = DateTime::createFromFormat("H:i", $time);

if (!$dateObject || $dateObject->format("Y-m-d") !== $date) {
    failResponse("Please enter a valid ride date.");
}

if (!$timeObject || $timeObject->format("H:i") !== $time) {
    failResponse("Please enter a valid ride time.");
}

$rideDateTime = new DateTime($date . " " . $time);

if ($rideDateTime <= new DateTime()) {
    failResponse("Ride date and time must be in the future.");
}

$conn->begin_transaction();

try {

    /* Lock the ride while checking bookings and updating it. */
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

    $rideResult = $rideStmt->get_result();
    $ride = $rideResult->fetch_assoc();
    $rideStmt->close();

    if (!$ride) {
        throw new Exception("Ride not found.");
    }

    if ((int) $ride['driver_id'] !== $driver_id) {
        throw new Exception("You can only edit your own rides.");
    }

    if ($ride['status'] === 'cancelled') {
        throw new Exception("Cancelled rides cannot be edited.");
    }

    $oldRideDateTime = new DateTime(
        $ride['ride_date'] . " " . $ride['ride_time']
    );

    if ($oldRideDateTime <= new DateTime()) {
        throw new Exception("This ride has already started or completed.");
    }

    /* Confirmed seats already occupied by passengers. */
    $bookingStmt = $conn->prepare("
        SELECT
            COALESCE(SUM(seats_booked), 0) AS booked_seats
        FROM bookings
        WHERE ride_id = ?
          AND booking_status = 'confirmed'
    ");

    if (!$bookingStmt) {
        throw new Exception("Booking check failed.");
    }

    $bookingStmt->bind_param("i", $ride_id);
    $bookingStmt->execute();

    $bookingData = $bookingStmt->get_result()->fetch_assoc();
    $bookingStmt->close();

    $bookedSeats = (int) ($bookingData['booked_seats'] ?? 0);

    if ($total_seats < $bookedSeats) {
        throw new Exception(
            "Total seats cannot be less than the " .
            $bookedSeats .
            " seats already booked."
        );
    }

    /*
     * If a driver turns on Female Only, do not invalidate existing
     * confirmed male passenger bookings.
     */
    if ($female_only === 1) {

        $maleBookingStmt = $conn->prepare("
            SELECT COUNT(*) AS male_bookings
            FROM bookings b
            INNER JOIN users u
                ON b.user_id = u.id
            WHERE b.ride_id = ?
              AND b.booking_status = 'confirmed'
              AND LOWER(TRIM(u.gender)) = 'male'
        ");

        if (!$maleBookingStmt) {
            throw new Exception("Passenger check failed.");
        }

        $maleBookingStmt->bind_param("i", $ride_id);
        $maleBookingStmt->execute();

        $maleBookings =
            (int) (
                $maleBookingStmt
                    ->get_result()
                    ->fetch_assoc()['male_bookings'] ?? 0
            );

        $maleBookingStmt->close();

        if ($maleBookings > 0) {
            throw new Exception(
                "This ride has confirmed male passenger bookings. " .
                "You cannot change it to Female Only."
            );
        }
    }

    /* Verify that the vehicle belongs to this driver. */
    $vehicleStmt = $conn->prepare("
        SELECT id
        FROM driver_details
        WHERE user_id = ?
          AND vehicle_number = ?
        LIMIT 1
    ");

    if (!$vehicleStmt) {
        throw new Exception("Vehicle verification failed.");
    }

    $vehicleStmt->bind_param("is", $driver_id, $vehicle);
    $vehicleStmt->execute();

    if ($vehicleStmt->get_result()->num_rows === 0) {
        $vehicleStmt->close();
        throw new Exception(
            "This vehicle is not registered under your driver account."
        );
    }

    $vehicleStmt->close();

    /* Get vehicle type from driver profile. */
    $vehicleTypeStmt = $conn->prepare("
        SELECT vehicle_type
        FROM driver_details
        WHERE user_id = ?
          AND vehicle_number = ?
        LIMIT 1
    ");

    if (!$vehicleTypeStmt) {
        throw new Exception("Vehicle type lookup failed.");
    }

    $vehicleTypeStmt->bind_param("is", $driver_id, $vehicle);
    $vehicleTypeStmt->execute();

    $vehicleType =
        $vehicleTypeStmt
            ->get_result()
            ->fetch_assoc()['vehicle_type'] ?? "";

    $vehicleTypeStmt->close();

    if ($vehicleType === "") {
        throw new Exception("Vehicle type could not be determined.");
    }

    $availableSeats = $total_seats - $bookedSeats;
    $newStatus = ($availableSeats <= 0) ? "full" : "available";

    /*
     * Detect current price column so the edit works with the
     * database version already used by the project.
     */
    $priceColumn = "price";

    $priceCheck = $conn->query("SHOW COLUMNS FROM rides LIKE 'price'");

    if (!$priceCheck || $priceCheck->num_rows === 0) {
        $legacyPriceCheck =
            $conn->query("SHOW COLUMNS FROM rides LIKE 'price_per_seat'");

        if ($legacyPriceCheck && $legacyPriceCheck->num_rows > 0) {
            $priceColumn = "price_per_seat";
        }
    }

    $sql = "
        UPDATE rides
        SET
            from_location = ?,
            to_location = ?,
            pickup_checkpoint = ?,
            drop_checkpoint = ?,
            ride_date = ?,
            ride_time = ?,
            available_seats = ?,
            $priceColumn = ?,
            vehicle_type = ?,
            female_only = ?,
            recurring = ?,
            status = ?
        WHERE id = ?
          AND driver_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Ride update query failed.");
    }

    $stmt->bind_param(
        "ssssssidsiisii",
        $pickup,
        $destination,
        $pickup_checkpoint,
        $drop_checkpoint,
        $date,
        $time,
        $availableSeats,
        $price,
        $vehicleType,
        $female_only,
        $recurring,
        $newStatus,
        $ride_id,
        $driver_id
    );

    $stmt->bind_param(
        "ssssssidsiisii",
        $pickup,
        $destination,
        $pickup_checkpoint,
        $drop_checkpoint,
        $date,
        $time,
        $availableSeats,
        $price,
        $vehicleType,
        $female_only,
        $recurring,
        $newStatus,
        $ride_id,
        $driver_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Ride could not be updated.");
    }

    if ($stmt->affected_rows < 0) {
        throw new Exception("Ride update failed.");
    }

    $stmt->close();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Ride updated successfully."
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    error_log("Update ride failed: " . $e->getMessage());

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>
