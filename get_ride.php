<?php

session_start();
include "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "driver") {
    echo json_encode([
        "success" => false,
        "message" => "Only drivers can edit rides."
    ]);
    exit;
}

$driver_id = (int) $_SESSION['user_id'];
$ride_id = (int) ($_GET['id'] ?? 0);

if ($ride_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid ride."
    ]);
    exit;
}

/* Support the project's current price column safely. */
$priceColumn = "price";
$priceCheck = $conn->query("SHOW COLUMNS FROM rides LIKE 'price'");

if (!$priceCheck || $priceCheck->num_rows === 0) {
    $legacyPriceCheck = $conn->query("SHOW COLUMNS FROM rides LIKE 'price_per_seat'");

    if ($legacyPriceCheck && $legacyPriceCheck->num_rows > 0) {
        $priceColumn = "price_per_seat";
    }
}

$sql = "
    SELECT
        r.id,
        r.from_location,
        r.to_location,
        r.pickup_checkpoint,
        r.drop_checkpoint,
        r.ride_date,
        r.ride_time,
        r.available_seats,
        r.$priceColumn AS price,
        r.vehicle_type,
        r.female_only,
        r.recurring,
        r.status,

        d.vehicle_number,

        COALESCE(
            (
                SELECT SUM(b.seats_booked)
                FROM bookings b
                WHERE b.ride_id = r.id
                  AND b.booking_status = 'confirmed'
            ),
            0
        ) AS booked_seats

    FROM rides r

    LEFT JOIN driver_details d
        ON d.user_id = r.driver_id
       AND d.vehicle_type = r.vehicle_type

    WHERE r.id = ?
      AND r.driver_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to load ride details."
    ]);
    exit;
}

$stmt->bind_param("ii", $ride_id, $driver_id);
$stmt->execute();

$result = $stmt->get_result();
$ride = $result->fetch_assoc();

if (!$ride) {
    echo json_encode([
        "success" => false,
        "message" => "Ride not found."
    ]);
    exit;
}

$rideDateTime = new DateTime(
    $ride['ride_date'] . " " . $ride['ride_time']
);

if ($ride['status'] === 'cancelled' || $rideDateTime <= new DateTime()) {
    echo json_encode([
        "success" => false,
        "message" => "This ride can no longer be edited."
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "ride" => $ride
]);

$stmt->close();
$conn->close();
?>
