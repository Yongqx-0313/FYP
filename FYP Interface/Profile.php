<?php
session_start(); // Start the session

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fyp";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user information
$userID = $_SESSION['user']['UserID']; // UserID is already stored in the session
$userData = [];


$sql = "SELECT * FROM user WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
    <link href="../src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/main.css">
    <link rel="stylesheet" href="../src/profile.css">
</head>


<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <?php include 'header.php' ?>

    <!-- Main Section -->
    <div class="flex justify-center items-center py-10 profile">
        <main class="bg-white shadow-md rounded-lg p-6 max-w-3xl w-full">
            <!-- Profile Container -->
            <div class="flex flex-col md:flex-row items-center space-y-6 md:space-y-0 md:space-x-8">
                <!-- Profile Image -->
                <div class="w-32 h-32">
                    <img src="img/studentprofile.png" alt="profile-picture" class="rounded-full border-4 border-gray-300 shadow-lg w-full h-full object-cover">
                </div>

                <!-- Profile Details -->
                <div class="flex flex-col space-y-2 text-center md:text-left">
                    <h2 class="text-2xl font-semibold text-gray-800"><?php echo ($userData['Name'] ?? ''); ?></h2>
                    <p class="text-gray-600">
                        <strong>Email: </strong>
                        <a href="mailto:<?php echo ($userData['Email'] ?? ''); ?>" class="text-blue-500 hover:underline">
                            <?php echo ($userData['Email'] ?? ''); ?>
                        </a>
                    </p>

                    <!-- Edit Profile Button -->
                    <div class="mt-4">
                        <a href="EditProfile.php">
                            <button class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow-md hover:bg-blue-700 transition">
                                Edit Profile
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <?php include 'footer.php' ?>
</body>


</html>