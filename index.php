<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CNLRRS Research Library</title>
  <link rel="icon" href="Images/Favicon.ico">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
  <!-- Your page content goes here -->
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
          <a href="#" class="hover:underline">Our Services</a>
          <a href="About.php" class="hover:underline">About Us</a>
        </div>

        <!-- Login -->
        <a href="account.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
      </div>
    </nav>
  

  <!-- Banner Section -->
<style>
  @layer utilities {
    .text-outline-white {
      -webkit-text-stroke: 1px white;
    }
  }
</style>

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
      
      <!-- Card Component -->
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
      <!-- Choice Circles -->
      <button class="w-4 h-4 rounded-full bg-white hover:bg-gray-300 transition"></button>
      <button class="w-4 h-4 rounded-full bg-white hover:bg-gray-300 transition"></button>
      <button class="w-4 h-4 rounded-full bg-white hover:bg-gray-300 transition"></button>
    </div>
  </div>
</section>

<!-- Search Section -->
<!--        <div class="bg-white rounded-lg p-6 mb-8 shadow-md ">-->
<!--            <h2 class="text-xl font-bold text-gray-800 mb-4">Advanced Search</h2>-->
<!--            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 mb-8">-->
<!--                <div class="flex-1">-->
<!--                    <input type="text" placeholder="Search keywords" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500" />-->
<!--                </div>-->
<!--                <div class="md:w-1/4">-->
<!--                    <select class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">-->
<!--                        <option value="">Category</option>-->
<!--                        <option value="cultivation">Cultivation</option>-->
<!--                        <option value="genetics">Genetics</option>-->
<!--                        <option value="nutrition">Nutrition</option>-->
<!--                        <option value="processing">Processing</option>-->
<!--                        <option value="market">Market Research</option>-->
<!--                    </select>-->
<!--                </div>-->
<!--                <div class="md:w-1/4">-->
<!--                    <select class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">-->
<!--                        <option value="">Publication Year</option>-->
<!--                        <option value="2025">2025</option>-->
<!--                        <option value="2024">2024</option>-->
<!--                        <option value="2023">2023</option>-->
<!--                        <option value="2022">2022</option>-->
<!--                        <option value="2021">2021</option>-->
<!--                        <option value="older">2020 & Older</option>-->
<!--                    </select>-->
<!--                </div>-->
<!--                <button class="bg-[#115D5B] hover:bg-green-600 text-white px-6 py-3 rounded-lg">Search</button>-->
<!--            </div>-->

<!--             <div class="grid grid-cols-1 md:grid-cols-3 gap-6">-->
      
      <!-- Card 1 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Books.png" alt="Books & Collection Services" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Books & Collection Services</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Explore our library collection. Maximize both time and selection with book tools when selecting print books.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>-->
<!--      </div>-->

      <!-- Card 2 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Soil.png" alt="Pineapple Soil & Growth Solutions" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Pineapple Soil & Growth Solutions</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Improve your farming knowledge with Pineapple Soil Amendment guides. Learn the needs of pineapple farming.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>-->
<!--      </div>-->

      <!-- Card 3 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Pests.png" alt="Pineapple Pest Resources" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Pineapple Pest Resources</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Get updated on pineapple pest management. Discover solutions that protect your crops and enhance productivity.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Browse pest solutions</a>-->
<!--      </div>-->

      <!-- Card 4 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Books.png" alt="Books & Collection Services" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Books & Collection Services</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Explore our library collection. Maximize both time and selection with book tools when selecting print books.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>-->
<!--      </div>-->

      <!-- Card 5 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Soil.png" alt="Pineapple Soil & Growth Solutions" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Pineapple Soil & Growth Solutions</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Improve your farming knowledge with Pineapple Soil Amendment guides. Learn the needs of pineapple farming.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>-->
<!--      </div>-->

      <!-- Card 6 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Pests.png" alt="Pineapple Pest Resources" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Pineapple Pest Resources</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Get updated on pineapple pest management. Discover solutions that protect your crops and enhance productivity.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Browse pest solutions</a>-->
<!--      </div>-->

      <!-- Card 7 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Books.png" alt="Books & Collection Services" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Books & Collection Services</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Explore our library collection. Maximize both time and selection with book tools when selecting print books.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>-->
<!--      </div>-->

      <!-- Card 8 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Soil.png" alt="Pineapple Soil & Growth Solutions" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Pineapple Soil & Growth Solutions</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Improve your farming knowledge with Pineapple Soil Amendment guides. Learn the needs of pineapple farming.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>-->
<!--      </div>-->

      <!-- Card 9 -->
<!--      <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">-->
<!--        <img src="Images/Pests.png" alt="Pineapple Pest Resources" class="h-32 w-full object-cover rounded mb-4" />-->
<!--        <h3 class="font-semibold text-lg mb-2">Pineapple Pest Resources</h3>-->
<!--        <p class="text-sm text-gray-700 mb-4">Get updated on pineapple pest management. Discover solutions that protect your crops and enhance productivity.</p>-->
<!--        <a href="#" class="text-[#115D5B] font-semibold hover:underline">Browse pest solutions</a>-->
<!--      </div>-->

    
    <!--</div>-->
 <!-- Browse Research button -->
    <div class="mt-6 flex justify-center items-center gap-4">
      <a href="elibrary.php" class="bg-[#115D5B] text-white px-6 py-3 rounded-md font-semibold border border-white hover:bg-[#16663F] transition rounded-lg">
        Browse Research
      </a>
    </div>

  </div>

  

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