<?php
// admin_reports_handler.php
// All database queries, HTML rendering functions, and chart generation

// ============================================
// DATABASE QUERY FUNCTIONS
// ============================================

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

function getPapersByStatus($conn, $start_date, $end_date, $status) {
    $stmt = $conn->prepare("
        SELECT 
            id, paper_title, author_name, affiliation, research_type,
            submission_date, review_date, reviewed_by, abstract, file_path,
            DATEDIFF(review_date, submission_date) as review_days
        FROM paper_submissions
        WHERE DATE(submission_date) BETWEEN ? AND ?
        AND status = ?
        ORDER BY submission_date DESC
    ");
    $stmt->bind_param("sss", $start_date, $end_date, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

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

function getTopPapers($conn, $start_date, $end_date, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            ps.id, ps.paper_title, ps.author_name, ps.affiliation, ps.research_type,
            COUNT(CASE WHEN pm.metric_type = 'view' THEN 1 END) as views,
            COUNT(CASE WHEN pm.metric_type = 'download' THEN 1 END) as downloads,
            ps.submission_date, ps.status, ps.keywords
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

function getReviewerPerformance($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            reviewed_by as reviewer,
            COUNT(*) as papers_reviewed,
            AVG(DATEDIFF(review_date, submission_date)) as avg_review_time,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
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

function getMonthlyPaperTrends($conn) {
    $stmt = $conn->query("
        SELECT 
            year, month, new_submissions, approved_papers,
            total_views, total_downloads, avg_review_time
        FROM monthly_stats
        WHERE new_submissions > 0 OR approved_papers > 0
        ORDER BY year DESC, month DESC
        LIMIT 12
    ");
    return $stmt->fetch_all(MYSQLI_ASSOC);
}

function getKeywordAnalysis($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            ps.keywords, ps.paper_title, ps.status,
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

function getOverallPaperStats($conn) {
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

// ============================================
// REPORT DATA HANDLER
// ============================================

function getReportData($conn, $report_type, $start_date, $end_date) {
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
    
    // Add detailed papers by status
    if (isset($reportData['submissions'])) {
        global $conn;
        $reportData['papers_by_status'] = [];
        foreach ($reportData['submissions'] as $stat) {
            $reportData['papers_by_status'][$stat['status']] = getPapersByStatus($conn, $start_date, $end_date, $stat['status']);
        }
    }
    
    return $reportData;
}

// ============================================
// CSV EXPORT
// ============================================

function exportToCSV($reportData, $start_date, $end_date) {
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
}

// ============================================
// HTML RENDERING FUNCTIONS
// ============================================

function renderModals() {
    ?>
    <!-- Paper View Modal -->
    <div id="paperViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-6 rounded-t-xl sticky top-0 z-10">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold"><i class="fas fa-file-alt mr-3"></i>Paper Details</h3>
                    <button onclick="closePaperViewModal()" class="text-white hover:text-gray-200 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="paperViewContent" class="p-6"></div>
        </div>
    </div>

    <!-- PDF Viewer Modal -->
    <div id="pdfViewerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full h-[90vh] flex flex-col">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-file-pdf mr-2"></i>Paper Viewer</h3>
                <button onclick="closePDFViewer()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="pdfViewerContent" class="flex-1 bg-gray-100 flex items-center justify-center overflow-auto">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">Loading PDF...</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function renderOverallStats($overall) {
    ?>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <div class="report-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Papers</p>
                    <p class="text-2xl font-bold text-[#115D5B]"><?php echo $overall['total_papers']; ?></p>
                </div>
                <i class="fas fa-file-alt text-3xl text-[#115D5B] opacity-50"></i>
            </div>
        </div>
        <div class="report-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo $overall['approved']; ?></p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-600 opacity-50"></i>
            </div>
        </div>
        <div class="report-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-orange-600"><?php echo $overall['pending']; ?></p>
                </div>
                <i class="fas fa-clock text-3xl text-orange-600 opacity-50"></i>
            </div>
        </div>
        <div class="report-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Views</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($overall['total_views']); ?></p>
                </div>
                <i class="fas fa-eye text-3xl text-blue-600 opacity-50"></i>
            </div>
        </div>
        <div class="report-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Downloads</p>
                    <p class="text-2xl font-bold text-purple-600"><?php echo number_format($overall['total_downloads']); ?></p>
                </div>
                <i class="fas fa-download text-3xl text-purple-600 opacity-50"></i>
            </div>
        </div>
    </div>
    <?php
}

function renderSubmissionsSection($reportData, $statusConfig) {
    $submissions = $reportData['submissions'];
    $papers_by_status = $reportData['papers_by_status'];
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-chart-bar mr-2"></i>Paper Submission Status Analysis
        </h3>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <?php foreach ($submissions as $stat): 
                $config = $statusConfig[$stat['status']] ?? ['icon' => 'file', 'color' => 'gray', 'label' => ucfirst($stat['status'])];
            ?>
            <div class="report-card bg-gradient-to-br from-<?php echo $config['color']; ?>-50 to-<?php echo $config['color']; ?>-100 p-4 rounded-lg cursor-pointer transition-transform hover:scale-105"
                 onclick="togglePaperList('<?php echo $stat['status']; ?>')">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-<?php echo $config['icon']; ?> text-2xl text-<?php echo $config['color']; ?>-600"></i>
                    <span class="text-3xl font-bold text-<?php echo $config['color']; ?>-700"><?php echo $stat['count']; ?></span>
                </div>
                <p class="text-sm text-<?php echo $config['color']; ?>-800 font-medium"><?php echo $config['label']; ?></p>
                <p class="text-xs text-<?php echo $config['color']; ?>-600 font-medium mt-2">
                    <i class="fas fa-mouse-pointer mr-1"></i>Click to view papers
                </p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <canvas id="submissionStatusChart" height="100"></canvas>
        </div>

        <?php foreach ($submissions as $stat): 
            $config = $statusConfig[$stat['status']] ?? ['icon' => 'file', 'color' => 'gray', 'label' => ucfirst($stat['status'])];
            $papers = $papers_by_status[$stat['status']] ?? [];
        ?>
        <div id="papers-<?php echo $stat['status']; ?>" class="paper-list-panel hidden mt-4 border-l-4 border-<?php echo $config['color']; ?>-500 bg-<?php echo $config['color']; ?>-50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-bold text-<?php echo $config['color']; ?>-700">
                    <i class="fas fa-<?php echo $config['icon']; ?> mr-2"></i>
                    <?php echo $config['label']; ?> Papers (<?php echo count($papers); ?>)
                </h4>
                <button onclick="togglePaperList('<?php echo $stat['status']; ?>')" 
                        class="text-<?php echo $config['color']; ?>-600 hover:text-<?php echo $config['color']; ?>-800">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <?php if (!empty($papers)): ?>
            <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
                <?php foreach ($papers as $paper): ?>
                <div class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h5 class="font-semibold text-gray-900 mb-1">
                                <?php echo htmlspecialchars($paper['paper_title']); ?>
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
                                <p><i class="fas fa-user mr-1 text-gray-400"></i>
                                    <strong>Author:</strong> <?php echo htmlspecialchars($paper['author_name']); ?>
                                </p>
                                <p><i class="fas fa-university mr-1 text-gray-400"></i>
                                    <strong>Affiliation:</strong> <?php echo htmlspecialchars($paper['affiliation']); ?>
                                </p>
                                <p><i class="fas fa-flask mr-1 text-gray-400"></i>
                                    <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?>
                                </p>
                                <p><i class="fas fa-calendar mr-1 text-gray-400"></i>
                                    <strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($paper['submission_date'])); ?>
                                </p>
                            </div>
                            <?php if (!empty($paper['abstract'])): ?>
                            <div class="mb-3 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                <strong>Abstract:</strong> 
                                <?php echo htmlspecialchars(substr($paper['abstract'], 0, 150)) . '...'; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4 flex flex-col gap-2">
                            <button onclick="viewPaperInModal(<?php echo $paper['id']; ?>)" 
                                   class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors whitespace-nowrap">
                                <i class="fas fa-eye mr-1"></i>View
                            </button>
                            <?php if (!empty($paper['file_path'])): ?>
                            <button onclick="viewPaperPDF(<?php echo $paper['id']; ?>)" 
                                   class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors whitespace-nowrap">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-4">No papers found with this status.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function renderResearchTypeSection($by_type) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-flask mr-2"></i>Papers by Research Type
        </h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <canvas id="researchTypeChart" height="80"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($by_type as $type): ?>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-700 capitalize text-sm"><?php echo str_replace('_', ' ', $type['research_type']); ?></p>
                    <span class="stat-badge bg-[#115D5B] text-white"><?php echo $type['count']; ?> papers</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function renderTopPapersSection($top_papers) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-star mr-2"></i>Top Performing Papers
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Rank</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Paper Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Author</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Views</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Downloads</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($top_papers as $index => $paper): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="font-bold text-lg <?php echo $index < 3 ? 'text-yellow-600' : 'text-gray-600'; ?>">
                                <?php if ($index === 0): ?><i class="fas fa-trophy"></i>
                                <?php elseif ($index === 1): ?><i class="fas fa-medal"></i>
                                <?php elseif ($index === 2): ?><i class="fas fa-award"></i>
                                <?php else: ?>#<?php echo $index + 1; ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($paper['paper_title']); ?></p>
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($paper['author_name']); ?></td>
                        <td class="px-4 py-4 text-center">
                            <span class="stat-badge bg-blue-100 text-blue-800">
                                <i class="fas fa-eye mr-1"></i><?php echo $paper['views']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
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
    <?php
}

function renderAffiliationSection($by_affiliation) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-university mr-2"></i>Papers by Institutional Affiliation
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Institution</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Approved</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Approval Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($by_affiliation as $affiliation): 
                        $approval_rate = $affiliation['total_papers'] > 0 ? 
                            round(($affiliation['approved'] / $affiliation['total_papers']) * 100) : 0;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($affiliation['affiliation']); ?></td>
                        <td class="px-4 py-4 text-center">
                            <span class="stat-badge bg-gray-100 text-gray-800"><?php echo $affiliation['total_papers']; ?></span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="stat-badge bg-green-100 text-green-800"><?php echo $affiliation['approved']; ?></span>
                        </td>
                        <td class="px-4 py-4 text-center">
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
    <?php
}

function renderReviewerSection($reviewer) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-user-check mr-2"></i>Reviewer Performance Analysis
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Reviewer</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Papers Reviewed</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Avg Time</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Approved</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Rejected</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reviewer as $rev): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rev['reviewer']); ?></td>
                        <td class="px-4 py-4 text-center">
                            <span class="stat-badge bg-blue-100 text-blue-800"><?php echo $rev['papers_reviewed']; ?></span>
                        </td>
                        <td class="px-4 py-4 text-center text-sm"><?php echo round($rev['avg_review_time'], 1); ?> days</td>
                        <td class="px-4 py-4 text-center">
                            <span class="stat-badge bg-green-100 text-green-800"><?php echo $rev['approved']; ?></span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="stat-badge bg-red-100 text-red-800"><?php echo $rev['rejected']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function renderMonthlyTrendsSection($monthly) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-chart-line mr-2"></i>Monthly Paper Trends
        </h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <canvas id="monthlyTrendsChart" height="100"></canvas>
        </div>
    </div>
    <?php
}

function renderEngagementSection($engagement) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-chart-area mr-2"></i>Paper Engagement Timeline
        </h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <canvas id="engagementChart" height="100"></canvas>
        </div>
    </div>
    <?php
}

function renderKeywordsSection($keywords) {
    ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-bold text-[#115D5B] mb-4">
            <i class="fas fa-tags mr-2"></i>Research Keywords & Focus Areas
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Paper Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Keywords</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Engagement</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($keywords as $paper): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($paper['paper_title']); ?></td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-1">
                                <?php 
                                $kws = explode(',', $paper['keywords']);
                                foreach (array_slice($kws, 0, 5) as $keyword): 
                                ?>
                                <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">
                                    <?php echo htmlspecialchars(trim($keyword)); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="stat-badge <?php 
                                echo $paper['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                    ($paper['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800');
                            ?>">
                                <?php echo ucfirst($paper['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
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
    <?php
}

function renderSummarySection($reportData) {
    ?>
    <div class="bg-gradient-to-r from-[#115D5B] to-[#103625] text-white rounded-lg shadow-lg p-8">
        <h3 class="text-2xl font-bold mb-4">
            <i class="fas fa-clipboard-check mr-2"></i>Report Summary
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-lg mb-3 border-b border-white border-opacity-30 pb-2">Key Insights</h4>
                <ul class="space-y-2 text-sm opacity-90">
                    <?php if (isset($reportData['submissions'])): ?>
                    <li><i class="fas fa-check-circle mr-2"></i>Total papers submitted: 
                        <strong><?php echo array_sum(array_column($reportData['submissions'], 'count')); ?></strong>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($reportData['top_papers'][0])): ?>
                    <li><i class="fas fa-check-circle mr-2"></i>Most viewed paper: 
                        <strong><?php echo htmlspecialchars(substr($reportData['top_papers'][0]['paper_title'], 0, 50)) . '...'; ?></strong>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($reportData['by_type'][0])): ?>
                    <li><i class="fas fa-check-circle mr-2"></i>Most common research type: 
                        <strong><?php echo ucfirst(str_replace('_', ' ', $reportData['by_type'][0]['research_type'])); ?></strong>
                        (<?php echo $reportData['by_type'][0]['count']; ?> papers)
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold text-lg mb-3 border-b border-white border-opacity-30 pb-2">Recommendations</h4>
                <ul class="space-y-2 text-sm opacity-90">
                    <li><i class="fas fa-lightbulb mr-2"></i>Continue monitoring paper metrics to identify trending research areas</li>
                    <li><i class="fas fa-chart-line mr-2"></i>Focus on improving engagement for papers with low view counts</li>
                    <li><i class="fas fa-users mr-2"></i>Consider reaching out to underrepresented institutions</li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

// ============================================
// CHART GENERATION FUNCTIONS
// ============================================

function generateSubmissionChart($submissions) {
    // Map status to correct colors
    $colorMap = [
        'approved' => ['bg' => 'rgba(34, 197, 94, 0.8)', 'border' => 'rgba(34, 197, 94, 1)'],
        'pending' => ['bg' => 'rgba(249, 115, 22, 0.8)', 'border' => 'rgba(249, 115, 22, 1)'],
        'under_review' => ['bg' => 'rgba(59, 130, 246, 0.8)', 'border' => 'rgba(59, 130, 246, 1)'],
        'rejected' => ['bg' => 'rgba(239, 68, 68, 0.8)', 'border' => 'rgba(239, 68, 68, 1)']
    ];
    
    $labels = [];
    $data = [];
    $bgColors = [];
    $borderColors = [];
    
    foreach ($submissions as $s) {
        $labels[] = ucfirst(str_replace('_', ' ', $s['status']));
        $data[] = $s['count'];
        $color = $colorMap[$s['status']] ?? ['bg' => 'rgba(156, 163, 175, 0.8)', 'border' => 'rgba(156, 163, 175, 1)'];
        $bgColors[] = $color['bg'];
        $borderColors[] = $color['border'];
    }
    
    return "
    new Chart(document.getElementById('submissionStatusChart'), {
        type: 'bar',
        data: {
            labels: " . json_encode($labels) . ",
            datasets: [{
                label: 'Number of Papers',
                data: " . json_encode($data) . ",
                backgroundColor: " . json_encode($bgColors) . ",
                borderColor: " . json_encode($borderColors) . ",
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Paper Status Distribution (Click bars to view details)',
                    font: { size: 16, weight: 'bold' },
                    padding: { bottom: 20 }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 12 } },
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                y: {
                    ticks: { font: { size: 13, weight: '600' } }
                }
            }
        }
    });
    ";
}
function generateResearchTypeChart($by_type) {
    $labels = array_map(function($t) { 
        return ucfirst(str_replace('_', ' ', $t['research_type'])); 
    }, $by_type);
    $data = array_column($by_type, 'count');
    
    return "
    new Chart(document.getElementById('researchTypeChart'), {
        type: 'bar',
        data: {
            labels: " . json_encode($labels) . ",
            datasets: [{
                label: 'Number of Papers',
                data: " . json_encode($data) . ",
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
    ";
}

function generateMonthlyTrendsChart($monthly) {
    $monthlyJson = json_encode(array_reverse($monthly));
    
    return "
    (function() {
        const monthlyData = $monthlyJson;
        const labels = monthlyData.map(m => new Date(m.year, m.month - 1).toLocaleDateString('en-US', {month: 'short', year: 'numeric'}));
        const el = document.getElementById('monthlyTrendsChart');
        
        if (el) {
            new Chart(el, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'New Submissions',
                            data: monthlyData.map(m => parseInt(m.new_submissions) || 0),
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Approved Papers',
                            data: monthlyData.map(m => parseInt(m.approved_papers) || 0),
                            borderColor: 'rgba(34, 197, 94, 1)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Total Views',
                            data: monthlyData.map(m => parseInt(m.total_views) || 0),
                            borderColor: 'rgba(168, 85, 247, 1)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgba(168, 85, 247, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Total Downloads',
                            data: monthlyData.map(m => parseInt(m.total_downloads) || 0),
                            borderColor: 'rgba(249, 115, 22, 1)',
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgba(249, 115, 22, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {mode: 'index', intersect: false},
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 15,
                                font: {size: 13},
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: {size: 14, weight: 'bold'},
                            bodyFont: {size: 12}
                        }
                    },
                    scales: {
                        x: {
                            grid: {display: true, color: 'rgba(0, 0, 0, 0.05)'},
                            ticks: {font: {size: 11}}
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            title: {display: true, text: 'Papers Count', font: {size: 12, weight: 'bold'}},
                            ticks: {stepSize: 1},
                            grid: {color: 'rgba(0, 0, 0, 0.08)'}
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            title: {display: true, text: 'Views/Downloads', font: {size: 12, weight: 'bold'}},
                            grid: {drawOnChartArea: false}
                        }
                    }
                }
            });
        }
    })();
    ";
}

function generateEngagementChart($engagement) {
    $reversedEngagement = array_reverse($engagement);
    $labels = array_map(function($e) {
        return date('M d', strtotime($e['date']));
    }, $reversedEngagement);
    
    return "
    const engagementDates = " . json_encode($labels) . ";
    
    const engagementData = {
        labels: engagementDates,
        datasets: [
            {
                label: 'Views',
                data: " . json_encode(array_column($reversedEngagement, 'views')) . ",
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Downloads',
                data: " . json_encode(array_column($reversedEngagement, 'downloads')) . ",
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
    ";
}
?>