<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CNLRRS Research Library</title>
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
<header id="main-header" class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg h-auto min-h-[4rem] sm:min-h-[5rem] md:h-24 lg:h-28">
  <div class="max-w-7xl mx-auto px-2 sm:px-4 md:px-6 py-2 md:py-0 flex flex-wrap justify-between items-center gap-2 sm:gap-3 md:gap-4 h-full">
    
    <!-- Logo and Title -->
    <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4 flex-shrink-0">
      <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-10 sm:h-14 md:h-16 lg:h-20 xl:h-24 object-contain" />
      <h1 class="text-[11px] sm:text-xs md:text-sm lg:text-base xl:text-lg font-bold leading-tight">
        <span class="block lg:hidden">CNLRRS</span>
        <span class="hidden lg:block">
          Camarines Norte Lowland <br />
          Rainfed Research Station
        </span>
      </h1>
    </div>

    <!-- Partner Logos -->
    <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4 flex-shrink-0">
      <img src="Images/Ph.png" alt="Philippines Logo" class="h-10 sm:h-14 md:h-16 lg:h-20 xl:h-24 object-contain"/>
      <img src="Images/Da.png" alt="DA Logo" class="h-10 sm:h-14 md:h-16 lg:h-20 xl:h-24 object-contain" />
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
        <a href="index.php" class="hover:underline">Home</a>
        <a href="OurService.php" class="hover:underline">Our Services</a>
        <a href="About.php" class="hover:underline">About Us</a>
      </div>

      <!-- Login -->
      <a href="account.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
    </div>
  </nav>
  
  <!-- Banner Section -->
  <section class="relative">
    <img src="Images/Library2.png" alt="Library" class="w-full h-[450px] object-cover" />

    <div class="absolute inset-0 flex flex-col justify-center px-8 md:px-16 text-left bg-black bg-opacity-40">
      <h2 class="text-4xl md:text-5xl font-bold text-[#103635] text-outline-white leading-tight">
        Welcome to CNLRRS Research Library
      </h2>
      <p class="font-bold mt-4 text-lg md:text-xl max-w-2xl text-[#103635]" 
         style="text-shadow:
            -1px -1px 0 white,
             1px -1px 0 white,
            -1px  1px 0 white,
             1px  1px 0 white;">
        Access the latest research and publication about Queen Pineapple Varieties, 
        cultivation, health benefits, and more.
      </p>

      <div class="mt-6 flex gap-4">
        <a href="elibrary.php" class="bg-[#1A4D3A] text-white px-6 py-3 rounded-md font-semibold border border-white hover:bg-[#16663F] transition rounded-lg">
          Browse Research
        </a>
        <a href="userlogin.php" class="bg-[#1A4D3A] border border-white text-white px-6 py-3 rounded-md font-semibold hover:bg-[#16663F] transition rounded-lg">
          Submit Paper
        </a>
      </div>
    </div>
  </section>

  <!-- Info Cards -->
  <section class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 px-4">
      
      <a href="About.php" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/About CN.png" alt="About CNLRRS" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">About CNLRRS</h3>
        <p class="text-sm text-gray-600">Explore agricultural studies showcasing decades of scientific research on Pineapple farming.</p>
      </a>

      <a href="UserGuide.php" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/UserG.png" alt="User Guide" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">User Guide</h3>
        <p class="text-sm text-gray-600">Learn how to find and read articles of your interest.</p>
      </a>

      <a href="elibrary.php" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/Collections.png" alt="Collections" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">Collections</h3>
        <p class="text-sm text-gray-600">Browse the CNLRRS library and learn about its collection.</p>
      </a>

      <a href="ForAuthor.php" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/Collections.png" alt="For Authors" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">For Authors</h3>
        <p class="text-sm text-gray-600">Navigate the CNLRRS submission methods easily.</p>
      </a>

      <a href="ForPublisher.php" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/Collections.png" alt="For Publisher" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">For Publisher</h3>
        <p class="text-sm text-gray-600">Learn about options for journals and publishers and the CNLRRS selection process.</p>
      </a>

    </div>
  </section>
</main>

<div class="w-full flex justify-center my-12">
  <img src="Images/Green_holder.png" alt="Section Divider" class="w-full h-30 object-cover" />
</div>

<section class="bg-gray-50 py-16 border-t">
  <div class="max-w-7xl mx-auto px-6">
    <div class="relative mb-12 text-center">
      <h2 class="inline-block bg-[#103635] text-3xl md:text-4xl font-extrabold text-white px-8 py-3 rounded-full shadow-lg">
        Recommended Research By CNLRRS
      </h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- Card 1 -->
      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">
        <img src="Images/Books.png" alt="Books & Collection Services" class="h-32 w-full object-cover rounded mb-4" />
        <h3 class="font-semibold text-lg mb-2">Books & Collection Services</h3>
        <p class="text-sm text-gray-700 mb-4">Explore our library collection. Maximize both time and selection with book tools when selecting print books.</p>
        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>
      </div>

      <!-- Card 2 -->
      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">
        <img src="Images/Soil.jpg" alt="Pineapple Soil & Growth Solutions" class="h-32 w-full object-cover rounded mb-4" />
        <h3 class="font-semibold text-lg mb-2">Pineapple Soil & Growth Solutions</h3>
        <p class="text-sm text-gray-700 mb-4">Improve your farming knowledge with Pineapple Soil Amendment guides. Learn the needs of pineapple farming.</p>
        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>
      </div>

      <!-- Card 3 -->
      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">
        <img src="Images/Pests.jpg" alt="Pineapple Pest Resources" class="h-32 w-full object-cover rounded mb-4" />
        <h3 class="font-semibold text-lg mb-2">Pineapple Pest Resources</h3>
        <p class="text-sm text-gray-700 mb-4">Get updated on pineapple pest management. Discover solutions that protect your crops and enhance productivity.</p>
        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Browse pest solutions</a>
      </div>

    </div>

    <!-- Green Background with Choices -->
    <div class="bg-[#115D5B] mt-12 py-6 flex justify-center items-center gap-4 rounded-lg">
      <button class="w-4 h-4 rounded-full bg-white hover:bg-gray-300 transition"></button>
      <button class="w-4 h-4 rounded-full bg-white hover:bg-gray-300 transition"></button>
      <button class="w-4 h-4 rounded-full bg-white hover:bg-gray-300 transition"></button>
    </div>

    <!-- Browse Research button -->
    <div class="mt-6 flex justify-center items-center gap-4">
      <a href="elibrary.php" class="bg-[#115D5B] text-white px-6 py-3 rounded-md font-semibold border border-white hover:bg-[#16663F] transition rounded-lg">
        Browse Research
      </a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-[#115D5B] text-white text-center py-4">
  <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
</footer>

<!-- Scripts -->
<script>
  // Hamburger toggle
  const menuToggle = document.getElementById('menu-toggle');
  const navMenu = document.getElementById('nav-menu');
  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      navMenu.classList.toggle('hidden');
    });
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

</body>
</html>