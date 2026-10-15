
<?php

include "db.php";

header("Content-Type: application/json");


$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$gender = $_POST['gender'] ?? '';
$role = $_POST['role'] ?? '';
if ($role !== "user" && $role !== "driver") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid registration type."
    ]);

    exit;
}

if (
    $name === '' ||
    $email === '' ||
    $phone === '' ||
    $password === '' ||
    $gender === '' ||
    $role === ''
) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);

    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    exit;
}


if (strlen($password) < 6) {

    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters."
    ]);

    exit;
}


$checkEmail = $conn->prepare(
    "SELECT id FROM users WHERE email = ?"
);

$checkEmail->bind_param("s", $email);

$checkEmail->execute();

$emailResult = $checkEmail->get_result();


if ($emailResult->num_rows > 0) {

    echo json_encode([
        "success" => false,
        "message" => "This email is already registered."
    ]);

    exit;
}


$checkPhone = $conn->prepare(
    "SELECT id FROM users WHERE phone = ?"
);

$checkPhone->bind_param("s", $phone);

$checkPhone->execute();

$phoneResult = $checkPhone->get_result();


if ($phoneResult->num_rows > 0) {

    echo json_encode([
        "success" => false,
        "message" => "This phone number is already registered."
    ]);

    exit;
}


$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($role === "user") {

    $department = trim($_POST['department'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $checkStudent = $conn->prepare(
    "SELECT id FROM users WHERE student_id = ?"
);

$checkStudent->bind_param("s", $student_id);

$checkStudent->execute();

$studentResult = $checkStudent->get_result();

if ($studentResult->num_rows > 0) {

    echo json_encode([
        "success" => false,
        "message" => "This Student ID is already registered."
    ]);

    exit;
}


    if ($department === '' || $student_id === '') {

        echo json_encode([
            "success" => false,
            "message" => "Department and Student ID are required."
        ]);

        exit;
    }


    $sql = $conn->prepare(
        "INSERT INTO users
        (
            name,
            email,
            phone,
            department,
            student_id,
            password,
            gender,
            role
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $sql->bind_param(
        "ssssssss",
        $name,
        $email,
        $phone,
        $department,
        $student_id,
        $hashedPassword,
        $gender,
        $role
    );


    if ($sql->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Student registration successful!"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Student registration failed."
        ]);
    }

}


else if ($role === "driver") {

    $license = trim($_POST['license'] ?? '');
    $vehicle = trim($_POST['vehicle'] ?? '');
    $vehicleType = trim($_POST['vehicleType'] ?? '');
    $model = trim($_POST['model'] ?? '');


    if (
        $license === '' ||
        $vehicle === '' ||
        $vehicleType === '' ||
        $model === ''
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Please fill in all driver information."
        ]);

        exit;
    }


    /* Check license */

    $checkLicense = $conn->prepare(
        "SELECT id FROM driver_details
         WHERE license_number = ?"
    );

    $checkLicense->bind_param("s", $license);

    $checkLicense->execute();

    $licenseResult = $checkLicense->get_result();


    if ($licenseResult->num_rows > 0) {

        echo json_encode([
            "success" => false,
            "message" => "This driving license is already registered."
        ]);

        exit;
    }


    /* Check vehicle number */

    $checkVehicle = $conn->prepare(
        "SELECT id FROM driver_details
         WHERE vehicle_number = ?"
    );

    $checkVehicle->bind_param("s", $vehicle);

    $checkVehicle->execute();

    $vehicleResult = $checkVehicle->get_result();


    if ($vehicleResult->num_rows > 0) {

        echo json_encode([
            "success" => false,
            "message" => "This vehicle number is already registered."
        ]);

        exit;
    }


    $conn->begin_transaction();


    try {

        /* Create user account */

        $sql = $conn->prepare(
            "INSERT INTO users
            (
                name,
                email,
                phone,
                password,
                gender,
                role
            )
            VALUES (?, ?, ?, ?, ?, ?)"
        );


        $sql->bind_param(
            "ssssss",
            $name,
            $email,
            $phone,
            $hashedPassword,
            $gender,
            $role
        );


        if (!$sql->execute()) {
            throw new Exception("Driver account could not be created.");
        }


        $driver_id = $conn->insert_id;


        /* Save driver information */

        $driverSql = $conn->prepare(
            "INSERT INTO driver_details
            (
                user_id,
                license_number,
                vehicle_number,
                vehicle_type,
                vehicle_model
            )
            VALUES (?, ?, ?, ?, ?)"
        );


        $driverSql->bind_param(
            "issss",
            $driver_id,
            $license,
            $vehicle,
            $vehicleType,
            $model
        );


        if (!$driverSql->execute()) {
            throw new Exception("Driver details could not be saved.");
        }


        $conn->commit();


        echo json_encode([
            "success" => true,
            "message" => "Driver registration successful!"
        ]);

    }

    catch (Exception $e) {

        $conn->rollback();

        error_log($e->getMessage());

        echo json_encode([
            "success" => false,
            "message" => "Driver registration failed. Please try again."
        ]);
    }

}


else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid registration type."
    ]);
}

$conn->close();

?>
