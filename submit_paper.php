<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once 'connect.php';

// Handle form submission
if ($_POST) {
    try {

        // Handle file upload
        $file_path = null;
        if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] == 0) {
            $upload_dir = 'uploads/papers/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['paper_file']['tmp_name'], $file_path)) {
                throw new Exception("Failed to upload file");
            }
        }

        // Insert paper submission into database using MySQLi
        $sql = "INSERT INTO paper_submissions (
            user_name, 
            author_name, 
            co_authors, 
            paper_title, 
            abstract, 
            keywords, 
            research_type, 
            file_path, 
            submission_date, 
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", 
            $_SESSION['name'],
            $_POST['author_name'],
            $_POST['co_authors'],
            $_POST['paper_title'],
            $_POST['abstract'],
            $_POST['keywords'],
            $_POST['research_type'],
            $file_path
        );

        if ($stmt->execute()) {
            $success_message = "Paper submitted successfully! Your submission is now under review.";
        } else {
            throw new Exception("Failed to submit paper: " . $conn->error);
        }
        
        $stmt->close();
        
    } catch(Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Paper - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-[#115D5B] text-white py-4 px-6">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                <h1 class="text-xl font-bold">Submit Paper</h1>
            </div>
            <a href="loggedin_index.php" class="flex items-center space-x-2 hover:text-green-200">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Home</span>
            </a>
        </div>
    </header>

    <div class="max-w-4xl mx-auto py-8 px-6">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Paper Submission Form -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-[#115D5B] mb-6">Submit Your Research Paper</h2>
            
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Author Information -->
                <div class="border-b pb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Author Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="author_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Primary Author Name *
                            </label>
                            <input type="text" id="author_name" name="author_name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="co_authors" class="block text-sm font-medium text-gray-700 mb-2">
                                Co-Authors (if any)
                            </label>
                            <input type="text" id="co_authors" name="co_authors"
                                placeholder="Separate multiple authors with commas"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Paper Details -->
                <div class="border-b pb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Paper Details</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="paper_title" class="block text-sm font-medium text-gray-700 mb-2">
                                Paper Title *
                            </label>
                            <input type="text" id="paper_title" name="paper_title" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                        </div>

                        <div>
                            <label for="research_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Research Type *
                            </label>
                            <select id="research_type" name="research_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                                <option value="">Select Research Type</option>
                                <option value="Agricultural Research">Agricultural Research</option>
                                <option value="Crop Science">Crop Science</option>
                                <option value="Soil Science">Soil Science</option>
                                <option value="Plant Pathology">Plant Pathology</option>
                                <option value="Entomology">Entomology</option>
                                <option value="Food Technology">Food Technology</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="keywords" class="block text-sm font-medium text-gray-700 mb-2">
                                Keywords *
                            </label>
                            <input type="text" id="keywords" name="keywords" required
                                placeholder="Separate keywords with commas (e.g., pineapple, farming, agriculture)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                        </div>

                        <div>
                            <label for="abstract" class="block text-sm font-medium text-gray-700 mb-2">
                                Abstract *
                            </label>
                            <textarea id="abstract" name="abstract" rows="6" required
                                placeholder="Provide a brief summary of your research paper..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>

                <!-- File Upload -->
                <div class="border-b pb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Paper File</h3>
                    
                    <div>
                        <label for="paper_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Paper File * (PDF, DOC, DOCX - Max 10MB)
                        </label>
                        <input type="file" id="paper_file" name="paper_file" 
                            accept=".pdf,.doc,.docx" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">
                            Accepted formats: PDF, DOC, DOCX. Maximum file size: 10MB
                        </p>
                    </div>
                </div>

                <!-- Submission Guidelines -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Submission Guidelines
                    </h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Ensure your paper follows academic formatting standards</li>
                        <li>• All research must be original and unpublished</li>
                        <li>• Papers will undergo peer review process</li>
                        <li>• You will receive updates on your submission status via email</li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <div class="pt-6">
                    <button type="submit" 
                        class="w-full bg-[#115D5B] hover:bg-[#0d4a47] text-white font-semibold py-3 px-6 rounded-md transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Paper for Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-[#115D5B] text-white text-center py-4 mt-12">
        <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
    </footer>

    <script>
        // File size validation
        document.getElementById('paper_file').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB in bytes
                if (file.size > maxSize) {
                    alert('File size must be less than 10MB. Please choose a smaller file.');
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>