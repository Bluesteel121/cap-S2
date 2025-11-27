<?php
/**
 * Search Handler - Processes AJAX search requests
 * Place this file in your root directory as search_handler.php
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1); // Log errors instead

session_start();
require_once 'search_functions.php';

// Check if it's a valid request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<div class="p-4 text-center text-red-600">Method not allowed</div>';
    exit();
}

// Get search query
$query = isset($_POST['query']) ? trim($_POST['query']) : '';
$is_logged_in = isset($_POST['is_logged_in']) && $_POST['is_logged_in'] === '1';

// Verify logged-in status from session
if ($is_logged_in && !isset($_SESSION['name'])) {
    $is_logged_in = false;
}

// Connect to database if user is logged in
$conn = null;
if ($is_logged_in) {
    try {
        require_once 'connect.php';
        
        // Verify connection is valid
        if (!isset($conn) || $conn->connect_error) {
            error_log("Database connection failed: " . ($conn ? $conn->connect_error : 'Connection object not created'));
            $conn = null;
            $is_logged_in = false; // Disable paper search if DB connection fails
        }
    } catch (Exception $e) {
        error_log("Exception connecting to database: " . $e->getMessage());
        $conn = null;
        $is_logged_in = false;
    }
}

// Validate query
if (empty($query)) {
    echo '<div class="p-4 text-center text-gray-600">Please enter a search term</div>';
    exit();
}

if (strlen($query) < 2) {
    echo '<div class="p-4 text-center text-gray-600">Search term must be at least 2 characters</div>';
    exit();
}

try {
    // Get search results
    $results = getSearchResults($query, $is_logged_in, $conn);
    
    // Render and output results
    echo renderSearchResults($results, $is_logged_in);
    
} catch (Exception $e) {
    error_log("Error in search_handler: " . $e->getMessage());
    echo '<div class="p-4 text-center text-red-600">An error occurred while searching. Please try again.</div>';
}

// Close database connection if it was opened
if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>