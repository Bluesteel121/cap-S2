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
        <a href="index.php" class="hover:underline">Home</a>
        <a href="OurServices.php" class="hover:underline">Our Services</a>
        <a href="About.php" class="hover:underline">About Us</a>
      </div>

      <!-- Login -->
      <a href="account.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
    </div>
  </nav>
  
  <div class="w-full flex justify-center my-1">
    <img src="Images/GuideCn.png" alt="Section Divider" class="w-full h-26 object-cover" />
  </div>
</main>

<!-- Full-width line -->
<div class="my-8 w-full border-t-4 border-[#103635]"></div>

<!-- User Guide Introduction -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    User Guide
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <p class="text-sm md:text-base leading-relaxed text-gray-800 mb-4">
      Welcome to the CNLRRS Research Library! This comprehensive guide will help you navigate 
      our platform, search for publications, access resources, and make the most of our digital 
      repository. Whether you're a researcher, farmer, student, or policymaker, this guide 
      provides step-by-step instructions for all features and functionalities.
    </p>
    <p class="text-sm md:text-base leading-relaxed text-gray-800">
      All content in our library is free and open-access, designed to promote knowledge sharing 
      and agricultural advancement. If you encounter any issues or have questions, please don't 
      hesitate to contact us.
    </p>
  </div>
</section>

<!-- Table of Contents -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Quick Navigation
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    
    <a href="#getting-started" class="bg-white border-2 border-[#103635] p-4 rounded-lg hover:bg-gray-50 transition">
      <div class="flex items-center">
        <i class="fas fa-play-circle text-2xl text-[#103635] mr-3"></i>
        <span class="font-bold text-[#103635]">Getting Started</span>
      </div>
    </a>

    <a href="#searching" class="bg-white border-2 border-[#103635] p-4 rounded-lg hover:bg-gray-50 transition">
      <div class="flex items-center">
        <i class="fas fa-search text-2xl text-[#103635] mr-3"></i>
        <span class="font-bold text-[#103635]">Searching</span>
      </div>
    </a>

    <a href="#filtering" class="bg-white border-2 border-[#103635] p-4 rounded-lg hover:bg-gray-50 transition">
      <div class="flex items-center">
        <i class="fas fa-filter text-2xl text-[#103635] mr-3"></i>
        <span class="font-bold text-[#103635]">Filtering Results</span>
      </div>
    </a>

    <a href="#viewing" class="bg-white border-2 border-[#103635] p-4 rounded-lg hover:bg-gray-50 transition">
      <div class="flex items-center">
        <i class="fas fa-book-reader text-2xl text-[#103635] mr-3"></i>
        <span class="font-bold text-[#103635]">Viewing Content</span>
      </div>
    </a>

    <a href="#downloading" class="bg-white border-2 border-[#103635] p-4 rounded-lg hover:bg-gray-50 transition">
      <div class="flex items-center">
        <i class="fas fa-download text-2xl text-[#103635] mr-3"></i>
        <span class="font-bold text-[#103635]">Downloading</span>
      </div>
    </a>

    <a href="#account" class="bg-white border-2 border-[#103635] p-4 rounded-lg hover:bg-gray-50 transition">
      <div class="flex items-center">
        <i class="fas fa-user text-2xl text-[#103635] mr-3"></i>
        <span class="font-bold text-[#103635]">Account & Favorites</span>
      </div>
    </a>

  </div>
</section>

<!-- Getting Started Section -->
<section id="getting-started" class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Getting Started
  </h2>
  <div class="space-y-6">
    
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-start gap-4">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold flex-shrink-0">
          1
        </div>
        <div>
          <h3 class="text-lg font-bold text-[#103635] mb-2">Access the Platform</h3>
          <p class="text-sm md:text-base text-gray-700">
            Visit the CNLRRS Research Library homepage. No registration is required to browse 
            and access all publications. Our platform is available 24/7 and is accessible from 
            any device with internet connection.
          </p>
        </div>
      </div>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-start gap-4">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold flex-shrink-0">
          2
        </div>
        <div>
          <h3 class="text-lg font-bold text-[#103635] mb-2">Create an Account (Optional)</h3>
          <p class="text-sm md:text-base text-gray-700">
            While browsing is free, creating an account allows you to save favorites, create 
            alerts for new publications, and customize your experience. Click "Log In" to create 
            a new account or sign in with your existing credentials.
          </p>
        </div>
      </div>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <div class="flex items-start gap-4">
        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-[#103635] text-white font-bold flex-shrink-0">
          3
        </div>
        <div>
          <h3 class="text-lg font-bold text-[#103635] mb-2">Explore the Homepage</h3>
          <p class="text-sm md:text-base text-gray-700">
            The homepage features recently published articles, popular publications, and 
            featured collections. Browse through these to discover relevant research or 
            navigate using the main menu for specific sections.
          </p>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Searching Section -->
<section id="searching" class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Searching the Library
  </h2>
  <div class="space-y-6">
    
    <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm mb-6">
      <h3 class="text-lg font-bold text-[#103635] mb-4">Basic Search</h3>
      <p class="text-sm md:text-base text-gray-800 mb-4">
        Use the search bar prominently displayed on every page. Type keywords related to your 
        research interest and press Enter or click the search icon. The system will search across 
        titles, abstracts, keywords, and author names.
      </p>
      <div class="bg-white p-4 rounded border-l-4 border-[#103635]">
        <p class="text-xs md:text-sm text-gray-700">
          <strong>Example searches:</strong> "rainfed farming", "climate resilience", "soil management", 
          "sustainable agriculture", "crop yield"
        </p>
      </div>
    </div>

    <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm mb-6">
      <h3 class="text-lg font-bold text-[#103635] mb-4">Advanced Search</h3>
      <p class="text-sm md:text-base text-gray-800 mb-4">
        Click the "Advanced" button next to the search bar to access advanced search options. 
        This allows you to combine multiple search criteria for more precise results.
      </p>
      <div class="bg-white p-4 rounded border-l-4 border-[#103635]">
        <ul class="text-xs md:text-sm text-gray-700 space-y-2">
          <li><strong>Keyword:</strong> Search specific terms or phrases</li>
          <li><strong>Author:</strong> Find publications by specific researchers</li>
          <li><strong>Title:</strong> Search within publication titles only</li>
          <li><strong>Subject:</strong> Browse by research topics and categories</li>
          <li><strong>Publication Date:</strong> Filter by year or date range</li>
        </ul>
      </div>
    </div>

    <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
      <h3 class="text-lg font-bold text-[#103635] mb-4">Search Tips</h3>
      <ul class="text-sm md:text-base text-gray-800 space-y-3">
        <li><strong>•</strong> Use specific keywords for better results</li>
        <li><strong>•</strong> Try different keyword combinations if initial search yields no results</li>
        <li><strong>•</strong> Use quotation marks for phrase searches: "lowland rainfed"</li>
        <li><strong>•</strong> Search for author names to find all publications by specific researchers</li>
        <li><strong>•</strong> Combine multiple filters in advanced search for refined results</li>
      </ul>
    </div>

  </div>
</section>

<!-- Filtering Results Section -->
<section id="filtering" class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Filtering Your Results
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-list text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Publication Type</h3>
      </div>
      <p class="text-sm text-gray-700 mb-3">
        Filter by content type to narrow your search:
      </p>
      <ul class="text-xs md:text-sm text-gray-700 space-y-1">
        <li>• Research Articles</li>
        <li>• Technical Reports</li>
        <li>• Extension Materials</li>
        <li>• Case Studies</li>
      </ul>
    </div>

    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-calendar text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Publication Date</h3>
      </div>
      <p class="text-sm text-gray-700 mb-3">
        Select a date range to find recent publications or historical research. Use the calendar 
        picker or enter specific dates to filter results.
      </p>
    </div>

    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-tags text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Subject/Topic</h3>
      </div>
      <p class="text-sm text-gray-700 mb-3">
        Browse research by specific topics such as crop management, soil science, water management, 
        climate adaptation, and rural development.
      </p>
    </div>

    <div class="bg-white border-2 border-[#103635] rounded-lg p-6 shadow-md">
      <div class="flex items-center mb-3">
        <i class="fas fa-user-circle text-2xl text-[#103635] mr-3"></i>
        <h3 class="text-lg font-bold text-[#103635]">Author</h3>
      </div>
      <p class="text-sm text-gray-700 mb-3">
        Filter publications by author name to find all works by specific researchers or institutions.
      </p>
    </div>

  </div>
</section>

<!-- Viewing Content Section -->
<section id="viewing" class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Viewing Publications
  </h2>
  <div class="space-y-6">
    
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-lg font-bold text-[#103635] mb-3">Publication Page</h3>
      <p class="text-sm md:text-base text-gray-700 mb-3">
        Click on any publication in the search results to view its detailed page. The publication 
        page includes:
      </p>
      <ul class="text-sm md:text-base text-gray-700 space-y-2 ml-4">
        <li>• Title and authors</li>
        <li>• Publication date and type</li>
        <li>• Abstract or summary</li>
        <li>• Keywords and subject classifications</li>
        <li>• Download options in various formats</li>
        <li>• Citation information</li>
      </ul>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-lg font-bold text-[#103635] mb-3">Preview & Reading</h3>
      <p class="text-sm md:text-base text-gray-700">
        Many publications offer a preview function allowing you to view the content directly in 
        your browser. Click the preview icon to read the publication online. Use the page 
        navigation controls to move between pages.
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-lg font-bold text-[#103635] mb-3">Related Publications</h3>
      <p class="text-sm md:text-base text-gray-700">
        At the bottom of each publication page, you'll find related and recommended publications 
        on similar topics. This helps you discover additional relevant research.
      </p>
    </div>

  </div>
</section>

<!-- Downloading Section -->
<section id="downloading" class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Downloading Publications
  </h2>
  <div class="bg-gray-100 p-6 md:p-8 rounded-lg shadow-sm">
    <div class="space-y-6">
      
      <div>
        <h3 class="text-lg font-bold text-[#103635] mb-3">Download Options</h3>
        <p class="text-sm md:text-base text-gray-800 mb-4">
          All publications can be downloaded for free in various formats. Look for the download 
          buttons on the publication page:
        </p>
        <div class="bg-white p-4 rounded border-l-4 border-[#103635] space-y-2">
          <p class="text-sm text-gray-700"><strong>PDF:</strong> Portable Document Format for viewing and printing</p>
          <p class="text-sm text-gray-700"><strong>HTML:</strong> Web format for online reading</p>
          <p class="text-sm text-gray-700"><strong>Word:</strong> Microsoft Word format for editing and citation</p>
        </div>
      </div>

      <div>
        <h3 class="text-lg font-bold text-[#103635] mb-3">Citing Downloaded Content</h3>
        <p class="text-sm md:text-base text-gray-800 mb-4">
          Each publication page includes citation information in multiple formats (APA, MLA, Chicago, 
          Harvard). Use the provided citations when referencing downloaded content in your work.
        </p>
      </div>

      <div>
        <h3 class="text-lg font-bold text-[#103635] mb-3">Download Tips</h3>
        <ul class="text-sm md:text-base text-gray-800 space-y-2 ml-4">
          <li>• All downloads are free and unrestricted</li>
          <li>• Check your internet connection before downloading large files</li>
          <li>• Save publications in organized folders for easy reference</li>
          <li>• Always cite the source when using downloaded content</li>
        </ul>
      </div>

    </div>
  </div>
</section>

<!-- Account & Favorites Section -->
<section id="account" class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Account Management & Favorites
  </h2>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-lg font-bold text-[#103635] mb-3">Creating an Account</h3>
      <p class="text-sm md:text-base text-gray-700 mb-3">
        Click "Log In" and select "Create New Account". Fill in your information including name, 
        email, and password. Verify your email address to activate your account.
      </p>
      <div class="bg-gray-100 p-3 rounded text-xs text-gray-700">
        <strong>Account Benefits:</strong> Save favorites, create alerts, and personalize your experience
      </div>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-lg font-bold text-[#103635] mb-3">Saving Favorites</h3>
      <p class="text-sm md:text-base text-gray-700 mb-3">
        Click the heart or star icon on any publication to add it to your favorites. Access your 
        saved publications anytime from your account dashboard. Create collections to organize 
        your favorites by topic.
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-lg font-bold text-[#103635] mb-3">Email Alerts</h3>
      <p class="text-sm md:text-base text-gray-700 mb-3">
        Create custom alerts to receive notifications about new publications in specific topics 
        or by favorite authors. Manage alert frequency in your account settings (daily, weekly, or monthly).
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-lg font-bold text-[#103635] mb-3">Profile Settings</h3>
      <p class="text-sm md:text-base text-gray-700 mb-3">
        Update your profile information, change your password, manage email preferences, and 
        customize your notification settings. Access these options from your account dashboard.
      </p>
    </div>

  </div>
</section>

<!-- Frequently Asked Questions -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="text-2xl md:text-3xl font-bold mb-6 text-[#103635]">
    Frequently Asked Questions
  </h2>
  <div class="space-y-4">
    
    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-base md:text-lg font-bold text-[#103635] mb-2">Is registration required to access publications?</h3>
      <p class="text-sm md:text-base text-gray-700">
        No, registration is optional. You can browse and download all publications without creating 
        an account. However, creating an account allows you to save favorites and receive alerts.
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-base md:text-lg font-bold text-[#103635] mb-2">Are all publications free to download?</h3>
      <p class="text-sm md:text-base text-gray-700">
        Yes, all publications in the CNLRRS Research Library are completely free to access and 
        download. This is an open-access repository promoting knowledge sharing.
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-base md:text-lg font-bold text-[#103635] mb-2">How often is new content added?</h3>
      <p class="text-sm md:text-base text-gray-700">
        New publications are added regularly as they are submitted and reviewed. Subscribe to email 
        alerts to receive notifications about newly published content in your areas of interest.
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-base md:text-lg font-bold text-[#103635] mb-2">Can I use downloaded content in my publications?</h3>
      <p class="text-sm md:text-base text-gray-700">
        Yes, you can use downloaded content for research, study, and reference purposes. Always 
        cite the original source according to the provided citation formats (APA, MLA, Chicago, Harvard).
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-base md:text-lg font-bold text-[#103635] mb-2">What should I do if I encounter a problem?</h3>
      <p class="text-sm md:text-base text-gray-700">
        If you experience any issues with downloads, search, or account access, please contact 
        us at <a href="mailto:dacnlrrs@gmail.com" class="text-[#103635] underline">dacnlrrs@gmail.com</a> 
        or call 0951 609 9599. Our support team is available to assist you.
      </p>
    </div>

    <div class="bg-white border-l-4 border-[#103635] p-6 shadow-md">
      <h3 class="text-base md:text-lg font-bold text-[#103635] mb-2">How can I submit my research for publication?</h3>
      <p class="text-sm md:text-base text-gray-700">
        Visit our <a href="ForAuthor.php" class="text-[#103635] underline">For Authors</a> page 
        for detailed submission guidelines and instructions on how to submit your work for consideration.
      </p>
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