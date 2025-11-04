<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once 'connect.php';
// REMOVED DUPLICATE session_start()

error_log("paper_viewer.php - Session: " . print_r($_SESSION, true));
error_log("paper_viewer.php - GET: " . print_r($_GET, true));

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

error_log("paper_viewer.php - Paper ID: $paper_id");

if (!$paper_id) {
    error_log("paper_viewer.php - No paper ID provided");
    http_response_code(404);
    echo "Paper not found";
    exit();
}

// Get paper file information with enhanced details
$sql = "SELECT ps.*, 
               COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
               COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
        FROM paper_submissions ps
        LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
        WHERE ps.id = ? AND ps.status IN ('approved', 'published')
        GROUP BY ps.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paper_id);
$stmt->execute();
$result = $stmt->get_result();
$paper = $result->fetch_assoc();

if (!$paper || !$paper['file_path']) {
    http_response_code(404);
    echo "File not found";
    exit();
}

// Check file existence using multiple path strategies for Hostinger
$file_path = $paper['file_path'];
$file_exists = false;

$possible_paths = [
    $file_path,
    ltrim($file_path, './'),
    $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file_path, './'),
    __DIR__ . '/' . ltrim($file_path, './'),
    dirname(__DIR__) . '/' . ltrim($file_path, './')
];

foreach ($possible_paths as $path) {
    if (file_exists($path) && is_readable($path)) {
        $file_exists = true;
        break;
    }
}

$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Record view metric if logged in - with error handling
if (isset($_SESSION['id'])) {
    try {
        $view_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
                     VALUES (?, 'view', ?, ?, NOW())";
        $view_stmt = $conn->prepare($view_sql);
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $view_stmt->bind_param('iis', $paper_id, $_SESSION['id'], $user_ip);
        $view_stmt->execute();
        $view_stmt->close();
    } catch (mysqli_sql_exception $e) {
        // Log error but don't stop page load
        error_log("Error recording view metric: " . $e->getMessage());
    }
}

// Create a secure URL for the PDF viewer
$pdf_url = 'serve_pdf.php?id=' . $paper_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($paper['paper_title']); ?> - CNLRRS Repository</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .pdf-viewer {
            width: 100%;
            height: calc(100vh - 400px);
            min-height: 600px;
            border: 1px solid #e5e7eb;
        }
        
        @media (max-width: 768px) {
            .pdf-viewer {
                height: calc(100vh - 500px);
                min-height: 400px;
            }
        }

        .loading-spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #115D5B;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-[#115D5B] text-white p-4 shadow-lg">
        <div class="container mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center">
                <i class="fas fa-file-pdf text-2xl mr-3"></i>
                <h1 class="text-lg font-semibold">Paper Viewer</h1>
            </div>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['id'])): ?>
                <a href="elibrary_loggedin.php" class="bg-white text-[#115D5B] px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Library
                </a>
                <?php else: ?>
                <a href="elibrary.php" class="bg-white text-[#115D5B] px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Library
                </a>
                <?php endif; ?>
                <button onclick="window.close()" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors text-sm">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    </div>

    <!-- Paper Metadata -->
    <div class="container mx-auto p-4 sm:p-6">
        <div class="bg-white rounded-lg shadow-lg p-4 sm:p-6 mb-6">
            <!-- Title -->
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($paper['paper_title']); ?></h1>
            
            <!-- Metadata Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-user mr-1"></i>Authors
                    </h3>
                    <p class="text-gray-800 text-sm sm:text-base">
                        <?php echo htmlspecialchars($paper['author_name']); ?>
                        <?php if (!empty($paper['co_authors'])): ?>
                            , <?php echo htmlspecialchars($paper['co_authors']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (!empty($paper['author_email'])): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-envelope mr-1"></i>Contact
                    </h3>
                    <p class="text-gray-800 text-sm break-all"><?php echo htmlspecialchars($paper['author_email']); ?></p>
                </div>
                <?php endif; ?>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-university mr-1"></i>Affiliation
                    </h3>
                    <p class="text-gray-800 text-sm sm:text-base"><?php echo htmlspecialchars($paper['affiliation']); ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-flask mr-1"></i>Research Type
                    </h3>
                    <p class="text-gray-800 text-sm sm:text-base"><?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-calendar mr-1"></i>Submission Date
                    </h3>
                    <p class="text-gray-800 text-sm sm:text-base"><?php echo date('F d, Y', strtotime($paper['submission_date'])); ?></p>
                </div>
                
                <?php if (!empty($paper['funding_source'])): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">
                        <i class="fas fa-hand-holding-usd mr-1"></i>Funding Source
                    </h3>
                    <p class="text-gray-800 text-sm sm:text-base"><?php echo htmlspecialchars($paper['funding_source']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Keywords -->
            <?php if (!empty($paper['keywords'])): ?>
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">
                    <i class="fas fa-tags mr-1"></i>Keywords
                </h3>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $keywords = explode(',', $paper['keywords']);
                    foreach ($keywords as $keyword): 
                    ?>
                    <span class="bg-blue-100 text-blue-800 text-xs sm:text-sm px-3 py-1 rounded-full">
                        <?php echo htmlspecialchars(trim($keyword)); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Abstract -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">
                    <i class="fas fa-align-left mr-1"></i>Abstract
                </h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700 text-sm sm:text-base leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($paper['abstract']); ?></p>
                </div>
            </div>
            
            <!-- Stats and Actions -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-t pt-4 gap-4">
                <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-eye text-blue-500 mr-2"></i><?php echo $paper['total_views']; ?> views
                    </span>
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-download text-green-500 mr-2"></i><?php echo $paper['total_downloads']; ?> downloads
                    </span>
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-tag text-purple-500 mr-2"></i><?php echo ucfirst($paper['status']); ?>
                    </span>
                </div>
                
                <div class="flex flex-wrap gap-3 w-full sm:w-auto">
                    <button onclick="showCitation()" 
                            class="flex-1 sm:flex-none bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-quote-right mr-2"></i>Cite
                    </button>
                    <?php if (isset($_SESSION['id']) && $file_exists): ?>
                    <a href="download_paper.php?id=<?php echo $paper_id; ?>" 
                       class="flex-1 sm:flex-none bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors text-center">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                    <?php elseif (!isset($_SESSION['id'])): ?>
                    <button onclick="loginPrompt()" 
                            class="flex-1 sm:flex-none bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-download mr-2"></i>Download
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PDF Viewer -->
        <?php if ($file_extension === 'pdf'): ?>
        <div class="bg-white rounded-lg shadow-lg p-2">
            <?php if (!$file_exists): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-4 text-center">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-4xl mb-3"></i>
                <p class="text-yellow-700 font-semibold mb-2">PDF file is temporarily unavailable</p>
                <p class="text-sm text-yellow-600">The file may have been moved or is being processed. Please try again later or contact support.</p>
            </div>
            <?php endif; ?>
            
            <div id="pdf-loading" class="flex flex-col items-center justify-center p-12">
                <div class="loading-spinner mb-4"></div>
                <p class="text-gray-600">Loading PDF...</p>
            </div>
            
            <iframe id="pdf-frame"
                    src="<?php echo htmlspecialchars($pdf_url); ?>#view=FitH" 
                    class="pdf-viewer rounded-lg hidden" 
                    frameborder="0"
                    title="PDF Viewer"
                    allow="fullscreen">
            </iframe>
            
            <div id="pdf-error" class="hidden p-8 text-center">
                <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                <p class="text-gray-700 mb-4">Unable to load PDF viewer.</p>
                <p class="text-sm text-gray-600 mb-4">Your browser may not support inline PDF viewing.</p>
                <?php if (isset($_SESSION['id']) && $file_exists): ?>
                <a href="download_paper.php?id=<?php echo $paper_id; ?>" 
                   class="inline-block bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-download mr-2"></i>Download PDF Instead
                </a>
                <?php else: ?>
                <a href="userlogin.php" class="inline-block bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Download
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow-lg p-12 text-center">
            <i class="fas fa-file text-6xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Preview Not Available</h3>
            <p class="text-gray-600 mb-6">Preview is not available for this file type (<?php echo strtoupper($file_extension); ?>).</p>
            <?php if (isset($_SESSION['id']) && $file_exists): ?>
            <a href="download_paper.php?id=<?php echo $paper_id; ?>" 
               class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg inline-block transition-colors">
                <i class="fas fa-download mr-2"></i>Download File
            </a>
            <?php else: ?>
            <button onclick="loginPrompt()" 
                    class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg transition-colors">
                <i class="fas fa-sign-in-alt mr-2"></i>Login to Download
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Citation Modal -->
    <div id="citationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-4 sm:p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="flex justify-between items-center mb-4 sm:mb-6">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800">
                        <i class="fas fa-quote-right mr-2"></i>Citation Formats
                    </h3>
                    <button onclick="closeCitation()" 
                            class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="citationContent" class="text-gray-700 text-sm sm:text-base">
                    <p>Loading citation formats...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // PDF iframe load handling
        const pdfFrame = document.getElementById('pdf-frame');
        const pdfLoading = document.getElementById('pdf-loading');
        const pdfError = document.getElementById('pdf-error');

        if (pdfFrame) {
            pdfFrame.onload = function() {
                pdfLoading.classList.add('hidden');
                pdfFrame.classList.remove('hidden');
            };

            pdfFrame.onerror = function() {
                pdfLoading.classList.add('hidden');
                pdfError.classList.remove('hidden');
            };

            // Timeout for loading
            setTimeout(function() {
                if (!pdfFrame.classList.contains('hidden')) return;
                pdfLoading.classList.add('hidden');
                pdfError.classList.remove('hidden');
            }, 10000); // 10 second timeout
        }

        function showCitation() {
            document.getElementById('citationModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.getElementById('citationContent').innerHTML = '<div class="text-center py-4"><div class="loading-spinner mx-auto mb-2"></div><p class="mt-2">Loading citation formats...</p></div>';
            
            fetch(`get_citation.php?id=<?php echo $paper_id; ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('citationContent').innerHTML = 
                            `<p class="text-red-600">${data.error}</p>`;
                    } else {
                        document.getElementById('citationContent').innerHTML = `
                            <div class="space-y-4">
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold">APA Format (7th Edition)</h4>
                                        <button onclick="copyCitation('apa', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="apa-citation" class="text-sm bg-gray-50 p-3 rounded">${data.apa}</p>
                                </div>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold">MLA Format (9th Edition)</h4>
                                        <button onclick="copyCitation('mla', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="mla-citation" class="text-sm bg-gray-50 p-3 rounded">${data.mla}</p>
                                </div>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold">Chicago Format (17th Edition)</h4>
                                        <button onclick="copyCitation('chicago', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="chicago-citation" class="text-sm bg-gray-50 p-3 rounded">${data.chicago}</p>
                                </div>
                                <div class="border-b pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold">IEEE Format</h4>
                                        <button onclick="copyCitation('ieee', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <p id="ieee-citation" class="text-sm bg-gray-50 p-3 rounded">${data.ieee}</p>
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-semibold">BibTeX Format</h4>
                                        <button onclick="copyCitation('bibtex', event)" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-copy mr-1"></i>Copy
                                        </button>
                                    </div>
                                    <pre id="bibtex-citation" class="text-xs bg-gray-50 p-3 rounded overflow-x-auto">${data.bibtex}</pre>
                                </div>
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                    <p class="text-sm text-blue-800">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>Note:</strong> Always verify citation format requirements with your institution or publisher.
                                    </p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('citationContent').innerHTML = 
                        '<p class="text-red-600">Error loading citation formats.</p>';
                });
        }

        function closeCitation() {
            document.getElementById('citationModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function copyCitation(format, event) {
            const citation = document.getElementById(`${format}-citation`).innerText;
            navigator.clipboard.writeText(citation).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
                btn.classList.remove('text-blue-600', 'hover:text-blue-800');
                btn.classList.add('text-green-600');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('text-green-600');
                    btn.classList.add('text-blue-600', 'hover:text-blue-800');
                }, 2000);
            }).catch(err => {
                alert('Failed to copy citation.');
            });
        }

        function loginPrompt() {
            if (confirm('You need to login to download papers. Would you like to login now?')) {
                window.location.href = 'userlogin.php';
            }
        }

        // Close modal on outside click
        document.getElementById('citationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCitation();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCitation();
            }
        });
    </script>
</body>
</html>