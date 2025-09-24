<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Include database connection
require_once 'connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Validate required fields
    $required_fields = ['paper_title', 'research_type', 'keywords', 'author_name', 'author_email', 'affiliation', 'abstract'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit();
    }
    
    // Validate research type
    $valid_types = ['experimental', 'observational', 'review', 'case_study'];
    if (!in_array($_POST['research_type'], $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid research type']);
        exit();
    }
    
    // Validate email
    if (!filter_var($_POST['author_email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/papers/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['paper_file'];
        
        // Validate file type
        if ($file['type'] !== 'application/pdf') {
            echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
            exit();
        }
        
        // Validate file size (25MB)
        if ($file['size'] > 25 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 25MB']);
            exit();
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'PDF file is required']);
        exit();
    }
    
    // Prepare data for database insertion
    $paper_title = trim($_POST['paper_title']);
    $research_type = $_POST['research_type'];
    $keywords = trim($_POST['keywords']);
    $author_name = trim($_POST['author_name']);
    $author_email = trim($_POST['author_email']);
    $affiliation = trim($_POST['affiliation']);
    $abstract = trim($_POST['abstract']);
    $user_name = $_SESSION['name'];
    
    // Optional fields
    $co_authors = trim($_POST['co_authors'] ?? '');
    $methodology = trim($_POST['methodology'] ?? '');
    $funding_source = trim($_POST['funding_source'] ?? '');
    $research_start_date = !empty($_POST['research_start_date']) ? $_POST['research_start_date'] : null;
    $research_end_date = !empty($_POST['research_end_date']) ? $_POST['research_end_date'] : null;
    $ethics_approval = trim($_POST['ethics_approval'] ?? '');
    $additional_comments = trim($_POST['additional_comments'] ?? '');
    
    // Consent checkboxes
    $terms_agreement = isset($_POST['terms_agreement']) ? 1 : 0;
    $email_consent = isset($_POST['email_consent']) ? 1 : 0;
    $data_consent = isset($_POST['data_consent']) ? 1 : 0;
    
    // Check what columns exist in the table
    $columns_check = $conn->query("DESCRIBE paper_submissions");
    $existing_columns = [];
    while ($row = $columns_check->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    // Build dynamic SQL based on available columns - FIXED: Use MySQLi instead of PDO
    $base_fields = [
        'paper_title' => $paper_title,
        'research_type' => $research_type,
        'keywords' => $keywords,
        'author_name' => $author_name,
        'abstract' => $abstract,
        'file_path' => $file_path,
        'status' => 'pending',
        'submission_date' => date('Y-m-d H:i:s'),
        'user_name' => $user_name
    ];
    
    $enhanced_fields = [
        'author_email' => $author_email,
        'affiliation' => $affiliation,
        'co_authors' => $co_authors,
        'methodology' => $methodology,
        'funding_source' => $funding_source,
        'research_start_date' => $research_start_date,
        'research_end_date' => $research_end_date,
        'ethics_approval' => $ethics_approval,
        'additional_comments' => $additional_comments,
        'terms_agreement' => $terms_agreement,
        'email_consent' => $email_consent,
        'data_consent' => $data_consent
    ];
    
    // Combine fields, only including those that exist in the table
    $insert_data = [];
    foreach ($base_fields as $field => $value) {
        if (in_array($field, $existing_columns)) {
            $insert_data[$field] = $value;
        }
    }
    
    foreach ($enhanced_fields as $field => $value) {
        if (in_array($field, $existing_columns)) {
            $insert_data[$field] = $value;
        }
    }
    
    // Build SQL query using MySQLi prepared statements
    $columns = implode(', ', array_keys($insert_data));
    $placeholders = implode(', ', array_fill(0, count($insert_data), '?'));
    
    $sql = "INSERT INTO paper_submissions ($columns) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Create types string and values array for binding
    $types = '';
    $values = [];
    
    foreach ($insert_data as $key => $value) {
        if (in_array($key, ['terms_agreement', 'email_consent', 'data_consent'])) {
            $types .= 'i'; // integer for boolean fields
        } elseif ($key === 'research_start_date' || $key === 'research_end_date') {
            $types .= 's'; // string for dates (can be null)
        } else {
            $types .= 's'; // string for most fields
        }
        $values[] = $value;
    }
    
    // Bind parameters
    $stmt->bind_param($types, ...$values);
    
    // Execute query
    if ($stmt->execute()) {
        $submission_id = $conn->insert_id;
        $reference_number = 'CNLRRS-' . date('Y') . '-' . str_pad($submission_id, 6, '0', STR_PAD_LEFT);
        
        // Update the record with reference number if that column exists
        if (in_array('reference_number', $existing_columns)) {
            $update_sql = "UPDATE paper_submissions SET reference_number = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('si', $reference_number, $submission_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Paper submitted successfully',
            'submission_id' => $submission_id,
            'reference_number' => $reference_number
        ]);
        
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
} catch (Exception $e) {
    // Clean up uploaded file if database insertion failed
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    
    error_log("Paper submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again. Error: ' . $e->getMessage()
    ]);
}
?>