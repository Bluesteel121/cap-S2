<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CNLRRS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-white pt-20">

  <!-- Header -->
<header id="main-header" class="bg-white text-[#103625] px-6 py-3 shadow-lg fixed top-0 left-0 w-full z-50 transition-transform duration-300">
 <div class="w-full mt-2 mx-auto flex justify-between items-center">
    <!-- Logo and Title -->
    <div class="flex items-center space-x-4">
      <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-20 w-auto object-contain">
      <h1 class="text-xl font-bold  leading-tight">Camarines Norte Lowland <br class="hidden sm:block" /> Rainfed Research Station</h1>
    </div>

   

    <img src="Images/Ph.png" alt="PH Logo" class="h-20 w-auto object-contain">
    <img src="Images/Da.png" alt="DA Logo" class="h-20 w-auto object-contain">
      <div class="text-center md:text-middle mt-2 md:mt-0">
        <p class="text-sm font-bold">DEPARTMENT OF AGRICULTURE</p>
        <p class="text-xs font-bold">Address: Calasgasan, Daet, Philippines</p>
        <p class="text-xs font-bold">Email: dacnlrrs@gmail.com</p>
        <p class="text-xs font-bold">Contact No.:  0951 609 9599</p>
      </div>

      <img src="Images/Bago.png" alt="Bagong Pilipinas Logo" class="h-20 w-auto object-contain">
  </div>

 
   <nav class="bg-white border-b py-10 px-10">
  <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-between items-center gap-4">
    
    <!-- Search with Advance -->
    <div class="flex flex-grow max-w-xl border border-green-900 rounded-full overflow-hidden">
      <input
        type="text"
        placeholder="Search publications, articles, keywords, etc."
        class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none"
      />
      <button class="px-4 text-green-900 text-xl">
        🔍
      </button>
      <button class="bg-transparent px-4 font-semibold text-green-900 hover:underline">
        Advance
      </button>
    </div>

    <!-- Navigation Links -->
    <div class="flex items-center gap-6 text-green-900 font-semibold text-sm">
      <a href="#" class="hover:underline">Home</a>
      <a href="#" class="hover:underline">Our Services</a>
      <a href="#" class="hover:underline">About Us</a>
    </div>

    <!-- Log In Button -->
    <a href="account.php" class="bg-green-900 text-white px-6 py-2 rounded-xl font-semibold">
      Log In
    </a>
  </div>
</nav>
 </header>


  <!-- Main Content -->
  <section class="relative">
    <img src="Images/Banner.png" alt="Pineapple Farm" class="py-20 w-full h-[750px] object-cover" />
    <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-white bg-black bg-opacity-50">
      <h2 class="text-3xl font-bold">Welcome to Camarines Norte Lowland Rainfed Research Station</h2>
      <p class="mt-2 text-lg">Learn and Discover the Secrets Behind the Sweetest Pineapple in Camarines Norte</p>
      <a href="services.php" class="mt-4 bg-green-500 px-4 py-2 rounded-md text-white font-semibold hover:bg-green-700">Explore More</a>
    </div>
  </section>
  
  <!-- Info Cards -->
  <section class="py-10 bg-gray-100">
  <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 px-4">

    <!-- Card 1 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/About CN.png" alt="About" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">About CNLRRS</h3>
      <p class="text-sm text-gray-600">Explore agricultural studies on Pineapple farming.</p>
    </a>

    <!-- Card 2 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/UserG.png" alt="User Guide" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">User Guide</h3>
      <p class="text-sm text-gray-600">Find and read articles of your interest.</p>
    </a>

    <!-- Card 3 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/Collections.png" alt="Collections" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">Collections</h3>
      <p class="text-sm text-gray-600">Browse and learn about the CNLRRS library.</p>
    </a>

    <!-- Card 4 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/Collections.png" alt="For Authors" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">For Authors</h3>
      <p class="text-sm text-gray-600">Navigate submission methods easily.</p>
    </a>

    <!-- Card 5 -->
    <a href="#" class="text-center bg-white rounded-lg p-4 shadow hover:shadow-md transition">
      <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
        <img src="Images/Collections.png" alt="For Publisher" class="h-10" />
      </div>
      <h3 class="font-semibold mb-1">For Publisher</h3>
      <p class="text-sm text-gray-600">Explore publisher options and process.</p>
    </a>

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