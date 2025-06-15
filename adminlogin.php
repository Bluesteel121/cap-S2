<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


ob_start();

session_start();


function debugLog($message) {
    error_log($message);
   
    file_put_contents('admin_login.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

include "connect.php"; 

include "admin_login_handler.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#1E3A34] flex justify-center items-center h-screen">

    <div id="main-container" class="bg-white p-8 rounded-lg shadow-lg text-center w-96 relative">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16">
        <h2 class="text-2xl font-bold mt-4">ADMINISTRATOR</h2>
        <p class="text-gray-500 mt-2">Admin</p>

        <?php
        // Display login errors if any
        if (isset($_SESSION['login_error'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo htmlspecialchars($_SESSION['login_error']);
            echo '</div>';
            // Clear the error after displaying
            unset($_SESSION['login_error']);
        }
        ?>

        <button onclick="showSection('developers')" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700">Head Admin</button>
        <button onclick="showSection('staff')" class="bg-green-500 text-white w-full py-2 mt-2 rounded-lg hover:bg-green-700">Staff</button>
        
        <!-- Back Button for Main Container -->
        <div class="mt-6 text-center">
            <a href="account.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200">
                ← Back to Account Selection
            </a>
        </div>
    </div>

    <div id="developers-container" class="bg-white p-8 rounded-lg shadow-lg text-center w-96 hidden relative">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16">
        <h2 class="text-2xl font-bold mt-4">Developers</h2>
        <p class="text-gray-500 mt-2">Admin</p>

        <form method="POST" action="admin_login_handler.php">
            <input type="text" name="username" placeholder="Username" class="border w-full px-4 py-2 rounded-lg mt-2" required>
            <input type="password" name="password" placeholder="Password" class="border w-full px-4 py-2 rounded-lg mt-2" required>
            <input type="hidden" name="role" value="dev">
            <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700">Login</button>
        </form>
        
        <!-- Back Button for Developers -->
        <div class="mt-6 text-center">
            <button onclick="showSection('main')" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200">
                ← Back to Admin Selection
            </button>
        </div>
    </div>

    <div id="staff-container" class="bg-white p-8 rounded-lg shadow-lg text-center w-96 hidden relative">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16">
        <h2 class="text-2xl font-bold mt-4">Staff</h2>
        <p class="text-gray-500 mt-2">Admin</p>

        <form method="POST" action="admin_login_handler.php">
            <input type="text" name="username" placeholder="Username" class="border w-full px-4 py-2 rounded-lg mt-2" required>
            <input type="password" name="password" placeholder="Password" class="border w-full px-4 py-2 rounded-lg mt-2" required>
            <input type="hidden" name="role" value="staff">
            <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700">Login</button>
        </form>
        
        <!-- Back Button for Staff -->
        <div class="mt-6 text-center">
            <button onclick="showSection('main')" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200">
                ← Back to Admin Selection
            </button>
        </div>
    </div>

    <script>
    function showSection(section) {
        document.getElementById("main-container").classList.add("hidden");
        document.getElementById("developers-container").classList.add("hidden");
        document.getElementById("staff-container").classList.add("hidden");

        if (section === "developers") {
            document.getElementById("developers-container").classList.remove("hidden");
        } else if (section === "staff") {
            document.getElementById("staff-container").classList.remove("hidden");
        } else if (section === "main") {
            document.getElementById("main-container").classList.remove("hidden");
        }
    }
    </script>
</body>
</html>