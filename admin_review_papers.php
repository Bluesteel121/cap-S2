<?php
session_start();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'reviewer'])) {
    header('Location: index.php');
    exit();
}

require_once 'connect.php';
require_once 'email_config.php';
require_once 'user_activity_logger.php';

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_paper'])) {
    header('Content-Type: application/json');
    
    try {
        $paper_id = (int)$_POST['paper_id'];
        
        // Get paper details before deletion
        $paper_sql = "SELECT * FROM paper_submissions WHERE id = ?";
        $stmt = $conn->prepare($paper_sql);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        $paper = $stmt->get_result()->fetch_assoc();
        
        if (!$paper) {
            throw new Exception('Paper not found');
        }
        
        $conn->begin_transaction();
        
        // Delete associated files
        if (!empty($paper['file_path']) && file_exists($paper['file_path'])) {
            unlink($paper['file_path']);
        }
        if (!empty($paper['previous_file_path']) && file_exists($paper['previous_file_path'])) {
            unlink($paper['previous_file_path']);
        }
        
        // Delete from paper_revisions
        $delete_revisions = "DELETE FROM paper_revisions WHERE paper_id = ?";
        $stmt = $conn->prepare($delete_revisions);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        
        // Delete from paper_metrics
        $delete_metrics = "DELETE FROM paper_metrics WHERE paper_id = ?";
        $stmt = $conn->prepare($delete_metrics);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        
        // Delete from submission_notifications
        $delete_notifs = "DELETE FROM submission_notifications WHERE paper_id = ?";
        $stmt = $conn->prepare($delete_notifs);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        
        // Delete the paper submission
        $delete_paper = "DELETE FROM paper_submissions WHERE id = ?";
        $stmt = $conn->prepare($delete_paper);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Log activity
        logActivity('PAPER_DELETED', "Paper ID: $paper_id, Title: {$paper['paper_title']}, Deleted by: {$_SESSION['username']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Paper deleted successfully'
        ]);
        
    } catch (Exception $e) {
        if ($conn) $conn->rollback();
        error_log("Paper deletion error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

// Handle REVIEW submission (existing code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $paper_id = (int)$_POST['paper_id'];
        $action = $_POST['action'];
        $reviewer_comments = trim($_POST['reviewer_comments'] ?? '');
        $reviewer_name = $_SESSION['username'];
        
        if (!in_array($action, ['approve', 'revision_requested', 'under_review'])) {
            throw new Exception('Invalid action');
        }
        
        $status_map = [
            'approve' => 'approved',
            'revision_requested' => 'revision_requested',
            'under_review' => 'under_review'
        ];
        $new_status = $status_map[$action];
        
        $paper_sql = "SELECT ps.*, a.email as user_email, a.name as author_full_name 
                      FROM paper_submissions ps 
                      LEFT JOIN accounts a ON ps.user_name = a.username 
                      WHERE ps.id = ?";
        $stmt = $conn->prepare($paper_sql);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        $paper = $stmt->get_result()->fetch_assoc();
        
        if (!$paper) {
            throw new Exception('Paper not found');
        }
        
        $user_email = $paper['user_email'] ?? $paper['author_email'] ?? null;
        if (!$user_email) {
            $email_search_sql = "SELECT email FROM accounts WHERE username = ? OR name = ? LIMIT 1";
            $email_stmt = $conn->prepare($email_search_sql);
            $email_stmt->bind_param('ss', $paper['user_name'], $paper['author_name']);
            $email_stmt->execute();
            $email_result = $email_stmt->get_result();
            
            if ($email_row = $email_result->fetch_assoc()) {
                $user_email = $email_row['email'];
            }
        }
        
        if (!$user_email) {
            error_log("WARNING: No email found for paper ID $paper_id, user: {$paper['user_name']}");
        }
        
        $conn->begin_transaction();
        
        if ($new_status === 'revision_requested') {
            $update_sql = "UPDATE paper_submissions 
                           SET status = ?, 
                               reviewer_comments = ?, 
                               reviewed_by = ?, 
                               review_date = NOW(),
                               revision_requested_date = NOW(),
                               is_revised = FALSE,
                               revision_count = revision_count + 1,
                               updated_at = NOW()
                           WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('sssi', $new_status, $reviewer_comments, $reviewer_name, $paper_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update paper status');
            }
            
            $revision_sql = "INSERT INTO paper_revisions 
                            (paper_id, revision_number, requested_by, reviewer_comments, old_file_path) 
                            VALUES (?, ?, ?, ?, ?)";
            $rev_stmt = $conn->prepare($revision_sql);
            $revision_number = ($paper['revision_count'] ?? 0) + 1;
            $rev_stmt->bind_param('iisss', $paper_id, $revision_number, $reviewer_name, 
                                  $reviewer_comments, $paper['file_path']);
            $rev_stmt->execute();
            
        } else {
            $update_sql = "UPDATE paper_submissions 
                           SET status = ?, 
                               reviewer_comments = ?, 
                               reviewed_by = ?, 
                               review_date = NOW(),
                               updated_at = NOW()
                           WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('sssi', $new_status, $reviewer_comments, $reviewer_name, $paper_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update paper status');
            }
        }
        
        $notification_type = $new_status;
        $notification_messages = [
            'approved' => 'Your paper has been approved for publication.',
            'revision_requested' => 'Your paper requires revision. Please review the feedback and submit your revised version.',
            'under_review' => 'Your paper is now under review by our team.'
        ];
        
        $notif_sql = "INSERT INTO submission_notifications 
                      (paper_id, user_name, notification_type, message, created_at) 
                      VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($notif_sql);
        $notif_message = $notification_messages[$new_status];
        $stmt->bind_param('isss', $paper_id, $paper['user_name'], $notification_type, $notif_message);
        $stmt->execute();
        
        $conn->commit();
        
        $paper['reviewed_by'] = $reviewer_name;
        $paper['review_date'] = date('F j, Y');
        $paper['reviewer_comments'] = $reviewer_comments;
        
        $email_sent = false;
        $email_error = null;
        
        if ($user_email) {
            try {
                $email_sent = EmailService::sendPaperReviewNotification(
                    $paper, 
                    $user_email, 
                    $new_status, 
                    $conn
                );
                
                if (!$email_sent) {
                    $email_error = "Email send returned false";
                    error_log("Email send failed for paper $paper_id to $user_email");
                }
            } catch (Exception $e) {
                $email_error = $e->getMessage();
                error_log("Exception sending email for paper $paper_id: " . $email_error);
            }
        } else {
            $email_error = "No valid email address found";
            error_log("Cannot send email for paper $paper_id: No email address");
        }
        
        $activity_msg = "Paper ID: $paper_id, Status: $new_status, Reviewer: $reviewer_name";
        if ($email_sent) {
            $activity_msg .= ", Email: Sent to $user_email";
        } else {
            $activity_msg .= ", Email: FAILED - $email_error";
        }
        logActivity('PAPER_REVIEWED', $activity_msg);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paper review submitted successfully',
            'new_status' => $new_status,
            'email_sent' => $email_sent,
            'email_info' => $email_sent 
                ? "Email sent to $user_email" 
                : "Warning: Email could not be sent - $email_error. The review was saved.",
            'email_to' => $user_email
        ]);
        
    } catch (Exception $e) {
        if ($conn) $conn->rollback();
        error_log("Review submission error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

// Rest of the existing code for displaying papers...
$status_filter = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';

$sql = "SELECT ps.*, 
        COALESCE(view_count.views, 0) as total_views,
        COALESCE(download_count.downloads, 0) as total_downloads,
        CASE 
            WHEN ps.revision_requested_date IS NOT NULL THEN 
                DATEDIFF(NOW(), ps.revision_requested_date)
            ELSE 0
        END as days_since_revision_request
        FROM paper_submissions ps
        LEFT JOIN (
            SELECT paper_id, COUNT(*) as views 
            FROM paper_metrics 
            WHERE metric_type = 'view' 
            GROUP BY paper_id
        ) view_count ON ps.id = view_count.paper_id
        LEFT JOIN (
            SELECT paper_id, COUNT(*) as downloads 
            FROM paper_metrics 
            WHERE metric_type = 'download' 
            GROUP BY paper_id
        ) download_count ON ps.id = download_count.paper_id
        WHERE 1=1";

if ($status_filter !== 'all') {
    $sql .= " AND ps.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $sql .= " AND (ps.paper_title LIKE '%$search_term%' 
              OR ps.author_name LIKE '%$search_term%' 
              OR ps.keywords LIKE '%$search_term%')";
}

$sql .= " ORDER BY ps.submission_date DESC";

$result = $conn->query($sql);
$papers = $result->fetch_all(MYSQLI_ASSOC);

$count_sql = "SELECT status, COUNT(*) as count FROM paper_submissions GROUP BY status";
$count_result = $conn->query($count_sql);
$status_counts = [];
while ($row = $count_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

function getStatusBadge($status) {
    switch($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
        case 'under_review':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'approved':
            return 'bg-green-100 text-green-800 border border-green-200';
        case 'revision_requested':
            return 'bg-orange-100 text-orange-800 border border-orange-200';
        case 'published':
            return 'bg-purple-100 text-purple-800 border border-purple-200';
        default:
            return 'bg-gray-100 text-gray-800 border border-gray-200';
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        .paper-card {
            transition: all 0.3s ease;
        }
        .paper-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .revision-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .tab-button {
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .tab-button.active {
            border-bottom-color: #115D5B;
            background-color: #f0fdfa;
            color: #115D5B;
        }
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white py-3 px-4 shadow-lg">
        <div class="max-w-7xl mx-auto">
            <div class="hidden md:flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="Images/CNLRRS_icon.png" alt="CNLRRS Logo" class="h-10 w-10">
                    <div>
                        <h1 class="text-2xl font-bold">Paper Review System</h1>
                        <p class="text-sm opacity-75">Review and manage submitted research papers</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">Reviewer: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_loggedin_index.php' : 'reviewer_dashboard.php'; ?>" 
                       class="flex items-center space-x-2 bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Search Papers</label>
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by title, author, keywords..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B]">
                </div>
                <div class="flex gap-2 items-end">
                    <button onclick="performSearch()" 
                            class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <button onclick="clearSearch()" 
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md mb-6 overflow-x-auto">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6">
                    <a href="?status=all" 
                       class="tab-button flex items-center py-4 px-2 text-sm font-medium <?php echo $status_filter === 'all' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        All (<?php echo array_sum($status_counts); ?>)
                    </a>
                    <a href="?status=pending" 
                       class="tab-button flex items-center py-4 px-2 text-sm font-medium <?php echo $status_filter === 'pending' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-clock text-yellow-500 mr-2"></i>
                        Pending (<?php echo $status_counts['pending'] ?? 0; ?>)
                    </a>
                    <a href="?status=under_review" 
                       class="tab-button flex items-center py-4 px-2 text-sm font-medium <?php echo $status_filter === 'under_review' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-eye text-blue-500 mr-2"></i>
                        Review (<?php echo $status_counts['under_review'] ?? 0; ?>)
                    </a>
                    <a href="?status=approved" 
                       class="tab-button flex items-center py-4 px-2 text-sm font-medium <?php echo $status_filter === 'approved' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Approved (<?php echo $status_counts['approved'] ?? 0; ?>)
                    </a>
                    <a href="?status=revision_requested" 
                       class="tab-button flex items-center py-4 px-2 text-sm font-medium <?php echo $status_filter === 'revision_requested' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-edit text-orange-500 mr-2"></i>
                        Revisions (<?php echo $status_counts['revision_requested'] ?? 0; ?>)
                    </a>
                </nav>
            </div>
        </div>

        <?php if (empty($papers)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-500 mb-2">No Papers Found</h3>
                <p class="text-gray-400">There are no papers matching your current filter.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($papers as $paper): ?>
                    <div class="paper-card bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($paper['paper_title']); ?>
                                    <?php if ($paper['status'] === 'revision_requested' && $paper['is_revised']): ?>
                                        <span class="revision-badge inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 ml-2">
                                            <i class="fas fa-check-circle mr-1"></i>Revised by Author
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-2">
                                    <span><strong>Author:</strong> <?php echo htmlspecialchars($paper['author_name']); ?></span>
                                    <span><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?></span>
                                    <span><strong>Submitted:</strong> <?php echo date('M j, Y', strtotime($paper['submission_date'])); ?></span>
                                    <?php if ($paper['status'] === 'revision_requested' && $paper['days_since_revision_request'] > 0): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-orange-100 text-orange-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo $paper['days_since_revision_request']; ?> day<?php echo $paper['days_since_revision_request'] != 1 ? 's' : ''; ?> since revision request
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($paper['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $paper['status'])); ?>
                            </span>
                        </div>

                        <div class="mb-4">
                            <p class="text-sm text-gray-700">
                                <strong>Abstract:</strong> <?php echo htmlspecialchars(substr($paper['abstract'], 0, 200)) . '...'; ?>
                            </p>
                            <p class="text-sm text-gray-600 mt-2">
                                <strong>Keywords:</strong> <?php echo htmlspecialchars($paper['keywords']); ?>
                            </p>
                        </div>

                        <?php if ($paper['reviewer_comments']): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                <p class="text-sm font-semibold text-yellow-800 mb-1">Previous Review Comments:</p>
                                <p class="text-sm text-yellow-700"><?php echo nl2br(htmlspecialchars($paper['reviewer_comments'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-2">
                            <button onclick="viewPaperDetails(<?php echo $paper['id']; ?>)" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition">
                                <i class="fas fa-info-circle mr-2"></i>Details
                            </button>
                            <button onclick="viewPaperPDF(<?php echo $paper['id']; ?>)" 
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition">
                                <i class="fas fa-file-pdf mr-2"></i>View PDF
                            </button>
                            <?php if ($paper['status'] !== 'approved'): ?>
                                <button onclick="openReviewModal(<?php echo $paper['id']; ?>, 'approve')" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                    <i class="fas fa-check mr-2"></i>Approve
                                </button>
                            <?php endif; ?>
                            <?php if ($paper['status'] !== 'under_review'): ?>
                                <button onclick="openReviewModal(<?php echo $paper['id']; ?>, 'under_review')" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                    <i class="fas fa-eye mr-2"></i>Review
                                </button>
                            <?php endif; ?>
                            <?php if ($paper['status'] !== 'revision_requested'): ?>
                                <button onclick="openReviewModal(<?php echo $paper['id']; ?>, 'revision_requested')" 
                                        class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                    <i class="fas fa-edit mr-2"></i>Request Revision
                                </button>
                            <?php endif; ?>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <button onclick="confirmDelete(<?php echo $paper['id']; ?>, '<?php echo htmlspecialchars(addslashes($paper['paper_title'])); ?>')" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                    <i class="fas fa-trash mr-2"></i>Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal (existing) -->
    <div id="reviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-6 rounded-t-xl">
                <h3 class="text-xl font-semibold" id="reviewModalTitle">
                    <i class="fas fa-edit mr-3"></i>Submit Review
                </h3>
            </div>
            <form id="reviewForm" class="p-6">
                <input type="hidden" id="reviewPaperId" name="paper_id">
                <input type="hidden" id="reviewAction" name="action">
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Review Comments:</label>
                    <textarea id="reviewerComments" name="reviewer_comments" rows="6" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B]"
                              placeholder="Provide detailed feedback for the author..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" 
                            class="flex-1 bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-3 rounded-lg transition">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Review
                    </button>
                    <button type="button" onclick="closeReviewModal()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-red-600 to-red-500 text-white p-6 rounded-t-xl">
                <h3 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>Confirm Deletion
                </h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4">Are you sure you want to delete this paper?</p>
                <p class="text-sm font-semibold text-gray-800 mb-4" id="deletePaperTitle"></p>
                <p class="text-sm text-red-600 mb-6">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    This action cannot be undone. All associated data will be permanently deleted.
                </p>
                <input type="hidden" id="deletePaperId">
                <div class="flex gap-3">
                    <button onclick="deletePaper()" 
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition">
                        <i class="fas fa-trash mr-2"></i>Yes, Delete
                    </button>
                    <button onclick="closeDeleteModal()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const papers = <?php echo json_encode($papers); ?>;

        function viewPaperDetails(paperId) {
            const paper = papers.find(p => p.id == paperId);
            if (paper) {
                alert('Paper details: ' + paper.paper_title);
                // Implement full modal view
            }
        }

        function viewPaperPDF(paperId) {
            const paper = papers.find(p => p.id == paperId);
            if (paper && paper.file_path) {
                window.open(paper.file_path, '_blank');
            }
        }

        function openReviewModal(paperId, action) {
            document.getElementById('reviewPaperId').value = paperId;
            document.getElementById('reviewAction').value = action;
            
            const titles = {
                'approve': 'Approve Paper',
                'revision_requested': 'Request Revision',
                'under_review': 'Mark as Under Review'
            };
            
            const icons = {
                'approve': 'fa-check-circle',
                'revision_requested': 'fa-edit',
                'under_review': 'fa-eye'
            };
            
            document.getElementById('reviewModalTitle').innerHTML = `
                <i class="fas ${icons[action]} mr-3"></i>${titles[action]}
            `;
            
            document.getElementById('reviewerComments').value = '';
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.getElementById('reviewModal').classList.remove('flex');
        }

        function confirmDelete(paperId, paperTitle) {
            document.getElementById('deletePaperId').value = paperId;
            document.getElementById('deletePaperTitle').textContent = paperTitle;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        async function deletePaper() {
            const paperId = document.getElementById('deletePaperId').value;
            const deleteBtn = event.target;
            const originalText = deleteBtn.innerHTML;
            
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
            deleteBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('delete_paper', '1');
                formData.append('paper_id', paperId);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Paper deleted successfully!', 'success');
                    closeDeleteModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.error || 'Failed to delete paper');
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
                deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
            }
        }

        document.getElementById('reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Review submitted successfully!', 'success');
                    closeReviewModal();
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(data.error || 'Failed to submit review');
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value;
            const currentStatus = new URLSearchParams(window.location.search).get('status') || 'pending';
            window.location.href = `?status=${currentStatus}&search=${encodeURIComponent(searchTerm)}`;
        }

        function clearSearch() {
            const currentStatus = new URLSearchParams(window.location.search).get('status') || 'pending';
            window.location.href = `?status=${currentStatus}`;
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            
            const colors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                warning: 'bg-yellow-500 text-white',
                info: 'bg-blue-500 text-white'
            };
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-triangle',
                warning: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            
            notification.className = `fixed top-4 right-4 max-w-md p-4 rounded-lg shadow-lg z-50 ${colors[type]}`;
            
            notification.innerHTML = `
                <div class="flex items-start space-x-3">
                    <i class="fas ${icons[type]} text-lg mt-1"></i>
                    <div class="flex-1">
                        <p class="font-semibold capitalize">${type}</p>
                        <p class="text-sm mt-1">${message}</p>
                    </div>
                    <button onclick="this.parentNode.parentNode.remove()" class="hover:opacity-75">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) closeReviewModal();
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeReviewModal();
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>