<?php

session_start();

include "db.php";

header("Content-Type: application/json");


// =========================================
// LOGIN CHECK
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


// =========================================
// GET PROFILE
// =========================================

$sql = "

    SELECT

        u.id,
        u.name,
        u.email,
        u.phone,
        u.gender,
        u.role,

        COALESCE(
            u.department,
            ''
        ) AS department,


        (
            SELECT COUNT(*)

            FROM rides r

            WHERE
                r.status = 'available'

                AND TIMESTAMP(
                    r.ride_date,
                    r.ride_time
                ) >= NOW()

        ) AS available_rides,


        (
            SELECT COUNT(*)

            FROM bookings b

            WHERE
                b.user_id = u.id

        ) AS total_bookings,


        (
            SELECT COUNT(*)

            FROM bookings b

            INNER JOIN rides r
                ON b.ride_id = r.id

            WHERE
                b.user_id = u.id

                AND b.booking_status = 'confirmed'

                AND TIMESTAMP(
                    r.ride_date,
                    r.ride_time
                ) < NOW()

        ) AS completed_trips,


        (
            SELECT
                ROUND(
                    AVG(dr.rating),
                    1
                )

            FROM driver_ratings dr

            WHERE
                dr.driver_id = u.id

        ) AS average_rating,


        (
            SELECT COUNT(*)

            FROM driver_ratings dr

            WHERE
                dr.driver_id = u.id

        ) AS rating_count,


        d.vehicle_type,

        d.vehicle_model,

        d.vehicle_number,


        (
            SELECT COUNT(*)

            FROM rides r2

            WHERE
                r2.driver_id = u.id

                AND r2.status != 'cancelled'

                AND TIMESTAMP(
                    r2.ride_date,
                    r2.ride_time
                ) < NOW()

        ) AS completed_rides


    FROM users u


    LEFT JOIN driver_details d

        ON d.user_id = u.id


    WHERE
        u.id = ?

    LIMIT 1

";


$stmt =
    $conn->prepare(
        $sql
    );


if (!$stmt) {

    error_log(
        "Profile prepare failed: " .
        $conn->error
    );

    echo json_encode([
        "success" => false,
        "message" =>
            "Database query failed."
    ]);

    $conn->close();

    exit;
}


$stmt->bind_param(
    "i",
    $user_id
);


if (!$stmt->execute()) {

    error_log(
        "Profile execute failed: " .
        $stmt->error
    );

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to load profile."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


$result =
    $stmt->get_result();


if (
    $result->num_rows === 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Profile not found."
    ]);

    $stmt->close();
    $conn->close();

    exit;
}


$profile =
    $result->fetch_assoc();


// =========================================
// FORMAT VALUES
// =========================================

$profile['available_rides'] =
    (int) (
        $profile['available_rides'] ?? 0
    );


$profile['total_bookings'] =
    (int) (
        $profile['total_bookings'] ?? 0
    );


$profile['completed_trips'] =
    (int) (
        $profile['completed_trips'] ?? 0
    );


$profile['completed_rides'] =
    (int) (
        $profile['completed_rides'] ?? 0
    );


$profile['rating_count'] =
    (int) (
        $profile['rating_count'] ?? 0
    );


if (
    $profile['average_rating'] !== null
) {

    $profile['average_rating'] =
        round(
            (float)
            $profile['average_rating'],
            1
        );

}


// =========================================
// RECENT ACTIVITY
// =========================================

$activities = [];


$activitySql = "

    SELECT

        b.id AS booking_id,

        b.booking_status,

        r.from_location,

        r.to_location,

        r.ride_date,

        r.ride_time,

        u.name AS driver_name


    FROM bookings b


    INNER JOIN rides r

        ON b.ride_id = r.id


    INNER JOIN users u

        ON r.driver_id = u.id


    WHERE
        b.user_id = ?


    ORDER BY

        r.ride_date DESC,

        r.ride_time DESC


    LIMIT 5

";


$activityStmt =
    $conn->prepare(
        $activitySql
    );


if ($activityStmt) {

    $activityStmt->bind_param(
        "i",
        $user_id
    );


    if (
        $activityStmt->execute()
    ) {

        $activityResult =
            $activityStmt->get_result();


        while (
            $row =
                $activityResult->fetch_assoc()
        ) {

            $status =
                strtolower(
                    $row['booking_status'] ??
                    ''
                );


            if (
                $status === "cancelled"
            ) {

                $icon =
                    "✕";

                $statusText =
                    "Cancelled";

            }

            else {

                $icon =
                    "🚗";

                $statusText =
                    "Booked";

            }


            $route =
                (
                    $row['from_location'] ??
                    "Unknown"
                )
                .
                " → "
                .
                (
                    $row['to_location'] ??
                    "Unknown"
                );


            $activities[] = [

                "icon" =>
                    $icon,

                "title" =>
                    "Ride booking",

                "description" =>
                    $route .
                    " • " .
                    (
                        $row['driver_name'] ??
                        "Driver"
                    ),

                "status" =>
                    $statusText

            ];

        }

    }


    $activityStmt->close();

}


// =========================================
// RETURN
// =========================================

echo json_encode([

    "success" => true,

    "profile" => $profile,

    "activities" => $activities

]);


$stmt->close();

$conn->close();

?>