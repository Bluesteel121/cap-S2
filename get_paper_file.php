<?php
session_start();

// Check if user is logged in and has admin/reviewer role
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'reviewer'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once 'connect.php';

header('Content-Type: application/json');

try {
    $paper_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($paper_id <= 0) {
        throw new Exception('Invalid paper ID');
    }
    
    // Get paper file path
    $stmt = $conn->prepare("SELECT file_path, paper_title FROM paper_submissions WHERE id = ?");
    $stmt->bind_param('i', $paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Verify file exists
        if (file_exists($row['file_path'])) {
            echo json_encode([
                'success' => true,
                'file_path' => $row['file_path'],
                'paper_title' => $row['paper_title']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'PDF file not found on server'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Paper not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>