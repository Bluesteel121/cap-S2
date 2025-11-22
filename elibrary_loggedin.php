<?php
// Include database connection
require_once 'connect.php';

// Start session and check if user is logged in
session_start();

// If not logged in, redirect to public version
if (!isset($_SESSION['id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: elibrary.php");
    exit();
}

// Get user information
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

// Get filter parameters
$search_keyword = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$papers_per_page = 10;
$offset = ($page - 1) * $papers_per_page;

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

// Get papers for current page
$sql = "SELECT ps.*, 
               COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
               COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
        FROM paper_submissions ps 
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
$featured_papers = [];
if (empty($search_keyword) && empty($category_filter) && empty($year_filter)) {
    $featured_sql = "SELECT ps.*, 
                            COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
                            COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
                     FROM paper_submissions ps 
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

// Get user's submission count
$user_submissions_sql = "SELECT COUNT(*) as count FROM paper_submissions WHERE user_name = ?";
$user_submissions_stmt = $conn->prepare($user_submissions_sql);
$user_submissions_stmt->bind_param('s', $username);
$user_submissions_stmt->execute();
$user_submissions_count = $user_submissions_stmt->get_result()->fetch_assoc()['count'];

// Check if search is active
$is_searching = !empty($search_keyword) || !empty($category_filter) || !empty($year_filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queen Pineapple Research E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Mobile Navigation Bar -->
    <nav class="bg-[#115D5B] text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <!-- Desktop Navigation -->
            <div class="hidden lg:flex justify-between items-center">
                <div class="flex items-center">
                    <img src="Images/CNLRRS_icon.png" alt="CNLRRS Logo" class="h-10 w-10 mr-2">
                    <span class="text-xl font-bold">CNLRRS Rainfed Research Station</span>
                </div>
                <div class="space-x-4">
                    <a href="loggedin_index.php" class="hover:underline">Home</a>
                    <a href="elibrary_loggedin.php" class="hover:underline font-semibold">E-Library</a>
                    <a href="About.php" class="hover:underline">About Us</a>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <button class="flex items-center space-x-2 bg-[#103635] px-4 py-2 rounded-xl">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($user_name); ?></span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 absolute right-0 mt-0 pt-2 w-48 z-50">
                            <div class="bg-white rounded-lg shadow-xl">
                                <a href="edit_profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-t-lg">
                                    <i class="fas fa-user-circle mr-2"></i>My Profile
                                </a>
                                <a href="my_submissions.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-alt mr-2"></i>My Submissions (<?php echo $user_submissions_count; ?>)
                                </a>
                                <a href="submit_paper.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-upload mr-2"></i>Submit Paper
                                </a>
                               
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div class="lg:hidden">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <img src="Images/CNLRRS_icon.png" alt="CNLRRS Logo" class="h-8 w-8 mr-2">
                        <span class="text-sm font-bold">CNLRRS</span>
                    </div>
                    <button id="mobile-menu-btn" class="text-white p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                
                <!-- Mobile Menu -->
                <div id="mobile-menu" class="hidden mt-4 pb-4">
                    <div class="space-y-2">
                        <a href="loggedin_index.php" class="block py-2 px-4 hover:bg-[#103635] rounded">Home</a>
                        <a href="elibrary_loggedin.php" class="block py-2 px-4 bg-[#103635] rounded font-semibold">E-Library</a>
                        <a href="About.php" class="block py-2 px-4 hover:bg-[#103635] rounded">About Us</a>

                        <hr class="border-[#103635]">
                        <a href="edit_profile.php" class="block py-2 px-4 hover:bg-[#103635] rounded">
                            <i class="fas fa-user-circle mr-2"></i>My Profile
                        </a>
                        <a href="my_submissions.php" class="block py-2 px-4 hover:bg-[#103635] rounded">
                            <i class="fas fa-file-alt mr-2"></i>My Submissions (<?php echo $user_submissions_count; ?>)
                        </a>
                        <a href="submit_paper.php" class="block py-2 px-4 hover:bg-[#103635] rounded">
                            <i class="fas fa-upload mr-2"></i>Submit Paper
                        </a>
                       
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-4 lg:p-4">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-[#115D5B] to-[#1A4D3A] text-white rounded-lg p-4 lg:p-6 mb-6 lg:mb-8 shadow-md">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center space-y-4 lg:space-y-0">
                <div class="w-full lg:w-auto">
                    <h1 class="text-xl lg:text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p class="text-sm lg:text-base text-gray-200">Explore the latest research or submit your own work.</p>
                </div>
                <div class="w-full lg:w-auto text-left lg:text-right">
                    <div class="text-2xl lg:text-3xl font-bold"><?php echo $user_submissions_count; ?></div>
                    <div class="text-xs lg:text-sm text-gray-200">Your Submissions</div>
                    <a href="submit_paper.php" class="mt-2 inline-block bg-white text-[#115D5B] px-3 py-2 lg:px-4 rounded-lg text-xs lg:text-sm font-semibold hover:bg-gray-100 transition">
                        <i class="fas fa-plus mr-1"></i>Submit New Paper
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div id="search-section" class="bg-white rounded-lg p-4 lg:p-6 mb-6 lg:mb-8 shadow-md">
            <h2 class="text-lg lg:text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-search mr-2"></i>Advanced Search
            </h2>
            <form method="GET" action="elibrary_loggedin.php" class="space-y-3 lg:space-y-0 lg:flex lg:space-x-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search_keyword); ?>" 
                           placeholder="Search by title, keywords, author..." 
                           class="w-full p-2 lg:p-3 text-sm lg:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B]" />
                </div>
                <div class="w-full lg:w-1/4">
                    <select name="category" class="w-full p-2 lg:p-3 text-sm lg:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B]">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['research_type']); ?>" 
                                    <?php echo $category_filter === $category['research_type'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($category['research_type'])); ?> (<?php echo $category['count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-full lg:w-1/4">
                    <select name="year" class="w-full p-2 lg:p-3 text-sm lg:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B]">
                        <option value="">All Years</option>
                        <option value="2025" <?php echo $year_filter === '2025' ? 'selected' : ''; ?>>2025</option>
                        <option value="2024" <?php echo $year_filter === '2024' ? 'selected' : ''; ?>>2024</option>
                        <option value="2023" <?php echo $year_filter === '2023' ? 'selected' : ''; ?>>2023</option>
                        <option value="2022" <?php echo $year_filter === '2022' ? 'selected' : ''; ?>>2022</option>
                        <option value="2021" <?php echo $year_filter === '2021' ? 'selected' : ''; ?>>2021</option>
                        <option value="older" <?php echo $year_filter === 'older' ? 'selected' : ''; ?>>2020 & Older</option>
                    </select>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="flex-1 lg:flex-none bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-4 lg:px-6 py-2 lg:py-3 rounded-lg text-sm lg:text-base transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if ($is_searching): ?>
                    <a href="elibrary_loggedin.php" class="flex-1 lg:flex-none bg-gray-500 hover:bg-gray-600 text-white px-4 lg:px-6 py-2 lg:py-3 rounded-lg text-sm lg:text-base transition-colors text-center">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Search Results Summary -->
            <?php if ($is_searching): ?>
            <div class="mt-4 p-3 lg:p-4 bg-blue-50 border-l-4 border-blue-400 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-400 mr-2 mt-1"></i>
                    <span class="text-xs lg:text-sm text-blue-800 font-medium">
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

        <!-- Papers Section -->
        <div class="bg-white rounded-lg p-4 lg:p-6 mb-6 lg:mb-8 shadow-md">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-4 lg:mb-6 space-y-2 lg:space-y-0">
                <h2 class="text-xl lg:text-2xl font-bold text-gray-800">
                    <i class="fas fa-file-alt mr-2"></i>
                    <?php echo $is_searching ? 'Search Results' : 'Research Papers'; ?>
                </h2>
                <div class="text-xs lg:text-sm text-gray-600">
                    <?php if ($total_papers > 0): ?>
                        Showing <?php echo (($page - 1) * $papers_per_page) + 1; ?> - <?php echo min($page * $papers_per_page, $total_papers); ?> of <?php echo $total_papers; ?> papers
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($papers)): ?>
                <div class="text-center py-8 lg:py-12">
                    <i class="fas fa-search text-gray-300 text-4xl lg:text-6xl mb-4"></i>
                    <h3 class="text-lg lg:text-xl font-semibold text-gray-600 mb-2">No Papers Found</h3>
                    <p class="text-sm lg:text-base text-gray-500 mb-4">Try adjusting your search criteria or browse all papers.</p>
                    <a href="elibrary_loggedin.php" class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-4 lg:px-6 py-2 lg:py-3 rounded-lg text-sm lg:text-base">
                        View All Papers
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4 lg:space-y-6">
                    <?php foreach ($papers as $index => $paper): ?>
                    <div class="border-b border-gray-200 pb-4 lg:pb-6 <?php echo $index === count($papers) - 1 ? 'border-b-0 pb-0' : ''; ?>">
                        <div class="flex flex-col lg:flex-row justify-between items-start mb-3 space-y-2 lg:space-y-0">
                            <h3 class="text-base lg:text-xl font-semibold text-[#115D5B] flex-1">
                                <a href="research_details.php?id=<?php echo $paper['id']; ?>" 
                                   class="hover:text-[#0e4e4c] hover:underline transition-colors cursor-pointer block">
                                    <?php echo htmlspecialchars($paper['paper_title']); ?>
                                </a>
                            </h3>
                            <div class="lg:ml-4">
                                <span class="px-2 lg:px-3 py-1 text-xs rounded-full <?php echo $paper['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst($paper['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-2 lg:gap-4 text-xs lg:text-sm text-gray-600 mb-3">
                            <span class="flex items-center">
                                <i class="fas fa-user mr-1"></i>
                                <span class="truncate max-w-[150px] lg:max-w-none"><?php echo htmlspecialchars($paper['author_name']); ?>
                                <?php if ($paper['co_authors']): ?>, <?php echo htmlspecialchars($paper['co_authors']); ?><?php endif; ?></span>
                            </span>
                            <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($paper['submission_date'])); ?></span>
                            <span><i class="fas fa-tag mr-1"></i><?php echo ucfirst(htmlspecialchars($paper['research_type'])); ?></span>
                            <?php if (!empty($paper['affiliation'])): ?>
                            <span class="hidden lg:inline"><i class="fas fa-university mr-1"></i><?php echo htmlspecialchars($paper['affiliation']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-sm lg:text-base text-gray-700 mb-4 leading-relaxed">
                            <?php echo htmlspecialchars(substr($paper['abstract'], 0, 200)) . '...'; ?>
                        </p>
                        
                        <?php if (!empty($paper['keywords'])): ?>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php 
                            $keywords = explode(',', $paper['keywords']);
                            foreach (array_slice($keywords, 0, 3) as $keyword): 
                            ?>
                            <span class="bg-gray-100 text-gray-700 text-xs px-2 lg:px-3 py-1 rounded-full">
                                <?php echo htmlspecialchars(trim($keyword)); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between space-y-3 lg:space-y-0">
                            <div class="flex items-center space-x-3 lg:space-x-4">
                                <span class="text-xs lg:text-sm text-gray-500">
                                    <i class="fas fa-eye text-blue-500"></i> <?php echo $paper['total_views']; ?>
                                </span>
                                <span class="text-xs lg:text-sm text-gray-500">
                                    <i class="fas fa-download text-green-500"></i> <?php echo $paper['total_downloads']; ?>
                                </span>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="research_details.php?id=<?php echo $paper['id']; ?>" 
                                   class="text-[#115D5B] hover:text-[#0e4e4c] font-medium text-xs lg:text-sm px-2 lg:px-3 py-1 rounded-md hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-info-circle mr-1"></i>Details
                                </a>
                                <button onclick="showAbstract(<?php echo $paper['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-xs lg:text-sm px-2 lg:px-3 py-1 rounded-md hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>Abstract
                                </button>
                                <?php if ($paper['file_path'] && file_exists($paper['file_path'])): ?>
                                <a href="paper_viewer.php?id=<?php echo $paper['id']; ?>" 
                                   target="_blank"
                                   class="text-purple-600 hover:text-purple-800 font-medium text-xs lg:text-sm px-2 lg:px-3 py-1 rounded-md hover:bg-purple-50 transition-colors">
                                    <i class="fas fa-eye mr-1"></i><span class="hidden lg:inline">View</span>
                                </a>
                                <a href="download_paper.php?id=<?php echo $paper['id']; ?>" 
                                   class="text-green-600 hover:text-green-800 font-medium text-xs lg:text-sm px-2 lg:px-3 py-1 rounded-md hover:bg-green-50 transition-colors">
                                    <i class="fas fa-download mr-1"></i><span class="hidden lg:inline">Download</span>
                                </a>
                                <?php endif; ?>
                                <button onclick="showCitation(<?php echo $paper['id']; ?>)" 
                                        class="text-gray-600 hover:text-gray-800 font-medium text-xs lg:text-sm px-2 lg:px-3 py-1 rounded-md hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-quote-right mr-1"></i><span class="hidden lg:inline">Cite</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-6 lg:mt-8 flex flex-col space-y-3 lg:space-y-0 lg:flex-row justify-between items-center">
                    <div class="text-gray-600 text-xs lg:text-sm">
                        Showing <?php echo (($page - 1) * $papers_per_page) + 1; ?> - <?php echo min($page * $papers_per_page, $total_papers); ?> of <?php echo $total_papers; ?> papers
                    </div>
                    <div class="flex flex-wrap justify-center gap-1 lg:gap-2">
                        <?php if ($page > 1): ?>
                        <a href="elibrary_loggedin.php?page=1&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 lg:px-3 py-1 lg:py-2 rounded-lg text-xs lg:text-sm">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="elibrary_loggedin.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 lg:px-3 py-1 lg:py-2 rounded-lg text-xs lg:text-sm">
                            <i class="fas fa-angle-left"></i><span class="hidden lg:inline"> Prev</span>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        // Show fewer pages on mobile
                        if ($total_pages > 5) {
                            $start_page = max(1, $page - 1);
                            $end_page = min($total_pages, $page + 1);
                        }
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                        <a href="elibrary_loggedin.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="<?php echo $i === $page ? 'bg-[#115D5B] text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'; ?> px-2 lg:px-3 py-1 lg:py-2 rounded-lg text-xs lg:text-sm">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="elibrary_loggedin.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-2 lg:px-3 py-1 lg:py-2 rounded-lg text-xs lg:text-sm">
                            <span class="hidden lg:inline">Next </span><i class="fas fa-angle-right"></i>
                        </a>
                        <a href="elibrary_loggedin.php?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 lg:px-3 py-1 lg:py-2 rounded-lg text-xs lg:text-sm">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Show other sections only when not searching -->
        <?php if (!$is_searching): ?>

        <!-- Research Categories -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-6 lg:mb-8">
            <?php
            $category_icons = [
                'experimental' => 'fas fa-flask',
                'observational' => 'fas fa-eye',
                'review' => 'fas fa-book',
                'case_study' => 'fas fa-file-alt',
                'other' => 'fas fa-seedling'
            ];
            
            $displayed = 0;
            foreach ($categories as $category):
                if ($displayed >= 3) break;
                $icon = isset($category_icons[$category['research_type']]) ? $category_icons[$category['research_type']] : 'fas fa-file-alt';
            ?>
            <div class="bg-white rounded-lg p-4 lg:p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-3xl lg:text-4xl text-[#115D5B] mb-3 lg:mb-4"><i class="<?php echo $icon; ?>"></i></div>
                <h3 class="text-lg lg:text-xl font-bold text-gray-800 mb-2"><?php echo ucfirst(htmlspecialchars($category['research_type'])); ?></h3>
                <p class="text-sm lg:text-base text-gray-600 mb-3 lg:mb-4">Research papers in <?php echo strtolower(htmlspecialchars($category['research_type'])); ?> from our repository.</p>
                <a href="elibrary_loggedin.php?category=<?php echo urlencode($category['research_type']); ?>" class="text-sm lg:text-base text-[#115D5B] hover:text-[#0e4e4c] font-semibold">View <?php echo $category['count']; ?> Papers →</a>
            </div>
            <?php 
                $displayed++;
            endforeach; 
            ?>
        </div>

        <!-- Featured Research -->
        <?php if (!empty($featured_papers)): ?>
        <div class="bg-white rounded-lg p-4 lg:p-6 mb-6 lg:mb-8 shadow-md">
            <h2 class="text-xl lg:text-2xl font-bold text-gray-800 mb-4 lg:mb-6">
                <i class="fas fa-star text-yellow-500 mr-2"></i>Featured Research
            </h2>
            <div class="space-y-4 lg:space-y-6">
                <?php foreach ($featured_papers as $index => $paper): ?>
                <div class="<?php echo $index < count($featured_papers) - 1 ? 'border-b border-gray-200 pb-4 lg:pb-6' : ''; ?>">
                    <h3 class="text-base lg:text-xl font-semibold text-[#115D5B] mb-2">
                        <a href="research_details.php?id=<?php echo $paper['id']; ?>" 
                           class="hover:text-[#0e4e4c] hover:underline transition-colors">
                            <?php echo htmlspecialchars($paper['paper_title']); ?>
                        </a>
                    </h3>
                    <div class="flex flex-wrap items-center gap-2 text-xs lg:text-sm text-gray-600 mb-2">
                        <span class="truncate max-w-[200px] lg:max-w-none">Authors: <?php echo htmlspecialchars($paper['author_name']); ?><?php if ($paper['co_authors']): ?>, <?php echo htmlspecialchars($paper['co_authors']); ?><?php endif; ?></span>
                        <span>•</span>
                        <span><?php echo date('Y', strtotime($paper['submission_date'])); ?></span>
                        <span>•</span>
                        <span><?php echo ucfirst(htmlspecialchars($paper['research_type'])); ?></span>
                    </div>
                    <p class="text-sm lg:text-base text-gray-700 mb-3"><?php echo htmlspecialchars(substr($paper['abstract'], 0, 150)) . '...'; ?></p>
                    <div class="flex items-center space-x-3 lg:space-x-4 mb-2">
                        <span class="text-xs lg:text-sm text-gray-500">
                            <i class="fas fa-eye"></i> <?php echo $paper['total_views']; ?>
                        </span>
                        <span class="text-xs lg:text-sm text-gray-500">
                            <i class="fas fa-download"></i> <?php echo $paper['total_downloads']; ?>
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="research_details.php?id=<?php echo $paper['id']; ?>" class="text-[#115D5B] hover:text-[#0e4e4c] font-medium text-xs lg:text-sm">View Details</a>
                        <button onclick="showAbstract(<?php echo $paper['id']; ?>)" class="text-blue-600 hover:text-blue-800 font-medium text-xs lg:text-sm">Abstract</button>
                        <?php if ($paper['file_path'] && file_exists($paper['file_path'])): ?>
                        <a href="paper_viewer.php?id=<?php echo $paper['id']; ?>" target="_blank" class="text-purple-600 hover:text-purple-800 font-medium text-xs lg:text-sm"><span class="lg:hidden">View</span><span class="hidden lg:inline">View PDF</span></a>
                        <a href="download_paper.php?id=<?php echo $paper['id']; ?>" class="text-green-600 hover:text-green-800 font-medium text-xs lg:text-sm">Download</a>
                        <?php endif; ?>
                        <button onclick="showCitation(<?php echo $paper['id']; ?>)" class="text-gray-600 hover:text-gray-800 font-medium text-xs lg:text-sm">Cite</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6 lg:mb-8">
            <div class="bg-white rounded-lg p-4 lg:p-6 shadow-md text-center">
                <div class="text-2xl lg:text-4xl font-bold text-[#115D5B] mb-1 lg:mb-2"><?php echo $stats['total_papers']; ?></div>
                <p class="text-xs lg:text-base text-gray-700">Research Papers</p>
            </div>
            <div class="bg-white rounded-lg p-4 lg:p-6 shadow-md text-center">
                <div class="text-2xl lg:text-4xl font-bold text-[#115D5B] mb-1 lg:mb-2"><?php echo $stats['total_researchers']; ?></div>
                <p class="text-xs lg:text-base text-gray-700">Researchers</p>
            </div>
            <div class="bg-white rounded-lg p-4 lg:p-6 shadow-md text-center">
                <div class="text-2xl lg:text-4xl font-bold text-[#115D5B] mb-1 lg:mb-2"><?php echo $stats['research_categories']; ?></div>
                <p class="text-xs lg:text-base text-gray-700">Categories</p>
            </div>
            <div class="bg-white rounded-lg p-4 lg:p-6 shadow-md text-center">
                <div class="text-2xl lg:text-4xl font-bold text-[#115D5B] mb-1 lg:mb-2"><?php echo $stats['active_projects']; ?></div>
                <p class="text-xs lg:text-base text-gray-700">Under Review</p>
            </div>
        </div>

        <?php endif; // End of non-searching sections ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-6 lg:p-8">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                <div>
                    <h3 class="text-lg lg:text-xl font-bold mb-3 lg:mb-4">Queen Pineapple Research E-Library</h3>
                    <p class="text-sm lg:text-base text-gray-400">A comprehensive repository of research papers and studies focused on Queen Pineapple varieties.</p>
                </div>
                <div>
                    <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm lg:text-base">
                        <li><a href="loggedin_index.php" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#search-section" class="text-gray-400 hover:text-white">Browse Research</a></li>
                        <li><a href="submit_paper.php" class="text-gray-400 hover:text-white">Submit Paper</a></li>
                        <li><a href="my_submissions.php" class="text-gray-400 hover:text-white">My Submissions</a></li>
                        <li><a href="user_profile.php" class="text-gray-400 hover:text-white">My Profile</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Research Categories</h3>
                    <ul class="space-y-2 text-sm lg:text-base">
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <li><a href="elibrary_loggedin.php?category=<?php echo urlencode($category['research_type']); ?>" class="text-gray-400 hover:text-white"><?php echo ucfirst(htmlspecialchars($category['research_type'])); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Connect With Us</h3>
                    <div class="flex space-x-4 mb-4">
                        <a href="#" class="text-gray-400 hover:text-white text-xl lg:text-2xl"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-xl lg:text-2xl"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-xl lg:text-2xl"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-xl lg:text-2xl"><i class="fab fa-instagram"></i></a>
                    </div>
                    <p class="text-xs lg:text-sm text-gray-400 mb-2">Subscribe to our newsletter for updates.</p>
                    <div class="flex">
                        <input type="email" placeholder="Your email" class="p-2 rounded-l-lg w-full focus:outline-none text-black text-sm" />
                        <button class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white p-2 rounded-r-lg text-sm">Subscribe</button>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-6 lg:mt-8 pt-6 lg:pt-8 text-center">
                <p class="text-xs lg:text-sm text-gray-400">&copy; 2025 CNLRRS Queen Pineapple Research E-Library. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modal for Abstract Display -->
    <div id="abstractModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-4 lg:p-6 max-w-4xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="flex justify-between items-center mb-4 lg:mb-6">
                    <h3 class="text-base lg:text-xl font-semibold text-gray-800">
                        <i class="fas fa-file-alt mr-2"></i>Research Abstract & Details
                    </h3>
                    <button onclick="closeAbstract()" 
                            class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-lg lg:text-xl"></i>
                    </button>
                </div>
                <div id="abstractContent" class="text-sm lg:text-base text-gray-700">
                    <!-- Abstract content will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Citation Display -->
    <div id="citationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-4 lg:p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="flex justify-between items-center mb-4 lg:mb-6">
                    <h3 class="text-base lg:text-xl font-semibold text-gray-800">
                        <i class="fas fa-quote-right mr-2"></i>Citation Formats
                    </h3>
                    <button onclick="closeCitation()" 
                            class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-lg lg:text-xl"></i>
                    </button>
                </div>
                <div id="citationContent" class="text-sm lg:text-base text-gray-700">
                    <!-- Citation content will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
// Mobile menu toggle
document.getElementById('mobile-menu-btn').addEventListener('click', function() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
});

function trackPaperView(paperId) {
    // Send async request to track view
    fetch(`track_paper_view.php?id=${paperId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('View tracked for paper ID:', paperId);
            }
        })
        .catch(error => {
            console.error('Error tracking view:', error);
        });
}

// Enhanced Abstract Display Function with Complete Metadata
function showAbstract(paperId) {
     trackPaperView(paperId);

    document.getElementById('abstractContent').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p class="mt-2">Loading abstract...</p></div>';
    document.getElementById('abstractModal').classList.remove('hidden');
    
    fetch(`get_abstract.php?id=${paperId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('abstractContent').innerHTML = 
                    `<div class="text-center py-8">
                        <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                        <p class="text-red-600">${data.error}</p>
                    </div>`;
            } else {
                let htmlContent = `
                    <div class="space-y-4">
                        <div class="border-b pb-4">
                            <h4 class="font-bold text-base lg:text-xl mb-3 text-gray-800">${data.title}</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 text-xs lg:text-sm">
                                <div>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-user mr-1"></i>Authors:</span>
                                    <p class="text-gray-600">${data.authors}</p>
                                </div>
                                
                                ${data.author_email ? `
                                <div>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-envelope mr-1"></i>Contact:</span>
                                    <p class="text-gray-600 break-all">${data.author_email}</p>
                                </div>` : ''}
                                
                                ${data.affiliation ? `
                                <div>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-university mr-1"></i>Affiliation:</span>
                                    <p class="text-gray-600">${data.affiliation}</p>
                                </div>` : ''}
                                
                                ${data.research_type ? `
                                <div>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-flask mr-1"></i>Research Type:</span>
                                    <p class="text-gray-600">${data.research_type}</p>
                                </div>` : ''}
                                
                                ${data.submission_year ? `
                                <div>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-calendar mr-1"></i>Year:</span>
                                    <p class="text-gray-600">${data.submission_year}</p>
                                </div>` : ''}
                                
                                ${data.funding_source ? `
                                <div>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-hand-holding-usd mr-1"></i>Funding:</span>
                                    <p class="text-gray-600">${data.funding_source}</p>
                                </div>` : ''}
                                
                                ${data.research_period ? `
                                <div>
                                    <span class="font-semibold text-gray-700"><i class="fas fa-clock mr-1"></i>Research Period:</span>
                                    <p class="text-gray-600">${data.research_period}</p>
                                </div>` : ''}
                            </div>
                        </div>
                        
                        ${data.keywords ? `
                        <div class="pb-4">
                            <h5 class="font-semibold text-gray-700 mb-2 text-sm lg:text-base"><i class="fas fa-tags mr-1"></i>Keywords</h5>
                            <div class="flex flex-wrap gap-2">
                                ${data.keywords.split(',').map(keyword => 
                                    `<span class="bg-blue-100 text-blue-800 text-xs px-2 lg:px-3 py-1 rounded-full">${keyword.trim()}</span>`
                                ).join('')}
                            </div>
                        </div>` : ''}
                        
                        <div class="pb-4">
                            <h5 class="font-semibold text-gray-700 mb-2 text-sm lg:text-base"><i class="fas fa-align-left mr-1"></i>Abstract</h5>
                            <div class="bg-gray-50 p-3 lg:p-4 rounded-lg">
                                <p class="text-gray-700 leading-relaxed whitespace-pre-line text-sm lg:text-base">${data.abstract}</p>
                            </div>
                        </div>
                        
                        ${data.methodology ? `
                        <div class="pb-4">
                            <h5 class="font-semibold text-gray-700 mb-2 text-sm lg:text-base"><i class="fas fa-microscope mr-1"></i>Methodology</h5>
                            <div class="bg-gray-50 p-3 lg:p-4 rounded-lg">
                                <p class="text-gray-700 leading-relaxed whitespace-pre-line text-sm lg:text-base">${data.methodology}</p>
                            </div>
                        </div>` : ''}
                        
                        ${data.ethics_approval ? `
                        <div class="pb-4">
                            <h5 class="font-semibold text-gray-700 mb-2 text-sm lg:text-base"><i class="fas fa-shield-alt mr-1"></i>Ethics Approval</h5>
                            <div class="bg-gray-50 p-3 lg:p-4 rounded-lg">
                                <p class="text-gray-700 leading-relaxed text-sm lg:text-base">${data.ethics_approval}</p>
                            </div>
                        </div>` : ''}
                        
                        <div class="flex flex-col sm:flex-row justify-end gap-2 lg:gap-3 pt-4 border-t">
                            ${data.file_path ? `
                            <a href="paper_viewer.php?id=${paperId}" 
                               target="_blank"
                               class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm transition-colors text-center">
                                <i class="fas fa-eye mr-1"></i>View Full Paper
                            </a>
                            <a href="download_paper.php?id=${paperId}" 
                               class="bg-green-600 hover:bg-green-700 text-white px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm transition-colors text-center">
                                <i class="fas fa-download mr-1"></i>Download
                            </a>` : `
                            <button onclick="showLoginPrompt()" 
                               class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-3 lg:px-4 py-2 rounded-lg text-xs lg:text-sm">
                                <i class="fas fa-sign-in-alt mr-1"></i>Login to View Full Details
                            </button>`}
                        </div>
                    </div>
                `;
                
                document.getElementById('abstractContent').innerHTML = htmlContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('abstractContent').innerHTML = 
                `<div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-5xl mb-4"></i>
                    <p class="text-red-600">Error loading abstract. Please try again.</p>
                </div>`;
        });
}

function closeAbstract() {
    document.getElementById('abstractModal').classList.add('hidden');
}

// Enhanced Citation Display Function with Multiple Formats
function showCitation(paperId) {
     trackPaperView(paperId);

    document.getElementById('citationContent').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p class="mt-2">Loading citation formats...</p></div>';
    document.getElementById('citationModal').classList.remove('hidden');
    
    fetch(`get_citation.php?id=${paperId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('citationContent').innerHTML = 
                    `<p class="text-red-600">${data.error}</p>`;
            } else {
                document.getElementById('citationContent').innerHTML = `
                    <div class="space-y-4">
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold text-gray-800 text-sm lg:text-base">APA Format (7th Edition)</h4>
                                <button onclick="copyCitation('apa', event)" 
                                        class="text-blue-600 hover:text-blue-800 text-xs lg:text-sm transition-colors">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                            </div>
                            <p id="apa-citation" class="text-xs lg:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.apa}</p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold text-gray-800 text-sm lg:text-base">MLA Format (9th Edition)</h4>
                                <button onclick="copyCitation('mla', event)" 
                                        class="text-blue-600 hover:text-blue-800 text-xs lg:text-sm transition-colors">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                            </div>
                            <p id="mla-citation" class="text-xs lg:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.mla}</p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold text-gray-800 text-sm lg:text-base">Chicago Format (17th Edition)</h4>
                                <button onclick="copyCitation('chicago', event)" 
                                        class="text-blue-600 hover:text-blue-800 text-xs lg:text-sm transition-colors">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                            </div>
                            <p id="chicago-citation" class="text-xs lg:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.chicago}</p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold text-gray-800 text-sm lg:text-base">IEEE Format</h4>
                                <button onclick="copyCitation('ieee', event)" 
                                        class="text-blue-600 hover:text-blue-800 text-xs lg:text-sm transition-colors">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                            </div>
                            <p id="ieee-citation" class="text-xs lg:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.ieee}</p>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold text-gray-800 text-sm lg:text-base">BibTeX Format</h4>
                                <button onclick="copyCitation('bibtex', event)" 
                                        class="text-blue-600 hover:text-blue-800 text-xs lg:text-sm transition-colors">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                            </div>
                            <pre id="bibtex-citation" class="text-xs bg-gray-50 p-3 rounded overflow-x-auto font-mono">${data.bibtex}</pre>
                        </div>
                        
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-3 lg:p-4 mt-4">
                            <p class="text-xs lg:text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Note:</strong> Always verify citation format requirements with your institution or publisher.
                            </p>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('citationContent').innerHTML = 
                '<p class="text-red-600">Error loading citation formats. Please try again.</p>';
        });
}

function closeCitation() {
    document.getElementById('citationModal').classList.add('hidden');
}

function copyCitation(format, event) {
    const citation = document.getElementById(`${format}-citation`).innerText;
    navigator.clipboard.writeText(citation).then(() => {
        // Create temporary success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
        btn.classList.remove('text-blue-600', 'hover:text-blue-800');
        btn.classList.add('text-green-600');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('text-green-600');
            btn.classList.add('text-blue-600', 'hover:text-blue-800');
        }, 2000);
    }).catch(err => {
        alert('Failed to copy citation. Please try selecting and copying manually.');
    });
}

function showLoginPrompt() {
    if (confirm('You need to login to access this feature. Would you like to login now?')) {
        window.location.href = 'userlogin.php';
    }
}

// Initialize event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Close modals when clicking outside
    const abstractModal = document.getElementById('abstractModal');
    const citationModal = document.getElementById('citationModal');
    
    if (abstractModal) {
        abstractModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAbstract();
            }
        });
    }
    
    if (citationModal) {
        citationModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCitation();
            }
        });
    }
    
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
                // Close mobile menu if open
                const mobileMenu = document.getElementById('mobile-menu');
                if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                }
            }
        });
    });
    
    // Auto-submit search form on Enter key
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAbstract();
            closeCitation();
        }
    });
});
</script>

</body>
</html>