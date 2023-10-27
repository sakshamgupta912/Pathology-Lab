<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: index.php"); // Redirect to the login page if not logged in
    exit();
}

// Check if the user's role 
if ($_SESSION['role'] !== 'admin') {
    echo "Access denied. You do not have permission to access this page.";
    header("Location: index.php"); // Redirect to the login page if not logged in

    exit();
} ?>
<?php
session_start();
require 'connection.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } elseif ($user['role'] === 'pathologist') {
            header("Location: pathologist.php");
        } else {
            echo "Invalid role!";
        }
    } else {
        echo "Invalid username or password";
    }
}
?>
