<?php
// Database connection
$servername = "localhost";
$username = "root"; // Default username for XAMPP
$password = ""; // Default password for XAMPP
$dbname = "fyp"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect form data
    $email = $_POST['user-email'];
    $password = $_POST['user-password'];

    // Query to validate user credentials
    $sql = "SELECT * FROM user WHERE Email = ? AND Password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found, start session
        session_start();
        $user = $result->fetch_assoc();
        $_SESSION['user'] = $user;

        // Redirect to student dashboard
        header("Location: ../FYP Interface/Main.php");
        exit();
    } else {
        // Invalid credentials
        echo "<script>alert('Invalid email or password. Please try again.'); window.location.href='../FYP Interface/Log In.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
