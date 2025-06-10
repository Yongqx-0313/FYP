<?php
session_start();
$servername = "localhost";
$username = "root";
$password = ""; // Default password for local
$dbname = "fyp";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// if (!isset($_SESSION['user'])) {
//     header('Location: Log In.html');
//     exit;
// }

$folderID = $_GET['folder_id'];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['custom-file-upload'])) {
    $file = file_get_contents($_FILES['custom-file-upload']['tmp_name']); // File content
    $fileName = $_FILES['custom-file-upload']['name']; // File name
    $uploadDate = date("Y-m-d"); // Current date

    // Save the file content and metadata into the database
    $stmt = $conn->prepare("INSERT INTO file (FileName, FolderID, UploadDate, UserFile) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $fileName, $folderID, $uploadDate, $file);
    $stmt->execute();
    $stmt->close();

    // Redirect to the same page (PRG pattern)
    header("Location: ?folder_id=$folderID");
    exit;
}

// Handle file rename
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_file_id'])) {
    $renameFileID = $_POST['rename_file_id'];
    $newFileName = $_POST['new_file_name'];

    $stmt = $conn->prepare("UPDATE file SET FileName = ? WHERE FileID = ?");
    $stmt->bind_param("si", $newFileName, $renameFileID);
    $stmt->execute();
    $stmt->close();

    // Redirect to the same page (PRG pattern)
    header("Location: ?folder_id=$folderID");
    exit;
}

// Handle file delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file_id'])) {
    $deleteFileID = $_POST['delete_file_id'];

    $stmt = $conn->prepare("DELETE FROM file WHERE FileID = ?");
    $stmt->bind_param("i", $deleteFileID);
    $stmt->execute();
    $stmt->close();

    // Redirect to the same page (PRG pattern)
    header("Location: ?folder_id=$folderID");
    exit;
}

// Fetch files for the folder
$stmt = $conn->prepare("SELECT * FROM file WHERE FolderID = ?");
$stmt->bind_param("i", $folderID);
$stmt->execute();
$result = $stmt->get_result();
$files = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/main.css">
    <link rel="stylesheet" href="../src/file.css">
    <script src="js/searchfile.js"></script>
    <title>Files in Folder</title>
</head>

<body class="transition-colors duration-500 bg-gradient-to-b from-yellow-100 to-yellow-50">
    <!-- Header Section -->
    <?php include 'header.php'; ?>
    <section class="py-16 ">
        <div class="container mx-auto px-4 ">
            <h2 class="text-3xl font-bold mb-6">Files</h2>

            <!-- File Upload Form -->
            <!-- <div class="mb-6">
                <form action="" method="POST" enctype="multipart/form-data" class="bg-white shadow rounded p-4">
                    <label for="custom-file-upload" class="block text-lg font-semibold mb-2">Upload File:</label>
                    <input type="file" name="custom-file-upload" id="custom-file-upload" class="block w-full mb-4 border border-gray-300 rounded p-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Upload</button>
                </form>
            </div> -->

            <!-- File List -->
            <div class="overflow-x-auto bg-white shadow px-2 py-1 rounded-lg">
                <div class="flex justify-between">
                    <div><input type="search" id="tablesearch" name="tablesearch"
                            placeholder="Search file name"
                            class=" mb-4 flex-grow px-4 py-2 text-gray-700 rounded-full border border-black focus:outline-none focus:ring-2 focus:ring-blue-400"
                            oninput="filterTable()" onkeypress="return event.keyCode !== 13"></div>
                    <div><a href="http://127.0.0.1:5000/?folder_id=<?= $folderID ?>">
                            <button type="button" class="mt-2 bg-lime-500 text-black px-3 py-1 rounded">Check UCD</button>
                        </a>
                    </div>
                </div>

                <table class="table-auto w-full text-left" id="filetable">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-2">No.</th>
                            <th class="px-4 py-2">File Name</th>
                            <th class="px-4 py-2">Upload Date</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $index => $file): ?>
                            <tr>
                                <td class="px-4 py-2"><?= $index + 1 ?></td>
                                <td class="px-4 py-2">
                                    <a href="DownloadFile.php?id=<?= htmlspecialchars($file['FileID']) ?>"
                                        class="text-blue-600 underline hover:text-blue-800">
                                        <?= htmlspecialchars($file['FileName']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2"><?= htmlspecialchars($file['UploadDate']) ?></td>
                                <td class="px-4 py-2">
                                    <!-- Rename File -->
                                    <form action="" method="POST" class="inline">
                                        <input type="hidden" name="rename_file_id" value="<?= $file['FileID'] ?>">
                                        <input type="text" name="new_file_name" placeholder="New Name" required class="border border-gray-300 rounded px-2 py-1">
                                        <button type="submit" class="bg-yellow-500 text-black px-3 py-1 rounded">Rename</button>
                                    </form>
                                    <!-- Delete File -->
                                    <form action="" method="POST" class="inline">
                                        <input type="hidden" name="delete_file_id" value="<?= $file['FileID'] ?>">
                                        <button type="submit" class="bg-red-500 text-black px-3 py-1 rounded" onclick="return confirm('Delete this file?');">Delete</button>
                                    </form><br />
                                    <!-- <a href="spelling/spelling.php?file_id=<?= $file['FileID'] ?>">
                                        <button type="button" class="mt-2 bg-lime-500 text-black px-3 py-1 rounded">Spelling Check</button>
                                    </a>

                                    <a href="ConsistencyCheck/testgooglemachine.php"><button type="button" class="mt-2 bg-orange-500 text-black px-3 py-1 rounded">Consistency Check</button></a> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php include 'footer.php'; ?>
</body>

</html>