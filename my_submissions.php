<?php
session_start();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: account.php');
    exit();
}

$current_username = $_SESSION['username'];
$display_name = $_SESSION['name'] ?? $_SESSION['username'];

require_once 'connect.php';
require_once 'email_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_revision'])) {
    header('Content-Type: application/json');
    
    try {
        $paper_id = (int)$_POST['paper_id'];
        $author_response = trim($_POST['author_response'] ?? '');
        
        // Verify paper belongs to user
        $verify_sql = "SELECT ps.*, a.name as author_full_name, a.email as user_email 
                       FROM paper_submissions ps 
                       LEFT JOIN accounts a ON ps.user_name = a.username 
                       WHERE ps.id = ? AND LOWER(ps.user_name) = LOWER(?)";
        $stmt = $conn->prepare($verify_sql);
        $stmt->bind_param('is', $paper_id, $current_username);
        $stmt->execute();
        $paper = $stmt->get_result()->fetch_assoc();
        
        if (!$paper || $paper['status'] !== 'revision_requested') {
            throw new Exception('Invalid paper or paper not awaiting revision');
        }
        
        // Handle file upload
        $new_file_path = null;
        if (isset($_FILES['revised_file']) && $_FILES['revised_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/papers/revisions/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['revised_file']['name'], PATHINFO_EXTENSION);
            $new_filename = 'revised_' . $paper_id . '_' . time() . '.' . $file_extension;
            $new_file_path = $upload_dir . $new_filename;
            
            if (!move_uploaded_file($_FILES['revised_file']['tmp_name'], $new_file_path)) {
                throw new Exception('Failed to upload revised file');
            }
        } else {
            throw new Exception('Revised file is required');
        }
        
        $conn->begin_transaction();
        
        // Update paper submission
        $update_sql = "UPDATE paper_submissions 
                       SET is_revised = TRUE,
                           revision_submitted_date = NOW(),
                           previous_file_path = file_path,
                           file_path = ?,
                           updated_at = NOW()
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('si', $new_file_path, $paper_id);
        $stmt->execute();
        
        // Update revision record
        $rev_update_sql = "UPDATE paper_revisions 
                           SET status = 'submitted',
                               submitted_date = NOW(),
                               new_file_path = ?,
                               author_response = ?
                           WHERE paper_id = ? AND status = 'pending'
                           ORDER BY revision_number DESC LIMIT 1";
        $stmt = $conn->prepare($rev_update_sql);
        $stmt->bind_param('ssi', $new_file_path, $author_response, $paper_id);
        $stmt->execute();
        
        // Create notification for admin
        $notif_sql = "INSERT INTO submission_notifications 
                      (paper_id, user_name, notification_type, message, created_at) 
                      VALUES (?, 'admin', 'revision_submitted', 'Author has submitted revised version of paper', NOW())";
        $stmt = $conn->prepare($notif_sql);
        $stmt->bind_param('i', $paper_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Send admin notification email
        $paperData = [
            'paper_title' => $paper['paper_title'],
            'author_name' => $paper['author_name'] ?? $paper['author_full_name'],
            'research_type' => $paper['research_type'],
            'revision_count' => $paper['revision_count'] ?? 1,
            'reviewed_by' => $paper['reviewed_by'] ?? 'N/A',
            'revision_notes' => $author_response,
            'submitted_by' => $current_username
        ];
        
        $email_sent = false;
        $email_error = null;
        
        try {
            $email_sent = EmailService::sendAdminNotification($paperData, 'revision', $conn);
            
            if (!$email_sent) {
                $email_error = "Admin notification email failed to send";
                error_log("Failed to send admin notification for revision of paper ID: $paper_id");
            }
        } catch (Exception $e) {
            $email_error = $e->getMessage();
            error_log("Exception sending admin notification for revision: " . $email_error);
        }
        
        // Log activity
        if (function_exists('logActivity')) {
            $activity_msg = "Paper ID: $paper_id, Revision submitted by: $current_username";
            if ($email_sent) {
                $activity_msg .= ", Admin notification: Sent";
            } else {
                $activity_msg .= ", Admin notification: FAILED - $email_error";
            }
            logActivity('REVISION_SUBMITTED', $activity_msg);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Revision submitted successfully!' . ($email_sent ? '' : ' (Admin notification email may not have been sent)'),
            'email_sent' => $email_sent
        ]);
        
    } catch (Exception $e) {
        if ($conn) $conn->rollback();
        error_log("Revision submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
// Fetch submissions with revision info
try {
    $sql = "SELECT ps.*, 
            COALESCE(view_count.views, 0) as total_views,
            COALESCE(download_count.downloads, 0) as total_downloads,
            CASE 
                WHEN ps.revision_requested_date IS NOT NULL THEN 
                    DATEDIFF(NOW(), ps.revision_requested_date)
                ELSE 0
            END as days_since_revision_request,
            (SELECT COUNT(*) FROM paper_revisions WHERE paper_id = ps.id) as total_revisions
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
            WHERE LOWER(ps.user_name) = LOWER(?)
            ORDER BY ps.submission_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $submissions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch(Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
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

function getResearchTypeDisplay($type) {
    $types = [
        'experimental' => 'Experimental Research',
        'observational' => 'Observational Study',
        'review' => 'Literature Review',
        'case_study' => 'Case Study',
        'other' => 'Other'
    ];
    return $types[$type] ?? ucfirst($type);
}

function isEnhancedSubmission($submission) {
    return !empty($submission['author_email']) || !empty($submission['affiliation']) || 
           !empty($submission['methodology']) || !empty($submission['funding_source']);
}

function renderSubmissionRow($submission) {
    ?>
    <div class="submission-row p-6 transition-all duration-200">
        <div class="flex flex-wrap lg:flex-nowrap justify-between items-start gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-gray-900 mb-1">
                            <?php echo htmlspecialchars($submission['paper_title']); ?>
                            <?php if (isEnhancedSubmission($submission)): ?>
                                <span class="enhanced-badge inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 ml-2">
                                    <i class="fas fa-star mr-1"></i>Enhanced
                                </span>
                            <?php endif; ?>
                            <?php if ($submission['status'] === 'revision_requested' && $submission['is_revised']): ?>
                                <span class="revision-badge inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 ml-2">
                                    <i class="fas fa-check-circle mr-1"></i>Revised & Submitted
                                </span>
                            <?php endif; ?>
                        </h4>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-2">
                            <span><strong>Type:</strong> <?php echo getResearchTypeDisplay($submission['research_type']); ?></span>
                            <span><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></span>
                            <?php if ($submission['status'] === 'revision_requested' && $submission['days_since_revision_request'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-orange-100 text-orange-800">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo $submission['days_since_revision_request']; ?> day<?php echo $submission['days_since_revision_request'] != 1 ? 's' : ''; ?> since revision requested
                                </span>
                            <?php endif; ?>
                            <?php if ((isset($submission['total_views']) && $submission['total_views'] > 0) || (isset($submission['total_downloads']) && $submission['total_downloads'] > 0)): ?>
                                <span class="flex items-center space-x-3">
                                    <span class="flex items-center"><i class="fas fa-eye text-blue-500 mr-1"></i><?php echo $submission['total_views'] ?? 0; ?></span>
                                    <span class="flex items-center"><i class="fas fa-download text-green-500 mr-1"></i><?php echo $submission['total_downloads'] ?? 0; ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($submission['status']); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                    </span>
                </div>
                
                <div class="mt-3">
                    <p class="text-sm"><strong>Keywords:</strong> <?php echo htmlspecialchars($submission['keywords']); ?></p>
                </div>
                
                <?php if (!empty($submission['reviewer_comments'])): ?>
                    <div class="mt-4 p-4 <?php echo $submission['status'] === 'revision_requested' ? 'bg-orange-50 border-l-4 border-orange-400' : 'bg-gray-50 border-l-4 border-blue-400'; ?>">
                        <p class="text-sm font-semibold <?php echo $submission['status'] === 'revision_requested' ? 'text-orange-700' : 'text-gray-700'; ?> mb-1">
                            <?php echo $submission['status'] === 'revision_requested' ? 'Revision Required:' : 'Reviewer Comments:'; ?>
                        </p>
                        <p class="text-sm <?php echo $submission['status'] === 'revision_requested' ? 'text-orange-700' : 'text-gray-700'; ?>">
                            <?php echo nl2br(htmlspecialchars($submission['reviewer_comments'])); ?>
                        </p>
                        <?php if (!empty($submission['reviewed_by'])): ?>
                            <p class="text-xs text-gray-500 mt-2">
                                Reviewed by: <?php echo htmlspecialchars($submission['reviewed_by']); ?>
                                <?php if (!empty($submission['review_date'])): ?>
                                    on <?php echo date('M j, Y', strtotime($submission['review_date'])); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex flex-col gap-2 min-w-40">
                <button onclick="viewPaper(<?php echo $submission['id']; ?>)" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-eye mr-2"></i>View Details
                </button>
                
                <?php if (!empty($submission['file_path'])): ?>
                    <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank"
                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                <?php endif; ?>
                
                <?php if ($submission['status'] === 'revision_requested' && !$submission['is_revised']): ?>
                    <button onclick="openRevisionModal(<?php echo $submission['id']; ?>)" 
                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center animate-pulse">
                        <i class="fas fa-edit mr-2"></i>Submit Revision
                    </button>
                <?php elseif ($submission['status'] === 'pending'): ?>
                    <button onclick="editPaper(<?php echo $submission['id']; ?>)" 
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </button>
                <?php endif; ?>
                
                <?php if ($submission['status'] === 'published'): ?>
                    <a href="view_paper.php?id=<?php echo $submission['id']; ?>" target="_blank"
                       class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-external-link-alt mr-2"></i>View Published
                    </a>
                <?php endif; ?>
                
                <?php if ($submission['total_revisions'] > 0): ?>
                    <button onclick="viewRevisionHistory(<?php echo $submission['id']; ?>)" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-history mr-2"></i>History (<?php echo $submission['total_revisions']; ?>)
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// Group submissions by status
$submissionsByStatus = [
    'pending' => [],
    'under_review' => [],
    'approved_published' => [],
    'revision_requested' => []
];

foreach ($submissions as $submission) {
    if ($submission['status'] === 'approved' || $submission['status'] === 'published') {
        $submissionsByStatus['approved_published'][] = $submission;
    } else {
        $submissionsByStatus[$submission['status']][] = $submission;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - CNLRRS</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .submission-row:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .enhanced-badge, .revision-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
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
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <header class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white py-6 px-6 shadow-lg">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="Images/CNLRRS_icon.png" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                <div>
                    <h1 class="text-2xl font-bold">My Paper Submissions</h1>
                    <p class="text-sm opacity-75">Track and manage your research submissions</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="submit_paper.php" class="bg-green-600 hover:bg-green-700 px-6 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-md">
                    <i class="fas fa-plus mr-2"></i>Submit New Paper
                </a>
                <a href="loggedin_index.php" class="flex items-center space-x-2 bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 shadow-md">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8 mb-8 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-[#115D5B] mb-2">
                        Welcome, <?php echo htmlspecialchars($display_name); ?>!
                    </h2>
                    <p class="text-gray-600 text-lg">Here you can view and manage all your proposal submissions and revisions</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-6">
                <h3 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-list-alt mr-3"></i>Your Research Submissions
                </h3>
                <p class="text-sm opacity-75 mt-1">Complete overview of all your submitted papers organized by status</p>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-file-alt text-8xl text-gray-300 mb-6"></i>
                    <h3 class="text-2xl font-semibold text-gray-500 mb-4">No submissions yet</h3>
                    <p class="text-gray-400 mb-8 max-w-md mx-auto">You haven't submitted any papers yet. Start by submitting your first research paper!</p>
                    <a href="submit_paper.php" class="bg-gradient-to-r from-[#115D5B] to-green-600 hover:from-[#0d4a47] hover:to-green-700 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-plus mr-3"></i>Submit Your First Paper
                    </a>
                </div>
            <?php else: ?>
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6">
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700 active" 
                                onclick="showTab('all')">
                            All Submissions (<?php echo count($submissions); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('pending')">
                            Pending (<?php echo count($submissionsByStatus['pending']); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('under_review')">
                            Under Review (<?php echo count($submissionsByStatus['under_review']); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('revision_requested')">
                            <i class="fas fa-exclamation-circle text-orange-500 mr-1"></i>
                            Needs Revision (<?php echo count($submissionsByStatus['revision_requested']); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('approved_published')">
                            Published (<?php echo count($submissionsByStatus['approved_published']); ?>)
                        </button>
                    </nav>
                </div>

                <div id="tab-all" class="tab-content active divide-y divide-gray-200">
                    <?php foreach ($submissions as $submission): ?>
                        <?php renderSubmissionRow($submission); ?>
                    <?php endforeach; ?>
                </div>

                <div id="tab-pending" class="tab-content divide-y divide-gray-200">
                    <?php if (empty($submissionsByStatus['pending'])): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-clock text-6xl text-yellow-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">No Pending Submissions</h3>
                            <p class="text-gray-400">All your submissions have been reviewed.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissionsByStatus['pending'] as $submission): ?>
                            <?php renderSubmissionRow($submission); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="tab-under_review" class="tab-content divide-y divide-gray-200">
                    <?php if (empty($submissionsByStatus['under_review'])): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-search text-6xl text-blue-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">No Submissions Under Review</h3>
                            <p class="text-gray-400">You don't have any papers currently being reviewed.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissionsByStatus['under_review'] as $submission): ?>
                            <?php renderSubmissionRow($submission); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="tab-revision_requested" class="tab-content divide-y divide-gray-200">
                    <?php if (empty($submissionsByStatus['revision_requested'])): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-edit text-6xl text-orange-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">No Revisions Needed</h3>
                            <p class="text-gray-400">Great! None of your submissions currently need revision.</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-orange-50 border-l-4 border-orange-400 p-4 m-6">
                            <div class="flex">
                                <i class="fas fa-info-circle text-orange-500 mr-2 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-orange-800">Action Required</h4>
                                    <p class="text-sm text-orange-700 mt-1">The papers below require revisions. Please review the feedback and submit your revised versions.</p>
                                </div>
                            </div>
                        </div>
                        <?php foreach ($submissionsByStatus['revision_requested'] as $submission): ?>
                            <?php renderSubmissionRow($submission); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="tab-approved_published" class="tab-content divide-y divide-gray-200">
                    <?php if (empty($submissionsByStatus['approved_published'])): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-check-circle text-6xl text-green-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">No Published Papers</h3>
                            <p class="text-gray-400">You don't have any published papers yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissionsByStatus['approved_published'] as $submission): ?>
                            <?php renderSubmissionRow($submission); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Revision Modal -->
    <div id="revisionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-orange-600 to-orange-500 text-white p-6 rounded-t-xl sticky top-0">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-edit mr-3"></i>Submit Revised Paper
                    </h3>
                    <button onclick="closeRevisionModal()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form id="revisionForm" class="p-6" enctype="multipart/form-data">
                <input type="hidden" id="revision_paper_id" name="paper_id">
                <input type="hidden" name="submit_revision" value="1">
                
                <div id="revision_feedback" class="mb-6 p-4 bg-orange-50 border-l-4 border-orange-400 rounded">
                    <!-- Will be populated dynamically -->
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-upload mr-2"></i>Upload Revised Paper (PDF) *
                    </label>
                    <input type="file" id="revised_file" name="revised_file" accept=".pdf" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Please upload your revised paper addressing all the reviewer comments.
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Your Response to Reviewer (Optional)
                    </label>
                    <textarea id="author_response" name="author_response" rows="5"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                              placeholder="Explain the changes you made in response to the reviewer's feedback..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" 
                            class="flex-1 bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg transition font-semibold">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Revision
                    </button>
                    <button type="button" onclick="closeRevisionModal()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition font-semibold">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Paper Details Modal -->
    <div id="paperModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-6 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-file-alt mr-3"></i>Paper Details
                    </h3>
                    <button onclick="closePaperModal()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="paperContent" class="p-6"></div>
        </div>
    </div>

    <!-- Revision History Modal -->
    <div id="revisionHistoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-gray-700 to-gray-600 text-white p-6 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-history mr-3"></i>Revision History
                    </h3>
                    <button onclick="closeRevisionHistoryModal()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="revisionHistoryContent" class="p-6"></div>
        </div>
    </div>

    <footer class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white text-center py-8 mt-12">
        <div class="max-w-6xl mx-auto px-6">
            <p class="text-lg font-semibold mb-2">Camarines Norte Lowland Rainfed Research Station</p>
            <p class="text-sm opacity-75">Supporting agricultural research and development in the Philippines</p>
            <p class="text-xs opacity-60 mt-2">&copy; 2025 CNLRRS. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const submissions = <?php echo json_encode($submissions); ?>;

        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            document.getElementById(`tab-${tabName}`).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        function openRevisionModal(paperId) {
            const paper = submissions.find(p => p.id == paperId);
            
            if (paper && paper.status === 'revision_requested') {
                document.getElementById('revision_paper_id').value = paperId;
                
                document.getElementById('revision_feedback').innerHTML = `
                    <h4 class="font-semibold text-orange-800 mb-2">Reviewer Feedback:</h4>
                    <p class="text-sm text-orange-700 whitespace-pre-wrap">${escapeHtml(paper.reviewer_comments)}</p>
                    ${paper.reviewed_by ? `
                        <p class="text-xs text-orange-600 mt-2">
                            Reviewed by: ${escapeHtml(paper.reviewed_by)} on ${new Date(paper.review_date).toLocaleDateString()}
                        </p>
                    ` : ''}
                    ${paper.days_since_revision_request > 0 ? `
                        <div class="mt-3 flex items-center text-orange-700">
                            <i class="fas fa-clock mr-2"></i>
                            <span class="text-sm font-semibold">
                                ${paper.days_since_revision_request} day${paper.days_since_revision_request != 1 ? 's' : ''} since revision was requested
                            </span>
                        </div>
                    ` : ''}
                `;
                
                document.getElementById('revised_file').value = '';
                document.getElementById('author_response').value = '';
                
                document.getElementById('revisionModal').classList.remove('hidden');
                document.getElementById('revisionModal').classList.add('flex');
            }
        }

        function closeRevisionModal() {
            document.getElementById('revisionModal').classList.add('hidden');
            document.getElementById('revisionModal').classList.remove('flex');
        }

        document.getElementById('revisionForm').addEventListener('submit', async function(e) {
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
                    showNotification('Revision submitted successfully! Your paper will be reviewed again.', 'success');
                    closeRevisionModal();
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(data.error || 'Failed to submit revision');
                }
            } catch (error) {
                showNotification('Error: ' + error.message, 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        function viewPaper(paperId) {
            const paper = submissions.find(p => p.id == paperId);
            
            if (paper) {
                const isEnhanced = paper.author_email || paper.affiliation || paper.methodology || paper.funding_source;
                
                document.getElementById('paperContent').innerHTML = `
                    <div class="space-y-6">
                        ${isEnhanced ? `
                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-star text-blue-600 mr-2"></i>
                                <span class="font-semibold text-blue-800">DOST-Compliant Enhanced Submission</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${paper.status === 'revision_requested' ? `
                        <div class="bg-orange-50 border border-orange-200 p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-exclamation-circle text-orange-600 mr-2"></i>
                                <span class="font-semibold text-orange-800">Revision Required</span>
                                ${paper.is_revised ? `
                                    <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm">
                                        <i class="fas fa-check-circle mr-1"></i>Revised & Submitted
                                    </span>
                                ` : ''}
                            </div>
                            ${paper.days_since_revision_request > 0 ? `
                                <p class="text-sm text-orange-700">
                                    <i class="fas fa-clock mr-1"></i>
                                    ${paper.days_since_revision_request} day${paper.days_since_revision_request != 1 ? 's' : ''} since revision was requested
                                </p>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Paper Title:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.paper_title)}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Research Type:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${getResearchTypeDisplay(paper.research_type)}</p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Keywords:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.keywords)}</p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Abstract:</h4>
                            <div class="text-gray-700 bg-gray-50 p-4 rounded-lg whitespace-pre-wrap max-h-40 overflow-y-auto">${escapeHtml(paper.abstract)}</div>
                        </div>
                        
                        ${paper.reviewer_comments ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Review Comments:</h4>
                            <div class="text-gray-700 ${paper.status === 'revision_requested' ? 'bg-orange-50 border-l-4 border-orange-400' : 'bg-yellow-50 border-l-4 border-yellow-400'} p-4 rounded-lg whitespace-pre-wrap">${escapeHtml(paper.reviewer_comments)}</div>
                            ${paper.reviewed_by ? `
                                <p class="text-sm text-gray-500 mt-2">
                                    Reviewed by: ${escapeHtml(paper.reviewed_by)}
                                    ${paper.review_date ? ` on ${new Date(paper.review_date).toLocaleDateString()}` : ''}
                                </p>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Submission Date:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${new Date(paper.submission_date).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Current Status:</h4>
                                <span class="inline-flex px-3 py-2 text-sm font-semibold rounded-full ${getStatusBadgeClass(paper.status)}">
                                    ${paper.status.replace('_', ' ').toUpperCase()}
                                </span>
                            </div>
                        </div>
                        
                        ${paper.total_views > 0 || paper.total_downloads > 0 ? `
                        <div class="bg-green-50 border border-green-200 p-4 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">
                                <i class="fas fa-chart-line mr-2"></i>Paper Metrics
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600">${paper.total_views || 0}</div>
                                    <div class="text-sm text-gray-600">Total Views</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">${paper.total_downloads || 0}</div>
                                    <div class="text-sm text-gray-600">Downloads</div>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                document.getElementById('paperModal').classList.remove('hidden');
                document.getElementById('paperModal').classList.add('flex');
            }
        }

        function closePaperModal() {
            document.getElementById('paperModal').classList.add('hidden');
            document.getElementById('paperModal').classList.remove('flex');
        }

        async function viewRevisionHistory(paperId) {
            try {
                const response = await fetch(`get_revision_history.php?paper_id=${paperId}`);
                const data = await response.json();
                
                if (data.success && data.revisions) {
                    const historyHTML = data.revisions.map((rev, index) => `
                        <div class="border-l-4 ${rev.status === 'submitted' ? 'border-green-400 bg-green-50' : 'border-orange-400 bg-orange-50'} p-4 rounded mb-4">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-semibold text-gray-800">
                                    Revision #${rev.revision_number}
                                    ${rev.status === 'submitted' ? '<span class="ml-2 text-green-600"><i class="fas fa-check-circle"></i> Submitted</span>' : '<span class="ml-2 text-orange-600"><i class="fas fa-clock"></i> Pending</span>'}
                                </h4>
                                <span class="text-sm text-gray-500">${new Date(rev.requested_date).toLocaleDateString()}</span>
                            </div>
                            <div class="text-sm text-gray-700 mb-2">
                                <strong>Requested by:</strong> ${escapeHtml(rev.requested_by)}
                            </div>
                            <div class="text-sm text-gray-700 mb-2">
                                <strong>Reviewer Comments:</strong>
                                <p class="mt-1 whitespace-pre-wrap">${escapeHtml(rev.reviewer_comments)}</p>
                            </div>
                            ${rev.submitted_date ? `
                                <div class="text-sm text-gray-700 mb-2">
                                    <strong>Submitted on:</strong> ${new Date(rev.submitted_date).toLocaleDateString()}
                                </div>
                            ` : ''}
                            ${rev.author_response ? `
                                <div class="text-sm text-gray-700 bg-white p-3 rounded mt-2">
                                    <strong>Your Response:</strong>
                                    <p class="mt-1 whitespace-pre-wrap">${escapeHtml(rev.author_response)}</p>
                                </div>
                            ` : ''}
                        </div>
                    `).join('');
                    
                    document.getElementById('revisionHistoryContent').innerHTML = historyHTML || '<p class="text-gray-500 text-center py-8">No revision history available.</p>';
                    document.getElementById('revisionHistoryModal').classList.remove('hidden');
                    document.getElementById('revisionHistoryModal').classList.add('flex');
                } else {
                    showNotification('Failed to load revision history', 'error');
                }
            } catch (error) {
                showNotification('Error loading revision history: ' + error.message, 'error');
            }
        }

        function closeRevisionHistoryModal() {
            document.getElementById('revisionHistoryModal').classList.add('hidden');
            document.getElementById('revisionHistoryModal').classList.remove('flex');
        }

        function editPaper(paperId) {
            window.location.href = `edit_paper.php?id=${paperId}`;
        }

        function getStatusBadgeClass(status) {
            switch(status) {
                case 'pending': return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                case 'under_review': return 'bg-blue-100 text-blue-800 border border-blue-200';
                case 'approved': return 'bg-green-100 text-green-800 border border-green-200';
                case 'revision_requested': return 'bg-orange-100 text-orange-800 border border-orange-200';
                case 'published': return 'bg-purple-100 text-purple-800 border border-purple-200';
                default: return 'bg-gray-100 text-gray-800 border border-gray-200';
            }
        }

        function getResearchTypeDisplay(type) {
            const types = {
                'experimental': 'Experimental Research',
                'observational': 'Observational Study',
                'review': 'Literature Review',
                'case_study': 'Case Study',
                'other': 'Other'
            };
            return types[type] || type.charAt(0).toUpperCase() + type.slice(1);
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
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
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
            
            notification.className = `fixed top-4 right-4 max-w-md p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${colors[type]}`;
            
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

        // Close modals when clicking outside
        document.getElementById('paperModal').addEventListener('click', function(e) {
            if (e.target === this) closePaperModal();
        });

        document.getElementById('revisionModal').addEventListener('click', function(e) {
            if (e.target === this) closeRevisionModal();
        });

        document.getElementById('revisionHistoryModal').addEventListener('click', function(e) {
            if (e.target === this) closeRevisionHistoryModal();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaperModal();
                closeRevisionModal();
                closeRevisionHistoryModal();
            }
        });
    </script>
</body>
</html>