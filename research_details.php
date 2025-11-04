<?php
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Include database connection
require_once 'connect.php';

// Start session and check if user is logged in
session_start();

// Debug: Log session data
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET parameters: " . print_r($_GET, true));

// If not logged in, redirect to public version
if (!isset($_SESSION['id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    error_log("User not logged in - redirecting to elibrary.php");
    header("Location: elibrary.php");
    exit();
}

// Get user information
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

error_log("User logged in: ID=$user_id, Username=$username, Name=$user_name, Role=$user_role");

// Get paper ID
$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

error_log("Paper ID requested: $paper_id");

if (!$paper_id) {
    error_log("No paper ID provided - redirecting to elibrary_loggedin.php");
    header("Location: elibrary_loggedin.php");
    exit();
}

// Get paper details with metrics
$sql = "SELECT ps.*, 
               COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
               COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
        FROM paper_submissions ps 
        LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
        WHERE ps.id = ? AND ps.status IN ('approved', 'published')
        GROUP BY ps.id";

if (!$stmt = $conn->prepare($sql)) {
    error_log("SQL prepare error: " . $conn->error);
    die("Database error: " . $conn->error);
}

$stmt->bind_param('i', $paper_id);

if (!$stmt->execute()) {
    error_log("SQL execute error: " . $stmt->error);
    die("Database error: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Paper not found: ID=$paper_id");
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Paper Not Found</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md text-center">
            <i class="fas fa-exclamation-circle text-red-500 text-6xl mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Paper Not Found</h1>
            <p class="text-gray-600 mb-6">The requested research paper could not be found or is not available.</p>
            <a href="elibrary_loggedin.php" class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg inline-block">
                <i class="fas fa-arrow-left mr-2"></i>Return to Library
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$paper = $result->fetch_assoc();
error_log("Paper found: " . $paper['paper_title']);

// Record view metric - with error handling
try {
    $view_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
                 VALUES (?, 'view', ?, ?, NOW())";
    if ($view_stmt = $conn->prepare($view_sql)) {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $view_stmt->bind_param('iis', $paper_id, $user_id, $user_ip);
        $view_stmt->execute();
        error_log("View metric recorded successfully");
    } else {
        error_log("Failed to prepare view metric statement: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Exception recording view metric: " . $e->getMessage());
}

// Log view activity - with error handling
try {
    $activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, paper_id, ip_address, created_at)
                     VALUES (?, ?, 'view_paper', ?, ?, ?, NOW())";
    if ($activity_stmt = $conn->prepare($activity_sql)) {
        $activity_desc = "Viewed paper: " . $paper['paper_title'];
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $activity_stmt->bind_param('issss', $user_id, $username, $activity_desc, $paper_id, $user_ip);
        $activity_stmt->execute();
        error_log("Activity logged successfully");
    } else {
        error_log("Failed to prepare activity log statement: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Exception logging activity: " . $e->getMessage());
}

// Get related papers (same research type)
$related_papers = [];
try {
    $related_sql = "SELECT ps.id, ps.paper_title, ps.author_name, ps.submission_date, ps.research_type,
                           COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views
                    FROM paper_submissions ps
                    LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
                    WHERE ps.research_type = ? AND ps.id != ? AND ps.status IN ('approved', 'published')
                    GROUP BY ps.id
                    ORDER BY total_views DESC
                    LIMIT 5";
    if ($related_stmt = $conn->prepare($related_sql)) {
        $related_stmt->bind_param('si', $paper['research_type'], $paper_id);
        $related_stmt->execute();
        $related_papers = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("Found " . count($related_papers) . " related papers");
    }
} catch (Exception $e) {
    error_log("Exception getting related papers: " . $e->getMessage());
}

// Check if file exists
$file_exists = false;
$file_path = $paper['file_path'];

if (!empty($file_path)) {
    $normalized_path = ltrim($file_path, './');
    $normalized_path = ltrim($normalized_path, '/');
    
    $possible_paths = [
        __DIR__ . '/' . $normalized_path,
        $_SERVER['DOCUMENT_ROOT'] . '/' . $normalized_path,
        dirname(__DIR__) . '/' . $normalized_path,
        __DIR__ . '/uploads/papers/' . basename($file_path),
        $file_path
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $file_exists = true;
            break;
        }
    }
}

// Format dates
$submission_date = date('F d, Y', strtotime($paper['submission_date']));
$research_period = '';
if (!empty($paper['research_start_date']) && !empty($paper['research_end_date'])) {
    $research_period = date('M Y', strtotime($paper['research_start_date'])) . ' - ' . 
                      date('M Y', strtotime($paper['research_end_date']));
}

error_log("Page rendering starting...");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($paper['paper_title']); ?> - Research Details</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #115D5B 0%, #1A4D3A 100%);
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navigation Bar -->
    <nav class="bg-[#115D5B] text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <!-- Desktop Navigation -->
            <div class="hidden lg:flex justify-between items-center">
                <div class="flex items-center">
                    <img src="Images/CNLRRS_icon.png" alt="CNLRRS Logo" class="h-10 w-10 mr-2">
                    <span class="text-xl font-bold">CNLRRS Research Repository</span>
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
                                    <i class="fas fa-file-alt mr-2"></i>My Submissions
                                </a>
                                <a href="submit_paper.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-upload mr-2"></i>Submit Paper
                                </a>
                                <hr class="my-1">
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 rounded-b-lg">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
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
                            <i class="fas fa-file-alt mr-2"></i>My Submissions
                        </a>
                        <a href="submit_paper.php" class="block py-2 px-4 hover:bg-[#103635] rounded">
                            <i class="fas fa-upload mr-2"></i>Submit Paper
                        </a>
                        <a href="logout.php" class="block py-2 px-4 hover:bg-[#103635] rounded text-red-300">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="container mx-auto px-4 py-3">
            <nav class="flex text-sm text-gray-600 flex-wrap">
                <a href="loggedin_index.php" class="hover:text-[#115D5B]">Home</a>
                <span class="mx-2">/</span>
                <a href="elibrary_loggedin.php" class="hover:text-[#115D5B]">E-Library</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Research Details</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4 md:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Paper Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Paper Header -->
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
                    <?php if (!$file_exists): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-400 mr-2 mt-1"></i>
                            <div>
                                <p class="text-sm text-yellow-700">
                                    <strong>Note:</strong> The PDF file for this paper is currently unavailable. You can still view the details below.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex flex-col md:flex-row justify-between items-start mb-4 gap-4">
                        <div class="flex-1 w-full">
                            <span class="inline-block px-3 py-1 text-xs rounded-full mb-3 <?php echo $paper['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <i class="fas fa-check-circle mr-1"></i><?php echo ucfirst($paper['status']); ?>
                            </span>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($paper['paper_title']); ?></h1>
                        </div>
                    </div>

                    <!-- Authors -->
                    <div class="mb-4 pb-4 border-b">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">
                            <i class="fas fa-users mr-2"></i>Authors
                        </h3>
                        <p class="text-base md:text-lg text-gray-800">
                            <?php echo htmlspecialchars($paper['author_name']); ?>
                            <?php if (!empty($paper['co_authors'])): ?>
                                <span class="text-gray-600">, <?php echo htmlspecialchars($paper['co_authors']); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($paper['author_email'])): ?>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($paper['author_email']); ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Affiliation</h4>
                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($paper['affiliation']); ?></p>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Research Type</h4>
                            <p class="text-sm text-gray-800">
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">
                                    <?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Submission Date</h4>
                            <p class="text-sm text-gray-800"><?php echo $submission_date; ?></p>
                        </div>
                        <?php if (!empty($paper['funding_source'])): ?>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Funding Source</h4>
                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($paper['funding_source']); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($research_period): ?>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Research Period</h4>
                            <p class="text-sm text-gray-800"><?php echo $research_period; ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Statistics</h4>
                            <p class="text-sm text-gray-800">
                                <i class="fas fa-eye text-blue-500"></i> <?php echo $paper['total_views']; ?> 
                                <i class="fas fa-download text-green-500 ml-2"></i> <?php echo $paper['total_downloads']; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Keywords -->
                    <?php if (!empty($paper['keywords'])): ?>
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">
                            <i class="fas fa-tags mr-2"></i>Keywords
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $keywords = explode(',', $paper['keywords']);
                            foreach ($keywords as $keyword): 
                            ?>
                            <span class="bg-blue-50 text-blue-700 text-xs md:text-sm px-3 py-1 rounded-full border border-blue-200">
                                <?php echo htmlspecialchars(trim($keyword)); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 pt-4 border-t">
                        <?php if ($file_exists): ?>
                        <a href="paper_viewer.php?id=<?php echo $paper_id; ?>" 
                           target="_blank"
                           class="flex-1 md:flex-none bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-4 md:px-6 py-2 md:py-3 rounded-lg text-center transition-colors text-sm md:text-base">
                            <i class="fas fa-eye mr-2"></i>View Paper
                        </a>
                        <a href="download_paper.php?id=<?php echo $paper_id; ?>" 
                           class="flex-1 md:flex-none bg-green-600 hover:bg-green-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg text-center transition-colors text-sm md:text-base">
                            <i class="fas fa-download mr-2"></i>Download
                        </a>
                        <?php else: ?>
                        <button disabled 
                                class="flex-1 md:flex-none bg-gray-400 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg text-center cursor-not-allowed text-sm md:text-base">
                            <i class="fas fa-eye mr-2"></i>File Unavailable
                        </button>
                        <?php endif; ?>
                        <button onclick="showCitation()" 
                                class="flex-1 md:flex-none bg-gray-600 hover:bg-gray-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg transition-colors text-sm md:text-base">
                            <i class="fas fa-quote-right mr-2"></i>Cite
                        </button>
                        <button onclick="sharePaper()" 
                                class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg transition-colors text-sm md:text-base">
                            <i class="fas fa-share-alt mr-2"></i>Share
                        </button>
                    </div>
                </div>

                <!-- Abstract -->
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-align-left mr-2 text-[#115D5B]"></i>Abstract
                    </h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-line text-sm md:text-base"><?php echo htmlspecialchars($paper['abstract']); ?></p>
                    </div>
                </div>

                <!-- Methodology -->
                <?php if (!empty($paper['methodology'])): ?>
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-microscope mr-2 text-[#115D5B]"></i>Methodology
                    </h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-line text-sm md:text-base"><?php echo htmlspecialchars($paper['methodology']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ethics Approval -->
                <?php if (!empty($paper['ethics_approval'])): ?>
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-shield-alt mr-2 text-[#115D5B]"></i>Ethics Approval
                    </h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed text-sm md:text-base"><?php echo htmlspecialchars($paper['ethics_approval']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Comments -->
                <?php if (!empty($paper['additional_comments'])): ?>
                <div class="bg-blue-50 border-l-4 border-blue-400 rounded-lg p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-semibold text-blue-900 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Additional Information
                    </h3>
                    <p class="text-sm md:text-base text-blue-800"><?php echo htmlspecialchars($paper['additional_comments']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions Card -->
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="elibrary_loggedin.php" 
                           class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 md:py-3 rounded-lg text-center transition-colors text-sm md:text-base">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Library
                        </a>
                        <button onclick="window.print()" 
                                class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 md:py-3 rounded-lg transition-colors text-sm md:text-base">
                            <i class="fas fa-print mr-2"></i>Print Page
                        </button>
                    </div>
                </div>

                <!-- Paper Statistics -->
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-bold text-gray-800 mb-4">Paper Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm md:text-base text-gray-600">
                                <i class="fas fa-eye text-blue-500 mr-2"></i>Total Views
                            </span>
                            <span class="text-xl md:text-2xl font-bold text-blue-600"><?php echo $paper['total_views']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm md:text-base text-gray-600">
                                <i class="fas fa-download text-green-500 mr-2"></i>Downloads
                            </span>
                            <span class="text-xl md:text-2xl font-bold text-green-600"><?php echo $paper['total_downloads']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm md:text-base text-gray-600">
                                <i class="fas fa-calendar text-purple-500 mr-2"></i>Submitted
                            </span>
                            <span class="text-xs md:text-sm font-medium text-gray-800"><?php echo date('M Y', strtotime($paper['submission_date'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Related Papers -->
                <?php if (!empty($related_papers)): ?>
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6">
                    <h3 class="text-base md:text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-link mr-2 text-[#115D5B]"></i>Related Papers
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($related_papers as $related): ?>
                        <a href="research_details.php?id=<?php echo $related['id']; ?>" 
                           class="block p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <h4 class="font-semibold text-xs md:text-sm text-gray-800 mb-1 line-clamp-2">
                                <?php echo htmlspecialchars($related['paper_title']); ?>
                            </h4>
                            <p class="text-xs text-gray-600">
                                <?php echo htmlspecialchars($related['author_name']); ?> • 
                                <?php echo date('Y', strtotime($related['submission_date'])); ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-eye text-blue-500"></i> <?php echo $related['total_views']; ?> views
                            </p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Research Category Info -->
                <div class="gradient-bg rounded-lg shadow-lg p-4 md:p-6 text-white">
                    <h3 class="text-base md:text-lg font-bold mb-3">
                        <i class="fas fa-info-circle mr-2"></i>Research Category
                    </h3>
                    <p class="text-sm mb-4"><?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?></p>
                    <a href="elibrary_loggedin.php?category=<?php echo urlencode($paper['research_type']); ?>" 
                       class="block w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg text-center transition-all text-sm">
                        View More in This Category
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-6 md:p-8 mt-12">
        <div class="container mx-auto text-center">
            <p class="text-sm md:text-base text-gray-400">&copy; 2025 CNLRRS Queen Pineapple Research E-Library. All rights reserved.</p>
            <div class="mt-4 space-x-4">
                <a href="#" class="text-sm md:text-base text-gray-400 hover:text-white">Terms</a>
                <a href="#" class="text-sm md:text-base text-gray-400 hover:text-white">Privacy</a>
                <a href="#" class="text-sm md:text-base text-gray-400 hover:text-white">Contact</a>
            </div>
        </div>
    </footer>

    <!-- Citation Modal -->
    <div id="citationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-4 md:p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="flex justify-between items-center mb-4 md:mb-6">
                    <h3 class="text-lg md:text-xl font-semibold text-gray-800">
                        <i class="fas fa-quote-right mr-2"></i>Citation Formats
                    </h3>
                    <button onclick="closeCitation()" 
                            class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="citationContent" class="text-gray-700">
                    <p>Loading citation formats...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-4 md:p-6 max-w-md w-full shadow-2xl">
                <div class="flex justify-between items-center mb-4 md:mb-6">
                    <h3 class="text-lg md:text-xl font-semibold text-gray-800">
                        <i class="fas fa-share-alt mr-2"></i>Share This Paper
                    </h3>
                    <button onclick="closeShare()" 
                            class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Share Link</label>
                        <div class="flex">
                            <input type="text" id="shareLink" readonly 
                                   value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg text-sm">
                            <button onclick="copyShareLink()" 
                                    class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-4 py-2 rounded-r-lg">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Share via Email</label>
                        <a href="mailto:?subject=<?php echo urlencode($paper['paper_title']); ?>&body=Check out this research paper: <?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center transition-colors">
                            <i class="fas fa-envelope mr-2"></i>Send Email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }

        function showCitation() {
            document.getElementById('citationModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.getElementById('citationContent').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p class="mt-2">Loading citation formats...</p></div>';
            
            fetch('get_citation.php?id=<?php echo $paper_id; ?>')
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
                                        <h4 class="font-semibold text-sm md:text-base">APA Format (7th Edition)</h4>
                                        <button onclick="copyCitation('apa', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-xs md:text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="apa-citation" class="text-xs md:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.apa}</p>
                                </div>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold text-sm md:text-base">MLA Format (9th Edition)</h4>
                                        <button onclick="copyCitation('mla', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-xs md:text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="mla-citation" class="text-xs md:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.mla}</p>
                                </div>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold text-sm md:text-base">Chicago Format (17th Edition)</h4>
                                        <button onclick="copyCitation('chicago', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-xs md:text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="chicago-citation" class="text-xs md:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.chicago}</p>
                                </div>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold text-sm md:text-base">IEEE Format</h4>
                                        <button onclick="copyCitation('ieee', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-xs md:text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="ieee-citation" class="text-xs md:text-sm bg-gray-50 p-3 rounded leading-relaxed">${data.ieee}</p>
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold text-sm md:text-base">BibTeX Format</h4>
                                        <button onclick="copyCitation('bibtex', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-xs md:text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <pre id="bibtex-citation" class="text-xs bg-gray-50 p-3 rounded overflow-x-auto font-mono">${data.bibtex}</pre>
                                </div>
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 md:p-4">
                                    <p class="text-xs md:text-sm text-blue-800">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>Note:</strong> Always verify citation format requirements with your institution or publisher.
                                    </p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Citation error:', error);
                    document.getElementById('citationContent').innerHTML = 
                        '<p class="text-red-600">Error loading citation formats. Please try again.</p>';
                });
        }

        function closeCitation() {
            document.getElementById('citationModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function copyCitation(format, event) {
            const citation = document.getElementById(`${format}-citation`).innerText;
            navigator.clipboard.writeText(citation).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
                btn.classList.add('text-green-600');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('text-green-600');
                }, 2000);
            }).catch(err => {
                console.error('Copy error:', err);
                alert('Failed to copy citation. Please select and copy manually.');
            });
        }

        function sharePaper() {
            document.getElementById('shareModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeShare() {
            document.getElementById('shareModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function copyShareLink() {
            const shareLink = document.getElementById('shareLink');
            shareLink.select();
            navigator.clipboard.writeText(shareLink.value).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Copy error:', err);
                alert('Failed to copy link. Please select and copy manually.');
            });
        }

        // Close modals on outside click
        document.getElementById('citationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCitation();
            }
        });

        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeShare();
            }
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCitation();
                closeShare();
            }
        });

        // Smooth scroll for any anchor links
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
    </script>

</body>
</html>