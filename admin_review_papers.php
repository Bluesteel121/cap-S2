<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include required files
require_once 'connect.php';
require_once 'email_config.php';
require_once 'user_activity_logger.php';

// Ensure autocommit is enabled
$conn->autocommit(TRUE);

// Initialize email templates
initializeDefaultEmailTemplates($conn);

// Handle POST requests for paper review
if ($_POST && isset($_POST['action'])) {
    // Add detailed logging
    error_log("=== REVIEW SUBMISSION START ===");
    error_log("POST Data: " . print_r($_POST, true));
    
    $paper_id = (int)$_POST['paper_id'];
    $action = $_POST['action'];
    $comments = $_POST['comments'] ?? '';
    $reviewer_recommendation = $_POST['reviewer_recommendation'] ?? '';
    
    error_log("Paper ID: $paper_id");
    error_log("Action: $action");
    error_log("Comments length: " . strlen($comments));
    
    $new_status = '';
    $reviewer_status = null;
    
    switch($action) {
        case 'approve':
            $new_status = 'approved';
            $reviewer_status = 'reviewer_approved';
            break;
        case 'reject':
            $new_status = 'rejected';
            $reviewer_status = 'reviewer_rejected';
            break;
        case 'review':
            $new_status = 'under_review';
            $reviewer_status = 'under_review';
            break;
        case 'revisions':
            $new_status = 'under_review';
            $reviewer_status = 'revisions_requested';
            break;
        default:
            error_log("Invalid action: $action");
            header("Location: admin_review_papers.php?error=invalid_action");
            exit();
    }
    
    error_log("New Status: $new_status");
    error_log("Reviewer Status: $reviewer_status");
    
    if ($new_status) {
        // Get paper details before updating
        $paper_sql = "SELECT * FROM paper_submissions WHERE id = ?";
        $paper_stmt = $conn->prepare($paper_sql);
        
        if (!$paper_stmt) {
            error_log("Prepare failed: " . $conn->error);
            header("Location: admin_review_papers.php?error=prepare_failed&detail=" . urlencode($conn->error));
            exit();
        }
        
        $paper_stmt->bind_param("i", $paper_id);
        
        if (!$paper_stmt->execute()) {
            error_log("Execute failed: " . $paper_stmt->error);
            header("Location: admin_review_papers.php?error=execute_failed&detail=" . urlencode($paper_stmt->error));
            exit();
        }
        
        $paper_result = $paper_stmt->get_result();
        $paper_data = $paper_result->fetch_assoc();
        
        if ($paper_data) {
            error_log("Paper found: " . $paper_data['paper_title']);
            error_log("Current status: " . $paper_data['status']);
            error_log("Current reviewer_status: " . ($paper_data['reviewer_status'] ?? 'NULL'));
            
            // Get email address
            $recipient_email = null;
            if (!empty($paper_data['author_email'])) {
                $recipient_email = $paper_data['author_email'];
            } else {
                $recipient_email = getUserEmail($paper_data['user_name'], $conn);
            }
            
            // Validate email
            if ($recipient_email && !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email format: $recipient_email");
                $recipient_email = null;
            }
            
            error_log("Recipient email: " . ($recipient_email ?? 'NULL'));
            
            // Update paper status with reviewer details
            $sql = "UPDATE paper_submissions SET 
                    status = ?, 
                    reviewer_status = ?,
                    reviewer_comments = ?, 
                    reviewer_recommendation = ?,
                    reviewed_by = ?, 
                    review_date = NOW() 
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                error_log("Update prepare failed: " . $conn->error);
                header("Location: admin_review_papers.php?error=update_prepare_failed&detail=" . urlencode($conn->error));
                exit();
            }
            
            $stmt->bind_param("sssssi", $new_status, $reviewer_status, $comments, $reviewer_recommendation, $_SESSION['username'], $paper_id);
            
            error_log("Executing update query...");
            error_log("Parameters: status=$new_status, reviewer_status=$reviewer_status, reviewer=" . $_SESSION['username']);
            
            if ($stmt->execute()) {
                $affected_rows = $stmt->affected_rows;
                error_log("Update successful! Affected rows: $affected_rows");
                
                if ($affected_rows === 0) {
                    error_log("WARNING: No rows were affected by the update!");
                }
                
                // Verify the update
                $verify_sql = "SELECT status, reviewer_status, reviewer_comments, reviewed_by, review_date FROM paper_submissions WHERE id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("i", $paper_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                $verify_data = $verify_result->fetch_assoc();
                
                error_log("Verification - New status: " . ($verify_data['status'] ?? 'NULL'));
                error_log("Verification - New reviewer_status: " . ($verify_data['reviewer_status'] ?? 'NULL'));
                error_log("Verification - Reviewed by: " . ($verify_data['reviewed_by'] ?? 'NULL'));
                error_log("Verification - Review date: " . ($verify_data['review_date'] ?? 'NULL'));
                
                $verify_stmt->close();
                
                // Log activity
                logActivity('PAPER_REVIEW_ACTION', "Paper ID: $paper_id, Status: $new_status, Reviewer Status: $reviewer_status, Admin: {$_SESSION['username']}");
                
                $email_status = 'no_email';
                
                // Send email for approve/reject/revisions
                if ($recipient_email && in_array($action, ['approve', 'reject', 'revisions'])) {
                    error_log("Attempting to send email notification...");
                    
                    $emailPaperData = array_merge($paper_data, [
                        'reviewer_comments' => $comments,
                        'reviewer_recommendation' => $reviewer_recommendation,
                        'reviewer_status' => $reviewer_status,
                        'reviewed_by' => $_SESSION['username'],
                        'status' => $new_status
                    ]);
                    
                    try {
                        $emailSent = EmailService::sendPaperReviewNotification(
                            $emailPaperData, 
                            $recipient_email, 
                            $new_status, 
                            $conn
                        );
                        
                        if ($emailSent) {
                            error_log("Email sent successfully to: $recipient_email");
                            logActivity('EMAIL_REVIEW_NOTIFICATION_SENT', "To: $recipient_email, Paper: $paper_id");
                            $email_status = 'sent';
                        } else {
                            error_log("Email send failed to: $recipient_email");
                            logError("Email send failed to: $recipient_email", 'EMAIL_SEND_FAILED');
                            $email_status = 'failed';
                        }
                    } catch (Exception $e) {
                        error_log("Email exception: " . $e->getMessage());
                        error_log("Email exception trace: " . $e->getTraceAsString());
                        logError("Email exception: " . $e->getMessage(), 'EMAIL_EXCEPTION');
                        $email_status = 'error';
                    }
                } elseif (!$recipient_email && in_array($action, ['approve', 'reject', 'revisions'])) {
                    error_log("No recipient email available");
                    $email_status = 'no_address';
                }
                
                // Log metrics
                try {
                    $metrics_sql = "INSERT INTO paper_metrics (paper_id, metric_type, created_at) VALUES (?, ?, NOW())";
                    $metrics_stmt = $conn->prepare($metrics_sql);
                    $metric_type = 'status_change';
                    $metrics_stmt->bind_param("is", $paper_id, $metric_type);
                    $metrics_stmt->execute();
                    $metrics_stmt->close();
                    error_log("Metrics logged successfully");
                } catch (Exception $e) {
                    error_log("Metrics logging failed: " . $e->getMessage());
                }
                
                error_log("=== REVIEW SUBMISSION SUCCESS ===");
                
                // Clear any output buffers
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // REDIRECT with success parameter
                header("Location: admin_review_papers.php?success=1&status_updated=$new_status&reviewer_status=$reviewer_status&email=$email_status&paper=$paper_id&timestamp=" . time());
                exit();
                
            } else {
                error_log("Update failed: " . $stmt->error);
                error_log("MySQL Error: " . $conn->error);
                error_log("MySQL Error Number: " . $conn->errno);
                header("Location: admin_review_papers.php?error=db_update&detail=" . urlencode($stmt->error));
                exit();
            }
            $stmt->close();
        } else {
            error_log("Paper not found with ID: $paper_id");
            header("Location: admin_review_papers.php?error=paper_not_found&id=$paper_id");
            exit();
        }
        $paper_stmt->close();
    }
}

// Handle success/error messages from redirect
$success_message = '';
$error_message = '';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $status_updated = $_GET['status_updated'] ?? 'unknown';
    $reviewer_status = $_GET['reviewer_status'] ?? 'unknown';
    $email_status = $_GET['email'] ?? 'unknown';
    
    $success_message = "Paper reviewed successfully! Status: '$status_updated', Review: '" . str_replace('_', ' ', $reviewer_status) . "'";
    
    switch($email_status) {
        case 'sent':
            $success_message .= " Email notification sent to author.";
            break;
        case 'failed':
            $success_message .= " However, email notification failed to send.";
            break;
        case 'error':
            $success_message .= " However, email notification encountered an error.";
            break;
        case 'no_address':
            $success_message .= " However, no email address found for the author.";
            break;
    }
}

if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'db_update':
            $error_message = "Failed to update paper status. Please try again.";
            if (isset($_GET['detail'])) {
                $error_message .= " Details: " . htmlspecialchars($_GET['detail']);
            }
            break;
        case 'paper_not_found':
            $error_message = "Paper not found.";
            if (isset($_GET['id'])) {
                $error_message .= " (ID: " . htmlspecialchars($_GET['id']) . ")";
            }
            break;
        case 'prepare_failed':
        case 'update_prepare_failed':
            $error_message = "Database prepare statement failed.";
            if (isset($_GET['detail'])) {
                $error_message .= " Details: " . htmlspecialchars($_GET['detail']);
            }
            break;
        case 'execute_failed':
            $error_message = "Database query execution failed.";
            if (isset($_GET['detail'])) {
                $error_message .= " Details: " . htmlspecialchars($_GET['detail']);
            }
            break;
        case 'invalid_action':
            $error_message = "Invalid review action specified.";
            break;
        default:
            $error_message = "An error occurred. Please try again.";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$reviewer_status_filter = $_GET['reviewer_status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build query with filters
$sql = "SELECT * FROM paper_submissions WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($reviewer_status_filter !== 'all') {
    $sql .= " AND reviewer_status = ?";
    $params[] = $reviewer_status_filter;
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
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN reviewer_status = 'revisions_requested' THEN 1 ELSE 0 END) as revisions_requested
                  FROM paper_submissions";
    $stats_result = $conn->query($stats_sql);
    if (!$stats_result) {
        throw new Exception("Stats query failed: " . $conn->error);
    }
    $stats = $stats_result->fetch_assoc();

} catch(Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Database error in admin review: " . $e->getMessage());
    logError("Database error in admin review: " . $e->getMessage(), 'DB_ERROR');
    $submissions = [];
    $stats = ['total' => 0, 'pending' => 0, 'under_review' => 0, 'approved' => 0, 'rejected' => 0, 'revisions_requested' => 0];
}

// Status badge colors function
function getStatusBadge($status) {
    switch($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'under_review':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'approved':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'rejected':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

function getReviewerStatusBadge($reviewer_status) {
    switch($reviewer_status) {
        case 'under_review':
            return 'bg-blue-50 text-blue-700 border-blue-300';
        case 'reviewer_approved':
            return 'bg-green-50 text-green-700 border-green-300';
        case 'reviewer_rejected':
            return 'bg-red-50 text-red-700 border-red-300';
        case 'revisions_requested':
            return 'bg-orange-50 text-orange-700 border-orange-300';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-300';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Papers - CNLRRS Admin</title>
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
        .pdf-viewer-container {
            width: 100%;
            height: 600px;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
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
                    <h1 class="text-xl font-bold">Paper Review Dashboard</h1>
                    <p class="text-sm opacity-75">Enhanced Review System with Detailed Feedback</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="admin_loggedin_index.php" class="flex items-center space-x-2 hover:text-yellow-200 transition">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" id="successAlert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <div>
                        <p class="font-semibold">Success!</p>
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <div>
                        <p class="font-semibold">Error</p>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-[#115D5B]"><?php echo $stats['total']; ?></div>
                <div class="text-sm text-gray-600">Total</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></div>
                <div class="text-sm text-gray-600">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['under_review']; ?></div>
                <div class="text-sm text-gray-600">Under Review</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-orange-600"><?php echo $stats['revisions_requested']; ?></div>
                <div class="text-sm text-gray-600">Revisions</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['approved']; ?></div>
                <div class="text-sm text-gray-600">Approved</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['rejected']; ?></div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Review Status</label>
                    <select name="reviewer_status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
                        <option value="all" <?php echo $reviewer_status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="under_review" <?php echo $reviewer_status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="revisions_requested" <?php echo $reviewer_status_filter === 'revisions_requested' ? 'selected' : ''; ?>>Revisions Requested</option>
                        <option value="reviewer_approved" <?php echo $reviewer_status_filter === 'reviewer_approved' ? 'selected' : ''; ?>>Reviewer Approved</option>
                        <option value="reviewer_rejected" <?php echo $reviewer_status_filter === 'reviewer_rejected' ? 'selected' : ''; ?>>Reviewer Rejected</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-md transition">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
                <div>
                    <a href="admin_review_papers.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Papers List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-list mr-2"></i>Submitted Papers 
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
                                        <div class="flex flex-col items-end space-y-1 ml-2">
                                            <span class="status-badge inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo getStatusBadge($paper['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $paper['status'])); ?>
                                            </span>
                                            <?php if ($paper['reviewer_status']): ?>
                                                <span class="status-badge inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo getReviewerStatusBadge($paper['reviewer_status']); ?>">
                                                    <i class="fas fa-clipboard-check mr-1"></i>
                                                    <?php echo ucfirst(str_replace('_', ' ', $paper['reviewer_status'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <p class="text-sm"><strong>Keywords:</strong> <?php echo htmlspecialchars($paper['keywords']); ?></p>
                                    </div>
                                    
                                    <?php if ($paper['reviewer_comments']): ?>
                                        <div class="mt-3 p-3 bg-blue-50 rounded-md border-l-4 border-blue-400">
                                            <p class="text-sm font-semibold text-blue-800">Review Comments:</p>
                                            <p class="text-sm text-gray-700 mt-1"><?php echo htmlspecialchars($paper['reviewer_comments']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($paper['reviewer_recommendation']): ?>
                                        <div class="mt-2 p-3 bg-purple-50 rounded-md border-l-4 border-purple-400">
                                            <p class="text-sm font-semibold text-purple-800">Reviewer Recommendation:</p>
                                            <p class="text-sm text-gray-700 mt-1"><?php echo htmlspecialchars($paper['reviewer_recommendation']); ?></p>
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
                                           class="action-btn bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm text-center transition">
                                            <i class="fas fa-download mr-2"></i>Download File
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="reviewPaper(<?php echo $paper['id']; ?>, '<?php echo addslashes($paper['paper_title']); ?>')" 
                                        class="action-btn bg-[#115D5B] hover:bg-[#0d4a47] text-white px-4 py-2 rounded-md text-sm transition">
                                        <i class="fas fa-gavel mr-2"></i>Review Paper
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
            <div id="paperContent" class="p-6"></div>
        </div>
    </div>

    <!-- Enhanced Review Modal -->
    <div id="reviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 my-8">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-clipboard-check mr-2"></i>Review Paper with Detailed Feedback
                    </h3>
                    <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: PDF Viewer -->
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-800">Submitted Document</h4>
                    <div id="pdfViewerContainer" class="pdf-viewer-container">
                        <iframe id="pdfViewer" class="w-full h-full rounded" frameborder="0"></iframe>
                    </div>
                    <a id="downloadPdfLink" href="#" target="_blank" class="block text-center bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-download mr-2"></i>Download PDF
                    </a>
                </div>

                <!-- Right: Enhanced Review Form -->
                <div>
                    <form method="POST" onsubmit="return confirmReview(event)">
                        <input type="hidden" name="paper_id" id="reviewPaperId">
                        
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-800 mb-2" id="reviewPaperTitle"></h4>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-600 mr-2 mt-1"></i>
                                <div class="text-sm text-blue-800">
                                    <p class="font-semibold">Review Guidelines</p>
                                    <p>Provide detailed feedback to help improve the quality of submissions. Email notifications are sent for approvals, rejections, and revision requests.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Review Comments
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea name="comments" rows="5" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                                placeholder="Provide detailed feedback on the paper's strengths, weaknesses, and areas for improvement..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                These comments will be shared with the author via email.
                            </p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Reviewer Recommendation
                            </label>
                            <textarea name="reviewer_recommendation" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                                placeholder="Optional: Summarize your overall recommendation for this paper..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                Summary of your evaluation (optional but recommended).
                            </p>
                        </div>

                        <div class="border-t pt-4 space-y-2">
                            <p class="text-sm font-semibold text-gray-700 mb-3">Review Decision:</p>
                            
                            <button type="submit" name="action" value="review" 
                                class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-md transition">
                                <i class="fas fa-eye mr-2"></i>Mark as Under Review
                                <span class="text-xs block mt-1">(No email notification)</span>
                            </button>
                            
                            <button type="submit" name="action" value="revisions" 
                                class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-3 rounded-md transition">
                                <i class="fas fa-edit mr-2"></i>Request Revisions
                                <span class="text-xs block mt-1">(Email sent with feedback)</span>
                            </button>
                            
                            <button type="submit" name="action" value="approve" 
                                class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md transition">
                                <i class="fas fa-check-circle mr-2"></i>Approve Paper
                                <span class="text-xs block mt-1">(Email sent with approval notice)</span>
                            </button>
                            
                            <button type="submit" name="action" value="reject" 
                                class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-md transition">
                                <i class="fas fa-times-circle mr-2"></i>Reject Paper
                                <span class="text-xs block mt-1">(Email sent with reasons)</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-[#115D5B] text-white text-center py-4 mt-12">
        <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
    </footer>

    <script>
        const submissions = <?php echo json_encode($submissions); ?>;

        function viewPaper(paperId) {
            const paper = submissions.find(p => p.id == paperId);
            
            if (paper) {
                let reviewerStatusHtml = '';
                if (paper.reviewer_status) {
                    const statusClass = getReviewerStatusBadgeClass(paper.reviewer_status);
                    reviewerStatusHtml = `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Reviewer Status:</h4>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${statusClass}">
                                ${paper.reviewer_status.replace(/_/g, ' ').toUpperCase()}
                            </span>
                        </div>
                    `;
                }
                
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
                                <h4 class="font-semibold text-gray-800 mb-2">Email:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.author_email || 'N/A'}</p>
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
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${getStatusBadgeClass(paper.status)}">
                                    ${paper.status.replace(/_/g, ' ').toUpperCase()}
                                </span>
                            </div>
                        </div>

                        ${reviewerStatusHtml}
                        
                        ${paper.reviewer_comments ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Review Comments:</h4>
                            <p class="text-gray-700 bg-blue-50 p-3 rounded border-l-4 border-blue-400">${paper.reviewer_comments}</p>
                        </div>
                        ` : ''}
                        
                        ${paper.reviewer_recommendation ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Reviewer Recommendation:</h4>
                            <p class="text-gray-700 bg-purple-50 p-3 rounded border-l-4 border-purple-400">${paper.reviewer_recommendation}</p>
                        </div>
                        ` : ''}
                        
                        ${paper.reviewed_by ? `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Reviewed by:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.reviewed_by}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Review Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.review_date ? new Date(paper.review_date).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                        ` : ''}

                        ${paper.file_path ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Submitted Document:</h4>
                            <div class="pdf-viewer-container">
                                <iframe src="${paper.file_path}" class="w-full h-full rounded" frameborder="0"></iframe>
                            </div>
                            <a href="${paper.file_path}" target="_blank" class="mt-2 inline-block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition">
                                <i class="fas fa-download mr-2"></i>Download Document
                            </a>
                        </div>
                        ` : ''}
                    </div>
                `;
                document.getElementById('paperModal').classList.remove('hidden');
                document.getElementById('paperModal').classList.add('flex');
            }
        }

        function reviewPaper(paperId, paperTitle) {
            const paper = submissions.find(p => p.id == paperId);
            
            document.getElementById('reviewPaperId').value = paperId;
            document.getElementById('reviewPaperTitle').textContent = paperTitle;
            
            // Load PDF in viewer
            if (paper && paper.file_path) {
                document.getElementById('pdfViewer').src = paper.file_path;
                document.getElementById('downloadPdfLink').href = paper.file_path;
            }
            
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function confirmReview(event) {
            const action = event.submitter.value;
            const comments = event.target.querySelector('[name="comments"]').value.trim();
            
            if (!comments) {
                alert('Please provide review comments before submitting.');
                event.preventDefault();
                return false;
            }
            
            const actionLabels = {
                'approve': 'approve this paper',
                'reject': 'reject this paper', 
                'review': 'mark this paper as under review',
                'revisions': 'request revisions for this paper'
            };
            
            const emailActions = ['approve', 'reject', 'revisions'];
            const willSendEmail = emailActions.includes(action);
            
            const message = willSendEmail ? 
                `Are you sure you want to ${actionLabels[action]}?\n\nAn email notification with your feedback will be sent to the author.` :
                `Are you sure you want to ${actionLabels[action]}?`;
            
            if (!confirm(message)) {
                event.preventDefault();
                return false;
            }
            
            // Show processing state
            const submitButton = event.submitter;
            const originalHTML = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            submitButton.disabled = true;
            
            // Enable all other buttons to prevent double submission
            const allButtons = event.target.querySelectorAll('button[type="submit"]');
            allButtons.forEach(btn => {
                if (btn !== submitButton) {
                    btn.disabled = true;
                }
            });
            
            return true;
        }

        function closePaperModal() {
            document.getElementById('paperModal').classList.add('hidden');
            document.getElementById('paperModal').classList.remove('flex');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.getElementById('reviewModal').classList.remove('flex');
            document.getElementById('pdfViewer').src = '';
        }

        function getStatusBadgeClass(status) {
            switch(status) {
                case 'pending': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                case 'under_review': return 'bg-blue-100 text-blue-800 border border-blue-200';
                case 'approved': return 'bg-green-100 text-green-800 border border-green-200';
                case 'rejected': return 'bg-red-100 text-red-800 border border-red-200';
                default: return 'bg-gray-100 text-gray-800 border border-gray-200';
            }
        }

        function getReviewerStatusBadgeClass(reviewerStatus) {
            switch(reviewerStatus) {
                case 'under_review': return 'bg-blue-50 text-blue-700 border border-blue-300';
                case 'reviewer_approved': return 'bg-green-50 text-green-700 border border-green-300';
                case 'reviewer_rejected': return 'bg-red-50 text-red-700 border border-red-300';
                case 'revisions_requested': return 'bg-orange-50 text-orange-700 border border-orange-300';
                default: return 'bg-gray-50 text-gray-700 border border-gray-300';
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
        const successMessage = document.getElementById('successAlert');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.5s ease-out';
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 500);
            }, 8000);
        }
    </script>
</body>
</html>