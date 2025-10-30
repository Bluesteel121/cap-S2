<?php
session_start();
require_once 'user_activity_logger.php';

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    logSecurityEvent('UNAUTHORIZED_ACCESS_ATTEMPT', 'Attempt to access dashboard without login');
    header('Location: index.php');
    exit();
}

// Include database connection
require_once 'connect.php';

// Determine which view to show based on URL parameter
$view = isset($_GET['view']) ? $_GET['view'] : 'home';

// Log page access
logPageView('Dashboard - ' . ($view === 'library' ? 'Library View' : 'Home View'));

// Get filter parameters for library view
$search_keyword = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$papers_per_page = 10;
$offset = ($page - 1) * $papers_per_page;

// Initialize variables
$papers = [];
$total_papers = 0;
$total_pages = 0;
$featured_papers = [];
$stats = [];
$categories = [];

if ($view === 'library') {
    // Build WHERE clause for search and filters
    $where_conditions = ["status IN ('approved', 'published')"];
    $params = [];
    $param_types = '';

    if (!empty($search_keyword)) {
        $where_conditions[] = "(paper_title LIKE ? OR keywords LIKE ? OR abstract LIKE ? OR author_name LIKE ? OR co_authors LIKE ?)";
        $search_term = "%$search_keyword%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $param_types .= 'sssss';
    }

    if (!empty($category_filter)) {
        $where_conditions[] = "research_type = ?";
        $params[] = $category_filter;
        $param_types .= 's';
    }

    if (!empty($year_filter)) {
        if ($year_filter === 'older') {
            $where_conditions[] = "YEAR(submission_date) <= 2020";
        } else {
            $where_conditions[] = "YEAR(submission_date) = ?";
            $params[] = $year_filter;
            $param_types .= 'i';
        }
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM paper_submissions WHERE $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $total_papers = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_papers / $papers_per_page);

    // Log search activity
    if (!empty($search_keyword) || !empty($category_filter) || !empty($year_filter)) {
        logSearch($search_keyword, $category_filter, $year_filter, $total_papers);
    }

    // Get papers for current page
    $sql = "SELECT ps.*, 
                   COALESCE(AVG(pr.rating), 0) as avg_rating,
                   COUNT(pr.id) as review_count,
                   COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
                   COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
            FROM paper_submissions ps 
            LEFT JOIN paper_reviews pr ON ps.id = pr.paper_id
            LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
            WHERE $where_clause 
            GROUP BY ps.id 
            ORDER BY ps.submission_date DESC 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $params[] = $papers_per_page;
    $params[] = $offset;
    $param_types .= 'ii';
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $papers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get featured papers (top 3 by views/downloads) - only show when not searching
    if (empty($search_keyword) && empty($category_filter) && empty($year_filter)) {
        $featured_sql = "SELECT ps.*, 
                                COALESCE(AVG(pr.rating), 0) as avg_rating,
                                COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
                                COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
                         FROM paper_submissions ps 
                         LEFT JOIN paper_reviews pr ON ps.id = pr.paper_id
                         LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
                         WHERE ps.status IN ('approved', 'published')
                         GROUP BY ps.id 
                         ORDER BY (COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) + 
                                  COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0)) DESC 
                         LIMIT 3";
        $featured_result = $conn->query($featured_sql);
        $featured_papers = $featured_result->fetch_all(MYSQLI_ASSOC);
    }

    // Get statistics
    $stats_sql = "SELECT 
        (SELECT COUNT(*) FROM paper_submissions WHERE status IN ('approved', 'published')) as total_papers,
        (SELECT COUNT(DISTINCT author_name) FROM paper_submissions WHERE status IN ('approved', 'published')) as total_researchers,
        (SELECT COUNT(DISTINCT research_type) FROM paper_submissions WHERE status IN ('approved', 'published')) as research_categories,
        (SELECT COUNT(*) FROM paper_submissions WHERE status = 'under_review') as active_projects";
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

    // Get category counts
    $category_sql = "SELECT research_type, COUNT(*) as count 
                    FROM paper_submissions 
                    WHERE status IN ('approved', 'published') 
                    GROUP BY research_type 
                    ORDER BY count DESC";
    $category_result = $conn->query($category_sql);
    $categories = $category_result->fetch_all(MYSQLI_ASSOC);
}

// Check if search is active
$is_searching = !empty($search_keyword) || !empty($category_filter) || !empty($year_filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CNLRRS Research Library</title>
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
        <p class="hidden md:block">Email: <a href="mailto:dacnlrrs@gmail.com" class="underline">dacnlrrs@gmail.com</a></p>
        
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
        <form method="GET" action="" class="flex w-full">
          <input type="hidden" name="view" value="library">
          <input type="text" name="search" value="<?php echo htmlspecialchars($search_keyword); ?>" placeholder="Search publications, articles, keywords, etc." class="flex-grow px-4 py-2 text-sm text-gray-700 placeholder-gray-500 focus:outline-none" />
          <button type="submit" class="px-4">
            <img src="Images/Search magni.png" alt="Search" class="h-5" />
          </button>
          <div class="flex items-center ">
           <a href="elibrary_loggedin.php" class="  px-4 text-sm font-semibold text-[#103635] hover:underline">Advanced</a>
          </div>
        </form>
      </div>

      <!-- Links -->
      <div class="flex items-center gap-6 font-bold">
        <a href="?view=home" class="hover:underline <?php echo $view === 'home' ? 'text-[#115D5B] underline' : ''; ?>">Home</a>
        <a href="OurService.php" class="hover:underline">Our Services</a>
        <a href="About.php" class="hover:underline">About Us</a>
      </div>

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

    </div>
  </nav>
        
  <!-- Sidebar Navigation -->
  <div id="mySidebar" class="sidebar">
      <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
      <a href="#">Settings</a>
      <a href="edit_profile.php">Profile</a>
      <a href="my_submissions.php"><i class="fas fa-file-alt mr-2"></i>My Submissions</a>
      <a href="submit_paper.php"><i class="fas fa-plus mr-2"></i>Submit Paper</a>
      <a href="index.php" onclick="logout()">Log Out</a>

  </div>

  <!-- Content Based on View -->
  <?php if ($view === 'home'): ?>
    <!-- HOME VIEW CONTENT -->
    
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
        <p class="font-bold mt-4 text-lg md:text-xl max-w-2xl text-[#103635] text-outline-white">
          Access the latest research and publication about Queen Pineapple Varieties, cultivation, health benefits, and more.
        </p>
        <div class="mt-6 flex gap-4">
          <a href="elibrary_loggedin.php" class="bg-[#1A4D3A] text-white px-6 py-3 rounded-md font-semibold border border-white hover:bg-[#16663F] transition rounded-lg">
            Browse Research
          </a>
          <a href="submit_paper.php" class="bg-[#1A4D3A] border border-white text-white px-6 py-3 rounded-md font-semibold hover:bg-[#16663F] transition rounded-lg">
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

        <a href="User_Guide.php" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
          <div class="bg-gray-200 p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center mb-3">
            <img src="Images/UserG.png" alt="User Guide" class="h-10" />
          </div>
          <h3 class="font-semibold mb-1">User Guide</h3>
          <p class="text-sm text-gray-600">Learn how to find and read articles of your interest.</p>
        </a>

        <a href="elibrary_loggedin.php" class="text-center bg-white rounded-lg p-6 shadow hover:shadow-md transition">
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

    <div class="w-full flex justify-center my-12">
      <img src="Images/Green_holder.png" alt="Section Divider" class="w-full h-30 object-cover" />
    </div>

    <!-- Recommended Research Section -->
    <section class="bg-white py-16 border-t">
      <div class="max-w-7xl mx-auto px-6">
        <h2 class="bg-[#103635] text-2xl md:text-3xl font-bold text-white text-center mb-8">
          Recommended Research By CNLRRS
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          <!-- Card 1 -->
          <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">
            <img src="Images/Books.png" alt="Books & Collection Services" class="h-32 w-full object-cover rounded mb-4" />
            <h3 class="font-semibold text-lg mb-2">Books & Collection Services</h3>
            <p class="text-sm text-gray-700 mb-4">Explore our library collection. Maximize both time and selection with book tools when selecting print books.</p>
            <a href="?view=library" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>
          </div>

          <!-- Card 2 -->
          <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">
            <img src="Images/Soil.png" alt="Pineapple Soil & Growth Solutions" class="h-32 w-full object-cover rounded mb-4" />
            <h3 class="font-semibold text-lg mb-2">Pineapple Soil & Growth Solutions</h3>
            <p class="text-sm text-gray-700 mb-4">Improve your farming knowledge with Pineapple Soil Amendment guides. Learn the needs of pineapple farming.</p>
            <a href="?view=library&category=Soil Science" class="text-[#115D5B] font-semibold hover:underline">Learn more</a>
          </div>

          <!-- Card 3 -->
          <div class="bg-gray-100 rounded-lg shadow p-6 hover:shadow-md transition">
            <img src="Images/Pests.png" alt="Pineapple Pest Resources" class="h-32 w-full object-cover rounded mb-4" />
            <h3 class="font-semibold text-lg mb-2">Pineapple Pest Resources</h3>
            <p class="text-sm text-gray-700 mb-4">Get updated on pineapple pest management. Discover solutions that protect your crops and enhance productivity.</p>
            <a href="?view=library&category=Plant Pathology" class="text-[#115D5B] font-semibold hover:underline">Browse pest solutions</a>
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

    <!-- Quick Search Section for Home -->
    <div class="bg-white rounded-lg p-6 mb-8 shadow-md mx-6">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Search Research</h2>
      <form method="GET" action="" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 mb-8">
        <input type="hidden" name="view" value="library">
        <div class="flex-1">
          <input type="text" name="search" placeholder="Search keywords" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B]" />
        </div>
        <div class="md:w-1/4">
          <select name="category" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
            <option value="">Category</option>
            <option value="Agricultural Research">Agricultural Research</option>
            <option value="Crop Science">Crop Science</option>
            <option value="Soil Science">Soil Science</option>
            <option value="Plant Pathology">Plant Pathology</option>
            <option value="Food Technology">Food Technology</option>
          </select>
        </div>
        <div class="md:w-1/4">
          <select name="year" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
            <option value="">Publication Year</option>
            <option value="2025">2025</option>
            <option value="2024">2024</option>
            <option value="2023">2023</option>
            <option value="2022">2022</option>
            <option value="2021">2021</option>
            <option value="older">2020 & Older</option>
          </select>
        </div>
        <button type="submit" class="bg-[#115D5B] hover:bg-green-600 text-white px-6 py-3 rounded-lg">Search</button>
      </form>

      <!-- Browse Research button -->
      <div class="mt-6 flex justify-center items-center gap-4">
        <a href="elibrary_loggedin.php" class="bg-[#115D5B] text-white px-6 py-3 rounded-md font-semibold border border-white hover:bg-[#16663F] transition rounded-lg">
          Browse Research Library
        </a>
      </div>
    </div>

  <?php else: ?>
    <!-- LIBRARY VIEW CONTENT -->
    
    <!-- Hero Section for Library -->
    <div class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white p-8 mb-8">
      <div class="container mx-auto">
        <div class="flex flex-col md:flex-row items-center justify-between">
          <div class="md:w-2/3 mb-4 md:mb-0">
            <h1 class="text-3xl font-bold mb-2">CNLRRS Queen Pineapple Research Repository</h1>
            <p class="text-gray-200 mb-4">Access the latest research, studies, and publications about Queen Pineapple varieties, cultivation, health benefits, and more.</p>
          </div>
          <div class="md:w-1/3 text-right">
            <div class="bg-white text-[#115D5B] rounded-lg p-4 inline-block">
              <div class="text-2xl font-bold"><?php echo isset($stats['total_papers']) ? $stats['total_papers'] : '0'; ?></div>
              <div class="text-sm">Research Papers</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Advanced Search Section -->
    <div class="container mx-auto p-4">
      <div id="search-section" class="bg-white rounded-lg p-6 mb-8 shadow-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
          <i class="fas fa-search mr-2"></i>Advanced Search
        </h2>
        <form method="GET" action="" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
          <input type="hidden" name="view" value="library">
          <div class="flex-1">
            <input type="text" 
                   name="search" 
                   value="<?php echo htmlspecialchars($search_keyword); ?>" 
                   placeholder="Search by title, keywords, abstract, or author..." 
                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B]" />
          </div>
          <div class="md:w-1/4">
            <select name="category" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B]">
              <option value="">All Categories</option>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                  <option value="<?php echo htmlspecialchars($category['research_type']); ?>" 
                          <?php echo $category_filter === $category['research_type'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['research_type']); ?> (<?php echo $category['count']; ?>)
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
          <div class="md:w-1/4">
            <select name="year" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B]">
              <option value="">All Years</option>
              <option value="2025" <?php echo $year_filter === '2025' ? 'selected' : ''; ?>>2025</option>
              <option value="2024" <?php echo $year_filter === '2024' ? 'selected' : ''; ?>>2024</option>
              <option value="2023" <?php echo $year_filter === '2023' ? 'selected' : ''; ?>>2023</option>
              <option value="2022" <?php echo $year_filter === '2022' ? 'selected' : ''; ?>>2022</option>
              <option value="2021" <?php echo $year_filter === '2021' ? 'selected' : ''; ?>>2021</option>
              <option value="older" <?php echo $year_filter === 'older' ? 'selected' : ''; ?>>2020 & Older</option>
            </select>
          </div>
          <button type="submit" class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg transition-colors">
            <i class="fas fa-search mr-2"></i>Search
          </button>
          <?php if ($is_searching): ?>
          <a href="?view=library" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-colors">
            <i class="fas fa-times mr-2"></i>Clear
          </a>
          <?php endif; ?>
        </form>

        <!-- Search Results Summary -->
        <?php if ($is_searching): ?>
        <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400 rounded-lg">
          <div class="flex items-center">
            <i class="fas fa-info-circle text-blue-400 mr-2"></i>
            <span class="text-blue-800 font-medium">
              Search Results: <?php echo $total_papers; ?> papers found
              <?php if (!empty($search_keyword)): ?>
                for "<?php echo htmlspecialchars($search_keyword); ?>"
              <?php endif; ?>
              <?php if (!empty($category_filter)): ?>
                in <?php echo htmlspecialchars($category_filter); ?>
              <?php endif; ?>
              <?php if (!empty($year_filter)): ?>
                from <?php echo $year_filter === 'older' ? '2020 & older' : $year_filter; ?>
              <?php endif; ?>
            </span>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Search Results (Papers) -->
      <?php if ($is_searching || (!empty($papers) && $is_searching)): ?>
      <div class="bg-white rounded-lg p-6 mb-8 shadow-md">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-file-alt mr-2"></i>
            <?php echo $is_searching ? 'Search Results' : 'All Research Papers'; ?>
          </h2>
          <div class="text-sm text-gray-600">
            <?php if ($total_papers > 0): ?>
              Showing <?php echo (($page - 1) * $papers_per_page) + 1; ?> - <?php echo min($page * $papers_per_page, $total_papers); ?> of <?php echo $total_papers; ?> papers
            <?php endif; ?>
          </div>
        </div>

        <?php if (empty($papers)): ?>
          <div class="text-center py-12">
            <i class="fas fa-search text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Papers Found</h3>
            <p class="text-gray-500 mb-4">Try adjusting your search criteria or browse all papers.</p>
            <a href="?view=library" class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg">
              View All Papers
            </a>
          </div>
        <?php else: ?>
          <div class="space-y-6">
            <?php foreach ($papers as $index => $paper): ?>
            <div class="border-b border-gray-200 pb-6 <?php echo $index === count($papers) - 1 ? 'border-b-0 pb-0' : ''; ?>">
              <div class="flex justify-between items-start mb-3">
                <h3 class="text-xl font-semibold text-[#115D5B] mb-2 flex-1">
                  <?php echo htmlspecialchars($paper['paper_title']); ?>
                </h3>
                <div class="ml-4">
                  <span class="px-3 py-1 text-xs rounded-full <?php echo $paper['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                    <?php echo ucfirst($paper['status']); ?>
                  </span>
                </div>
              </div>
              
              <div class="flex flex-wrap items-center space-x-4 text-sm text-gray-600 mb-3">
                <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($paper['author_name']); ?>
                <?php if ($paper['co_authors']): ?>, <?php echo htmlspecialchars($paper['co_authors']); ?><?php endif; ?></span>
                <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($paper['submission_date'])); ?></span>
                <span><i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($paper['research_type']); ?></span>
              </div>
              
              <p class="text-gray-700 mb-4 leading-relaxed">
                <?php echo htmlspecialchars(substr($paper['abstract'], 0, 300)) . '...'; ?>
              </p>
              
              <div class="flex flex-wrap items-center justify-between">
                <div class="flex items-center space-x-4 mb-2">
                  <span class="text-sm text-gray-500">
                    <i class="fas fa-eye text-blue-500"></i> <?php echo $paper['total_views']; ?> views
                  </span>
                  <span class="text-sm text-gray-500">
                    <i class="fas fa-download text-green-500"></i> <?php echo $paper['total_downloads']; ?> downloads
                  </span>
                  <?php if ($paper['avg_rating'] > 0): ?>
                  <span class="text-sm text-gray-500">
                    <i class="fas fa-star text-yellow-500"></i> <?php echo number_format($paper['avg_rating'], 1); ?>/5 
                    (<?php echo $paper['review_count']; ?> reviews)
                  </span>
                  <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-3">
                  <button onclick="showAbstract(<?php echo $paper['id']; ?>)" 
                          class="text-blue-600 hover:text-blue-800 font-medium text-sm px-3 py-1 rounded-md hover:bg-blue-50 transition-colors">
                    <i class="fas fa-eye mr-1"></i>Abstract
                  </button>
                  <?php if ($paper['file_path'] && file_exists($paper['file_path'])): ?>
                  <a href="download_paper.php?id=<?php echo $paper['id']; ?>" 
                     class="text-green-600 hover:text-green-800 font-medium text-sm px-3 py-1 rounded-md hover:bg-green-50 transition-colors">
                    <i class="fas fa-download mr-1"></i>Download
                  </a>
                  <?php endif; ?>
                  <button class="text-gray-600 hover:text-gray-800 font-medium text-sm px-3 py-1 rounded-md hover:bg-gray-50 transition-colors">
                    <i class="fas fa-quote-right mr-1"></i>Cite
                  </button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <div class="mt-8 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
            <div class="text-gray-600 text-sm">
              Showing <?php echo (($page - 1) * $papers_per_page) + 1; ?> - <?php echo min($page * $papers_per_page, $total_papers); ?> of <?php echo $total_papers; ?> papers
            </div>
            <div class="flex flex-wrap justify-center space-x-1">
              <?php if ($page > 1): ?>
              <a href="?view=library&page=1&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                 class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm">
                <i class="fas fa-angle-double-left"></i>
              </a>
              <a href="?view=library&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                 class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm">
                <i class="fas fa-angle-left"></i> Prev
              </a>
              <?php endif; ?>
              
              <?php 
              $start_page = max(1, $page - 2);
              $end_page = min($total_pages, $page + 2);
              for ($i = $start_page; $i <= $end_page; $i++): 
              ?>
              <a href="?view=library&page=<?php echo $i; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                 class="<?php echo $i === $page ? 'bg-[#115D5B] text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'; ?> px-3 py-2 rounded-lg text-sm">
                <?php echo $i; ?>
              </a>
              <?php endfor; ?>
              
              <?php if ($page < $total_pages): ?>
              <a href="?view=library&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                 class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-3 py-2 rounded-lg text-sm">
                Next <i class="fas fa-angle-right"></i>
              </a>
              <a href="?view=library&page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                 class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm">
                <i class="fas fa-angle-double-right"></i>
              </a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Show other sections only when not searching -->
      <?php if (!$is_searching): ?>

      <!-- Research Categories -->
      <?php if (!empty($categories)): ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php
        // Define category icons
        $category_icons = [
            'Agricultural Research' => 'fas fa-seedling',
            'Crop Science' => 'fas fa-leaf',
            'Soil Science' => 'fas fa-mountain',
            'Plant Pathology' => 'fas fa-bug',
            'Entomology' => 'fas fa-spider',
            'Food Technology' => 'fas fa-apple-alt',
            'Sustainable Agriculture' => 'fas fa-recycle',
            'Climate Change Agriculture' => 'fas fa-thermometer-half'
        ];
        
        // Display top 3 categories
        $displayed = 0;
        foreach ($categories as $category):
            if ($displayed >= 3) break;
            $icon = isset($category_icons[$category['research_type']]) ? $category_icons[$category['research_type']] : 'fas fa-file-alt';
        ?>
        <div class="bg-white rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
          <div class="text-4xl text-[#115D5B] mb-4"><i class="<?php echo $icon; ?>"></i></div>
          <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($category['research_type']); ?></h3>
          <p class="text-gray-600 mb-4">Research papers in <?php echo strtolower(htmlspecialchars($category['research_type'])); ?> from our repository.</p>
          <a href="?view=library&category=<?php echo urlencode($category['research_type']); ?>" class="text-[#115D5B] hover:text-[#0e4e4c] font-semibold">View <?php echo $category['count']; ?> Papers →</a>
        </div>
        <?php 
            $displayed++;
        endforeach; 
        ?>
      </div>
      <?php endif; ?>

      <!-- Featured Research -->
      <?php if (!empty($featured_papers)): ?>
      <div class="bg-white rounded-lg p-6 mb-8 shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Featured Research</h2>
        <div class="space-y-6">
          <?php foreach ($featured_papers as $index => $paper): ?>
          <div class="<?php echo $index < count($featured_papers) - 1 ? 'border-b border-gray-200 pb-6' : ''; ?>">
            <h3 class="text-xl font-semibold text-[#115D5B] mb-2"><?php echo htmlspecialchars($paper['paper_title']); ?></h3>
            <div class="flex items-center space-x-2 text-gray-600 mb-2">
              <span>Authors: <?php echo htmlspecialchars($paper['author_name']); ?><?php if ($paper['co_authors']): ?>, <?php echo htmlspecialchars($paper['co_authors']); ?><?php endif; ?></span>
              <span>•</span>
              <span><?php echo date('Y', strtotime($paper['submission_date'])); ?></span>
              <span>•</span>
              <span><?php echo htmlspecialchars($paper['research_type']); ?></span>
            </div>
            <p class="text-gray-700 mb-3"><?php echo htmlspecialchars(substr($paper['abstract'], 0, 200)) . '...'; ?></p>
            <div class="flex items-center space-x-4 mb-2">
              <span class="text-sm text-gray-500">
                <i class="fas fa-eye"></i> <?php echo $paper['total_views']; ?> views
              </span>
              <span class="text-sm text-gray-500">
                <i class="fas fa-download"></i> <?php echo $paper['total_downloads']; ?> downloads
              </span>
              <?php if ($paper['avg_rating'] > 0): ?>
              <span class="text-sm text-gray-500">
                <i class="fas fa-star text-yellow-500"></i> <?php echo number_format($paper['avg_rating'], 1); ?>/5
              </span>
              <?php endif; ?>
            </div>
            <div class="flex items-center space-x-4">
              <button onclick="showAbstract(<?php echo $paper['id']; ?>)" class="text-blue-600 hover:text-blue-800 font-medium">Abstract</button>
              <?php if ($paper['file_path'] && file_exists($paper['file_path'])): ?>
              <a href="download_paper.php?id=<?php echo $paper['id']; ?>" class="text-green-600 hover:text-green-800 font-medium">Download PDF</a>
              <?php endif; ?>
              <button class="text-gray-600 hover:text-gray-800 font-medium">Cite</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Statistics Section -->
      <?php if (!empty($stats)): ?>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
          <div class="text-4xl font-bold text-[#115D5B] mb-2"><?php echo $stats['total_papers']; ?></div>
          <p class="text-gray-700">Research Papers</p>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
          <div class="text-4xl font-bold text-[#115D5B] mb-2"><?php echo $stats['total_researchers']; ?></div>
          <p class="text-gray-700">Researchers</p>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
          <div class="text-4xl font-bold text-[#115D5B] mb-2"><?php echo $stats['research_categories']; ?></div>
          <p class="text-gray-700">Research Categories</p>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
          <div class="text-4xl font-bold text-[#115D5B] mb-2"><?php echo $stats['active_projects']; ?></div>
          <p class="text-gray-700">Under Review</p>
        </div>
      </div>
      <?php endif; ?>

      <?php endif; // End of non-searching sections ?>
    </div>

  <?php endif; ?>

</main>

<!-- Footer -->
<footer class="bg-[#115D5B] text-white text-center py-4">
  <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
</footer>

<!-- Modal for Abstract Display -->
<div id="abstractModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800">Research Abstract</h3>
        <button onclick="closeAbstract()" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      <div id="abstractContent" class="text-gray-700">
        <!-- Abstract content will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script>
  // Logout function
  function logout() {
      // Clear the session by making an AJAX call to end the session
      fetch('logout.php', {
          method: 'POST'
      }).then(() => {
          // Redirect to index.php
          window.location.href = 'index.php';
      });
  }

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

  // Close sidebar when clicking outside
  document.addEventListener('click', function(event) {
      const sidebar = document.getElementById('mySidebar');
      const profilePic = event.target.closest('.profile-pic');
      
      if (!sidebar.contains(event.target) && !profilePic && sidebar.style.width === '250px') {
          closeSidebar();
      }
  });

  // Abstract modal functions
  function showAbstract(paperId) {
      document.getElementById('abstractContent').innerHTML = '<p>Loading abstract...</p>';
      document.getElementById('abstractModal').classList.remove('hidden');
      
      // Here you would make an AJAX call to fetch the full abstract
      fetch(`get_abstract.php?id=${paperId}`)
         .then(response => response.json())
          .then(data => {
              document.getElementById('abstractContent').innerHTML = `
                 <h4 class="font-semibold mb-2">${data.title}</h4>
                  <p class="text-sm text-gray-600 mb-3">Authors: ${data.authors}</p>
                  <p class="leading-relaxed">${data.abstract}</p>
               `;
           })
           .catch(error => {
               document.getElementById('abstractContent').innerHTML = '<p>Error loading abstract. Please try again.</p>';
           });
  }

  function closeAbstract() {
      document.getElementById('abstractModal').classList.add('hidden');
  }

  // Close modal when clicking outside
  document.getElementById('abstractModal').addEventListener('click', function(e) {
      if (e.target === this) {
          closeAbstract();
      }
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
              target.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
              });
          }
      });
  });

  // Auto-submit search form on Enter key
  const searchInputs = document.querySelectorAll('input[name="search"]');
  searchInputs.forEach(input => {
      input.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
              e.preventDefault();
              this.form.submit();
          }
      });
  });

   function setViewport() {
            // Check if device is mobile
            function isMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) 
                    || window.innerWidth <= 768 
                    || ('ontouchstart' in window);
            }
            
            // Get or create viewport meta tag
            let viewport = document.querySelector('meta[name="viewport"]');
            if (!viewport) {
                viewport = document.createElement('meta');
                viewport.name = 'viewport';
                document.head.appendChild(viewport);
            }
            
            // Set content based on device type
            if (isMobile()) {
                viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes';
            } else {
                viewport.content = 'width=device-width, initial-scale=1.5, maximum-scale=5.0, user-scalable=yes';
            }
        }
        
        // Run immediately
        setViewport();
        
        // Also run on resize (in case device orientation changes)
        window.addEventListener('resize', setViewport);
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
  }

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

  @media (min-width: 475px) {
  .xs\:inline {
    display: inline;
  }
}

/* Alternative mobile-first approach for very small screens */
@media (max-width: 374px) {
  .profile-pic {
    width: 2rem;
    height: 2rem;
  }
  
  .text-xs-mobile {
    font-size: 0.75rem;
  }
}
</style>
</body>
</html>