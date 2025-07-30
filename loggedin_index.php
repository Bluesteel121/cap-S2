<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CNLRRS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Profile picture styles */
    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
      border: 2px solid #115D5B;
    }

    .sidebar {
      height: 100%;
      width: 0;
      position: fixed;
      z-index: 1000;
      top: 0;
      left: 0;
      background-color: #115D5B;
      overflow-x: hidden;
      transition: 0.5s;
      padding-top: 60px;
      color: white;
    }

    .sidebar a {
      padding: 8px 8px 8px 32px;
      text-decoration: none;
      font-size: 25px;
      color: white;
      display: block;
      transition: 0.3s;
    }

    .sidebar a:hover {
      background-color: #103625;
    }

    .sidebar .closebtn {
      position: absolute;
      top: 0;
      right: 25px;
      font-size: 36px;
      margin-left: 50px;
      color: white;
    }
  </style>
</head>

<body class="bg-white pt-20">

  <!-- Header -->
<header id="main-header" class="bg-white text-[#103625] px-6 py-3 shadow-lg fixed top-0 left-0 w-full z-50 transition-transform duration-300">
 <div class="w-full mt-2 mx-auto flex justify-between items-center">
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
      <a href="#" class="hover:underline">Home</a>
      <a href="#" class="hover:underline">Our Services</a>
      <a href="#" class="hover:underline">About Us</a>
      <!-- Add My Submissions Link -->
      <a href="my_submissions.php" class="hover:underline flex items-center">
        <i class="fas fa-file-alt mr-1"></i>My Submissions
      </a>
      <!-- Add Submit Paper Button -->
      <a href="submit_paper.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
        <i class="fas fa-plus mr-2"></i>Submit Paper
      </a>
    </div>

    <!-- Profile Picture with User's Initial Image -->
    <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['name'], 0, 1)); ?>.png" 
         alt="Profile Picture" 
         class="profile-pic" 
         onclick="toggleSidebar()" 
         title="<?php echo htmlspecialchars($_SESSION['name']); ?>"
         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
    
    <!-- Fallback div in case image doesn't exist -->
    <div class="profile-pic" 
         onclick="toggleSidebar()" 
         title="<?php echo htmlspecialchars($_SESSION['name']); ?>"
         style="display: none; background: linear-gradient(135deg, #115D5B, #103625); align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: bold; text-transform: uppercase;">
      <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
    </div>
  </div>
</nav>
 </header>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
    <a href="#">Settings</a>
    <a href="edit_profile.php">Profile</a>
    <a href="my_submissions.php"><i class="fas fa-file-alt mr-2"></i>My Submissions</a>
    <a href="submit_paper.php"><i class="fas fa-plus mr-2"></i>Submit Paper</a>
    <a href="index.php" onclick="logout()">Log Out</a>
</div>

  <!-- Main Content -->
  <section class="relative">
    <img src="Images/Banner.png" alt="Pineapple Farm" class="py-20 w-full h-[750px] object-cover" />
    <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-white bg-black bg-opacity-50">
      <h2 class="text-3xl font-bold">Welcome to Camarines Norte Lowland Rainfed Research Station</h2>
      <p class="mt-2 text-lg">Learn and Discover the Secrets Behind the Sweetest Pineapple in Camarines Norte</p>
      <a href="services.php" class="mt-4 bg-green-500 px-4 py-2 rounded-md text-white font-semibold hover:bg-green-700">Explore More</a>
    </div>
  </section>
  
  <!-- Info Cards -->
  <section class="py-10 bg-gray-100">
  <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 px-4">

    <!-- Card 1 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/About CN.png" alt="About" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">About CNLRRS</h3>
      <p class="text-sm text-gray-600">Explore agricultural studies on Pineapple farming.</p>
    </a>

    <!-- Card 2 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/UserG.png" alt="User Guide" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">User Guide</h3>
      <p class="text-sm text-gray-600">Find and read articles of your interest.</p>
    </a>

    <!-- Card 3 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/Collections.png" alt="Collections" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">Collections</h3>
      <p class="text-sm text-gray-600">Browse and learn about the CNLRRS library.</p>
    </a>

    <!-- Card 4 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/Collections.png" alt="For Authors" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">For Authors</h3>
      <p class="text-sm text-gray-600">Navigate submission methods easily.</p>
    </a>

    <!-- Card 5 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/Collections.png" alt="For Publisher" class="h-10" />
      </div>   
      <h3 class="font-semibold mb-1">For Publisher</h3>
      <p class="text-sm text-gray-600">Explore publisher options and process.</p>
    </a>

  </div>
</section>

  <!-- Footer -->
  <footer class="bg-[#115D5B] text-white text-center py-4">
    <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
  </footer>

  <!-- Scripts -->
  <script>
    // Logout function
    function logout() {
        // Clear the session by making an AJAX call to end the session
        fetch('logout.php', {
            method: 'POST'
        }).then(() => {
            // Redirect to index.php
            window.location.href = 'index.php';
        });
    }

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

    // Close sidebar when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('mySidebar');
        const profilePic = event.target.closest('.profile-pic');
        
        if (!sidebar.contains(event.target) && !profilePic && sidebar.style.width === '250px') {
            closeSidebar();
        }
    });
  </script>
</body>
</html>