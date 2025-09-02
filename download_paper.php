<?php
require_once 'connect.php';
session_start();

// Check if paper ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    exit('Paper not found');
}

$paper_id = (int)$_GET['id'];

// Get paper details
$sql = "SELECT * FROM paper_submissions WHERE id = ? AND status IN ('approved', 'published')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paper_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.0 404 Not Found');
    exit('Paper not found or not available for download');
}

$paper = $result->fetch_assoc();

// Check if file exists
if (!$paper['file_path'] || !file_exists($paper['file_path'])) {
    header('HTTP/1.0 404 Not Found');
    exit('Paper file not found');
}

// Log download metric
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

$metric_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) VALUES (?, 'download', ?, ?, NOW())";
$metric_stmt = $conn->prepare($metric_sql);
$metric_stmt->bind_param('iis', $paper_id, $user_id, $ip_address);
$metric_stmt->execute();

// Log user activity if logged in
if ($user_id && isset($_SESSION['username'])) {
    $activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, ip_address, paper_id, created_at) 
                     VALUES (?, ?, 'download_paper', ?, ?, ?, NOW())";
    $activity_stmt = $conn->prepare($activity_sql);
    $description = "Downloaded paper: " . $paper['paper_title'];
    $activity_stmt->bind_param('isssi', $user_id, $_SESSION['username'], $description, $ip_address, $paper_id);
    $activity_stmt->execute();
}

// Prepare file for download
$file_path = $paper['file_path'];
$file_name = basename($file_path);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Create a clean filename for download
$clean_title = preg_replace('/[^a-zA-Z0-9\s]/', '', $paper['paper_title']);
$clean_title = preg_replace('/\s+/', '_', trim($clean_title));
$download_name = substr($clean_title, 0, 50) . '.' . $file_extension;

// Set appropriate headers based on file type
switch ($file_extension) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'doc':
        $content_type = 'application/msword';
        break;
    case 'docx':
        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    default:
        $content_type = 'application/octet-stream';
}

// Clear any output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Set headers for file download
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output file
readfile($file_path);
exit();
?>