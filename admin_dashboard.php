<?php

session_start();
include "db.php";


// =====================================================
// ADMIN ACCESS CHECK
// =====================================================

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: admin_login.html");
    exit;
}


// =====================================================
// BASIC COUNTS
// =====================================================

$total_students = 0;
$total_drivers = 0;
$total_rides = 0;
$total_bookings = 0;

$active_rides = 0;
$cancelled_rides = 0;
$upcoming_rides = 0;
$today_rides = 0;
$female_rides = 0;

$confirmed_bookings = 0;
$cancelled_bookings = 0;


// Students
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'user'
");

if ($result) {
    $total_students = (int)$result->fetch_assoc()["total"];
}


// Drivers
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'driver'
");

if ($result) {
    $total_drivers = (int)$result->fetch_assoc()["total"];
}


// Total rides
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM rides
");

if ($result) {
    $total_rides = (int)$result->fetch_assoc()["total"];
}


// Total bookings
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM bookings
");

if ($result) {
    $total_bookings = (int)$result->fetch_assoc()["total"];
}


// Active rides
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM rides
    WHERE status = 'available'
");

if ($result) {
    $active_rides = (int)$result->fetch_assoc()["total"];
}


// Cancelled rides
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM rides
    WHERE status = 'cancelled'
");

if ($result) {
    $cancelled_rides = (int)$result->fetch_assoc()["total"];
}


// Upcoming rides
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM rides
    WHERE CONCAT(ride_date, ' ', ride_time) > NOW()
    AND status = 'available'
");

if ($result) {
    $upcoming_rides = (int)$result->fetch_assoc()["total"];
}


// Today's rides
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM rides
    WHERE ride_date = CURDATE()
");

if ($result) {
    $today_rides = (int)$result->fetch_assoc()["total"];
}


// Female-only rides
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM rides
    WHERE female_only = 1
");

if ($result) {
    $female_rides = (int)$result->fetch_assoc()["total"];
}


// Confirmed bookings
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE booking_status = 'confirmed'
");

if ($result) {
    $confirmed_bookings = (int)$result->fetch_assoc()["total"];
}


// Cancelled bookings
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE booking_status = 'cancelled'
");

if ($result) {
    $cancelled_bookings = (int)$result->fetch_assoc()["total"];
}


// =====================================================
// USERS
// =====================================================

$users = $conn->query("
    SELECT
        id,
        name,
        email,
        phone,
        gender,
        role
    FROM users
    ORDER BY id DESC
");


// =====================================================
// RIDES
// =====================================================

$rides = $conn->query("
    SELECT
        r.id,
        r.from_location,
        r.to_location,
        r.ride_date,
        r.ride_time,
        r.available_seats,
        r.vehicle_type,
        r.female_only,
        r.recurring,
        r.status,
        u.name AS driver_name
    FROM rides r
    INNER JOIN users u
        ON r.driver_id = u.id
    ORDER BY r.id DESC
");


// =====================================================
// BOOKINGS
// =====================================================

$bookings = $conn->query("
    SELECT
        b.id,
        b.seats_booked,
        b.booking_status,
        b.booked_at,
        u.name AS student_name,
        r.from_location,
        r.to_location,
        r.ride_date,
        r.ride_time
    FROM bookings b
    INNER JOIN users u
        ON b.user_id = u.id
    INNER JOIN rides r
        ON b.ride_id = r.id
    ORDER BY b.id DESC
");


// =====================================================
// RECENT BOOKINGS
// =====================================================

$recent_bookings = $conn->query("
    SELECT
        b.id,
        b.booking_status,
        b.seats_booked,
        b.booked_at,
        u.name AS student_name,
        r.from_location,
        r.to_location
    FROM bookings b
    INNER JOIN users u
        ON b.user_id = u.id
    INNER JOIN rides r
        ON b.ride_id = r.id
    ORDER BY b.id DESC
    LIMIT 5
");


// =====================================================
// HELPERS
// =====================================================

function statusClass($status)
{
    $status = strtolower(trim($status));

    if (
        $status === "available" ||
        $status === "confirmed" ||
        $status === "active"
    ) {
        return "success";
    }

    if (
        $status === "cancelled" ||
        $status === "canceled"
    ) {
        return "danger";
    }

    if (
        $status === "pending"
    ) {
        return "warning";
    }

    if (
        $status === "completed"
    ) {
        return "info";
    }

    return "neutral";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>RideNest Admin Dashboard</title>

    <link
        rel="stylesheet"
        href="admin_dashboard.css"
    >

</head>


<body>

<div class="admin-layout">


    <!-- ================================================= -->
    <!-- SIDEBAR -->
    <!-- ================================================= -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-logo">
                RideNest
            </div>

            <span>
                ADMIN
            </span>

        </div>


        <nav class="sidebar-menu">

            <button
                class="menu-item active"
                data-target="dashboard"
            >
                <span>▦</span>
                Dashboard
            </button>


            <button
                class="menu-item"
                data-target="users-section"
            >
                <span>👥</span>
                Users
            </button>


            <button
                class="menu-item"
                data-target="rides-section"
            >
                <span>🚗</span>
                Rides
            </button>


            <button
                class="menu-item"
                data-target="bookings-section"
            >
                <span>📋</span>
                Bookings
            </button>

        </nav>


        <div class="sidebar-bottom">

            <div class="admin-mini">

                <div class="admin-avatar">
                    A
                </div>

                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["name"] ?? "Admin"
                        );
                        ?>
                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>


            <button
                id="themeToggle"
                class="theme-toggle"
            >
                🌙 Dark mode
            </button>


            <a
                href="admin_logout.php"
                class="logout-btn"
            >
                ↪ Logout
            </a>

        </div>

    </aside>


    <!-- ================================================= -->
    <!-- MAIN CONTENT -->
    <!-- ================================================= -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">

            <div>

                <div class="page-label">
                    ADMIN PANEL
                </div>

                <h1>
                    Dashboard
                </h1>

            </div>


            <div class="topbar-right">

                <div class="current-date" id="currentDate">
                    --
                </div>

                <div class="top-avatar">
                    A
                </div>

            </div>

        </header>


        <!-- ================================================= -->
        <!-- DASHBOARD -->
        <!-- ================================================= -->

        <section id="dashboard">


            <div class="welcome-card">

                <div>

                    <span class="welcome-small">
                        Welcome back 👋
                    </span>

                    <h2>
                        RideNest System Overview
                    </h2>

                    <p>
                        Monitor students, drivers, rides and bookings
                        from one place.
                    </p>

                </div>


                <div class="dashboard-date">

                    <span>
                        Today
                    </span>

                    <strong id="dashboardDate">
                        --
                    </strong>

                </div>

            </div>


            <!-- MAIN STATS -->

            <div class="stats-grid">


                <div class="stat-card blue-card">

                    <div class="stat-icon">
                        👨‍🎓
                    </div>

                    <div>

                        <p>
                            Total Students
                        </p>

                        <h2>
                            <?php echo $total_students; ?>
                        </h2>

                        <span>
                            Registered users
                        </span>

                    </div>

                </div>


                <div class="stat-card purple-card">

                    <div class="stat-icon">
                        🚗
                    </div>

                    <div>

                        <p>
                            Total Drivers
                        </p>

                        <h2>
                            <?php echo $total_drivers; ?>
                        </h2>

                        <span>
                            Registered drivers
                        </span>

                    </div>

                </div>


                <div class="stat-card green-card">

                    <div class="stat-icon">
                        🛣️
                    </div>

                    <div>

                        <p>
                            Total Rides
                        </p>

                        <h2>
                            <?php echo $total_rides; ?>
                        </h2>

                        <span>
                            Posted rides
                        </span>

                    </div>

                </div>


                <div class="stat-card orange-card">

                    <div class="stat-icon">
                        📋
                    </div>

                    <div>

                        <p>
                            Total Bookings
                        </p>

                        <h2>
                            <?php echo $total_bookings; ?>
                        </h2>

                        <span>
                            Ride bookings
                        </span>

                    </div>

                </div>

            </div>


            <!-- SECONDARY STATS -->

            <div class="mini-grid">


                <div class="mini-card">

                    <div class="mini-icon green">
                        ✓
                    </div>

                    <div>

                        <span>
                            Active Rides
                        </span>

                        <strong>
                            <?php echo $active_rides; ?>
                        </strong>

                    </div>

                </div>


                <div class="mini-card">

                    <div class="mini-icon blue">
                        📅
                    </div>

                    <div>

                        <span>
                            Upcoming Rides
                        </span>

                        <strong>
                            <?php echo $upcoming_rides; ?>
                        </strong>

                    </div>

                </div>


                <div class="mini-card">

                    <div class="mini-icon orange">
                        ☀
                    </div>

                    <div>

                        <span>
                            Today's Rides
                        </span>

                        <strong>
                            <?php echo $today_rides; ?>
                        </strong>

                    </div>

                </div>


                <div class="mini-card">

                    <div class="mini-icon pink">
                        ♀
                    </div>

                    <div>

                        <span>
                            Female-only Rides
                        </span>

                        <strong>
                            <?php echo $female_rides; ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- OVERVIEW -->

            <div class="overview-grid">


                <div class="overview-card">

                    <div class="card-heading">

                        <div>

                            <h3>
                                Booking Overview
                            </h3>

                            <p>
                                Current booking status
                            </p>

                        </div>

                    </div>


                    <div class="booking-overview">

                        <div class="overview-number">

                            <strong>
                                <?php echo $confirmed_bookings; ?>
                            </strong>

                            <span>
                                Confirmed
                            </span>

                        </div>


                        <div class="overview-number">

                            <strong>
                                <?php echo $cancelled_bookings; ?>
                            </strong>

                            <span>
                                Cancelled
                            </span>

                        </div>

                    </div>


                    <div class="progress-area">

                        <div class="progress-label">

                            <span>
                                Confirmed bookings
                            </span>

                            <span>

                                <?php

                                $bookingPercentage = 0;

                                if ($total_bookings > 0) {

                                    $bookingPercentage =
                                        round(
                                            ($confirmed_bookings /
                                                $total_bookings) * 100
                                        );

                                }

                                echo $bookingPercentage;

                                ?>%

                            </span>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?php echo $bookingPercentage; ?>%;"
                            ></div>

                        </div>

                    </div>

                </div>


                <div class="overview-card">

                    <div class="card-heading">

                        <div>

                            <h3>
                                Ride Overview
                            </h3>

                            <p>
                                Current ride status
                            </p>

                        </div>

                    </div>


                    <div class="ride-overview-list">


                        <div class="ride-overview-row">

                            <span>
                                🟢 Available
                            </span>

                            <strong>
                                <?php echo $active_rides; ?>
                            </strong>

                        </div>


                        <div class="ride-overview-row">

                            <span>
                                📅 Upcoming
                            </span>

                            <strong>
                                <?php echo $upcoming_rides; ?>
                            </strong>

                        </div>


                        <div class="ride-overview-row">

                            <span>
                                ♀ Female-only
                            </span>

                            <strong>
                                <?php echo $female_rides; ?>
                            </strong>

                        </div>


                        <div class="ride-overview-row">

                            <span>
                                🔴 Cancelled
                            </span>

                            <strong>
                                <?php echo $cancelled_rides; ?>
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            <!-- RECENT BOOKINGS -->

            <div class="recent-card">

                <div class="card-heading">

                    <div>

                        <h3>
                            Recent Bookings
                        </h3>

                        <p>
                            Latest booking activity
                        </p>

                    </div>

                    <button
                        class="view-all-btn"
                        data-target="bookings-section"
                    >
                        View all →
                    </button>

                </div>


                <div class="recent-list">

                    <?php

                    if (
                        $recent_bookings &&
                        $recent_bookings->num_rows > 0
                    ) {

                        while (
                            $recent =
                            $recent_bookings->fetch_assoc()
                        ) {

                    ?>

                    <div class="recent-row">

                        <div class="recent-avatar">
                            <?php
                            echo strtoupper(
                                substr(
                                    $recent["student_name"],
                                    0,
                                    1
                                )
                            );
                            ?>
                        </div>


                        <div class="recent-info">

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $recent["student_name"]
                                );
                                ?>
                            </strong>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $recent["from_location"]
                                );
                                ?>

                                →

                                <?php
                                echo htmlspecialchars(
                                    $recent["to_location"]
                                );
                                ?>

                            </span>

                        </div>


                        <div class="recent-seats">

                            <?php
                            echo $recent["seats_booked"];
                            ?>

                            seat(s)

                        </div>


                        <span
                            class="status-badge <?php
                                echo statusClass(
                                    $recent["booking_status"]
                                );
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $recent["booking_status"]
                            );
                            ?>

                        </span>

                    </div>

                    <?php

                        }

                    } else {

                    ?>

                    <div class="empty-state">
                        No bookings available.
                    </div>

                    <?php

                    }

                    ?>

                </div>

            </div>

        </section>


        <!-- ================================================= -->
        <!-- USERS -->
        <!-- ================================================= -->

        <section
            id="users-section"
            class="data-section"
        >

            <div class="section-header">

                <div>

                    <span class="section-label">
                        MANAGEMENT
                    </span>

                    <h2>
                        Users
                    </h2>

                    <p>
                        Manage registered students, drivers and admins.
                    </p>

                </div>


                <div class="section-tools">

                    <input
                        type="text"
                        class="table-search"
                        data-table="usersTable"
                        placeholder="Search users..."
                    >

                    <button
                        class="export-btn"
                        data-table="usersTable"
                        data-name="users"
                    >
                        ↓ Export CSV
                    </button>

                </div>

            </div>


            <div class="table-card">

                <table id="usersTable">

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

                    <?php

                    if (
                        $users &&
                        $users->num_rows > 0
                    ) {

                        while (
                            $user =
                            $users->fetch_assoc()
                        ) {

                    ?>

                    <tr>

                        <td>
                            #<?php echo $user["id"]; ?>
                        </td>

                        <td>

                            <div class="person-cell">

                                <div class="person-avatar">

                                    <?php

                                    echo strtoupper(
                                        substr(
                                            $user["name"],
                                            0,
                                            1
                                        )
                                    );

                                    ?>

                                </div>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $user["name"]
                                    );
                                    ?>
                                </strong>

                            </div>

                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $user["email"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $user["phone"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $user["gender"]
                            );
                            ?>
                        </td>

                        <td>

                            <span class="role-badge">

                                <?php
                                echo htmlspecialchars(
                                    $user["role"]
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <?php

                            if (
                                $user["role"] !== "admin"
                            ) {

                            ?>

                            <button
                                class="delete-btn"
                                onclick="deleteUser(<?php echo $user['id']; ?>)"
                            >
                                Delete
                            </button>

                            <?php

                            } else {

                            ?>

                            <span class="protected-label">
                                Protected
                            </span>

                            <?php

                            }

                            ?>

                        </td>

                    </tr>

                    <?php

                        }

                    } else {

                    ?>

                    <tr>

                        <td colspan="7">
                            No users found.
                        </td>

                    </tr>

                    <?php

                    }

                    ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ================================================= -->
        <!-- RIDES -->
        <!-- ================================================= -->

        <section
            id="rides-section"
            class="data-section"
        >

            <div class="section-header">

                <div>

                    <span class="section-label">
                        MANAGEMENT
                    </span>

                    <h2>
                        Posted Rides
                    </h2>

                    <p>
                        Monitor all rides posted by drivers.
                    </p>

                </div>


                <div class="section-tools">

                    <input
                        type="text"
                        class="table-search"
                        data-table="ridesTable"
                        placeholder="Search rides..."
                    >

                    <button
                        class="export-btn"
                        data-table="ridesTable"
                        data-name="rides"
                    >
                        ↓ Export CSV
                    </button>

                </div>

            </div>


            <div class="table-card">

                <table id="ridesTable">

                    <thead>

                    <tr>

                        <th>ID</th>
                        <th>Driver</th>
                        <th>Route</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Seats</th>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Action</th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        $rides &&
                        $rides->num_rows > 0
                    ) {

                        while (
                            $ride =
                            $rides->fetch_assoc()
                        ) {

                    ?>

                    <tr>

                        <td>
                            #<?php echo $ride["id"]; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $ride["driver_name"]
                            );
                            ?>
                        </td>

                        <td>

                            <div class="route-cell">

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $ride["from_location"]
                                    );
                                    ?>
                                </span>

                                <b>→</b>

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $ride["to_location"]
                                    );
                                    ?>
                                </span>

                            </div>

                        </td>

                        <td>
                            <?php echo $ride["ride_date"]; ?>
                        </td>

                        <td>
                            <?php echo $ride["ride_time"]; ?>
                        </td>

                        <td>

                            <span class="seat-badge">

                                <?php
                                echo $ride["available_seats"];
                                ?>

                            </span>

                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $ride["vehicle_type"]
                            );
                            ?>
                        </td>

                        <td>

                            <?php

                            if (
                                (int)$ride["female_only"] === 1
                            ) {

                            ?>

                            <span class="type-badge female">
                                ♀ Female only
                            </span>

                            <?php

                            } elseif (
                                (int)$ride["recurring"] === 1
                            ) {

                            ?>

                            <span class="type-badge recurring">
                                ↻ Recurring
                            </span>

                            <?php

                            } else {

                            ?>

                            <span class="type-badge normal">
                                Normal
                            </span>

                            <?php

                            }

                            ?>

                        </td>

                        <td>

                            <span
                                class="status-badge <?php
                                    echo statusClass(
                                        $ride["status"]
                                    );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $ride["status"]
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <button
                                class="delete-btn"
                                onclick="deleteRide(<?php echo $ride['id']; ?>)"
                            >
                                Delete
                            </button>

                        </td>

                    </tr>

                    <?php

                        }

                    } else {

                    ?>

                    <tr>

                        <td colspan="10">
                            No rides found.
                        </td>

                    </tr>

                    <?php

                    }

                    ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ================================================= -->
        <!-- BOOKINGS -->
        <!-- ================================================= -->

        <section
            id="bookings-section"
            class="data-section"
        >

            <div class="section-header">

                <div>

                    <span class="section-label">
                        MANAGEMENT
                    </span>

                    <h2>
                        Bookings
                    </h2>

                    <p>
                        Monitor student ride bookings.
                    </p>

                </div>


                <div class="section-tools">

                    <input
                        type="text"
                        class="table-search"
                        data-table="bookingsTable"
                        placeholder="Search bookings..."
                    >

                    <button
                        class="export-btn"
                        data-table="bookingsTable"
                        data-name="bookings"
                    >
                        ↓ Export CSV
                    </button>

                </div>

            </div>


            <div class="table-card">

                <table id="bookingsTable">

                    <thead>

                    <tr>

                        <th>ID</th>
                        <th>Student</th>
                        <th>Route</th>
                        <th>Date</th>
                        <th>Seats</th>
                        <th>Status</th>
                        <th>Booked At</th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        $bookings &&
                        $bookings->num_rows > 0
                    ) {

                        while (
                            $booking =
                            $bookings->fetch_assoc()
                        ) {

                    ?>

                    <tr>

                        <td>
                            #<?php echo $booking["id"]; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking["student_name"]
                            );
                            ?>
                        </td>

                        <td>

                            <div class="route-cell">

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $booking["from_location"]
                                    );
                                    ?>
                                </span>

                                <b>→</b>

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $booking["to_location"]
                                    );
                                    ?>
                                </span>

                            </div>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $booking["ride_date"]
                            );
                            ?>

                            <br>

                            <small>
                                <?php
                                echo htmlspecialchars(
                                    $booking["ride_time"]
                                );
                                ?>
                            </small>

                        </td>

                        <td>
                            <?php
                            echo $booking["seats_booked"];
                            ?>
                        </td>

                        <td>

                            <span
                                class="status-badge <?php
                                    echo statusClass(
                                        $booking["booking_status"]
                                    );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $booking["booking_status"]
                                );
                                ?>

                            </span>

                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $booking["booked_at"]
                            );
                            ?>
                        </td>

                    </tr>

                    <?php

                        }

                    } else {

                    ?>

                    <tr>

                        <td colspan="7">
                            No bookings found.
                        </td>

                    </tr>

                    <?php

                    }

                    ?>

                    </tbody>

                </table>

            </div>

        </section>


        <footer class="admin-footer">

            <span>
                RideNest Admin Panel
            </span>

            <span>
                System Management
            </span>

        </footer>


    </main>

</div>


<script src="admin_dashboard.js"></script>

</body>

</html>

<?php

$conn->close();

?>