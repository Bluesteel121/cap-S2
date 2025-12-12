<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php';
session_start();

// Check if user is logged in and has admin role
// Using the same session variable pattern as admin_email_templates.php
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get admin information
$user_name = $_SESSION['username'];

// Get date range from request or use defaults
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Function to get submission analysis data
function getSubmissionAnalysis($conn, $start_date, $end_date) {
    $sql = "SELECT 
                ps.id,
                ps.paper_title,
                ps.author_name,
                ps.submission_date,
                ps.status,
                ps.research_type,
                COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as views,
                COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as downloads
            FROM paper_submissions ps
            LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
            WHERE DATE(ps.submission_date) BETWEEN ? AND ?
            GROUP BY ps.id
            ORDER BY ps.submission_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get paper performance data
function getPaperPerformance($conn, $start_date, $end_date) {
    $sql = "SELECT 
                ps.id,
                ps.paper_title,
                ps.author_name,
                ps.submission_date,
                COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
                COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads,
                COALESCE(SUM(CASE WHEN pm.metric_type = 'view' AND DATE(pm.created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as period_views,
                COALESCE(SUM(CASE WHEN pm.metric_type = 'download' AND DATE(pm.created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as period_downloads
            FROM paper_submissions ps
            LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
            WHERE ps.status IN ('approved', 'published')
            GROUP BY ps.id, ps.paper_title, ps.author_name, ps.submission_date
            ORDER BY (
                COALESCE(SUM(CASE WHEN pm.metric_type = 'view' AND DATE(pm.created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) + 
                COALESCE(SUM(CASE WHEN pm.metric_type = 'download' AND DATE(pm.created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) * 2
            ) DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssss', $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Filter out papers with no period activity
    return array_filter($result, function($paper) {
        return $paper['period_views'] > 0 || $paper['period_downloads'] > 0;
    });
}

// Function to get review analytics
function getReviewAnalytics($conn, $start_date, $end_date) {
    $sql = "SELECT 
                ps.status,
                ps.reviewer_status,
                COUNT(*) as count,
                AVG(DATEDIFF(ps.review_date, ps.submission_date)) as avg_review_days,
                ps.reviewed_by
            FROM paper_submissions ps
            WHERE DATE(ps.submission_date) BETWEEN ? AND ?
            GROUP BY ps.status, ps.reviewer_status, ps.reviewed_by
            ORDER BY count DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get revision analytics
function getRevisionAnalytics($conn, $start_date, $end_date) {
    $sql = "SELECT 
                COUNT(*) as total_revisions,
                AVG(revision_count) as avg_revisions_per_paper,
                SUM(CASE WHEN status = 'revision_requested' THEN 1 ELSE 0 END) as pending_revisions,
                SUM(CASE WHEN is_revised = 1 THEN 1 ELSE 0 END) as completed_revisions
            FROM paper_submissions
            WHERE DATE(submission_date) BETWEEN ? AND ?
            AND (status = 'revision_requested' OR is_revised = 1)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get daily engagement data
function getDailyEngagement($conn, $start_date, $end_date) {
    $sql = "SELECT 
                DATE(pm.created_at) as date,
                SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END) as views,
                SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END) as downloads,
                COUNT(DISTINCT pm.user_id) as unique_users
            FROM paper_metrics pm
            WHERE DATE(pm.created_at) BETWEEN ? AND ?
            GROUP BY DATE(pm.created_at)
            ORDER BY date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get keyword analysis
function getKeywordAnalysis($conn, $start_date, $end_date) {
    $sql = "SELECT keywords FROM paper_submissions 
            WHERE DATE(submission_date) BETWEEN ? AND ?
            AND keywords IS NOT NULL AND keywords != ''";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $keyword_count = [];
    $keyword_papers = [];
    
    while ($row = $result->fetch_assoc()) {
        $keywords = array_map('trim', explode(',', strtolower($row['keywords'])));
        foreach ($keywords as $keyword) {
            if (!empty($keyword)) {
                if (!isset($keyword_count[$keyword])) {
                    $keyword_count[$keyword] = 0;
                    $keyword_papers[$keyword] = [];
                }
                $keyword_count[$keyword]++;
            }
        }
    }
    
    arsort($keyword_count);
    return ['counts' => $keyword_count, 'papers' => $keyword_papers];
}

// Get papers by keyword
function getPapersByKeyword($conn, $keyword, $start_date, $end_date) {
    $sql = "SELECT 
                ps.id,
                ps.paper_title,
                ps.author_name,
                ps.submission_date,
                ps.keywords
            FROM paper_submissions ps
            WHERE DATE(ps.submission_date) BETWEEN ? AND ?
            AND LOWER(ps.keywords) LIKE ?
            ORDER BY ps.submission_date DESC";
    
    $stmt = $conn->prepare($sql);
    $search_keyword = '%' . strtolower($keyword) . '%';
    $stmt->bind_param('sss', $start_date, $end_date, $search_keyword);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch data based on report type
$data = [];
switch ($report_type) {
    case 'submissions':
        $data['submissions'] = getSubmissionAnalysis($conn, $start_date, $end_date);
        break;
    case 'performance':
        $data['performance'] = getPaperPerformance($conn, $start_date, $end_date);
        break;
    case 'review':
        $data['review'] = getReviewAnalytics($conn, $start_date, $end_date);
        $data['revisions'] = getRevisionAnalytics($conn, $start_date, $end_date);
        break;
    case 'engagement':
        $data['engagement'] = getDailyEngagement($conn, $start_date, $end_date);
        break;
    case 'keywords':
        $data['keywords'] = getKeywordAnalysis($conn, $start_date, $end_date);
        break;
    default:
        // Overview - get all data
        $data['submissions'] = getSubmissionAnalysis($conn, $start_date, $end_date);
        $data['performance'] = getPaperPerformance($conn, $start_date, $end_date);
        $data['review'] = getReviewAnalytics($conn, $start_date, $end_date);
        $data['engagement'] = getDailyEngagement($conn, $start_date, $end_date);
        $data['keywords'] = getKeywordAnalysis($conn, $start_date, $end_date);
        break;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="research_report_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<html xmlns:x='urn:schemas-microsoft-com:office:excel'>";
    echo "<head><meta charset='UTF-8'></head>";
    echo "<body>";
    echo "<h1>Research Repository Report</h1>";
    echo "<p>Period: " . date('F d, Y', strtotime($start_date)) . " to " . date('F d, Y', strtotime($end_date)) . "</p>";
    
    if ($report_type === 'submissions' || $report_type === 'overview') {
        echo "<h2>Submission Analysis</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Paper Title</th><th>Author</th><th>Date</th><th>Status</th><th>Type</th><th>Views</th><th>Downloads</th></tr>";
        foreach ($data['submissions'] as $paper) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($paper['paper_title']) . "</td>";
            echo "<td>" . htmlspecialchars($paper['author_name']) . "</td>";
            echo "<td>" . date('Y-m-d', strtotime($paper['submission_date'])) . "</td>";
            echo "<td>" . ucfirst($paper['status']) . "</td>";
            echo "<td>" . ucfirst($paper['research_type']) . "</td>";
            echo "<td>" . $paper['views'] . "</td>";
            echo "<td>" . $paper['downloads'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br><br>";
    }
    
    if ($report_type === 'performance' || $report_type === 'overview') {
        echo "<h2>Paper Performance</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Paper Title</th><th>Author</th><th>Total Views</th><th>Total Downloads</th><th>Period Views</th><th>Period Downloads</th></tr>";
        foreach ($data['performance'] as $paper) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($paper['paper_title']) . "</td>";
            echo "<td>" . htmlspecialchars($paper['author_name']) . "</td>";
            echo "<td>" . $paper['total_views'] . "</td>";
            echo "<td>" . $paper['total_downloads'] . "</td>";
            echo "<td>" . $paper['period_views'] . "</td>";
            echo "<td>" . $paper['period_downloads'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br><br>";
    }
    
    if ($report_type === 'keywords' || $report_type === 'overview') {
        echo "<h2>Keyword Analysis</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Keyword</th><th>Frequency</th></tr>";
        foreach (array_slice($data['keywords']['counts'], 0, 20) as $keyword => $count) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($keyword) . "</td>";
            echo "<td>" . $count . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "</body></html>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - CNLRRS</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    #paperModal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        overflow-y: auto;
    }
    
    #paperModal.active {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    
    #paperModal .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        margin: auto;
    }
    
    #paperModal .modal-header {
        padding: 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    #modalBody {
        padding: 24px;
    }
    
    .detail-section {
        margin-bottom: 24px;
    }
    
    .detail-label {
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }
    
    .detail-value {
        color: #1f2937;
        line-height: 1.5;
    }
    
    .author-tag {
        display: inline-block;
        background-color: #e0f2f1;
        color: #115D5B;
        padding: 6px 12px;
        border-radius: 6px;
        margin-right: 8px;
        margin-bottom: 8px;
        font-size: 0.875rem;
    }
    
    .authors-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .metric-card {
        background-color: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
    }
    
    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #115D5B;
    }
    
    .metric-label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .file-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #115D5B;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    
    .file-link:hover {
        background-color: #0d4a49;
    }

        .report-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        @media print {
            .no-print { display: none !important; }
            .print-full-width { width: 100% !important; max-width: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-[#115D5B] text-white shadow-lg no-print">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <img src="Images/CNLRRS_icon.png" alt="Logo" class="h-10 w-10 mr-2">
                    <span class="text-xl font-bold">CNLRRS Admin Reports</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="admin_loggedin_index.php" class="hover:underline">
                     <i class="fas fa-arrow-left">    </i>       Dashboard
                    </a>

                    <span class="px-4 py-2 bg-[#103635] rounded-xl">
                    <?php echo htmlspecialchars($user_name); ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Report Header & Filters -->
    <div class="container mx-auto px-4 py-6 no-print">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-chart-line mr-3 text-[#115D5B]"></i>Research Reports
                    </h1>

                </div>
            </div>

            <!-- Filter Form -->
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Report Type</label>
                    <select name="report_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Full Report</option>
                        <option value="submissions" <?php echo $report_type === 'submissions' ? 'selected' : ''; ?>>Submission Analysis</option>
                        <option value="performance" <?php echo $report_type === 'performance' ? 'selected' : ''; ?>>Paper Performance</option>
                        <option value="review" <?php echo $report_type === 'review' ? 'selected' : ''; ?>>Review Analytics</option>
                        <option value="engagement" <?php echo $report_type === 'engagement' ? 'selected' : ''; ?>>Trends & Engagement</option>
                        <option value="keywords" <?php echo $report_type === 'keywords' ? 'selected' : ''; ?>>Keyword Search</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i>Generate
                    </button>
                    <button type="button" onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="container mx-auto px-4 pb-8">
        <?php if ($report_type === 'submissions' || $report_type === 'overview'): ?>
        <!-- Submission Analysis -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-file-upload mr-2 text-blue-600"></i>Submission Analysis
            </h2>
            <p class="text-gray-600 mb-4">Analysis of research papers submitted during the selected period</p>
            
            <!-- Submissions Chart -->
            <div class="mb-6">
                <canvas id="submissionsChart" height="80"></canvas>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Paper Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Author</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($data['submissions'] as $paper): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars(substr($paper['paper_title'], 0, 60)) . (strlen($paper['paper_title']) > 60 ? '...' : ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($paper['author_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo date('M d, Y', strtotime($paper['submission_date'])); ?></td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs <?php 
                                    echo $paper['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                        ($paper['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                        ($paper['status'] === 'under_review' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'));
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $paper['status'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?></td>
                            <td class="px-4 py-3 text-sm text-center no-print">
                                <button onclick="viewPaperDetails(<?php echo $paper['id']; ?>)" 
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition">
    <i class="fas fa-info-circle mr-2"></i>Details
</button>
                               <button onclick="viewPaperPDF(<?php echo $paper['id']; ?>)" 
        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition">
    <i class="fas fa-file-pdf mr-2"></i>View PDF
</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 pt-6 border-t">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600 font-semibold">Total Submissions</div>
                    <div class="text-3xl font-bold text-blue-700 mt-2"><?php echo count($data['submissions']); ?></div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600 font-semibold">Approved</div>
                    <div class="text-3xl font-bold text-green-700 mt-2">
                        <?php echo count(array_filter($data['submissions'], function($p) { return $p['status'] === 'approved'; })); ?>
                    </div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="text-sm text-yellow-600 font-semibold">Under Review</div>
                    <div class="text-3xl font-bold text-yellow-700 mt-2">
                        <?php echo count(array_filter($data['submissions'], function($p) { return $p['status'] === 'under_review' || $p['status'] === 'pending'; })); ?>
                    </div>
                </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'performance' || $report_type === 'overview'): ?>
        <!-- Paper Performance -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-bar mr-2 text-green-600"></i>Paper Performance
            </h2>
            <p class="text-gray-600 mb-4">Top performing papers based on views and downloads</p>
            
            <!-- Performance Chart -->
            <div class="mb-6">
                <canvas id="performanceChart" height="80"></canvas>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Paper Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Author</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Period Views</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Period Downloads</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Total Views</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Total Downloads</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        $rank = 1;
                        foreach ($data['performance'] as $paper): 
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-bold text-gray-900">#<?php echo $rank++; ?></td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars(substr($paper['paper_title'], 0, 60)) . (strlen($paper['paper_title']) > 60 ? '...' : ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo htmlspecialchars($paper['author_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">
                                    <?php echo $paper['period_views']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full font-semibold">
                                    <?php echo $paper['period_downloads']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-gray-600"><?php echo $paper['total_views']; ?></td>
                            <td class="px-4 py-3 text-sm text-center text-gray-600"><?php echo $paper['total_downloads']; ?></td>
                            <td class="px-4 py-3 text-sm text-center no-print">
                                <button onclick="viewPaperDetails(<?php echo $paper['id']; ?>)" 
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition">
    <i class="fas fa-info-circle mr-2"></i>Details
</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'review' || $report_type === 'overview'): ?>
        <!-- Review Analytics -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-clipboard-check mr-2 text-purple-600"></i>Review Analytics
            </h2>
            <p class="text-gray-600 mb-4">Statistics on paper review process and revisions</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Review Status Distribution -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Review Status Distribution</h3>
                    <canvas id="reviewStatusChart"></canvas>
        </div>
            <!-- Review Details Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Count</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Avg. Review Days</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Reviewed By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($data['review'] as $review): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs <?php 
                                    echo $review['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                        ($review['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800');
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $review['status'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center font-semibold"><?php echo $review['count']; ?></td>
                            <td class="px-4 py-3 text-sm text-center">
                                <?php echo $review['avg_review_days'] ? number_format($review['avg_review_days'], 1) . ' days' : 'N/A'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $review['reviewed_by'] ?? 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'engagement' || $report_type === 'overview'): ?>
        <!-- Trends & Engagement -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-line mr-2 text-orange-600"></i>Trends & Engagement
            </h2>
            <p class="text-gray-600 mb-4">Daily engagement metrics and system usage trends</p>
            
            <!-- Engagement Chart -->
            <div class="mb-6">
                <canvas id="engagementChart" height="80"></canvas>
            </div>
            
            <!-- Daily Engagement Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Views</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Downloads</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Unique Users</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Total Engagement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($data['engagement'] as $day): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                <?php echo date('M d, Y', strtotime($day['date'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                                    <?php echo $day['views']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full">
                                    <?php echo $day['downloads']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full">
                                    <?php echo $day['unique_users']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center font-semibold">
                                <?php echo $day['views'] + $day['downloads']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600 font-semibold">Total Views</div>
                    <div class="text-3xl font-bold text-blue-700 mt-2">
                        <?php echo array_sum(array_column($data['engagement'], 'views')); ?>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600 font-semibold">Total Downloads</div>
                    <div class="text-3xl font-bold text-green-700 mt-2">
                        <?php echo array_sum(array_column($data['engagement'], 'downloads')); ?>
                    </div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm text-purple-600 font-semibold">Avg. Daily Users</div>
                    <div class="text-3xl font-bold text-purple-700 mt-2">
                        <?php echo count($data['engagement']) > 0 ? number_format(array_sum(array_column($data['engagement'], 'unique_users')) / count($data['engagement']), 1) : 0; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'keywords' || $report_type === 'overview'): ?>
        <!-- Keyword Analysis -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-tags mr-2 text-indigo-600"></i>Keyword Analysis
            </h2>
            <p class="text-gray-600 mb-4">Most frequently used keywords in research papers</p>
            
            <!-- Keyword Chart -->
            <div class="mb-6">
                <canvas id="keywordChart" height="80"></canvas>
            </div>
            
            <!-- Top Keywords -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php 
                $top_keywords = array_slice($data['keywords']['counts'], 0, 15);
                foreach ($top_keywords as $keyword => $count): 
                ?>
                <div class="report-card bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-4 cursor-pointer"
                     onclick="showKeywordPapers('<?php echo htmlspecialchars($keyword); ?>')">
                    <div class="flex justify-between items-start mb-2">
                        <div class="text-lg font-bold text-indigo-700"><?php echo htmlspecialchars($keyword); ?></div>
                        <div class="bg-indigo-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm">
                            <?php echo $count; ?>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        Used in <?php echo $count; ?> paper<?php echo $count > 1 ? 's' : ''; ?>
                    </div>
                    <div class="mt-2 text-xs text-indigo-600 hover:text-indigo-800">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Keyword Papers Modal -->
    <div id="keywordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">
                        Papers with keyword: <span id="modalKeyword" class="text-indigo-600"></span>
                    </h3>
                    <button onclick="closeKeywordModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="keywordPapers" class="space-y-3">
                    <!-- Papers will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Paper Details Modal -->
    <div id="paperModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Paper Details</h2>
            <button class="modal-close" onclick="closePaperModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

    <script>
    const papers = <?php echo json_encode($data['submissions'] ?? []); ?>;
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper function to format dates
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    // View Paper Details - Fixed version
async function viewPaperDetails(paperId) {
    const modal = document.getElementById('paperModal');
    const modalBody = document.getElementById('modalBody');
    
    // Show modal with loading state
    modal.classList.add('active');
    modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin text-3xl text-[#115D5B]"></i><p class="mt-4 text-gray-600">Loading paper details...</p></div>';
    
    try {
        const response = await fetch(`get_paper_details.php?id=${paperId}`);
        const data = await response.json();
        
        if (data.error) {
            modalBody.innerHTML = `<div style="color: #dc2626; text-align: center; padding: 40px;"><i class="fas fa-exclamation-triangle text-3xl mb-4"></i><p>${data.error}</p></div>`;
            return;
        }
        
        const paper = data.paper;
        
        // Handle authors field - check both 'authors' and 'author_name'
        const authorsField = paper.authors || paper.author_name || 'Unknown';
        const authorsList = authorsField.split(',').map(author => 
            `<span class="author-tag">${escapeHtml(author.trim())}</span>`
        ).join('');
        
        // Build the modal content
        modalBody.innerHTML = `
            <div class="detail-section">
                <div class="detail-label">Title</div>
                <div class="detail-value" style="font-size: 1.25rem; font-weight: 600; margin-top: 8px;">${escapeHtml(paper.paper_title)}</div>
            </div>
            
            <div class="detail-section">
                <div class="detail-label">Authors</div>
                <div class="authors-list" style="margin-top: 8px;">
                    ${authorsList}
                </div>
            </div>
            
            <div class="detail-section">
                <div class="detail-label">Research Type</div>
                <div class="detail-value" style="margin-top: 8px;">${escapeHtml(paper.research_type || 'N/A')}</div>
            </div>
            
            <div class="detail-section">
                <div class="detail-label">Abstract</div>
                <div class="detail-value" style="margin-top: 8px; line-height: 1.6;">${escapeHtml(paper.abstract)}</div>
            </div>
            
            <div class="detail-section">
                <div class="detail-label">Keywords</div>
                <div class="detail-value" style="margin-top: 8px;">${escapeHtml(paper.keywords)}</div>
            </div>
            
            <div class="detail-section">
                <div class="detail-label">Status</div>
                <div class="detail-value" style="margin-top: 8px;">
                    <span class="status-badge status-${paper.status.toLowerCase().replace(' ', '_')}">${paper.status.replace('_', ' ').toUpperCase()}</span>
                </div>
            </div>
            
            ${paper.reviewer_comments ? `
            <div class="detail-section">
                <div class="detail-label">Reviewer Comments</div>
                <div class="detail-value" style="background-color: #fef3c7; padding: 16px; border-radius: 6px; border-left: 4px solid #f59e0b; margin-top: 8px;">
                    ${escapeHtml(paper.reviewer_comments).replace(/\n/g, '<br>')}
                </div>
            </div>
            ` : ''}
            
            <div class="detail-section">
                <div class="detail-label">Paper Metrics</div>
                <div class="metrics-grid" style="margin-top: 8px;">
                    <div class="metric-card">
                        <div class="metric-value">${paper.total_views || 0}</div>
                        <div class="metric-label">Views</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">${paper.total_downloads || 0}</div>
                        <div class="metric-label">Downloads</div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <div class="detail-label">Paper File</div>
                <div class="detail-value" style="margin-top: 8px;">
                    <a href="${escapeHtml(paper.file_path)}" class="file-link" target="_blank">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download Paper
                    </a>
                </div>
            </div>
            
            <div class="detail-section">
                <div class="detail-label">Submission Information</div>
                <div class="detail-value" style="font-size: 0.875rem; color: #6b7280; margin-top: 8px; line-height: 1.8;">
                    <div style="margin-bottom: 4px;">Submitted by: <strong>${escapeHtml(paper.user_name || paper.username || 'Unknown')}</strong></div>
                    <div style="margin-bottom: 4px;">Submission Date: <strong>${formatDate(paper.submission_date)}</strong></div>
                    ${paper.review_date ? `<div style="margin-bottom: 4px;">Review Date: <strong>${formatDate(paper.review_date)}</strong></div>` : ''}
                    ${paper.reviewed_by ? `<div>Reviewed by: <strong>${escapeHtml(paper.reviewed_by)}</strong></div>` : ''}
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error fetching paper details:', error);
        modalBody.innerHTML = `<div style="color: #dc2626; text-align: center; padding: 40px;"><i class="fas fa-exclamation-triangle text-3xl mb-4"></i><p>Failed to load paper details. Please try again.</p></div>`;
    }
}
// Function to close the modal
function closePaperModal() {
    const modal = document.getElementById('paperModal');
    modal.classList.remove('active');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('paperModal');
    if (event.target === modal) {
        closePaperModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePaperModal();
    }
});

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to format dates
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

async function viewPaperPDF(paperId) {
    try {
        // Try to find paper in local papers array first
        const paper = papers.find(p => p.id == paperId);
        if (paper && paper.file_path) {
            window.open(paper.file_path, '_blank');
            return;
        }
        
        // If not found locally, fetch from server
        const response = await fetch(`get_paper_details.php?id=${paperId}`);
        const data = await response.json();
        
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        if (data.paper && data.paper.file_path) {
            window.open(data.paper.file_path, '_blank');
        } else {
            alert('PDF file not found for this paper');
        }
    } catch (error) {
        console.error('Error opening PDF:', error);
        alert('Failed to open PDF. Please try again.');
    }
}

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('paperModal');
        if (event.target === modal) {
            closePaperModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closePaperModal();
        }
    });

    function exportToExcel() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'excel');
        window.location.href = '?' + params.toString();
    }

 function showKeywordPapers(keyword) {
        document.getElementById('modalKeyword').textContent = keyword;
        document.getElementById('keywordModal').classList.remove('hidden');
        
        const container = document.getElementById('keywordPapers');
        container.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i><p class="mt-3 text-gray-600">Loading papers...</p></div>';
        
        const params = new URLSearchParams(window.location.search);
        const startDate = params.get('start_date') || '<?php echo $start_date; ?>';
        const endDate = params.get('end_date') || '<?php echo $end_date; ?>';
        
        fetch(`get_keyword.php?keyword=${encodeURIComponent(keyword)}&start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = `<p class="text-red-600">${data.error}</p>`;
                    return;
                }
                
                if (data.length === 0) {
                    container.innerHTML = '<p class="text-gray-600 text-center py-8">No papers found with this keyword.</p>';
                    return;
                }
                
                container.innerHTML = data.map(paper => `
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 text-lg mb-2">${escapeHtml(paper.paper_title)}</h4>
                                <p class="text-sm text-gray-600 mb-3">
                                    <i class="fas fa-user mr-1"></i> ${escapeHtml(paper.author_name)} • 
                                    <i class="fas fa-calendar ml-2 mr-1"></i> ${new Date(paper.submission_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                </p>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    ${paper.keywords.split(',').map(k => {
                                        const trimmed = k.trim();
                                        const isSelected = trimmed.toLowerCase() === keyword.toLowerCase();
                                        return `<span class="px-2 py-1 ${isSelected ? 'bg-indigo-600 text-white font-semibold' : 'bg-indigo-100 text-indigo-700'} text-xs rounded">${escapeHtml(trimmed)}</span>`;
                                    }).join('')}
                                </div>
                                <div class="flex gap-3 mt-3">
                                    <button onclick="viewPaperDetails(${paper.id}); closeKeywordModal();" 
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                        <i class="fas fa-info-circle mr-2"></i>View Details
                                    </button>
                                    <button onclick="viewPaperPDF(${paper.id})" 
                                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                        <i class="fas fa-file-pdf mr-2"></i>View PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error fetching keyword papers:', error);
                container.innerHTML = '<p class="text-red-600 text-center py-8">Failed to load papers. Please try again.</p>';
            });
    }

    function closeKeywordModal() {
        document.getElementById('keywordModal').classList.add('hidden');
    }
    <?php if ($report_type === 'submissions' || $report_type === 'overview'): ?>
    // Submissions Chart
    const submissionsCtx = document.getElementById('submissionsChart').getContext('2d');
    const submissionsData = <?php echo json_encode($data['submissions']); ?>;
    
    const statusGroups = {};
    submissionsData.forEach(paper => {
        const status = paper.status;
        if (!statusGroups[status]) {
            statusGroups[status] = 0;
        }
        statusGroups[status]++;
    });
    
    new Chart(submissionsCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(statusGroups).map(s => s.replace('_', ' ').toUpperCase()),
            datasets: [{
                label: 'Number of Papers',
                data: Object.values(statusGroups),
                backgroundColor: [
                    'rgba(34, 197, 94, 0.6)',
                    'rgba(239, 68, 68, 0.6)',
                    'rgba(59, 130, 246, 0.6)',
                    'rgba(251, 191, 36, 0.6)',
                    'rgba(168, 85, 247, 0.6)',
                    'rgba(236, 72, 153, 0.6)'
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)',
                    'rgb(59, 130, 246)',
                    'rgb(251, 191, 36)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Papers by Status'
                }
            }
        }
    });
    <?php endif; ?>

    <?php if ($report_type === 'performance' || $report_type === 'overview'): ?>
    const perfCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(perfCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($p) { return substr($p['paper_title'], 0, 30) . '...'; }, array_slice($data['performance'], 0, 10))); ?>,
            datasets: [{
                label: 'Views',
                data: <?php echo json_encode(array_column(array_slice($data['performance'], 0, 10), 'period_views')); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }, {
                label: 'Downloads',
                data: <?php echo json_encode(array_column(array_slice($data['performance'], 0, 10), 'period_downloads')); ?>,
                backgroundColor: 'rgba(34, 197, 94, 0.5)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>

    <?php if ($report_type === 'review' || $report_type === 'overview'): ?>
    const reviewCtx = document.getElementById('reviewStatusChart').getContext('2d');
    const reviewData = <?php echo json_encode($data['review']); ?>;
    const statusCounts = {};
    reviewData.forEach(item => {
        statusCounts[item.status] = (statusCounts[item.status] || 0) + parseInt(item.count);
    });
    
    new Chart(reviewCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(statusCounts).map(s => s.replace('_', ' ').toUpperCase()),
            datasets: [{
                label: 'Number of Papers',
                data: Object.values(statusCounts),
                backgroundColor: [
                    'rgba(34, 197, 94, 0.6)',
                    'rgba(239, 68, 68, 0.6)',
                    'rgba(59, 130, 246, 0.6)',
                    'rgba(251, 191, 36, 0.6)',
                    'rgba(168, 85, 247, 0.6)'
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)',
                    'rgb(59, 130, 246)',
                    'rgb(251, 191, 36)',
                    'rgb(168, 85, 247)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    <?php endif; ?>

    <?php if ($report_type === 'engagement' || $report_type === 'overview'): ?>
    const engCtx = document.getElementById('engagementChart').getContext('2d');
    new Chart(engCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($d) { return date('M d', strtotime($d['date'])); }, $data['engagement'])); ?>,
            datasets: [{
                label: 'Views',
                data: <?php echo json_encode(array_column($data['engagement'], 'views')); ?>,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Downloads',
                data: <?php echo json_encode(array_column($data['engagement'], 'downloads')); ?>,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>

    <?php if ($report_type === 'keywords' || $report_type === 'overview'): ?>
    const kwCtx = document.getElementById('keywordChart').getContext('2d');
    const topKeywords = <?php echo json_encode(array_slice($data['keywords']['counts'], 0, 15)); ?>;
    
    new Chart(kwCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(topKeywords),
            datasets: [{
                label: 'Frequency',
                data: Object.values(topKeywords),
                backgroundColor: 'rgba(99, 102, 241, 0.5)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>
</script>
</body>
</html> 