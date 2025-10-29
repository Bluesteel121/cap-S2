<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'connect.php';

// Get date range from request or default to last 30 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Function to get paper submission statistics
function getPaperSubmissionStats($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            AVG(DATEDIFF(review_date, submission_date)) as avg_review_days,
            MIN(submission_date) as earliest_submission,
            MAX(submission_date) as latest_submission
        FROM paper_submissions
        WHERE DATE(submission_date) BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get papers by research type
function getPapersByResearchType($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            research_type,
            COUNT(*) as count,
            GROUP_CONCAT(DISTINCT affiliation SEPARATOR '; ') as affiliations
        FROM paper_submissions
        WHERE DATE(submission_date) BETWEEN ? AND ?
        AND research_type != ''
        GROUP BY research_type
        ORDER BY count DESC
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get top performing papers by views and downloads
function getTopPapers($conn, $start_date, $end_date, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            ps.id,
            ps.paper_title,
            ps.author_name,
            ps.affiliation,
            ps.research_type,
            COUNT(CASE WHEN pm.metric_type = 'view' THEN 1 END) as views,
            COUNT(CASE WHEN pm.metric_type = 'download' THEN 1 END) as downloads,
            ps.submission_date,
            ps.status,
            ps.keywords
        FROM paper_submissions ps
        LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id 
            AND DATE(pm.created_at) BETWEEN ? AND ?
        WHERE ps.status = 'approved'
        GROUP BY ps.id
        ORDER BY views DESC, downloads DESC
        LIMIT ?
    ");
    $stmt->bind_param("ssi", $start_date, $end_date, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get papers by affiliation
function getPapersByAffiliation($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            affiliation,
            COUNT(*) as total_papers,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review
        FROM paper_submissions
        WHERE DATE(submission_date) BETWEEN ? AND ?
        GROUP BY affiliation
        ORDER BY total_papers DESC
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get reviewer performance for papers
function getReviewerPerformance($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            reviewed_by as reviewer,
            COUNT(*) as papers_reviewed,
            AVG(DATEDIFF(review_date, submission_date)) as avg_review_time,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
            COUNT(CASE WHEN reviewer_status = 'reviewer_approved' THEN 1 END) as reviewer_approved,
            COUNT(CASE WHEN reviewer_status = 'reviewer_rejected' THEN 1 END) as reviewer_rejected,
            MIN(review_date) as first_review,
            MAX(review_date) as latest_review
        FROM paper_submissions
        WHERE reviewed_by IS NOT NULL
        AND DATE(review_date) BETWEEN ? AND ?
        GROUP BY reviewed_by
        ORDER BY papers_reviewed DESC
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get monthly paper trends
function getMonthlyPaperTrends($conn) {
    $stmt = $conn->query("
        SELECT 
            year,
            month,
            new_submissions,
            approved_papers,
            total_views,
            total_downloads,
            avg_review_time
        FROM monthly_stats
        WHERE new_submissions > 0 OR approved_papers > 0
        ORDER BY year DESC, month DESC
        LIMIT 12
    ");
    return $stmt->fetch_all(MYSQLI_ASSOC);
}

// Function to get paper keywords analysis
function getKeywordAnalysis($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            ps.keywords,
            ps.paper_title,
            ps.status,
            COUNT(pm.id) as engagement_count
        FROM paper_submissions ps
        LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
        WHERE DATE(ps.submission_date) BETWEEN ? AND ?
        AND ps.keywords != ''
        GROUP BY ps.id
        ORDER BY engagement_count DESC
        LIMIT 20
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get paper engagement metrics
function getPaperEngagementMetrics($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            DATE(pm.created_at) as date,
            COUNT(CASE WHEN pm.metric_type = 'view' THEN 1 END) as views,
            COUNT(CASE WHEN pm.metric_type = 'download' THEN 1 END) as downloads
        FROM paper_metrics pm
        WHERE DATE(pm.created_at) BETWEEN ? AND ?
        GROUP BY DATE(pm.created_at)
        ORDER BY date DESC
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get overall paper statistics
function getOverallPaperStats($conn) {
    $stats = [];
    
    // Total papers by status
    $result = $conn->query("
        SELECT 
            COUNT(*) as total_papers,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review
        FROM paper_submissions
    ");
    $stats = $result->fetch_assoc();
    
    // Total views and downloads
    $result = $conn->query("
        SELECT 
            COUNT(CASE WHEN metric_type = 'view' THEN 1 END) as total_views,
            COUNT(CASE WHEN metric_type = 'download' THEN 1 END) as total_downloads
        FROM paper_metrics
    ");
    $metrics = $result->fetch_assoc();
    $stats['total_views'] = $metrics['total_views'];
    $stats['total_downloads'] = $metrics['total_downloads'];
    
    return $stats;
}

// Get report data based on type
$reportData = [];
switch($report_type) {
    case 'overview':
        $reportData['submissions'] = getPaperSubmissionStats($conn, $start_date, $end_date);
        $reportData['top_papers'] = getTopPapers($conn, $start_date, $end_date, 5);
        $reportData['by_type'] = getPapersByResearchType($conn, $start_date, $end_date);
        $reportData['overall'] = getOverallPaperStats($conn);
        break;
    case 'submissions':
        $reportData['submissions'] = getPaperSubmissionStats($conn, $start_date, $end_date);
        $reportData['by_affiliation'] = getPapersByAffiliation($conn, $start_date, $end_date);
        $reportData['by_type'] = getPapersByResearchType($conn, $start_date, $end_date);
        break;
    case 'performance':
        $reportData['top_papers'] = getTopPapers($conn, $start_date, $end_date, 15);
        $reportData['engagement'] = getPaperEngagementMetrics($conn, $start_date, $end_date);
        break;
    case 'reviews':
        $reportData['reviewer'] = getReviewerPerformance($conn, $start_date, $end_date);
        $reportData['submissions'] = getPaperSubmissionStats($conn, $start_date, $end_date);
        break;
    case 'trends':
        $reportData['monthly'] = getMonthlyPaperTrends($conn);
        $reportData['engagement'] = getPaperEngagementMetrics($conn, $start_date, $end_date);
        break;
    case 'keywords':
        $reportData['keywords'] = getKeywordAnalysis($conn, $start_date, $end_date);
        $reportData['by_type'] = getPapersByResearchType($conn, $start_date, $end_date);
        break;
    case 'full':
        $reportData['submissions'] = getPaperSubmissionStats($conn, $start_date, $end_date);
        $reportData['top_papers'] = getTopPapers($conn, $start_date, $end_date, 15);
        $reportData['by_affiliation'] = getPapersByAffiliation($conn, $start_date, $end_date);
        $reportData['by_type'] = getPapersByResearchType($conn, $start_date, $end_date);
        $reportData['reviewer'] = getReviewerPerformance($conn, $start_date, $end_date);
        $reportData['monthly'] = getMonthlyPaperTrends($conn);
        $reportData['keywords'] = getKeywordAnalysis($conn, $start_date, $end_date);
        $reportData['engagement'] = getPaperEngagementMetrics($conn, $start_date, $end_date);
        $reportData['overall'] = getOverallPaperStats($conn);
        break;
}

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cnlrrs_paper_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['CNLRRS Research Paper Report']);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, ['Period: ' . $start_date . ' to ' . $end_date]);
    fputcsv($output, []);
    
    foreach ($reportData as $section => $data) {
        fputcsv($output, [strtoupper(str_replace('_', ' ', $section))]);
        if (!empty($data) && is_array($data)) {
            if (isset($data[0]) && is_array($data[0])) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            } else {
                foreach ($data as $key => $value) {
                    fputcsv($output, [$key, $value]);
                }
            }
        }
        fputcsv($output, []);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNLRRS - Research Paper Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-card {
            transition: all 0.3s ease;
            border-left: 4px solid #115D5B;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white shadow-lg no-print">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">
                        <i class="fas fa-file-alt mr-2"></i>Research Paper Reports
                    </h1>
                    <p class="text-sm opacity-90 mt-1">Analytics and insights for submitted research papers</p>
                </div>
                <a href="admin_loggedin_index.php" class="bg-white text-[#115D5B] px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Report Controls -->
    <div class="max-w-7xl mx-auto px-4 py-6 no-print">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <select name="report_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="submissions" <?php echo $report_type === 'submissions' ? 'selected' : ''; ?>>Submissions Analysis</option>
                        <option value="performance" <?php echo $report_type === 'performance' ? 'selected' : ''; ?>>Paper Performance</option>
                        <option value="reviews" <?php echo $report_type === 'reviews' ? 'selected' : ''; ?>>Review Analytics</option>
                        <option value="trends" <?php echo $report_type === 'trends' ? 'selected' : ''; ?>>Trends & Engagement</option>
                        <option value="keywords" <?php echo $report_type === 'keywords' ? 'selected' : ''; ?>>Keywords Analysis</option>
                        <option value="full" <?php echo $report_type === 'full' ? 'selected' : ''; ?>>Full Report</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-[#115D5B] text-white px-4 py-2 rounded-lg hover:bg-[#0d4544] transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Generate Report
                    </button>
                </div>
                <div class="flex items-end gap-2">
                    <button type="button" onclick="window.print()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                       class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-center">
                        <i class="fas fa-download mr-2"></i>CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="max-w-7xl mx-auto px-4 pb-8">
        
        <!-- Report Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="border-b pb-4 mb-4">
                <h2 class="text-2xl font-bold text-[#115D5B]">
                    <?php 
                    $titles = [
                        'overview' => 'Research Paper Overview',
                        'submissions' => 'Paper Submissions Analysis',
                        'performance' => 'Paper Performance Metrics',
                        'reviews' => 'Review Process Analytics',
                        'trends' => 'Paper Trends & Engagement',
                        'keywords' => 'Keywords & Research Focus',
                        'full' => 'Complete Research Paper Report'
                    ];
                    echo $titles[$report_type];
                    ?>
                </h2>
                <p class="text-gray-600 mt-1">
                    Period: <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to 
                    <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong>
                </p>
                <p class="text-sm text-gray-500">Generated on: <?php echo date('F d, Y - h:i A'); ?></p>
            </div>
        </div>

        <?php if (isset($reportData['overall'])): ?>
        <!-- Overall Paper Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
            <div class="report-card bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Papers</p>
                        <p class="text-2xl font-bold text-[#115D5B]"><?php echo $reportData['overall']['total_papers']; ?></p>
                    </div>
                    <i class="fas fa-file-alt text-3xl text-[#115D5B] opacity-50"></i>
                </div>
            </div>
            <div class="report-card bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Approved</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $reportData['overall']['approved']; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-600 opacity-50"></i>
                </div>
            </div>
            <div class="report-card bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-orange-600"><?php echo $reportData['overall']['pending']; ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-orange-600 opacity-50"></i>
                </div>
            </div>
            <div class="report-card bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Views</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo number_format($reportData['overall']['total_views']); ?></p>
                    </div>
                    <i class="fas fa-eye text-3xl text-blue-600 opacity-50"></i>
                </div>
            </div>
            <div class="report-card bg-white p-6 rounded-lg shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Downloads</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo number_format($reportData['overall']['total_downloads']); ?></p>
                    </div>
                    <i class="fas fa-download text-3xl text-purple-600 opacity-50"></i>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['submissions']) && !empty($reportData['submissions'])): ?>
        <!-- Submission Statistics -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-chart-pie mr-2"></i>Paper Submission Status
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <canvas id="submissionStatusChart"></canvas>
                </div>
                <div class="space-y-4">
                    <?php foreach ($reportData['submissions'] as $stat): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-700 capitalize"><?php echo str_replace('_', ' ', $stat['status']); ?></p>
                            <?php if ($stat['avg_review_days']): ?>
                            <p class="text-sm text-gray-500">Avg Review: <?php echo round($stat['avg_review_days'], 1); ?> days</p>
                            <?php endif; ?>
                        </div>
                        <span class="stat-badge <?php 
                            echo $stat['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                ($stat['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                ($stat['status'] === 'under_review' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'));
                        ?>">
                            <?php echo $stat['count']; ?> papers
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['by_type']) && !empty($reportData['by_type'])): ?>
        <!-- Papers by Research Type -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-flask mr-2"></i>Papers by Research Type
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <canvas id="researchTypeChart"></canvas>
                </div>
                <div class="space-y-3">
                    <?php foreach ($reportData['by_type'] as $type): ?>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-semibold text-gray-700 capitalize"><?php echo str_replace('_', ' ', $type['research_type']); ?></p>
                            <span class="stat-badge bg-[#115D5B] text-white"><?php echo $type['count']; ?></span>
                        </div>
                        <?php if (!empty($type['affiliations'])): ?>
                        <p class="text-xs text-gray-500">Affiliations: <?php echo substr($type['affiliations'], 0, 100) . (strlen($type['affiliations']) > 100 ? '...' : ''); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['top_papers']) && !empty($reportData['top_papers'])): ?>
        <!-- Top Performing Papers -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-star mr-2"></i>Top Performing Papers (by Views & Downloads)
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Paper Title</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Author</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Affiliation</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Views</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Downloads</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reportData['top_papers'] as $index => $paper): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="font-bold text-lg <?php echo $index < 3 ? 'text-yellow-600' : 'text-gray-600'; ?>">
                                    <?php if ($index === 0): ?>
                                        <i class="fas fa-trophy"></i>
                                    <?php elseif ($index === 1): ?>
                                        <i class="fas fa-medal"></i>
                                    <?php elseif ($index === 2): ?>
                                        <i class="fas fa-award"></i>
                                    <?php else: ?>
                                        #<?php echo $index + 1; ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($paper['paper_title']); ?></p>
                                <?php if (!empty($paper['keywords'])): ?>
                                <p class="text-xs text-gray-500 mt-1">Keywords: <?php echo htmlspecialchars(substr($paper['keywords'], 0, 50)) . '...'; ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($paper['author_name']); ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                <?php echo htmlspecialchars($paper['affiliation']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-indigo-100 text-indigo-800 text-xs">
                                    <?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-blue-100 text-blue-800">
                                    <i class="fas fa-eye mr-1"></i><?php echo $paper['views']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-green-100 text-green-800">
                                    <i class="fas fa-download mr-1"></i><?php echo $paper['downloads']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['by_affiliation']) && !empty($reportData['by_affiliation'])): ?>
        <!-- Papers by Affiliation -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-university mr-2"></i>Papers by Institutional Affiliation
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Institution</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Total Papers</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Approved</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Pending</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Under Review</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Rejected</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Approval Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reportData['by_affiliation'] as $affiliation): 
                            $approval_rate = $affiliation['total_papers'] > 0 ? 
                                round(($affiliation['approved'] / $affiliation['total_papers']) * 100) : 0;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($affiliation['affiliation']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-gray-100 text-gray-800">
                                    <?php echo $affiliation['total_papers']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-green-100 text-green-800">
                                    <?php echo $affiliation['approved']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-orange-100 text-orange-800">
                                    <?php echo $affiliation['pending']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-blue-100 text-blue-800">
                                    <?php echo $affiliation['under_review']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-red-100 text-red-800">
                                    <?php echo $affiliation['rejected']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $approval_rate; ?>%"></div>
                                    </div>
                                    <span class="text-sm font-semibold"><?php echo $approval_rate; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['reviewer']) && !empty($reportData['reviewer'])): ?>
        <!-- Reviewer Performance -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-user-check mr-2"></i>Reviewer Performance Analysis
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reviewer</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Papers Reviewed</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Avg Review Time</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Approved</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Rejected</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Approval Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reportData['reviewer'] as $reviewer): 
                            $approval_rate = $reviewer['papers_reviewed'] > 0 ? 
                                round(($reviewer['approved'] / $reviewer['papers_reviewed']) * 100) : 0;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reviewer['reviewer']); ?></p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-blue-100 text-blue-800">
                                    <?php echo $reviewer['papers_reviewed']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm">
                                <?php echo round($reviewer['avg_review_time'], 1); ?> days
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-green-100 text-green-800">
                                    <?php echo $reviewer['approved']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-red-100 text-red-800">
                                    <?php echo $reviewer['rejected']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $approval_rate; ?>%"></div>
                                    </div>
                                    <span class="text-sm font-semibold"><?php echo $approval_rate; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['monthly']) && !empty($reportData['monthly'])): ?>
        <!-- Monthly Trends -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-chart-line mr-2"></i>Monthly Paper Trends
            </h3>
            <div class="mb-6">
                <canvas id="monthlyTrendsChart" height="80"></canvas>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Month</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Submissions</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Approved</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Views</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Downloads</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Avg Review Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reportData['monthly'] as $month): 
                            $monthName = date('M Y', mktime(0, 0, 0, $month['month'], 1, $month['year']));
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap font-semibold text-gray-900">
                                <?php echo $monthName; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-blue-100 text-blue-800">
                                    <?php echo $month['new_submissions']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-green-100 text-green-800">
                                    <?php echo $month['approved_papers']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm">
                                <?php echo number_format($month['total_views']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm">
                                <?php echo number_format($month['total_downloads']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm">
                                <?php echo $month['avg_review_time'] ? round($month['avg_review_time'], 1) . ' days' : 'N/A'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['engagement']) && !empty($reportData['engagement'])): ?>
        <!-- Engagement Timeline -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-chart-area mr-2"></i>Paper Engagement Timeline
            </h3>
            <div class="mb-6">
                <canvas id="engagementChart" height="80"></canvas>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php 
                $total_views = array_sum(array_column($reportData['engagement'], 'views'));
                $total_downloads = array_sum(array_column($reportData['engagement'], 'downloads'));
                $avg_views = count($reportData['engagement']) > 0 ? round($total_views / count($reportData['engagement']), 1) : 0;
                $avg_downloads = count($reportData['engagement']) > 0 ? round($total_downloads / count($reportData['engagement']), 1) : 0;
                ?>
                <div class="report-card bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg text-center">
                    <i class="fas fa-eye text-3xl text-blue-600 mb-2"></i>
                    <p class="text-sm text-gray-600">Total Views</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_views); ?></p>
                </div>
                <div class="report-card bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg text-center">
                    <i class="fas fa-download text-3xl text-green-600 mb-2"></i>
                    <p class="text-sm text-gray-600">Total Downloads</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_downloads); ?></p>
                </div>
                <div class="report-card bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg text-center">
                    <i class="fas fa-chart-line text-3xl text-purple-600 mb-2"></i>
                    <p class="text-sm text-gray-600">Avg Views/Day</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $avg_views; ?></p>
                </div>
                <div class="report-card bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg text-center">
                    <i class="fas fa-arrow-down text-3xl text-orange-600 mb-2"></i>
                    <p class="text-sm text-gray-600">Avg Downloads/Day</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $avg_downloads; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($reportData['keywords']) && !empty($reportData['keywords'])): ?>
        <!-- Keywords Analysis -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-bold text-[#115D5B] mb-4">
                <i class="fas fa-tags mr-2"></i>Research Keywords & Focus Areas
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Paper Title</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Keywords</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Engagement</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reportData['keywords'] as $paper): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($paper['paper_title']); ?></p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <?php 
                                    $keywords = explode(',', $paper['keywords']);
                                    foreach (array_slice($keywords, 0, 5) as $keyword): 
                                    ?>
                                    <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">
                                        <?php echo htmlspecialchars(trim($keyword)); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge <?php 
                                    echo $paper['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                        ($paper['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                        ($paper['status'] === 'under_review' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'));
                                ?>">
                                    <?php echo ucfirst($paper['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="stat-badge bg-indigo-100 text-indigo-800">
                                    <?php echo $paper['engagement_count']; ?> interactions
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Summary Section -->
        <div class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white rounded-lg shadow-lg p-8">
            <h3 class="text-2xl font-bold mb-4">
                <i class="fas fa-clipboard-check mr-2"></i>Report Summary
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-lg mb-3 border-b border-white border-opacity-30 pb-2">Key Insights</h4>
                    <ul class="space-y-2 text-sm opacity-90">
                        <?php if (isset($reportData['submissions']) && !empty($reportData['submissions'])): ?>
                        <li><i class="fas fa-check-circle mr-2"></i>Total papers submitted: 
                            <strong><?php echo array_sum(array_column($reportData['submissions'], 'count')); ?></strong>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($reportData['top_papers']) && !empty($reportData['top_papers'])): ?>
                        <li><i class="fas fa-check-circle mr-2"></i>Most viewed paper: 
                            <strong><?php echo htmlspecialchars(substr($reportData['top_papers'][0]['paper_title'], 0, 50)) . '...'; ?></strong>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($reportData['by_type']) && !empty($reportData['by_type'])): ?>
                        <li><i class="fas fa-check-circle mr-2"></i>Most common research type: 
                            <strong><?php echo ucfirst(str_replace('_', ' ', $reportData['by_type'][0]['research_type'])); ?></strong>
                            (<?php echo $reportData['by_type'][0]['count']; ?> papers)
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($reportData['reviewer']) && !empty($reportData['reviewer'])): ?>
                        <li><i class="fas fa-check-circle mr-2"></i>Most active reviewer: 
                            <strong><?php echo htmlspecialchars($reportData['reviewer'][0]['reviewer']); ?></strong>
                            (<?php echo $reportData['reviewer'][0]['papers_reviewed']; ?> papers reviewed)
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-lg mb-3 border-b border-white border-opacity-30 pb-2">Recommendations</h4>
                    <ul class="space-y-2 text-sm opacity-90">
                        <?php if (isset($reportData['submissions'])): 
                            $total = array_sum(array_column($reportData['submissions'], 'count'));
                            $approved_count = 0;
                            foreach ($reportData['submissions'] as $stat) {
                                if ($stat['status'] === 'approved') $approved_count = $stat['count'];
                            }
                            $approval_rate = $total > 0 ? ($approved_count / $total) * 100 : 0;
                            if ($approval_rate < 30):
                        ?>
                        <li><i class="fas fa-exclamation-triangle mr-2"></i>Approval rate is <?php echo round($approval_rate); ?>% - consider providing more feedback to authors</li>
                        <?php elseif ($approval_rate > 70): ?>
                        <li><i class="fas fa-thumbs-up mr-2"></i>Strong approval rate of <?php echo round($approval_rate); ?>% - excellent paper quality</li>
                        <?php endif; endif; ?>
                        
                        <?php if (isset($reportData['overall']) && $reportData['overall']['pending'] > 10): ?>
                        <li><i class="fas fa-clock mr-2"></i>High number of pending papers (<?php echo $reportData['overall']['pending']; ?>) - consider expediting review process</li>
                        <?php endif; ?>
                        
                        <?php if (isset($reportData['by_affiliation']) && count($reportData['by_affiliation']) < 3): ?>
                        <li><i class="fas fa-university mr-2"></i>Limited institutional diversity - consider outreach to more institutions</li>
                        <?php endif; ?>
                        
                        <li><i class="fas fa-lightbulb mr-2"></i>Continue monitoring paper metrics to identify trending research areas</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center py-6 mt-12 no-print">
        <p>&copy; <?php echo date('Y'); ?> CNLRRS. All rights reserved.</p>
        <p class="text-sm opacity-75 mt-1">Research Paper Reporting System</p>
    </footer>

    <script>
    // Submission Status Chart
    <?php if (isset($reportData['submissions']) && !empty($reportData['submissions'])): ?>
    const submissionLabels = <?php echo json_encode(array_map(function($s) { 
        return ucfirst(str_replace('_', ' ', $s['status'])); 
    }, $reportData['submissions'])); ?>;
    const submissionData = <?php echo json_encode(array_column($reportData['submissions'], 'count')); ?>;
    
    new Chart(document.getElementById('submissionStatusChart'), {
        type: 'doughnut',
        data: {
            labels: submissionLabels,
            datasets: [{
                data: submissionData,
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(156, 163, 175, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'Paper Status Distribution'
                }
            }
        }
    });
    <?php endif; ?>

    // Research Type Chart
    <?php if (isset($reportData['by_type']) && !empty($reportData['by_type'])): ?>
    const researchTypeLabels = <?php echo json_encode(array_map(function($t) { 
        return ucfirst(str_replace('_', ' ', $t['research_type'])); 
    }, $reportData['by_type'])); ?>;
    const researchTypeData = <?php echo json_encode(array_column($reportData['by_type'], 'count')); ?>;
    
    new Chart(document.getElementById('researchTypeChart'), {
        type: 'bar',
        data: {
            labels: researchTypeLabels,
            datasets: [{
                label: 'Number of Papers',
                data: researchTypeData,
                backgroundColor: 'rgba(17, 93, 91, 0.8)',
                borderColor: 'rgba(17, 93, 91, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Papers by Research Type'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Monthly Trends Chart
    <?php if (isset($reportData['monthly']) && !empty($reportData['monthly'])): ?>
    const monthlyLabels = <?php echo json_encode(array_reverse(array_map(function($m) {
        return date('M Y', mktime(0, 0, 0, $m['month'], 1, $m['year']));
    }, $reportData['monthly']))); ?>;
    
    const monthlyData = {
        labels: monthlyLabels,
        datasets: [
            {
                label: 'Submissions',
                data: <?php echo json_encode(array_reverse(array_column($reportData['monthly'], 'new_submissions'))); ?>,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            },
            {
                label: 'Approved Papers',
                data: <?php echo json_encode(array_reverse(array_column($reportData['monthly'], 'approved_papers'))); ?>,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            },
            {
                label: 'Total Views',
                data: <?php echo json_encode(array_reverse(array_column($reportData['monthly'], 'total_views'))); ?>,
                borderColor: 'rgb(249, 115, 22)',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    };
    
    new Chart(document.getElementById('monthlyTrendsChart'), {
        type: 'line',
        data: monthlyData,
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Paper Trends'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Papers'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Views'
                    },
                    grid: {
                        drawOnChartArea: false,
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Engagement Chart
    <?php if (isset($reportData['engagement']) && !empty($reportData['engagement'])): ?>
    const engagementDates = <?php echo json_encode(array_map(function($e) {
        return date('M d', strtotime($e['date']));
    }, array_reverse($reportData['engagement']))); ?>;
    
    const engagementData = {
        labels: engagementDates,
        datasets: [
            {
                label: 'Views',
                data: <?php echo json_encode(array_reverse(array_column($reportData['engagement'], 'views'))); ?>,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Downloads',
                data: <?php echo json_encode(array_reverse(array_column($reportData['engagement'], 'downloads'))); ?>,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    };
    
    new Chart(document.getElementById('engagementChart'), {
        type: 'line',
        data: engagementData,
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Daily Paper Engagement'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>