<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.html");
    exit;
}

$total_users = 0;
$total_drivers = 0;
$total_rides = 0;
$total_bookings = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
if ($result) {
    $total_users = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'driver'");
if ($result) {
    $total_drivers = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM rides");
if ($result) {
    $total_rides = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM bookings");
if ($result) {
    $total_bookings = $result->fetch_assoc()['total'];
}

$users = $conn->query("
    SELECT id, name, email, phone, gender, role
    FROM users
    ORDER BY id DESC
");

$rides = $conn->query("
    SELECT 
        r.id,
        r.from_location,
        r.to_location,
        r.ride_date,
        r.ride_time,
        r.available_seats,
        r.vehicle_type,
        r.status,
        u.name AS driver_name
    FROM rides r
    INNER JOIN users u ON r.driver_id = u.id
    ORDER BY r.id DESC
");

$bookings = $conn->query("
    SELECT
        b.id,
        b.seats_booked,
        b.booking_status,
        b.booked_at,
        u.name AS user_name,
        r.from_location,
        r.to_location
    FROM bookings b
    INNER JOIN users u ON b.user_id = u.id
    INNER JOIN rides r ON b.ride_id = r.id
    ORDER BY b.id DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>RideNest - Admin Dashboard</title>

    <link rel="stylesheet" href="admin_dashboard.css">

</head>

<body>

<div class="page">


    <div class="navbar">

        <div class="logo">
            RideNest
        </div>

        <div class="admin-title">
            Admin Dashboard
        </div>

        <a href="admin_logout.php" class="logout">
            Logout
        </a>

    </div>

    <div class="main">

        <h1>Dashboard</h1>

        <p class="subtitle">
            Manage users, drivers, rides and bookings.
        </p>

        <div class="stats">

            <div class="stat-card blue">
                <p>Total Students</p>
                <h2><?php echo $total_users; ?></h2>
            </div>

            <div class="stat-card">
                <p>Total Drivers</p>
                <h2><?php echo $total_drivers; ?></h2>
            </div>

            <div class="stat-card blue">
                <p>Total Rides</p>
                <h2><?php echo $total_rides; ?></h2>
            </div>

            <div class="stat-card">
                <p>Total Bookings</p>
                <h2><?php echo $total_bookings; ?></h2>
            </div>

        </div>


        <div class="section">

            <h2>Users</h2>

            <div class="table-box">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($user = $users->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo $user['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user['gender']); ?>
                            </td>

                            <td>
                                <span class="role">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($user['role'] != 'admin') { ?>

                                    <button
                                        class="delete-btn"
                                        onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        Delete
                                    </button>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>
        <div class="section">

            <h2>Posted Rides</h2>

            <div class="table-box">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Driver</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Seats</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($ride = $rides->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo $ride['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ride['driver_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ride['from_location']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ride['to_location']); ?>
                            </td>

                            <td>
                                <?php echo $ride['ride_date']; ?>
                            </td>

                            <td>
                                <?php echo $ride['ride_time']; ?>
                            </td>

                            <td>
                                <?php echo $ride['available_seats']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($ride['vehicle_type']); ?>
                            </td>

                            <td>
                                <span class="status">
                                    <?php echo $ride['status']; ?>
                                </span>
                            </td>

                            <td>

                                <button
                                    class="delete-btn"
                                    onclick="deleteRide(<?php echo $ride['id']; ?>)">
                                    Delete
                                </button>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>


        <div class="section">

            <h2>Bookings</h2>

            <div class="table-box">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Route</th>
                            <th>Seats</th>
                            <th>Status</th>
                            <th>Booked At</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($booking = $bookings->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo $booking['id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking['user_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($booking['from_location']); ?>
                                →
                                <?php echo htmlspecialchars($booking['to_location']); ?>
                            </td>

                            <td>
                                <?php echo $booking['seats_booked']; ?>
                            </td>

                            <td>
                                <span class="status">
                                    <?php echo $booking['booking_status']; ?>
                                </span>
                            </td>

                            <td>
                                <?php echo $booking['booked_at']; ?>
                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script src="admin_dashboard.js"></script>

</body>

</html>