<?php
session_start();

if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "cap");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    
    try {
        // Basic fields only - these should exist in your original table
        $user_name = $_SESSION['name'];
        $author_name = $_POST['author_name'] ?? '';
        $paper_title = $_POST['paper_title'] ?? '';
        $abstract = $_POST['abstract'] ?? '';
        $keywords = $_POST['keywords'] ?? '';
        $research_type = $_POST['research_type'] ?? 'other';

        if (empty($author_name) || empty($paper_title) || empty($abstract)) {
            throw new Exception("Missing required fields");
        }

        // Handle file upload
        if (!isset($_FILES["paper_file"]) || $_FILES["paper_file"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed");
        }

        $file = $_FILES["paper_file"];
        if ($file["type"] !== "application/pdf") {
            throw new Exception("Only PDF files allowed");
        }

        $upload_dir = "uploads/papers/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $unique_name = uniqid() . "_" . basename($file["name"]);
        $file_path = $upload_dir . $unique_name;

        if (!move_uploaded_file($file["tmp_name"], $file_path)) {
            throw new Exception("Failed to upload file");
        }

        // Insert with basic fields only
        $sql = "INSERT INTO paper_submissions (user_name, author_name, paper_title, abstract, keywords, research_type, file_path, submission_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $user_name, $author_name, $paper_title, $abstract, $keywords, $research_type, $file_path);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "message" => "Paper submitted successfully",
                "redirect" => "my_submissions.php"
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

    } catch (Exception $e) {
        if (isset($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Submit</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8">
    <form id="testForm" enctype="multipart/form-data" class="max-w-md mx-auto space-y-4">
        <div>
            <label class="block text-sm font-medium">Author Name*</label>
            <input type="text" name="author_name" required class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium">Paper Title*</label>
            <input type="text" name="paper_title" required class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium">Abstract*</label>
            <textarea name="abstract" required class="w-full border rounded px-3 py-2"></textarea>
        </div>
        
        <div>
            <label class="block text-sm font-medium">Keywords</label>
            <input type="text" name="keywords" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-sm font-medium">Research Type</label>
            <select name="research_type" class="w-full border rounded px-3 py-2">
                <option value="experimental">Experimental</option>
                <option value="observational">Observational</option>
                <option value="review">Review</option>
                <option value="case_study">Case Study</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium">PDF File*</label>
            <input type="file" name="paper_file" accept=".pdf" required class="w-full border rounded px-3 py-2">
        </div>
        
        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded">Submit Test</button>
    </form>

    <script>
    document.getElementById('testForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Success! Redirecting...');
                window.location.href = data.redirect;
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    });
    </script>
</body>
</html>