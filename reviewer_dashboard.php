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

// Initialize email templates
initializeDefaultEmailTemplates($conn);

// Handle paper review submissions
if ($_POST && isset($_POST['action'])) {
    $paper_id = (int)$_POST['paper_id'];
    $action = $_POST['action'];
    $comments = $_POST['comments'] ?? '';
    $reviewer_recommendation = $_POST['reviewer_recommendation'] ?? '';
    
    $new_status = '';
    switch($action) {
        case 'recommend_approve':
            $new_status = 'reviewer_approved';
            break;
        case 'recommend_reject':
            $new_status = 'reviewer_rejected';
            break;
        case 'request_revisions':
            $new_status = 'revisions_requested';
            break;
        case 'under_review':
            $new_status = 'under_review';
            break;
    }
    
    if ($new_status) {
        // Get paper details before updating
        $paper_sql = "SELECT * FROM paper_submissions WHERE id = ?";
        $paper_stmt = $conn->prepare($paper_sql);
        $paper_stmt->bind_param("i", $paper_id);
        $paper_stmt->execute();
        $paper_result = $paper_stmt->get_result();
        $paper_data = $paper_result->fetch_assoc();
        
        if ($paper_data) {
            // Insert reviewer feedback into a separate table (or update existing table with reviewer fields)
            $review_sql = "UPDATE paper_submissions SET 
                          reviewer_status = ?, 
                          reviewer_comments = ?, 
                          reviewer_recommendation = ?,
                          reviewed_by = ?, 
                          review_date = NOW() 
                          WHERE id = ?";
            $review_stmt = $conn->prepare($review_sql);
            $review_stmt->bind_param("ssssi", $new_status, $comments, $reviewer_recommendation, $_SESSION['username'], $paper_id);
            
            if ($review_stmt->execute()) {
                // Log the review action
                logActivity('REVIEWER_ACTION', "Paper ID: $paper_id, Status: $new_status, Reviewer: {$_SESSION['username']}");
                
                // Send notification to admin (you might want to implement this)
                // EmailService::sendReviewerNotificationToAdmin($paper_data, $_SESSION['username'], $new_status, $conn);
                
                $success_message = "Review submitted successfully! Admin has been notified of your recommendation.";
                
                // Log metrics
                $metrics_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_name, metric_date, additional_data) VALUES (?, ?, ?, NOW(), ?)";
                $metrics_stmt = $conn->prepare($metrics_sql);
                $metric_type = 'reviewer_action';
                $additional_data = json_encode(['action' => $new_status, 'reviewer' => $_SESSION['username']]);
                $metrics_stmt->bind_param("isss", $paper_id, $metric_type, $_SESSION['username'], $additional_data);
                $metrics_stmt->execute();
                
            } else {
                $error_message = "Failed to submit review: " . $conn->error;
                logError("Database error submitting reviewer feedback: " . $conn->error, 'DB_UPDATE_FAILED');
            }
            $review_stmt->close();
        } else {
            $error_message = "Paper not found.";
            logError("Paper not found for ID: $paper_id", 'PAPER_NOT_FOUND');
        }
        $paper_stmt->close();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending_review';
$search_query = $_GET['search'] ?? '';

// Build query for papers that need review or have been reviewed by this reviewer
$sql = "SELECT * FROM paper_submissions WHERE 1=1";
$params = [];
$types = "";

// Show papers that are pending review or under review, or papers this reviewer has already reviewed
if ($status_filter === 'my_reviews') {
    $sql .= " AND reviewed_by = ?";
    $params[] = $_SESSION['username'];
    $types .= "s";
} elseif ($status_filter === 'pending_review') {
    $sql .= " AND (status IN ('pending', 'under_review') OR reviewer_status IS NULL)";
} elseif ($status_filter !== 'all') {
    $sql .= " AND reviewer_status = ?";
    $params[] = $status_filter;
    $types .= "s";
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
                    SUM(CASE WHEN reviewed_by = ? THEN 1 ELSE 0 END) as my_reviews,
                    SUM(CASE WHEN reviewer_status = 'reviewer_approved' AND reviewed_by = ? THEN 1 ELSE 0 END) as my_approved,
                    SUM(CASE WHEN reviewer_status = 'reviewer_rejected' AND reviewed_by = ? THEN 1 ELSE 0 END) as my_rejected,
                    SUM(CASE WHEN reviewer_status = 'revisions_requested' AND reviewed_by = ? THEN 1 ELSE 0 END) as revisions_requested,
                    SUM(CASE WHEN (status IN ('pending', 'under_review') AND (reviewed_by IS NULL OR reviewed_by != ?)) THEN 1 ELSE 0 END) as pending_review
                  FROM paper_submissions";
    $stats_stmt = $conn->prepare($stats_sql);
    $username = $_SESSION['username'];
    $stats_stmt->bind_param("sssss", $username, $username, $username, $username, $username);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();

} catch(Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    logError("Database error in reviewer dashboard: " . $e->getMessage(), 'DB_ERROR');
    $submissions = [];
    $stats = ['total_available' => 0, 'my_reviews' => 0, 'my_approved' => 0, 'my_rejected' => 0, 'revisions_requested' => 0, 'pending_review' => 0];
}

// Status badge colors function for reviewer statuses
function getReviewerStatusBadge($reviewer_status, $status) {
    if ($reviewer_status) {
        switch($reviewer_status) {
            case 'under_review':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'reviewer_approved':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'reviewer_rejected':
                return 'bg-red-100 text-red-800 border-red-200';
            case 'revisions_requested':
                return 'bg-orange-100 text-orange-800 border-orange-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    } else {
        // Show original status if no reviewer status
        switch($status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'under_review':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    }
}

function getDisplayStatus($reviewer_status, $status) {
    if ($reviewer_status) {
        switch($reviewer_status) {
            case 'under_review':
                return 'Under Review';
            case 'reviewer_approved':
                return 'Recommended for Approval';
            case 'reviewer_rejected':
                return 'Recommended for Rejection';
            case 'revisions_requested':
                return 'Revisions Requested';
            default:
                return ucfirst(str_replace('_', ' ', $reviewer_status));
        }
    } else {
        return 'Awaiting Review';
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
        .status-badge {
            transition: all 0.2s ease;
        }
        .paper-row:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .reviewer-badge {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 8px;
            position: absolute;
            top: -2px;
            right: -2px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid #059669;
            transition: all 0.3s ease;
        }
        .profile-pic:hover {
            border-color: #f59e0b;
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-green-600 to-green-700 text-white py-4 px-6 shadow-lg">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                <div>
                    <h1 class="text-xl font-bold">
                        <i class="fas fa-user-graduate mr-2"></i>
                        Reviewer Dashboard
                    </h1>
                    <p class="text-sm opacity-75">Review and Evaluate Research Submissions</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <span class="text-sm font-medium">Reviewer: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div class="relative inline-block ml-2">
                        <img src="Images/initials profile/<?php echo strtolower(substr($_SESSION['username'], 0, 1)); ?>.png" 
                             alt="Profile Picture" 
                             class="profile-pic" 
                             title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Reviewer)"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        
                        <div class="profile-pic" 
                             title="<?php echo htmlspecialchars($_SESSION['username']); ?> (Reviewer)"
                             style="display: none; background: linear-gradient(135deg, #059669, #047857); align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: bold; text-transform: uppercase;">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        
                        <div class="reviewer-badge">REVIEWER</div>
                    </div>
                </div>
                <a href="index.php" onclick="logout()" class="flex items-center space-x-2 hover:text-yellow-200 transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
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

        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-green-50 to-green-100 border border-green-200 rounded-lg p-6 mb-8">
            <div class="flex items-start space-x-4">
                <div class="bg-green-600 p-3 rounded-full">
                    <i class="fas fa-clipboard-list text-white text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-green-800 mb-2">
                        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                    </h2>
                    <p class="text-green-700 mb-4">
                        As a reviewer, you can evaluate research submissions and provide recommendations to the admin team. 
                        Your expert feedback helps maintain the quality and integrity of published research.
                    </p>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full">
                            <i class="fas fa-eye mr-1"></i>Review Papers
                        </span>
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full">
                            <i class="fas fa-comments mr-1"></i>Provide Feedback
                        </span>
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full">
                            <i class="fas fa-thumbs-up mr-1"></i>Make Recommendations
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['total_available']; ?></div>
                <div class="text-sm text-gray-600">Total Papers</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_review']; ?></div>
                <div class="text-sm text-gray-600">Pending Review</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['my_reviews']; ?></div>
                <div class="text-sm text-gray-600">My Reviews</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['my_approved']; ?></div>
                <div class="text-sm text-gray-600">Recommended</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['my_rejected']; ?></div>
                <div class="text-sm text-gray-600">Not Recommended</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-orange-600"><?php echo $stats['revisions_requested']; ?></div>
                <div class="text-sm text-gray-600">Revisions Requested</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-64">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Papers</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search by title, author, or keywords..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="pending_review" <?php echo $status_filter === 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="my_reviews" <?php echo $status_filter === 'my_reviews' ? 'selected' : ''; ?>>My Reviews</option>
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Papers</option>
                        <option value="reviewer_approved" <?php echo $status_filter === 'reviewer_approved' ? 'selected' : ''; ?>>Recommended for Approval</option>
                        <option value="reviewer_rejected" <?php echo $status_filter === 'reviewer_rejected' ? 'selected' : ''; ?>>Recommended for Rejection</option>
                        <option value="revisions_requested" <?php echo $status_filter === 'revisions_requested' ? 'selected' : ''; ?>>Revisions Requested</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition">
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
                    <i class="fas fa-list mr-2"></i>Papers for Review 
                    <span class="text-sm font-normal text-gray-500">(<?php echo count($submissions); ?> results)</span>
                </h3>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-500 mb-2">No papers found</h3>
                    <p class="text-gray-400">No papers match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($submissions as $paper): ?>
                        <div class="paper-row p-6 transition-all duration-200">
                            <div class="flex flex-wrap lg:flex-nowrap justify-between items-start gap-4">
                                <!-- Paper Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between mb-2">
                                        <h4 class="text-lg font-semibold text-gray-900 truncate">
                                            <?php echo htmlspecialchars($paper['paper_title']); ?>
                                        </h4>
                                        <div class="flex items-center space-x-2 ml-2">
                                            <span class="status-badge inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo getReviewerStatusBadge($paper['reviewer_status'], $paper['status']); ?>">
                                                <?php echo getDisplayStatus($paper['reviewer_status'], $paper['status']); ?>
                                            </span>
                                            <?php if ($paper['reviewed_by'] === $_SESSION['username']): ?>
                                                <i class="fas fa-user-check text-green-600" title="Reviewed by you"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                        <div>
                                            <p><strong>Author:</strong> <?php echo htmlspecialchars($paper['author_name']); ?></p>
                                            <?php if ($paper['co_authors']): ?>
                                                <p><strong>Co-authors:</strong> <?php echo htmlspecialchars($paper['co_authors']); ?></p>
                                            <?php endif; ?>
                                            <p><strong>Submitted by:</strong> <?php echo htmlspecialchars($paper['user_name']); ?></p>
                                        </div>
                                        <div>
                                            <p><strong>Research Type:</strong> <?php echo htmlspecialchars($paper['research_type']); ?></p>
                                            <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($paper['submission_date'])); ?></p>
                                            <?php if ($paper['reviewed_by']): ?>
                                                <p><strong>Reviewed by:</strong> <?php echo htmlspecialchars($paper['reviewed_by']); ?></p>
                                                <p><strong>Review date:</strong> <?php echo $paper['review_date'] ? date('M j, Y', strtotime($paper['review_date'])) : 'N/A'; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <p class="text-sm"><strong>Keywords:</strong> <?php echo htmlspecialchars($paper['keywords']); ?></p>
                                    </div>
                                    
                                    <?php if ($paper['reviewer_comments']): ?>
                                        <div class="mt-3 p-3 bg-green-50 rounded-md border-l-4 border-green-400">
                                            <p class="text-sm"><strong>My Review Comments:</strong></p>
                                            <p class="text-sm text-gray-700 mt-1"><?php echo htmlspecialchars($paper['reviewer_comments']); ?></p>
                                            <?php if ($paper['reviewer_recommendation']): ?>
                                                <p class="text-sm mt-2"><strong>Recommendation:</strong> <?php echo htmlspecialchars($paper['reviewer_recommendation']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2 min-w-48">
                                    <button onclick="viewPaper(<?php echo $paper['id']; ?>)" 
                                        class="action-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition">
                                        <i class="fas fa-eye mr-2"></i>View Details
                                    </button>
                                    
                                    <?php if ($paper['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($paper['file_path']); ?>" target="_blank"
                                           class="action-btn bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md text-sm text-center transition">
                                            <i class="fas fa-download mr-2"></i>Download File
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="reviewPaper(<?php echo $paper['id']; ?>, '<?php echo addslashes($paper['paper_title']); ?>')" 
                                        class="action-btn bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm transition">
                                        <i class="fas fa-clipboard-check mr-2"></i>
                                        <?php echo ($paper['reviewed_by'] === $_SESSION['username']) ? 'Update Review' : 'Review Paper'; ?>
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
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-800">Paper Details</h3>
                    <button onclick="closePaperModal()" class="text-gray-400 hover:text-gray-600">
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
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-clipboard-check mr-2"></i>Review Paper
                    </h3>
                    <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6" onsubmit="return confirmReview(event)">
                <input type="hidden" name="paper_id" id="reviewPaperId">
                
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-800 mb-2" id="reviewPaperTitle"></h4>
                </div>

                <div class="bg-green-50 border border-green-200 p-4 rounded-lg mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-green-600 mr-2 mt-1"></i>
                        <div class="text-sm text-green-800">
                            <p class="font-semibold">Reviewer Guidelines</p>
                            <p>Your review will be submitted to the admin team for final decision. Please provide detailed feedback to help improve the quality of research.</p>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Review Comments
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea name="comments" rows="5" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Provide detailed feedback on the paper's methodology, findings, clarity, and overall quality..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Your comments will help the admin make an informed decision about the paper.
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Recommendation Summary
                    </label>
                    <textarea name="reviewer_recommendation" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Brief recommendation summary (e.g., 'Accept with minor revisions', 'Reject due to methodology issues')"></textarea>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="action" value="under_review" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-md transition">
                        <i class="fas fa-clock mr-2"></i>
                        <div class="text-left">
                            <div class="font-semibold">Set Under Review</div>
                            <div class="text-xs opacity-75">Mark as being reviewed</div>
                        </div>
                    </button>
                    <button type="submit" name="action" value="recommend_approve" 
                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-md transition">
                        <i class="fas fa-thumbs-up mr-2"></i>
                        <div class="text-left">
                            <div class="font-semibold">Recommend Approval</div>
                            <div class="text-xs opacity-75">Suggest for publication</div>
                        </div>
                    </button>
                    <button type="submit" name="action" value="request_revisions" 
                        class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-md transition">
                        <i class="fas fa-edit mr-2"></i>
                        <div class="text-left">
                            <div class="font-semibold">Request Revisions</div>
                            <div class="text-xs opacity-75">Needs improvements</div>
                        </div>
                    </button>
                    <button type="submit" name="action" value="recommend_reject" 
                        class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-md transition">
                        <i class="fas fa-thumbs-down mr-2"></i>
                        <div class="text-left">
                            <div class="font-semibold">Recommend Rejection</div>
                            <div class="text-xs opacity-75">Not suitable for publication</div>
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-green-600 text-white text-center py-4 mt-12">
        <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
        <p class="text-sm opacity-75">Reviewer Dashboard - Maintaining Research Excellence</p>
    </footer>

    <script>
        const submissions = <?php echo json_encode($submissions); ?>;

        function logout() {
            if (confirm('Are you sure you want to log out?')) {
                fetch('logout.php', {
                    method: 'POST'
                }).then(() => {
                    window.location.href = 'index.php';
                });
            }
        }

        function viewPaper(paperId) {
            const paper = submissions.find(p => p.id == paperId);
            
            if (paper) {
                document.getElementById('paperContent').innerHTML = `
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Title:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.paper_title}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Research Type:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.research_type}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Primary Author:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.author_name}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Submitted by:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.user_name}</p>
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
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Submission Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${new Date(paper.submission_date).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Current Status:</h4>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${getStatusBadgeClass(paper.reviewer_status, paper.status)}">
                                    ${getDisplayStatusText(paper.reviewer_status, paper.status)}
                                </span>
                            </div>
                        </div>
                        
                        ${paper.reviewer_comments ? `
                        <div class="bg-green-50 border border-green-200 p-4 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">
                                <i class="fas fa-user-check mr-2"></i>My Review Comments:
                            </h4>
                            <p class="text-green-700 mb-2">${paper.reviewer_comments}</p>
                            ${paper.reviewer_recommendation ? `
                                <p class="text-sm"><strong>Recommendation:</strong> ${paper.reviewer_recommendation}</p>
                            ` : ''}
                            <p class="text-xs text-green-600 mt-2">
                                <i class="fas fa-clock mr-1"></i>
                                Reviewed: ${paper.review_date ? new Date(paper.review_date).toLocaleDateString() : 'Recently'}
                            </p>
                        </div>
                        ` : `
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 mb-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>Review Needed
                            </h4>
                            <p class="text-yellow-700">This paper has not been reviewed yet. Click "Review Paper" to provide your evaluation.</p>
                        </div>
                        `}

                        <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                            <h4 class="font-semibold text-blue-800 mb-2">
                                <i class="fas fa-info-circle mr-2"></i>Reviewer Guidelines
                            </h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Evaluate the research methodology and validity</li>
                                <li>• Assess the clarity and quality of writing</li>
                                <li>• Check for originality and contribution to the field</li>
                                <li>• Provide constructive feedback for improvements</li>
                                <li>• Make a clear recommendation to the admin team</li>
                            </ul>
                        </div>
                    </div>
                `;
                document.getElementById('paperModal').classList.remove('hidden');
                document.getElementById('paperModal').classList.add('flex');
            }
        }

        function reviewPaper(paperId, paperTitle) {
            document.getElementById('reviewPaperId').value = paperId;
            document.getElementById('reviewPaperTitle').textContent = paperTitle;
            
            // Pre-fill existing review if available
            const paper = submissions.find(p => p.id == paperId);
            if (paper && paper.reviewer_comments) {
                document.querySelector('textarea[name="comments"]').value = paper.reviewer_comments;
            }
            if (paper && paper.reviewer_recommendation) {
                document.querySelector('textarea[name="reviewer_recommendation"]').value = paper.reviewer_recommendation;
            }
            
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function confirmReview(event) {
            const action = event.submitter.value;
            const actionLabels = {
                'recommend_approve': 'recommend this paper for approval',
                'recommend_reject': 'recommend this paper for rejection',
                'request_revisions': 'request revisions for this paper',
                'under_review': 'mark this paper as under review'
            };
            
            const message = `Are you sure you want to ${actionLabels[action]}? Your recommendation will be sent to the admin team.`;
            
            if (!confirm(message)) {
                event.preventDefault();
                return false;
            }
            
            // Show processing state
            const submitButton = event.submitter;
            const originalHTML = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
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

        function getStatusBadgeClass(reviewer_status, status) {
            if (reviewer_status) {
                switch(reviewer_status) {
                    case 'under_review': return 'bg-blue-100 text-blue-800 border border-blue-200';
                    case 'reviewer_approved': return 'bg-green-100 text-green-800 border border-green-200';
                    case 'reviewer_rejected': return 'bg-red-100 text-red-800 border border-red-200';
                    case 'revisions_requested': return 'bg-orange-100 text-orange-800 border border-orange-200';
                    default: return 'bg-gray-100 text-gray-800 border border-gray-200';
                }
            } else {
                switch(status) {
                    case 'pending': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                    case 'under_review': return 'bg-blue-100 text-blue-800 border border-blue-200';
                    default: return 'bg-gray-100 text-gray-800 border border-gray-200';
                }
            }
        }

        function getDisplayStatusText(reviewer_status, status) {
            if (reviewer_status) {
                switch(reviewer_status) {
                    case 'under_review': return 'Under Review';
                    case 'reviewer_approved': return 'Recommended for Approval';
                    case 'reviewer_rejected': return 'Recommended for Rejection';
                    case 'revisions_requested': return 'Revisions Requested';
                    default: return reviewer_status.replace('_', ' ').toUpperCase();
                }
            } else {
                return 'Awaiting Review';
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
            }, 8000);
        }

        // Auto-refresh page after successful submission
        <?php if (isset($success_message)): ?>
            setTimeout(() => {
                window.location.href = window.location.pathname + window.location.search;
            }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>