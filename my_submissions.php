<?php
session_start();

// Check if user is logged in - NOW USING 'username' instead of 'name'
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: account.php');
    exit();
}

// Store session variables for easier access
$current_username = $_SESSION['username'];
$display_name = $_SESSION['name'] ?? $_SESSION['username']; // Use 'name' if available, otherwise 'username'

// Include database connection
require_once 'connect.php';


try {
    // First, let's check what columns actually exist in the table
    $columns_sql = "DESCRIBE paper_submissions";
    $columns_result = $conn->query($columns_sql);
    $available_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $available_columns[] = $row['Field'];
    }

    // Build the SELECT query based on available columns
    $select_fields = [
        'ps.id',
        'ps.paper_title',
        'ps.research_type',
        'ps.keywords',
        'ps.author_name',
        'ps.abstract',
        'ps.file_path',
        'ps.status',
        'ps.submission_date',
        'ps.reviewer_comments',
        'ps.user_name'
    ];

    // Add optional enhanced fields if they exist
    $optional_fields = [
        'author_email', 'affiliation', 'co_authors', 'methodology',
        'funding_source', 'research_start_date', 'research_end_date',
        'ethics_approval', 'additional_comments', 'reviewed_by',
        'review_date', 'terms_agreement', 'email_consent', 'data_consent'
    ];

    foreach ($optional_fields as $field) {
        if (in_array($field, $available_columns)) {
            $select_fields[] = "ps.$field";
        }
    }

    // Build the main query
    $sql = "SELECT " . implode(', ', $select_fields) . ",
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
            WHERE LOWER(ps.user_name) = LOWER(?)
            ORDER BY ps.submission_date DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // FIX: Use $current_username instead of $_SESSION['name']
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $submissions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch(Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
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

// Check if submission is enhanced (has new fields)
function isEnhancedSubmission($submission) {
    return !empty($submission['author_email']) || !empty($submission['affiliation']) || 
           !empty($submission['methodology']) || !empty($submission['funding_source']);
}

// Function to render submission row
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
                        </h4>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-2">
                            <span><strong>Type:</strong> <?php echo getResearchTypeDisplay($submission['research_type']); ?></span>
                            <span><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></span>
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
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <p><strong>Primary Author:</strong> <?php echo htmlspecialchars($submission['author_name']); ?></p>
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
                        <?php if (!empty($submission['research_start_date']) && !empty($submission['research_end_date'])): ?>
                            <p><strong>Research Period:</strong> 
                               <?php echo date('M Y', strtotime($submission['research_start_date'])); ?> - 
                               <?php echo date('M Y', strtotime($submission['research_end_date'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <p class="text-sm"><strong>Keywords:</strong> <?php echo htmlspecialchars($submission['keywords']); ?></p>
                </div>
                
                <?php if (!empty($submission['reviewer_comments'])): ?>
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border-l-4 border-blue-400">
                        <p class="text-sm font-semibold text-gray-700 mb-1">Reviewer Comments:</p>
                        <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($submission['reviewer_comments'])); ?></p>
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
                
                <?php if ($submission['status'] === 'pending'): ?>
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
        .metric-card {
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: scale(1.05);
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
        .tab-button:hover:not(.active) {
            background-color: #f9fafb;
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
                    <p class="text-gray-600 text-lg">Here you can view and manage all your proposal submissions</p>
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
                    <p class="text-gray-400 mb-8 max-w-md mx-auto">You haven't submitted any papers yet. Start by submitting your first research paper using our enhanced DOST-compliant form!</p>
                    <a href="submit_paper.php" class="bg-gradient-to-r from-[#115D5B] to-green-600 hover:from-[#0d4a47] hover:to-green-700 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-plus mr-3"></i>Submit Your First Paper
                    </a>
                </div>
            <?php else: ?>
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6">
                        <button class="tab-button flex items-center py-4 px-2 text-sm font-medium text-gray-500 hover:text-gray-700 active" 
                                onclick="showTab('all')">
                            <span class="status-icon bg-gray-400"></span>
                            All Submissions (<?php echo count($submissions); ?>)
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
                            <p class="text-gray-400">All your submissions have been reviewed or are currently under review.</p>
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
                            <p class="text-gray-400">You don't have any papers currently being reviewed by our team.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissionsByStatus['under_review'] as $submission): ?>
                            <?php renderSubmissionRow($submission); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="tab-approved_published" class="tab-content divide-y divide-gray-200">
                    <?php if (empty($submissionsByStatus['approved_published'])): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-check-circle text-6xl text-green-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">No Published Papers</h3>
                            <p class="text-gray-400">You don't have any published papers yet. Keep working on your research!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissionsByStatus['approved_published'] as $submission): ?>
                            <?php renderSubmissionRow($submission); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="tab-rejected" class="tab-content divide-y divide-gray-200">
                    <?php if (empty($submissionsByStatus['rejected'])): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-times-circle text-6xl text-red-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">No Rejected Submissions</h3>
                            <p class="text-gray-400">Great! None of your submissions have been rejected.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissionsByStatus['rejected'] as $submission): ?>
                            <?php renderSubmissionRow($submission); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
            <div id="paperContent" class="p-6">
            </div>
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

        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(`tab-${tabName}`).classList.add('active');
            
            // Add active class to clicked tab button
            event.currentTarget.classList.add('active');
        }

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
                            <p class="text-sm text-blue-700 mt-1">This submission includes comprehensive research information following DOST standards.</p>
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
                        
                        <div class="grid grid-cols-1 ${isEnhanced ? 'md:grid-cols-3' : 'md:grid-cols-2'} gap-6">
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Primary Author:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.author_name)}</p>
                            </div>
                            ${paper.author_email ? `
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Author Email:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.author_email)}</p>
                            </div>
                            ` : ''}
                            ${paper.affiliation ? `
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Affiliation:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.affiliation)}</p>
                            </div>
                            ` : ''}
                        </div>

                        ${paper.co_authors ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Co-Authors:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.co_authors)}</p>
                        </div>
                        ` : ''}
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Keywords:</h4>
                            <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.keywords)}</p>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Abstract:</h4>
                            <div class="text-gray-700 bg-gray-50 p-4 rounded-lg whitespace-pre-wrap max-h-40 overflow-y-auto">${escapeHtml(paper.abstract)}</div>
                        </div>
                        
                        ${paper.methodology ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Research Methodology:</h4>
                            <div class="text-gray-700 bg-gray-50 p-4 rounded-lg whitespace-pre-wrap">${escapeHtml(paper.methodology)}</div>
                        </div>
                        ` : ''}
                        
                        ${paper.funding_source || (paper.research_start_date && paper.research_end_date) ? `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            ${paper.funding_source ? `
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Funding Source:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">${escapeHtml(paper.funding_source)}</p>
                            </div>
                            ` : ''}
                            ${paper.research_start_date && paper.research_end_date ? `
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Research Period:</h4>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">
                                    ${new Date(paper.research_start_date).toLocaleDateString()} - 
                                    ${new Date(paper.research_end_date).toLocaleDateString()}
                                </p>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        ${paper.ethics_approval ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Ethics Approval/Permits:</h4>
                            <div class="text-gray-700 bg-gray-50 p-4 rounded-lg whitespace-pre-wrap">${escapeHtml(paper.ethics_approval)}</div>
                        </div>
                        ` : ''}
                        
                        ${paper.additional_comments ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Additional Comments:</h4>
                            <div class="text-gray-700 bg-gray-50 p-4 rounded-lg whitespace-pre-wrap">${escapeHtml(paper.additional_comments)}</div>
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
                        
                        ${paper.reviewer_comments ? `
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Review Comments:</h4>
                            <div class="text-gray-700 bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400 whitespace-pre-wrap">${escapeHtml(paper.reviewer_comments)}</div>
                            ${paper.reviewed_by ? `
                                <p class="text-sm text-gray-500 mt-2">
                                    Reviewed by: ${escapeHtml(paper.reviewed_by)}
                                    ${paper.review_date ? ` on ${new Date(paper.review_date).toLocaleDateString()}` : ''}
                                </p>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        ${isEnhanced ? `
                        <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800 mb-2">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>Submission Compliance
                            </h4>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div class="flex items-center">
                                    <i class="fas fa-${paper.terms_agreement ? 'check text-green-600' : 'times text-red-600'} mr-2"></i>
                                    Terms Agreement
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-${paper.email_consent ? 'check text-green-600' : 'times text-red-600'} mr-2"></i>
                                    Email Consent
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-${paper.data_consent ? 'check text-green-600' : 'times text-red-600'} mr-2"></i>
                                    Data Consent
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

        function editPaper(paperId) {
            window.location.href = `edit_paper.php?id=${paperId}`;
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

        // Close modal when clicking outside
        document.getElementById('paperModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaperModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaperModal();
            }
        });

        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.metric-card, .submission-row');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>