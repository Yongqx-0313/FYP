<?php
session_start(); // Start the session

// Ensure user is logged in
if (!isset($_SESSION['user']['UserID'])) {
    die("Access denied. Please log in.");
}

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
$userID = $_SESSION['user']['UserID'];
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

// Handle form submission for updating profile
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['username']);
    $email = trim($_POST['useremail']);
    $password = trim($_POST['password']);

    // Validate input fields
    if (empty($name) || empty($email) || empty($password)) {
        echo "<script>alert('All fields are required.');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.');</script>";
    } else {
        // Update user details (without hashing for local testing)
        $sql = "UPDATE user SET Name = ?, Email = ?, Password = ? WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $password, $userID);
        
        if ($stmt->execute()) {
            echo "<script>alert('Profile updated successfully!'); window.location.href = 'Profile.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating profile.');</script>";
        }
        $stmt->close();
    }
}

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

<body class="transition-colors duration-500 bg-gradient-to-b from-yellow-100 to-yellow-50 min-h-screen">
    <!-- Header -->
    <?php include 'header.php'; ?>

    <div class="flex justify-center items-center py-10">
        <main class="bg-white shadow-lg rounded-lg p-6 max-w-lg w-full">
            <h2 class="text-2xl font-semibold text-gray-800 text-center mb-4">Edit Profile</h2>

            <div class="flex flex-col items-center space-y-6">
                <!-- Profile Image -->
                <div class="w-28 h-28">
                    <img src="img/studentprofile.png" alt="profile-picture" class="rounded-full border-4 border-gray-300 shadow-lg w-full h-full object-cover">
                </div>

                <!-- Edit Profile Form -->
                <form action="" method="POST" class="w-full space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="username" id="username" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"
                               value="<?php echo htmlspecialchars($userData['Name'] ?? ''); ?>" required />
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="useremail" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="useremail" id="useremail" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"
                               value="<?php echo htmlspecialchars($userData['Email'] ?? ''); ?>" required />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"
                                   value="<?php echo htmlspecialchars($userData['Password'] ?? ''); ?>" required />
                            <button type="button" onclick="togglePasswordVisibility('password')"
                                    class="absolute inset-y-0 right-3 flex items-center text-gray-500">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-center">
                        <button type="submit"
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow-md hover:bg-blue-700 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        function togglePasswordVisibility(inputId) {
            var passwordInput = document.getElementById(inputId);
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }
    </script>
</body>

</html>
