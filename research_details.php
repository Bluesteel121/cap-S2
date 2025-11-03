<?php

session_start();
// Get paper details with metrics
$sql = "SELECT ps.*, 
               COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
               COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
        FROM paper_submissions ps 
        LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
        WHERE ps.id = ? AND ps.status IN ('approved', 'published')
        GROUP BY ps.id";

if (!$stmt = $conn->prepare($sql)) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param('i', $paper_id);

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Paper not found or not available.";
    header("Location: elibrary_loggedin.php");
    exit();
}

$paper = $result->fetch_assoc();

// Record view metric - wrapped in try-catch
try {
    $view_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
                 VALUES (?, 'view', ?, ?, NOW())";
    $view_stmt = $conn->prepare($view_sql);
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $view_stmt->bind_param('iis', $paper_id, $user_id, $user_ip);
    $view_stmt->execute();
} catch (Exception $e) {
    // Silently fail - don't break the page if metrics recording fails
    error_log("Failed to record view metric: " . $e->getMessage());
}

// Log view activity - wrapped in try-catch
try {
    $activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, paper_id, ip_address, created_at)
                     VALUES (?, ?, 'view_paper', ?, ?, ?, NOW())";
    $activity_stmt = $conn->prepare($activity_sql);
    $activity_desc = "Viewed paper: " . $paper['paper_title'];
    $activity_stmt->bind_param('issss', $user_id, $username, $activity_desc, $paper_id, $user_ip);
    $activity_stmt->execute();
} catch (Exception $e) {
    // Silently fail
    error_log("Failed to log activity: " . $e->getMessage());
}
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
        .gradient-bg {
            background: linear-gradient(135deg, #115D5B 0%, #1A4D3A 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Navigation Bar -->
    <nav class="bg-[#115D5B] text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="Images/logo.png" alt="CNLRRS Logo" class="h-10 w-10 mr-2">
                <span class="text-xl font-bold">CNLRRS Research Repository</span>
            </div>
            <div class="space-x-4">
                <a href="loggedin_index.php" class="hover:underline">Home</a>
                <a href="elibrary_loggedin.php" class="hover:underline font-semibold">E-Library</a>
                <a href="#" class="hover:underline">About Us</a>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative group">
                    <button class="flex items-center space-x-2 bg-[#103635] px-4 py-2 rounded-xl">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 absolute right-0 mt-0 pt-2 w-48 z-50">
                        <div class="bg-white rounded-lg shadow-xl">
                            <a href="user_profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-t-lg">
                                <i class="fas fa-user-circle mr-2"></i>My Profile
                            </a>
                            <a href="my_submissions.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-file-alt mr-2"></i>My Submissions
                            </a>
                            <a href="submit_paper.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-upload mr-2"></i>Submit Paper
                            </a>
                            <hr class="my-1">
                            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100 rounded-b-lg">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="container mx-auto px-4 py-3">
            <nav class="flex text-sm text-gray-600">
                <a href="loggedin_index.php" class="hover:text-[#115D5B]">Home</a>
                <span class="mx-2">/</span>
                <a href="elibrary_loggedin.php" class="hover:text-[#115D5B]">E-Library</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Research Details</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Paper Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Paper Header -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <span class="inline-block px-3 py-1 text-xs rounded-full mb-3 <?php echo $paper['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <i class="fas fa-check-circle mr-1"></i><?php echo ucfirst($paper['status']); ?>
                            </span>
                            <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($paper['paper_title']); ?></h1>
                        </div>
                    </div>

                    <!-- Authors -->
                    <div class="mb-4 pb-4 border-b">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">
                            <i class="fas fa-users mr-2"></i>Authors
                        </h3>
                        <p class="text-lg text-gray-800">
                            <?php echo htmlspecialchars($paper['author_name']); ?>
                            <?php if (!empty($paper['co_authors'])): ?>
                                <span class="text-gray-600">, <?php echo htmlspecialchars($paper['co_authors']); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($paper['author_email'])): ?>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($paper['author_email']); ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Affiliation</h4>
                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($paper['affiliation']); ?></p>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Research Type</h4>
                            <p class="text-sm text-gray-800">
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                    <?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Submission Date</h4>
                            <p class="text-sm text-gray-800"><?php echo $submission_date; ?></p>
                        </div>
                        <?php if (!empty($paper['funding_source'])): ?>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Funding Source</h4>
                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($paper['funding_source']); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($research_period): ?>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Research Period</h4>
                            <p class="text-sm text-gray-800"><?php echo $research_period; ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Statistics</h4>
                            <p class="text-sm text-gray-800">
                                <i class="fas fa-eye text-blue-500"></i> <?php echo $paper['total_views']; ?> 
                                <i class="fas fa-download text-green-500 ml-2"></i> <?php echo $paper['total_downloads']; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Keywords -->
                    <?php if (!empty($paper['keywords'])): ?>
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2">
                            <i class="fas fa-tags mr-2"></i>Keywords
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $keywords = explode(',', $paper['keywords']);
                            foreach ($keywords as $keyword): 
                            ?>
                            <span class="bg-blue-50 text-blue-700 text-sm px-3 py-1 rounded-full border border-blue-200">
                                <?php echo htmlspecialchars(trim($keyword)); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 pt-4 border-t">
                        <?php if ($paper['file_path'] && file_exists($paper['file_path'])): ?>
                        <a href="paper_viewer.php?id=<?php echo $paper_id; ?>" 
                           target="_blank"
                           class="flex-1 md:flex-none bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-6 py-3 rounded-lg text-center transition-colors">
                            <i class="fas fa-eye mr-2"></i>View Paper
                        </a>
                        <a href="download_paper.php?id=<?php echo $paper_id; ?>" 
                           class="flex-1 md:flex-none bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-center transition-colors">
                            <i class="fas fa-download mr-2"></i>Download
                        </a>
                        <?php endif; ?>
                        <button onclick="showCitation()" 
                                class="flex-1 md:flex-none bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition-colors">
                            <i class="fas fa-quote-right mr-2"></i>Cite
                        </button>
                        <button onclick="sharePaper()" 
                                class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                            <i class="fas fa-share-alt mr-2"></i>Share
                        </button>
                    </div>
                </div>

                <!-- Abstract -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-align-left mr-2 text-[#115D5B]"></i>Abstract
                    </h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($paper['abstract']); ?></p>
                    </div>
                </div>

                <!-- Methodology -->
                <?php if (!empty($paper['methodology'])): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-microscope mr-2 text-[#115D5B]"></i>Methodology
                    </h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($paper['methodology']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ethics Approval -->
                <?php if (!empty($paper['ethics_approval'])): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-shield-alt mr-2 text-[#115D5B]"></i>Ethics Approval
                    </h2>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($paper['ethics_approval']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Comments -->
                <?php if (!empty($paper['additional_comments'])): ?>
                <div class="bg-blue-50 border-l-4 border-blue-400 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Additional Information
                    </h3>
                    <p class="text-blue-800"><?php echo htmlspecialchars($paper['additional_comments']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="elibrary_loggedin.php" 
                           class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-3 rounded-lg text-center transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Library
                        </a>
                        <button onclick="window.print()" 
                                class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-print mr-2"></i>Print Page
                        </button>
                        <button onclick="reportIssue()" 
                                class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-flag mr-2"></i>Report Issue
                        </button>
                    </div>
                </div>

                <!-- Paper Statistics -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Paper Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">
                                <i class="fas fa-eye text-blue-500 mr-2"></i>Total Views
                            </span>
                            <span class="text-2xl font-bold text-blue-600"><?php echo $paper['total_views']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">
                                <i class="fas fa-download text-green-500 mr-2"></i>Downloads
                            </span>
                            <span class="text-2xl font-bold text-green-600"><?php echo $paper['total_downloads']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">
                                <i class="fas fa-calendar text-purple-500 mr-2"></i>Submitted
                            </span>
                            <span class="text-sm font-medium text-gray-800"><?php echo date('M Y', strtotime($paper['submission_date'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Related Papers -->
                <?php if (!empty($related_papers)): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-link mr-2 text-[#115D5B]"></i>Related Papers
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($related_papers as $related): ?>
                        <a href="research_details.php?id=<?php echo $related['id']; ?>" 
                           class="block p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <h4 class="font-semibold text-sm text-gray-800 mb-1 line-clamp-2">
                                <?php echo htmlspecialchars($related['paper_title']); ?>
                            </h4>
                            <p class="text-xs text-gray-600">
                                <?php echo htmlspecialchars($related['author_name']); ?> • 
                                <?php echo date('Y', strtotime($related['submission_date'])); ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-eye text-blue-500"></i> <?php echo $related['total_views']; ?> views
                            </p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Research Category Info -->
                <div class="gradient-bg rounded-lg shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-3">
                        <i class="fas fa-info-circle mr-2"></i>Research Category
                    </h3>
                    <p class="text-sm mb-4"><?php echo ucfirst(str_replace('_', ' ', $paper['research_type'])); ?></p>
                    <a href="elibrary_loggedin.php?category=<?php echo urlencode($paper['research_type']); ?>" 
                       class="block w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg text-center transition-all">
                        View More in This Category
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-8 mt-12">
        <div class="container mx-auto text-center">
            <p class="text-gray-400">&copy; 2025 CNLRRS Queen Pineapple Research E-Library. All rights reserved.</p>
            <div class="mt-4 space-x-4">
                <a href="#" class="text-gray-400 hover:text-white">Terms</a>
                <a href="#" class="text-gray-400 hover:text-white">Privacy</a>
                <a href="#" class="text-gray-400 hover:text-white">Contact</a>
            </div>
        </div>
    </footer>

    <!-- Citation Modal -->
    <div id="citationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-quote-right mr-2"></i>Citation Formats
                    </h3>
                    <button onclick="closeCitation()" 
                            class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="citationContent" class="text-gray-700">
                    <p>Loading citation formats...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-share-alt mr-2"></i>Share This Paper
                    </h3>
                    <button onclick="closeShare()" 
                            class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Share Link</label>
                        <div class="flex">
                            <input type="text" id="shareLink" readonly 
                                   value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg text-sm">
                            <button onclick="copyShareLink()" 
                                    class="bg-[#115D5B] hover:bg-[#0e4e4c] text-white px-4 py-2 rounded-r-lg">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Share via Email</label>
                        <a href="mailto:?subject=<?php echo urlencode($paper['paper_title']); ?>&body=Check out this research paper: <?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center transition-colors">
                            <i class="fas fa-envelope mr-2"></i>Send Email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showCitation() {
            document.getElementById('citationModal').classList.remove('hidden');
            document.getElementById('citationContent').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p class="mt-2">Loading citation formats...</p></div>';
            
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
                                        <h4 class="font-semibold">Chicago Format</h4>
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
                                    <pre id="bibtex-citation" class="text-xs bg-gray-50 p-3 rounded overflow-x-auto font-mono">${data.bibtex}</pre>
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
        }

        function copyCitation(format, event) {
            const citation = document.getElementById(`${format}-citation`).innerText;
            navigator.clipboard.writeText(citation).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
                btn.classList.add('text-green-600');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('text-green-600');
                }, 2000);
            }).catch(err => {
                alert('Failed to copy citation.');
            });
        }

        function sharePaper() {
            document.getElementById('shareModal').classList.remove('hidden');
        }

        function closeShare() {
            document.getElementById('shareModal').classList.add('hidden');
        }

        function copyShareLink() {
            const shareLink = document.getElementById('shareLink');
            shareLink.select();
            navigator.clipboard.writeText(shareLink.value).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                alert('Failed to copy link.');
            });
        }

        function reportIssue() {
            if (confirm('Would you like to report an issue with this paper? This will send a notification to the administrators.')) {
                // You can implement this functionality
                alert('Thank you for reporting. An administrator will review your report.');
            }
        }

        // Close modals on outside click
        document.getElementById('citationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCitation();
            }
        });

        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeShare();
            }
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCitation();
                closeShare();
            }
        });

        // Smooth scroll for any anchor links
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
    </script>

</body>
</html>