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
    $newFolderName = $_POST['new_folder_name'];
    $userID = $_SESSION['user']['UserID'];

    $stmt = $conn->prepare("UPDATE folder SET FolderName = ? WHERE FolderID = ? AND UserID = ?");
    $stmt->bind_param("sii", $newFolderName, $folderID, $userID);

    if ($stmt->execute()) {
        header('Location: ../FYP Interface/Main.php');
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
