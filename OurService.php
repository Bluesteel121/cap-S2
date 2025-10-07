<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Our Services - CNLRRS Research Library</title>
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
        <a href="index.php" class="hover:underline">Home</a>
        <a href="OurServices.php" class="hover:underline">Our Services</a>
        <a href="About.php" class="hover:underline">About Us</a>
      </div>

      <!-- Login -->
      <a href="account.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
    </div>
  </nav>
  
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

</body>
</html>

<!-- Footer -->
<footer class="bg-[#115D5B] text-white text-center py-4 mt-16">
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
    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // For Mobile
  });
</script>
</body>
</html>