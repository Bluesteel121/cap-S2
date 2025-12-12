<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php';
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get parameters from request
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Validate keyword
if (empty($keyword)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Keyword parameter is required']);
    exit();
}

// Get papers by keyword
$sql = "SELECT 
            ps.id,
            ps.paper_title,
            ps.author_name,
            ps.submission_date,
            ps.keywords,
            ps.status,
            ps.research_type
        FROM paper_submissions ps
        WHERE DATE(ps.submission_date) BETWEEN ? AND ?
        AND LOWER(ps.keywords) LIKE ?
        ORDER BY ps.submission_date DESC";

$stmt = $conn->prepare($sql);
$search_keyword = '%' . strtolower($keyword) . '%';
$stmt->bind_param('sss', $start_date, $end_date, $search_keyword);
$stmt->execute();
$result = $stmt->get_result();

$papers = [];
while ($row = $result->fetch_assoc()) {
    $papers[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($papers);

$stmt->close();
$conn->close();
?>