<?php
session_start();

// Check if user is logged in and has admin role
// FIXED: Changed from $_SESSION['name'] to $_SESSION['username']
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CNLRRS - Admin Dashboard</title>
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

    .admin-badge {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 10px;
      position: absolute;
      top: -5px;
      right: -5px;
      font-weight: bold;
    }

    .admin-card {
      background: linear-gradient(135deg, #115D5B, #103625);
      color: white;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .admin-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(17, 93, 91, 0.3);
    }

    .stats-card {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      border-left: 4px solid #115D5B;
    }
  </style>
</head>

<body class="bg-gray-50 pt-20">

  <!-- Header -->
<header id="main-header" class="bg-white text-[#103625] px-6 py-3 shadow-lg fixed top-0 left-0 w-full z-50 transition-transform duration-300">
 <div class="w-full mt-2 mx-auto flex justify-between items-center">
    <!-- Logo and Title -->
    <div class="flex items-center space-x-4">
      <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-20 w-auto object-contain">
      <div>
        <h1 class="text-xl font-bold leading-tight">Camarines Norte Lowland <br class="hidden sm:block" /> Rainfed Research Station</h1>
        <p class="text-sm text-orange-600 font-semibold"><i class="fas fa-crown mr-1"></i>Admin Dashboard</p>
      </div>
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
        placeholder="Search publications, articles, users, etc."
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
      <a href="#" class="hover:underline">Dashboard</a>
      <a href="#" class="hover:underline">Manage Users</a>
      <a href="#" class="hover:underline">Publications</a>
      <a href="#" class="hover:underline">Reports</a>
    </div>

    <!-- Profile Picture with Admin Badge -->
    <div class="relative">
      <!-- FIXED: Changed from $_SESSION['name'] to $_SESSION['username'] -->
      <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['username'], 0, 1)); ?>.png" 
           alt="Profile Picture" 
           class="profile-pic" 
           onclick="toggleSidebar()" 
           title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)"
           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
      
      <!-- Fallback div in case image doesn't exist -->
      <div class="profile-pic" 
           onclick="toggleSidebar()" 
           title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)"
           style="display: none; background: linear-gradient(135deg, #115D5B, #103625); align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: bold; text-transform: uppercase;">
        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
      </div>
      
      <!-- Admin Badge -->
      <div class="admin-badge">ADMIN</div>
    </div>
  </div>
</nav>
 </header>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
    <a href="#"><i class="fas fa-tachometer-alt mr-3"></i>Dashboard</a>
    <a href="#"><i class="fas fa-users mr-3"></i>Manage Users</a>
    <a href="#"><i class="fas fa-book mr-3"></i>Publications</a>
    <a href="#"><i class="fas fa-chart-bar mr-3"></i>Reports</a>
    <a href="#"><i class="fas fa-cog mr-3"></i>Settings</a>
    <a href="edit_profile.php"><i class="fas fa-user-edit mr-3"></i>Profile</a>
    <a href="index.php" onclick="logout()"><i class="fas fa-sign-out-alt mr-3"></i>Log Out</a>
</div>

  <!-- Welcome Banner -->
  <section class="relative bg-gradient-to-r from-[#115D5B] to-[#103625] text-white py-16">
    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
    <div class="relative max-w-6xl mx-auto px-4 text-center">
      <div class="flex items-center justify-center mb-4">
        <i class="fas fa-crown text-4xl text-yellow-400 mr-4"></i>
        <!-- FIXED: Changed from $_SESSION['name'] to $_SESSION['username'] -->
        <h2 class="text-4xl font-bold">Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
      </div>
      <p class="text-xl mb-6">Manage the Camarines Norte Lowland Rainfed Research Station Platform</p>
      <div class="flex flex-wrap justify-center gap-4">
        <a href="#" class="bg-yellow-500 hover:bg-yellow-600 text-black px-6 py-3 rounded-lg font-semibold transition">
          <i class="fas fa-plus mr-2"></i>Add New Publication
        </a>
        <a href="#" class="bg-transparent border-2 border-white hover:bg-white hover:text-[#115D5B] px-6 py-3 rounded-lg font-semibold transition">
          <i class="fas fa-users mr-2"></i>Manage Users
        </a>
      </div>
    </div>
  </section>

  <!-- Quick Stats -->
  <section class="py-8 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
      <h3 class="text-2xl font-bold text-[#115D5B] mb-6"><i class="fas fa-chart-line mr-2"></i>System Overview</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Users -->
        <div class="stats-card p-6 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Total Users</p>
              <p class="text-3xl font-bold text-[#115D5B]">2,847</p>
            </div>
            <div class="bg-[#115D5B] p-3 rounded-full">
              <i class="fas fa-users text-white text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Publications -->
        <div class="stats-card p-6 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Publications</p>
              <p class="text-3xl font-bold text-[#115D5B]">1,234</p>
            </div>
            <div class="bg-green-600 p-3 rounded-full">
              <i class="fas fa-book text-white text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Pending Reviews -->
        <div class="stats-card p-6 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Pending Reviews</p>
              <p class="text-3xl font-bold text-orange-600">45</p>
            </div>
            <div class="bg-orange-500 p-3 rounded-full">
              <i class="fas fa-clock text-white text-xl"></i>
            </div>
          </div>
        </div>

        <!-- Monthly Views -->
        <div class="stats-card p-6 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Monthly Views</p>
              <p class="text-3xl font-bold text-blue-600">89.2k</p>
            </div>
            <div class="bg-blue-500 p-3 rounded-full">
              <i class="fas fa-eye text-white text-xl"></i>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
  
  <!-- Admin Management Cards -->
  <section class="py-10 bg-white">
    <div class="max-w-6xl mx-auto px-4">
      <h3 class="text-2xl font-bold text-[#115D5B] mb-6"><i class="fas fa-tools mr-2"></i>Admin Tools</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

        <!-- User Management -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-users-cog text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">User Management</h3>
            <p class="text-sm opacity-90">Add, edit, and manage user accounts and permissions.</p>
          </div>
        </a>

        <!-- Content Management -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-file-alt text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">Publications</h3>
            <p class="text-sm opacity-90">Review, approve, and manage research publications.</p>
          </div>
        </a>

        <!-- Analytics -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-chart-bar text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">Analytics</h3>
            <p class="text-sm opacity-90">View detailed reports and system analytics.</p>
          </div>
        </a>

        <!-- System Settings -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-cogs text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">System Settings</h3>
            <p class="text-sm opacity-90">Configure system settings and preferences.</p>
          </div>
        </a>

        <!-- Database Backup -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-database text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">Backup</h3>
            <p class="text-sm opacity-90">Manage database backups and system recovery.</p>
          </div>
        </a>

        <!-- Activity Logs -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-history text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">Activity Logs</h3>
            <p class="text-sm opacity-90">Monitor user activities and system logs.</p>
          </div>
        </a>

        <!-- Email Templates -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-envelope text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">Email Templates</h3>
            <p class="text-sm opacity-90">Manage automated email templates and notifications.</p>
          </div>
        </a>

        <!-- Security Center -->
        <a href="#" class="admin-card rounded-lg p-6 shadow hover:shadow-lg transition">
          <div class="text-center">
            <div class="bg-white bg-opacity-20 p-4 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
              <i class="fas fa-shield-alt text-2xl"></i>
            </div>
            <h3 class="font-bold mb-2">Security</h3>
            <p class="text-sm opacity-90">Monitor security settings and access controls.</p>
          </div>
        </a>

      </div>
    </div>
  </section>

  <!-- Recent Activity -->
  <section class="py-10 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
      <h3 class="text-2xl font-bold text-[#115D5B] mb-6"><i class="fas fa-clock mr-2"></i>Recent Activity</h3>
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
          <div class="space-y-4">
            
            <div class="flex items-center justify-between border-b pb-4">
              <div class="flex items-center">
                <div class="bg-green-100 p-2 rounded-full mr-4">
                  <i class="fas fa-user-plus text-green-600"></i>
                </div>
                <div>
                  <p class="font-medium">New user registered: Maria Santos</p>
                  <p class="text-sm text-gray-500">2 minutes ago</p>
                </div>
              </div>
              <span class="text-green-600 text-sm font-medium">New User</span>
            </div>

            <div class="flex items-center justify-between border-b pb-4">
              <div class="flex items-center">
                <div class="bg-blue-100 p-2 rounded-full mr-4">
                  <i class="fas fa-file-upload text-blue-600"></i>
                </div>
                <div>
                  <p class="font-medium">Publication submitted: "Pineapple Cultivation Methods"</p>
                  <p class="text-sm text-gray-500">15 minutes ago</p>
                </div>
              </div>
              <span class="text-blue-600 text-sm font-medium">Pending Review</span>
            </div>

            <div class="flex items-center justify-between border-b pb-4">
              <div class="flex items-center">
                <div class="bg-orange-100 p-2 rounded-full mr-4">
                  <i class="fas fa-exclamation-triangle text-orange-600"></i>
                </div>
                <div>
                  <p class="font-medium">System backup completed successfully</p>
                  <p class="text-sm text-gray-500">1 hour ago</p>
                </div>
              </div>
              <span class="text-orange-600 text-sm font-medium">System</span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="bg-purple-100 p-2 rounded-full mr-4">
                  <i class="fas fa-check-circle text-purple-600"></i>
                </div>
                <div>
                  <p class="font-medium">Publication approved: "Sustainable Farming Practices"</p>
                  <p class="text-sm text-gray-500">3 hours ago</p>
                </div>
              </div>
              <span class="text-purple-600 text-sm font-medium">Approved</span>
            </div>

          </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
          <a href="#" class="text-[#115D5B] font-medium hover:underline">View all activity →</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-[#115D5B] text-white text-center py-4">
    <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
    <p class="text-sm opacity-75 mt-1">Admin Dashboard - Manage with responsibility</p>
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

    // Add some interactive effects
    document.querySelectorAll('.admin-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
  </script>
</body>
</html>