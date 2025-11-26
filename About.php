<?php
session_start();

// Include database connection for logged-in users
if (isset($_SESSION['name'])) {
    require_once 'connect.php';
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us - CNLRRS Research Library</title>
  <link rel="icon" href="Images/Favicon.ico">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-white pt-24 text-[#103625] font-sans">

<!-- Header -->
<header id="main-header" class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg h-16 sm:h-20 md:h-24 lg:h-28">
  <div class="max-w-7xl mx-auto px-1 sm:px-3 md:px-6 flex flex-wrap justify-between items-center gap-1 sm:gap-2 md:gap-4 h-full">
    
    <!-- Logo and Title -->
    <div class="flex items-center space-x-1 sm:space-x-2 md:space-x-4 flex-shrink-0">
      <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-8 sm:h-12 md:h-16 lg:h-20 xl:h-24 object-contain" />
      <h1 class="text-[10px] sm:text-xs md:text-sm lg:text-lg font-bold leading-tight">
        <span class="block sm:hidden">CNLRRS</span>
        <span class="hidden sm:block">
          Camarines Norte Lowland <br class="hidden sm:block" />
          Rainfed Research Station
        </span>
      </h1>
    </div>

    <!-- Partner Logos -->
    <div class="flex items-center space-x-1 sm:space-x-2 md:space-x-4 flex-shrink-0">
      <img src="Images/Ph.png" alt="Philippines Logo" class="h-8 sm:h-12 md:h-16 lg:h-20 xl:h-24 object-contain"/>
      <img src="Images/Da.png" alt="CNLRRS Logo" class="h-8 sm:h-12 md:h-16 lg:h-20 xl:h-24 object-contain" />
    </div>

    <!-- Contact Info Section -->
    <div class="flex items-center space-x-1 sm:space-x-2 md:space-x-4 flex-shrink-0 min-w-0">
      <!-- Text Info -->
      <div class="text-[8px] sm:text-xs md:text-sm font-semibold text-center leading-tight">
        <p class="md:hidden">DA</p>
        <p class="hidden md:block">DEPARTMENT OF AGRICULTURE</p>
        
        <p class="sm:hidden">Daet, PH</p>
        <p class="hidden sm:block">Calasgasan, Daet, Philippines</p>
        
        <p class="md:hidden">
          <a href="mailto:dacnlrrs@gmail.com" class="underline">Email</a>
        </p>
        <p class="hidden md:block">
          Email: 
          <a href="https://mail.google.com/mail/?view=cm&fs=1&to=dacnlrrs@gmail.com" 
             target="_blank" 
             class="underline">
            dacnlrrs@gmail.com
          </a>
        </p>

        <p>0951 609 9599</p>
      </div>
      <!-- Logo -->
      <img src="Images/Bago.png" alt="Bagong Pilipinas Logo" class="h-8 sm:h-12 md:h-16 lg:h-20 xl:h-24 object-contain" />
    </div>
  </div>
</header>

<!-- Main Content -->
<main class="pt-17">
  <!-- Navigation -->
  <nav class="border-t mt-2">
    <div class="max-w-7xl mx-auto px-6 py-4 flex flex-wrap justify-between items-center gap-4">
      <!-- Search Bar -->
      <div class="flex flex-grow max-w-xl border border-[#103635] rounded-full overflow-hidden">
        <input type="text" placeholder="Search publications, articles, keywords, etc." class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none" />
        <button class="px-4">
          <img src="Images/Search magni.png" alt="Search" class="h-5" />
        </button>
        <button class="px-4 text-sm font-semibold text-[#103635] hover:underline">Advance</button>
      </div>

      <!-- Links -->
      <div class="flex items-center gap-6 font-bold">
        <a href="<?php echo $is_logged_in ? 'loggedin_index.php' : 'index.php'; ?>" class="hover:underline">Home</a>
        <a href="OurService.php" class="hover:underline">Our Services</a>
        <a href="About.php" class="hover:underline text-[#115D5B] underline">About Us</a>
      </div>

      <!-- Login / Profile Picture -->
      <?php if ($is_logged_in): ?>
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
      <?php else: ?>
        <!-- Login Button -->
        <a href="account.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
      <?php endif; ?>
    </div>
  </nav>
  
  <!-- Sidebar Navigation (only show when logged in) -->
  <?php if ($is_logged_in): ?>
  <div id="mySidebar" class="sidebar fixed left-0 top-0 h-screen w-64 bg-gradient-to-b from-[#115D5B] to-[#0e4e4c] text-white shadow-2xl transform transition-transform duration-300 z-[1000] overflow-y-auto">
    
    <!-- Close Button (Mobile Only) -->
    <a href="javascript:void(0)" class="closebtn lg:hidden block absolute top-4 right-4 text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-lg transition-colors text-2xl" onclick="closeSidebar()">&times;</a>

    <!-- User Profile Section -->
    <div class="px-6 py-6 border-b border-white border-opacity-20 mt-12 lg:mt-0">
      <div class="flex items-center space-x-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
          <i class="fas fa-user text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-sm font-bold truncate"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?></h3>
          <p class="text-xs opacity-75 truncate"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'username'; ?></p>
        </div>
      </div>
    </div>

    <!-- Navigation Links -->
    <nav class="px-4 py-6 space-y-2 pb-40">
      <div class="mb-6">
        <p class="text-xs font-semibold text-white opacity-60 px-2 mb-3 uppercase tracking-wider">Main Menu</p>
        
        <a href="loggedin_index.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors group">
          <i class="fas fa-home mr-3 text-lg"></i>
          <span class="text-sm font-medium">Home</span>
        </a>

        <a href="edit_profile.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors group">
          <i class="fas fa-user-circle mr-3 text-lg"></i>
          <span class="text-sm font-medium">Profile</span>
        </a>

        <a href="my_submissions.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors group">
          <i class="fas fa-file-alt mr-3 text-lg"></i>
          <span class="text-sm font-medium">My Submissions</span>
          <span class="ml-auto bg-white bg-opacity-20 text-xs px-2 py-1 rounded-full">
            <?php 
              $user_name = $_SESSION['name'];
              $submission_query = $conn->prepare("SELECT COUNT(*) as count FROM paper_submissions WHERE author_name = ?");
              $submission_query->bind_param("s", $user_name);
              $submission_query->execute();
              $submission_result = $submission_query->get_result()->fetch_assoc();
              echo $submission_result['count'];
            ?>
          </span>
        </a>

        <a href="submit_paper.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 hover:bg-opacity-20 transition-colors group border border-white border-opacity-20">
          <i class="fas fa-upload mr-3 text-lg"></i>
          <span class="text-sm font-medium">Submit Paper</span>
        </a>
      </div>

      <div class="mb-6">
        <p class="text-xs font-semibold text-white opacity-60 px-2 mb-3 uppercase tracking-wider">Account</p>
        
        <a href="edit_profile.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors group">
          <i class="fas fa-user-circle mr-3 text-lg"></i>
          <span class="text-sm font-medium">Edit Profile</span>
        </a>

        <a href="saved_papers.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors group">
          <i class="fas fa-bookmark mr-3 text-lg"></i>
          <span class="text-sm font-medium">Saved Papers</span>
        </a>
      </div>

      <!-- Divider -->
      <div class="border-t border-white border-opacity-20 my-4"></div>

      <div class="mb-20">
        <p class="text-xs font-semibold text-white opacity-60 px-2 mb-3 uppercase tracking-wider">Other</p> 
        <a href="About.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 hover:bg-opacity-20 transition-colors group">
          <i class="fas fa-info-circle mr-3 text-lg"></i>
          <span class="text-sm font-medium">About Us</span>
        </a>
        <a href="OurService.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors group">
          <i class="fas fa-handshake mr-3 text-lg"></i>
          <span class="text-sm font-medium">Services</span>
        </a>
      </div>
    </nav>
    
    <!-- Logout Section -->
    <div class="fixed bottom-0 left-0 right-0 w-64 px-4 py-6 bg-gradient-to-t from-[#0e4e4c] via-[#0e4e4c] to-transparent border-t border-white border-opacity-20 lg:relative">
      <a href="logout.php" class="flex items-center justify-center px-4 py-3 rounded-lg bg-red-600 hover:bg-red-700 transition-colors w-full group font-medium">
        <i class="fas fa-sign-out-alt mr-2"></i>
        <span class="text-sm">Log Out</span>
      </a>
    </div>
  </div>

  <!-- Sidebar Overlay (Mobile) -->
  <div id="sidebarOverlay" onclick="closeSidebar()" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 hidden z-[999] transition-opacity duration-300"></div>
  <?php endif; ?>
  
  <div class="w-full flex justify-center my-1">
    <img src="Images/AboutCn.png" alt="Section Divider" class="w-full h-26 object-cover" />
  </div>
</main>

<!-- Full-width line -->
<div class="my-8 w-full border-t-4 border-[#103635]"></div>

<!-- About CNLRRS Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    About CNLRRS
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
      CNLRRS Research Archive is an open-access digital repository dedicated to publishing 
      and preserving agricultural research literature from the Camarines Norte Lowland 
      Rainfed Research Station. As part of the institution's mission to advance sustainable 
      farming and rural development, the CNLRRS Research Archive provides free access to 
      peer-reviewed studies, technical reports, and extension materials that support modern 
      agricultural practices and future research.
    </p>
    <p class="text-sm md:text-base leading-relaxed text-gray-800">
      Launched to promote knowledge sharing and innovation in lowland rainfed farming 
      systems, this platform ensures that vital research findings are accessible to farmers, 
      scientists, policymakers, and the public. Developed and maintained by the CNLRRS, 
      the archive serves as a key resource for evidence-based agriculture and environmental 
      stewardship in the region.
    </p>
  </div>
</section>

<!-- About Content Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    About Content
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
      Since its establishment, the CNLRRS Research Archive has evolved from initially publishing
       a limited number of studies to becoming a comprehensive digital repository featuring research papers,
        technical reports, and extension materials from various agricultural disciplines. What began with 
        foundational studies on lowland rainfed farming systems has now expanded to include a growing collection
         of peer-reviewed articles, contributing to sustainable agriculture, climate resilience, and
          rural development in Camarines Norte and beyond.
    </p>
    <p class="text-sm md:text-base leading-relaxed text-gray-800">
      This platform continues to grow, fostering collaboration among researchers, farmers,
       and policymakers while ensuring that critical agricultural knowledge is freely accessible to all.
    </p>
  </div>
</section>

<!-- Footer -->
<footer class="bg-[#115D5B] text-white text-center py-4 mt-16">
  <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
</footer>

<!-- Scripts -->
<script>
function toggleSidebar() {
  const sidebar = document.getElementById('mySidebar');
  const overlay = document.getElementById('sidebarOverlay');
  
  if (sidebar && overlay) {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('hidden');
  }
}

function closeSidebar() {
  const sidebar = document.getElementById('mySidebar');
  const overlay = document.getElementById('sidebarOverlay');
  
  if (sidebar && overlay) {
    sidebar.classList.remove('active');
    overlay.classList.add('hidden');
  }
}

function logout() {
  fetch('logout.php', {
    method: 'POST'
  }).then(() => {
    window.location.href = 'index.php';
  });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const sidebarLinks = document.querySelectorAll('#mySidebar a');
  
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
  }
  
  sidebarLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (!this.classList.contains('closebtn') && this.href.indexOf('logout.php') === -1) {
        closeSidebar();
      }
    });
  });
  
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeSidebar();
    }
  });
});

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

<style>
/* Profile picture styles */
.profile-pic {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  cursor: pointer;
  border: 2px solid #115D5B;
  transition: transform 0.2s;
}

.profile-pic:hover {
  transform: scale(1.1);
}

/* Sidebar styles */
.sidebar {
  left: -256px;
  transition: left 0.3s ease-in-out;
}

.sidebar.active {
  left: 0;
}

@media (min-width: 1024px) {
  .sidebar {
    left: -256px !important;
  }
  
  .sidebar.active {
    left: 0 !important;
  }
}

#mySidebar {
  padding-bottom: 120px;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

#mySidebar::-webkit-scrollbar {
  width: 6px;
}

#mySidebar::-webkit-scrollbar-track {
  background: transparent;
}

#mySidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

#mySidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}

.closebtn {
  display: block;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.closebtn:hover {
  color: #f1f1f1;
}

@media (max-width: 1023px) {
  #mySidebar {
    padding-bottom: 140px;
  }
}

/* Header transition */
#main-header {
  transition: transform 0.3s ease-in-out;
}
</style>

</body>
</html>