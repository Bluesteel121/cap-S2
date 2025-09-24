<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once 'connect.php';

// Debug information
$debug_info = [];
$debug_info['session_name'] = $_SESSION['name'] ?? 'NOT SET';
$debug_info['session_keys'] = array_keys($_SESSION);

try {
    // Get submissions for the logged-in user
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
            WHERE ps.user_name = ? 
            ORDER BY ps.submission_date DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $_SESSION['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $submissions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Debug queries
    $debug_queries = [];
    
    // Count total submissions for this user
    $count_sql = "SELECT COUNT(*) as count FROM paper_submissions WHERE user_name = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $_SESSION['name']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $debug_queries['exact_match'] = $count_result->fetch_assoc()['count'];
    $count_stmt->close();
    
    // Get sample usernames
    $users_sql = "SELECT DISTINCT user_name FROM paper_submissions ORDER BY user_name LIMIT 10";
    $users_result = $conn->query($users_sql);
    $debug_user_names = [];
    while ($row = $users_result->fetch_assoc()) {
        $debug_user_names[] = $row['user_name'];
    }
    $debug_queries['sample_usernames'] = $debug_user_names;
    
    $debug_info['query_results'] = $debug_queries;

} catch(Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    $debug_info['error'] = $e->getMessage();
    $submissions = [];
}

// Status badge colors
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
        case 'published':
            return 'bg-purple-100 text-purple-800 border border-purple-200';
        default:
            return 'bg-gray-100 text-gray-800 border border-gray-200';
    }
}

// Research type display mapping
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

// Group submissions by status
$submissionsByStatus = [
    'pending' => [],
    'under_review' => [],
    'approved_published' => [],
    'rejected' => []
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .submission-row:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .enhanced-badge {
            animation: pulse 2s infinite;
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
        .status-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-pending { background-color: #f59e0b; }
        .status-under_review { background-color: #3b82f6; }
        .status-approved_published { background-color: #10b981; }
        .status-rejected { background-color: #ef4444; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white py-6 px-6 shadow-lg">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
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
        <!-- Debug Info (you can remove this in production) -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6 border-l-4 border-blue-500">
            <h3 class="font-bold text-gray-800 mb-2">Debug Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p><strong>Session Name:</strong> <?php echo htmlspecialchars($debug_info['session_name']); ?></p>
                    <p><strong>Submissions Found:</strong> <?php echo count($submissions); ?></p>
                </div>
                <div>
                    <p><strong>Query Count:</strong> <?php echo $debug_info['query_results']['exact_match'] ?? 'N/A'; ?></p>
                    <?php if (isset($debug_info['error'])): ?>
                        <p class="text-red-600"><strong>Error:</strong> <?php echo htmlspecialchars($debug_info['error']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p><strong>Sample Users:</strong></p>
                    <div class="text-xs bg-gray-100 p-2 rounded max-h-16 overflow-y-auto">
                        <?php echo implode(', ', array_map('htmlspecialchars', $debug_info['query_results']['sample_usernames'] ?? [])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8 border border-gray-100">
            <h2 class="text-3xl font-bold text-[#115D5B] mb-2">
                Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
            </h2>
            <p class="text-gray-600 text-lg">Here you can view and manage all your paper submissions.</p>
        </div>

        <!-- Submissions List -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-6">
                <h3 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-list-alt mr-3"></i>Your Research Submissions (<?php echo count($submissions); ?>)
                </h3>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-file-alt text-8xl text-gray-300 mb-6"></i>
                    <h3 class="text-2xl font-semibold text-gray-500 mb-4">No submissions yet</h3>
                    <p class="text-gray-400 mb-8 max-w-md mx-auto">
                        You haven't submitted any papers yet. Start by submitting your first research paper!
                    </p>
                    <a href="submit_paper.php" class="bg-gradient-to-r from-[#115D5B] to-green-600 hover:from-[#0d4a47] hover:to-green-700 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-plus mr-3"></i>Submit Your First Paper
                    </a>
                </div>
            <?php else: ?>
                <!-- Status Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6">
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700 active" 
                                onclick="showTab('all')">
                            <span class="status-icon bg-gray-400"></span>
                            All (<?php echo count($submissions); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('pending')">
                            <span class="status-icon status-pending"></span>
                            Pending (<?php echo count($submissionsByStatus['pending']); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('under_review')">
                            <span class="status-icon status-under_review"></span>
                            Under Review (<?php echo count($submissionsByStatus['under_review']); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('approved_published')">
                            <span class="status-icon status-approved_published"></span>
                            Published (<?php echo count($submissionsByStatus['approved_published']); ?>)
                        </button>
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700" 
                                onclick="showTab('rejected')">
                            <span class="status-icon status-rejected"></span>
                            Rejected (<?php echo count($submissionsByStatus['rejected']); ?>)
                        </button>
                    </nav>
                </div>

                <!-- Tab Contents -->
                <!-- All Submissions Tab -->
                <div id="tab-all" class="tab-content active">
                    <?php foreach ($submissions as $submission): ?>
                        <div class="submission-row p-6 border-b border-gray-200 last:border-b-0 transition-all duration-200">
                            <div class="flex flex-wrap lg:flex-nowrap justify-between items-start gap-4">
                                <!-- Paper Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-semibold text-gray-900 mb-1">
                                                <?php echo htmlspecialchars($submission['paper_title']); ?>
                                            </h4>
                                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-2">
                                                <span><strong>Type:</strong> <?php echo getResearchTypeDisplay($submission['research_type']); ?></span>
                                                <span><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></span>
                                                <?php if ($submission['total_views'] > 0 || $submission['total_downloads'] > 0): ?>
                                                    <span class="flex items-center space-x-3">
                                                        <span class="flex items-center"><i class="fas fa-eye text-blue-500 mr-1"></i><?php echo $submission['total_views']; ?></span>
                                                        <span class="flex items-center"><i class="fas fa-download text-green-500 mr-1"></i><?php echo $submission['total_downloads']; ?></span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($submission['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                                        <div>
                                            <p><strong>Author:</strong> <?php echo htmlspecialchars($submission['author_name']); ?></p>
                                            <?php if (!empty($submission['author_email'])): ?>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['author_email']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($submission['affiliation'])): ?>
                                                <p><strong>Affiliation:</strong> <?php echo htmlspecialchars($submission['affiliation']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (!empty($submission['co_authors'])): ?>
                                                <p><strong>Co-authors:</strong> <?php echo htmlspecialchars($submission['co_authors']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($submission['funding_source'])): ?>
                                                <p><strong>Funding:</strong> <?php echo htmlspecialchars($submission['funding_source']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <p class="text-sm"><strong>Keywords:</strong> <?php echo htmlspecialchars($submission['keywords']); ?></p>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h5 class="font-semibold text-gray-800 mb-2">Abstract</h5>
                                        <p class="text-sm text-gray-700">
                                            <?php 
                                            $abstract = htmlspecialchars($submission['abstract']);
                                            echo strlen($abstract) > 300 ? substr($abstract, 0, 300) . '...' : $abstract;
                                            ?>
                                        </p>
                                    </div>
                                    
                                    <?php if (!empty($submission['reviewer_comments'])): ?>
                                        <div class="mt-4 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                                            <p class="text-sm font-semibold text-blue-800 mb-1">Reviewer Comments:</p>
                                            <p class="text-sm text-blue-700"><?php echo nl2br(htmlspecialchars($submission['reviewer_comments'])); ?></p>
                                            <?php if (!empty($submission['reviewed_by'])): ?>
                                                <p class="text-xs text-blue-600 mt-2">
                                                    Reviewed by: <?php echo htmlspecialchars($submission['reviewed_by']); ?>
                                                    <?php if (!empty($submission['review_date'])): ?>
                                                        on <?php echo date('M j, Y', strtotime($submission['review_date'])); ?>
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2 min-w-40">
                                    <button onclick="viewPaper(<?php echo $submission['id']; ?>)" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                        <i class="fas fa-eye mr-2"></i>View Details
                                    </button>
                                    
                                    <?php if (!empty($submission['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank"
                                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition">
                                            <i class="fas fa-download mr-2"></i>Download
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($submission['status'] === 'pending'): ?>
                                        <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-edit mr-2"></i>Edit
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($submission['status'] === 'published'): ?>
                                        <a href="view_paper.php?id=<?php echo $submission['id']; ?>" target="_blank"
                                           class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition">
                                            <i class="fas fa-external-link-alt mr-2"></i>View Published
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Other tabs would be similar with filtered data -->
                <?php foreach (['pending', 'under_review', 'approved_published', 'rejected'] as $status): ?>
                    <div id="tab-<?php echo $status; ?>" class="tab-content">
                        <?php if (empty($submissionsByStatus[$status])): ?>
                            <div class="p-12 text-center">
                                <h3 class="text-xl font-semibold text-gray-500 mb-2">No <?php echo ucwords(str_replace('_', ' ', $status)); ?> Submissions</h3>
                            </div>
                        <?php else: ?>
                            <?php foreach ($submissionsByStatus[$status] as $submission): ?>
                                <!-- Same submission display code as above -->
                                <div class="submission-row p-6 border-b border-gray-200 last:border-b-0">
                                    <h4 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($submission['paper_title']); ?></h4>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo date('M j, Y', strtotime($submission['submission_date'])); ?></p>
                                    <p class="text-sm"><?php echo htmlspecialchars(substr($submission['abstract'], 0, 200)) . '...'; ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

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

        function viewPaper(paperId) {
            const paper = submissions.find(p => p.id == paperId);
            if (paper) {
                alert(`Paper: ${paper.paper_title}\nStatus: ${paper.status}\nSubmitted: ${paper.submission_date}`);
                // You can implement a proper modal here
            }
        }
    </script>
</body>
</html>