<?php
/**
 * Track Paper View - Improved Version
 * Records when a user views a research paper with better error handling
 */

session_start();
require_once 'connect.php';

// Set JSON header
header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Check if paper_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No paper ID provided']);
    exit();
}

$paper_id = intval($_GET['id']);

// Get user information
$user_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'guest';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Check for forwarded IP (if behind proxy/load balancer)
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip_address = trim($ip_list[0]);
} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $ip_address = $_SERVER['HTTP_X_REAL_IP'];
}

// Verify paper exists and is approved/published
$check_sql = "SELECT id, paper_title, status FROM paper_submissions WHERE id = ? AND status IN ('approved', 'published')";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $paper_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Track view failed: Paper $paper_id not found or not approved");
    echo json_encode(['success' => false, 'message' => 'Paper not found or not published']);
    exit();
}

$paper = $result->fetch_assoc();
$paper_title = $paper['paper_title'];

// IMPROVED: Check for duplicate views in last 30 minutes (reduced from 1 hour)
// This allows the same user to re-view after 30 minutes
$duplicate_check_sql = "SELECT id FROM paper_metrics 
                        WHERE paper_id = ? 
                        AND metric_type = 'view' 
                        AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";

// Check by user_id if logged in, otherwise by IP
if ($user_id) {
    $duplicate_check_sql .= " AND user_id = ?";
    $dup_stmt = $conn->prepare($duplicate_check_sql);
    $dup_stmt->bind_param('ii', $paper_id, $user_id);
} else {
    $duplicate_check_sql .= " AND ip_address = ? AND user_id IS NULL";
    $dup_stmt = $conn->prepare($duplicate_check_sql);
    $dup_stmt->bind_param('is', $paper_id, $ip_address);
}

$dup_stmt->execute();
$dup_result = $dup_stmt->get_result();

if ($dup_result->num_rows > 0) {
    // Already counted this view recently
    error_log("Duplicate view prevented for paper $paper_id from " . ($user_id ? "user $user_id" : "IP $ip_address"));
    echo json_encode([
        'success' => true, 
        'message' => 'View already counted in last 30 minutes', 
        'duplicate' => true,
        'paper_id' => $paper_id
    ]);
    exit();
}

// Insert view metric
$insert_sql = "INSERT INTO paper_metrics (paper_id, metric_type, user_id, ip_address, created_at) 
               VALUES (?, 'view', ?, ?, NOW())";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param('iis', $paper_id, $user_id, $ip_address);

if ($insert_stmt->execute()) {
    $metric_id = $insert_stmt->insert_id;
    
    // Log to user_activity_logs if user is logged in
    if ($user_id) {
        $log_sql = "INSERT INTO user_activity_logs 
                    (user_id, username, activity_type, activity_description, ip_address, paper_id, created_at) 
                    VALUES (?, ?, 'view_paper', ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $activity_desc = "Viewed paper: " . substr($paper_title, 0, 100);
        $log_stmt->bind_param('isssi', $user_id, $username, $activity_desc, $ip_address, $paper_id);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    // Get current view count
    $count_sql = "SELECT COUNT(*) as view_count FROM paper_metrics WHERE paper_id = ? AND metric_type = 'view'";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param('i', $paper_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $view_count = $count_result->fetch_assoc()['view_count'];
    
    error_log("View tracked successfully for paper $paper_id (metric_id: $metric_id, total views: $view_count)");
    
    echo json_encode([
        'success' => true, 
        'message' => 'View tracked successfully',
        'metric_id' => $metric_id,
        'paper_id' => $paper_id,
        'total_views' => $view_count,
        'user_id' => $user_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    error_log("Failed to track view for paper $paper_id: " . $conn->error);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to track view: ' . $conn->error,
        'paper_id' => $paper_id
    ]);
}

$conn->close();
?>