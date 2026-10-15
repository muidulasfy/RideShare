<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// =========================================
// 1. Login check
// =========================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


// =========================================
// 2. Driver check
// =========================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== "driver"
) {

    echo json_encode([
        "success" => false,
        "message" => "Only drivers can use recurring rides."
    ]);

    exit;
}


$driver_id = (int) $_SESSION['user_id'];


// =========================================
// 3. Check price column
// =========================================

$priceColumn = "price";

$priceCheck = $conn->query(
    "SHOW COLUMNS FROM rides LIKE 'price'"
);


if (
    !$priceCheck ||
    $priceCheck->num_rows === 0
) {

    $legacyPriceCheck = $conn->query(
        "SHOW COLUMNS FROM rides LIKE 'price_per_seat'"
    );


    if (
        $legacyPriceCheck &&
        $legacyPriceCheck->num_rows > 0
    ) {

        $priceColumn = "price_per_seat";

    }

}


// =========================================
// 4. Get latest recurring ride
// =========================================

$sql = "
    SELECT

        id,

        from_location,

        to_location,

        pickup_checkpoint,

        drop_checkpoint,

        ride_time,

        available_seats,

        vehicle_type,

        female_only,

        $priceColumn AS price

    FROM rides

    WHERE driver_id = ?

    AND recurring = 1

    ORDER BY

        ride_date DESC,

        ride_time DESC,

        id DESC

    LIMIT 1
";


// =========================================
// 5. Prepare
// =========================================

$stmt = $conn->prepare($sql);


if (!$stmt) {

    error_log(
        "Recurring ride query failed: "
        . $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Database query failed."
    ]);

    exit;
}


// =========================================
// 6. Bind driver
// =========================================

$stmt->bind_param(
    "i",
    $driver_id
);


// =========================================
// 7. Execute
// =========================================

if (!$stmt->execute()) {

    error_log(
        "Recurring ride execute failed: "
        . $stmt->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Could not load previous recurring ride."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// =========================================
// 8. Get result
// =========================================

$result = $stmt->get_result();


// =========================================
// 9. No previous recurring ride
// =========================================

if ($result->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "No previous recurring ride found."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// =========================================
// 10. Get latest ride
// =========================================

$ride = $result->fetch_assoc();


// =========================================
// 11. Return data
// =========================================

echo json_encode([
    "success" => true,
    "ride" => $ride
]);


$stmt->close();

$conn->close();

?>