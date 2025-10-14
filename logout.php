<?php
session_start();

// Log the logout activity before destroying session
if (isset($_SESSION['id']) && isset($_SESSION['username'])) {
    require_once 'connect.php';
    require_once 'user_activity_logger.php';
    
    // Log logout activity
    $user_id = $_SESSION['id'];
    $username = $_SESSION['username'];
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $activity_sql = "INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, ip_address, created_at)
                     VALUES (?, ?, 'logout', 'User logged out', ?, NOW())";
    $activity_stmt = $conn->prepare($activity_sql);
    $activity_stmt->bind_param('iss', $user_id, $username, $user_ip);
    $activity_stmt->execute();
}

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page or homepage
header('Location: index.php');
exit();
?>