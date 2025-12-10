<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'connect.php';
require_once 'admin_report_handler.php';

// Get date range from request or default to last 30 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Get report data based on type
$reportData = getReportData($conn, $report_type, $start_date, $end_date);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportToCSV($reportData, $start_date, $end_date);
    exit();
}

// Report type titles
$titles = [
    'overview' => 'Research Paper Overview',
    'submissions' => 'Paper Submissions Analysis',
    'performance' => 'Paper Performance Metrics',
    'reviews' => 'Review Process Analytics',
    'trends' => 'Paper Trends & Engagement',
    'keywords' => 'Keywords & Research Focus',
    'full' => 'Complete Research Paper Report'
];

$statusConfig = [
    'approved' => ['icon' => 'check-circle', 'color' => 'green', 'label' => 'Approved'],
    'pending' => ['icon' => 'clock', 'color' => 'orange', 'label' => 'Pending'],
    'under_review' => ['icon' => 'eye', 'color' => 'blue', 'label' => 'Under Review'],
    'rejected' => ['icon' => 'times-circle', 'color' => 'red', 'label' => 'Rejected']
];
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
        .paper-list-panel {
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                padding-top: 0;
                padding-bottom: 0;
            }
            to {
                opacity: 1;
                max-height: 2000px;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
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
                    <?php echo $titles[$report_type]; ?>
                </h2>
                <p class="text-gray-600 mt-1">
                    Period: <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to 
                    <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong>
                </p>
                <p class="text-sm text-gray-500">Generated on: <?php echo date('F d, Y - h:i A'); ?></p>
            </div>
        </div>

        <?php
        // Modals
        renderModals();
        
        // Render report sections
        if (isset($reportData['overall'])) {
            renderOverallStats($reportData['overall']);
        }
        
        if (isset($reportData['submissions']) && !empty($reportData['submissions'])) {
            renderSubmissionsSection($reportData, $statusConfig);
        }
        
        if (isset($reportData['by_type']) && !empty($reportData['by_type'])) {
            renderResearchTypeSection($reportData['by_type']);
        }
        
        if (isset($reportData['top_papers']) && !empty($reportData['top_papers'])) {
            renderTopPapersSection($reportData['top_papers']);
        }
        
        if (isset($reportData['by_affiliation']) && !empty($reportData['by_affiliation'])) {
            renderAffiliationSection($reportData['by_affiliation']);
        }
        
        if (isset($reportData['reviewer']) && !empty($reportData['reviewer'])) {
            renderReviewerSection($reportData['reviewer']);
        }
        
        if (isset($reportData['monthly']) && !empty($reportData['monthly'])) {
            renderMonthlyTrendsSection($reportData['monthly']);
        }
        
        if (isset($reportData['engagement']) && !empty($reportData['engagement'])) {
            renderEngagementSection($reportData['engagement']);
        }
        
        if (isset($reportData['keywords']) && !empty($reportData['keywords'])) {
            renderKeywordsSection($reportData['keywords']);
        }
        
        renderSummarySection($reportData);
        ?>

    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white text-center py-6 mt-12 no-print">
        <p>&copy; <?php echo date('Y'); ?> CNLRRS. All rights reserved.</p>
        <p class="text-sm opacity-75 mt-1">Research Paper Reporting System</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
    // Setup PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    
    // Toggle Paper List
    function togglePaperList(status) {
        const panel = document.getElementById('papers-' + status);
        if (panel) {
            document.querySelectorAll('.paper-list-panel').forEach(p => {
                if (p.id !== 'papers-' + status) p.classList.add('hidden');
            });
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
            }
        }
    }
    
    // View Paper Modal
    function viewPaperInModal(paperId) {
        const modal = document.getElementById('paperViewModal');
        const content = document.getElementById('paperViewContent');
        
        if (!modal || !content) return alert('Error: Modal not found');
        
        content.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i><p class="text-gray-600">Loading paper details...</p></div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        fetch('get_paper_details.php?id=' + paperId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    content.innerHTML = '<div class="text-center py-8"><i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i><p class="text-red-600">' + data.error + '</p></div>';
                    return;
                }
                
                const p = data.paper;
                const statusClass = p.status === 'approved' ? 'bg-green-100 text-green-800' : 
                                   p.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                   p.status === 'under_review' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800';
                
                content.innerHTML = `
                    <div class="space-y-6">
                        <div class="border-b pb-4">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">${escapeHtml(p.paper_title)}</h3>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">
                                    ${p.status.replace('_', ' ').toUpperCase()}
                                </span>
                                <span><i class="fas fa-user mr-1"></i>${escapeHtml(p.author_name)}</span>
                                <span><i class="fas fa-calendar mr-1"></i>${new Date(p.submission_date).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-2">Author Information</h4>
                                <div class="bg-gray-50 p-3 rounded-lg space-y-1 text-sm">
                                    <p><strong>Name:</strong> ${escapeHtml(p.author_name)}</p>
                                    ${p.co_authors ? '<p><strong>Co-authors:</strong> ' + escapeHtml(p.co_authors) + '</p>' : ''}
                                    ${p.author_email ? '<p><strong>Email:</strong> ' + escapeHtml(p.author_email) + '</p>' : ''}
                                    <p><strong>Affiliation:</strong> ${escapeHtml(p.affiliation)}</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-2">Research Information</h4>
                                <div class="bg-gray-50 p-3 rounded-lg space-y-1 text-sm">
                                    <p><strong>Type:</strong> ${p.research_type.replace('_', ' ').toUpperCase()}</p>
                                    <p><strong>Status:</strong> ${p.status.replace('_', ' ').toUpperCase()}</p>
                                    ${p.reviewed_by ? '<p><strong>Reviewed by:</strong> ' + escapeHtml(p.reviewed_by) + '</p>' : ''}
                                    ${p.review_date ? '<p><strong>Review Date:</strong> ' + new Date(p.review_date).toLocaleDateString() + '</p>' : ''}
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Abstract</h4>
                            <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-700 max-h-60 overflow-y-auto custom-scrollbar">
                                ${escapeHtml(p.abstract)}
                            </div>
                        </div>
                        ${p.keywords ? '<div><h4 class="font-semibold text-gray-700 mb-2">Keywords</h4><div class="flex flex-wrap gap-2">' + 
                            p.keywords.split(',').map(k => '<span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full">' + escapeHtml(k.trim()) + '</span>').join('') + 
                            '</div></div>' : ''}
                        <div class="grid grid-cols-2 gap-4 bg-gradient-to-r from-blue-50 to-purple-50 p-4 rounded-lg">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600">${p.total_views || 0}</div>
                                <div class="text-sm text-gray-600">Total Views</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600">${p.total_downloads || 0}</div>
                                <div class="text-sm text-gray-600">Downloads</div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 pt-4 border-t">
                            ${p.file_path ? '<button onclick="viewPaperPDF(' + p.id + ')" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors"><i class="fas fa-file-pdf mr-2"></i>View PDF</button>' : ''}
                            <button onclick="closePaperViewModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-colors"><i class="fas fa-times mr-2"></i>Close</button>
                        </div>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = '<div class="text-center py-8"><i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i><p class="text-red-600">Error loading paper details.</p></div>';
            });
    }
    
    function closePaperViewModal() {
        const modal = document.getElementById('paperViewModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    }
    
    function viewPaperPDF(paperId) {
        const modal = document.getElementById('pdfViewerModal');
        const content = document.getElementById('pdfViewerContent');
        
        content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i><p class="text-gray-500">Loading PDF...</p></div>';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        fetch('get_paper_file.php?id=' + paperId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.file_path) {
                    content.innerHTML = '<embed src="' + data.file_path + '" type="application/pdf" width="100%" height="100%" />';
                } else {
                    content.innerHTML = '<div class="text-center"><i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i><p class="text-red-600">' + (data.error || 'PDF not found') + '</p></div>';
                }
            });
    }
    
    function closePDFViewer() {
        document.getElementById('pdfViewerModal').classList.add('hidden');
        document.getElementById('pdfViewerModal').classList.remove('flex');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
    
    // Event Listeners
    document.addEventListener('click', e => {
        if (e.target.id === 'paperViewModal') closePaperViewModal();
        if (e.target.id === 'pdfViewerModal') closePDFViewer();
    });
    
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closePaperViewModal();
            closePDFViewer();
        }
    });
    
    // Initialize Charts
    <?php if (isset($reportData['submissions']) && !empty($reportData['submissions'])): ?>
    <?php echo generateSubmissionChart($reportData['submissions']); ?>
    <?php endif; ?>
    
    <?php if (isset($reportData['by_type']) && !empty($reportData['by_type'])): ?>
    <?php echo generateResearchTypeChart($reportData['by_type']); ?>
    <?php endif; ?>
    
    <?php if (isset($reportData['monthly']) && !empty($reportData['monthly'])): ?>
    <?php echo generateMonthlyTrendsChart($reportData['monthly']); ?>
    <?php endif; ?>
    
    <?php if (isset($reportData['engagement']) && !empty($reportData['engagement'])): ?>
    <?php echo generateEngagementChart($reportData['engagement']); ?>
    <?php endif; ?>
    </script>
</body>
</html>