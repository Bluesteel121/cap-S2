<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once 'connect.php';

try {
    // Get user's submissions using MySQLi
    $sql = "SELECT * FROM paper_submissions WHERE user_name = ? ORDER BY submission_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['name']);
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
            return 'bg-yellow-100 text-yellow-800';
        case 'under_review':
            return 'bg-blue-100 text-blue-800';
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'published':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
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
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-[#115D5B] text-white py-4 px-6">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                <h1 class="text-xl font-bold">My Paper Submissions</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="submit_paper.php" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md transition-colors">
                    <i class="fas fa-plus mr-2"></i>Submit New Paper
                </a>
                <a href="loggedin_index.php" class="flex items-center space-x-2 hover:text-green-200">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto py-8 px-6">
        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Message -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-[#115D5B] mb-2">
                Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
            </h2>
            <p class="text-gray-600">Here you can view and manage all your paper submissions.</p>
        </div>

        <!-- Submissions Summary -->
        <?php if (!empty($submissions)): ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <?php
                $statusCounts = array_count_values(array_column($submissions, 'status'));
                $totalSubmissions = count($submissions);
                ?>
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-[#115D5B]"><?php echo $totalSubmissions; ?></div>
                    <div class="text-gray-600">Total Submissions</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-yellow-600"><?php echo $statusCounts['pending'] ?? 0; ?></div>
                    <div class="text-gray-600">Pending</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600"><?php echo $statusCounts['under_review'] ?? 0; ?></div>
                    <div class="text-gray-600">Under Review</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-green-600"><?php echo ($statusCounts['approved'] ?? 0) + ($statusCounts['published'] ?? 0); ?></div>
                    <div class="text-gray-600">Approved/Published</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Submissions List -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="p-6 border-b">
                <h3 class="text-xl font-semibold text-gray-800">Your Submissions</h3>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-500 mb-2">No submissions yet</h3>
                    <p class="text-gray-400 mb-6">You haven't submitted any papers yet. Start by submitting your first research paper!</p>
                    <a href="submit_paper.php" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-3 rounded-md">
                        <i class="fas fa-plus mr-2"></i>Submit Your First Paper
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paper Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Research Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submission Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($submissions as $submission): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($submission['paper_title']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Keywords: <?php echo htmlspecialchars($submission['keywords']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($submission['author_name']); ?></div>
                                        <?php if ($submission['co_authors']): ?>
                                            <div class="text-sm text-gray-500">Co-authors: <?php echo htmlspecialchars($submission['co_authors']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($submission['research_type']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($submission['submission_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($submission['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewPaper(<?php echo $submission['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-900" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($submission['file_path']): ?>
                                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                                                   target="_blank" class="text-green-600 hover:text-green-900" title="Download File">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($submission['status'] === 'pending'): ?>
                                                <button onclick="editPaper(<?php echo $submission['id']; ?>)" 
                                                    class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paper Details Modal -->
    <div id="paperModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-800">Paper Details</h3>
                    <button onclick="closePaperModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="paperContent" class="p-6">
                <!-- Paper details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-[#115D5B] text-white text-center py-4 mt-12">
        <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
    </footer>

    <script>
        function viewPaper(paperId) {
            // Find paper data from PHP
            const submissions = <?php echo json_encode($submissions); ?>;
            const paper = submissions.find(p => p.id == paperId);
            
            if (paper) {
                document.getElementById('paperContent').innerHTML = `
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-800">Title:</h4>
                            <p class="text-gray-600">${paper.paper_title}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Author:</h4>
                            <p class="text-gray-600">${paper.author_name}</p>
                        </div>
                        ${paper.co_authors ? `
                        <div>
                            <h4 class="font-semibold text-gray-800">Co-Authors:</h4>
                            <p class="text-gray-600">${paper.co_authors}</p>
                        </div>
                        ` : ''}
                        <div>
                            <h4 class="font-semibold text-gray-800">Research Type:</h4>
                            <p class="text-gray-600">${paper.research_type}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Keywords:</h4>
                            <p class="text-gray-600">${paper.keywords}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Abstract:</h4>
                            <p class="text-gray-600">${paper.abstract}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Submission Date:</h4>
                            <p class="text-gray-600">${new Date(paper.submission_date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Status:</h4>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadgeClass(paper.status)}">
                                ${paper.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                        ${paper.reviewer_comments ? `
                        <div>
                            <h4 class="font-semibold text-gray-800">Reviewer Comments:</h4>
                            <p class="text-gray-600">${paper.reviewer_comments}</p>
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
                case 'pending': return 'bg-yellow-100 text-yellow-800';
                case 'under_review': return 'bg-blue-100 text-blue-800';
                case 'approved': return 'bg-green-100 text-green-800';
                case 'rejected': return 'bg-red-100 text-red-800';
                case 'published': return 'bg-purple-100 text-purple-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        // Close modal when clicking outside
        document.getElementById('paperModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaperModal();
            }
        });
    </script>
</body>
</html>