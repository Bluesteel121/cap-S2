<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once 'connect.php';

header('Content-Type: application/json');

try {
    $paper_id = (int)$_GET['paper_id'];
    $current_username = $_SESSION['username'];
    
    // Verify paper belongs to user (unless admin/reviewer)
    if (!in_array($_SESSION['role'] ?? 'user', ['admin', 'reviewer'])) {
        $verify_sql = "SELECT id FROM paper_submissions WHERE id = ? AND LOWER(user_name) = LOWER(?)";
        $stmt = $conn->prepare($verify_sql);
        $stmt->bind_param('is', $paper_id, $current_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Paper not found or access denied');
        }
    }
    
    // Get revision history
    $sql = "SELECT * FROM paper_revisions 
            WHERE paper_id = ? 
            ORDER BY revision_number DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $paper_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $revisions = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'revisions' => $revisions
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching revision history: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>