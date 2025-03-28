<?php
session_start();
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "fyp";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit("Unauthorized access");
}

if (!isset($_GET['file_id'])) {
    http_response_code(400);
    exit("File ID is required");
}

$fileID = intval($_GET['file_id']);

$stmt = $conn->prepare("SELECT FileName, UserFile FROM file WHERE FileID = ?");
$stmt->bind_param("i", $fileID);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($fileName, $fileContent);

if ($stmt->num_rows === 0) {
    http_response_code(404);
    exit("File not found");
}

$stmt->fetch();
$stmt->close();
$conn->close();

// Send file as a response
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"$fileName\"");
echo $fileContent;
exit;
?>
