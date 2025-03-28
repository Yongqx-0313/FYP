<?php
$conn = new mysqli("localhost", "root", "", "fyp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$fileID = $_GET['id'] ?? null;

if ($fileID) {
    $stmt = $conn->prepare("SELECT UserFile, FileName FROM file WHERE FileID = ?");
    $stmt->bind_param("i", $fileID);
    $stmt->execute();
    $stmt->bind_result($fileContent, $fileName);
    $stmt->fetch();
    $stmt->close();

    if ($fileContent) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\"");
        echo $fileContent;
        exit;
    }
}

echo "No file found.";
$conn->close();
?>
