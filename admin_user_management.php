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
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #115D5B; border-radius: 3px; }

    .profile-pic {
      width: 40px; height: 40px; border-radius: 50%;
      object-fit: cover; cursor: pointer;
      border: 2px solid #115D5B; transition: all 0.3s ease;
    }
    .profile-pic:hover { border-color: #f59e0b; transform: scale(1.05); }

    .sidebar {
      height: 100vh; width: 0; position: fixed;
      z-index: 1000; top: 0; left: 0;
      background: linear-gradient(180deg, #115D5B 0%, #103625 100%);
      overflow-x: hidden; overflow-y: auto;
      transition: width 0.3s ease; padding-top: 60px;
      color: white; box-shadow: 5px 0 15px rgba(0,0,0,0.1);
    }
    .sidebar a {
      padding: 15px 20px 15px 32px; text-decoration: none;
      font-size: 16px; color: white; display: flex;
      align-items: center; transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }
    .sidebar a:hover {
      background-color: rgba(255,255,255,0.1);
      border-left-color: #f59e0b; padding-left: 40px;
    }
    .sidebar .closebtn {
      position: absolute; top: 15px; right: 25px;
      font-size: 28px; color: white; cursor: pointer;
      transition: all 0.3s ease;
    }
    .sidebar .closebtn:hover { color: #f59e0b; transform: rotate(90deg); }

    .admin-badge {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white; font-size: 9px; padding: 2px 6px;
      border-radius: 8px; position: absolute; top: -2px; right: -2px;
      font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .stats-card {
      background: white; border-left: 4px solid #115D5B;
      transition: all 0.3s ease;
    }
    .stats-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      border-left-width: 6px;
    }

    .logo-container { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .logo-container img { height: 60px; width: auto; object-fit: contain; }
    .main-header { backdrop-filter: blur(10px); background: rgba(255,255,255,0.95); }
    .nav-container { background: white; border-bottom: 2px solid #e5e7eb; padding: 1rem 0; }
    .nav-links { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }

    /* Modal */
    .modal {
      display: none; position: fixed; z-index: 2000;
      left: 0; top: 0; width: 100%; height: 100%;
      overflow: auto; background-color: rgba(0,0,0,0.5);
      backdrop-filter: blur(4px);
    }
    .modal-content {
      background-color: #fefefe; margin: 2% auto; padding: 0;
      border: none; width: 90%; max-width: 680px;
      border-radius: 12px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
      animation: modalSlideIn 0.3s ease-out;
    }
    @keyframes modalSlideIn {
      from { opacity: 0; transform: translateY(-50px) scale(0.95); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Edit modal wider */
    #editModal .modal-content { max-width: 750px; }

    .modal-close-btn {
      color: rgba(255,255,255,0.8); font-size: 28px; font-weight: bold;
      cursor: pointer; transition: all 0.3s ease; line-height: 1;
      background: none; border: none; padding: 0;
    }
    .modal-close-btn:hover { color: #ffffff; transform: scale(1.2); }

    .user-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
    .user-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); border-left-color: #115D5B; }
    .user-card.admin { border-left-color: #f59e0b; background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%); }

    .role-badge { font-size: 10px; padding: 4px 8px; border-radius: 12px; font-weight: bold; text-transform: uppercase; }
    .role-admin { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .role-user  { background: linear-gradient(135deg, #115D5B, #103625); color: white; }

    .action-btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 14px; border-radius: 8px; font-size: 13px;
      font-weight: 600; cursor: pointer; border: none;
      transition: all 0.2s ease; text-decoration: none;
    }
    .action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .btn-view   { background: #115D5B; color: white; }
    .btn-edit   { background: #3b82f6; color: white; }
    .btn-promote{ background: #f59e0b; color: white; }
    .btn-demote { background: #f97316; color: white; }
    .btn-reset  { background: #8b5cf6; color: white; }
    .btn-delete { background: #ef4444; color: white; }
    .btn-cancel { background: #6b7280; color: white; }
    .btn-save   { background: #115D5B; color: white; }

    .form-field {
      width: 100%; padding: 10px 12px; border: 2px solid #e5e7eb;
      border-radius: 8px; font-size: 14px; transition: border-color 0.2s;
      outline: none;
    }
    .form-field:focus { border-color: #115D5B; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 4px; }

    .detail-row { display: flex; flex-direction: column; gap: 2px; }
    .detail-label { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
    .detail-value { font-size: 14px; color: #111827; background: #f9fafb; padding: 10px 12px; border-radius: 8px; border: 1px solid #e5e7eb; }

    .tab-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
    .tab-btn.active { background: #115D5B; color: white; }
    .tab-btn:not(.active) { color: #6b7280; border-color: #e5e7eb; }
    .tab-btn:not(.active):hover { border-color: #115D5B; color: #115D5B; }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    @media (max-width: 768px) {
      .logo-container { justify-content: center; text-align: center; }
      .nav-links { justify-content: center; gap: 1rem; }
      .modal-content { width: 95%; margin: 5% auto; }
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">

  <!-- Header -->
  <header id="main-header" class="main-header text-[#103625] px-4 py-3 shadow-lg fixed top-0 left-0 w-full z-50 transition-transform duration-300">
    <div class="max-w-7xl mx-auto">
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

      <div class="nav-container">
        <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-between items-center gap-4">
          <!-- Search Bar -->
          <div class="flex-1 max-w-sm">
            <div class="relative">
              <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input type="text" id="searchUsers" placeholder="Search users..."
                     class="w-full pl-9 pr-4 py-2 border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#115D5B]">
            </div>
          </div>

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

            <div class="relative">
              <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['username'], 0, 1)); ?>.png"
                   alt="Profile Picture" class="profile-pic" onclick="toggleSidebar()"
                   title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)"
                   onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
              <div class="profile-pic" onclick="toggleSidebar()"
                   title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)"
                   style="display:none; background: linear-gradient(135deg,#115D5B,#103625); align-items:center; justify-content:center; color:white; font-size:16px; font-weight:bold; text-transform:uppercase;">
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
    <section class="relative bg-gradient-to-br from-[#115D5B] via-[#103625] to-[#0d2818] text-white py-12 mb-8">
      <div class="absolute inset-0 bg-black bg-opacity-20"></div>
      <div class="relative max-w-6xl mx-auto px-4">
        <div class="flex items-center mb-4">
          <i class="fas fa-users-cog text-3xl text-yellow-400 mr-4"></i>
          <h2 class="text-3xl font-bold">User Management</h2>
        </div>
        <p class="text-lg opacity-90">Manage user accounts, edit data, and control permissions</p>
      </div>
    </section>

    <!-- Statistics Cards -->
    <section class="max-w-6xl mx-auto px-4 mb-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
        <div class="stats-card p-6 rounded-lg shadow-md" style="border-left-color:#f59e0b">
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
        <div class="stats-card p-6 rounded-lg shadow-md" style="border-left-color:#3b82f6">
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
          <div class="flex flex-wrap justify-between items-center gap-4">
            <h3 class="text-xl font-bold text-[#115D5B]">All User Accounts</h3>
            <div class="flex gap-2 flex-wrap">
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
              <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center space-x-4">
                  <div class="relative">
                    <img src="Images/initials profile/<?php echo strtolower(substr($account['username'], 0, 1)); ?>.png"
                         alt="Profile" class="w-12 h-12 rounded-full border-2 border-gray-200"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-12 h-12 rounded-full border-2 border-gray-200 bg-gradient-to-br from-[#115D5B] to-[#103625] flex items-center justify-center text-white font-bold text-lg" style="display:none;">
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

                <div class="flex items-center gap-2 flex-wrap">
                  <span class="role-badge role-<?php echo $account['role']; ?>">
                    <?php echo $account['role'] === 'admin' ? 'Administrator' : 'User'; ?>
                  </span>
                  <button onclick="viewUserDetails(<?php echo $account['id']; ?>)" class="action-btn btn-view">
                    <i class="fas fa-eye"></i>View
                  </button>
                  <button onclick="openEditModal(<?php echo $account['id']; ?>)" class="action-btn btn-edit">
                    <i class="fas fa-edit"></i>Edit
                  </button>
                  <?php if($account['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                  <button onclick="deleteUser(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['username']); ?>')" class="action-btn btn-delete">
                    <i class="fas fa-trash"></i>Delete
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ===================== VIEW DETAILS MODAL ===================== -->
  <div id="userModal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-content">
      <div class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white p-5 rounded-t-lg flex justify-between items-center">
        <h3 class="text-xl font-bold"><i class="fas fa-user-circle mr-2"></i>User Details</h3>
        <button class="modal-close-btn" onclick="closeModal('userModal')" title="Close">&times;</button>
      </div>
      <div id="modalBody" class="p-6">
        <!-- Loaded via JS -->
      </div>
      <div class="px-6 pb-5 flex justify-end">
        <button onclick="closeModal('userModal')" class="action-btn btn-cancel">
          <i class="fas fa-times"></i>Close
        </button>
      </div>
    </div>
  </div>

  <!-- ===================== EDIT USER MODAL ===================== -->
  <div id="editModal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-content" style="max-width:750px;">
      <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-5 rounded-t-lg flex justify-between items-center">
        <h3 class="text-xl font-bold"><i class="fas fa-user-edit mr-2"></i>Edit User Account</h3>
        <button class="modal-close-btn" onclick="closeModal('editModal')" title="Close">&times;</button>
      </div>

      <!-- Tabs -->
      <div class="px-6 pt-4 flex gap-2 border-b border-gray-200 pb-3">
        <button class="tab-btn active" onclick="switchTab('tab-info')" id="tabBtn-info">
          <i class="fas fa-user mr-1"></i>Profile Info
        </button>
        <button class="tab-btn" onclick="switchTab('tab-account')" id="tabBtn-account">
          <i class="fas fa-lock mr-1"></i>Account & Role
        </button>
        <button class="tab-btn" onclick="switchTab('tab-password')" id="tabBtn-password">
          <i class="fas fa-key mr-1"></i>Reset Password
        </button>
      </div>

      <div id="editModalBody" class="p-6">
        <div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i><p class="mt-2">Loading...</p></div>
      </div>
    </div>
  </div>

  <!-- ===================== RESET PASSWORD CONFIRM MODAL ===================== -->
  <div id="resetModal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-content" style="max-width:420px;">
      <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white p-5 rounded-t-lg flex justify-between items-center">
        <h3 class="text-lg font-bold"><i class="fas fa-key mr-2"></i>Reset Password</h3>
        <button class="modal-close-btn" onclick="closeModal('resetModal')" title="Close">&times;</button>
      </div>
      <div class="p-6" id="resetModalBody"></div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white text-center py-8 mt-12">
    <div class="max-w-6xl mx-auto px-4">
      <p class="text-lg font-semibold">&copy; 2025 Camarines Norte Lowland Rainfed Research Station</p>
      <p class="text-sm opacity-75 mt-2">Admin Dashboard - User Management</p>
    </div>
  </footer>

  <script>
  // ── Helpers ──────────────────────────────────────────────────────────────────
  const CURRENT_ADMIN_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  // Close any modal by clicking the dark backdrop
  window.addEventListener('click', function(e) {
    ['userModal','editModal','resetModal'].forEach(id => {
      const m = document.getElementById(id);
      if (e.target === m) m.style.display = 'none';
    });
  });

  // Close modals with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      ['userModal','editModal','resetModal'].forEach(id => {
        document.getElementById(id).style.display = 'none';
      });
    }
  });

  // ── Sidebar ───────────────────────────────────────────────────────────────────
  function toggleSidebar() {
    const sb = document.getElementById('mySidebar');
    sb.style.width = sb.style.width === '280px' ? '0' : '280px';
  }
  function closeSidebar() {
    document.getElementById('mySidebar').style.width = '0';
  }
  document.addEventListener('click', function(e) {
    const sb = document.getElementById('mySidebar');
    if (!sb.contains(e.target) && !e.target.closest('.profile-pic') && sb.style.width === '280px') closeSidebar();
  });

  // ── Scroll header hide ────────────────────────────────────────────────────────
  let lastST = 0;
  window.addEventListener('scroll', () => {
    const st = window.scrollY;
    document.getElementById('main-header').style.transform = (st > 100 && st > lastST) ? 'translateY(-100%)' : 'translateY(0)';
    lastST = st <= 0 ? 0 : st;
  });

  // ── Logout ────────────────────────────────────────────────────────────────────
  function logout() {
    if (confirm('Are you sure you want to log out?')) {
      fetch('logout.php', { method: 'POST' }).then(() => { window.location.href = 'index.php'; });
    }
  }

  // ── Filter users ─────────────────────────────────────────────────────────────
  function filterUsers(role) {
    document.querySelectorAll('.user-card').forEach(c => {
      c.style.display = (role === 'all' || c.dataset.role === role) ? 'block' : 'none';
    });
    document.querySelectorAll('.filter-btn').forEach(b => {
      b.classList.remove('bg-[#115D5B]','bg-yellow-500','bg-blue-500','text-white');
      b.classList.add('bg-gray-200','text-gray-700');
    });
    const map = { all:'bg-[#115D5B]', admin:'bg-yellow-500', user:'bg-blue-500' };
    event.target.classList.remove('bg-gray-200','text-gray-700');
    event.target.classList.add(map[role], 'text-white');
  }

  // ── Search ────────────────────────────────────────────────────────────────────
  document.getElementById('searchUsers').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.user-card').forEach(card => {
      const text = card.textContent.toLowerCase();
      card.style.display = text.includes(q) ? 'block' : 'none';
    });
  });

  // ── VIEW DETAILS MODAL ────────────────────────────────────────────────────────
  function viewUserDetails(userId) {
    const modal = document.getElementById('userModal');
    const body  = document.getElementById('modalBody');
    body.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-[#115D5B]"></i><p class="mt-2 text-gray-600">Loading user details...</p></div>';
    modal.style.display = 'block';

    fetch('get_user_details.php?id=' + userId)
      .then(r => r.json())
      .then(data => {
        if (!data.success) { body.innerHTML = errorHtml('Error loading user details'); return; }
        const u = data.user;
        const isAdmin = u.role === 'admin';
        const isSelf  = u.id == CURRENT_ADMIN_ID;
        body.innerHTML = `
          <div class="flex items-center gap-4 mb-6 pb-5 border-b border-gray-200">
            <div class="relative flex-shrink-0">
              <img src="Images/initials profile/${u.username.charAt(0).toLowerCase()}.png"
                   alt="Profile" class="w-16 h-16 rounded-full border-4 border-[#115D5B]"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <div class="w-16 h-16 rounded-full border-4 border-[#115D5B] bg-gradient-to-br from-[#115D5B] to-[#103625] items-center justify-center text-white font-bold text-xl" style="display:none;">
                ${u.username.charAt(0).toUpperCase()}
              </div>
              ${isAdmin ? '<div class="absolute -top-1 -right-1 bg-yellow-500 text-white text-xs px-2 py-1 rounded-full"><i class="fas fa-crown"></i></div>' : ''}
            </div>
            <div>
              <h4 class="text-xl font-bold text-gray-900">${escHtml(u.name)}</h4>
              <p class="text-gray-500 text-sm">@${escHtml(u.username)}</p>
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold mt-1" style="background:${isAdmin?'#fef3c7':'#dbeafe'};color:${isAdmin?'#92400e':'#1e40af'}">
                <i class="${isAdmin?'fas fa-crown':'fas fa-user'} mr-1"></i>${isAdmin?'Administrator':'User'}
              </span>
            </div>
            ${isSelf ? '<span class="ml-auto text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold">You</span>' : ''}
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value">${escHtml(u.name)}</span></div>
            <div class="detail-row"><span class="detail-label">Username</span><span class="detail-value">@${escHtml(u.username)}</span></div>
            <div class="detail-row"><span class="detail-label">Email Address</span><span class="detail-value">${escHtml(u.email)}</span></div>
            <div class="detail-row"><span class="detail-label">Contact Number</span><span class="detail-value">${escHtml(u.contact)}</span></div>
            <div class="detail-row"><span class="detail-label">Birth Date</span><span class="detail-value">${formatDate(u.birth_date)}</span></div>
            <div class="detail-row"><span class="detail-label">Role</span><span class="detail-value">${isAdmin?'Administrator':'Regular User'}</span></div>
            <div class="detail-row md:col-span-2"><span class="detail-label">Address</span><span class="detail-value">${escHtml(u.address)}</span></div>
            <div class="detail-row"><span class="detail-label">Account Created</span><span class="detail-value">${formatDateTime(u.created_at)}</span></div>
            <div class="detail-row"><span class="detail-label">Last Updated</span><span class="detail-value">${formatDateTime(u.updated_at)}</span></div>
          </div>

          ${!isSelf ? `
          <div class="border-t border-gray-200 pt-5">
            <p class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-bolt mr-1 text-yellow-500"></i>Quick Actions</p>
            <div class="flex flex-wrap gap-2">
              <button onclick="openEditModal(${u.id}); closeModal('userModal');" class="action-btn btn-edit">
                <i class="fas fa-edit"></i>Edit User
              </button>
              ${isAdmin
                ? `<button onclick="changeRole(${u.id},'demote')" class="action-btn btn-demote"><i class="fas fa-user-minus"></i>Demote to User</button>`
                : `<button onclick="changeRole(${u.id},'promote')" class="action-btn btn-promote"><i class="fas fa-crown"></i>Promote to Admin</button>`
              }
              <button onclick="openResetPassword(${u.id}, '${escHtml(u.username)}')" class="action-btn btn-reset">
                <i class="fas fa-key"></i>Reset Password
              </button>
              <button onclick="deleteUser(${u.id},'${escHtml(u.username)}')" class="action-btn btn-delete">
                <i class="fas fa-trash"></i>Delete Account
              </button>
            </div>
          </div>` : '<p class="text-center text-sm text-gray-500 italic border-t pt-4">Cannot modify your own account from here. Use Edit Profile instead.</p>'}
        `;
      })
      .catch(() => { body.innerHTML = errorHtml('Network error. Could not load user details.'); });
  }

  // ── EDIT MODAL ────────────────────────────────────────────────────────────────
  let currentEditUserId = null;

  function openEditModal(userId) {
    currentEditUserId = userId;
    const modal = document.getElementById('editModal');
    const body  = document.getElementById('editModalBody');
    body.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i><p class="mt-2 text-gray-600">Loading user data...</p></div>';
    modal.style.display = 'block';
    switchTab('tab-info');

    fetch('get_user_details.php?id=' + userId)
      .then(r => r.json())
      .then(data => {
        if (!data.success) { body.innerHTML = errorHtml('Error loading user data'); return; }
        const u = data.user;
        const isSelf = u.id == CURRENT_ADMIN_ID;
        body.innerHTML = `
          <!-- Tab: Profile Info -->
          <div id="tab-info" class="tab-panel active">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                <input type="text" id="edit_name" class="form-field" value="${escHtml(u.name)}" placeholder="Full name">
              </div>
              <div>
                <label class="form-label">Username <span class="text-red-500">*</span></label>
                <input type="text" id="edit_username" class="form-field" value="${escHtml(u.username)}" placeholder="Username">
              </div>
              <div>
                <label class="form-label">Email Address <span class="text-red-500">*</span></label>
                <input type="email" id="edit_email" class="form-field" value="${escHtml(u.email)}" placeholder="Email">
              </div>
              <div>
                <label class="form-label">Contact Number <span class="text-red-500">*</span></label>
                <input type="text" id="edit_contact" class="form-field" value="${escHtml(u.contact)}" placeholder="Contact number">
              </div>
              <div>
                <label class="form-label">Birth Date <span class="text-red-500">*</span></label>
                <input type="date" id="edit_birth_date" class="form-field" value="${u.birth_date}">
              </div>
              <div class="md:col-span-2">
                <label class="form-label">Address <span class="text-red-500">*</span></label>
                <textarea id="edit_address" class="form-field" rows="3" placeholder="Full address">${escHtml(u.address)}</textarea>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
              <button onclick="closeModal('editModal')" class="action-btn btn-cancel"><i class="fas fa-times"></i>Cancel</button>
              <button onclick="saveProfileInfo(${u.id})" class="action-btn btn-save"><i class="fas fa-save"></i>Save Changes</button>
            </div>
          </div>

          <!-- Tab: Account & Role -->
          <div id="tab-account" class="tab-panel">
            <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
              <p class="text-sm text-gray-600"><strong>Current Role:</strong>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold ml-1" style="background:${u.role==='admin'?'#fef3c7':'#dbeafe'};color:${u.role==='admin'?'#92400e':'#1e40af'}">
                  <i class="${u.role==='admin'?'fas fa-crown':'fas fa-user'} mr-1"></i>${u.role==='admin'?'Administrator':'Regular User'}
                </span>
              </p>
            </div>
            ${isSelf ? `<div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 text-yellow-800 text-sm"><i class="fas fa-exclamation-triangle mr-2"></i>You cannot change your own role.</div>` : `
            <div class="space-y-3">
              ${u.role === 'user'
                ? `<div class="flex items-start gap-3 p-4 border-2 border-yellow-200 rounded-lg bg-yellow-50">
                    <i class="fas fa-crown text-yellow-500 mt-1"></i>
                    <div class="flex-1">
                      <p class="font-semibold text-gray-800">Promote to Administrator</p>
                      <p class="text-sm text-gray-600">This user will gain full admin access to the dashboard.</p>
                    </div>
                    <button onclick="changeRole(${u.id},'promote')" class="action-btn btn-promote"><i class="fas fa-crown"></i>Promote</button>
                  </div>`
                : `<div class="flex items-start gap-3 p-4 border-2 border-orange-200 rounded-lg bg-orange-50">
                    <i class="fas fa-user-minus text-orange-500 mt-1"></i>
                    <div class="flex-1">
                      <p class="font-semibold text-gray-800">Demote to Regular User</p>
                      <p class="text-sm text-gray-600">This administrator will lose admin privileges.</p>
                    </div>
                    <button onclick="changeRole(${u.id},'demote')" class="action-btn btn-demote"><i class="fas fa-user-minus"></i>Demote</button>
                  </div>`
              }
              <div class="flex items-start gap-3 p-4 border-2 border-red-200 rounded-lg bg-red-50">
                <i class="fas fa-trash text-red-500 mt-1"></i>
                <div class="flex-1">
                  <p class="font-semibold text-gray-800">Delete Account</p>
                  <p class="text-sm text-gray-600">Permanently remove this account. This cannot be undone.</p>
                </div>
                <button onclick="deleteUser(${u.id},'${escHtml(u.username)}')" class="action-btn btn-delete"><i class="fas fa-trash"></i>Delete</button>
              </div>
            </div>`}
          </div>

          <!-- Tab: Reset Password -->
          <div id="tab-password" class="tab-panel">
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
              <p class="text-sm text-purple-800"><i class="fas fa-info-circle mr-2"></i>Set a new password for <strong>@${escHtml(u.username)}</strong>. The user will need to use this new password on their next login.</p>
            </div>
            <div class="space-y-4">
              <div>
                <label class="form-label">New Password <span class="text-red-500">*</span></label>
                <div class="relative">
                  <input type="password" id="new_password" class="form-field pr-10" placeholder="Enter new password">
                  <button type="button" onclick="togglePw('new_password','eyeNew')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                    <i id="eyeNew" class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <div>
                <label class="form-label">Confirm New Password <span class="text-red-500">*</span></label>
                <div class="relative">
                  <input type="password" id="confirm_new_password" class="form-field pr-10" placeholder="Confirm new password">
                  <button type="button" onclick="togglePw('confirm_new_password','eyeConfirm')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                    <i id="eyeConfirm" class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <div id="pw_strength" class="text-xs font-medium"></div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
              <button onclick="closeModal('editModal')" class="action-btn btn-cancel"><i class="fas fa-times"></i>Cancel</button>
              <button onclick="saveNewPassword(${u.id})" class="action-btn btn-reset"><i class="fas fa-key"></i>Reset Password</button>
            </div>
          </div>
        `;

        // Password strength watcher
        document.getElementById('new_password').addEventListener('input', function() {
          const v = this.value;
          let s = 0, msg = '', color = '';
          if (v.length >= 8) s++;
          if (/[A-Z]/.test(v)) s++;
          if (/[0-9]/.test(v)) s++;
          if (/[^A-Za-z0-9]/.test(v)) s++;
          if (!v) { document.getElementById('pw_strength').textContent = ''; return; }
          if (s <= 1) { msg='Weak'; color='text-red-600'; }
          else if (s === 2) { msg='Fair'; color='text-yellow-600'; }
          else if (s === 3) { msg='Good'; color='text-blue-600'; }
          else { msg='Strong'; color='text-green-600'; }
          document.getElementById('pw_strength').innerHTML = `Password strength: <span class="${color} font-bold">${msg}</span>`;
        });
      })
      .catch(() => { body.innerHTML = errorHtml('Network error. Could not load user data.'); });
  }

  // ── Save Profile Info ─────────────────────────────────────────────────────────
  function saveProfileInfo(userId) {
    const payload = {
      action: 'update_profile',
      user_id: userId,
      name:       document.getElementById('edit_name').value.trim(),
      username:   document.getElementById('edit_username').value.trim(),
      email:      document.getElementById('edit_email').value.trim(),
      contact:    document.getElementById('edit_contact').value.trim(),
      birth_date: document.getElementById('edit_birth_date').value,
      address:    document.getElementById('edit_address').value.trim(),
    };
    if (!payload.name || !payload.username || !payload.email || !payload.contact || !payload.birth_date || !payload.address) {
      showToast('Please fill in all required fields.', 'error'); return;
    }
    fetch('manage_user_role.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) { showToast('User profile updated successfully!', 'success'); closeModal('editModal'); setTimeout(()=>location.reload(),1000); }
      else showToast('Error: ' + data.message, 'error');
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
  }

  // ── Save New Password ─────────────────────────────────────────────────────────
  function saveNewPassword(userId) {
    const pw  = document.getElementById('new_password').value;
    const cpw = document.getElementById('confirm_new_password').value;
    if (!pw) { showToast('Please enter a new password.', 'error'); return; }
    if (pw !== cpw) { showToast('Passwords do not match.', 'error'); return; }
    if (pw.length < 4) { showToast('Password must be at least 4 characters.', 'error'); return; }
    fetch('manage_user_role.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'reset_password', user_id: userId, new_password: pw })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) { showToast('Password reset successfully!', 'success'); closeModal('editModal'); }
      else showToast('Error: ' + data.message, 'error');
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
  }

  // ── Change Role ───────────────────────────────────────────────────────────────
  function changeRole(userId, action) {
    const label = action === 'promote' ? 'promote this user to Administrator' : 'demote this Administrator to regular user';
    if (!confirm('Are you sure you want to ' + label + '?')) return;
    fetch('manage_user_role.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, user_id: userId })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(action === 'promote' ? 'User promoted to Administrator!' : 'Administrator demoted to User!', 'success');
        closeModal('userModal'); closeModal('editModal');
        setTimeout(() => location.reload(), 1000);
      } else showToast('Error: ' + data.message, 'error');
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
  }

  // ── Delete User ───────────────────────────────────────────────────────────────
  function deleteUser(userId, username) {
    if (!confirm('Delete account "@' + username + '"? This CANNOT be undone!')) return;
    fetch('manage_user_role.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', user_id: userId })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Account deleted successfully.', 'success');
        closeModal('userModal'); closeModal('editModal');
        setTimeout(() => location.reload(), 1000);
      } else showToast('Error: ' + data.message, 'error');
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
  }

  // ── Tab Switching ─────────────────────────────────────────────────────────────
  function switchTab(tabId) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const panel = document.getElementById(tabId);
    if (panel) panel.classList.add('active');
    const btnId = 'tabBtn-' + tabId.replace('tab-','');
    const btn = document.getElementById(btnId);
    if (btn) btn.classList.add('active');
  }

  // ── Password Toggle ───────────────────────────────────────────────────────────
  function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    if (inp.type === 'password') { inp.type = 'text'; ico.classList.replace('fa-eye','fa-eye-slash'); }
    else { inp.type = 'password'; ico.classList.replace('fa-eye-slash','fa-eye'); }
  }

  // ── Toast ─────────────────────────────────────────────────────────────────────
  function showToast(msg, type='info') {
    const colors = { success:'#115D5B', error:'#ef4444', info:'#3b82f6' };
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;padding:14px 20px;border-radius:10px;color:#fff;font-size:14px;font-weight:600;background:${colors[type]||colors.info};box-shadow:0 8px 24px rgba(0,0,0,0.2);transition:opacity 0.3s;max-width:320px;`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),400); }, 3000);
  }

  // ── Utility ───────────────────────────────────────────────────────────────────
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });
  }
  function formatDateTime(d) {
    if (!d) return '—';
    const dt = new Date(d);
    return dt.toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' }) + ' at ' + dt.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit' });
  }
  function errorHtml(msg) {
    return `<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-3xl mb-3"></i><p class="font-semibold">${msg}</p></div>`;
  }
  </script>
</body>
</html>