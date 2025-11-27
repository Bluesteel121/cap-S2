<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Continue with the rest of your code...
require_once 'includes/ReviewerNotifications.php';
include 'connect.php';
// Get total users count
$result = $conn->query("SELECT COUNT(*) as total_users FROM accounts");
$total_users = $result->fetch_assoc()['total_users'];

// Get total publications count (approved + published)
$result = $conn->query("SELECT COUNT(*) as total_publications FROM paper_submissions WHERE status IN ('approved', 'published')");
$total_publications = $result->fetch_assoc()['total_publications'];


$result = $conn->query("SELECT COUNT(*) as pending_reviews FROM paper_submissions WHERE status IN ('pending', 'under_review')");
$pending_reviews = $result->fetch_assoc()['pending_reviews'];





$current_month = date('Y-m');
$stmt = $conn->prepare("
    SELECT COUNT(*) as monthly_views 
    FROM paper_metrics 
    WHERE metric_type = 'view' 
    AND DATE_FORMAT(created_at, '%Y-%m') = ?
");
$stmt->bind_param("s", $current_month);
$stmt->execute();
$monthly_views = $stmt->get_result()->fetch_assoc()['monthly_views'];

// Calculate percentage changes (comparing with previous month)
$previous_month = date('Y-m', strtotime('-1 month'));

// Users growth calculation
$stmt = $conn->prepare("SELECT COUNT(*) as prev_users FROM accounts WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->bind_param("s", $previous_month);
$stmt->execute();
$prev_month_users = $stmt->get_result()->fetch_assoc()['prev_users'];

$stmt = $conn->prepare("SELECT COUNT(*) as curr_users FROM accounts WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->bind_param("s", $current_month);
$stmt->execute();
$curr_month_users = $stmt->get_result()->fetch_assoc()['curr_users'];

$user_growth = $prev_month_users > 0 ? round((($curr_month_users - $prev_month_users) / $prev_month_users) * 100) : 0;

// Publications growth calculation
$stmt = $conn->prepare("
    SELECT COUNT(*) as prev_publications 
    FROM paper_submissions 
    WHERE status IN ('approved', 'published') 
    AND DATE_FORMAT(submission_date, '%Y-%m') = ?
");
$stmt->bind_param("s", $previous_month);
$stmt->execute();
$prev_publications = $stmt->get_result()->fetch_assoc()['prev_publications'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as curr_publications 
    FROM paper_submissions 
    WHERE status IN ('approved', 'published') 
    AND DATE_FORMAT(submission_date, '%Y-%m') = ?
");
$stmt->bind_param("s", $current_month);
$stmt->execute();
$curr_publications = $stmt->get_result()->fetch_assoc()['curr_publications'];

$publication_growth = $prev_publications > 0 ? round((($curr_publications - $prev_publications) / $prev_publications) * 100) : 0;

// Views growth calculation
$stmt = $conn->prepare("
    SELECT COUNT(*) as prev_views 
    FROM paper_metrics 
    WHERE metric_type = 'view' 
    AND DATE_FORMAT(created_at, '%Y-%m') = ?
");
$stmt->bind_param("s", $previous_month);
$stmt->execute();
$prev_views = $stmt->get_result()->fetch_assoc()['prev_views'];

$views_growth = $prev_views > 0 ? round((($monthly_views - $prev_views) / $prev_views) * 100) : 0;

// Format numbers for display
function formatNumber($number) {
    if ($number >= 1000) {
        return number_format($number / 1000, 1) . 'k';
    }
    return number_format($number);
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

    .admin-card {
      background: linear-gradient(135deg, #115D5B 0%, #103625 100%);
      color: white;
      transition: all 0.3s ease;
      border-radius: 12px;
      overflow: hidden;
      position: relative;
    }

    .admin-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .admin-card:hover::before {
      opacity: 1;
    }

    .admin-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(17, 93, 91, 0.3);
    }

    /* Enhanced admin card icon styles */
    .admin-card .icon-container {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }

    .admin-card:hover .icon-container {
      background: rgba(255, 255, 255, 0.25);
      transform: scale(1.1);
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

    /* Activity item hover effects */
    .activity-item {
      transition: all 0.3s ease;
      border-radius: 8px;
      margin: -4px;
      padding: 16px;
    }

    .activity-item:hover {
      background-color: #f8fafc;
      transform: translateX(4px);
    }

    /* Enhanced grid layout for admin tools */
    .admin-tools-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      justify-items: center;
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
        grid-template-columns: 1fr 1fr;
      }
      
      .admin-tools-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .admin-tools-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Enhanced card styles for better visual balance */
    .enhanced-card {
      min-height: 200px;
      display: flex;
      flex-direction: column;
      justify-content: center;
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
              <i class="fas fa-crown mr-1"></i>Admin Dashboard
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
                placeholder="Search publications, articles, users, etc."
                class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-inset"
              />
              <button class="px-4 text-green-900 hover:bg-green-50 transition-colors">
                <i class="fas fa-search"></i>
              </button>
              <button class="px-4 font-semibold text-green-900 hover:bg-green-50 transition-colors border-l border-gray-200">
                Advanced
              </button>
            </div>
          </div>

          <!-- Navigation Links -->
          <div class="nav-links text-green-900 font-semibold text-sm">
            <a href="admin_loggedin_index.php" class="hover:text-green-700 transition-colors">Dashboard</a>
            <a href="admin_review_papers.php" class="bg-yellow-500 hover:bg-yellow-600 text-black px-4 py-2 rounded-lg transition-colors">
              <i class="fas fa-eye mr-1"></i>Reviews
            </a>
            <a href="admin_user_management.php" class="border-2 border-green-900 hover:bg-green-900 hover:text-white px-4 py-2 rounded-lg transition-colors">
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
    <a href="admin_user_management.php"><i class="fas fa-users mr-3"></i>Manage Users</a>
    <a href="admin_review_papers.php"><i class="fas fa-book mr-3"></i>Publications</a>
    <a href="admin_analytics.php"><i class="fas fa-chart-bar mr-3"></i>Analytics</a>
    <a href="edit_profile.php"><i class="fas fa-user-edit mr-3"></i>Edit Profile</a>
    <a href="admin_reports.php"><i class="fas fa-chart-pie mr-3"></i>Comprehensive Reports</a>
    <div class="mt-8 border-t border-white border-opacity-20 pt-4">
      <a href="index.php" onclick="logout()"><i class="fas fa-sign-out-alt mr-3"></i>Log Out</a>
    </div>
  </div>

  <!-- Main Content -->
  <main class="pt-32 pb-8">
    <!-- Welcome Banner -->
    <section class="relative bg-gradient-to-br from-[#115D5B] via-[#103625] to-[#0d2818] text-white py-16 mb-8">
      <div class="absolute inset-0 bg-black bg-opacity-20"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-5"></div>
      <div class="relative max-w-6xl mx-auto px-4 text-center">
        <div class="flex items-center justify-center mb-4">
          <i class="fas fa-crown text-4xl text-yellow-400 mr-4 animate-pulse"></i>
          <h2 class="text-4xl font-bold">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        </div>
        <p class="text-xl mb-8 opacity-90">Manage the CNLRRS Research Platform</p>
        <div class="flex flex-wrap justify-center gap-4">
          <a href="admin_review_papers.php" class="bg-yellow-500 hover:bg-yellow-600 text-black px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105">
            <i class="fas fa-plus mr-2"></i>Add Publication
          </a>
          <a href="admin_user_management.php" class="bg-transparent border-2 border-white hover:bg-white hover:text-[#115D5B] px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105">
            <i class="fas fa-users mr-2"></i>Manage Users
          </a>
        </div>
      </div>
    </section>

    <!-- Quick Stats -->
<section class="max-w-6xl mx-auto px-4 mb-12">
  <h3 class="text-2xl font-bold text-[#115D5B] mb-6">
    <i class="fas fa-chart-line mr-2"></i>System Overview
  </h3>
  <div class="stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    <!-- Total Users Card -->
    <div class="stats-card p-6 rounded-lg shadow-md">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600 font-medium">Total Users</p>
          <p class="text-3xl font-bold text-[#115D5B]"><?php echo formatNumber($total_users); ?></p>
          <p class="text-xs <?php echo $user_growth >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium mt-1">
            <?php echo $user_growth >= 0 ? '↑' : '↓'; ?> <?php echo abs($user_growth); ?>% from last month
          </p>
        </div>
        <div class="bg-[#115D5B] p-3 rounded-full">
          <i class="fas fa-users text-white text-xl"></i>
        </div>
      </div>
    </div>

    <!-- Total Publications Card -->
    <div class="stats-card p-6 rounded-lg shadow-md">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600 font-medium">Publications</p>
          <p class="text-3xl font-bold text-[#115D5B]"><?php echo formatNumber($total_publications); ?></p>
          <p class="text-xs <?php echo $publication_growth >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium mt-1">
            <?php echo $publication_growth >= 0 ? '↑' : '↓'; ?> <?php echo abs($publication_growth); ?>% from last month
          </p>
        </div>
        <div class="bg-green-600 p-3 rounded-full">
          <i class="fas fa-book text-white text-xl"></i>
        </div>
      </div>
    </div>

    <!-- Pending Reviews Card -->
    <div class="stats-card p-6 rounded-lg shadow-md">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600 font-medium">Pending Reviews</p>
          <p class="text-3xl font-bold text-orange-600"><?php echo $pending_reviews; ?></p>
          <p class="text-xs text-orange-600 font-medium mt-1">
            <?php echo $pending_reviews > 0 ? 'Needs attention' : 'All caught up!'; ?>
          </p>
        </div>
        <div class="bg-orange-500 p-3 rounded-full">
          <i class="fas fa-clock text-white text-xl"></i>
        </div>
      </div>
    </div>

    <!-- Monthly Views Card -->
    <div class="stats-card p-6 rounded-lg shadow-md">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600 font-medium">Monthly Views</p>
          <p class="text-3xl font-bold text-blue-600"><?php echo formatNumber($monthly_views); ?></p>
          <p class="text-xs <?php echo $views_growth >= 0 ? 'text-blue-600' : 'text-red-600'; ?> font-medium mt-1">
            <?php echo $views_growth >= 0 ? '↑' : '↓'; ?> <?php echo abs($views_growth); ?>% from last month
          </p>
        </div>
        <div class="bg-blue-500 p-3 rounded-full">
          <i class="fas fa-eye text-white text-xl"></i>
        </div>
      </div>
    </div>

      </div>
    </section>
    
    <!-- Admin Management Cards -->
    <section class="max-w-6xl mx-auto px-4 mb-12">
      <h3 class="text-2xl font-bold text-[#115D5B] mb-6">
        <i class="fas fa-tools mr-2"></i>Admin Tools
      </h3>
      <div class="admin-tools-grid">

        <a href="admin_user_management.php" class="admin-card enhanced-card p-8 shadow-lg transition-all w-full max-w-sm">
          <div class="text-center">
            <div class="icon-container p-6 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-6">
              <i class="fas fa-users-cog text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3">User Management</h3>
            <p class="opacity-90 leading-relaxed">Manage user accounts, roles, and permissions across the platform</p>
          </div>
        </a>

        <a href="admin_activity_logs.php" class="admin-card enhanced-card p-8 shadow-lg transition-all w-full max-w-sm">
          <div class="text-center">
            <div class="icon-container p-6 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-6">
              <i class="fas fa-history text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3">Activity Logs</h3>
            <p class="opacity-90 leading-relaxed">Monitor system activities and user interactions</p>
          </div>
        </a>

        <a href="admin_review_papers.php" class="admin-card enhanced-card p-8 shadow-lg transition-all w-full max-w-sm">
          <div class="text-center">
            <div class="icon-container p-6 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-6">
              <i class="fas fa-file-alt text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3">Publications</h3>
            <p class="opacity-90 leading-relaxed">Review, approve, and manage research publications and submissions</p>
          </div>
        </a>

        <a href="admin_email_templates.php" class="admin-card enhanced-card p-8 shadow-lg transition-all w-full max-w-sm">
          <div class="text-center">
            <div class="icon-container p-6 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-6">
              <i class="fas fa-envelope text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3">Email Templates</h3>
            <p class="opacity-90 leading-relaxed">Manage email notifications and communication templates</p>
          </div>
        </a>

        <a href="admin_analytics.php" class="admin-card enhanced-card p-8 shadow-lg transition-all w-full max-w-sm">
          <div class="text-center">
            <div class="icon-container p-6 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-6">
              <i class="fas fa-chart-bar text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3">Analytics</h3>
            <p class="opacity-90 leading-relaxed">View detailed reports, statistics, and platform analytics</p>
          </div>
        </a>
        
<a href="admin_reports.php" class="admin-card enhanced-card p-8 shadow-lg transition-all w-full max-w-sm">
  <div class="text-center">
    <div class="icon-container p-6 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-6">
      <i class="fas fa-chart-pie text-3xl"></i>
    </div>
    <h3 class="text-xl font-bold mb-3">Reports</h3>
    <p class="opacity-90 leading-relaxed">Generate detailed analytics, insights, and system performance reports</p>
  </div>
</a>

      </div>
    </section>


  <!-- Footer -->
  <footer class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white text-center py-8 mt-12">
    <div class="max-w-6xl mx-auto px-4">
      <p class="text-lg font-semibold">&copy; 2025 Camarines Norte Lowland Rainfed Research Station</p>
      <p class="text-sm opacity-75 mt-2">Admin Dashboard - Manage with responsibility and care</p>
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

    // Add loading states for buttons
    document.querySelectorAll('a[href="#"]').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
        setTimeout(() => {
          this.innerHTML = originalText;
        }, 1500);
      });
    });

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      // Add some animation delays to stats cards
      document.querySelectorAll('.stats-card').forEach((card, index) => {
        card.style.animationDelay = `${index * 100}ms`;
      });
    });
  </script>
</body>
</html>