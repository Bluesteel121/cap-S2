<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.5" />
  <title>CNLRRS Research Library</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-white pt-24 text-[#103625] font-sans">

  <!-- Header -->
  <header id="main-header" class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex flex-wrap justify-between items-center gap-4">
      <!-- Logo and Title -->
      <div class="flex items-center space-x-4">
        <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-16 object-contain" />
        <h1 class="text-lg font-bold leading-tight">
          Camarines Norte Lowland <br class="hidden sm:block" />
          Rainfed Research Station
        </h1>
      </div>

      <!-- Partner Logos -->
      <div class="flex items-center space-x-4">
        <img src="Images/Ph.png" alt="Philippines Logo" class="h-16 object-contain" />
        <img src="Images/Da.png" alt="Department of Agriculture Logo" class="h-16 object-contain" />

      </div>

      <!-- Contact Info Section -->
<div class="flex items-center justify-start space-x-4">

  <!-- Text Info -->
  <div class="text-sm font-semibold text-center">
    <p>DEPARTMENT OF AGRICULTURE</p>
    <p>Calasgasan, Daet, Philippines</p>
    <p>Email: <a href="mailto:dacnlrrs@gmail.com" class="underline">dacnlrrs@gmail.com</a></p>
    <p>Contact No.: 0951 609 9599</p>
  </div>
  <!-- Logo -->
  <img src="Images/Bago.png" alt="Bagong Pilipinas Logo" class="h-16 object-contain" />

</div>
    </div>

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
          <a href="#" class="hover:underline">Home</a>
          <a href="#" class="hover:underline">Our Services</a>
          <a href="#" class="hover:underline">About Us</a>
        </div>

        <!-- Login -->
        <a href="account.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
      </div>
    </nav>
  </header>

  <!-- Banner Section -->
  <section class="relative">
    <img src="Images/Library2.png" alt="Library" class="w-full h-[750px] object-cover" />
    <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-white bg-black bg-opacity-50 px-4">
      <h2 class="text-3xl md:text-4xl font-bold">Welcome to CNLRRS Research Library</h2>
      <p class="mt-2 text-lg max-w-2xl">Access the latest research and publications about Queen Pineapple varieties, including cultivation, health benefits, and more.</p>
      <div class="mt-4 flex gap-4">
        <a href="#" class="bg-green-600 px-4 py-2 rounded-md font-semibold hover:bg-green-700">Browse Research</a>
        <a href="#" class="bg-green-600 px-4 py-2 rounded-md font-semibold hover:bg-green-700">Submit Paper</a>
      </div>
    </div>
  </section>

  <!-- Info Cards -->
  <section class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 px-4">
      
      <!-- Card Component -->
      <a href="#" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/About CN.png" alt="About CNLRRS" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">About CNLRRS</h3>
        <p class="text-sm text-gray-600">Explore agricultural studies showcasing decades of scientific research on Pineapple farming.</p>
      </a>

      <a href="#" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/UserG.png" alt="User Guide" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">User Guide</h3>
        <p class="text-sm text-gray-600">Learn how to find and read articles of your interest.</p>
      </a>

      <a href="#" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/Collections.png" alt="Collections" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">Collections</h3>
        <p class="text-sm text-gray-600">Browse the CNLRRS library and learn about its collection.</p>
      </a>

      <a href="#" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/Collections.png" alt="For Authors" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">For Authors</h3>
        <p class="text-sm text-gray-600">Navigate the CNLRRS submission methods easily.</p>
      </a>

      <a href="#" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
        <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
          <img src="Images/Collections.png" alt="For Publisher" class="h-10" />
        </div>
        <h3 class="font-semibold mb-1">For Publisher</h3>
        <p class="text-sm text-gray-600">Learn about options for journals and publishers and the CNLRRS selection process.</p>
      </a>

    </div>
  </section>

</body>
</html>
  
  <!-- Footer -->
  <footer class="bg-[#115D5B] text-white text-center py-4">
    <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
  </footer>

  <!-- Scripts -->
  <script>
    // Hamburger toggle
    const menuToggle = document.getElementById('menu-toggle');
    const navMenu = document.getElementById('nav-menu');
    menuToggle.addEventListener('click', () => {
      navMenu.classList.toggle('hidden');
    });

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

    
  </script>
</body>
</html>