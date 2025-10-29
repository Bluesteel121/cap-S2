<?php
session_start();
// Check if user is logged in
$is_logged_in = isset($_SESSION['name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Guide - CNLRRS Research Library</title>
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
        <a href="About.php" class="hover:underline">About Us</a>
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
  <div id="mySidebar" class="sidebar">
      <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
      <a href="#">Settings</a>
      <a href="edit_profile.php">Profile</a>
      <a href="my_submissions.php"><i class="fas fa-file-alt mr-2"></i>My Submissions</a>
      <a href="submit_paper.php"><i class="fas fa-plus mr-2"></i>Submit Paper</a>
      <a href="index.php" onclick="logout()">Log Out</a>
  </div>
  <?php endif; ?>
  
  <div class="w-full flex justify-center my-1">
    <img src="Images/UserGuideCn.png" alt="Section Divider" class="w-full h-26 object-cover" 
         onerror="this.src='Images/AboutCn.png'" />
  </div>
</main>

<!-- Full-width line -->
<div class="my-8 w-full border-t-4 border-[#103635]"></div>

<!-- User Guide Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    User Guide
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
      Welcome to the CNLRRS Research Library User Guide. This guide will help you navigate the platform, 
      search for research materials, and make the most of our digital repository. Whether you're a researcher, 
      farmer, student, or policymaker, this guide provides step-by-step instructions for accessing the 
      agricultural knowledge you need.
    </p>
  </div>
</section>

<!-- Getting Started Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Getting Started
  </h2>
  <div class="space-y-6">
    
    <!-- Step 1 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0 bg-[#103635] text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
          1
        </div>
        <div>
          <h3 class="text-lg font-bold text-[#103635] mb-2">Create an Account (Optional)</h3>
          <p class="text-sm md:text-base text-gray-700">
            While you can browse our repository without an account, registering gives you access to 
            additional features like saving favorite papers, submitting your own research, and receiving 
            updates on new publications in your areas of interest.
          </p>
        </div>
      </div>
    </div>

    <!-- Step 2 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0 bg-[#103635] text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
          2
        </div>
        <div>
          <h3 class="text-lg font-bold text-[#103635] mb-2">Browse the Library</h3>
          <p class="text-sm md:text-base text-gray-700">
            Navigate to the Research Library to explore our collection. Papers are organized by category, 
            publication date, and research type. You can view featured research on the homepage or browse 
            all available publications.
          </p>
        </div>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0 bg-[#103635] text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
          3
        </div>
        <div>
          <h3 class="text-lg font-bold text-[#103635] mb-2">Use the Search Function</h3>
          <p class="text-sm md:text-base text-gray-700 mb-3">
            Use the search bar at the top of any page to find specific papers. You can search by:
          </p>
          <ul class="list-disc list-inside text-sm md:text-base text-gray-700 space-y-1 ml-4">
            <li>Paper title or keywords</li>
            <li>Author name</li>
            <li>Research category (e.g., Soil Science, Crop Science)</li>
            <li>Publication year</li>
            <li>Abstract content</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Step 4 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0 bg-[#103635] text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
          4
        </div>
        <div>
          <h3 class="text-lg font-bold text-[#103635] mb-2">View and Download Papers</h3>
          <p class="text-sm md:text-base text-gray-700">
            Click on any paper title to view its abstract and details. Each paper page includes download 
            options (PDF format), citation information, and related research suggestions. All downloads 
            are free and unlimited.
          </p>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Advanced Features Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Advanced Features
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <div class="bg-gray-100 p-6 rounded-lg">
      <div class="flex items-center mb-3">
        <i class="fas fa-filter text-2xl text-[#103635] mr-3"></i>
        <h3 class="font-bold text-[#103635]">Advanced Filtering</h3>
      </div>
      <p class="text-sm text-gray-700">
        Use multiple filters simultaneously to narrow down your search results by category, year, 
        and research type for precise results.
      </p>
    </div>

    <div class="bg-gray-100 p-6 rounded-lg">
      <div class="flex items-center mb-3">
        <i class="fas fa-quote-right text-2xl text-[#103635] mr-3"></i>
        <h3 class="font-bold text-[#103635]">Citation Tools</h3>
      </div>
      <p class="text-sm text-gray-700">
        Each paper includes formatted citations in multiple styles (APA, MLA, Chicago) that you 
        can copy directly for your research.
      </p>
    </div>

    <div class="bg-gray-100 p-6 rounded-lg">
      <div class="flex items-center mb-3">
        <i class="fas fa-bell text-2xl text-[#103635] mr-3"></i>
        <h3 class="font-bold text-[#103635]">Alerts & Notifications</h3>
      </div>
      <p class="text-sm text-gray-700">
        Set up email alerts to receive notifications when new papers are published in your 
        chosen categories or by specific authors.
      </p>
    </div>

    <div class="bg-gray-100 p-6 rounded-lg">
      <div class="flex items-center mb-3">
        <i class="fas fa-upload text-2xl text-[#103635] mr-3"></i>
        <h3 class="font-bold text-[#103635]">Submit Your Research</h3>
      </div>
      <p class="text-sm text-gray-700">
        Registered users can submit their own research papers for peer review and publication 
        through the "Submit Paper" option in the user menu.
      </p>
    </div>

  </div>
</section>

<!-- FAQ Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Frequently Asked Questions
  </h2>
  <div class="space-y-4">
    
    <div class="bg-white border border-gray-300 rounded-lg p-6">
      <h3 class="font-bold text-[#103635] mb-2">Do I need to create an account to access papers?</h3>
      <p class="text-sm md:text-base text-gray-700">
        No, all research papers are freely accessible without registration. However, creating an 
        account provides additional features like saving favorites and submitting research.
      </p>
    </div>

    <div class="bg-white border border-gray-300 rounded-lg p-6">
      <h3 class="font-bold text-[#103635] mb-2">Are there any fees for downloading papers?</h3>
      <p class="text-sm md:text-base text-gray-700">
        No, all content in the CNLRRS Research Library is completely free to access and download. 
        We believe in open access to agricultural knowledge.
      </p>
    </div>

    <div class="bg-white border border-gray-300 rounded-lg p-6">
      <h3 class="font-bold text-[#103635] mb-2">How do I cite papers from this library?</h3>
      <p class="text-sm md:text-base text-gray-700">
        Each paper page includes pre-formatted citations in common styles. Click the "Cite" button 
        on any paper to access citation formats.
      </p>
    </div>

    <div class="bg-white border border-gray-300 rounded-lg p-6">
      <h3 class="font-bold text-[#103635] mb-2">Can I submit my own research?</h3>
      <p class="text-sm md:text-base text-gray-700">
        Yes! Registered users can submit research papers through the submission portal. Visit the 
        "For Authors" page for detailed submission guidelines.
      </p>
    </div>

  </div>
</section>

<!-- Contact Support Section -->
<section class="max-w-7xl mx-auto px-6 py-10 bg-gray-50 rounded-lg">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635] text-center">
    Need Additional Help?
  </h2>
  <div class="max-w-2xl mx-auto bg-white p-6 md:p-8 rounded-lg border-2 border-[#103635]">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-6">
      If you have questions not covered in this guide or need technical assistance, 
      our support team is here to help.
    </p>
    <div class="space-y-3">
      <p class="text-sm md:text-base font-semibold text-[#103635]">
        <i class="fas fa-envelope mr-2"></i>
        Email: <a href="mailto:dacnlrrs@gmail.com" class="underline">dacnlrrs@gmail.com</a>
      </p>
      <p class="text-sm md:text-base font-semibold text-[#103635]">
        <i class="fas fa-phone mr-2"></i>
        Phone: 0951 609 9599
      </p>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-[#115D5B] text-white text-center py-4 mt-16">
  <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
</footer>

<!-- Scripts -->
<script>
  // Sidebar toggle functionality (only needed when logged in)
  function toggleSidebar() {
      const sidebar = document.getElementById('mySidebar');
      if (sidebar) {
          if (sidebar.style.width === '250px') {
              sidebar.style.width = '0';
          } else {
              sidebar.style.width = '250px';
          }
      }
  }

  function closeSidebar() {
      const sidebar = document.getElementById('mySidebar');
      if (sidebar) {
          sidebar.style.width = '0';
      }
  }

  // Logout function
  function logout() {
      fetch('logout.php', {
          method: 'POST'
      }).then(() => {
          window.location.href = 'index.php';
      });
  }

  // Close sidebar when clicking outside
  document.addEventListener('click', function(event) {
      const sidebar = document.getElementById('mySidebar');
      if (!sidebar) return;
      
      const profilePic = event.target.closest('.profile-pic');
      
      if (!sidebar.contains(event.target) && !profilePic && sidebar.style.width === '250px') {
          closeSidebar();
      }
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

  /* Header transition */
  #main-header {
    transition: transform 0.3s ease-in-out;
  }
</style>

</body>
</html>