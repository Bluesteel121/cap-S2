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

// Initialize email templates
initializeDefaultEmailTemplates($conn);

// Function to get user email
function getUserEmail($username, $conn) {
    $sql = "SELECT email FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ? $user['email'] : null;
}

// Function to log errors
function logError($message, $type = 'ERROR') {
    error_log("[$type] " . date('Y-m-d H:i:s') . " - $message");
}

// Handle paper status updates
if ($_POST && isset($_POST['action'])) {
    $paper_id = (int)$_POST['paper_id'];
    $action = $_POST['action'];
    $comments = trim($_POST['comments'] ?? '');
    
    // Validate required fields
    if (empty($comments) && in_array($action, ['approve', 'reject', 'publish'])) {
        $error_message = "Review comments are required for approve, reject, or publish actions.";
    } else {
        $new_status = '';
        switch($action) {
            case 'approve':
                $new_status = 'approved';
                break;
            case 'reject':
                $new_status = 'rejected';
                break;
            case 'review':
                $new_status = 'under_review';
                break;
            case 'publish':
                $new_status = 'published';
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
                // Update paper status
                $sql = "UPDATE paper_submissions SET 
                        status = ?, 
                        reviewer_comments = ?, 
                        reviewed_by = ?, 
                        review_date = NOW() 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $new_status, $comments, $_SESSION['username'], $paper_id);
                
                if ($stmt->execute()) {
                    // Log the review action
                    logActivity('PAPER_REVIEW_ACTION', "Paper ID: $paper_id, Status: $new_status, Admin: {$_SESSION['username']}");
                    
                    // Send email notification to the author for certain status changes
                    if (in_array($new_status, ['approved', 'rejected', 'published'])) {
                        $userEmail = getUserEmail($paper_data['user_name'], $conn);
                        
                        if ($userEmail) {
                            try {
                                // Prepare template variables
                                $templateVariables = [
                                    'author_name' => $paper_data['author_name'],
                                    'paper_title' => $paper_data['paper_title'],
                                    'research_type' => $paper_data['research_type'],
                                    'submission_date' => date('F j, Y', strtotime($paper_data['submission_date'])),
                                    'review_date' => date('F j, Y'),
                                    'reviewed_by' => $_SESSION['username'],
                                    'reviewer_comments' => $comments,
                                    'submitted_by' => $paper_data['user_name'],
                                    'paper_url' => 'https://cnlrrs.gov.ph/view_paper.php?id=' . $paper_id
                                ];
                                
                                // Determine template type based on status
                                $template_type = '';
                                switch($new_status) {
                                    case 'approved':
                                        $template_type = 'paper_approved';
                                        break;
                                    case 'rejected':
                                        $template_type = 'paper_rejected';
                                        break;
                                    case 'published':
                                        $template_type = 'paper_published';
                                        break;
                                }
                                
                                if ($template_type) {
                                    // Get email template
                                    $template_sql = "SELECT * FROM email_templates WHERE template_type = ? AND is_active = 1";
                                    $template_stmt = $conn->prepare($template_sql);
                                    $template_stmt->bind_param("s", $template_type);
                                    $template_stmt->execute();
                                    $template_result = $template_stmt->get_result();
                                    $template = $template_result->fetch_assoc();
                                    $template_stmt->close();
                                    
                                    if ($template) {
                                        // Replace template variables
                                        $subject = $template['subject'];
                                        $body = $template['body'];
                                        
                                        foreach ($templateVariables as $key => $value) {
                                            $subject = str_replace('{{' . $key . '}}', $value, $subject);
                                            $body = str_replace('{{' . $key . '}}', $value, $body);
                                        }
                                        
                                        // Send email using EmailService
                                        if (EmailService::sendEmail($userEmail, $subject, $body)) {
                                            logActivity('EMAIL_REVIEW_NOTIFICATION_SENT', 
                                                "Review notification sent to: $userEmail, Paper ID: $paper_id, Status: $new_status");
                                            $success_message = "Paper status updated to '$new_status' successfully! Email notification sent to author ($userEmail).";
                                        } else {
                                            logError("Failed to send review notification email to: $userEmail for paper ID: $paper_id", 'EMAIL_SEND_FAILED');
                                            $success_message = "Paper status updated to '$new_status' successfully! (Email notification failed to send to $userEmail)";
                                        }
                                    } else {
                                        logError("No active email template found for template_type: $template_type", 'EMAIL_TEMPLATE_MISSING');
                                        $success_message = "Paper status updated to '$new_status' successfully! (No email template configured for this status)";
                                    }
                                }
                            } catch (Exception $e) {
                                logError("Email sending error: " . $e->getMessage(), 'EMAIL_ERROR');
                                $success_message = "Paper status updated to '$new_status' successfully! (Email error: " . $e->getMessage() . ")";
                            }
                        } else {
                            logError("User email not found for username: " . $paper_data['user_name'], 'USER_EMAIL_NOT_FOUND');
                            $success_message = "Paper status updated to '$new_status' successfully! (Author email not found)";
                        }
                    } else {
                        // For 'under_review' status, no email is sent
                        $success_message = "Paper status updated to '$new_status' successfully!";
                    }
                    
                    // Log metrics
                    try {
                        $metrics_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_name, metric_date, additional_data) VALUES (?, ?, ?, NOW(), ?)";
                        $metrics_stmt = $conn->prepare($metrics_sql);
                        $metric_type = 'status_change';
                        $additional_data = json_encode([
                            'old_status' => $paper_data['status'], 
                            'new_status' => $new_status, 
                            'admin' => $_SESSION['username'],
                            'comments' => $comments
                        ]);
                        $metrics_stmt->bind_param("isss", $paper_id, $metric_type, $_SESSION['username'], $additional_data);
                        $metrics_stmt->execute();
                        $metrics_stmt->close();
                    } catch (Exception $e) {
                        logError("Failed to log metrics: " . $e->getMessage(), 'METRICS_ERROR');
                    }
                    
                } else {
                    $error_message = "Failed to update paper status: " . $conn->error;
                    logError("Database error updating paper status: " . $conn->error, 'DB_UPDATE_FAILED');
                }
                $stmt->close();
            } else {
                $error_message = "Paper not found.";
                logError("Paper not found for ID: $paper_id", 'PAPER_NOT_FOUND');
            }
            $paper_stmt->close();
        } else {
            $error_message = "Invalid action specified.";
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
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

    // Get statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published
                  FROM paper_submissions";
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result->fetch_assoc();

} catch(Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    logError("Database error in admin review: " . $e->getMessage(), 'DB_ERROR');
    $submissions = [];
    $stats = ['total' => 0, 'pending' => 0, 'under_review' => 0, 'approved' => 0, 'rejected' => 0, 'published' => 0];
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
        case 'published':
            return 'bg-purple-100 text-purple-800 border-purple-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

// Test email configuration
$email_config_status = "Active";
$email_config_message = "Email system configured with templates";
try {
    // Check if email templates exist
    $template_check_sql = "SELECT COUNT(*) as template_count FROM email_templates WHERE is_active = 1";
    $template_check_result = $conn->query($template_check_sql);
    $template_count = $template_check_result->fetch_assoc()['template_count'];
    
    if ($template_count < 3) {
        $email_config_status = "Warning";
        $email_config_message = "Only $template_count active email templates found. Some notifications may not work.";
    }
} catch (Exception $e) {
    $email_config_status = "Error";
    $email_config_message = "Email configuration check failed: " . $e->getMessage();
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
        .email-indicator {
            animation: pulse 2s infinite;
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
                    <p class="text-sm opacity-75">Admin Panel - Review Submissions with Email Notifications</p>
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
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <div>
                        <p class="font-semibold">Success!</p>
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Email System Status -->
        <div class="<?php echo $email_config_status === 'Active' ? 'bg-blue-50 border-blue-200 text-blue-800' : 
                             ($email_config_status === 'Warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 
                              'bg-red-50 border-red-200 text-red-800'); ?> border px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-envelope<?php echo $email_config_status === 'Active' ? '-open email-indicator' : 
                                                  ($email_config_status === 'Warning' ? '-open' : ''); ?> mr-3"></i>
                <div>
                    <p class="font-semibold">Email System Status: <?php echo $email_config_status; ?></p>
                    <p class="text-sm"><?php echo $email_config_message; ?></p>
                    <a href="admin_email_templates.php" class="<?php echo $email_config_status === 'Active' ? 'text-blue-600 hover:text-blue-800' : 
                                                                       ($email_config_status === 'Warning' ? 'text-yellow-600 hover:text-yellow-800' : 
                                                                        'text-red-600 hover:text-red-800'); ?> text-sm underline">
                        Manage Email Templates
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
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
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['approved']; ?></div>
                <div class="text-sm text-gray-600">Approved</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['rejected']; ?></div>
                <div class="text-sm text-gray-600">Rejected</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-purple-600"><?php echo $stats['published']; ?></div>
                <div class="text-sm text-gray-600">Published</div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
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
                                        <h4 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($paper['paper_title']); ?>
                                        </h4>
                                        <div class="flex items-center space-x-2 ml-2">
                                            <span class="status-badge inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo getStatusBadge($paper['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $paper['status'])); ?>
                                            </span>
                                            <?php if ($paper['reviewer_comments'] && in_array($paper['status'], ['approved', 'rejected', 'published'])): ?>
                                                <i class="fas fa-envelope text-green-600" title="Email notification sent"></i>
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
                                        <div class="mt-3 p-3 bg-gray-50 rounded-md border-l-4 border-blue-400">
                                            <p class="text-sm"><strong>Review Comments:</strong></p>
                                            <p class="text-sm text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($paper['reviewer_comments'])); ?></p>
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
                                        <i class="fas fa-gavel mr-2"></i>Review & Email
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
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-envelope mr-2"></i>Review Paper & Send Email
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

                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-yellow-600 mr-2 mt-1"></i>
                        <div class="text-sm text-yellow-800">
                            <p class="font-semibold">Email Notification</p>
                            <p>The author will automatically receive an email notification when you approve, reject, or publish this paper. Comments are required for these actions.</p>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Review Comments
                        <span class="text-red-500" id="commentsRequired">*</span>
                    </label>
                    <textarea name="comments" id="reviewComments" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                        placeholder="Enter your review comments here... These will be included in the email notification for approve/reject/publish actions."></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Your comments will be included in the email notification for approve, reject, and publish actions.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="action" value="review" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-eye mr-2"></i>Set Under Review
                        <span class="text-xs block">(No email sent)</span>
                    </button>
                    <button type="submit" name="action" value="approve" 
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-check mr-2"></i>Approve
                        <span class="text-xs block">& Send Email</span>
                    </button>
                    <button type="submit" name="action" value="reject" 
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-times mr-2"></i>Reject
                        <span class="text-xs block">& Send Email</span>
                    </button>
                    <button type="submit" name="action" value="publish" 
                        class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-globe mr-2"></i>Publish
                        <span class="text-xs block">& Send Email</span>
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

        function viewPaper(paperId) {
            const paper = submissions.find(p => p.id == paperId);
            
            if (paper) {
                document.getElementById('paperContent').innerHTML = `
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Title:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${escapeHtml(paper.paper_title)}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Research Type:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${escapeHtml(paper.research_type)}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Primary Author:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${escapeHtml(paper.author_name)}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Submitted by:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${escapeHtml(paper.user_name)}</p>
                            </div>
                        </div>

                        ${paper.co_authors ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Co-Authors:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded">${escapeHtml(paper.co_authors)}</p>
                        </div>
                        ` : ''}
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Keywords:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded">${escapeHtml(paper.keywords)}</p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Abstract:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded whitespace-pre-wrap">${escapeHtml(paper.abstract)}</p>
                        </div>
                        
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
                        
                        ${paper.reviewer_comments ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Previous Review Comments:</h4>
                            <p class="text-gray-700 bg-yellow-50 p-3 rounded border-l-4 border-yellow-400 whitespace-pre-wrap">${escapeHtml(paper.reviewer_comments)}</p>
                        </div>
                        ` : ''}
                        
                        ${paper.reviewed_by ? `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Reviewed by:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${escapeHtml(paper.reviewed_by)}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Review Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded">${paper.review_date ? new Date(paper.review_date).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                        ` : ''}

                        <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                            <h4 class="font-semibold text-blue-800 mb-2">
                                <i class="fas fa-envelope mr-2"></i>Email Notification Status
                            </h4>
                            <p class="text-sm text-blue-700">
                                ${paper.reviewer_comments && ['approved', 'rejected', 'published'].includes(paper.status) ? 
                                    'Email notifications have been sent to the author for this paper.' : 
                                    'Author will receive email notifications when paper status is updated to approved, rejected, or published.'
                                }
                            </p>
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
            document.getElementById('reviewComments').value = '';
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function confirmReview(event) {
            const action = event.submitter.value;
            const comments = document.getElementById('reviewComments').value.trim();
            
            // Check if comments are required for certain actions
            const emailActions = ['approve', 'reject', 'publish'];
            if (emailActions.includes(action) && !comments) {
                alert('Review comments are required for approve, reject, and publish actions as they will be included in the email notification.');
                event.preventDefault();
                return false;
            }
            
            const actionLabels = {
                'approve': 'approve and send notification email to',
                'reject': 'reject and send notification email to', 
                'publish': 'publish and send notification email to',
                'review': 'set under review for'
            };
            
            const willSendEmail = emailActions.includes(action);
            const message = willSendEmail ? 
                `Are you sure you want to ${actionLabels[action]} the author? This action cannot be easily undone.` :
                `Are you sure you want to ${actionLabels[action]} this paper?`;
            
            if (!confirm(message)) {
                event.preventDefault();
                return false;
            }
            
            // Show processing state
            const submitButton = event.submitter;
            const originalHTML = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
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

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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

        // Auto-hide success message after delay
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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaperModal();
                closeReviewModal();
            }
        });
    </script>
</body>
</html>