<?php
require_once 'connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: userlogin.php");
    exit();
}

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$view_mode = isset($_GET['view']) && $_GET['view'] == 1;

if (!$paper_id) {
    http_response_code(404);
    die("Paper not found");
}

// Get paper details
$sql = "SELECT file_path, paper_title, status FROM paper_submissions 
        WHERE id = ? AND status IN ('approved', 'published')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paper_id);
$stmt->execute();
$result = $stmt->get_result();
$paper = $result->fetch_assoc();

if (!$paper || !$paper['file_path'] || !file_exists($paper['file_path'])) {
    http_response_code(404);
    die("File not found");
}

$file_path = $paper['file_path'];
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$file_name = sanitizeFileName($paper['paper_title']) . '.' . $file_extension;

// Record download metric (only for actual downloads, not views)
if (!$view_mode) {
    $download_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
                     VALUES (?, 'download', ?, ?, NOW())";
    $download_stmt = $conn->prepare($download_sql);
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $download_stmt->bind_param('iis', $paper_id, $_SESSION['id'], $user_ip);
    $download_stmt->execute();
    
    // Log download activity
    $activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, paper_id, ip_address, created_at)
                     VALUES (?, ?, 'download_paper', ?, ?, ?, NOW())";
    $activity_stmt = $conn->prepare($activity_sql);
    $activity_desc = "Downloaded paper: " . $paper['paper_title'];
    $activity_stmt->bind_param('issss', $_SESSION['id'], $_SESSION['username'], $activity_desc, $paper_id, $user_ip);
    $activity_stmt->execute();
} else {
    // Record view metric
    $view_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
                 VALUES (?, 'view', ?, ?, NOW())";
    $view_stmt = $conn->prepare($view_sql);
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $view_stmt->bind_param('iis', $paper_id, $_SESSION['id'], $user_ip);
    $view_stmt->execute();
}

// Set appropriate content type
switch ($file_extension) {
    case 'pdf':
        header('Content-Type: application/pdf');
        break;
    case 'doc':
        header('Content-Type: application/msword');
        break;
    case 'docx':
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        break;
    default:
        header('Content-Type: application/octet-stream');
}

// Set disposition based on view mode
if ($view_mode && $file_extension === 'pdf') {
    header('Content-Disposition: inline; filename="' . $file_name . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
}

header('Content-Length: ' . filesize($file_path));
header('Cache-Control: public, max-age=3600');
header('Accept-Ranges: bytes');

// Handle range requests for PDF streaming
if (isset($_SERVER['HTTP_RANGE']) && $file_extension === 'pdf') {
    $size = filesize($file_path);
    $range = $_SERVER['HTTP_RANGE'];
    
    if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
        $start = $matches[1] ? intval($matches[1]) : 0;
        $end = $matches[2] ? intval($matches[2]) : $size - 1;
        
        if ($end >= $size) {
            $end = $size - 1;
        }
        
        $length = $end - $start + 1;
        
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: $length");
        
        $file = fopen($file_path, 'rb');
        fseek($file, $start);
        echo fread($file, $length);
        fclose($file);
        exit;
    }
}

// Output the file
readfile($file_path);
exit;

// Helper function to sanitize filename
function sanitizeFileName($filename) {
    // Remove special characters and limit length
    $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
    $filename = preg_replace('/\s+/', '_', $filename);
    $filename = substr($filename, 0, 200);
    return $filename;
}
?>