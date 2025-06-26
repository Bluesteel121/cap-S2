<?php
session_start();

if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

include "connect.php"; // Include your database connection file

$userEmail = ""; // Initialize variables
$userName = $_SESSION['name'];
$userUsername = "";
$userContact = "";
$successMessage = "";
$errorMessage = "";

// --- 1. Fetch existing user data ---
if (isset($_SESSION['username'])) { // Assuming you store username in session
    $loggedInUsername = $_SESSION['username'];

    // Prepare and execute a SELECT query to get user data
    $stmt = $conn->prepare("SELECT email, name, username, contact FROM accounts WHERE username = ?");
    if ($stmt === false) {
        // Handle prepare error
        $errorMessage = "Error fetching user data: " . $conn->error;
    } else {
        $stmt->bind_param("s", $loggedInUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $userData = $result->fetch_assoc();
            $userEmail = $userData['email'];
            // $userName is already set from session
            $userUsername = $userData['username'];
            $userContact = $userData['contact'];
        } else {
            // Handle case where user data is not found (shouldn't happen if login works)
            $errorMessage = "Error: User data not found.";
            // You might want to redirect or show an error
        }
        $stmt->close();
    }
} else {
    // Handle case where username is not in session (should be caught by initial check, but good practice)
    $errorMessage = "User not logged in.";
    // Redirect or handle appropriately
}

// --- 2. Handle form submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from the form
    $newEmail = $_POST['email'] ?? '';
    $newName = $_POST['name'] ?? '';
    $newUsername = $_POST['username'] ?? '';
    $newPassword = $_POST['password'] ?? ''; // Get the password (no hashing for now)
    $newContact = $_POST['contact'] ?? '';

    // Construct the UPDATE query
    $sql = "UPDATE accounts SET email = ?, name = ?, username = ?, contact = ?";
    $params = [$newEmail, $newName, $newUsername, $newContact];
    $types = "ssss";

    // Add password to update if it's not empty
    if (!empty($newPassword)) {
        $sql .= ", password = ?";
        $params[] = $newPassword;
        $types .= "s";
    }

    $sql .= " WHERE username = ?";
    $params[] = $loggedInUsername; // Use the username from the session for the WHERE clause
    $types .= "s";

    // Prepare and execute the UPDATE query
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
         $errorMessage = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $successMessage = "Profile updated successfully!";
            // Update session name if name was changed
            $_SESSION['name'] = $newName;
             // You might want to redirect after successful update
             // header("Location: loggedin_index.php");
             // exit();
        } else {
            $errorMessage = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Close the database connection
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Edit Profile - CNLRRS</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
    /* Added styles for the profile picture and sidebar */
    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
    }

    .sidebar {
      height: 100%;
      width: 0;
      position: fixed;
      z-index: 1;
      top: 0;
      left: 0;
      background-color: #115D5B; /* Using a color from your design */
      overflow-x: hidden;
      transition: 0.5s;
      padding-top: 60px;
      color: white; /* Text color for sidebar links */
    }

    .sidebar a {
      padding: 8px 8px 8px 32px;
      text-decoration: none;
      font-size: 25px;
      color: white; /* Text color for sidebar links */
      display: block;
      transition: 0.3s;
    }

    .sidebar a:hover {
      background-color: #103625; /* A darker shade for hover effect */
    }

    .sidebar .closebtn {
      position: absolute;
      top: 0;
      right: 25px;
      font-size: 36px;
      margin-left: 50px;
      color: white; /* Color for the close button */
    }
  </style>
</head>

<body class="bg-gray-100 pt-20">

  <!-- Header -->
<header id="main-header" class="bg-white text-[#103625] px-6 py-3 shadow-lg fixed top-0 left-0 w-full z-50 transition-transform duration-300">
  <div class="container mx-auto flex justify-between items-center">
    <!-- Logo and Title -->
    <div class="flex items-center space-x-4">
      <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-20 w-auto object-contain">
      <h1 class="text-xl font-bold  leading-tight">Camarines Norte Lowland <br class="hidden sm:block" /> Rainfed Research Station</h1>
    </div>



    <img src="Images/Ph.png" alt="PH Logo" class="h-20 w-auto object-contain">
    <img src="Images/Da.png" alt="DA Logo" class="h-20 w-auto object-contain">
      <div class="text-center md:text-middle mt-2 md:mt-0">
        <p class="text-sm font-bold">DEPARTMENT OF AGRICULTURE</p>
        <p class="text-xs font-bold">Address: Calasgasan, Daet, Philippines</p>
        <p class="text-xs font-bold">Email: dacnlrrs@gmail.com</p>
        <p class="text-xs font-bold">Contact No.:  0951 609 9599</p>
      </div>

      <img src="Images/Bago.png" alt="Bagong Pilipinas Logo" class="h-20 w-auto object-contain">
  </div>
</header>

   <nav class="bg-white border-b py-10 px-10">
  <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-between items-center gap-4">

    <!-- Search with Advance -->
    <div class="flex flex-grow max-w-xl border border-green-900 rounded-full overflow-hidden">
      <input
        type="text"
        placeholder="Search publications, articles, keywords, etc."
        class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none"
      />
      <button class="px-4 text-green-900 text-xl">
        🔍
      </button>
      <button class="bg-transparent px-4 font-semibold text-green-900 hover:underline">
        Advance
      </button>
    </div>

    <!-- Navigation Links -->
    <div class="flex items-center gap-6 text-green-900 font-semibold text-sm">
      <a href="loggedin_index.php" class="hover:underline">Home</a>
      <a href="#" class="hover:underline">Our Services</a>
      <a href="#" class="hover:underline">About Us</a>
    </div>

    <!-- Profile Picture (replaces Log In Button) -->
    <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['name'], 0, 1)); ?>.png" alt="Profile Picture" class="profile-pic" onclick="toggleSidebar()">
  </div>
</nav>

    <!-- Sidebar -->
    <div id="mySidebar" class="sidebar">
        <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
        <a href="#">Settings</a>
        <a href="edit_profile.php">Profile</a>
        <a href="index.php">Log Out</a>
    </div>


  <!-- Main Content for Profile Editing -->
  <section class="container mx-auto my-8 px-4">
      <h2 class="text-3xl font-bold text-center text-[#115D5B] mb-8">Edit Profile</h2>

      <div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
          <?php if (!empty($successMessage)): ?>
              <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                  <strong class="font-bold">Success!</strong>
                  <span class="block sm:inline"><?php echo $successMessage; ?></span>
              </div>
          <?php endif; ?>
          <?php if (!empty($errorMessage)): ?>
              <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                  <strong class="font-bold">Error!</strong>
                  <span class="block sm:inline"><?php echo $errorMessage; ?></span>
              </div>
          <?php endif; ?>
          <form action="" method="POST">
              <div class="mb-4">
                  <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                  <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
              </div>
              <div class="mb-4">
                  <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name:</label>
                  <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userName); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
              </div>
              <div class="mb-4">
                  <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                  <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userUsername); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
              </div>
              <div class="mb-4">
                  <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                  <input type="password" id="password" name="password" placeholder="Leave blank to keep current password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
              </div>
              <div class="mb-6">
                  <label for="contact" class="block text-gray-700 text-sm font-bold mb-2">Contact:</label>
                  <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($userContact); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
              </div>
              <div class="flex items-center justify-between">
                  <button type="submit" class="bg-green-900 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                      Save Changes
                  </button>
              </div>
          </form>
      </div>
  </section>


  <!-- Footer -->
  <footer class="bg-[#115D5B] text-white text-center py-4">
    <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
  </footer>

  <!-- Scripts -->
  <script>
    // Hamburger toggle (kept for consistency, though not used with this nav structure)
    const menuToggle = document.getElementById('menu-toggle');
    const navMenu = document.getElementById('nav-menu');
    if(menuToggle && navMenu){
        menuToggle.addEventListener('click', () => {
          navMenu.classList.toggle('hidden');
        });
    }


    // Hide header on scroll down, show on scroll up
    let lastScrollTop = 0;
    const header = document.getElementById('main-header');

    window.addEventListener('scroll', () => {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      if (scrollTop > lastScrollTop) {
        header.style.transform = 'translateY(-100%)'; // hide
      } else {
        header.style.transform = 'translateY(0)'; // show
      }
      lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // For Mobile
    });


    // Sidebar toggle functionality
    function toggleSidebar() {
        const sidebar = document.getElementById('mySidebar');
        if (sidebar.style.width === '250px') {
            sidebar.style.width = '0';
        } else {
            sidebar.style.width = '250px';
        }
    }

    function closeSidebar() {
        document.getElementById('mySidebar').style.width = '0';
    }
  </script>
</body>
</html>