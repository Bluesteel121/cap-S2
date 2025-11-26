<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once 'connect.php';

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

if (!$paper || !$paper['file_path']) {
    http_response_code(404);
    die("File not found in database");
}

$file_path = $paper['file_path'];

// Try multiple path resolution strategies for Hostinger
$possible_paths = [
    $file_path,
    ltrim($file_path, './'),
    $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file_path, './'),
    __DIR__ . '/' . ltrim($file_path, './'),
    dirname(__DIR__) . '/' . ltrim($file_path, './')
];

$absolute_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path) && is_readable($path)) {
        $absolute_path = $path;
        error_log("Found file at: $path");
        break;
    }
}

if (!$absolute_path) {
    error_log("File not found for paper ID: $paper_id");
    error_log("Stored path: $file_path");
    http_response_code(404);
    die("File not found on server. Path stored in DB: " . htmlspecialchars($file_path));
}

$file_extension = strtolower(pathinfo($absolute_path, PATHINFO_EXTENSION));
$file_name = sanitizeFileName($paper['paper_title']) . '.' . $file_extension;

// Get user IP with proxy detection
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $user_ip = trim($ip_list[0]);
} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $user_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $user_ip = $_SERVER['HTTP_X_REAL_IP'];
}

// IMPROVED: Record view metric for PDF viewer mode
if ($view_mode && $file_extension === 'pdf') {
    try {
        // Check for duplicate views within last 30 minutes (reduced from 1 hour)
        $view_check = "SELECT id FROM paper_metrics 
                       WHERE paper_id = ? 
                       AND metric_type = 'view' 
                       AND user_id = ? 
                       AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
        $view_stmt = $conn->prepare($view_check);
        $view_stmt->bind_param('ii', $paper_id, $_SESSION['id']);
        $view_stmt->execute();
        $view_result = $view_stmt->get_result();
        
        if ($view_result->num_rows === 0) {
            $view_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
                         VALUES (?, 'view', ?, ?, NOW())";
            $view_insert_stmt = $conn->prepare($view_sql);
            $view_insert_stmt->bind_param('iis', $paper_id, $_SESSION['id'], $user_ip);
            $view_insert_stmt->execute();
            $view_insert_stmt->close();
            
            // Log view activity
            $view_activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, paper_id, ip_address, created_at)
                                  VALUES (?, ?, 'view_paper', ?, ?, ?, NOW())";
            $view_activity_stmt = $conn->prepare($view_activity_sql);
            $view_activity_desc = "Viewed paper: " . $paper['paper_title'];
            $view_activity_stmt->bind_param('isssi', $_SESSION['id'], $_SESSION['username'], $view_activity_desc, $user_ip, $paper_id);
            $view_activity_stmt->execute();
            $view_activity_stmt->close();
            
            error_log("View tracked for paper ID: $paper_id by user: {$_SESSION['username']}");
        } else {
            error_log("Duplicate view prevented (within 30 min) for paper ID: $paper_id");
        }
        $view_stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        error_log("Error recording view metric: " . $e->getMessage());
    }
}

// IMPROVED: Record download metric (only for actual downloads, not views)
if (!$view_mode) {
    try {
        // Check for duplicate downloads within last 2 minutes (reduced from 5 minutes)
        // This prevents accidental double-clicks but allows re-downloads
        $duplicate_check = "SELECT id FROM paper_metrics 
                           WHERE paper_id = ? 
                           AND metric_type = 'download' 
                           AND user_id = ? 
                           AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)";
        $dup_stmt = $conn->prepare($duplicate_check);
        $dup_stmt->bind_param('ii', $paper_id, $_SESSION['id']);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result();
        
        if ($dup_result->num_rows === 0) {
            $download_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
                             VALUES (?, 'download', ?, ?, NOW())";
            $download_stmt = $conn->prepare($download_sql);
            $download_stmt->bind_param('iis', $paper_id, $_SESSION['id'], $user_ip);
            $download_stmt->execute();
            $download_stmt->close();
            
            // Log download activity
            $activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, paper_id, ip_address, created_at)
                             VALUES (?, ?, 'download_paper', ?, ?, ?, NOW())";
            $activity_stmt = $conn->prepare($activity_sql);
            $activity_desc = "Downloaded paper: " . $paper['paper_title'];
            $activity_stmt->bind_param('isssi', $_SESSION['id'], $_SESSION['username'], $activity_desc, $user_ip, $paper_id);
            $activity_stmt->execute();
            $activity_stmt->close();
            
            error_log("Download tracked for paper ID: $paper_id by user: {$_SESSION['username']}");
        } else {
            error_log("Duplicate download prevented (within 2 min) for paper ID: $paper_id");
        }
        $dup_stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        error_log("Error recording download metric: " . $e->getMessage());
    }
}

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
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

header('Content-Length: ' . filesize($absolute_path));
header('Cache-Control: public, max-age=3600');
header('Accept-Ranges: bytes');
header('Pragma: public');

// Handle range requests for PDF streaming
if (isset($_SERVER['HTTP_RANGE']) && $file_extension === 'pdf') {
    $size = filesize($absolute_path);
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
        
        $file = fopen($absolute_path, 'rb');
        fseek($file, $start);
        
        $buffer_size = 8192;
        while (!feof($file) && $length > 0) {
            $read_size = min($buffer_size, $length);
            echo fread($file, $read_size);
            $length -= $read_size;
            flush();
        }
        
        fclose($file);
        exit;
    }
}

// Output the file
readfile($absolute_path);
exit;

function sanitizeFileName($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
    $filename = preg_replace('/\s+/', '_', $filename);
    $filename = substr($filename, 0, 200);
    return $filename;
}
?>