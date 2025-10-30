<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

// Include the database connection file
require_once 'connect.php';

$error_message = '';
$success_message = '';

// Get current user data
$username = $_SESSION['username'] ?? '';
$current_user_sql = "SELECT * FROM accounts WHERE username = ?";
$stmt = $conn->prepare($current_user_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    header('Location: edit_profile.php');
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $fullname = trim($_POST['fullname'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $is_outside_philippines = $_POST['is_outside_philippines'] ?? 'false';
    $general_address = trim($_POST['general_address'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($fullname) || empty($contact_number) || empty($email) || empty($birth_date)) {
        $error_message = "All required fields must be filled.";
    } else {
        // Validate password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error_message = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $error_message = "Password must be at least 6 characters long.";
            }
        }

        // Validate address
        if ($is_outside_philippines === 'false' && (empty($barangay) || empty($municipality) || empty($province))) {
            $error_message = "Please select your complete address or indicate if you are outside the Philippines.";
        }

        if (empty($error_message)) {
            // Determine the address to save
            $address = ($is_outside_philippines === 'true') ? $general_address : implode(', ', array_filter([$barangay, $municipality, $province]));

            // Update user data
            if (!empty($new_password)) {
                // Update with new password (should be hashed in production)
                $update_sql = "UPDATE accounts SET name = ?, contact = ?, email = ?, birth_date = ?, address = ?, password = ? WHERE username = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("sssssss", $fullname, $contact_number, $email, $birth_date, $address, $new_password, $username);
            } else {
                // Update without changing password
                $update_sql = "UPDATE accounts SET name = ?, contact = ?, email = ?, birth_date = ?, address = ? WHERE username = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssssss", $fullname, $contact_number, $email, $birth_date, $address, $username);
            }

            if ($stmt->execute()) {
                // Update session name if it changed
                $_SESSION['name'] = $fullname;
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $user_data['name'] = $fullname;
                $user_data['contact'] = $contact_number;
                $user_data['email'] = $email;
                $user_data['birth_date'] = $birth_date;
                $user_data['address'] = $address;
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Parse address for form fields
$address_parts = explode(', ', $user_data['address']);
$is_philippines_address = count($address_parts) >= 3;
$current_barangay = $is_philippines_address ? ($address_parts[0] ?? '') : '';
$current_municipality = $is_philippines_address ? ($address_parts[1] ?? '') : '';
$current_province = $is_philippines_address ? ($address_parts[2] ?? '') : '';
$current_general_address = !$is_philippines_address ? $user_data['address'] : '';

closeConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-pic {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #115D5B, #103625);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1;
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

        .form-section {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 24px;
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
            <h1 class="text-xl font-bold leading-tight">Camarines Norte Lowland <br class="hidden sm:block" /> Rainfed Research Station</h1>
        </div>

        <img src="Images/Ph.png" alt="PH Logo" class="h-20 w-auto object-contain">
        <img src="Images/Da.png" alt="DA Logo" class="h-20 w-auto object-contain">
        <div class="text-center md:text-middle mt-2 md:mt-0">
            <p class="text-sm font-bold">DEPARTMENT OF AGRICULTURE</p>
            <p class="text-xs font-bold">Address: Calasgasan, Daet, Philippines</p>
            <p class="text-xs font-bold">Email: dacnlrrs@gmail.com</p>
            <p class="text-xs font-bold">Contact No.: 0951 609 9599</p>
        </div>

        <img src="Images/Bago.png" alt="Bagong Pilipinas Logo" class="h-20 w-auto object-contain">
    </div>
</header>

<nav class="bg-white border-b py-10 px-10">
    <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-between items-center gap-4">
        <!-- Search with Advance -->
        <div class="flex flex-grow max-w-xl border border-green-900 rounded-full overflow-hidden">
            <input type="text" placeholder="Search publications, articles, keywords, etc." class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none" />
            <button class="px-0.1 text-green-900 text-xl hover:underline">🔍</button>
            <button class="bg-transparent px-6 font-semibold text-green-900 hover:underline">Search</button>
        </div>

        <!-- Navigation Links -->
        <div class="flex items-center gap-6 text-green-900 font-semibold text-sm">
            <a href="loggedin_index.php" class="hover:underline">Home</a>
            <a href="OurService.php" class="hover:underline">Our Services</a>
            <a href="About.php" class="hover:underline">About Us</a>
        </div>

        <!-- Profile Picture with User's Initial Image -->
      <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['name'], 0, 1)); ?>.png" 
           alt="Profile Picture" 
           class="profile-pic w-10 h-10 rounded-full cursor-pointer object-cover"
           onclick="toggleSidebar()" 
           title="<?php echo htmlspecialchars($_SESSION['name']); ?>"
           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

      <!-- Fallback div -->
      <div class="profile-pic w-10 h-10 rounded-full cursor-pointer flex items-center justify-center bg-gradient-to-br from-[#115D5B] to-[#103625] text-white font-bold text-lg uppercase"
           onclick="toggleSidebar()" 
           title="<?php echo htmlspecialchars($_SESSION['name']); ?>"
           style="display: none;">
        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
      </div>
    </div>
</nav>

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
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex items-center mb-8">
        <div class="profile-pic mr-6">
            <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
        </div>
        <div>
            <h1 class="text-3xl font-bold text-[#115D5B]">Edit Profile</h1>
            <p class="text-gray-600">Update your personal information and account settings</p>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_profile.php">
        <!-- Account Information -->
        <div class="form-section">
            <h2 class="text-xl font-semibold text-[#115D5B] mb-4">
                <i class="fas fa-user mr-2"></i>Account Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed" disabled>
                    <p class="text-xs text-gray-500 mt-1">Username cannot be changed</p>
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <input type="text" id="role" value="<?php echo htmlspecialchars(ucfirst($user_data['role'])); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed" disabled>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="form-section">
            <h2 class="text-xl font-semibold text-[#115D5B] mb-4">
                <i class="fas fa-id-card mr-2"></i>Personal Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="fullname" name="fullname" 
                           value="<?php echo htmlspecialchars($user_data['name']); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent" required>
                </div>

                <div>
                    <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Birth Date *</label>
                    <input type="date" id="birth_date" name="birth_date" 
                           value="<?php echo htmlspecialchars($user_data['birth_date']); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <input type="tel" id="contact_number" name="contact_number" 
                           value="<?php echo htmlspecialchars($user_data['contact']); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user_data['email']); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent" required>
                </div>
            </div>
        </div>

        <!-- Address Information -->
        <div class="form-section">
            <h2 class="text-xl font-semibold text-[#115D5B] mb-4">
                <i class="fas fa-map-marker-alt mr-2"></i>Address Information
            </h2>

            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" id="is_outside_philippines" name="is_outside_philippines" value="true" 
                           class="mr-2" <?php echo !$is_philippines_address ? 'checked' : ''; ?>>
                    <span class="text-sm text-gray-700">I am outside the Philippines</span>
                </label>
            </div>

            <!-- Philippines Address Fields -->
            <div id="philippines_address" class="<?php echo !$is_philippines_address ? 'hidden' : ''; ?>">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="barangay" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                        <input type="text" id="barangay" name="barangay" 
                               value="<?php echo htmlspecialchars($current_barangay); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                    </div>

                    <div>
                        <label for="municipality" class="block text-sm font-medium text-gray-700 mb-1">Municipality</label>
                        <input type="text" id="municipality" name="municipality" 
                               value="<?php echo htmlspecialchars($current_municipality); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                    </div>

                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                        <input type="text" id="province" name="province" 
                               value="<?php echo htmlspecialchars($current_province); ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- General Address Field -->
            <div id="general_address" class="<?php echo $is_philippines_address ? 'hidden' : ''; ?>">
                <label for="general_address_input" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea id="general_address_input" name="general_address" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent"><?php echo htmlspecialchars($current_general_address); ?></textarea>
            </div>
        </div>

        <!-- Password Change -->
        <div class="form-section">
            <h2 class="text-xl font-semibold text-[#115D5B] mb-4">
                <i class="fas fa-lock mr-2"></i>Change Password (Optional)
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Account Details -->
        <div class="form-section">
            <h2 class="text-xl font-semibold text-[#115D5B] mb-4">
                <i class="fas fa-info-circle mr-2"></i>Account Details
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                <div>
                    <p><strong>Account Created:</strong> <?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></p>
                </div>
                <div>
                    <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i A', strtotime($user_data['updated_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-end">
            <a href="loggedin_index.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-center">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-[#115D5B] text-white rounded-md hover:bg-[#103625] transition">
                <i class="fas fa-save mr-2"></i>Update Profile
            </button>
        </div>
    </form>
</div>

<!-- Footer -->
<footer class="bg-[#115D5B] text-white text-center py-4 mt-12">
    <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
</footer>

<script>
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

    // Address toggle functionality
    const outsidePhilippinesCheckbox = document.getElementById('is_outside_philippines');
    const philippinesAddress = document.getElementById('philippines_address');
    const generalAddress = document.getElementById('general_address');

    outsidePhilippinesCheckbox.addEventListener('change', function() {
        if (this.checked) {
            philippinesAddress.classList.add('hidden');
            generalAddress.classList.remove('hidden');
        } else {
            philippinesAddress.classList.remove('hidden');
            generalAddress.classList.add('hidden');
        }
    });

    // Password validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    function validatePasswords() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    newPassword.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);

    // Hide header on scroll down, show on scroll up
    let lastScrollTop = 0;
    const header = document.getElementById('main-header');

    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        if (scrollTop > lastScrollTop) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });
</script>
</body>
</html>