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
    $folderID = $_POST['folder_id'];
    $userID = $_SESSION['user']['UserID'];

    // Delete files in the folder first (if any)
    $stmt = $conn->prepare("DELETE FROM file WHERE FolderID = ?");
    $stmt->bind_param("i", $folderID);
    $stmt->execute();
    $stmt->close();

    // Delete the folder
    $stmt = $conn->prepare("DELETE FROM folder WHERE FolderID = ? AND UserID = ?");
    $stmt->bind_param("ii", $folderID, $userID);

    if ($stmt->execute()) {
        header('Location: ../FYP Interface/Main.php');
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
