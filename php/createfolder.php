<?php
session_start();
$servername = "localhost";
$username = "root"; // Default username for local
$password = ""; // Default password for local
$dbname = "fyp"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folderName = $_POST['folder_name'];
    $userID = $_SESSION['user']['UserID'];

    $stmt = $conn->prepare("INSERT INTO folder (FolderName, CreateDate, UserID) VALUES (?, NOW(), ?)");
    $stmt->bind_param("si", $folderName, $userID);

    if ($stmt->execute()) {
        header('Location: ../FYP Interface/Main.php');
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
