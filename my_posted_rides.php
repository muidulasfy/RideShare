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
        "message" => "Only drivers can view posted rides."
    ]);

    exit;
}


$driver_id = (int) $_SESSION['user_id'];

$status = $_GET['status'] ?? 'upcoming';


// ==========================================
// VALID STATUS
// ==========================================

if (
    !in_array(
        $status,
        ['upcoming', 'completed', 'cancelled'],
        true
    )
) {

    $status = 'upcoming';
}


// ==========================================
// PRICE COLUMN
// Supports price OR old price_per_seat
// ==========================================

$priceColumn = "price";

$priceCheck =
    $conn->query(
        "SHOW COLUMNS FROM rides LIKE 'price'"
    );


if (
    !$priceCheck ||
    $priceCheck->num_rows === 0
) {

    $legacyPriceCheck =
        $conn->query(
            "SHOW COLUMNS FROM rides LIKE 'price_per_seat'"
        );

    if (
        $legacyPriceCheck &&
        $legacyPriceCheck->num_rows > 0
    ) {

        $priceColumn = "price_per_seat";
    }
}


// ==========================================
// UPCOMING
// ==========================================

if ($status === 'upcoming') {

    /*
        Upcoming means:

        - Not completed
        - Not cancelled

        Even if scheduled time has passed,
        driver can still complete the ride.
    */

    $sql = "
        SELECT

            r.id,
            r.from_location,
            r.to_location,
            r.ride_date,
            r.ride_time,
            r.available_seats,
            r.vehicle_type,
            r.$priceColumn AS price_per_seat,
            r.female_only,
            r.recurring,
            r.status,

            COALESCE(
                SUM(
                    CASE
                        WHEN b.booking_status = 'confirmed'
                        THEN b.seats_booked
                        ELSE 0
                    END
                ),
                0
            ) AS booked_seats

        FROM rides r

        LEFT JOIN bookings b
            ON r.id = b.ride_id

        WHERE r.driver_id = ?

        AND r.status NOT IN (
            'completed',
            'cancelled'
        )

        GROUP BY

            r.id,
            r.from_location,
            r.to_location,
            r.ride_date,
            r.ride_time,
            r.available_seats,
            r.vehicle_type,
            r.$priceColumn,
            r.female_only,
            r.recurring,
            r.status

        ORDER BY
            r.ride_date ASC,
            r.ride_time ASC
    ";

}


// ==========================================
// COMPLETED
// ==========================================

elseif ($status === 'completed') {

    /*
        Completed means:
        Driver explicitly clicked
        "Complete Ride"
    */

    $sql = "
        SELECT

            r.id,
            r.from_location,
            r.to_location,
            r.ride_date,
            r.ride_time,
            r.available_seats,
            r.vehicle_type,
            r.$priceColumn AS price_per_seat,
            r.female_only,
            r.recurring,
            r.status,

            COALESCE(
                SUM(
                    CASE
                        WHEN b.booking_status = 'completed'
                        THEN b.seats_booked
                        ELSE 0
                    END
                ),
                0
            ) AS booked_seats

        FROM rides r

        LEFT JOIN bookings b
            ON r.id = b.ride_id

        WHERE r.driver_id = ?

        AND r.status = 'completed'

        GROUP BY

            r.id,
            r.from_location,
            r.to_location,
            r.ride_date,
            r.ride_time,
            r.available_seats,
            r.vehicle_type,
            r.$priceColumn,
            r.female_only,
            r.recurring,
            r.status

        ORDER BY
            r.ride_date DESC,
            r.ride_time DESC
    ";

}


// ==========================================
// CANCELLED
// ==========================================

else {

    $sql = "
        SELECT

            r.id,
            r.from_location,
            r.to_location,
            r.ride_date,
            r.ride_time,
            r.available_seats,
            r.vehicle_type,
            r.$priceColumn AS price_per_seat,
            r.female_only,
            r.recurring,
            r.status,

            COALESCE(
                SUM(
                    CASE
                        WHEN b.booking_status IN (
                            'confirmed',
                            'completed'
                        )
                        THEN b.seats_booked
                        ELSE 0
                    END
                ),
                0
            ) AS booked_seats

        FROM rides r

        LEFT JOIN bookings b
            ON r.id = b.ride_id

        WHERE r.driver_id = ?

        AND r.status = 'cancelled'

        GROUP BY

            r.id,
            r.from_location,
            r.to_location,
            r.ride_date,
            r.ride_time,
            r.available_seats,
            r.vehicle_type,
            r.$priceColumn,
            r.female_only,
            r.recurring,
            r.status

        ORDER BY
            r.ride_date DESC,
            r.ride_time DESC
    ";
}


// ==========================================
// PREPARE
// ==========================================

$stmt = $conn->prepare($sql);


if (!$stmt) {

    error_log(
        "My Posted Rides prepare failed: "
        . $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Unable to load rides right now."
    ]);

    exit;
}


// ==========================================
// BIND
// ==========================================

$stmt->bind_param(
    "i",
    $driver_id
);


// ==========================================
// EXECUTE
// ==========================================

if (!$stmt->execute()) {

    error_log(
        "My Posted Rides execute failed: "
        . $stmt->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Unable to load rides right now."
    ]);

    exit;
}


// ==========================================
// FETCH
// ==========================================

$result = $stmt->get_result();

$rides = [];


while ($row = $result->fetch_assoc()) {

    $rides[] = $row;
}


// ==========================================
// RESPONSE
// ==========================================

echo json_encode([
    "success" => true,
    "rides" => $rides
]);


$stmt->close();

$conn->close();

?>