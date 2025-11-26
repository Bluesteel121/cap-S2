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
  <title>For Publishers - CNLRRS Research Library</title>
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
    <img src="Images/PublishersCn.png" alt="Section Divider" class="w-full h-26 object-cover" />
  </div>
</main>

<!-- Full-width line -->
<div class="my-8 w-full border-t-4 border-[#103635]"></div>

<!-- For Publishers Introduction -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    For Publishers
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
      The CNLRRS Research Library offers strategic partnership opportunities for publishers, 
      academic institutions, research organizations, and content providers. Our open-access platform 
      reaches a wide audience of researchers, farmers, policymakers, and agricultural professionals 
      across the Philippines and the Asia-Pacific region.
    </p>
    <p class="text-sm md:text-base leading-relaxed text-gray-800">
      By partnering with us, publishers can expand their reach, increase the visibility of their 
      publications, contribute to agricultural advancement, and support evidence-based decision-making 
      in the agricultural sector.
    </p>
  </div>
</section>

<!-- Partnership Opportunities Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Partnership Opportunities
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Partnership 1 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-book-open text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Content Distribution</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Distribute your publications through our platform to reach a growing audience of 
        agricultural professionals and researchers. Leverage our established network for 
        greater visibility and impact.
      </p>
    </div>

    <!-- Partnership 2 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-handshake text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Strategic Collaboration</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Work with CNLRRS on joint research initiatives, special collections, or themed 
        publication series. Collaborate on projects that advance agricultural knowledge 
        and innovation.
      </p>
    </div>

    <!-- Partnership 3 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-sync text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Metadata Harvesting</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Access our repository through OAI-PMH protocol for metadata harvesting and integration 
        with your publishing systems. Ensure seamless interoperability and discoverability.
      </p>
    </div>

    <!-- Partnership 4 -->
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
      <div class="flex items-center mb-4">
        <i class="fas fa-chart-line text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg md:text-xl font-bold text-[#103635]">Analytics & Reporting</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Access detailed analytics and usage statistics for your publications. Monitor downloads, 
        views, and audience engagement to measure the impact of your content.
      </p>
    </div>

  </div>
</section>

<!-- Publisher Benefits Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Publisher Benefits
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <div class="flex gap-4">
        <i class="fas fa-globe text-2xl text-[#103635] flex-shrink-0"></i>
        <div>
          <h3 class="font-bold text-[#103635] mb-2">Global Reach</h3>
          <p class="text-sm text-gray-700">
            Expand your publications to a worldwide audience of researchers and agricultural 
            professionals interested in lowland rainfed farming and sustainable agriculture.
          </p>
        </div>
      </div>

      <div class="flex gap-4">
        <i class="fas fa-eye text-2xl text-[#103635] flex-shrink-0"></i>
        <div>
          <h3 class="font-bold text-[#103635] mb-2">Increased Visibility</h3>
          <p class="text-sm text-gray-700">
            Benefit from our established reputation and searchability in academic databases. 
            Your content will be discoverable through multiple channels and search engines.
          </p>
        </div>
      </div>

      <div class="flex gap-4">
        <i class="fas fa-lock-open text-2xl text-[#103635] flex-shrink-0"></i>
        <div>
          <h3 class="font-bold text-[#103635] mb-2">Open Access Model</h3>
          <p class="text-sm text-gray-700">
            Participate in the open-access movement while maintaining copyright protections. 
            Maximize impact by removing barriers to knowledge access.
          </p>
        </div>
      </div>

      <div class="flex gap-4">
        <i class="fas fa-users text-2xl text-[#103635] flex-shrink-0"></i>
        <div>
          <h3 class="font-bold text-[#103635] mb-2">Community Engagement</h3>
          <p class="text-sm text-gray-700">
            Connect with researchers, farmers, and policymakers. Foster collaboration and 
            generate feedback from your target audience.
          </p>
        </div>
      </div>

      <div class="flex gap-4">
        <i class="fas fa-shield-alt text-2xl text-[#103635] flex-shrink-0"></i>
        <div>
          <h3 class="font-bold text-[#103635] mb-2">Content Preservation</h3>
          <p class="text-sm text-gray-700">
            Ensure your publications are preserved long-term with our robust digital 
            preservation infrastructure and backup systems.
          </p>
        </div>
      </div>

      <div class="flex gap-4">
        <i class="fas fa-cogs text-2xl text-[#103635] flex-shrink-0"></i>
        <div>
          <h3 class="font-bold text-[#103635] mb-2">Technical Support</h3>
          <p class="text-sm text-gray-700">
            Receive dedicated technical support for integration, metadata management, and 
            system optimization to ensure smooth operations.
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Publisher Requirements Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Publisher Requirements & Standards
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Requirement 1 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-certificate text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Quality Assurance</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        All content must meet quality standards and be peer-reviewed or professionally vetted. 
        Peer-review processes must be transparent and rigorous.
      </p>
    </div>

    <!-- Requirement 2 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-stamp text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Metadata Standards</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Provide complete and accurate metadata including Dublin Core elements, keywords, 
        authors, and subject classifications for all publications.
      </p>
    </div>

    <!-- Requirement 3 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-legal text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Legal Compliance</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Ensure all publications comply with intellectual property laws and include proper 
        rights statements. Obtain necessary permissions for all third-party content.
      </p>
    </div>

    <!-- Requirement 4 -->
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-layer-group text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Format Compatibility</h3>
      </div>
      <p class="text-sm md:text-base leading-relaxed text-gray-700">
        Provide content in standard formats (PDF, HTML, XML) that ensure compatibility and 
        long-term accessibility across different platforms and devices.
      </p>
    </div>

  </div>
</section>

<!-- Partnership Agreement Section -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Partnership Process
  </h2>
  <div class="space-y-4">
    
    <div class="flex gap-4">
      <div class="flex-shrink-0">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold">
          1
        </div>
      </div>
      <div class="flex-grow">
        <h3 class="text-lg font-bold text-[#103635] mb-2">Initial Inquiry</h3>
        <p class="text-sm md:text-base text-gray-700">
          Contact us to discuss your publishing organization, content scope, and partnership 
          goals. We'll evaluate potential collaboration opportunities.
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
        <h3 class="text-lg font-bold text-[#103635] mb-2">Needs Assessment</h3>
        <p class="text-sm md:text-base text-gray-700">
          We'll conduct a comprehensive assessment of your publishing needs, content requirements, 
          and technical infrastructure to design a customized solution.
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
        <h3 class="text-lg font-bold text-[#103635] mb-2">Agreement Negotiation</h3>
        <p class="text-sm md:text-base text-gray-700">
          We'll draft a partnership agreement outlining terms, responsibilities, revenue sharing 
          (if applicable), and technical specifications for integration.
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
        <h3 class="text-lg font-bold text-[#103635] mb-2">Technical Setup</h3>
        <p class="text-sm md:text-base text-gray-700">
          Our technical team will configure systems for content integration, metadata harvesting, 
          and analytics. Testing will ensure smooth operation.
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
        <h3 class="text-lg font-bold text-[#103635] mb-2">Content Migration</h3>
        <p class="text-sm md:text-base text-gray-700">
          We'll assist in migrating your publications to our platform, ensuring accurate 
          metadata, proper indexing, and quality assurance throughout the process.
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
        <h3 class="text-lg font-bold text-[#103635] mb-2">Launch & Support</h3>
        <p class="text-sm md:text-base text-gray-700">
          Your content goes live and we provide ongoing technical support, monitoring, and 
          regular analytics reporting to ensure successful partnership.
        </p>
      </div>
    </div>

  </div>
</section>

<!-- Contact Section -->
<section class="max-w-7xl mx-auto px-6 py-10 bg-gray-50 rounded-lg">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635] text-center">
    Ready to Partner With Us?
  </h2>
  <div class="max-w-2xl mx-auto bg-white p-6 md:p-8 rounded-lg border-2 border-[#103635]">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-6">
      We're interested in exploring partnership opportunities with publishers, research 
      organizations, and content providers. Let's discuss how we can work together to 
      advance agricultural research and knowledge sharing.
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
    <div class="mt-6 text-center">
      <a href="mailto:dacnlrrs@gmail.com?subject=Publisher%20Partnership%20Inquiry" class="inline-block bg-[#103635] text-white px-8 py-3 rounded-xl font-semibold hover:bg-opacity-90 transition">
        Send Partnership Inquiry
      </a>
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