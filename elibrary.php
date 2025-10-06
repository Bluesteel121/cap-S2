<?php
// Include database connection
require_once 'connect.php';

// Check if user is logged in and redirect to logged-in version
session_start();
if (isset($_SESSION['id']) && isset($_SESSION['username']) && isset($_SESSION['role'])) {
    header("Location: elibrary_loggedin.php");
    exit();
}

// Get filter parameters
$search_keyword = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$papers_per_page = 10;
$offset = ($page - 1) * $papers_per_page;

// Build WHERE clause for search and filters - ONLY APPROVED PAPERS
$where_conditions = ["status = 'approved'"];
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
                     WHERE ps.status = 'approved'
                     GROUP BY ps.id 
                     ORDER BY (COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) + 
                              COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0)) DESC 
                     LIMIT 3";
    $featured_result = $conn->query($featured_sql);
    $featured_papers = $featured_result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM paper_submissions WHERE status = 'approved') as total_papers,
    (SELECT COUNT(DISTINCT author_name) FROM paper_submissions WHERE status = 'approved') as total_researchers,
    (SELECT COUNT(DISTINCT research_type) FROM paper_submissions WHERE status = 'approved') as research_categories,
    (SELECT COUNT(*) FROM paper_submissions WHERE status = 'under_review') as active_projects";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get category counts
$category_sql = "SELECT research_type, COUNT(*) as count 
                FROM paper_submissions 
                WHERE status = 'approved' 
                GROUP BY research_type 
                ORDER BY count DESC";
$category_result = $conn->query($category_sql);
$categories = $category_result->fetch_all(MYSQLI_ASSOC);

// Check if search is active
$is_searching = !empty($search_keyword) || !empty($category_filter) || !empty($year_filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.5">
    <title>Queen Pineapple Research E-Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navigation Bar -->
    <nav class="bg-[#115D5B] text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="Images/logo.png" alt="CNLRRS Logo" class="h-10 w-10 mr-2">
                <span class="text-xl font-bold">CNLRRS Rainfed Research Station</span>
            </div>
            <div class="space-x-4">
                <a href="index.php" class="hover:underline">Home</a>
                <a href="#" class="hover:underline">Our Services</a>
                <a href="#" class="hover:underline">About Us</a>
            </div>
            <a href="userlogin.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <!-- Hero Section -->
        <div class="flex flex-col md:flex-row items-center justify-between bg-white rounded-lg p-6 mb-8 shadow-md">
            <div class="md:w-1/2 mb-4 md:mb-0">
                <h1 class="text-3xl font-bold text-[#115D5B] mb-2">CNLRRS Queen Pineapple Research Repository</h1>
                <p class="text-gray-700 mb-4">Access the latest research, studies, and publications about Queen Pineapple varieties, cultivation, health benefits, and more.</p>
                <div class="mt-6 flex gap-4">
                    <a href="#search-section" class="bg-[#1A4D3A] text-white px-6 py-3 rounded-md font-semibold border border-white hover:bg-[#16663F] transition rounded-lg">
                        Browse Research
                    </a>
                    <a href="userlogin.php" class="bg-[#1A4D3A] border border-white text-white px-6 py-3 rounded-md font-semibold hover:bg-[#16663F] transition rounded-lg">
                        Submit Paper
                    </a>
                </div>
            </div>
            <div class="md:w-1/3">
                <img src="Images/md2.jpg" alt="Queen Pineapple" class="rounded-lg shadow-md w-full h-auto max-w-md" />
            </div>
        </div>

        <!-- Search Section -->
        <div id="search-section" class="bg-white rounded-lg p-6 mb-8 shadow-md">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-search mr-2"></i>Advanced Search
            </h2>
            <form method="GET" action="" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
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
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['research_type']); ?>" 
                                    <?php echo $category_filter === $category['research_type'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($category['research_type'])); ?> (<?php echo $category['count']; ?>)
                            </option>
                        <?php endforeach; ?>
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
                <a href="elibrary.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-colors">
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
                        Search Results: <?php echo $total_papers; ?> approved papers found
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
                    <a href="elibrary.php" class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg">
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
                                <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    Approved
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap items-center space-x-4 text-sm text-gray-600 mb-3">
                            <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($paper['author_name']); ?>
                            <?php if ($paper['co_authors']): ?>, <?php echo htmlspecialchars($paper['co_authors']); ?><?php endif; ?></span>
                            <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($paper['submission_date'])); ?></span>
                            <span><i class="fas fa-tag mr-1"></i><?php echo ucfirst(htmlspecialchars($paper['research_type'])); ?></span>
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
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <button onclick="showLoginPrompt()" 
                                        class="text-[#115D5B] hover:text-[#0e4e4c] font-medium text-sm px-3 py-1 rounded-md hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-info-circle mr-1"></i>Details
                                </button>
                                <button onclick="showAbstract(<?php echo $paper['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm px-3 py-1 rounded-md hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>Abstract
                                </button>
                                <button onclick="showLoginPrompt()" 
                                        class="text-green-600 hover:text-green-800 font-medium text-sm px-3 py-1 rounded-md hover:bg-green-50 transition-colors">
                                    <i class="fas fa-download mr-1"></i>Download
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
                        <a href="?page=1&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm">
                            <i class="fas fa-angle-left"></i> Prev
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="<?php echo $i === $page ? 'bg-[#115D5B] text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'; ?> px-3 py-2 rounded-lg text-sm">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
                           class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-3 py-2 rounded-lg text-sm">
                            Next <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search_keyword); ?>&category=<?php echo urlencode($category_filter); ?>&year=<?php echo urlencode($year_filter); ?>" 
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
            <div class="bg-white rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl text-[#115D5B] mb-4"><i class="<?php echo $icon; ?>"></i></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo ucfirst(htmlspecialchars($category['research_type'])); ?></h3>
                <p class="text-gray-600 mb-4">Research papers in <?php echo strtolower(htmlspecialchars($category['research_type'])); ?> from our repository.</p>
                <a href="?category=<?php echo urlencode($category['research_type']); ?>" class="text-[#115D5B] hover:text-[#0e4e4c] font-semibold">View <?php echo $category['count']; ?> Papers →</a>
            </div>
            <?php 
                $displayed++;
            endforeach; 
            ?>
        </div>

        <!-- Featured Research -->
        <?php if (!empty($featured_papers)): ?>
        <div class="bg-white rounded-lg p-6 mb-8 shadow-md">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Featured Research</h2>
            <div class="space-y-6">
                <?php foreach ($featured_papers as $index => $paper): ?>
                <div class="<?php echo $index < count($featured_papers) - 1 ? 'border-b border-gray-200 pb-6' : ''; ?>">
                    <h3 class="text-xl font-semibold text-[#115D5B] mb-2">
                        <?php echo htmlspecialchars($paper['paper_title']); ?>
                    </h3>
                    <div class="flex items-center space-x-2 text-gray-600 mb-2">
                        <span>Authors: <?php echo htmlspecialchars($paper['author_name']); ?><?php if ($paper['co_authors']): ?>, <?php echo htmlspecialchars($paper['co_authors']); ?><?php endif; ?></span>
                        <span>•</span>
                        <span><?php echo date('Y', strtotime($paper['submission_date'])); ?></span>
                        <span>•</span>
                        <span><?php echo ucfirst(htmlspecialchars($paper['research_type'])); ?></span>
                    </div>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars(substr($paper['abstract'], 0, 200)) . '...'; ?></p>
                    <div class="flex items-center space-x-4 mb-2">
                        <span class="text-sm text-gray-500">
                            <i class="fas fa-eye"></i> <?php echo $paper['total_views']; ?> views
                        </span>
                        <span class="text-sm text-gray-500">
                            <i class="fas fa-download"></i> <?php echo $paper['total_downloads']; ?> downloads
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button onclick="showLoginPrompt()" class="text-[#115D5B] hover:text-[#0e4e4c] font-medium">View Details</button>
                        <button onclick="showAbstract(<?php echo $paper['id']; ?>)" class="text-blue-600 hover:text-blue-800 font-medium">Abstract</button>
                        <button onclick="showLoginPrompt()" class="text-green-600 hover:text-green-800 font-medium">Download PDF</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Section -->
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

        <?php endif; // End of non-searching sections ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-8">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Research Categories</h3>
                    <ul class="space-y-2">
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <li><a href="?category=<?php echo urlencode($category['research_type']); ?>" class="text-gray-400 hover:text-white"><?php echo ucfirst(htmlspecialchars($category['research_type'])); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4 mb-4">
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white text-2xl"><i class="fab fa-instagram"></i></a>
                    </div>
                    <p class="text-gray-400">Subscribe to our newsletter for updates on the latest research.</p>
                    <div class="flex mt-2">
                        <input type="email" placeholder="Your email" class="p-2 rounded-l-lg w-full focus:outline-none text-black" />
                        <button class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white p-2 rounded-r-lg">Subscribe</button>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; 2025 CNLRRS Queen Pineapple Research E-Library. All rights reserved.</p>
            </div>
        </div>
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

    <script>
        function showAbstract(paperId) {
            document.getElementById('abstractContent').innerHTML = '<p>Loading abstract...</p>';
            document.getElementById('abstractModal').classList.remove('hidden');
            
            fetch(`get_abstract.php?id=${paperId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('abstractContent').innerHTML = `<p class="text-red-600">${data.error}</p>`;
                    } else {
                        document.getElementById('abstractContent').innerHTML = `
                            <h4 class="font-semibold mb-2">${data.title}</h4>
                            <p class="text-sm text-gray-600 mb-3">Authors: ${data.authors}</p>
                            <p class="leading-relaxed mb-4">${data.abstract}</p>
                            <div class="flex justify-end">
                                <button onclick="showLoginPrompt()" 
                                   class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-4 py-2 rounded-lg text-sm">
                                    Login to View Full Details
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('abstractContent').innerHTML = '<p class="text-red-600">Error loading abstract.</p>';
                });
        }

        function closeAbstract() {
            document.getElementById('abstractModal').classList.add('hidden');
        }

        function showLoginPrompt() {
            if (confirm('You need to login to access this feature. Would you like to login now?')) {
                window.location.href = 'userlogin.php';
            }
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
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    </script>

</body>
</html>