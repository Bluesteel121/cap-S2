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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    
    debugLog("Database Connection Status: " . ($conn ? "Connected" : "Failed"));
    debugLog("Attempting login - Username: $username, Role: $role");

    try {
        
        $all_users_query = "SELECT * FROM accounts";
        $all_users_result = $conn->query($all_users_query);
        
        debugLog("Total users in database: " . $all_users_result->num_rows);
        
       
        while ($user = $all_users_result->fetch_assoc()) {
            debugLog("Existing User - Username: " . $user['username'] . ", Role: " . $user['role']);
        }

        
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ? AND role = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        debugLog("Query result rows: " . $result->num_rows);

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
           
            debugLog("Stored Password: " . $row["password"]);
            debugLog("Submitted Password: " . $password);

            if ($password === $row["password"]) {
                $_SESSION["username"] = $username;
                $_SESSION["role"] = $role;
                
                debugLog("Password match. Redirecting to index.php");
                
                ob_clean();
                header("Location: adminpage.php");
                exit();
            } else {
                debugLog("Password mismatch for user: $username");
                $_SESSION['login_error'] = "Invalid password";
                header("Location: adminlogin.php");
                exit();
            }
        } else {
            debugLog("No matching user found for Username: $username, Role: $role");
            $_SESSION['login_error'] = "Invalid username or role";
            header("Location: adminlogin.php");
            exit();
        }
    } catch (Exception $e) {
        debugLog("Exception: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login";
        header("Location: adminlogin.php");
        exit();
    }
}
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

        <form method="POST" action="adminlogin.php">
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

        <form method="POST" action="adminlogin.php">
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