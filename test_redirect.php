<?php
// Simple test to see if redirect works at all
session_start();

// Set test session data
$_SESSION['username'] = 'test';
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;

// Try the redirect
header("Location: admin_loggedin_index.php");
exit();
?>