<?php
require_once 'connect.php';
session_start();

// Get paper ID
$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paper_id) {
    http_response_code(404);
    die("Paper not found");
}

// Get paper file information
$sql = "SELECT file_path, paper_title, status FROM paper_submissions 
        WHERE id = ? AND status IN ('approved', 'published')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $paper_id);
$stmt->execute();
$result = $stmt->get_result();
$paper = $result->fetch_assoc();

if (!$paper || !$paper['file_path']) {
    http_response_code(404);
    die("File not found");
}

// Get the file path
$file_path = $paper['file_path'];

// Try multiple path resolution strategies for Hostinger
$possible_paths = [
    // Direct path as stored
    $file_path,
    // Remove leading ./ or /
    ltrim($file_path, './'),
    // Prepend document root
    $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($file_path, './'),
    // Try with current directory
    __DIR__ . '/' . ltrim($file_path, './'),
    // Try parent directory (common Hostinger structure)
    dirname(__DIR__) . '/' . ltrim($file_path, './'),
    // Try public_html path (Hostinger default)
    $_SERVER['DOCUMENT_ROOT'] . '/public_html/' . ltrim($file_path, './')
];

$absolute_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path) && is_readable($path)) {
        $absolute_path = $path;
        break;
    }
}

if (!$absolute_path) {
    // Debug information (remove in production)
    error_log("PDF File not found. Tried paths: " . print_r($possible_paths, true));
    http_response_code(404);
    die("PDF file not found on server");
}

// Verify it's a PDF
$file_extension = strtolower(pathinfo($absolute_path, PATHINFO_EXTENSION));
if ($file_extension !== 'pdf') {
    http_response_code(400);
    die("Invalid file type");
}

// Get file size
$file_size = filesize($absolute_path);

// Set headers for PDF display
header('Content-Type: application/pdf');
header('Content-Length: ' . $file_size);
header('Content-Disposition: inline; filename="' . basename($absolute_path) . '"');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');
header('Pragma: public');

// Handle range requests for streaming
if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    
    if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
        $start = $matches[1] ? intval($matches[1]) : 0;
        $end = $matches[2] ? intval($matches[2]) : $file_size - 1;
        
        if ($end >= $file_size) {
            $end = $file_size - 1;
        }
        
        $length = $end - $start + 1;
        
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$file_size");
        header("Content-Length: $length");
        
        $file = fopen($absolute_path, 'rb');
        fseek($file, $start);
        
        // Output in chunks
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

// Output entire file
readfile($absolute_path);
exit;
?>