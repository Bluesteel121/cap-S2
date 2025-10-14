<?php
session_start();

// Check if user is logged in and has reviewer role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'reviewer') {
    header('Location: index.php');
    exit();
}

// Include required files
require_once 'connect.php';
require_once 'email_config.php';
require_once 'user_activity_logger.php';

// Handle reviewer submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $paper_id = (int)$_POST['paper_id'];
    $reviewer_status = $_POST['reviewer_status'];
    $reviewer_recommendation = $_POST['reviewer_recommendation'] ?? '';
    $reviewer_comments = $_POST['reviewer_comments'] ?? '';
    
    if ($paper_id && $reviewer_status && $reviewer_recommendation) {
        // Get paper details
        $paper_sql = "SELECT * FROM paper_submissions WHERE id = ?";
        $paper_stmt = $conn->prepare($paper_sql);
        $paper_stmt->bind_param("i", $paper_id);
        $paper_stmt->execute();
        $paper_result = $paper_stmt->get_result();
        $paper_data = $paper_result->fetch_assoc();
        
        if ($paper_data) {
            // Update paper with reviewer feedback
            $review_header = "\n\n--- REVIEWER FEEDBACK ---\n";
            $review_content = "Status: " . str_replace('_', ' ', strtoupper($reviewer_status)) . "\n" . 
                            "Recommendation: " . $reviewer_recommendation . "\n" .
                            "Comments: " . $reviewer_comments . "\n" .
                            "[Reviewed by: " . $_SESSION['username'] . " on " . date('Y-m-d H:i:s') . "]";
            
            $combined_comments = $review_header . $review_content;
            
            $update_sql = "UPDATE paper_submissions SET 
                          reviewer_status = ?,
                          reviewer_recommendation = ?,
                          reviewer_comments = CONCAT(IFNULL(reviewer_comments, ''), ?),
                          status = 'under_review',
                          reviewed_by = ?,
                          review_date = NOW()
                          WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", 
                $reviewer_status, 
                $reviewer_recommendation,
                $combined_comments,
                $_SESSION['username'], 
                $paper_id
            );
            
            if ($update_stmt->execute()) {
                // Log the review activity
                logActivity('REVIEWER_SUBMISSION', 
                    "Reviewer {$_SESSION['username']} submitted review for Paper ID: $paper_id with status: $reviewer_status");
                
                // Send notification to admin
                $admin_sql = "SELECT email FROM accounts WHERE role = 'admin' LIMIT 1";
                $admin_result = $conn->query($admin_sql);
                if ($admin_row = $admin_result->fetch_assoc()) {
                    $admin_email = $admin_row['email'];
                    
                    // Optional: Send email notification to admin
                    $subject = "New Review Submitted - " . $paper_data['paper_title'];
                    $message = "A reviewer has submitted feedback for paper: " . $paper_data['paper_title'] . "\n\n";
                    $message .= "Reviewer: " . $_SESSION['username'] . "\n";
                    $message .= "Status: " . str_replace('_', ' ', strtoupper($reviewer_status)) . "\n\n";
                    $message .= "Please check the admin dashboard for details.";
                    
                    // Uncomment to send email
                    // mail($admin_email, $subject, $message);
                }
                
                $success_message = "Review submitted successfully! The admin has been notified.";
            } else {
                $error_message = "Failed to submit review: " . $conn->error;
                logError("Database error submitting review: " . $conn->error, 'DB_UPDATE_FAILED');
            }
            $update_stmt->close();
        } else {
            $error_message = "Paper not found.";
        }
        $paper_stmt->close();
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build query - reviewers see papers that are pending or under review
$sql = "SELECT * FROM paper_submissions WHERE status IN ('pending', 'under_review')";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    if ($status_filter === 'reviewed_by_me') {
        $sql .= " AND reviewed_by = ?";
        $params[] = $_SESSION['username'];
        $types .= "s";
    } elseif ($status_filter === 'not_reviewed') {
        $sql .= " AND (reviewed_by IS NULL OR reviewed_by != ?)";
        $params[] = $_SESSION['username'];
        $types .= "s";
    }
}

if (!empty($search_query)) {
    $sql .= " AND (paper_title LIKE ? OR author_name LIKE ? OR keywords LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY submission_date DESC";

try {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get reviewer statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_available,
                    SUM(CASE WHEN reviewed_by = ? THEN 1 ELSE 0 END) as reviewed_by_me,
                    SUM(CASE WHEN reviewed_by IS NULL OR reviewed_by != ? THEN 1 ELSE 0 END) as pending_review,
                    SUM(CASE WHEN reviewer_status = 'reviewer_approved' AND reviewed_by = ? THEN 1 ELSE 0 END) as approved_by_me,
                    SUM(CASE WHEN reviewer_status = 'reviewer_rejected' AND reviewed_by = ? THEN 1 ELSE 0 END) as rejected_by_me
                  FROM paper_submissions 
                  WHERE status IN ('pending', 'under_review')";
    $stats_stmt = $conn->prepare($stats_sql);
    $username = $_SESSION['username'];
    $stats_stmt->bind_param("ssss", $username, $username, $username, $username);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();

} catch(Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    logError("Database error in reviewer dashboard: " . $e->getMessage(), 'DB_ERROR');
    $submissions = [];
    $stats = ['total_available' => 0, 'reviewed_by_me' => 0, 'pending_review' => 0, 
              'approved_by_me' => 0, 'rejected_by_me' => 0];
}

// Status badge colors function
function getReviewerStatusBadge($status) {
    if (!$status) return 'bg-gray-100 text-gray-800 border-gray-200';
    
    switch($status) {
        case 'under_review':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'reviewer_approved':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'reviewer_rejected':
            return 'bg-red-100 text-red-800 border-red-200';
        case 'revisions_requested':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Dashboard - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .paper-card {
            transition: all 0.3s ease;
        }
        .paper-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .status-badge {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-[#115D5B] text-white py-4 px-6 shadow-lg">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                <div>
                    <h1 class="text-xl font-bold">Reviewer Dashboard</h1>
                    <p class="text-sm opacity-75">Review and Evaluate Research Submissions</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Reviewer: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="flex items-center space-x-2 hover:text-yellow-200 transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <div>
                        <p class="font-semibold">Success!</p>
                        <p><?php echo $success_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Reviewer Info Banner -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    <p class="text-blue-100">Your reviews help maintain the quality of research publications</p>
                </div>
                <i class="fas fa-user-check text-6xl opacity-20"></i>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-[#115D5B]"><?php echo $stats['total_available']; ?></div>
                <div class="text-sm text-gray-600">Total Available</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['reviewed_by_me']; ?></div>
                <div class="text-sm text-gray-600">Reviewed by Me</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_review']; ?></div>
                <div class="text-sm text-gray-600">Pending Review</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['approved_by_me']; ?></div>
                <div class="text-sm text-gray-600">Approved</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['rejected_by_me']; ?></div>
                <div class="text-sm text-gray-600">Rejected</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-64">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Papers</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search by title, author, or keywords..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Papers</option>
                        <option value="reviewed_by_me" <?php echo $status_filter === 'reviewed_by_me' ? 'selected' : ''; ?>>Reviewed by Me</option>
                        <option value="not_reviewed" <?php echo $status_filter === 'not_reviewed' ? 'selected' : ''; ?>>Not Yet Reviewed</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-md transition">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
                <div>
                    <a href="reviewer_dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Papers List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-file-alt mr-2"></i>Papers Available for Review 
                    <span class="text-sm font-normal text-gray-500">(<?php echo count($submissions); ?> results)</span>
                </h3>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-clipboard-check text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-500 mb-2">No papers available</h3>
                    <p class="text-gray-400">There are no papers matching your current filters.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-6 p-6">
                    <?php foreach ($submissions as $paper): ?>
                        <div class="paper-card bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <div class="flex flex-wrap lg:flex-nowrap justify-between items-start gap-4">
                                <!-- Paper Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between mb-3">
                                        <h4 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($paper['paper_title']); ?>
                                        </h4>
                                        <div class="flex items-center space-x-2 ml-2">
                                            <?php if ($paper['reviewer_status']): ?>
                                                <span class="status-badge inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo getReviewerStatusBadge($paper['reviewer_status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $paper['reviewer_status'])); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($paper['reviewed_by'] === $_SESSION['username']): ?>
                                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                                                    <i class="fas fa-check mr-1"></i>Reviewed by You
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 mb-3">
                                        <div>
                                            <p><strong>Author:</strong> <?php echo htmlspecialchars($paper['author_name']); ?></p>
                                            <p><strong>Affiliation:</strong> <?php echo htmlspecialchars($paper['affiliation']); ?></p>
                                            <?php if ($paper['co_authors']): ?>
                                                <p><strong>Co-authors:</strong> <?php echo htmlspecialchars($paper['co_authors']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p><strong>Research Type:</strong> <?php echo htmlspecialchars($paper['research_type']); ?></p>
                                            <p><strong>Submitted:</strong> <?php echo date('M j, Y', strtotime($paper['submission_date'])); ?></p>
                                            <?php if ($paper['reviewed_by']): ?>
                                                <p><strong>Last Reviewer:</strong> <?php echo htmlspecialchars($paper['reviewed_by']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="text-sm"><strong>Keywords:</strong> <?php echo htmlspecialchars($paper['keywords']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="text-sm"><strong>Abstract:</strong></p>
                                        <p class="text-sm text-gray-700 mt-1 line-clamp-3"><?php echo htmlspecialchars(substr($paper['abstract'], 0, 300)) . '...'; ?></p>
                                    </div>
                                    
                                    <?php if ($paper['reviewer_recommendation']): ?>
                                        <div class="mt-3 p-3 bg-blue-50 rounded-md border-l-4 border-blue-400">
                                            <p class="text-sm"><strong>Previous Recommendation:</strong></p>
                                            <p class="text-sm text-gray-700 mt-1"><?php echo htmlspecialchars($paper['reviewer_recommendation']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2 min-w-48">
                                    <button onclick="viewPaperDetails(<?php echo $paper['id']; ?>)" 
                                        class="action-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition">
                                        <i class="fas fa-eye mr-2"></i>View Full Details
                                    </button>
                                    
                                    <?php if ($paper['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($paper['file_path']); ?>" target="_blank"
                                           class="action-btn bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm text-center transition">
                                            <i class="fas fa-download mr-2"></i>Download Paper
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="openReviewModal(<?php echo $paper['id']; ?>, '<?php echo addslashes($paper['paper_title']); ?>')" 
                                        class="action-btn bg-[#115D5B] hover:bg-[#0d4a47] text-white px-4 py-2 rounded-md text-sm transition">
                                        <i class="fas fa-clipboard-check mr-2"></i>Submit Review
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paper Details Modal -->
    <div id="paperModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b bg-[#115D5B] text-white rounded-t-lg">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold">Paper Details</h3>
                    <button onclick="closePaperModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="paperContent" class="p-6">
                <!-- Paper details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b bg-[#115D5B] text-white rounded-t-lg">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold">
                        <i class="fas fa-clipboard-check mr-2"></i>Submit Review
                    </h3>
                    <button onclick="closeReviewModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6" onsubmit="return confirmReview(event)">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="paper_id" id="reviewPaperId">
                
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-800 text-lg mb-2" id="reviewPaperTitle"></h4>
                </div>

                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mr-2 mt-1"></i>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold">Review Guidelines</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Evaluate the research methodology and validity</li>
                                <li>Assess the originality and significance of findings</li>
                                <li>Check for clarity, organization, and completeness</li>
                                <li>Provide constructive feedback for improvement</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Review Status
                        <span class="text-red-500">*</span>
                    </label>
                    <select name="reviewer_status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
                        <option value="">-- Select Status --</option>
                        <option value="under_review">Under Review (Need More Time)</option>
                        <option value="reviewer_approved">Recommend for Approval</option>
                        <option value="reviewer_rejected">Recommend for Rejection</option>
                        <option value="revisions_requested">Revisions Requested</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Recommendation Summary
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reviewer_recommendation" rows="3" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                        placeholder="Provide a brief summary of your recommendation (e.g., 'This paper demonstrates strong methodology and significant findings. Recommend approval with minor revisions.')"></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Detailed Review Comments
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reviewer_comments" rows="6" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                        placeholder="Provide detailed feedback on:
- Research methodology and design
- Data analysis and interpretation  
- Clarity and organization of content
- Originality and significance
- Suggestions for improvement"></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Your detailed comments will help the admin make final decisions and provide feedback to authors.
                    </p>
                </div>

                <div class="flex gap-3">
                    <button type="submit" 
                        class="flex-1 bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-3 rounded-md transition font-semibold">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Review
                    </button>
                    <button type="button" onclick="closeReviewModal()"
                        class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-[#115D5B] text-white text-center py-4 mt-12">
        <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
    </footer>

    <script>
        const submissions = <?php echo json_encode($submissions); ?>;

        function viewPaperDetails(paperId) {
            const paper = submissions.find(p => p.id == paperId);
            
            if (paper) {
                document.getElementById('paperContent').innerHTML = `
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2 text-lg">Title:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.paper_title}</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Primary Author:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.author_name}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Email:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.author_email}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Affiliation:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.affiliation}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Research Type:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.research_type}</p>
                            </div>
                        </div>

                        ${paper.co_authors ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Co-Authors:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.co_authors}</p>
                        </div>
                        ` : ''}
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Keywords:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.keywords}</p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Abstract:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded whitespace-pre-wrap">${paper.abstract}</p>
                        </div>
                        
                        ${paper.methodology ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Methodology:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded whitespace-pre-wrap">${paper.methodology}</p>
                        </div>
                        ` : ''}
                        
                        ${paper.funding_source ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Funding Source:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.funding_source}</p>
                        </div>
                        ` : ''}
                        
                        ${paper.research_start_date || paper.research_end_date ? `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            ${paper.research_start_date ? `
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Research Start Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${new Date(paper.research_start_date).toLocaleDateString()}</p>
                            </div>
                            ` : ''}
                            ${paper.research_end_date ? `
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Research End Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${new Date(paper.research_end_date).toLocaleDateString()}</p>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Submission Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${new Date(paper.submission_date).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Current Status:</h4>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${getStatusBadgeClass(paper.status)}">
                                    ${paper.status.replace('_', ' ').toUpperCase()}
                                </span>
                            </div>
                        </div>
                        
                        ${paper.reviewer_recommendation ? `
                        <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                            <h4 class="font-semibold text-blue-800 mb-2">Previous Reviewer Recommendation:</h4>
                            <p class="text-sm text-blue-700">${paper.reviewer_recommendation}</p>
                        </div>
                        ` : ''}
                        
                        ${paper.reviewer_comments ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Review History:</h4>
                            <p class="text-gray-700 bg-yellow-50 p-3 rounded border-l-4 border-yellow-400 whitespace-pre-wrap">${paper.reviewer_comments}</p>
                        </div>
                        ` : ''}
                        
                        ${paper.reviewed_by ? `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Last Reviewed by:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.reviewed_by}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Review Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.review_date ? new Date(paper.review_date).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                        ` : ''}

                        ${paper.file_path ? `
                        <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-400">
                            <h4 class="font-semibold text-green-800 mb-2">
                                <i class="fas fa-file-pdf mr-2"></i>Research Paper Document
                            </h4>
                            <a href="${paper.file_path}" target="_blank" 
                               class="inline-flex items-center text-green-700 hover:text-green-900 font-medium">
                                <i class="fas fa-download mr-2"></i>Download Full Paper
                            </a>
                        </div>
                        ` : ''}
                    </div>
                `;
                document.getElementById('paperModal').classList.remove('hidden');
                document.getElementById('paperModal').classList.add('flex');
            }
        }

        function openReviewModal(paperId, paperTitle) {
            document.getElementById('reviewPaperId').value = paperId;
            document.getElementById('reviewPaperTitle').textContent = paperTitle;
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function confirmReview(event) {
            const status = event.target.querySelector('[name="reviewer_status"]').value;
            const recommendation = event.target.querySelector('[name="reviewer_recommendation"]').value;
            
            if (!status || !recommendation) {
                alert('Please fill in all required fields.');
                event.preventDefault();
                return false;
            }
            
            const statusLabels = {
                'under_review': 'mark as under review',
                'reviewer_approved': 'recommend for approval',
                'reviewer_rejected': 'recommend for rejection',
                'revisions_requested': 'request revisions'
            };
            
            const message = `Are you sure you want to ${statusLabels[status]}?\n\nYour review will be submitted to the admin for final decision.`;
            
            if (!confirm(message)) {
                event.preventDefault();
                return false;
            }
            
            // Show processing state
            const submitButton = event.target.querySelector('button[type="submit"]');
            const originalHTML = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting Review...';
            submitButton.disabled = true;
            
            return true;
        }

        function closePaperModal() {
            document.getElementById('paperModal').classList.add('hidden');
            document.getElementById('paperModal').classList.remove('flex');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.getElementById('reviewModal').classList.remove('flex');
        }

        function getStatusBadgeClass(status) {
            switch(status) {
                case 'pending': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                case 'under_review': return 'bg-blue-100 text-blue-800 border border-blue-200';
                case 'approved': return 'bg-green-100 text-green-800 border border-green-200';
                case 'rejected': return 'bg-red-100 text-red-800 border border-red-200';
                case 'published': return 'bg-purple-100 text-purple-800 border border-purple-200';
                default: return 'bg-gray-100 text-gray-800 border border-gray-200';
            }
        }

        // Close modals when clicking outside
        document.getElementById('paperModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaperModal();
            }
        });

        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReviewModal();
            }
        });

        // Auto-hide success message
        const successMessage = document.querySelector('.bg-green-100');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.5s ease-out';
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>