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
  <title>For Authors - CNLRRS Research Library</title>
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
        <a href="OurServices.php" class="hover:underline">Our Services</a>
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
    <img src="Images/AuthorsCn.png" alt="Section Divider" class="w-full h-26 object-cover" />
  </div>
</main>

<!-- Full-width line -->
<div class="my-8 w-full border-t-4 border-[#103635]"></div>

<!-- For Authors Introduction -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    For Authors
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
      The CNLRRS Research Library welcomes submissions from researchers, scientists, and agricultural 
      professionals. We provide an open-access platform to publish and disseminate high-quality research 
      that contributes to sustainable agriculture, rural development, and food security in the Philippines 
      and the broader region.
    </p>
    <p class="text-sm md:text-base leading-relaxed text-gray-800">
      Whether you have peer-reviewed research papers, technical reports, extension materials, or 
      case studies related to lowland rainfed farming systems and agricultural innovation, we encourage 
      you to share your work with our growing community of researchers, farmers, and policymakers.
    </p>
  </div>
</section>

<!-- Submission Guidelines Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Submission Guidelines
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Guideline 1 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-file-alt text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Manuscript Requirements</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Submit manuscripts in PDF or Word format. Include a title page with author names, 
        affiliations, contact information, and an abstract of 150-250 words. Ensure proper 
        formatting with clear headings and references.
      </p>
    </div>

    <!-- Guideline 2 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-check-circle text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Content Scope</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        We accept research on lowland rainfed farming systems, sustainable agriculture, 
        climate resilience, crop management, soil conservation, water management, and 
        rural development initiatives.
      </p>
    </div>

    <!-- Guideline 3 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-gavel text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Peer Review Process</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        All submissions undergo a rigorous peer review process by agricultural experts 
        and specialists. The review typically takes 4-8 weeks. Authors will be notified 
        of the results and any required revisions.
      </p>
    </div>

    <!-- Guideline 4 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-copyright text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Rights & Licensing</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Published works are made available under a Creative Commons License, allowing 
        for free access and distribution while maintaining author attribution. Authors 
        retain copyright to their work.
      </p>
    </div>

  </div>
</section>

<!-- Types of Publications -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Types of Publications We Accept
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <div class="bg-white p-4 rounded border-b-4 border-[#103635]">
        <h3 class="font-bold text-[#103635] mb-2 text-base">Peer-Reviewed Research Articles</h3>
        <p class="text-sm text-gray-700">
          Original research findings with introduction, methodology, results, discussion, 
          and conclusions. Typically 3,000-8,000 words.
        </p>
      </div>

      <div class="bg-white p-4 rounded border-b-4 border-[#103635]">
        <h3 class="font-bold text-[#103635] mb-2 text-base">Technical Reports</h3>
        <p class="text-sm text-gray-700">
          Comprehensive reports on research projects, field trials, surveys, and 
          experimental studies with detailed findings and recommendations.
        </p>
      </div>

      <div class="bg-white p-4 rounded border-b-4 border-[#103635]">
        <h3 class="font-bold text-[#103635] mb-2 text-base">Extension Materials</h3>
        <p class="text-sm text-gray-700">
          Practical guides, fact sheets, training manuals, and brochures designed for 
          farmers and agricultural practitioners.
        </p>
      </div>

      <div class="bg-white p-4 rounded border-b-4 border-[#103635]">
        <h3 class="font-bold text-[#103635] mb-2 text-base">Case Studies</h3>
        <p class="text-sm text-gray-700">
          Detailed accounts of real-world agricultural projects, innovations, and 
          success stories demonstrating practical applications.
        </p>
      </div>

      <div class="bg-white p-4 rounded border-b-4 border-[#103635]">
        <h3 class="font-bold text-[#103635] mb-2 text-base">Review Articles</h3>
        <p class="text-sm text-gray-700">
          Comprehensive reviews of current knowledge, trends, and challenges in specific 
          agricultural topics and research areas.
        </p>
      </div>

      <div class="bg-white p-4 rounded border-b-4 border-[#103635]">
        <h3 class="font-bold text-[#103635] mb-2 text-base">Policy Documents</h3>
        <p class="text-sm text-gray-700">
          Evidence-based policy recommendations and strategic documents supporting 
          agricultural development and sustainability initiatives.
        </p>
      </div>

    </div>
  </div>
</section>

<!-- Submission Process -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Submission Process
  </h2>
  <div class="space-y-4">
    
    <div class="flex gap-4">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold">
          1
        </div>
      </div>
      <div class="flex-grow">
        <h3 class="text-lg font-bold text-[#103635] mb-2">Prepare Your Manuscript</h3>
        <p class="text-sm md:text-base text-gray-700">
          Prepare your manuscript following our guidelines. Ensure proper formatting, 
          citations, and that all co-authors are included with their affiliations.
        </p>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold">
          2
        </div>
      </div>
      <div class="flex-grow">
        <h3 class="text-lg font-bold text-[#103635] mb-2">Submit Your Work</h3>
        <p class="text-sm md:text-base text-gray-700">
          Submit your manuscript through our online submission portal or email it to 
          <a href="mailto:dacnlrrs@gmail.com" class="text-[#103635] underline">dacnlrrs@gmail.com</a> 
          with the subject line "Manuscript Submission: [Your Title]".
        </p>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold">
          3
        </div>
      </div>
      <div class="flex-grow">
        <h3 class="text-lg font-bold text-[#103635] mb-2">Initial Review</h3>
        <p class="text-sm md:text-base text-gray-700">
          Our editorial team will perform an initial review to ensure the manuscript 
          meets our scope and quality standards. You will receive a confirmation within 1-2 weeks.
        </p>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold">
          4
        </div>
      </div>
      <div class="flex-grow">
        <h3 class="text-lg font-bold text-[#103635] mb-2">Peer Review</h3>
        <p class="text-sm md:text-base text-gray-700">
          Your manuscript will be sent to 2-3 independent peer reviewers for evaluation. 
          This process typically takes 4-8 weeks.
        </p>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold">
          5
        </div>
      </div>
      <div class="flex-grow">
        <h3 class="text-lg font-bold text-[#103635] mb-2">Revision & Decision</h3>
        <p class="text-sm md:text-base text-gray-700">
          You will receive reviewers' comments and a decision (Accept, Revise, or Reject). 
          If revisions are requested, resubmit your revised manuscript within the specified timeframe.
        </p>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold">
          6
        </div>
      </div>
      <div class="flex-grow">
        <h3 class="text-lg font-bold text-[#103635] mb-2">Publication</h3>
        <p class="text-sm md:text-base text-gray-700">
          Upon final acceptance, your work will be published on our platform and made 
          available to the global research community at no charge.
        </p>
      </div>
    </div>

  </div>
</section>

<!-- Contact Section -->
<section class="max-w-7xl mx-auto px-6 py-10 bg-gray-50 rounded-lg">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635] text-center">
    Questions? Get in Touch
  </h2>
  <div class="max-w-2xl mx-auto bg-white p-6 md:p-8 rounded-lg border-2 border-[#103635]">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-6">
      If you have questions about the submission process, manuscript requirements, or need 
      further assistance, please don't hesitate to contact us.
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
      <p class="text-sm md:text-base font-semibold text-[#103635]">
        <i class="fas fa-map-marker-alt mr-2"></i>
        Address: Calasgasan, Daet, Camarines Norte, Philippines
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