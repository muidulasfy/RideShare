if ($password == $user['password']) {

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    header("Location: admin_dashboard.php");
    exit;

} else {

    die("Invalid admin email or password.");

}