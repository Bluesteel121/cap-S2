<?php
session_start();

// Check if user is logged in and has admin/reviewer role
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'reviewer'])) {
    header('Location: index.php');
    exit();
}

require_once 'connect.php';
require_once 'email_config.php';
require_once 'user_activity_logger.php';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $paper_id = (int)$_POST['paper_id'];
        $action = $_POST['action'];
        $reviewer_comments = trim($_POST['reviewer_comments'] ?? '');
        $reviewer_name = $_SESSION['username'];
        
        // Validate action
        if (!in_array($action, ['approve', 'reject', 'under_review'])) {
            throw new Exception('Invalid action');
        }
        
        // Map action to status
        $status_map = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'under_review' => 'under_review'
        ];
        $new_status = $status_map[$action];
        
        // Get paper details
        $paper_sql = "SELECT ps.*, a.email FROM paper_submissions ps 
                      LEFT JOIN accounts a ON ps.user_name = a.username 
                      WHERE ps.id = ?";
        $stmt = $conn->prepare($paper_sql);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        $paper = $stmt->get_result()->fetch_assoc();
        
        if (!$paper) {
            throw new Exception('Paper not found');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update paper status
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
        
        // Create notification
        $notification_type = $new_status;
        $notification_messages = [
            'approved' => 'Your paper has been approved for publication.',
            'rejected' => 'Your paper requires revision. Please review the feedback.',
            'under_review' => 'Your paper is now under review by our team.'
        ];
        
        $notif_sql = "INSERT INTO submission_notifications 
                      (paper_id, user_name, notification_type, message, created_at) 
                      VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($notif_sql);
        $notif_message = $notification_messages[$new_status];
        $stmt->bind_param('isss', $paper_id, $paper['user_name'], $notification_type, $notif_message);
        $stmt->execute();
        
        // Send email notification
        $user_email = $paper['email'] ?? $paper['author_email'];
        if ($user_email) {
            $paper['reviewed_by'] = $reviewer_name;
            $paper['review_date'] = date('F j, Y');
            EmailService::sendPaperReviewNotification($paper, $user_email, $new_status, $conn);
        }
        
        // Log activity
        logActivity('PAPER_REVIEWED', "Paper ID: $paper_id, Status: $new_status, Reviewer: $reviewer_name");
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Paper review submitted successfully',
            'new_status' => $new_status
        ]);
        
    } catch (Exception $e) {
        if ($conn) $conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT ps.*, 
        COALESCE(view_count.views, 0) as total_views,
        COALESCE(download_count.downloads, 0) as total_downloads
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

// Get counts for status tabs
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
        case 'rejected':
            return 'bg-red-100 text-red-800 border border-red-200';
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
        .pdf-viewer-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            background: #000;
            display: flex;
            flex-direction: column;
        }
        .pdf-viewer-fullscreen.hidden {
            display: none;
        }
        .pdf-canvas-container {
            flex: 1;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background: #333;
            padding: 20px;
        }
        .pdf-canvas-container canvas {
            border: 1px solid #555;
            background: white;
            max-width: 100%;
            height: auto;
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
        
        /* Mobile menu styles */
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .mobile-menu.open {
            transform: translateX(0);
        }
        
        /* Responsive tabs */
        @media (max-width: 768px) {
            .tab-button {
                font-size: 0.75rem;
                padding: 0.75rem 0.5rem;
            }
            .pdf-canvas-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Header -->
    <header class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white py-3 px-4 shadow-lg">
        <div class="max-w-7xl mx-auto">
            <!-- Mobile View -->
            <div class="flex items-center justify-between md:hidden">
                <button onclick="toggleMobileMenu()" class="text-white p-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <img src="Images/CNLRRS_icon.png" alt="Logo" class="h-8 w-auto">
                    <span class="text-sm font-bold">CNLRRS</span>
                </div>
                <div class="w-8"></div>
            </div>
            
            <!-- Desktop View -->
            <div class="hidden md:flex items-center justify-between">
                <div class="flex items-center space-x-4">
<<<<<<< HEAD
                    <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
=======
                    <img src="Images/Logo.png" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
>>>>>>> f45e10da8af2b1313eda66040e71a60f38f3366c
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

    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="toggleMobileMenu()"></div>
    
    <!-- Mobile Sidebar Menu -->
    <div id="mobileMenu" class="mobile-menu fixed top-0 left-0 h-full w-64 bg-white z-50 shadow-xl md:hidden">
        <div class="p-4 bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold">Menu</h2>
                <button onclick="toggleMobileMenu()" class="text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <p class="text-sm opacity-75">Reviewer: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        <nav class="p-4">
            <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_loggedin_index.php' : 'reviewer_dashboard.php'; ?>" 
               class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-arrow-left text-[#115D5B]"></i>
                <span>Back to Dashboard</span>
            </a>
        </nav>
    </div>

    <div class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-8">
        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
            <div class="flex flex-col gap-3 sm:gap-4">
                <div class="flex-1">
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">Search Papers</label>
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by title, author, keywords..."
                           class="w-full px-3 sm:px-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B]">
                </div>
                <div class="flex gap-2">
                    <button onclick="performSearch()" 
                            class="flex-1 bg-[#115D5B] hover:bg-[#0d4a47] text-white px-4 py-2 rounded-lg transition text-sm sm:text-base">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <button onclick="clearSearch()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition text-sm sm:text-base">
                        <i class="fas fa-times mr-2"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="bg-white rounded-lg shadow-md mb-4 sm:mb-6 overflow-x-auto">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-2 sm:space-x-8 px-3 sm:px-6 min-w-max">
                    <a href="?status=all" 
                       class="tab-button flex items-center py-3 sm:py-4 px-2 text-xs sm:text-sm font-medium whitespace-nowrap <?php echo $status_filter === 'all' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        All (<?php echo array_sum($status_counts); ?>)
                    </a>
                    <a href="?status=pending" 
                       class="tab-button flex items-center py-3 sm:py-4 px-2 text-xs sm:text-sm font-medium whitespace-nowrap <?php echo $status_filter === 'pending' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-clock text-yellow-500 mr-1 sm:mr-2"></i>
                        Pending (<?php echo $status_counts['pending'] ?? 0; ?>)
                    </a>
                    <a href="?status=under_review" 
                       class="tab-button flex items-center py-3 sm:py-4 px-2 text-xs sm:text-sm font-medium whitespace-nowrap <?php echo $status_filter === 'under_review' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-eye text-blue-500 mr-1 sm:mr-2"></i>
                        Review (<?php echo $status_counts['under_review'] ?? 0; ?>)
                    </a>
                    <a href="?status=approved" 
                       class="tab-button flex items-center py-3 sm:py-4 px-2 text-xs sm:text-sm font-medium whitespace-nowrap <?php echo $status_filter === 'approved' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-check-circle text-green-500 mr-1 sm:mr-2"></i>
                        Approved (<?php echo $status_counts['approved'] ?? 0; ?>)
                    </a>
                    <a href="?status=rejected" 
                       class="tab-button flex items-center py-3 sm:py-4 px-2 text-xs sm:text-sm font-medium whitespace-nowrap <?php echo $status_filter === 'rejected' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                        <i class="fas fa-times-circle text-red-500 mr-1 sm:mr-2"></i>
                        Rejected (<?php echo $status_counts['rejected'] ?? 0; ?>)
                    </a>
                </nav>
            </div>
        </div>

        <!-- Papers List -->
        <?php if (empty($papers)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 sm:p-12 text-center">
                <i class="fas fa-inbox text-4xl sm:text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-500 mb-2">No Papers Found</h3>
                <p class="text-sm sm:text-base text-gray-400">There are no papers matching your current filter.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($papers as $paper): ?>
                    <div class="paper-card bg-white rounded-lg shadow-md p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start mb-4 gap-3">
                            <div class="flex-1 w-full">
                                <h3 class="text-base sm:text-xl font-bold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($paper['paper_title']); ?>
                                </h3>
                                <div class="flex flex-col sm:flex-row sm:flex-wrap items-start sm:items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-600 mb-2">
                                    <span><strong>Author:</strong> <?php echo htmlspecialchars($paper['author_name']); ?></span>
                                    <span><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?></span>
                                    <span><strong>Submitted:</strong> <?php echo date('M j, Y', strtotime($paper['submission_date'])); ?></span>
                                </div>
                                <?php if ($paper['reviewed_by']): ?>
                                    <div class="text-xs sm:text-sm text-gray-600">
                                        <strong>Reviewed by:</strong> <?php echo htmlspecialchars($paper['reviewed_by']); ?> 
                                        on <?php echo date('M j, Y', strtotime($paper['review_date'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="inline-flex px-2 sm:px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap <?php echo getStatusBadge($paper['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $paper['status'])); ?>
                            </span>
                        </div>

                        <div class="mb-4">
                            <p class="text-xs sm:text-sm text-gray-700 line-clamp-3">
                                <strong>Abstract:</strong> <?php echo htmlspecialchars(substr($paper['abstract'], 0, 200)) . '...'; ?>
                            </p>
                            <p class="text-xs sm:text-sm text-gray-600 mt-2">
                                <strong>Keywords:</strong> <?php echo htmlspecialchars($paper['keywords']); ?>
                            </p>
                        </div>

                        <?php if ($paper['reviewer_comments']): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 sm:p-4 mb-4">
                                <p class="text-xs sm:text-sm font-semibold text-yellow-800 mb-1">Previous Review Comments:</p>
                                <p class="text-xs sm:text-sm text-yellow-700"><?php echo nl2br(htmlspecialchars($paper['reviewer_comments'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-2">
                            <button onclick="viewPaperDetails(<?php echo $paper['id']; ?>)" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition">
                                <i class="fas fa-info-circle mr-1 sm:mr-2"></i>Details
                            </button>
                            <button onclick="viewPaperPDF(<?php echo $paper['id']; ?>)" 
                                    class="bg-green-500 hover:bg-green-600 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition">
                                <i class="fas fa-file-pdf mr-1 sm:mr-2"></i>View
                            </button>
                            <button onclick="openFullscreenPDF(<?php echo $paper['id']; ?>)" 
                                    class="bg-purple-500 hover:bg-purple-600 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition">
                                <i class="fas fa-expand mr-1 sm:mr-2"></i>Full
                            </button>
                            <?php if ($paper['status'] !== 'approved'): ?>
                                <button onclick="openReviewModal(<?php echo $paper['id']; ?>, 'approve')" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition">
                                    <i class="fas fa-check mr-1 sm:mr-2"></i>Approve
                                </button>
                            <?php endif; ?>
                            <?php if ($paper['status'] !== 'under_review'): ?>
                                <button onclick="openReviewModal(<?php echo $paper['id']; ?>, 'under_review')" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition">
                                    <i class="fas fa-eye mr-1 sm:mr-2"></i>Review
                                </button>
                            <?php endif; ?>
                            <?php if ($paper['status'] !== 'rejected'): ?>
                                <button onclick="openReviewModal(<?php echo $paper['id']; ?>, 'reject')" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm transition">
                                    <i class="fas fa-times mr-1 sm:mr-2"></i>Reject
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Paper Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-4 sm:p-6 rounded-t-xl sticky top-0 z-10">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg sm:text-xl font-semibold">
                        <i class="fas fa-file-alt mr-2 sm:mr-3"></i>Paper Details
                    </h3>
                    <button onclick="closeDetailsModal()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="detailsContent" class="p-4 sm:p-6"></div>
        </div>
    </div>

    <!-- PDF Viewer Modal -->
    <div id="pdfModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-2 sm:p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full h-[95vh] sm:h-[90vh] flex flex-col">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-3 sm:p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-sm sm:text-lg font-semibold">
                    <i class="fas fa-file-pdf mr-2"></i>Paper Viewer
                </h3>
                <div class="flex gap-2">
                    <button onclick="openFullscreenPDFFromModal()" 
                            class="bg-white bg-opacity-20 hover:bg-opacity-30 px-2 sm:px-3 py-1 rounded transition text-xs sm:text-sm">
                        <i class="fas fa-expand mr-1"></i><span class="hidden sm:inline">Fullscreen</span>
                    </button>
                    <button onclick="closePDFModal()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-lg sm:text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="pdfContent" class="flex-1 bg-gray-100 flex items-center justify-center overflow-auto">
                <div id="pdfStatus" class="text-center">
                    <i class="fas fa-spinner fa-spin text-3xl sm:text-4xl text-gray-400 mb-4"></i>
                    <p class="text-sm sm:text-base text-gray-500">Loading PDF...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-4 sm:p-6 rounded-t-xl sticky top-0 z-10">
                <h3 class="text-lg sm:text-xl font-semibold" id="reviewModalTitle">
                    <i class="fas fa-edit mr-2 sm:mr-3"></i>Submit Review
                </h3>
            </div>
            <form id="reviewForm" class="p-4 sm:p-6">
                <input type="hidden" id="reviewPaperId" name="paper_id">
                <input type="hidden" id="reviewAction" name="action">
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Review Comments:</label>
                    <textarea id="reviewerComments" name="reviewer_comments" rows="6" required
                              class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B]"
                              placeholder="Provide detailed feedback for the author..."></textarea>
                    <p class="text-xs sm:text-sm text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        This feedback will be sent to the author via email.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" 
                            class="w-full sm:w-auto bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-3 rounded-lg transition text-sm sm:text-base">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Review
                    </button>
                    <button type="button" onclick="closeReviewModal()" 
                            class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition text-sm sm:text-base">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Fullscreen PDF Viewer -->
    <div id="fullscreenPDF" class="pdf-viewer-fullscreen hidden">
        <div class="absolute top-2 sm:top-4 right-2 sm:right-4 z-10 flex flex-wrap gap-2 justify-end">
            <div class="bg-gray-800 px-2 sm:px-3 py-1 sm:py-2 rounded-lg text-white text-xs sm:text-sm">
                Page <span id="pageNum">1</span> / <span id="pageCount">1</span>
            </div>
            <button onclick="previousPage()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 sm:px-3 py-1 sm:py-2 rounded-lg transition">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button onclick="nextPage()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 sm:px-3 py-1 sm:py-2 rounded-lg transition">
                <i class="fas fa-chevron-right"></i>
            </button>
            <button onclick="downloadCurrentPDF()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-3 sm:px-4 py-1 sm:py-2 rounded-lg transition text-xs sm:text-sm">
                <i class="fas fa-download mr-1 sm:mr-2"></i><span class="hidden sm:inline">Download</span>
            </button>
            <button onclick="closeFullscreenPDF()" 
                    class="bg-red-600 hover:bg-red-700 text-white px-3 sm:px-4 py-1 sm:py-2 rounded-lg transition text-xs sm:text-sm">
                <i class="fas fa-times mr-1 sm:mr-2"></i><span class="hidden sm:inline">Close</span>
            </button>
        </div>
        <div class="pdf-canvas-container">
            <canvas id="pdfCanvas"></canvas>
        </div>
    </div>

    <script>
        // Set up PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const papers = <?php echo json_encode($papers); ?>;
        let currentPaperId = null;
        let currentPdfDoc = null;
        let currentPageNum = 1;
        let currentPageRendering = false;
        let pageNumPending = null;

        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            menu.classList.toggle('open');
            overlay.classList.toggle('hidden');
        }

        function viewPaperDetails(paperId) {
            const paper = papers.find(p => p.id == paperId);
            if (!paper) return;

            document.getElementById('detailsContent').innerHTML = `
                <div class="space-y-4 sm:space-y-6">
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2 text-sm sm:text-base">Abstract:</h4>
                        <div class="text-xs sm:text-sm text-gray-700 bg-gray-50 p-3 sm:p-4 rounded-lg max-h-60 overflow-y-auto">${escapeHtml(paper.abstract)}</div>
                    </div>

                    ${paper.methodology ? `
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2 text-sm sm:text-base">Methodology:</h4>
                        <div class="text-xs sm:text-sm text-gray-700 bg-gray-50 p-3 sm:p-4 rounded-lg">${escapeHtml(paper.methodology)}</div>
                    </div>
                    ` : ''}

                    ${paper.funding_source ? `
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2 text-sm sm:text-base">Funding Source:</h4>
                        <p class="text-xs sm:text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.funding_source)}</p>
                    </div>
                    ` : ''}

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2 text-sm sm:text-base">Submission Date:</h4>
                            <p class="text-xs sm:text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">${new Date(paper.submission_date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2 text-sm sm:text-base">Current Status:</h4>
                            <span class="inline-flex px-2 sm:px-3 py-1 sm:py-2 text-xs sm:text-sm font-semibold rounded-full ${getStatusBadgeClass(paper.status)}">
                                ${paper.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>

                    ${paper.reviewer_comments ? `
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2 text-sm sm:text-base">Previous Review Comments:</h4>
                        <div class="text-xs sm:text-sm text-gray-700 bg-yellow-50 p-3 sm:p-4 rounded-lg border-l-4 border-yellow-400">${escapeHtml(paper.reviewer_comments)}</div>
                        ${paper.reviewed_by ? `<p class="text-xs sm:text-sm text-gray-500 mt-2">Reviewed by: ${escapeHtml(paper.reviewed_by)} on ${new Date(paper.review_date).toLocaleDateString()}</p>` : ''}
                    </div>
                    ` : ''}

                    <div class="bg-blue-50 border border-blue-200 p-3 sm:p-4 rounded-lg">
                        <h4 class="font-semibold text-blue-800 mb-2 text-sm sm:text-base">
                            <i class="fas fa-chart-line mr-2"></i>Paper Metrics
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="text-xl sm:text-2xl font-bold text-blue-600">${paper.total_views || 0}</div>
                                <div class="text-xs sm:text-sm text-gray-600">Total Views</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl sm:text-2xl font-bold text-green-600">${paper.total_downloads || 0}</div>
                                <div class="text-xs sm:text-sm text-gray-600">Downloads</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t">
                        <button onclick="viewPaperPDF(${paper.id})" 
                                class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition text-sm sm:text-base">
                            <i class="fas fa-file-pdf mr-2"></i>View Paper
                        </button>
                        <button onclick="openFullscreenPDF(${paper.id})" 
                                class="w-full sm:w-auto bg-purple-500 hover:bg-purple-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg transition text-sm sm:text-base">
                            <i class="fas fa-expand mr-2"></i>Fullscreen View
                        </button>
                    </div>
                </div>
            `;

            document.getElementById('detailsModal').classList.remove('hidden');
            document.getElementById('detailsModal').classList.add('flex');
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
            document.getElementById('detailsModal').classList.remove('flex');
        }

        function getPaperFilePath(paperId) {
            const paper = papers.find(p => p.id == paperId);
            return paper ? paper.file_path : null;
        }

        function renderPage(num) {
            if (!currentPdfDoc) return;
            
            currentPageRendering = true;
            currentPdfDoc.getPage(num).then(function(page) {
                const container = document.querySelector('.pdf-canvas-container');
                const containerWidth = container.clientWidth - 40; // padding
                const viewport = page.getViewport({ scale: 1 });
                const scale = Math.min(containerWidth / viewport.width, 2.0);
                const scaledViewport = page.getViewport({ scale: scale });
                
                const canvas = document.getElementById('pdfCanvas');
                const context = canvas.getContext('2d');
                
                canvas.width = scaledViewport.width;
                canvas.height = scaledViewport.height;

                const renderContext = {
                    canvasContext: context,
                    viewport: scaledViewport
                };

                page.render(renderContext).promise.then(function() {
                    currentPageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });

            document.getElementById('pageNum').textContent = num;
        }

        function queuePage(num) {
            if (num < 1 || num > currentPdfDoc.numPages) {
                return;
            }
            currentPageNum = num;
            if (currentPageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function nextPage() {
            queuePage(currentPageNum + 1);
        }

        function previousPage() {
            queuePage(currentPageNum - 1);
        }

        function viewPaperPDF(paperId) {
            currentPaperId = paperId;
            const filePath = getPaperFilePath(paperId);
            
            if (!filePath) {
                showNotification('Error: Paper file path not found', 'error');
                return;
            }

            document.getElementById('pdfStatus').innerHTML = '<i class="fas fa-spinner fa-spin text-3xl sm:text-4xl text-gray-400 mb-4"></i><p class="text-sm sm:text-base text-gray-500">Loading PDF...</p>';
            document.getElementById('pdfModal').classList.remove('hidden');
            document.getElementById('pdfModal').classList.add('flex');

            const loadingTask = pdfjsLib.getDocument(filePath);
            loadingTask.promise.then(function(pdf) {
                const pdfContent = document.getElementById('pdfContent');
                pdfContent.innerHTML = '<div style="width: 100%; height: 100%; display: flex; justify-content: center; align-items: center;"><embed src="' + filePath + '" type="application/pdf" width="100%" height="100%" /></div>';
            }).catch(function(error) {
                showNotification('Error loading PDF: ' + error.message, 'error');
                console.error('PDF load error:', error);
            });
        }

        function closePDFModal() {
            document.getElementById('pdfModal').classList.add('hidden');
            document.getElementById('pdfModal').classList.remove('flex');
            document.getElementById('pdfContent').innerHTML = '';
        }

        function openFullscreenPDF(paperId) {
            currentPaperId = paperId;
            const filePath = getPaperFilePath(paperId);
            
            if (!filePath) {
                showNotification('Error: Paper file path not found', 'error');
                return;
            }

            document.getElementById('fullscreenPDF').classList.remove('hidden');
            const loadingTask = pdfjsLib.getDocument(filePath);
            
            loadingTask.promise.then(function(pdf) {
                currentPdfDoc = pdf;
                document.getElementById('pageCount').textContent = pdf.numPages;
                currentPageNum = 1;
                renderPage(currentPageNum);
            }).catch(function(error) {
                showNotification('Error loading PDF: ' + error.message, 'error');
                console.error('PDF load error:', error);
                document.getElementById('fullscreenPDF').classList.add('hidden');
            });
        }

        function openFullscreenPDFFromModal() {
            closePDFModal();
            if (currentPaperId) {
                openFullscreenPDF(currentPaperId);
            }
        }

        function closeFullscreenPDF() {
            document.getElementById('fullscreenPDF').classList.add('hidden');
            currentPdfDoc = null;
            currentPageNum = 1;
            currentPageRendering = false;
            pageNumPending = null;
        }

        function downloadCurrentPDF() {
            if (currentPaperId) {
                const filePath = getPaperFilePath(currentPaperId);
                if (filePath) {
                    const link = document.createElement('a');
                    link.href = filePath;
                    link.download = 'paper.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }
        }

        function openReviewModal(paperId, action) {
            currentPaperId = paperId;
            document.getElementById('reviewPaperId').value = paperId;
            document.getElementById('reviewAction').value = action;
            
            const titles = {
                'approve': 'Approve Paper',
                'reject': 'Reject Paper / Request Revisions',
                'under_review': 'Mark as Under Review'
            };
            
            const icons = {
                'approve': 'fa-check-circle',
                'reject': 'fa-times-circle',
                'under_review': 'fa-eye'
            };
            
            document.getElementById('reviewModalTitle').innerHTML = `
                <i class="fas ${icons[action]} mr-2 sm:mr-3"></i>${titles[action]}
            `;
            
            document.getElementById('reviewerComments').value = '';
            document.getElementById('reviewModal').classList.remove('hidden');
            document.getElementById('reviewModal').classList.add('flex');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.getElementById('reviewModal').classList.remove('flex');
        }

        // Review form submission
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
                    showNotification('Review submitted successfully! Email sent to author.', 'success');
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

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        function getStatusBadgeClass(status) {
            switch(status) {
                case 'pending': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                case 'under_review': return 'bg-blue-100 text-blue-800 border border-blue-200';
                case 'approved': return 'bg-green-100 text-green-800 border border-green-200';
                case 'rejected': return 'bg-red-100 text-red-800 border border-red-200';
                default: return 'bg-gray-100 text-gray-800 border border-gray-200';
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, m => map[m]);
        }

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
            
            notification.className = `fixed top-4 right-4 max-w-sm sm:max-w-md p-3 sm:p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${colors[type]}`;
            
            notification.innerHTML = `
                <div class="flex items-start space-x-2 sm:space-x-3">
                    <i class="fas ${icons[type]} text-base sm:text-lg mt-1"></i>
                    <div class="flex-1">
                        <p class="font-semibold capitalize text-sm sm:text-base">${type}</p>
                        <p class="text-xs sm:text-sm mt-1">${message}</p>
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

        // Close modals when clicking outside
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) closeDetailsModal();
        });

        document.getElementById('pdfModal').addEventListener('click', function(e) {
            if (e.target === this) closePDFModal();
        });

        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) closeReviewModal();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetailsModal();
                closePDFModal();
                closeReviewModal();
                closeFullscreenPDF();
            }
            if (e.key === 'ArrowRight' && currentPdfDoc) {
                nextPage();
            }
            if (e.key === 'ArrowLeft' && currentPdfDoc) {
                previousPage();
            }
        });

        // Handle window resize for PDF rendering
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (currentPdfDoc && document.getElementById('fullscreenPDF').classList.contains('hidden') === false) {
                    renderPage(currentPageNum);
                }
            }, 250);
        });
    </script>
</body>
</html>