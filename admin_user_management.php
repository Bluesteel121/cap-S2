<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

include 'connect.php';

// Get user and admin counts
$user_count_query = "SELECT COUNT(*) as count FROM accounts WHERE role = 'user'";
$admin_count_query = "SELECT COUNT(*) as count FROM accounts WHERE role = 'admin'";
$total_count_query = "SELECT COUNT(*) as count FROM accounts";

$user_count_result = $conn->query($user_count_query);
$admin_count_result = $conn->query($admin_count_query);
$total_count_result = $conn->query($total_count_query);

$user_count = $user_count_result->fetch_assoc()['count'];
$admin_count = $admin_count_result->fetch_assoc()['count'];
$total_count = $total_count_result->fetch_assoc()['count'];

// Get all accounts
$accounts_query = "SELECT * FROM accounts ORDER BY role DESC, created_at DESC";
$accounts_result = $conn->query($accounts_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CNLRRS - User Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }
    ::-webkit-scrollbar-thumb {
      background: #115D5B;
      border-radius: 3px;
    }

    /* Profile picture styles */
    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
      border: 2px solid #115D5B;
      transition: all 0.3s ease;
    }

    .profile-pic:hover {
      border-color: #f59e0b;
      transform: scale(1.05);
    }

    /* Sidebar styles */
    .sidebar {
      height: 100vh;
      width: 0;
      position: fixed;
      z-index: 1000;
      top: 0;
      left: 0;
      background: linear-gradient(180deg, #115D5B 0%, #103625 100%);
      overflow-x: hidden;
      overflow-y: auto;
      transition: width 0.3s ease;
      padding-top: 60px;
      color: white;
      box-shadow: 5px 0 15px rgba(0,0,0,0.1);
    }

    .sidebar a {
      padding: 15px 20px 15px 32px;
      text-decoration: none;
      font-size: 16px;
      color: white;
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }

    .sidebar a:hover {
      background-color: rgba(255,255,255,0.1);
      border-left-color: #f59e0b;
      padding-left: 40px;
    }

    .sidebar .closebtn {
      position: absolute;
      top: 15px;
      right: 25px;
      font-size: 28px;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .sidebar .closebtn:hover {
      color: #f59e0b;
      transform: rotate(90deg);
    }

    .admin-badge {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      font-size: 9px;
      padding: 2px 6px;
      border-radius: 8px;
      position: absolute;
      top: -2px;
      right: -2px;
      font-weight: bold;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .stats-card {
      background: white;
      border-left: 4px solid #115D5B;
      transition: all 0.3s ease;
    }

    .stats-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      border-left-width: 6px;
    }

    /* Logo container fix */
    .logo-container {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .logo-container img {
      height: 60px;
      width: auto;
      object-fit: contain;
    }

    /* Header improvements */
    .main-header {
      backdrop-filter: blur(10px);
      background: rgba(255,255,255,0.95);
    }

    /* Navigation improvements */
    .nav-container {
      background: white;
      border-bottom: 2px solid #e5e7eb;
      padding: 1rem 0;
    }

    .search-container {
      flex: 1;
      max-width: 600px;
      position: relative;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      flex-wrap: wrap;
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
      backdrop-filter: blur(4px);
    }

    .modal-content {
      background-color: #fefefe;
      margin: 2% auto;
      padding: 0;
      border: none;
      width: 90%;
      max-width: 600px;
      border-radius: 12px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.25);
      animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      padding: 10px 15px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .close:hover,
    .close:focus {
      color: #e53e3e;
      transform: scale(1.1);
    }

    /* User card styles */
    .user-card {
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
    }

    .user-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      border-left-color: #115D5B;
    }

    .user-card.admin {
      border-left-color: #f59e0b;
      background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
    }

    .role-badge {
      font-size: 10px;
      padding: 4px 8px;
      border-radius: 12px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .role-admin {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }

    .role-user {
      background: linear-gradient(135deg, #115D5B, #103625);
      color: white;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .logo-container {
        justify-content: center;
        text-align: center;
      }
      
      .nav-links {
        justify-content: center;
        gap: 1rem;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .modal-content {
        width: 95%;
        margin: 5% auto;
      }
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">

  <!-- Header -->
  <header id="main-header" class="main-header text-[#103625] px-4 py-3 shadow-lg fixed top-0 left-0 w-full z-50 transition-transform duration-300">
    <div class="max-w-7xl mx-auto">
      <!-- Logo Section -->
      <div class="logo-container justify-between items-center mb-4">
        <div class="flex items-center space-x-4">
          <img src="Images/Logo.jpg" alt="CNLRRS Logo">
          <div class="hidden sm:block">
            <h1 class="text-lg font-bold leading-tight">Camarines Norte Lowland Rainfed Research Station</h1>
            <p class="text-sm text-orange-600 font-semibold">
              <i class="fas fa-crown mr-1"></i>Admin Dashboard - User Management
            </p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <img src="Images/Ph.png" alt="PH Logo">
          <img src="Images/Da.png" alt="DA Logo">
          <div class="hidden md:block text-center">
            <p class="text-sm font-bold">DEPARTMENT OF AGRICULTURE</p>
            <p class="text-xs">Address: Calasgasan, Daet, Philippines</p>
            <p class="text-xs">Email: dacnlrrs@gmail.com | Contact: 0951 609 9599</p>
          </div>
          <img src="Images/Bago.png" alt="Bagong Pilipinas Logo">
        </div>
      </div>

      <!-- Navigation -->
      <div class="nav-container">
        <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-between items-center gap-4">
          <!-- Search Bar -->
          <div class="search-container">
            <div class="flex border border-green-900 rounded-full overflow-hidden bg-white">
              <input
                type="text"
                id="searchUsers"
                placeholder="Search users by name, username, or email..."
                class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-inset"
              />
              <button class="px-4 text-green-900 hover:bg-green-50 transition-colors">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>

          <!-- Navigation Links -->
          <div class="nav-links text-green-900 font-semibold text-sm">
            <a href="admin_loggedin_index.php" class="hover:text-green-700 transition-colors">Dashboard</a>
            <a href="admin_review_papers.php" class="hover:text-green-700 transition-colors">
              <i class="fas fa-eye mr-1"></i>Reviews
            </a>
            <a href="admin_user_management.php" class="bg-green-900 text-white px-4 py-2 rounded-lg transition-colors">
              <i class="fas fa-users mr-1"></i>Users
            </a>
            <a href="admin_review_papers.php" class="hover:text-green-700 transition-colors">Publications</a>
            <a href="admin_reports.php" class="hover:text-green-700 transition-colors">Reports</a>

            <!-- Profile Picture with Admin Badge -->
            <div class="relative">
              <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['username'], 0, 1)); ?>.png" 
                   alt="Profile Picture" 
                   class="profile-pic" 
                   onclick="toggleSidebar()" 
                   title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)"
                   onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
              
              <div class="profile-pic" 
                   onclick="toggleSidebar()" 
                   title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)"
                   style="display: none; background: linear-gradient(135deg, #115D5B, #103625); align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: bold; text-transform: uppercase;">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
              </div>
              
              <div class="admin-badge">ADMIN</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Sidebar -->
  <div id="mySidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
    <div class="px-6 py-4 border-b border-white border-opacity-20 mb-4">
      <h3 class="text-lg font-bold">Admin Panel</h3>
      <p class="text-sm opacity-75"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>
    <a href="admin_loggedin_index.php"><i class="fas fa-tachometer-alt mr-3"></i>Dashboard</a>
    <a href="admin_review_papers.php"><i class="fas fa-eye mr-3"></i>Review Submissions</a>
    <a href="admin_user_management.php" class="bg-white bg-opacity-10"><i class="fas fa-users mr-3"></i>Manage Users</a>
    <a href="#"><i class="fas fa-book mr-3"></i>Publications</a>
    <a href="#"><i class="fas fa-chart-bar mr-3"></i>Reports & Analytics</a>
    <a href="#"><i class="fas fa-cog mr-3"></i>System Settings</a>
    <a href="edit_profile.php"><i class="fas fa-user-edit mr-3"></i>Edit Profile</a>
    <div class="mt-8 border-t border-white border-opacity-20 pt-4">
      <a href="index.php" onclick="logout()"><i class="fas fa-sign-out-alt mr-3"></i>Log Out</a>
    </div>
  </div>

  <!-- Main Content -->
  <main class="pt-32 pb-8">
    <!-- Page Header -->
    <section class="relative bg-gradient-to-br from-[#115D5B] via-[#103625] to-[#0d2818] text-white py-12 mb-8">
      <div class="absolute inset-0 bg-black bg-opacity-20"></div>
      <div class="relative max-w-6xl mx-auto px-4">
        <div class="flex items-center mb-4">
          <i class="fas fa-users-cog text-3xl text-yellow-400 mr-4"></i>
          <h2 class="text-3xl font-bold">User Management</h2>
        </div>
        <p class="text-lg opacity-90">Manage user accounts and permissions</p>
      </div>
    </section>

    <!-- Statistics Cards -->
    <section class="max-w-6xl mx-auto px-4 mb-8">
      <div class="stats-grid grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="stats-card p-6 rounded-lg shadow-md">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Total Users</p>
              <p class="text-3xl font-bold text-[#115D5B]"><?php echo $total_count; ?></p>
              <p class="text-xs text-gray-500 font-medium mt-1">All registered accounts</p>
            </div>
            <div class="bg-[#115D5B] p-3 rounded-full">
              <i class="fas fa-users text-white text-xl"></i>
            </div>
          </div>
        </div>

        <div class="stats-card p-6 rounded-lg shadow-md border-l-yellow-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Administrators</p>
              <p class="text-3xl font-bold text-yellow-600"><?php echo $admin_count; ?></p>
              <p class="text-xs text-gray-500 font-medium mt-1">Admin accounts</p>
            </div>
            <div class="bg-yellow-500 p-3 rounded-full">
              <i class="fas fa-crown text-white text-xl"></i>
            </div>
          </div>
        </div>

        <div class="stats-card p-6 rounded-lg shadow-md border-l-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-medium">Regular Users</p>
              <p class="text-3xl font-bold text-blue-600"><?php echo $user_count; ?></p>
              <p class="text-xs text-gray-500 font-medium mt-1">Standard accounts</p>
            </div>
            <div class="bg-blue-500 p-3 rounded-full">
              <i class="fas fa-user text-white text-xl"></i>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- User List -->
    <section class="max-w-6xl mx-auto px-4">
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold text-[#115D5B]">All User Accounts</h3>
            <div class="flex gap-2">
              <button onclick="filterUsers('all')" class="filter-btn bg-[#115D5B] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                All (<?php echo $total_count; ?>)
              </button>
              <button onclick="filterUsers('admin')" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors hover:bg-yellow-500 hover:text-white">
                Admins (<?php echo $admin_count; ?>)
              </button>
              <button onclick="filterUsers('user')" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors hover:bg-blue-500 hover:text-white">
                Users (<?php echo $user_count; ?>)
              </button>
            </div>
          </div>
        </div>
        
        <div class="p-6">
          <div id="usersList" class="space-y-4">
            <?php while($account = $accounts_result->fetch_assoc()): ?>
            <div class="user-card <?php echo $account['role']; ?> bg-white p-4 rounded-lg shadow-sm border border-gray-200" data-role="<?php echo $account['role']; ?>">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <div class="relative">
                    <img src="Images/initials profile/<?php echo strtolower(substr($account['username'], 0, 1)); ?>.png" 
                         alt="Profile" 
                         class="w-12 h-12 rounded-full border-2 border-gray-200"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    
                    <div class="w-12 h-12 rounded-full border-2 border-gray-200 bg-gradient-to-br from-[#115D5B] to-[#103625] flex items-center justify-center text-white font-bold text-lg" style="display: none;">
                      <?php echo strtoupper(substr($account['username'], 0, 1)); ?>
                    </div>
                    
                    <?php if($account['role'] === 'admin'): ?>
                    <div class="absolute -top-1 -right-1 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-xs px-1 rounded-full">
                      <i class="fas fa-crown"></i>
                    </div>
                    <?php endif; ?>
                  </div>
                  
                  <div>
                    <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($account['name']); ?></h4>
                    <p class="text-sm text-gray-600">@<?php echo htmlspecialchars($account['username']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($account['email']); ?></p>
                  </div>
                </div>
                
                <div class="flex items-center space-x-3">
                  <span class="role-badge role-<?php echo $account['role']; ?>">
                    <?php echo $account['role'] === 'admin' ? 'Administrator' : 'User'; ?>
                  </span>
                  <button onclick="viewUserDetails(<?php echo $account['id']; ?>)" 
                          class="bg-[#115D5B] hover:bg-[#103625] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-eye mr-1"></i>View Details
                  </button>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- User Details Modal -->
  <div id="userModal" class="modal">
    <div class="modal-content">
      <div class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white p-6 rounded-t-lg">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-bold">User Details</h3>
          <span class="close">&times;</span>
        </div>
      </div>
      <div id="modalBody" class="p-6">
        <!-- User details will be loaded here -->
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white text-center py-8 mt-12">
    <div class="max-w-6xl mx-auto px-4">
      <p class="text-lg font-semibold">&copy; 2025 Camarines Norte Lowland Rainfed Research Station</p>
      <p class="text-sm opacity-75 mt-2">Admin Dashboard - User Management</p>
    </div>
  </footer>

  <!-- Scripts -->
  <script>
    function logout() {
      if (confirm('Are you sure you want to log out?')) {
        fetch('logout.php', {
          method: 'POST'
        }).then(() => {
          window.location.href = 'index.php';
        });
      }
    }

    function toggleSidebar() {
      const sidebar = document.getElementById('mySidebar');
      sidebar.style.width = sidebar.style.width === '280px' ? '0' : '280px';
    }

    function closeSidebar() {
      document.getElementById('mySidebar').style.width = '0';
    }

    // Filter users by role
    function filterUsers(role) {
      const userCards = document.querySelectorAll('.user-card');
      const filterBtns = document.querySelectorAll('.filter-btn');
      
      // Reset filter buttons
      filterBtns.forEach(btn => {
        btn.classList.remove('bg-[#115D5B]', 'bg-yellow-500', 'bg-blue-500', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
      });
      
      // Highlight active filter
      event.target.classList.remove('bg-gray-200', 'text-gray-700');
      if (role === 'all') {
        event.target.classList.add('bg-[#115D5B]', 'text-white');
      } else if (role === 'admin') {
        event.target.classList.add('bg-yellow-500', 'text-white');
      } else {
        event.target.classList.add('bg-blue-500', 'text-white');
      }
      
      // Filter cards
      userCards.forEach(card => {
        if (role === 'all' || card.dataset.role === role) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Search functionality
    document.getElementById('searchUsers').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const userCards = document.querySelectorAll('.user-card');
      
      userCards.forEach(card => {
        const name = card.querySelector('h4').textContent.toLowerCase();
        const username = card.querySelector('p').textContent.toLowerCase();
        const email = card.querySelectorAll('p')[1].textContent.toLowerCase();
        
        if (name.includes(searchTerm) || username.includes(searchTerm) || email.includes(searchTerm)) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });

    // View user details
    function viewUserDetails(userId) {
      const modal = document.getElementById('userModal');
      const modalBody = document.getElementById('modalBody');
      
      // Show loading state
      modalBody.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-[#115D5B]"></i><p class="mt-2">Loading user details...</p></div>';
      modal.style.display = 'block';
      
      // Fetch user details
      fetch('get_user_details.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const user = data.user;
            const roleClass = user.role === 'admin' ? 'text-yellow-600' : 'text-blue-600';
            const roleIcon = user.role === 'admin' ? 'fas fa-crown' : 'fas fa-user';
            
            modalBody.innerHTML = `
              <div class="flex items-center space-x-4 mb-6">
                <div class="relative">
                  <img src="Images/initials profile/${user.username.charAt(0).toLowerCase()}.png" 
                       alt="Profile" 
                       class="w-16 h-16 rounded-full border-4 border-[#115D5B]"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                  
                  <div class="w-16 h-16 rounded-full border-4 border-[#115D5B] bg-gradient-to-br from-[#115D5B] to-[#103625] flex items-center justify-center text-white font-bold text-xl" style="display: none;">
                    ${user.username.charAt(0).toUpperCase()}
                  </div>
                  
                  ${user.role === 'admin' ? '<div class="absolute -top-1 -right-1 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-xs px-2 py-1 rounded-full"><i class="fas fa-crown"></i></div>' : ''}
                </div>
                
                <div>
                  <h4 class="text-xl font-bold text-gray-900">${user.name}</h4>
                  <p class="text-gray-600">@${user.username}</p>
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${roleClass} bg-opacity-10" style="background-color: ${user.role === 'admin' ? '#fef3c7' : '#dbeafe'};">
                    <i class="${roleIcon} mr-1"></i>
                    ${user.role === 'admin' ? 'Administrator' : 'User'}
                  </span>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                  <div>
                    <label class="text-sm font-medium text-gray-700">Email Address</label>
                    <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">${user.email}</p>
                  </div>
                  
                  <div>
                    <label class="text-sm font-medium text-gray-700">Contact Number</label>
                    <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">${user.contact}</p>
                  </div>
                  
                  <div>
                    <label class="text-sm font-medium text-gray-700">Birth Date</label>
                    <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">${new Date(user.birth_date).toLocaleDateString()}</p>
                  </div>
                </div>
                
                <div class="space-y-4">
                  <div>
                    <label class="text-sm font-medium text-gray-700">Address</label>
                    <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">${user.address}</p>
                  </div>
                  
                  <div>
                    <label class="text-sm font-medium text-gray-700">Account Created</label>
                    <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">${new Date(user.created_at).toLocaleDateString()} at ${new Date(user.created_at).toLocaleTimeString()}</p>
                  </div>
                  
                  <div>
                    <label class="text-sm font-medium text-gray-700">Last Updated</label>
                    <p class="text-gray-900 bg-gray-50 p-3 rounded-lg">${new Date(user.updated_at).toLocaleDateString()} at ${new Date(user.updated_at).toLocaleTimeString()}</p>
                  </div>
                </div>
              </div>
              
              <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex justify-between items-center">
                  <h5 class="font-semibold text-gray-900">Account Actions</h5>
                  <div class="space-x-2">
                    ${user.role === 'user' ? 
                      `<button onclick="promoteToAdmin(${user.id})" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-crown mr-1"></i>Promote to Admin
                      </button>` : 
                      (user.id != getCurrentAdminId() ? 
                        `<button onclick="demoteToUser(${user.id})" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                          <i class="fas fa-user-minus mr-1"></i>Demote to User
                        </button>` : 
                        '<span class="text-gray-500 text-sm italic">Cannot modify your own account</span>'
                      )
                    }
                    ${user.id != getCurrentAdminId() ? 
                      `<button onclick="deleteUser(${user.id})" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-trash mr-1"></i>Delete Account
                      </button>` : ''
                    }
                  </div>
                </div>
              </div>
            `;
          } else {
            modalBody.innerHTML = '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><p>Error loading user details</p></div>';
          }
        })
        .catch(error => {
          modalBody.innerHTML = '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><p>Error loading user details</p></div>';
        });
    }

    // Get current admin ID (you'll need to pass this from PHP)
    function getCurrentAdminId() {
      return <?php echo $_SESSION['user_id'] ?? 0; ?>; // You may need to add user_id to session
    }

    // Promote user to admin
    function promoteToAdmin(userId) {
      if (confirm('Are you sure you want to promote this user to administrator?')) {
        fetch('manage_user_role.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'promote',
            user_id: userId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('User promoted to administrator successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          alert('Error promoting user');
        });
      }
    }

    // Demote admin to user
    function demoteToUser(userId) {
      if (confirm('Are you sure you want to demote this administrator to regular user?')) {
        fetch('manage_user_role.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'demote',
            user_id: userId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Administrator demoted to user successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          alert('Error demoting user');
        });
      }
    }

    // Delete user
    function deleteUser(userId) {
      if (confirm('Are you sure you want to delete this user account? This action cannot be undone!')) {
        fetch('manage_user_role.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'delete',
            user_id: userId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('User account deleted successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          alert('Error deleting user');
        });
      }
    }

    // Modal functionality
    const modal = document.getElementById('userModal');
    const closeBtn = document.getElementsByClassName('close')[0];

    closeBtn.onclick = function() {
      modal.style.display = 'none';
    }

    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }

    // Header visibility on scroll
    let lastScrollTop = 0;
    const header = document.getElementById('main-header');

    window.addEventListener('scroll', () => {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      if (scrollTop > 100 && scrollTop > lastScrollTop) {
        header.style.transform = 'translateY(-100%)';
      } else {
        header.style.transform = 'translateY(0)';
      }
      lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', function(event) {
      const sidebar = document.getElementById('mySidebar');
      const profilePic = event.target.closest('.profile-pic');
      
      if (!sidebar.contains(event.target) && !profilePic && sidebar.style.width === '280px') {
        closeSidebar();
      }
    });
  </script>
</body>
</html>