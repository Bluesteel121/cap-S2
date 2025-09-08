<?php
session_start();
require_once 'user_activity_logger.php';

// Set content type for JSON response
header('Content-Type: application/json');

if ($_POST) {
    $action = $_POST['action'] ?? '';
    $details = $_POST['details'] ?? '';
    
    try {
        switch ($action) {
            case 'login_attempt':
                $username = $_POST['username'] ?? '';
                logActivity('LOGIN_FORM_SUBMITTED', "Username: $username");
                break;
                
            case 'signup_attempt':
                logActivity('SIGNUP_FORM_SUBMITTED', $details);
                break;
                
            case 'paper_view':
                $paper_id = $_POST['paper_id'] ?? '';
                $paper_title = $_POST['paper_title'] ?? '';
                logPaperView($paper_id, $paper_title);
                break;
                
            case 'paper_download':
                $paper_id = $_POST['paper_id'] ?? '';
                $paper_title = $_POST['paper_title'] ?? '';
                logPaperDownload($paper_id, $paper_title);
                break;
                
            case 'search_interaction':
                logActivity('SEARCH_INTERACTION', $details);
                break;
                
            case 'navigation_click':
                logActivity('NAVIGATION_CLICK', $details);
                break;
                
            case 'form_field_interaction':
                logActivity('FORM_FIELD_INTERACTION', $details);
                break;
                
            case 'file_validation_error':
                logError($details, 'FILE_VALIDATION_ERROR');
                break;
                
            case 'page_time_spent':
                logActivity('PAGE_TIME_TRACKING', $details);
                break;
                
            // Signup specific actions
            case 'ADDRESS_PREFERENCE_CHANGED':
                logActivity('ADDRESS_PREFERENCE_CHANGED', $details);
                break;
                
            case 'PROVINCE_SELECTED':
            case 'MUNICIPALITY_SELECTED':
            case 'BARANGAY_SELECTED':
                logActivity($action, $details);
                break;
                
            case 'LOCATION_DATA_LOADED':
            case 'LOCATION_DATA_ERROR':
                logActivity($action, $details);
                break;
                
            case 'SIGNUP_VALIDATION_ERROR':
                logError($details, 'SIGNUP_VALIDATION_ERROR');
                break;
                
            // Paper submission specific actions
            case 'FILE_SELECTED':
            case 'FILE_SIZE_VALIDATION_ERROR':
            case 'RESEARCH_TYPE_SELECTED':
            case 'FORM_FIELD_COMPLETED':
            case 'SUBMISSION_VALIDATION_ERROR':
            case 'PAPER_SUBMISSION_FORM_SUBMIT':
                logActivity($action, $details);
                break;
                
            default:
                // Generic activity logging
                logActivity($action, $details);
                break;
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Activity logged']);
        
    } catch (Exception $e) {
        logError("Failed to log activity: " . $e->getMessage(), 'LOGGING_ERROR');
        echo json_encode(['status' => 'error', 'message' => 'Failed to log activity']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No POST data received']);
}
?>