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
  <title>Our Services - CNLRRS Research Library</title>
  <link rel="icon" href="Images/Favicon.ico">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    @layer utilities {
      .text-outline-white {
        -webkit-text-stroke: 1px white;
      }
    }
    
    /* Search dropdown styles */
    #search-results {
      max-height: 500px;
      overflow-y: auto;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    #search-results::-webkit-scrollbar {
      width: 8px;
    }
    
    #search-results::-webkit-scrollbar-track {
      background: #f1f1f1;
    }
    
    #search-results::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 4px;
    }
    
    #search-results::-webkit-scrollbar-thumb:hover {
      background: #555;
    }
    
    .search-container {
      position: relative;
    }
  </style>
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
    <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4 flex-shrink-0">
      <!-- Text Info -->
      <div class="text-[9px] sm:text-[10px] md:text-xs lg:text-sm font-semibold text-left leading-tight">
        <p class="hidden md:block">DEPARTMENT OF AGRICULTURE</p>
        <p class="md:hidden">DA</p>
        
        <p class="hidden md:block">Calasgasan, Daet, Philippines</p>
        <p class="md:hidden">Daet, PH</p>
        
        <p class="hidden md:block">
          Email: 
          <a href="https://mail.google.com/mail/?view=cm&fs=1&to=dacnlrrs@gmail.com" 
             target="_blank" 
             class="underline hover:text-[#115D5B]">
            dacnlrrs@gmail.com
          </a>
        </p>
        <p class="md:hidden">
          <a href="mailto:dacnlrrs@gmail.com" class="underline">Email</a>
        </p>
        
        <p>0951 609 9599</p>
      </div>
      <!-- Logo -->
      <img src="Images/Bago.png" alt="Bagong Pilipinas Logo" class="h-10 sm:h-14 md:h-16 lg:h-20 xl:h-24 object-contain" />
    </div>
  </div>
</header>

<!-- Main Content -->
<main class="pt-17">
  <!-- Navigation -->
  <nav class="border-t mt-2">
    <div class="max-w-7xl mx-auto px-6 py-4 flex flex-wrap justify-between items-center gap-4">
      <!-- Search Bar with Dropdown -->
      <div class="flex flex-grow max-w-xl search-container">
        <div class="relative flex-grow border border-[#103635] rounded-full overflow-hidden">
          <input 
            type="text" 
            id="main-search-input"
            placeholder="Search publications, articles, keywords, etc." 
            class="w-full px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none"
            autocomplete="off"
          />
          <button 
            id="search-button"
            class="absolute right-20 top-1/2 transform -translate-y-1/2 px-4 hover:opacity-75">
            <img src="Images/Search magni.png" alt="Search" class="h-5" />
          </button>
          <button 
            id="advance-search-button"
            class="absolute right-2 top-1/2 transform -translate-y-1/2 px-4 text-sm font-semibold text-[#103635] hover:underline">
            Advance
          </button>
        </div>
        
        <!-- Search Results Dropdown -->
        <div 
          id="search-results" 
          class="hidden absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-lg mt-2 z-50 shadow-lg">
          <!-- Results will be inserted here -->
        </div>
      </div>

      <!-- Links -->
      <div class="flex items-center gap-6 font-bold">
        <a href="<?php echo $is_logged_in ? 'loggedin_index.php' : 'index.php'; ?>" class="hover:underline">Home</a>
        <a href="OurService.php" class="hover:underline text-[#115D5B] underline">Our Services</a>
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
        <a href="About.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors group">
          <i class="fas fa-info-circle mr-3 text-lg"></i>
          <span class="text-sm font-medium">About Us</span>
        </a>
        <a href="OurService.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 hover:bg-opacity-20 transition-colors group">
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
    <img src="Images/ServicesCn.png" alt="Section Divider" class="w-full h-26 object-cover" />
  </div>
</main>

<!-- Full-width line -->
<div class="my-8 w-full border-t-4 border-[#103635]"></div>

<!-- Our Services Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Our Services
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
      The CNLRRS Research Library provides comprehensive services to support agricultural research, 
      knowledge sharing, and sustainable farming practices. Our platform is designed to serve farmers, 
      researchers, policymakers, and the broader agricultural community by offering easy access to 
      valuable research resources and expert knowledge.
    </p>
  </div>
</section>

<!-- Services Grid -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <!-- Service 1 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-book text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Research Publications</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Access a comprehensive collection of peer-reviewed research papers and technical reports 
        on lowland rainfed farming systems, sustainable agriculture, and rural development.
      </p>
    </div>

    <!-- Service 2 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-graduation-cap text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Extension Materials</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Discover practical guides, training materials, and best practices designed to help farmers 
        implement modern agricultural techniques and improve productivity.
      </p>
    </div>

    <!-- Service 3 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-search text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Advanced Search</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Utilize our powerful search and filtering tools to find specific research papers, articles, 
        and resources by keywords, authors, topics, and publication dates.
      </p>
    </div>

    <!-- Service 4 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-download text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Free Downloads</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Download all publications and materials in various formats at no cost. Our open-access 
        repository ensures knowledge is freely available to everyone.
      </p>
    </div>

    <!-- Service 5 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-network-wired text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Research Collaboration</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Connect with fellow researchers, scientists, and policymakers through our platform to foster 
        collaboration and innovation in agricultural research.
      </p>
    </div>

    <!-- Service 6 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-envelope text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Support & Inquiries</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Get in touch with our team for assistance, inquiries, or to submit your research for 
        publication in our archive.
      </p>
    </div>

  </div>
</section>

<!-- Content Types Section -->
<section class="max-w-7xl mx-auto px-6 py-10 mt-8">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Content We Provide
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <h3 class="font-bold text-[#103635] mb-2">Peer-Reviewed Research Papers</h3>
        <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
          Scholarly articles covering agricultural sciences, environmental studies, and sustainable 
          farming practices.
        </p>
      </div>
      <div>
        <h3 class="font-bold text-[#103635] mb-2">Technical Reports</h3>
        <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
          Comprehensive reports on research findings, field trials, and agricultural projects.
        </p>
      </div>
      <div>
        <h3 class="font-bold text-[#103635] mb-2">Extension Materials</h3>
        <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
          Practical guides, brochures, and training materials for farmers and agricultural practitioners.
        </p>
      </div>
      <div>
        <h3 class="font-bold text-[#103635] mb-2">Policy Documents</h3>
        <p class="text-sm md:text-base leading-relaxed text-gray-800">
          Evidence-based policy recommendations and strategic documents for agricultural development.
        </p>
      </div>
    </div>
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

// ==================== SEARCH FUNCTIONALITY ====================
  
  const searchInput = document.getElementById('main-search-input');
  const searchButton = document.getElementById('search-button');
  const advanceButton = document.getElementById('advance-search-button');
  const searchResults = document.getElementById('search-results');
  
  let searchTimeout;
  let isSearching = false;

  // Check if user is logged in (from PHP session)
  const isLoggedIn = <?php echo isset($_SESSION['name']) ? '1' : '0'; ?>;

  // Debounced search function
  function performSearch(query) {
    // Clear previous timeout
    clearTimeout(searchTimeout);
    
    // Don't search if query is too short
    if (query.trim().length < 2) {
      searchResults.classList.add('hidden');
      return;
    }

    // Set timeout for debouncing (wait 300ms after user stops typing)
    searchTimeout = setTimeout(async () => {
      if (isSearching) return; // Prevent multiple simultaneous searches
      
      isSearching = true;
      searchResults.classList.remove('hidden');
      searchResults.innerHTML = '<div class="p-4 text-center text-gray-600"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>';

      try {
        const response = await fetch('search_handler.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `query=${encodeURIComponent(query)}&is_logged_in=${isLoggedIn}`
        });

        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const html = await response.text();
        searchResults.innerHTML = html;

      } catch (error) {
        console.error('Search error:', error);
        searchResults.innerHTML = '<div class="p-4 text-center text-red-600"><i class="fas fa-exclamation-circle mr-2"></i>Search failed. Please try again.</div>';
      } finally {
        isSearching = false;
      }
    }, 300); // 300ms delay
  }

  // Event listener for input
  searchInput.addEventListener('input', (e) => {
    const query = e.target.value;
    performSearch(query);
  });

  // Event listener for search button
  searchButton.addEventListener('click', () => {
    const query = searchInput.value;
    if (query.trim().length >= 2) {
      performSearch(query);
    } else {
      searchResults.classList.remove('hidden');
      searchResults.innerHTML = '<div class="p-4 text-center text-gray-600">Please enter at least 2 characters</div>';
    }
  });

  // Event listener for Enter key
  searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const query = searchInput.value;
      if (query.trim().length >= 2) {
        clearTimeout(searchTimeout); // Clear debounce timeout
        performSearch(query);
      }
    }
  });

  // Close search results when clicking outside
  document.addEventListener('click', (e) => {
    const searchContainer = document.querySelector('.search-container');
    if (searchContainer && !searchContainer.contains(e.target)) {
      searchResults.classList.add('hidden');
    }
  });

  // Prevent closing when clicking inside search results
  searchResults.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  // Advanced search button functionality
  advanceButton.addEventListener('click', (e) => {
    e.preventDefault();
    // You can implement advanced search functionality here
    // For now, redirect to elibrary with search parameter
    const query = searchInput.value;
    if (query.trim().length > 0) {
      window.location.href = `elibrary.php?search=${encodeURIComponent(query)}`;
    } else {
      window.location.href = 'elibrary.php';
    }
  });

  // Clear search when input is emptied
  searchInput.addEventListener('input', (e) => {
    if (e.target.value.trim().length === 0) {
      searchResults.classList.add('hidden');
    }
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