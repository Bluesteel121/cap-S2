<?php
/**
 * User Activity Logger
 * This file provides functions to log user activities across the CNLRRS system
 */

function logActivity($action, $details = '', $user_id = null, $ip_address = null) {
    $log_file = 'user_activity.txt';
    
    // Get current timestamp
    $timestamp = date('Y-m-d H:i:s');
    
    // Get user information
    $user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';
    if ($user_id) {
        $user = $user_id;
    }
    
    // Get IP address if not provided
    if (!$ip_address) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        // Check for forwarded IP in case of proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Get current page/script
    $current_page = $_SERVER['PHP_SELF'] ?? 'Unknown';
    
    // Create log entry
    $log_entry = "[$timestamp] USER: $user | IP: $ip_address | PAGE: $current_page | ACTION: $action";
    if (!empty($details)) {
        $log_entry .= " | DETAILS: $details";
    }
    $log_entry .= " | USER_AGENT: $user_agent\n";
    
    // Write to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function logLogin($username, $success = true, $role = 'user') {
    $action = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
    $details = "Role: $role, Username: $username";
    logActivity($action, $details);
}

function logLogout($username) {
    logActivity('LOGOUT', "Username: $username");
}

function logRegistration($username, $success = true) {
    $action = $success ? 'REGISTRATION_SUCCESS' : 'REGISTRATION_FAILED';
    $details = "Username: $username";
    logActivity($action, $details);
}

function logPaperSubmission($paper_title, $author, $success = true) {
    $action = $success ? 'PAPER_SUBMITTED' : 'PAPER_SUBMISSION_FAILED';
    $details = "Title: $paper_title, Author: $author";
    logActivity($action, $details);
}

function logPaperDownload($paper_id, $paper_title) {
    $details = "Paper ID: $paper_id, Title: $paper_title";
    logActivity('PAPER_DOWNLOADED', $details);
}

function logPaperView($paper_id, $paper_title) {
    $details = "Paper ID: $paper_id, Title: $paper_title";
    logActivity('PAPER_VIEWED', $details);
}

function logSearch($search_query, $category = '', $year = '', $results_count = 0) {
    $details = "Query: '$search_query'";
    if ($category) $details .= ", Category: $category";
    if ($year) $details .= ", Year: $year";
    $details .= ", Results: $results_count";
    logActivity('SEARCH_PERFORMED', $details);
}

function logPageView($page_name) {
    logActivity('PAGE_VIEW', "Page: $page_name");
}

function logError($error_message, $error_type = 'GENERAL_ERROR') {
    logActivity($error_type, $error_message);
}

function logProfileUpdate($username) {
    logActivity('PROFILE_UPDATED', "Username: $username");
}

function logPasswordChange($username, $success = true) {
    $action = $success ? 'PASSWORD_CHANGED' : 'PASSWORD_CHANGE_FAILED';
    logActivity($action, "Username: $username");
}

function logSecurityEvent($event_type, $details = '') {
    logActivity("SECURITY_EVENT_$event_type", $details);
}

function logAdminAction($admin_user, $action, $target = '') {
    $details = "Admin: $admin_user, Action: $action";
    if ($target) $details .= ", Target: $target";
    logActivity('ADMIN_ACTION', $details);
}

function logSystemEvent($event_type, $details = '') {
    logActivity("SYSTEM_$event_type", $details);
}

// Function to read recent activities (for admin dashboard)
function getRecentActivities($limit = 100) {
    $log_file = 'user_activity.txt';
    if (!file_exists($log_file)) {
        return [];
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_slice(array_reverse($lines), 0, $limit);
}

// Function to get activity statistics
function getActivityStats($days = 30) {
    $log_file = 'user_activity.txt';
    if (!file_exists($log_file)) {
        return [];
    }
    
    $cutoff_date = date('Y-m-d', strtotime("-$days days"));
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $stats = [
        'total_activities' => 0,
        'unique_users' => [],
        'page_views' => 0,
        'logins' => 0,
        'paper_submissions' => 0,
        'paper_downloads' => 0,
        'searches' => 0,
        'registrations' => 0
    ];
    
    foreach ($lines as $line) {
        // Extract date from log entry
        if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
            if ($matches[1] >= $cutoff_date) {
                $stats['total_activities']++;
                
                // Extract user
                if (preg_match('/USER: ([^|]+)/', $line, $user_matches)) {
                    $user = trim($user_matches[1]);
                    if ($user !== 'Guest') {
                        $stats['unique_users'][$user] = true;
                    }
                }
                
                // Count specific actions
                if (strpos($line, 'PAGE_VIEW') !== false) $stats['page_views']++;
                if (strpos($line, 'LOGIN_SUCCESS') !== false) $stats['logins']++;
                if (strpos($line, 'PAPER_SUBMITTED') !== false) $stats['paper_submissions']++;
                if (strpos($line, 'PAPER_DOWNLOADED') !== false) $stats['paper_downloads']++;
                if (strpos($line, 'SEARCH_PERFORMED') !== false) $stats['searches']++;
                if (strpos($line, 'REGISTRATION_SUCCESS') !== false) $stats['registrations']++;
            }
        }
    }
    
    $stats['unique_users'] = count($stats['unique_users']);
    return $stats;
}

// Function to clean old logs (keep only recent entries)
function cleanOldLogs($days_to_keep = 90) {
    $log_file = 'user_activity.txt';
    if (!file_exists($log_file)) {
        return false;
    }
    
    $cutoff_date = date('Y-m-d', strtotime("-$days_to_keep days"));
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_lines = [];
    
    foreach ($lines as $line) {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
            if ($matches[1] >= $cutoff_date) {
                $new_lines[] = $line;
            }
        }
    }
    
    // Write back the filtered logs
    file_put_contents($log_file, implode("\n", $new_lines) . "\n");
    logSystemEvent('LOG_CLEANUP', "Cleaned logs older than $days_to_keep days");
    
    return true;
}


?>