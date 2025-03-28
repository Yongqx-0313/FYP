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

// Handle registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if all required fields are set and not empty
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {
        // Collect form data
        $name = htmlspecialchars($_POST['name']); // Sanitize input
        $email = htmlspecialchars($_POST['email']);
        $password = $_POST['password']; // No hashing for local testing

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email format";
            exit;
        }

        // Insert into User table
        $userSql = "INSERT INTO user (Name, Email, Password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($userSql);
        if ($stmt) {
            $stmt->bind_param("sss", $name, $email, $password);
            if ($stmt->execute()) {
                echo "<script>
                alert('User registered successfully!');
                window.location.href = '../FYP Interface/Log In.html';
                </script>";
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        echo "All fields are required";
    }
}

$conn->close();
?>
