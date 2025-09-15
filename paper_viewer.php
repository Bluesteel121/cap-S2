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

// Set appropriate content type
switch ($file_extension) {
    case 'pdf':
        header('Content-Type: application/pdf');
        break;
    case 'doc':
    case 'docx':
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        break;
    default:
        header('Content-Type: application/octet-stream');
}

// Set headers for inline viewing
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));

// Output the file
readfile($file_path);
exit();
?>