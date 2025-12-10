<?php
// ============================================
// FILE 1: get_paper_details.php
// Place this in your root directory
// ============================================
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once 'connect.php';

$paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$paper_id) {
    echo json_encode(['error' => 'Invalid paper ID']);
    exit();
}

try {
    // Get paper details with metrics
    $sql = "SELECT ps.*, 
                   COALESCE(SUM(CASE WHEN pm.metric_type = 'view' THEN 1 ELSE 0 END), 0) as total_views,
                   COALESCE(SUM(CASE WHEN pm.metric_type = 'download' THEN 1 ELSE 0 END), 0) as total_downloads
            FROM paper_submissions ps 
            LEFT JOIN paper_metrics pm ON ps.id = pm.paper_id
            WHERE ps.id = ?
            GROUP BY ps.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Paper not found']);
        exit();
    }
    
    $paper = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'paper' => $paper
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching paper details: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>

