<?php
require_once 'connect.php';

// Set content type to HTML
header('Content-Type: text/html; charset=UTF-8');

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paper_id) {
    header('Location: elibrary.php');
    exit();
}

// Get paper details with metrics and reviews
$sql = "SELECT ps.*, 
               COALESCE(AVG(pr.rating), 0) as avg_rating,
               COUNT(pr.id) as review_count,
               COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
               COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
        FROM paper_submissions ps 
        LEFT JOIN paper_reviews pr ON ps.id = pr.paper_id
        LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
        WHERE ps.id = ? AND ps.status IN ('approved', 'published')
        GROUP BY ps.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paper_id);
$stmt->execute();
$result = $stmt->get_result();
$paper = $result->fetch_assoc();

if (!$paper) {
    header('Location: elibrary.php');
    exit();
}

// Record view metric
$view_sql = "INSERT INTO paper_metrics (paper_id, metric_type, ip_address, created_at) VALUES (?, 'view', ?, NOW())";
$view_stmt = $conn->prepare($view_sql);
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$view_stmt->bind_param('is', $paper_id, $user_ip);
$view_stmt->execute();

// Get reviews for this paper
$review_sql = "SELECT * FROM paper_reviews WHERE paper_id = ? AND review_status = 'completed' ORDER BY review_date DESC";
$review_stmt = $conn->prepare($review_sql);
$review_stmt->bind_param('i', $paper_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get related papers (same research type, excluding current paper)
$related_sql = "SELECT ps.id, ps.paper_title, ps.author_name, ps.co_authors, ps.submission_date,
                       COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views
                FROM paper_submissions ps
                LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
                WHERE ps.research_type = ? AND ps.id != ? AND ps.status IN ('approved', 'published')
                GROUP BY ps.id
                ORDER BY total_views DESC
                LIMIT 3";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param('si', $paper['research_type'], $paper_id);
$related_stmt->execute();
$related_papers = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($paper['paper_title']); ?> - CNLRRS Research Repository</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .paper-content {
            line-height: 1.8;
        }
        .section-divider {
            border-left: 4px solid #115D5B;
            padding-left: 1rem;
        }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12pt; }
        }
        
        /* Custom scrollbar for better UX */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-[#115D5B] text-white p-4 shadow-lg no-print">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="Images/logo.png" alt="CNLRRS Logo" class="h-10 w-10 mr-2">
                <span class="text-xl font-bold">CNLRRS Research Repository</span>
            </div>
            <div class="space-x-4">
                <a href="index.php" class="hover:underline">Home</a>
                <a href="elibrary.php" class="hover:underline">Browse Research</a>
                <a href="#" class="hover:underline">About Us</a>
            </div>
            <a href="account.php" class="bg-[#103635] text-white px-6 py-2 rounded-xl font-semibold">Log In</a>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="container mx-auto p-4 no-print">
        <nav class="text-sm text-gray-600 mb-4">
            <a href="index.php" class="hover:text-[#115D5B]">Home</a> 
            <span class="mx-2">/</span>
            <a href="elibrary.php" class="hover:text-[#115D5B]">Research Library</a>
            <span class="mx-2">/</span>
            <span class="text-gray-800"><?php echo htmlspecialchars(substr($paper['paper_title'], 0, 50)); ?>...</span>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Paper Content -->
            <div class="lg:col-span-3">
                <!-- Paper Header -->
                <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                    <div class="mb-6">
                        <div class="flex flex-wrap justify-between items-start mb-4">
                            <div class="flex-1">
                                <h1 class="text-3xl font-bold text-gray-900 mb-4 leading-tight">
                                    <?php echo htmlspecialchars($paper['paper_title']); ?>
                                </h1>
                                
                                <!-- Authors and Metadata -->
                                <div class="space-y-3 mb-6">
                                    <div class="flex items-center text-lg">
                                        <i class="fas fa-user-circle text-[#115D5B] mr-3"></i>
                                        <span class="font-semibold">
                                            <?php echo htmlspecialchars($paper['author_name']); ?>
                                            <?php if ($paper['co_authors']): ?>
                                                <span class="font-normal text-gray-600">
                                                    , <?php echo htmlspecialchars($paper['co_authors']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-gray-600">
                                        <span class="flex items-center">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <?php echo date('F d, Y', strtotime($paper['submission_date'])); ?>
                                        </span>
                                        <span class="flex items-center">
                                            <i class="fas fa-tag mr-2"></i>
                                            <?php echo htmlspecialchars($paper['research_type']); ?>
                                        </span>
                                        <span class="flex items-center">
                                            <i class="fas fa-eye mr-2 text-blue-500"></i>
                                            <?php echo $paper['total_views'] + 1; ?> views
                                        </span>
                                        <span class="flex items-center">
                                            <i class="fas fa-download mr-2 text-green-500"></i>
                                            <?php echo $paper['total_downloads']; ?> downloads
                                        </span>
                                        <?php if ($paper['avg_rating'] > 0): ?>
                                        <span class="flex items-center">
                                            <i class="fas fa-star mr-2 text-yellow-500"></i>
                                            <?php echo number_format($paper['avg_rating'], 1); ?>/5 
                                            (<?php echo $paper['review_count']; ?> reviews)
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="mb-6">
                                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                                        <?php echo $paper['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <?php echo ucfirst($paper['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-3 mb-8 no-print">
                            <?php if ($paper['file_path'] && file_exists($paper['file_path'])): ?>
                            <a href="paper_viewer.php?id=<?php echo $paper['id']; ?>&action=download" 
                               class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg font-semibold flex items-center transition-colors">
                                <i class="fas fa-download mr-2"></i>Download PDF
                            </a>
                            <button onclick="viewFullResearch()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center transition-colors">
                                <i class="fas fa-external-link-alt mr-2"></i>View Full Research
                            </button>
                            <?php endif; ?>
                            <button onclick="generateCitation()" 
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center transition-colors">
                                <i class="fas fa-quote-right mr-2"></i>Cite Paper
                            </button>
                            <button onclick="window.print()" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center transition-colors">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                            <button onclick="sharePaper()" 
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center transition-colors">
                                <i class="fas fa-share mr-2"></i>Share
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Abstract Section -->
                <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                    <div class="section-divider">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-align-left mr-3 text-[#115D5B]"></i>Abstract
                        </h2>
                        <div class="paper-content text-gray-700 text-lg">
                            <?php echo nl2br(htmlspecialchars($paper['abstract'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Keywords Section -->
                <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                    <div class="section-divider">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-tags mr-3 text-[#115D5B]"></i>Keywords
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $keywords = explode(',', $paper['keywords']);
                            foreach ($keywords as $keyword): 
                                $keyword = trim($keyword);
                                if (!empty($keyword)):
                            ?>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo htmlspecialchars($keyword); ?>
                            </span>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Reviews Section -->
                <?php if (!empty($reviews)): ?>
                <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                    <div class="section-divider">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-star mr-3 text-[#115D5B]"></i>Peer Reviews
                        </h2>
                        <div class="space-y-6">
                            <?php foreach ($reviews as $review): ?>
                            <div class="border-l-4 border-blue-200 pl-6 py-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                        <p class="text-sm text-gray-600"><?php echo date('F d, Y', strtotime($review['review_date'])); ?></p>
                                    </div>
                                    <?php if ($review['rating']): ?>
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-500' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ml-2 text-sm text-gray-600"><?php echo $review['rating']; ?>/5</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($review['comments']): ?>
                                <p class="text-gray-700 mb-3"><?php echo nl2br(htmlspecialchars($review['comments'])); ?></p>
                                <?php endif; ?>
                                <?php if ($review['recommendations']): ?>
                                <span class="inline-block px-3 py-1 text-xs rounded-full font-medium
                                    <?php 
                                    switch($review['recommendations']) {
                                        case 'accept': echo 'bg-green-100 text-green-800'; break;
                                        case 'minor_revision': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'major_revision': echo 'bg-orange-100 text-orange-800'; break;
                                        case 'reject': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    Recommendation: <?php echo ucfirst(str_replace('_', ' ', $review['recommendations'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar - Fixed width and better layout -->
            <div class="lg:col-span-1">
                <!-- Paper Information Card - Made non-sticky to not block related papers -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Paper Information</h3>
                    
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="font-semibold text-gray-700 block mb-1">Research Type:</span>
                            <p class="text-gray-600"><?php echo htmlspecialchars($paper['research_type']); ?></p>
                        </div>
                        
                        <div>
                            <span class="font-semibold text-gray-700 block mb-1">Submission Date:</span>
                            <p class="text-gray-600"><?php echo date('F d, Y', strtotime($paper['submission_date'])); ?></p>
                        </div>
                        
                        <?php if ($paper['review_date']): ?>
                        <div>
                            <span class="font-semibold text-gray-700 block mb-1">Review Date:</span>
                            <p class="text-gray-600"><?php echo date('F d, Y', strtotime($paper['review_date'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <span class="font-semibold text-gray-700 block mb-1">Status:</span>
                            <p class="text-gray-600 capitalize"><?php echo htmlspecialchars($paper['status']); ?></p>
                        </div>

                        <div class="border-t pt-3">
                            <span class="font-semibold text-gray-700 block mb-2">Metrics:</span>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Views:</span>
                                    <span class="font-medium"><?php echo $paper['total_views'] + 1; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Downloads:</span>
                                    <span class="font-medium"><?php echo $paper['total_downloads']; ?></span>
                                </div>
                                <?php if ($paper['avg_rating'] > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Rating:</span>
                                    <span class="font-medium"><?php echo number_format($paper['avg_rating'], 1); ?>/5</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4 space-y-2">
                        <button onclick="reportPaper()" 
                                class="w-full bg-red-50 hover:bg-red-100 text-red-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-flag mr-2"></i>Report Issue
                        </button>
                        <button onclick="bookmarkPaper()" 
                                class="w-full bg-yellow-50 hover:bg-yellow-100 text-yellow-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-bookmark mr-2"></i>Bookmark
                        </button>
                    </div>
                </div>

                <!-- Related Papers - Now visible -->
                <?php if (!empty($related_papers)): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Related Research</h3>
                    <div class="space-y-4">
                        <?php foreach ($related_papers as $related): ?>
                        <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                            <h4 class="font-semibold text-sm text-[#115D5B] hover:text-[#0e4e4c] mb-1 leading-tight">
                                <a href="research_details.php?id=<?php echo $related['id']; ?>" class="hover:underline">
                                    <?php echo htmlspecialchars(substr($related['paper_title'], 0, 70)); ?><?php echo strlen($related['paper_title']) > 70 ? '...' : ''; ?>
                                </a>
                            </h4>
                            <p class="text-xs text-gray-600 mb-2">
                                <?php echo htmlspecialchars($related['author_name']); ?>
                                <?php if ($related['co_authors']): ?>, <?php echo htmlspecialchars(substr($related['co_authors'], 0, 25)); ?><?php echo strlen($related['co_authors']) > 25 ? '...' : ''; ?><?php endif; ?>
                            </p>
                            <div class="flex justify-between items-center text-xs text-gray-500">
                                <span><?php echo date('M Y', strtotime($related['submission_date'])); ?></span>
                                <span><?php echo $related['total_views']; ?> views</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="elibrary.php?category=<?php echo urlencode($paper['research_type']); ?>" 
                           class="text-[#115D5B] hover:text-[#0e4e4c] text-sm font-medium inline-flex items-center">
                            View more in <?php echo htmlspecialchars($paper['research_type']); ?> 
                            <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    
    <!-- Full Research Viewer Modal -->
    <div id="fullResearchModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-7xl w-full max-h-[95vh] flex flex-col shadow-2xl">
                <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                    <h3 class="text-xl font-semibold text-gray-800">Full Research Document</h3>
                    <div class="flex items-center gap-3">
                        <div id="loadingIndicator" class="hidden flex items-center text-gray-600">
                            <div class="loading mr-2"></div>
                            <span>Loading document...</span>
                        </div>
                        <button onclick="closeFullResearch()" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-200 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-hidden bg-gray-100">
                    <iframe id="researchViewer" src="" class="w-full h-full border-0" style="min-height: 70vh;"></iframe>
                </div>
                <div class="p-4 border-t bg-gray-50 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        If the document doesn't load, try downloading it directly.
                    </div>
                    <div class="flex gap-3">
                        <a href="paper_viewer.php?id=<?php echo $paper['id']; ?>&action=download" 
                           class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-4 py-2 rounded-lg font-medium flex items-center transition-colors">
                            <i class="fas fa-download mr-2"></i>Download PDF
                        </a>
                        <button onclick="closeFullResearch()" 
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Citation Modal -->
    <div id="citationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">Citation</h3>
                    <button onclick="closeCitation()" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2">APA Format</label>
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <p id="apaCitation" class="text-sm text-gray-800 select-all"></p>
                        </div>
                        <button onclick="copyCitation('apa')" class="mt-2 text-sm text-[#115D5B] hover:text-[#0e4e4c] font-medium">
                            <i class="fas fa-copy mr-1"></i>Copy APA Citation
                        </button>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2">MLA Format</label>
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <p id="mlaCitation" class="text-sm text-gray-800 select-all"></p>
                        </div>
                        <button onclick="copyCitation('mla')" class="mt-2 text-sm text-[#115D5B] hover:text-[#0e4e4c] font-medium">
                            <i class="fas fa-copy mr-1"></i>Copy MLA Citation
                        </button>
                    </div>
                    
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2">Chicago Format</label>
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <p id="chicagoCitation" class="text-sm text-gray-800 select-all"></p>
                        </div>
                        <button onclick="copyCitation('chicago')" class="mt-2 text-sm text-[#115D5B] hover:text-[#0e4e4c] font-medium">
                            <i class="fas fa-copy mr-1"></i>Copy Chicago Citation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // View full research with improved PDF handling
        function viewFullResearch() {
            const modal = document.getElementById('fullResearchModal');
            const viewer = document.getElementById('researchViewer');
            const loadingIndicator = document.getElementById('loadingIndicator');
            
            modal.classList.remove('hidden');
            loadingIndicator.classList.remove('hidden');
            
            // Try multiple PDF viewing methods for better compatibility
            const paperId = <?php echo $paper['id']; ?>;
            const pdfUrl = `paper_viewer.php?id=${paperId}`;
            
            // First try: Direct PDF embed
            viewer.src = pdfUrl;
            
            // Fallback: Use PDF.js or Google Docs Viewer
            viewer.onload = function() {
                loadingIndicator.classList.add('hidden');
            };
            
            viewer.onerror = function() {
                // Fallback to Google Docs Viewer
                const googleViewerUrl = `https://docs.google.com/viewer?url=${encodeURIComponent(window.location.origin + '/' + pdfUrl)}&embedded=true`;
                viewer.src = googleViewerUrl;
                
                setTimeout(() => {
                    loadingIndicator.classList.add('hidden');
                }, 3000);
            };
        }

        function closeFullResearch() {
            document.getElementById('fullResearchModal').classList.add('hidden');
            document.getElementById('researchViewer').src = '';
            document.getElementById('loadingIndicator').classList.add('hidden');
        }

        // Generate citations
        function generateCitation() {
            const title = <?php echo json_encode($paper['paper_title']); ?>;
            const author = <?php echo json_encode($paper['author_name']); ?>;
            const coAuthors = <?php echo json_encode($paper['co_authors']); ?>;
            const year = <?php echo json_encode(date('Y', strtotime($paper['submission_date']))); ?>;
            const url = window.location.href;
            
            const allAuthors = coAuthors ? `${author}, ${coAuthors}` : author;
            const currentDate = new Date().toLocaleDateString();
            
            // APA Format
            document.getElementById('apaCitation').textContent = 
                `${allAuthors} (${year}). ${title}. CNLRRS Research Repository. Retrieved ${currentDate}, from ${url}`;
            
            // MLA Format
            document.getElementById('mlaCitation').textContent = 
                `${allAuthors}. "${title}." CNLRRS Research Repository, ${year}, ${url}. Accessed ${currentDate}.`;
            
            // Chicago Format
            document.getElementById('chicagoCitation').textContent = 
                `${allAuthors}. "${title}." CNLRRS Research Repository. Accessed ${currentDate}. ${url}.`;
            
            document.getElementById('citationModal').classList.remove('hidden');
        }

        function closeCitation() {
            document.getElementById('citationModal').classList.add('hidden');
        }

        function copyCitation(format) {
            let text = '';
            switch(format) {
                case 'apa':
                    text = document.getElementById('apaCitation').textContent;
                    break;
                case 'mla':
                    text = document.getElementById('mlaCitation').textContent;
                    break;
                case 'chicago':
                    text = document.getElementById('chicagoCitation').textContent;
                    break;
            }
            
            navigator.clipboard.writeText(text).then(() => {
                // Show success message
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
                button.classList.add('text-green-600');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('text-green-600');
                }, 2000);
            }).catch(() => {
                alert('Failed to copy citation. Please select and copy manually.');
            });
        }

        // Share paper
        function sharePaper() {
            if (navigator.share) {
                navigator.share({
                    title: <?php echo json_encode($paper['paper_title']); ?>,
                    text: 'Check out this research paper from CNLRRS Repository',
                    url: window.location.href
                });
            } else {
                // Fallback - copy URL to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Paper URL copied to clipboard!');
                }).catch(() => {
                    // Manual fallback
                    prompt('Copy this URL:', window.location.href);
                });
            }
        }

        // Bookmark paper
        function bookmarkPaper() {
            alert('Bookmark feature will be implemented with user accounts.');
        }

        // Report paper
        function reportPaper() {
            if (confirm('Are you sure you want to report an issue with this paper?')) {
                alert('Report submitted. Our team will review it shortly.');
            }
        }

        // Close modals when clicking outside
        document.getElementById('fullResearchModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFullResearch();
            }
        });

        document.getElementById('citationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCitation();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('fullResearchModal').classList.contains('hidden')) {
                    closeFullResearch();
                }
                if (!document.getElementById('citationModal').classList.contains('hidden')) {
                    closeCitation();
                }
            }
        });

        // Track download clicks for analytics
        document.querySelectorAll('a[href*="paper_viewer.php"]').forEach(link => {
            link.addEventListener('click', function() {
                console.log('Paper download initiated');
            });
        });

        // Smooth scrolling for any anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Improved PDF loading with timeout
        function loadPdfWithFallback(url, iframe) {
            return new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject('Timeout');
                }, 10000); // 10 second timeout
                
                iframe.onload = () => {
                    clearTimeout(timeout);
                    resolve();
                };
                
                iframe.onerror = () => {
                    clearTimeout(timeout);
                    reject('Load error');
                };
                
                iframe.src = url;
            });
        }

        // Enhanced error handling for PDF viewer
        document.addEventListener('DOMContentLoaded', function() {
            // Add error handling for iframe loading issues
            const researchViewer = document.getElementById('researchViewer');
            if (researchViewer) {
                researchViewer.addEventListener('error', function() {
                    console.log('PDF viewer error - switching to fallback');
                });
            }
        });
    </script>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-8 mt-12 no-print">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">CNLRRS Research Repository</h3>
                    <p class="text-gray-400">Advancing agricultural research through knowledge sharing and collaboration in the Bicol region and beyond.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="elibrary.php" class="text-gray-400 hover:text-white">Browse Research</a></li>
                        <li><a href="userlogin.php" class="text-gray-400 hover:text-white">Submit Paper</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Information</h3>
                    <div class="text-gray-400 space-y-2">
                        <p><i class="fas fa-envelope mr-2"></i>research@cnlrrs.edu.ph</p>
                        <p><i class="fas fa-phone mr-2"></i>+63 (54) 123-4567</p>
                        <p><i class="fas fa-map-marker-alt mr-2"></i>Camarines Norte, Philippines</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; 2025 CNLRRS Research Repository. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>