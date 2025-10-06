<?php
// Start session first
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    // If this is an AJAX request, return JSON error
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Session expired. Please log in again.',
            'redirect' => 'account.php'
        ]);
        exit();
    }
    // Otherwise redirect to login
    header('Location: account.php');
    exit();
}

// Store username in variable for easier access
$current_username = $_SESSION['username'];

include 'connect.php';

// Handle AJAX form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    
    try {
        $username = $current_username; // Use the variable we set earlier

        // Validate required fields
        $required_fields = ['author_name', 'author_email', 'affiliation', 'paper_title', 'abstract', 'keywords', 'research_type'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required.");
            }
        }

        // Validate file upload
        if (!isset($_FILES['paper_file']) || $_FILES['paper_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload a valid PDF file.");
        }

        // Validate file type and size
        $file = $_FILES['paper_file'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);

        if ($mime_type !== 'application/pdf') {
            throw new Exception("Only PDF files are allowed.");
        }

        if ($file['size'] > 25 * 1024 * 1024) { // 25MB
            throw new Exception("File size must be less than 25MB.");
        }

        // Validate terms agreement
        if (!isset($_POST['terms_agreement'])) {
            throw new Exception("You must agree to the terms and conditions.");
        }

        // Form inputs with proper sanitization
        $author_name = trim($_POST['author_name']);
        $author_email = filter_var(trim($_POST['author_email']), FILTER_VALIDATE_EMAIL);
        if (!$author_email) {
            throw new Exception("Invalid email address format.");
        }
        
        $affiliation = trim($_POST['affiliation']);
        $co_authors = isset($_POST['co_authors']) ? trim($_POST['co_authors']) : null;
        $paper_title = trim($_POST['paper_title']);
        $abstract = trim($_POST['abstract']);
        $keywords = trim($_POST['keywords']);
        $methodology = isset($_POST['methodology']) ? trim($_POST['methodology']) : null;
        $funding_source = isset($_POST['funding_source']) ? trim($_POST['funding_source']) : null;
        $research_start_date = isset($_POST['research_start_date']) && !empty($_POST['research_start_date']) ? $_POST['research_start_date'] : null;
        $research_end_date = isset($_POST['research_end_date']) && !empty($_POST['research_end_date']) ? $_POST['research_end_date'] : null;
        $ethics_approval = isset($_POST['ethics_approval']) ? trim($_POST['ethics_approval']) : null;
        $additional_comments = isset($_POST['additional_comments']) ? trim($_POST['additional_comments']) : null;
        $research_type = $_POST['research_type'];

        // Checkboxes - ensure they're properly set
        $terms_agreement = isset($_POST['terms_agreement']) ? 1 : 0;
        $email_consent = isset($_POST['email_consent']) ? 1 : 0;
        $data_consent = isset($_POST['data_consent']) ? 1 : 0;

        // Additional validation
        if (strlen($abstract) < 100) {
            throw new Exception("Abstract must be at least 100 characters long.");
        }
        
        if (strlen($abstract) > 2000) {
            throw new Exception("Abstract cannot exceed 2000 characters.");
        }

        // Validate research dates
        if ($research_start_date && $research_end_date) {
            $start = new DateTime($research_start_date);
            $end = new DateTime($research_end_date);
            if ($start > $end) {
                throw new Exception("Research start date cannot be after end date.");
            }
        }

        // Handle file upload
        $upload_dir = "uploads/papers/";
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_name = uniqid() . "_" . time() . "." . $file_extension;
        $target_file = $upload_dir . $unique_name;

        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            throw new Exception("Failed to upload file.");
        }

        // Start transaction
        $conn->begin_transaction();

        try {
       // Replace the database insert section in submit_paper.php (around line 113-135)

// Prepare SQL statement - Fixed to properly include research_type
$sql = "INSERT INTO paper_submissions 
    (user_name, author_name, author_email, affiliation, co_authors, 
    paper_title, abstract, keywords, methodology, funding_source, 
    research_start_date, research_end_date, ethics_approval, additional_comments, 
    terms_agreement, email_consent, data_consent, research_type, file_path, 
    submission_date, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    throw new Exception("Database prepare error: " . $conn->error);
}

// Fixed bind_param - research_type is now properly positioned as 's' (string)
// Order: username, author_name, author_email, affiliation, co_authors,
//        paper_title, abstract, keywords, methodology, funding_source,
//        research_start_date, research_end_date, ethics_approval, additional_comments,
//        terms_agreement, email_consent, data_consent, research_type, target_file
$stmt->bind_param("ssssssssssssssiisss",
    $username, $author_name, $author_email, $affiliation, $co_authors,
    $paper_title, $abstract, $keywords, $methodology, $funding_source,
    $research_start_date, $research_end_date, $ethics_approval, $additional_comments,
    $terms_agreement, $email_consent, $data_consent, $research_type, $target_file
);

if (!$stmt->execute()) {
    throw new Exception("Database error: " . $stmt->error);
}

$paper_id = $conn->insert_id;
$stmt->close();
            // Create notification
            $notification_sql = "INSERT INTO submission_notifications (paper_id, user_name, notification_type, message) 
                                VALUES (?, ?, 'submitted', 'Your paper has been successfully submitted and is pending review.')";
            $notification_stmt = $conn->prepare($notification_sql);
            if ($notification_stmt) {
                $notification_stmt->bind_param("is", $paper_id, $username);
                $notification_stmt->execute();
                $notification_stmt->close();
            }

            // Log user activity
            $activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, paper_id, ip_address) 
                            SELECT id, username, 'submit_paper', 'Submitted research paper', ?, ?
                            FROM accounts WHERE username = ?";
            $activity_stmt = $conn->prepare($activity_sql);
            if ($activity_stmt) {
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $activity_stmt->bind_param("iss", $paper_id, $ip_address, $username);
                $activity_stmt->execute();
                $activity_stmt->close();
            }

        // Commit transaction
$conn->commit();

// Send confirmation email to the author
require_once 'email_config.php';

$paperData = [
    'author_name' => $author_name,
    'paper_title' => $paper_title,
    'research_type' => $research_type,
    'user_name' => $username
];

$emailSent = EmailService::sendPaperSubmissionNotification(
    $paperData, 
    $author_email, 
    $conn
);

// Log email attempt
if ($emailSent) {
    error_log("Submission confirmation email sent successfully to: $author_email for paper ID: $paper_id");
    
    // Log email activity in database if you have a logging function
    if (function_exists('logActivity')) {
        logActivity('EMAIL_SUBMISSION_SENT', "Email sent to: $author_email, Paper ID: $paper_id");
    }
} else {
    error_log("FAILED to send submission confirmation email to: $author_email for paper ID: $paper_id");
    
    if (function_exists('logError')) {
        logError("Email send failed for submission to: $author_email", 'EMAIL_SEND_FAILED');
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Paper submitted successfully! ' . ($emailSent ? 'Confirmation email has been sent to your email address.' : 'However, the confirmation email could not be sent. Please check your spam folder or contact the administrator.'),
    'paper_id' => $paper_id,
    'redirect' => 'my_submissions.php?success=1'
]);
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        // If we get here, remove uploaded file if it exists
        if (isset($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    $conn->close();
    exit();
}

// Get user information for form pre-filling
$user_info = null;
if (isset($_SESSION['username'])) {
    $user_sql = "SELECT name, email FROM accounts WHERE username = ?";
    $stmt = $conn->prepare($user_sql);
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_info = $result->fetch_assoc();
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Research Paper - CNLRRS</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .field-help {
            background: linear-gradient(135deg, #e0f2fe 0%, #f1f8ff 100%);
            border-left: 4px solid #2196F3;
            transition: all 0.3s ease;
        }
        .field-help:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
        }
        .required::after {
            content: " *";
            color: #ef4444;
            font-weight: bold;
        }
        .form-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .form-section:hover {
            box-shadow: 0 8px 25px -2px rgba(0, 0, 0, 0.1);
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
        .section-icon {
            background: linear-gradient(135deg, #115D5B 0%, #0d4a47 100%);
        }
        .help-toggle {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .help-toggle:hover {
            color: #2196F3;
        }
        .character-count {
            font-size: 0.75rem;
            transition: color 0.3s ease;
        }
        .character-warning {
            color: #f59e0b;
        }
        .character-error {
            color: #ef4444;
        }
        .file-drop-zone {
            border: 2px dashed #cbd5e1;
            transition: all 0.3s ease;
        }
        .file-drop-zone.dragover {
            border-color: #115D5B;
            background-color: #f0fdfa;
        }
        .research-type-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .research-type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .research-type-card.selected {
            border-color: #115D5B;
            background-color: #f0fdfa;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <header class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white py-6 px-6 shadow-lg">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="Images/CNLRRS_icon.png" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                    <div>
                        <h1 class="text-2xl font-bold">Research Paper Submission</h1>
                        <p class="text-sm opacity-90">Camarines Norte Lowland Rainfed Research Station</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="loggedin_index.php" class="flex items-center space-x-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Home</span>
                    </a>
                    <div class="text-right">
                        <p class="text-sm opacity-75">Welcome, <?php echo htmlspecialchars($current_username); ?></p>
                        <p class="text-xs opacity-60">Follow instructions for best results</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-6 py-4">
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span>Submission Progress</span>
                <span id="progressText">0% Complete</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progressBar" class="progress-bar bg-gradient-to-r from-[#115D5B] to-green-500 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 py-8">
        <div class="form-section p-6 mb-8 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-start space-x-4">
                <div class="section-icon text-white p-3 rounded-full">
                    <i class="fas fa-info-circle text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-3">Submission Requirements</h2>
                    <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-700">
                        <div>
                            <h3 class="font-semibold mb-2">Required Information:</h3>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Complete research paper in PDF format (max 25MB)</li>
                                <li>Detailed abstract (100-2000 characters)</li>
                                <li>Author information and affiliation</li>
                                <li>Research keywords and methodology</li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">Optional Documents:</h3>
                            <ul class="list-disc list-inside space-y-1">
                                <li>DOST Research Proposal Template
                                    <a href="Images/worksheet.xlsx" download class="text-blue-600 underline ml-2">
                                         Download Here</a> 
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="paperSubmissionForm" method="POST" enctype="multipart/form-data">
            <!-- Basic Information Section -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-clipboard text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">1. Basic Information</h2>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700 required">Research Paper Title</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('titleHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <input type="text" id="paperTitle" name="paper_title" required maxlength="500"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="Enter the complete title of your research paper">
                    <div class="flex justify-between items-center mt-1">
                        <span id="titleCount" class="character-count text-gray-500">0/500 characters</span>
                    </div>
                    <div id="titleHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Paper Title Guidelines:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>Be Specific:</strong> Clearly state what your research is about</li>
                            <li><strong>Include Keywords:</strong> Use terms that researchers would search for</li>
                            <li><strong>Keep Concise:</strong> Aim for 10-15 words when possible</li>
                            <li><strong>Avoid Jargon:</strong> Use terminology that broader audience can understand</li>
                        </ul>
                    </div>
                </div>
<div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-gray-700 required">Research Type</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('typeHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('experimental')">
                            <input type="radio" name="research_type" value="experimental" id="experimental" class="hidden" required>
                            <div class="text-center">
                                <i class="fas fa-flask text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800 text-sm">Experimental</h3>
                                <p class="text-xs text-gray-600 mt-1">Controlled studies</p>
                            </div>
                        </div>
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('observational')">
                            <input type="radio" name="research_type" value="observational" id="observational" class="hidden" required>
                            <div class="text-center">
                                <i class="fas fa-eye text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800 text-sm">Observational</h3>
                                <p class="text-xs text-gray-600 mt-1">Field observations</p>
                            </div>
                        </div>
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('review')">
                            <input type="radio" name="research_type" value="review" id="review" class="hidden" required>
                            <div class="text-center">
                                <i class="fas fa-book text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800 text-sm">Literature Review</h3>
                                <p class="text-xs text-gray-600 mt-1">Analysis of research</p>
                            </div>
                        </div>
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('case_study')">
                            <input type="radio" name="research_type" value="case_study" id="case_study" class="hidden" required>
                            <div class="text-center">
                                <i class="fas fa-search text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800 text-sm">Case Study</h3>
                                <p class="text-xs text-gray-600 mt-1">Specific analysis</p>
                            </div>
                        </div>
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('other')">
                            <input type="radio" name="research_type" value="other" id="other" class="hidden" required>
                            <div class="text-center">
                                <i class="fas fa-plus-circle text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800 text-sm">Other</h3>
                                <p class="text-xs text-gray-600 mt-1">Mixed methods</p>
                            </div>
                        </div>
                    </div>
                    <div id="typeHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Research Type Selection:</h4>
                        <div class="grid md:grid-cols-2 gap-4 text-sm text-blue-700">
                            <div>
                                <p><strong>Experimental:</strong> You manipulated variables and measured outcomes</p>
                                <p><strong>Observational:</strong> You collected data without manipulating conditions</p>
                                <p><strong>Literature Review:</strong> You analyzed existing research papers</p>
                            </div>
                            <div>
                                <p><strong>Case Study:</strong> You conducted detailed analysis of specific instances</p>
                                <p><strong>Other:</strong> Mixed methods or hybrid approaches</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700 required">Keywords</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('keywordHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <input type="text" id="keywords" name="keywords" required maxlength="500"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="Enter 5-8 keywords separated by commas">
                    <div class="flex justify-between items-center mt-1">
                        <span id="keywordCount" class="character-count text-gray-500">0/500 characters</span>
                        <span id="keywordWordCount" class="character-count text-gray-500">0 keywords</span>
                    </div>
                    <div id="keywordHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Keyword Guidelines:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>5-8 Keywords:</strong> Provide between 5-8 relevant terms</li>
                            <li><strong>Specific Terms:</strong> Use precise scientific terms</li>
                            <li><strong>Separate by Commas:</strong> Use commas to separate each keyword</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Author Information Section -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-users text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">2. Author Information</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 required mb-2">Primary Author Full Name</label>
                        <input type="text" id="authorName" name="author_name" required maxlength="255"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                               placeholder="Dr. Juan A. Dela Cruz"
                               value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 required mb-2">Primary Author Email</label>
                        <input type="email" id="authorEmail" name="author_email" required maxlength="100"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                               placeholder="juan.delacruz@institution.edu.ph"
                               value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 required mb-2">Primary Author Affiliation</label>
                    <input type="text" id="affiliation" name="affiliation" required maxlength="200"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="Department of Agriculture, University of the Philippines Los Baños">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Co-Authors (Optional)</label>
                    <textarea id="coAuthors" name="co_authors" rows="3" maxlength="500"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="List co-authors with their affiliations"></textarea>
                    <span id="coAuthorCount" class="character-count text-gray-500 block mt-1">0/500 characters</span>
                </div>
            </div>

            <!-- Abstract Section -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-file-alt text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">3. Abstract & Research Details</h2>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700 required">Abstract</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('abstractHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <textarea id="abstract" name="abstract" rows="8" required maxlength="2000" minlength="100"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="Provide a comprehensive abstract of your research (minimum 100 characters)..."></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <span id="abstractCount" class="character-count text-gray-500">0/2000 characters (min 100)</span>
                        <span id="abstractWordCount" class="character-count text-gray-500">0 words</span>
                    </div>
                    <div id="abstractHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Abstract Guidelines:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>Structure:</strong> Background, objectives, methods, results, conclusion</li>
                            <li><strong>Length:</strong> Minimum 100 characters, maximum 2000 characters</li>
                            <li><strong>Clarity:</strong> Write for both experts and general scientific audience</li>
                            <li><strong>Keywords:</strong> Include main keywords naturally in the text</li>
                        </ul>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Research Methodology (Optional)</label>
                    <textarea id="methodology" name="methodology" rows="4" maxlength="1000"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="Brief description of your research methods and approach..."></textarea>
                    <span id="methodCount" class="character-count text-gray-500 block mt-1">0/1000 characters</span>
                </div>
            </div>

            <!-- File Upload Section -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-cloud-upload-alt text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">4. Upload Research Paper</h2>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3 required">Research Paper File (PDF only, max 25MB)</label>
                    
                    <div class="file-drop-zone p-8 border-2 border-dashed rounded-lg text-center transition" 
                         ondrop="dropHandler(event)" ondragover="dragOverHandler(event)" ondragleave="dragLeaveHandler(event)">
                        <i class="fas fa-file-pdf text-4xl text-gray-400 mb-4"></i>
                        <p class="text-lg font-semibold text-gray-700 mb-2">Drop your PDF file here</p>
                        <p class="text-sm text-gray-500 mb-4">or click to browse</p>
                        <input type="file" id="paperFile" name="paper_file" accept=".pdf" required
                               class="hidden" onchange="fileSelected(event)">
                        <button type="button" onclick="document.getElementById('paperFile').click()"
                                class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-lg transition">
                            Choose File
                        </button>
                    </div>
                    
                    <div id="fileInfo" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-pdf text-red-500"></i>
                                <span id="fileName" class="font-semibold text-gray-700"></span>
                                <span id="fileSize" class="text-sm text-gray-500"></span>
                            </div>
                            <button type="button" onclick="removeFile()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-plus-circle text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">5. Additional Information</h2>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Funding Source (Optional)</label>
                    <input type="text" id="fundingSource" name="funding_source" maxlength="200"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="e.g., DOST-PCAARRD, University Research Grant">
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Research Start Date</label>
                        <input type="date" id="startDate" name="research_start_date"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Research End Date</label>
                        <input type="date" id="endDate" name="research_end_date"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ethics Approval/Permits (Optional)</label>
                    <textarea id="ethicsApproval" name="ethics_approval" rows="3" maxlength="500"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="List any ethics approvals, permits, or clearances obtained..."></textarea>
                    <span id="ethicsCount" class="character-count text-gray-500 block mt-1">0/500 characters</span>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Additional Comments (Optional)</label>
                    <textarea id="additionalComments" name="additional_comments" rows="4" maxlength="1000"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="Any additional information for reviewers..."></textarea>
                    <span id="commentsCount" class="character-count text-gray-500 block mt-1">0/1000 characters</span>
                </div>
            </div>

            <!-- Terms and Agreement Section -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-shield-alt text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">6. Terms and Submission Agreement</h2>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Submission Guidelines and Agreement</h3>
                    <div class="text-sm text-gray-700 space-y-3">
                        <p><strong>By submitting this research paper, you confirm that:</strong></p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>This work is original and has not been published elsewhere</li>
                            <li>All co-authors have agreed to this submission</li>
                            <li>The research was conducted ethically and with proper approvals</li>
                            <li>You have the right to submit this work for review</li>
                            <li>All sources and references are properly cited</li>
                            <li>The data and findings are accurate to the best of your knowledge</li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" id="termsAgree" name="terms_agreement" required onchange="updateProgress()"
                               class="mt-1 h-5 w-5 text-[#115D5B] border-2 border-gray-300 rounded focus:ring-2 focus:ring-[#115D5B]">
                        <span class="text-sm text-gray-700">
                            <span class="font-semibold required">I agree to the submission terms and conditions</span> listed above and confirm that all information provided is accurate and complete.
                        </span>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" id="emailConsent" name="email_consent"
                               class="mt-1 h-5 w-5 text-[#115D5B] border-2 border-gray-300 rounded focus:ring-2 focus:ring-[#115D5B]">
                        <span class="text-sm text-gray-700">
                            I consent to receive email notifications about my submission status and related communications from CNLRRS.
                        </span>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" id="dataConsent" name="data_consent"
                               class="mt-1 h-5 w-5 text-[#115D5B] border-2 border-gray-300 rounded focus:ring-2 focus:ring-[#115D5B]">
                        <span class="text-sm text-gray-700">
                            I understand that my submission data will be stored securely and used only for review purposes and research database management.
                        </span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" id="submitBtn" disabled
                        class="bg-gradient-to-r from-[#115D5B] to-green-600 hover:from-[#0d4a47] hover:to-green-700 disabled:from-gray-400 disabled:to-gray-500 text-white px-12 py-4 rounded-lg font-bold text-lg shadow-lg transition-all duration-300 transform hover:scale-105 disabled:transform-none disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane mr-3"></i>
                    Submit Research Paper
                </button>
                <p class="text-sm text-gray-500 mt-4">
                    Please review all information carefully before submitting. You will receive a confirmation email once your submission is received.
                </p>
            </div>
        </form>
    </div>

    <footer class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white text-center py-8 mt-12">
        <div class="max-w-6xl mx-auto px-6">
            <p class="text-lg font-semibold mb-2">Camarines Norte Lowland Rainfed Research Station</p>
            <p class="text-sm opacity-75">Supporting agricultural research and development in the Philippines</p>
            <p class="text-xs opacity-60 mt-2">&copy; 2025 CNLRRS. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Character counting functions
        function setupCharacterCounters() {
            const counters = [
                { input: 'paperTitle', counter: 'titleCount', max: 500 },
                { input: 'keywords', counter: 'keywordCount', max: 500, wordCounter: 'keywordWordCount' },
                { input: 'abstract', counter: 'abstractCount', max: 2000, min: 100, wordCounter: 'abstractWordCount' },
                { input: 'coAuthors', counter: 'coAuthorCount', max: 500 },
                { input: 'methodology', counter: 'methodCount', max: 1000 },
                { input: 'ethicsApproval', counter: 'ethicsCount', max: 500 },
                { input: 'additionalComments', counter: 'commentsCount', max: 1000 }
            ];

            counters.forEach(({ input, counter, max, min, wordCounter }) => {
                const inputEl = document.getElementById(input);
                const counterEl = document.getElementById(counter);
                
                if (inputEl && counterEl) {
                    inputEl.addEventListener('input', function() {
                        const count = this.value.length;
                        
                        if (min) {
                            counterEl.textContent = `${count}/${max} characters (min ${min})`;
                            
                            if (count < min) {
                                counterEl.className = 'character-count character-warning';
                            } else if (count > max * 0.9) {
                                counterEl.className = 'character-count character-error';
                            } else {
                                counterEl.className = 'character-count text-gray-500';
                            }
                        } else {
                            counterEl.textContent = `${count}/${max} characters`;
                            
                            if (count > max * 0.9) {
                                counterEl.className = 'character-count character-error';
                            } else if (count > max * 0.7) {
                                counterEl.className = 'character-count character-warning';
                            } else {
                                counterEl.className = 'character-count text-gray-500';
                            }
                        }
                        
                        if (wordCounter) {
                            const wordCountEl = document.getElementById(wordCounter);
                            if (wordCountEl) {
                                const isKeywords = wordCounter.includes('keyword');
                                if (isKeywords) {
                                    const keywords = this.value.split(',').map(k => k.trim()).filter(k => k.length > 0);
                                    wordCountEl.textContent = `${keywords.length} keywords`;
                                } else {
                                    const words = this.value.trim().split(/\s+/).filter(word => word.length > 0);
                                    wordCountEl.textContent = `${words.length} words`;
                                }
                            }
                        }
                        
                        updateProgress();
                    });
                }
            });
        }

        // Progress tracking
        function updateProgress() {
            const requiredFields = [
                'paperTitle', 'keywords', 'authorName', 'authorEmail', 
                'affiliation', 'abstract', 'paperFile', 'termsAgree'
            ];
            
            let completed = 0;
            let totalRequired = requiredFields.length + 1; // +1 for research type
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    if (field.type === 'checkbox') {
                        if (field.checked) completed++;
                    } else if (field.type === 'file') {
                        if (field.files && field.files.length > 0) completed++;
                    } else {
                        const value = field.value.trim();
                        if (value) {
                            // Special validation for abstract minimum length
                            if (fieldId === 'abstract' && value.length < 100) {
                                // Don't count as completed if below minimum
                            } else {
                                completed++;
                            }
                        }
                    }
                }
            });
            
            // Check research type selection
            const researchTypeSelected = document.querySelector('input[name="research_type"]:checked');
            if (researchTypeSelected) completed++;
            
            const progress = Math.round((completed / totalRequired) * 100);
            document.getElementById('progressBar').style.width = `${progress}%`;
            document.getElementById('progressText').textContent = `${progress}% Complete`;
            
            // Enable submit button when form is complete
            const submitBtn = document.getElementById('submitBtn');
            if (completed === totalRequired) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Research type selection
        function selectResearchType(type) {
            document.querySelectorAll('.research-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            document.getElementById(type).checked = true;
            updateProgress();
        }

        // Help toggle function
        function toggleHelp(helpId) {
            const helpDiv = document.getElementById(helpId);
            helpDiv.classList.toggle('hidden');
        }

        // File handling functions
        function dragOverHandler(event) {
            event.preventDefault();
            event.currentTarget.classList.add('dragover');
        }

        function dragLeaveHandler(event) {
            event.currentTarget.classList.remove('dragover');
        }

        function dropHandler(event) {
            event.preventDefault();
            event.currentTarget.classList.remove('dragover');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('paperFile');
                fileInput.files = files;
                fileSelected({ target: fileInput });
            }
        }

        function fileSelected(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.type !== 'application/pdf') {
                    showNotification('Please select a PDF file only.', 'error');
                    event.target.value = '';
                    return;
                }
                
                if (file.size > 25 * 1024 * 1024) { // 25MB
                    showNotification('File size must be less than 25MB.', 'error');
                    event.target.value = '';
                    return;
                }
                
                // Show file info
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                document.getElementById('fileInfo').classList.remove('hidden');
                
                updateProgress();
            }
        }

        function removeFile() {
            document.getElementById('paperFile').value = '';
            document.getElementById('fileInfo').classList.add('hidden');
            updateProgress();
        }

        // Form submission handler
        document.getElementById('paperSubmissionForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Validate required fields before submission
            const requiredFields = [
                { id: 'paperTitle', name: 'Paper Title' },
                { id: 'keywords', name: 'Keywords' },
                { id: 'authorName', name: 'Author Name' },
                { id: 'authorEmail', name: 'Author Email' },
                { id: 'affiliation', name: 'Affiliation' },
                { id: 'abstract', name: 'Abstract' },
                { id: 'paperFile', name: 'Paper File' },
                { id: 'termsAgree', name: 'Terms Agreement' }
            ];

            let isValid = true;
            let firstErrorField = null;

            for (let field of requiredFields) {
                const element = document.getElementById(field.id);
                if (!element) continue;

                let isEmpty = false;
                if (element.type === 'checkbox') {
                    isEmpty = !element.checked;
                } else if (element.type === 'file') {
                    isEmpty = !element.files || element.files.length === 0;
                } else {
                    const value = element.value.trim();
                    isEmpty = !value;
                    
                    // Special validation for abstract minimum length
                    if (field.id === 'abstract' && value && value.length < 100) {
                        showNotification('Abstract must be at least 100 characters long.', 'error');
                        isValid = false;
                        if (!firstErrorField) firstErrorField = element;
                        continue;
                    }
                }

                if (isEmpty) {
                    isValid = false;
                    if (!firstErrorField) {
                        firstErrorField = element;
                    }
                    element.classList.add('border-red-500');
                } else {
                    element.classList.remove('border-red-500');
                }
            }

            // Check research type
            const researchType = document.querySelector('input[name="research_type"]:checked');
            if (!researchType) {
                isValid = false;
                showNotification('Please select a research type.', 'error');
                return;
            }

            // Validate email format
            const emailField = document.getElementById('authorEmail');
            if (emailField.value && !isValidEmail(emailField.value)) {
                isValid = false;
                showNotification('Please enter a valid email address.', 'error');
                emailField.classList.add('border-red-500');
                if (!firstErrorField) firstErrorField = emailField;
            }

            // Validate research dates if both are provided
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                isValid = false;
                showNotification('Research start date cannot be after end date.', 'error');
                return;
            }

            if (!isValid) {
                showNotification('Please fix the highlighted errors before submitting.', 'error');
                if (firstErrorField) {
                    firstErrorField.focus();
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Create FormData object
            const formData = new FormData(this);
            
            // Submit form via fetch
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Paper submitted successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    throw new Error(data.error || 'Submission failed');
                }
            })
            .catch(error => {
                console.error('Submission error:', error);
                showNotification('Error: ' + error.message, 'error');
                
                // Reset button
                submitBtn.innerHTML = originalText;
                updateProgress(); // This will re-enable if form is complete
            });
        });

        // Helper functions
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 max-w-md p-4 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full`;
            
            const colors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                warning: 'bg-yellow-500 text-white',
                info: 'bg-blue-500 text-white'
            };
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-triangle',
                warning: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            
            notification.className += ` ${colors[type]}`;
            
            notification.innerHTML = `
                <div class="flex items-start space-x-3">
                    <i class="fas ${icons[type]} text-lg mt-1"></i>
                    <div class="flex-1">
                        <p class="font-semibold capitalize">${type}</p>
                        <p class="text-sm mt-1 whitespace-pre-line">${message}</p>
                    </div>
                    <button onclick="this.parentNode.parentNode.remove()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            const timeout = type === 'error' ? 8000 : 5000;
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, timeout);
        }

        // Date validation
        function validateResearchDates() {
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            
            startDateInput.addEventListener('change', function() {
                if (endDateInput.value && this.value > endDateInput.value) {
                    showNotification('Start date cannot be after end date.', 'warning');
                    this.value = '';
                }
            });
            
            endDateInput.addEventListener('change', function() {
                if (startDateInput.value && this.value < startDateInput.value) {
                    showNotification('End date cannot be before start date.', 'warning');
                    this.value = '';
                }
            });
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupCharacterCounters();
            validateResearchDates();
            
            // Add event listeners for required fields
            const requiredInputs = document.querySelectorAll('#paperTitle, #keywords, #authorName, #authorEmail, #affiliation, #abstract');
            requiredInputs.forEach(input => {
                input.addEventListener('input', updateProgress);
                
                // Remove error styling when user starts typing
                input.addEventListener('input', function() {
                    this.classList.remove('border-red-500');
                });
            });
            
            // Add event listeners for checkboxes
            document.getElementById('termsAgree').addEventListener('change', updateProgress);
            
            // Initialize progress
            updateProgress();
        });
    </script>
</body>
</html>