<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// =========================================================
// CHECK LOGIN
// =========================================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


// =========================================================
// GET USER INFORMATION
// =========================================================

$userId = (int) $_SESSION['user_id'];

$userStmt = $conn->prepare("
    SELECT gender, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$userStmt) {

    error_log("Find Ride user query failed: " . $conn->error);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load user information."
    ]);

    exit;
}

$userStmt->bind_param("i", $userId);

$userStmt->execute();

$userResult = $userStmt->get_result();

$user = $userResult->fetch_assoc();

$userStmt->close();


if (!$user) {

    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);

    exit;
}


$userGender = strtolower(
    trim($user['gender'] ?? "")
);

$userRole = strtolower(
    trim($user['role'] ?? "")
);


// =========================================================
// GET PICKUPS
// =========================================================

$pickups = [];

if (isset($_GET['pickup'])) {

    if (is_array($_GET['pickup'])) {

        $pickups = $_GET['pickup'];

    } else {

        $pickups = [$_GET['pickup']];

    }
}


$pickups = array_map("trim", $pickups);

$pickups = array_filter(
    $pickups,
    function ($value) {

        return $value !== "";

    }
);

$pickups = array_values($pickups);


// =========================================================
// GET DESTINATIONS
// =========================================================

$destinations = [];

if (isset($_GET['destination'])) {

    if (is_array($_GET['destination'])) {

        $destinations = $_GET['destination'];

    } else {

        $destinations = [$_GET['destination']];

    }
}


$destinations = array_map(
    "trim",
    $destinations
);

$destinations = array_filter(
    $destinations,
    function ($value) {

        return $value !== "";

    }
);

$destinations = array_values($destinations);


// =========================================================
// GET PICKUP TIME
// =========================================================

$pickupTime = trim(
    $_GET['pickup_time'] ?? ""
);


// Validate time if provided

if ($pickupTime !== "") {

    $timeObject =
        DateTime::createFromFormat(
            "H:i",
            $pickupTime
        );

    if (
        !$timeObject ||
        $timeObject->format("H:i") !== $pickupTime
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid pickup time."
        ]);

        exit;
    }
}


// =========================================================
// GET PASSENGERS
// =========================================================

$passengers = 0;

if (isset($_GET['passengers'])) {

    $passengers =
        intval($_GET['passengers']);

}


// Validate passengers

if ($passengers < 0 || $passengers > 4) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid passenger number."
    ]);

    exit;
}


// =========================================================
// BASE QUERY
// =========================================================

$sql = "
    SELECT

        r.id,

        r.driver_id,

        r.from_location,

        r.to_location,

        r.ride_date,

        r.ride_time,

        r.available_seats,

        r.price_per_seat,

        r.vehicle_type,

        r.female_only,

        u.name AS driver_name

    FROM rides r

    LEFT JOIN users u
        ON r.driver_id = u.id

    WHERE r.status = 'available'

      AND (
          r.ride_date > CURDATE()
          OR (
              r.ride_date = CURDATE()
              AND r.ride_time > CURTIME()
          )
      )
";


$params = [];

$types = "";


// =========================================================
// FEMALE-ONLY SECURITY
// =========================================================
//
// Male students/users cannot see female-only rides.
//
// Female users can see normal + female-only rides.
//
// Drivers are not restricted here because this endpoint
// is for finding available rides.
//

if (
    $userRole === "user" &&
    $userGender === "male"
) {

    $sql .= "
        AND r.female_only = 0
    ";

}


// =========================================================
// PICKUP FILTER
// =========================================================

if (count($pickups) > 0) {

    $conditions = [];

    foreach ($pickups as $pickup) {

        $conditions[] =
            "r.from_location LIKE ?";

        $params[] =
            "%" . $pickup . "%";

        $types .= "s";

    }


    $sql .= "
        AND (
            " .
            implode(" OR ", $conditions)
            . "
        )
    ";

}


// =========================================================
// DESTINATION FILTER
// =========================================================

if (count($destinations) > 0) {

    $conditions = [];

    foreach ($destinations as $destination) {

        $conditions[] =
            "r.to_location LIKE ?";

        $params[] =
            "%" . $destination . "%";

        $types .= "s";

    }


    $sql .= "
        AND (
            " .
            implode(" OR ", $conditions)
            . "
        )
    ";

}


// =========================================================
// PICKUP TIME FILTER
// =========================================================

if ($pickupTime !== "") {

    $sql .= "
        AND r.ride_time = ?
    ";

    $params[] = $pickupTime;

    $types .= "s";

}


// =========================================================
// PASSENGER FILTER
// =========================================================
//
// Example:
// User selects 2 passengers.
//
// Ride must have at least 2 available seats.
//

if ($passengers > 0) {

    $sql .= "
        AND r.available_seats >= ?
    ";

    $params[] = $passengers;

    $types .= "i";

}


// =========================================================
// ORDER
// =========================================================

$sql .= "
    ORDER BY
        r.ride_date ASC,
        r.ride_time ASC
";


// =========================================================
// PREPARE
// =========================================================

$stmt = $conn->prepare($sql);


if (!$stmt) {

    error_log(
        "Find Ride prepare failed: " .
        $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Unable to search rides right now."
    ]);

    $conn->close();

    exit;
}


// =========================================================
// BIND PARAMETERS
// =========================================================

if (count($params) > 0) {

    $bindValues = [];

    $bindValues[] = $types;

    foreach ($params as $key => $value) {

        $bindValues[] = &$params[$key];

    }

    call_user_func_array(
        [$stmt, "bind_param"],
        $bindValues
    );

}


// =========================================================
// EXECUTE
// =========================================================

if (!$stmt->execute()) {

    error_log(
        "Find Ride execute failed: " .
        $stmt->error
    );

    echo json_encode([
        "success" => false,
        "message" => "Unable to search rides right now."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


// =========================================================
// FETCH RESULTS
// =========================================================

$result = $stmt->get_result();

$rides = [];


while ($row = $result->fetch_assoc()) {

    $rides[] = $row;

}


// =========================================================
// RESPONSE
// =========================================================

echo json_encode([
    "success" => true,
    "rides" => $rides
]);


$stmt->close();

$conn->close();

?>