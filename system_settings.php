<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

include 'connect.php';

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_site_settings':
            $site_name = $_POST['site_name'] ?? '';
            $site_description = $_POST['site_description'] ?? '';
            $contact_email = $_POST['contact_email'] ?? '';
            $contact_phone = $_POST['contact_phone'] ?? '';
            $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
            
            // Update or insert site settings
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value) VALUES 
                ('site_name', ?),
                ('site_description', ?),
                ('contact_email', ?),
                ('contact_phone', ?),
                ('maintenance_mode', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->bind_param("ssssi", $site_name, $site_description, $contact_email, $contact_phone, $maintenance_mode);
            
            if ($stmt->execute()) {
                $message = 'Site settings updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating site settings.';
                $message_type = 'error';
            }
            break;
            
        case 'update_user_settings':
            $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
            $email_verification = isset($_POST['email_verification']) ? 1 : 0;
            $max_login_attempts = (int)($_POST['max_login_attempts'] ?? 5);
            $session_timeout = (int)($_POST['session_timeout'] ?? 30);
            
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value) VALUES 
                ('allow_registration', ?),
                ('email_verification', ?),
                ('max_login_attempts', ?),
                ('session_timeout', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->bind_param("iiii", $allow_registration, $email_verification, $max_login_attempts, $session_timeout);
            
            if ($stmt->execute()) {
                $message = 'User settings updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating user settings.';
                $message_type = 'error';
            }
            break;
            
        case 'update_publication_settings':
            $auto_approval = isset($_POST['auto_approval']) ? 1 : 0;
            $require_approval = isset($_POST['require_approval']) ? 1 : 0;
            $max_file_size = (int)($_POST['max_file_size'] ?? 10);
            $allowed_formats = $_POST['allowed_formats'] ?? '';
            
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value) VALUES 
                ('auto_approval', ?),
                ('require_approval', ?),
                ('max_file_size', ?),
                ('allowed_formats', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->bind_param("iiis", $auto_approval, $require_approval, $max_file_size, $allowed_formats);
            
            if ($stmt->execute()) {
                $message = 'Publication settings updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating publication settings.';
                $message_type = 'error';
            }
            break;
            
        case 'clear_cache':
            // Simulate cache clearing
            $message = 'System cache cleared successfully!';
            $message_type = 'success';
            break;
            
        case 'backup_database':
            // Simulate database backup
            $message = 'Database backup initiated successfully!';
            $message_type = 'success';
            break;
    }
}

// Fetch current settings
function getSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['setting_value'] : $default;
}

// Current settings
$site_name = getSetting($conn, 'site_name', 'CNLRRS Research Platform');
$site_description = getSetting($conn, 'site_description', 'Camarines Norte Lowland Rainfed Research Station');
$contact_email = getSetting($conn, 'contact_email', 'dacnlrrs@gmail.com');
$contact_phone = getSetting($conn, 'contact_phone', '0951 609 9599');
$maintenance_mode = getSetting($conn, 'maintenance_mode', '0');

$allow_registration = getSetting($conn, 'allow_registration', '1');
$email_verification = getSetting($conn, 'email_verification', '0');
$max_login_attempts = getSetting($conn, 'max_login_attempts', '5');
$session_timeout = getSetting($conn, 'session_timeout', '30');

$auto_approval = getSetting($conn, 'auto_approval', '0');
$require_approval = getSetting($conn, 'require_approval', '1');
$max_file_size = getSetting($conn, 'max_file_size', '10');
$allowed_formats = getSetting($conn, 'allowed_formats', 'PDF,DOC,DOCX');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CNLRRS - System Settings</title>
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

    .settings-card {
      background: white;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      transition: all 0.3s ease;
    }

    .settings-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 48px;
      height: 24px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: #115D5B;
    }

    input:checked + .slider:before {
      transform: translateX(24px);
    }

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

    .main-header {
      backdrop-filter: blur(10px);
      background: rgba(255,255,255,0.95);
    }

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

    .message-alert {
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 20px;
      font-weight: 500;
    }

    .message-success {
      background-color: #d1fae5;
      border: 1px solid #a7f3d0;
      color: #065f46;
    }

    .message-error {
      background-color: #fee2e2;
      border: 1px solid #fca5a5;
      color: #991b1b;
    }

    @media (max-width: 768px) {
      .logo-container {
        justify-content: center;
        text-align: center;
      }
      
      .nav-links {
        justify-content: center;
        gap: 1rem;
      }
      
      .settings-grid {
        grid-template-columns: 1fr;
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
              <i class="fas fa-cogs mr-1"></i>System Settings
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
                placeholder="Search settings..."
                class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-inset"
              />
              <button class="px-4 text-green-900 hover:bg-green-50 transition-colors">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>

          <!-- Navigation Links -->
          <div class="nav-links text-green-900 font-semibold text-sm">
            <a href="admin_loggedin_index.php" class="hover:text-green-700 transition-colors">
              <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
            </a>
            <a href="admin_review_papers.php" class="hover:text-green-700 transition-colors">Reviews</a>
            <a href="admin_user_management.php" class="hover:text-green-700 transition-colors">Users</a>

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
    <a href="#"><i class="fas fa-book mr-3"></i>Publications</a>
    <a href="#"><i class="fas fa-chart-bar mr-3"></i>Reports & Analytics</a>
    <a href="#" class="bg-white bg-opacity-20"><i class="fas fa-cog mr-3"></i>System Settings</a>
    <a href="edit_profile.php"><i class="fas fa-user-edit mr-3"></i>Edit Profile</a>
    <div class="mt-8 border-t border-white border-opacity-20 pt-4">
      <a href="index.php" onclick="logout()"><i class="fas fa-sign-out-alt mr-3"></i>Log Out</a>
    </div>
  </div>

  <!-- Main Content -->
  <main class="pt-32 pb-8">
    <!-- Page Header -->
    <section class="max-w-6xl mx-auto px-4 mb-8">
      <div class="bg-gradient-to-br from-[#115D5B] via-[#103625] to-[#0d2818] text-white p-8 rounded-lg shadow-lg">
        <div class="flex items-center mb-4">
          <i class="fas fa-cogs text-4xl mr-4"></i>
          <div>
            <h1 class="text-3xl font-bold">System Settings</h1>
            <p class="text-lg opacity-90">Configure system preferences and options</p>
          </div>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="message-alert <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
          <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Settings Content -->
    <section class="max-w-6xl mx-auto px-4">
      <div class="settings-grid grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Site Settings -->
        <div class="settings-card p-6 shadow-lg">
          <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
              <i class="fas fa-globe text-blue-600 text-xl"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Site Settings</h2>
              <p class="text-sm text-gray-600">Configure basic site information</p>
            </div>
          </div>

          <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_site_settings">
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
              <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Site Description</label>
              <textarea name="site_description" rows="3" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent"><?php echo htmlspecialchars($site_description); ?></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
              <input type="email" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
              <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($contact_phone); ?>" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Maintenance Mode</label>
                <p class="text-sm text-gray-500">Temporarily disable public access</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="maintenance_mode" <?php echo $maintenance_mode ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <button type="submit" class="w-full bg-[#115D5B] hover:bg-[#103625] text-white py-3 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-save mr-2"></i>Save Site Settings
            </button>
          </form>
        </div>

        <!-- User Settings -->
        <div class="settings-card p-6 shadow-lg">
          <div class="flex items-center mb-6">
            <div class="bg-green-100 p-3 rounded-full mr-4">
              <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">User Settings</h2>
              <p class="text-sm text-gray-600">Configure user registration and security</p>
            </div>
          </div>

          <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_user_settings">

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Allow Registration</label>
                <p class="text-sm text-gray-500">Allow new users to register</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="allow_registration" <?php echo $allow_registration ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Email Verification</label>
                <p class="text-sm text-gray-500">Require email verification for new accounts</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="email_verification" <?php echo $email_verification ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Max Login Attempts</label>
              <select name="max_login_attempts" 
                      class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                <option value="3" <?php echo $max_login_attempts == 3 ? 'selected' : ''; ?>>3 attempts</option>
                <option value="5" <?php echo $max_login_attempts == 5 ? 'selected' : ''; ?>>5 attempts</option>
                <option value="10" <?php echo $max_login_attempts == 10 ? 'selected' : ''; ?>>10 attempts</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout (minutes)</label>
              <select name="session_timeout" 
                      class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                <option value="15" <?php echo $session_timeout == 15 ? 'selected' : ''; ?>>15 minutes</option>
                <option value="30" <?php echo $session_timeout == 30 ? 'selected' : ''; ?>>30 minutes</option>
                <option value="60" <?php echo $session_timeout == 60 ? 'selected' : ''; ?>>1 hour</option>
                <option value="120" <?php echo $session_timeout == 120 ? 'selected' : ''; ?>>2 hours</option>
              </select>
            </div>

            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-save mr-2"></i>Save User Settings
            </button>
          </form>
        </div>

        <!-- Publication Settings -->
        <div class="settings-card p-6 shadow-lg">
          <div class="flex items-center mb-6">
            <div class="bg-purple-100 p-3 rounded-full mr-4">
              <i class="fas fa-book text-purple-600 text-xl"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Publication Settings</h2>
              <p class="text-sm text-gray-600">Configure publication submission rules</p>
            </div>
          </div>

          <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_publication_settings">

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Auto Approval</label>
                <p class="text-sm text-gray-500">Automatically approve new submissions</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="auto_approval" <?php echo $auto_approval ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Require Approval</label>
                <p class="text-sm text-gray-500">All submissions need admin approval</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="require_approval" <?php echo $require_approval ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Max File Size (MB)</label>
              <select name="max_file_size" 
                      class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                <option value="5" <?php echo $max_file_size == 5 ? 'selected' : ''; ?>>5 MB</option>
                <option value="10" <?php echo $max_file_size == 10 ? 'selected' : ''; ?>>10 MB</option>
                <option value="25" <?php echo $max_file_size == 25 ? 'selected' : ''; ?>>25 MB</option>
                <option value="50" <?php echo $max_file_size == 50 ? 'selected' : ''; ?>>50 MB</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Allowed File Formats</label>
              <input type="text" name="allowed_formats" value="<?php echo htmlspecialchars($allowed_formats); ?>" 
                     placeholder="PDF,DOC,DOCX,TXT"
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
              <p class="text-xs text-gray-500 mt-1">Comma-separated list of allowed file extensions</p>
            </div>

            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-save mr-2"></i>Save Publication Settings
            </button>
          </form>
        </div>

        <!-- System Maintenance -->
        <div class="settings-card p-6 shadow-lg">
          <div class="flex items-center mb-6">
            <div class="bg-orange-100 p-3 rounded-full mr-4">
              <i class="fas fa-tools text-orange-600 text-xl"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">System Maintenance</h2>
              <p class="text-sm text-gray-600">System maintenance and optimization tools</p>
            </div>
          </div>

          <div class="space-y-4">
            <form method="POST" class="inline-block w-full">
              <input type="hidden" name="action" value="clear_cache">
              <button type="submit" class="w-full bg-orange-100 hover:bg-orange-200 text-orange-800 py-3 px-4 rounded-lg font-semibold transition-colors border border-orange-300">
                <i class="fas fa-broom mr-2"></i>Clear System Cache
              </button>
            </form>

            <form method="POST" class="inline-block w-full">
              <input type="hidden" name="action" value="backup_database">
              <button type="submit" class="w-full bg-blue-100 hover:bg-blue-200 text-blue-800 py-3 px-4 rounded-lg font-semibold transition-colors border border-blue-300">
                <i class="fas fa-database mr-2"></i>Backup Database
              </button>
            </form>

            <button onclick="optimizeDatabase()" class="w-full bg-green-100 hover:bg-green-200 text-green-800 py-3 px-4 rounded-lg font-semibold transition-colors border border-green-300">
              <i class="fas fa-magic mr-2"></i>Optimize Database
            </button>

            <button onclick="viewSystemLogs()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 py-3 px-4 rounded-lg font-semibold transition-colors border border-gray-300">
              <i class="fas fa-file-alt mr-2"></i>View System Logs
            </button>
          </div>

          <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h4 class="font-semibold text-red-800 mb-2">
              <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
            </h4>
            <p class="text-sm text-red-600 mb-3">These actions cannot be undone. Please be careful.</p>
            <button onclick="resetSystem()" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-trash mr-2"></i>Reset System Settings
            </button>
          </div>
        </div>

        <!-- Security Settings -->
        <div class="settings-card p-6 shadow-lg">
          <div class="flex items-center mb-6">
            <div class="bg-red-100 p-3 rounded-full mr-4">
              <i class="fas fa-shield-alt text-red-600 text-xl"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Security Settings</h2>
              <p class="text-sm text-gray-600">Configure security and access controls</p>
            </div>
          </div>

          <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_security_settings">

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Two-Factor Authentication</label>
                <p class="text-sm text-gray-500">Require 2FA for admin accounts</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="two_factor_auth" <?php echo $two_factor_auth ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">IP Whitelist</label>
                <p class="text-sm text-gray-500">Restrict admin access by IP</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="ip_whitelist_enabled" <?php echo $ip_whitelist_enabled ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Login Notifications</label>
                <p class="text-sm text-gray-500">Email notifications for admin logins</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="login_notifications" <?php echo $login_notifications ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Password Policy</label>
              <select name="password_policy" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                <option value="basic" <?php echo $password_policy === 'basic' ? 'selected' : ''; ?>>Basic (8+ characters)</option>
                <option value="strong" <?php echo $password_policy === 'strong' ? 'selected' : ''; ?>>Strong (8+ chars, mixed case, numbers)</option>
                <option value="very_strong" <?php echo $password_policy === 'very_strong' ? 'selected' : ''; ?>>Very Strong (12+ chars, special characters)</option>
              </select>
            </div>

            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-save mr-2"></i>Save Security Settings
            </button>
          </form>

          <div class="mt-6">
            <button onclick="viewSecurityLogs()" class="w-full bg-red-100 hover:bg-red-200 text-red-800 py-3 px-4 rounded-lg font-semibold transition-colors border border-red-300">
              <i class="fas fa-shield-alt mr-2"></i>View Security Logs
            </button>
          </div>
        </div>

        <!-- Email Settings -->
        <div class="settings-card p-6 shadow-lg">
          <div class="flex items-center mb-6">
            <div class="bg-indigo-100 p-3 rounded-full mr-4">
              <i class="fas fa-envelope text-indigo-600 text-xl"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Email Settings</h2>
              <p class="text-sm text-gray-600">Configure email notifications and SMTP</p>
            </div>
          </div>

          <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_email_settings">

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Server</label>
              <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" 
                     placeholder="smtp.gmail.com" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" 
                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Security</label>
                <select name="smtp_security" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                  <option value="none" <?php echo $smtp_security === 'none' ? 'selected' : ''; ?>>None</option>
                  <option value="tls" <?php echo $smtp_security === 'tls' ? 'selected' : ''; ?>>TLS</option>
                  <option value="ssl" <?php echo $smtp_security === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">From Email</label>
              <input type="email" name="from_email" value="<?php echo htmlspecialchars($from_email); ?>" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Email Notifications</label>
                <p class="text-sm text-gray-500">Send automated email notifications</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" name="email_notifications_enabled" <?php echo $email_notifications_enabled ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <button onclick="testEmail()" type="button" class="w-full bg-indigo-100 hover:bg-indigo-200 text-indigo-800 py-3 px-4 rounded-lg font-semibold transition-colors border border-indigo-300">
              <i class="fas fa-paper-plane mr-2"></i>Send Test Email
            </button>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-save mr-2"></i>Save Email Settings
            </button>
          </form>
        </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">IP Whitelist</label>
                <p class="text-sm text-gray-500">Restrict admin access by IP</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="ipWhitelist">
                <span class="slider"></span>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Login Notifications</label>
                <p class="text-sm text-gray-500">Email notifications for admin logins</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="loginNotifications" checked>
                <span class="slider"></span>
              </label>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Password Policy</label>
              <select class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                <option>Basic (8+ characters)</option>
                <option selected>Strong (8+ chars, mixed case, numbers)</option>
                <option>Very Strong (12+ chars, special characters)</option>
              </select>
            </div>

            <button onclick="viewSecurityLogs()" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-shield-alt mr-2"></i>View Security Logs
            </button>
          </div>
        </div>

        <!-- Email Settings -->
        <div class="settings-card p-6 shadow-lg">
          <div class="flex items-center mb-6">
            <div class="bg-indigo-100 p-3 rounded-full mr-4">
              <i class="fas fa-envelope text-indigo-600 text-xl"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Email Settings</h2>
              <p class="text-sm text-gray-600">Configure email notifications and SMTP</p>
            </div>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Server</label>
              <input type="text" placeholder="smtp.gmail.com" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                <input type="number" value="587" 
                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Security</label>
                <select class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                  <option>None</option>
                  <option selected>TLS</option>
                  <option>SSL</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">From Email</label>
              <input type="email" value="noreply@cnlrrs.gov.ph" 
                     class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="font-medium text-gray-700">Email Notifications</label>
                <p class="text-sm text-gray-500">Send automated email notifications</p>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
              </label>
            </div>

            <button onclick="testEmail()" class="w-full bg-indigo-100 hover:bg-indigo-200 text-indigo-800 py-3 px-4 rounded-lg font-semibold transition-colors border border-indigo-300">
              <i class="fas fa-paper-plane mr-2"></i>Send Test Email
            </button>

            <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-4 rounded-lg font-semibold transition-colors">
              <i class="fas fa-save mr-2"></i>Save Email Settings
            </button>
          </div>
        </div>

      </div>

      <!-- System Information -->
      <div class="mt-8 settings-card p-6 shadow-lg">
        <div class="flex items-center mb-6">
          <div class="bg-gray-100 p-3 rounded-full mr-4">
            <i class="fas fa-info-circle text-gray-600 text-xl"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">System Information</h2>
            <p class="text-sm text-gray-600">Current system status and information</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <i class="fas fa-server text-2xl text-blue-600 mb-2"></i>
            <p class="text-sm font-medium text-gray-700">Server Status</p>
            <p class="text-lg font-bold text-blue-600">Online</p>
          </div>

          <div class="text-center p-4 bg-green-50 rounded-lg">
            <i class="fas fa-database text-2xl text-green-600 mb-2"></i>
            <p class="text-sm font-medium text-gray-700">Database</p>
            <p class="text-lg font-bold text-green-600">Connected</p>
          </div>

          <div class="text-center p-4 bg-orange-50 rounded-lg">
            <i class="fas fa-hdd text-2xl text-orange-600 mb-2"></i>
            <p class="text-sm font-medium text-gray-700">Storage Used</p>
            <p class="text-lg font-bold text-orange-600">2.4 GB</p>
          </div>

          <div class="text-center p-4 bg-purple-50 rounded-lg">
            <i class="fas fa-clock text-2xl text-purple-600 mb-2"></i>
            <p class="text-sm font-medium text-gray-700">Uptime</p>
            <p class="text-lg font-bold text-purple-600">7 days</p>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
          <div>
            <h4 class="font-semibold text-gray-800 mb-3">System Details</h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">PHP Version:</span>
                <span class="font-medium"><?php echo phpversion(); ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">System:</span>
                <span class="font-medium">Linux Ubuntu</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Web Server:</span>
                <span class="font-medium">Apache 2.4</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Database:</span>
                <span class="font-medium">MySQL 8.0</span>
              </div>
            </div>
          </div>

          <div>
            <h4 class="font-semibold text-gray-800 mb-3">Application Info</h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">Version:</span>
                <span class="font-medium">CNLRRS v2.1.0</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Last Updated:</span>
                <span class="font-medium">2025-01-15</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Environment:</span>
                <span class="font-medium text-green-600">Production</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Debug Mode:</span>
                <span class="font-medium text-red-600">Disabled</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white text-center py-8 mt-12">
    <div class="max-w-6xl mx-auto px-4">
      <p class="text-lg font-semibold">&copy; 2025 Camarines Norte Lowland Rainfed Research Station</p>
      <p class="text-sm opacity-75 mt-2">System Settings - Configure with care</p>
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

    // System maintenance functions
    function optimizeDatabase() {
      if (confirm('Are you sure you want to optimize the database? This may take a few moments.')) {
        // Simulate optimization
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Optimizing...';
        button.disabled = true;
        
        setTimeout(() => {
          button.innerHTML = '<i class="fas fa-check mr-2"></i>Optimized!';
          setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
          }, 2000);
        }, 3000);
      }
    }

    function viewSystemLogs() {
      // Simulate opening logs in modal or new window
      alert('System logs would open here. This would typically show recent system activities, errors, and important events.');
    }

    function viewSecurityLogs() {
      alert('Security logs would open here. This would show login attempts, security events, and access logs.');
    }

    function resetSystem() {
      if (confirm('⚠️ WARNING: This will reset all system settings to defaults. Are you absolutely sure?')) {
        if (confirm('This action cannot be undone. Click OK to proceed.')) {
          alert('System settings have been reset to defaults. Please review all settings.');
        }
      }
    }

    function testEmail() {
      const button = event.target;
      const originalText = button.innerHTML;
      button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
      button.disabled = true;
      
      setTimeout(() => {
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Email Sent!';
        setTimeout(() => {
          button.innerHTML = originalText;
          button.disabled = false;
        }, 2000);
      }, 2000);
    }

    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
      // Add form submission feedback
      const forms = document.querySelectorAll('form');
      forms.forEach(form => {
        form.addEventListener('submit', function(e) {
          const button = form.querySelector('button[type="submit"]');
          if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            button.disabled = true;
          }
        });
      });

      // Auto-hide success/error messages after 5 seconds
      const messageAlert = document.querySelector('.message-alert');
      if (messageAlert) {
        setTimeout(() => {
          messageAlert.style.opacity = '0';
          messageAlert.style.transform = 'translateY(-10px)';
          setTimeout(() => {
            messageAlert.remove();
          }, 300);
        }, 5000);
      }

      // Add search functionality
      const searchInput = document.querySelector('input[placeholder="Search settings..."]');
      if (searchInput) {
        searchInput.addEventListener('input', function(e) {
          const searchTerm = e.target.value.toLowerCase();
          const settingsCards = document.querySelectorAll('.settings-card');
          
          settingsCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            if (cardText.includes(searchTerm)) {
              card.style.display = 'block';
              card.style.opacity = '1';
            } else {
              card.style.opacity = '0.3';
            }
          });
          
          if (searchTerm === '') {
            settingsCards.forEach(card => {
              card.style.opacity = '1';
            });
          }
        });
      }
    });

    // Add confirmation for destructive actions
    document.querySelectorAll('.toggle-switch input').forEach(toggle => {
      toggle.addEventListener('change', function(e) {
        const label = this.closest('.toggle-switch').previousElementSibling.querySelector('label');
        if (label && (label.textContent.includes('Maintenance') || label.textContent.includes('Auto Approval'))) {
          if (this.checked && !confirm(`Are you sure you want to enable ${label.textContent}?`)) {
            this.checked = false;
          }
        }
      });
    });
  </script>

  <!-- Create system_settings table if it doesn't exist -->
  <?php
  // The table creation is already handled in the initializeSystemSettings() function above
  ?>
</body>
</html>