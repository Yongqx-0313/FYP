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

// Check if user is logged in
if (!isset($_SESSION['user'])) {
  header('Location: login.php'); // Redirect to login if not logged in
  exit;
}

$userID = $_SESSION['user']['UserID'];

// Fetch folders for the logged-in user
$query = "SELECT * FROM folder WHERE UserID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$folders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
  <link href="../src/output.css" rel="stylesheet">
  <link rel="stylesheet" href="../src/main.css">
  <script src="js/searchfolder.js"></script>
  <title>AI-Driven Use Case Diagram Consistency Checker</title>
</head>

<body class="bg-gray-100 text-gray-800 font-sans">
  <!-- Header -->
  <?php include 'header.php' ?>

  <!-- Hero Section -->
  <section class="bg-gray-300 text-black py-20">
    <div class="container mx-auto px-4 text-center">
      <h2 class="text-4xl font-extrabold mb-4">Simplify Consistency Checking</h2>
      <p class="text-lg mb-6">Automatically validate your use case diagrams against written requirements with AI-powered precision.</p>
      <a href="#" class="bg-white text-blue-600 px-6 py-3 rounded shadow-lg hover:bg-gray-100">
        Get Started
      </a>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 text-center">
      <h3 class="text-3xl font-bold mb-6">Features</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white shadow rounded p-6">
          <h4 class="text-xl font-semibold mb-3">Upload & Analyze</h4>
          <p>Upload documents and diagrams, and let the AI perform consistency checks instantly.</p>
        </div>
        <div class="bg-white shadow rounded p-6">
          <h4 class="text-xl font-semibold mb-3">Detailed Reports</h4>
          <p>Get comprehensive feedback on inconsistencies and actionable insights for improvements.</p>
        </div>
        <div class="bg-white shadow rounded p-6">
          <h4 class="text-xl font-semibold mb-3">Easy-to-Use</h4>
          <p>An intuitive interface designed for students, lecturers, and professionals alike.</p>
        </div>
      </div>
    </div>
  </section>
  <!-- Folder Section -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold mb-6">Manage Folders</h2>
      <div class="flex justify-between items-center mb-6">
        <p class="text-lg">Create folders to upload files and perform consistency checks.</p>
        <form action="../php/createfolder.php" method="POST" class="inline">
          <input type="text" name="folder_name" placeholder="Folder Name" required class="border px-2 py-2 rounded">
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-500">+ Create Folder</button>
        </form>
      </div>
      <!-- Folder Table -->
        <!-- Search Input -->
        <input type="search" id="tablesearch" name="tablesearch"
          placeholder="Search folder name"
          class="mb-4 flex-grow px-4 py-2 text-gray-700 rounded-full border border-black focus:outline-none focus:ring-2 focus:ring-blue-400"
          oninput="filterTable()" onkeypress="return event.keyCode !== 13">


      <div class="overflow-x-auto bg-white shadow rounded">
        <table class="table-auto w-full text-left" id="foldertable">
          <thead class="bg-blue-600 text-white">
            <tr>
              <th class="px-4 py-2">No.</th>
              <th class="px-4 py-2 text-center">Folder Name</th>
              <th class="px-4 py-2 text-center">CreateDate</th>
              <th class="px-4 py-2 text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($folders as $index => $folder): ?>
              <tr>
                <td class="px-4 py-2"><?= $index + 1 ?></td>
                <td class="px-4 py-2 text-center">
                  <a href="viewfile.php?folder_id=<?= $folder['FolderID'] ?>" class="text-blue-500 hover:underline">
                    <?= htmlspecialchars($folder['FolderName']) ?>
                  </a>
                </td>
                <td class="text-center">
                  <?php echo htmlspecialchars($folder['CreateDate']); ?>
                </td>
                <td class="px-4 py-2 text-center flex justify-center">
                  <form action="../php/renamefolder.php" method="POST" class="flex">
                    <input type="hidden" name="folder_id" value="<?= $folder['FolderID'] ?>">
                    <input type="text" name="new_folder_name" placeholder="Rename" required class="border px-2 py-1 rounded">
                    <button type="submit" class="bg-orange-400 text-black px-4 py-1 rounded ml-4">Rename</button>
                  </form>
                  <form action="../php/deletefolder.php" method="POST" class="flex ">
                    <input type="hidden" name="folder_id" value="<?= $folder['FolderID'] ?>">
                    <button type="submit" class="bg-red-500 text-black px-4 py-2 rounded ml-8" onclick="return confirm('Delete this folder?');">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section id="how-it-works" class="py-16 bg-white">
    <div class="container mx-auto px-4">
      <h3 class="text-3xl font-bold mb-6 text-center">How It Works</h3>
      <div class="flex flex-col md:flex-row items-center gap-10">
        <div class="flex-1">
          <ol class="list-decimal ml-8 space-y-4 text-lg">
            <li>Register and log in to your account.</li>
            <li>Upload your use case diagrams and requirements document.</li>
            <li>Run automated consistency checks using the AI tool.</li>
            <li>Download detailed analysis reports with suggestions.</li>
          </ol>
        </div>
        <div class="flex-1 ">
          <img src="how to work.png" alt="How It Works" class="rounded shadow lg:ml-20 xl:ml-40">
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'footer.php' ?>
</body>

</html>