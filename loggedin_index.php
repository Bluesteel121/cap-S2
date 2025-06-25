<?php
session_start();

if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CNLRRS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Added styles for the profile picture and sidebar */
    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
    }

    .sidebar {
      height: 100%;
      width: 0;
      position: fixed;
      z-index: 1;
      top: 0;
      left: 0;
      background-color: #115D5B; /* Using a color from your design */
      overflow-x: hidden;
      transition: 0.5s;
      padding-top: 60px;
      color: white; /* Text color for sidebar links */
    }

    .sidebar a {
      padding: 8px 8px 8px 32px;
      text-decoration: none;
      font-size: 25px;
      color: white; /* Text color for sidebar links */
      display: block;
      transition: 0.3s;
    }

    .sidebar a:hover {
      background-color: #103625; /* A darker shade for hover effect */
    }

    .sidebar .closebtn {
      position: absolute;
      top: 0;
      right: 25px;
      font-size: 36px;
      margin-left: 50px;
      color: white; /* Color for the close button */
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
</header>

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

    <!-- Profile Picture (replaces Log In Button) -->
    <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['name'], 0, 1)); ?>.png" alt="Profile Picture" class="profile-pic" onclick="toggleSidebar()">
  </div>
</nav>

    <!-- Sidebar -->
    <div id="mySidebar" class="sidebar">
        <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
        <a href="#">Settings</a>
        <a href="/cap_s2/cap-S2/edit_profile.php">Profile</a>
        <a href="index.php">Log Out</a>
    </div>


  <!-- Main Content -->
  <section class="relative">
    <img src="Images/Banner.png" alt="Pineapple Farm" class="py-20 w-full h-[750px] object-cover" />
    <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-white bg-black bg-opacity-50">
      <h2 class="text-3xl font-bold">Welcome to Camarines Norte Lowland Rainfed Research Station</h2>
      <p class="mt-2 text-lg">Learn and Discover the Secrets Behind the Sweetest Pineapple in Camarines Norte</p>
      <a href="services.php" class="mt-4 bg-green-500 px-4 py-2 rounded-md text-white font-semibold hover:bg-green-700">Explore More</a>
    </div>
  </section>

  <!-- Image Grid Section -->
  <section class="container mx-auto my-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="rounded-lg overflow-hidden shadow-lg border">
        <img src="Images/farm.jpg" alt="Farm Image" class="w-full h-[300px] object-cover" />
      </div>
      <div class="rounded-lg overflow-hidden shadow-lg border">
        <img src="Images/facility.jpg" alt="Facility Image" class="w-full h-[300px] object-cover" />
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section id="faq" class="container mx-auto my-12 px-4">
    <h2 class="text-3xl font-bold text-center text-[#115D5B] mb-8">Frequently Asked Questions</h2>

    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
        <button class="faq-question w-full text-left p-4 bg-[#115D5B] text-white font-semibold flex justify-between items-center">
          <span>What is the main focus of your research station?</span>
          <i class="fas fa-chevron-down transition-transform duration-300"></i>
        </button>
        <div class="faq-answer p-4 hidden">
          <p>Our research station primarily focuses on improving pineapple cultivation techniques, developing sustainable farming practices, and enhancing the quality of lowland rainfed crops in the region.</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
        <button class="faq-question w-full text-left p-4 bg-[#115D5B] text-white font-semibold flex justify-between items-center">
          <span>Can visitors tour the research facility?</span>
          <i class="fas fa-chevron-down transition-transform duration-300"></i>
        </button>
        <div class="faq-answer p-4 hidden">
          <p>Yes, we offer guided tours by appointment. Please contact us at least one week in advance to schedule your visit.</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
        <button class="faq-question w-full text-left p-4 bg-[#115D5B] text-white font-semibold flex justify-between items-center">
          <span>How can local farmers benefit from your research?</span>
          <i class="fas fa-chevron-down transition-transform duration-300"></i>
        </button>
        <div class="faq-answer p-4 hidden">
          <p>We regularly publish our findings and offer training programs for local farmers. Our research helps improve crop yields, reduce costs, and implement sustainable practices.</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
        <button class="faq-question w-full text-left p-4 bg-[#115D5B] text-white font-semibold flex justify-between items-center">
          <span>Do you offer educational programs for students?</span>
          <i class="fas fa-chevron-down transition-transform duration-300"></i>
        </button>
        <div class="faq-answer p-4 hidden">
          <p>Yes, we collaborate with schools and universities to provide educational programs, internships, and research opportunities for students interested in agriculture.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Us Section -->
  <section id="contact" class="bg-[#115D5B] text-white py-12">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-8">Contact Us</h2>

      <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <h3 class="text-xl font-semibold mb-4">Get in Touch</h3>
          <p class="mb-6">Have questions or want to learn more about our research? Reach out to us using the information below or fill out the contact form.</p>

          <div class="space-y-4">
            <div class="flex items-start">
              <i class="fas fa-map-marker-alt mt-1 mr-4"></i>
              <div>
                <h4 class="font-semibold">Address</h4>
                <p>Calasgasan, Daet, Camarines Norte, Philippines 4600</p>
              </div>
            </div>

            <div class="flex items-start">
              <i class="fas fa-phone-alt mt-1 mr-4"></i>
              <div>
                <h4 class="font-semibold">Phone</h4>
                <p>(+63) 951 609 9599</p>
              </div>
            </div>

            <div class="flex items-start">
              <i class="fas fa-envelope mt-1 mr-4"></i>
              <div>
                <h4 class="font-semibold">Email</h4>
                <p>dacnlrrs@gmail.com</p>
              </div>
            </div>

            <div class="flex items-start">
              <i class="fas fa-clock mt-1 mr-4"></i>
              <div>
                <h4 class="font-semibold">Operating Hours</h4>
                <p>Monday to Friday: 8:00 AM - 5:00 PM</p>
                <p>Saturday: 8:00 AM - 12:00 PM</p>
                <p>Sunday: Closed</p>
              </div>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-xl font-semibold mb-4">Send Us a Message</h3>
          <form class="space-y-4">
            <div>
              <label for="name" class="block mb-1">Name</label>
              <input type="text" id="name" class="w-full px-4 py-2 rounded text-gray-800" required>
            </div>

            <div>
              <label for="email" class="block mb-1">Email</label>
              <input type="email" id="email" class="w-full px-4 py-2 rounded text-gray-800" required>
            </div>

            <div>
              <label for="subject" class="block mb-1">Subject</label>
              <input type="text" id="subject" class="w-full px-4 py-2 rounded text-gray-800" required>
            </div>

            <div>
              <label for="message" class="block mb-1">Message</label>
              <textarea id="message" rows="4" class="w-full px-4 py-2 rounded text-gray-800" required></textarea>
            </div>

            <button type="submit" class="bg-white text-[#115D5B] px-6 py-2 rounded font-semibold hover:bg-gray-200 transition">Send Message</button>
          </form>
        </div>
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

    // FAQ accordion functionality
    document.querySelectorAll('.faq-question').forEach(button => {
      button.addEventListener('click', () => {
        const answer = button.nextElementSibling;
        const icon = button.querySelector('i');

        // Toggle answer visibility
        answer.classList.toggle('hidden');

        // Toggle icon rotation
        icon.classList.toggle('transform');
        icon.classList.toggle('rotate-180');

        // Close other open answers
        document.querySelectorAll('.faq-question').forEach(otherButton => {
          if (otherButton !== button) {
            otherButton.nextElementSibling.classList.add('hidden');
            otherButton.querySelector('i').classList.remove('transform', 'rotate-180');
          }
        });
      });
    });

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
  </script>
</body>
</html>
