<?php
require_once 'connect.php';

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paper_id) {
    http_response_code(404);
    echo "Paper not found";
    exit();
}

// Get paper file information
$sql = "SELECT file_path, paper_title FROM paper_submissions WHERE id = ? AND status IN ('approved', 'published')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paper_id);
$stmt->execute();
$result = $stmt->get_result();
$paper = $result->fetch_assoc();

if (!$paper || !$paper['file_path'] || !file_exists($paper['file_path'])) {
    http_response_code(404);
    echo "File not found";
    exit();
}

$file_path = $paper['file_path'];
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Record download metric
$download_sql = "INSERT INTO paper_metrics (paper_id, metric_type, ip_address, created_at) VALUES (?, 'download', ?, NOW())";
$download_stmt = $conn->prepare($download_sql);
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$download_stmt->bind_param('is', $paper_id, $user_ip);
$download_stmt->execute();

// Set appropriate content type and headers
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

// Set headers for inline viewing or download based on URL parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'inline';

if ($action === 'download') {
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
}

header('Content-Length: ' . filesize($file_path));
header('Accept-Ranges: bytes');

// Enable caching for better performance
header('Cache-Control: public, max-age=3600');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_path)) . ' GMT');

// Handle range requests for better PDF streaming
if (isset($_SERVER['HTTP_RANGE'])) {
    $size = filesize($file_path);
    $range = $_SERVER['HTTP_RANGE'];
    
    if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
        $start = intval($matches[1]);
        $end = intval($matches[2]);
        
        if ($start == 0 && $end == 0) {
            $end = $size - 1;
        }
        
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
    }
} else {
    // Output the entire file
    readfile($file_path);
}

exit();
?>