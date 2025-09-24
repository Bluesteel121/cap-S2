<?php
// Add this to your email_config.php or create a new file: reviewer_notifications.php

class ReviewerNotifications {
    
    /**
     * Send notification to admin when reviewer submits a recommendation
     */
    public static function notifyAdminOfReviewerAction($paper_data, $reviewer_username, $action, $comments, $recommendation, $conn) {
        try {
            // Get admin emails
            $admin_sql = "SELECT email FROM accounts WHERE role = 'admin' AND active = 1";
            $admin_result = $conn->query($admin_sql);
            $admin_emails = [];
            
            while ($admin = $admin_result->fetch_assoc()) {
                $admin_emails[] = $admin['email'];
            }
            
            if (empty($admin_emails)) {
                error_log("No admin emails found for reviewer notification");
                return false;
            }
            
            $action_labels = [
                'reviewer_approved' => 'Recommended for Approval',
                'reviewer_rejected' => 'Recommended for Rejection', 
                'revisions_requested' => 'Requested Revisions',
                'under_review' => 'Set Under Review'
            ];
            
            $action_label = $action_labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
            $action_colors = [
                'reviewer_approved' => '#059669',
                'reviewer_rejected' => '#dc2626', 
                'revisions_requested' => '#ea580c',
                'under_review' => '#2563eb'
            ];
            $action_color = $action_colors[$action] ?? '#6b7280';
            
            $subject = "[CNLRRS] Reviewer Action: {$action_label} - {$paper_data['paper_title']}";
            
            $body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #115D5B, #103625); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .footer { background: #115D5B; color: white; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; color: white; font-weight: bold; margin: 10px 0; }
        .paper-info { background: white; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .review-section { background: #f0f9ff; border-left: 4px solid #2563eb; padding: 15px; margin: 15px 0; }
        .action-required { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>📋 Reviewer Action Required</h2>
            <p>A reviewer has submitted their evaluation for a research paper</p>
        </div>
        
        <div class='content'>
            <div class='action-required'>
                <h3>🔔 Action Required</h3>
                <p><strong>Reviewer {$reviewer_username}</strong> has completed their review and needs your administrative decision.</p>
            </div>
            
            <div class='paper-info'>
                <h3>📄 Paper Details</h3>
                <p><strong>Title:</strong> {$paper_data['paper_title']}</p>
                <p><strong>Author:</strong> {$paper_data['author_name']}</p>
                <p><strong>Submitted by:</strong> {$paper_data['user_name']}</p>
                <p><strong>Research Type:</strong> {$paper_data['research_type']}</p>
                <p><strong>Submission Date:</strong> " . date('F j, Y', strtotime($paper_data['submission_date'])) . "</p>
            </div>
            
            <div style='text-align: center; margin: 20px 0;'>
                <div class='status-badge' style='background-color: {$action_color};'>
                    {$action_label}
                </div>
            </div>
            
            <div class='review-section'>
                <h3>👨‍🎓 Reviewer: {$reviewer_username}</h3>
                <p><strong>Review Date:</strong> " . date('F j, Y g:i A') . "</p>
                
                <h4>📝 Review Comments:</h4>
                <div style='background: white; padding: 10px; border-radius: 4px; border-left: 3px solid #2563eb;'>
                    " . nl2br(htmlspecialchars($comments)) . "
                </div>";
            
            if ($recommendation) {
                $body .= "
                <h4>🎯 Recommendation Summary:</h4>
                <div style='background: white; padding: 10px; border-radius: 4px; border-left: 3px solid {$action_color};'>
                    " . nl2br(htmlspecialchars($recommendation)) . "
                </div>";
            }
            
            $body .= "
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <p><strong>Next Steps:</strong></p>
                <p>Please log into the admin panel to review the recommendation and make your final decision.</p>
                <div style='margin: 20px 0;'>
                    <a href='" . self::getBaseUrl() . "/admin_review_papers.php' 
                       style='background: #115D5B; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                        📋 Review in Admin Panel
                    </a>
                </div>
            </div>
        </div>
        
        <div class='footer'>
            <p>&copy; " . date('Y') . " Camarines Norte Lowland Rainfed Research Station</p>
            <p style='font-size: 12px; opacity: 0.8;'>Admin Notification System</p>
        </div>
    </div>
</body>
</html>";
            
            // Send to all admins
            $mail_sent = false;
            foreach ($admin_emails as $admin_email) {
                if (EmailService::sendEmail($admin_email, $subject, $body)) {
                    $mail_sent = true;
                    logActivity('ADMIN_NOTIFICATION_SENT', 
                        "Reviewer action notification sent to admin: $admin_email, Paper ID: {$paper_data['id']}, Action: $action");
                }
            }
            
            return $mail_sent;
            
        } catch (Exception $e) {
            logError("Error sending admin notification: " . $e->getMessage(), 'EMAIL_NOTIFICATION_FAILED');
            return false;
        }
    }
    
    /**
     * Get reviewer statistics for admin dashboard
     */
    public static function getReviewerStats($conn) {
        $sql = "SELECT 
                    COUNT(DISTINCT reviewed_by) as total_reviewers,
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN reviewer_status = 'reviewer_approved' THEN 1 ELSE 0 END) as approved_reviews,
                    SUM(CASE WHEN reviewer_status = 'reviewer_rejected' THEN 1 ELSE 0 END) as rejected_reviews,
                    SUM(CASE WHEN reviewer_status = 'revisions_requested' THEN 1 ELSE 0 END) as revision_requests,
                    SUM(CASE WHEN reviewer_status = 'under_review' THEN 1 ELSE 0 END) as under_review
                FROM paper_submissions 
                WHERE reviewed_by IS NOT NULL AND reviewer_status IS NOT NULL";
        
        $result = $conn->query($sql);
        return $result->fetch_assoc();
    }
    
    /**
     * Get pending reviewer actions for admin dashboard
     */
    public static function getPendingReviewerActions($conn) {
        $sql = "SELECT ps.*, 
                       a.email as reviewer_email
                FROM paper_submissions ps
                LEFT JOIN accounts a ON ps.reviewed_by = a.username
                WHERE ps.reviewer_status IN ('reviewer_approved', 'reviewer_rejected', 'revisions_requested')
                AND ps.status NOT IN ('approved', 'rejected', 'published')
                ORDER BY ps.review_date DESC
                LIMIT 10";
        
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get base URL for email links
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $protocol . $host . $path;
    }
    
    /**
     * Send weekly reviewer summary to admins
     */
    public static function sendWeeklyReviewerSummary($conn) {
        try {
            // Get admin emails
            $admin_sql = "SELECT email FROM accounts WHERE role = 'admin' AND active = 1";
            $admin_result = $conn->query($admin_sql);
            $admin_emails = [];
            
            while ($admin = $admin_result->fetch_assoc()) {
                $admin_emails[] = $admin['email'];
            }
            
            if (empty($admin_emails)) {
                return false;
            }
            
            // Get weekly statistics
            $week_sql = "SELECT 
                            COUNT(*) as total_reviews_this_week,
                            COUNT(DISTINCT reviewed_by) as active_reviewers,
                            SUM(CASE WHEN reviewer_status = 'reviewer_approved' THEN 1 ELSE 0 END) as approved_this_week,
                            SUM(CASE WHEN reviewer_status = 'reviewer_rejected' THEN 1 ELSE 0 END) as rejected_this_week,
                            SUM(CASE WHEN reviewer_status = 'revisions_requested' THEN 1 ELSE 0 END) as revisions_this_week
                        FROM paper_submissions 
                        WHERE review_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
                        AND reviewer_status IS NOT NULL";
            
            $week_result = $conn->query($week_sql);
            $week_stats = $week_result->fetch_assoc();
            
            // Get pending actions
            $pending_actions = self::getPendingReviewerActions($conn);
            
            $subject = "[CNLRRS] Weekly Reviewer Summary - " . date('F j, Y');
            
            $body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #115D5B, #103625); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .footer { background: #115D5B; color: white; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 24px; font-weight: bold; color: #115D5B; }
        .pending-item { background: white; padding: 12px; margin: 8px 0; border-radius: 6px; border-left: 4px solid #f59e0b; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Weekly Reviewer Summary</h2>
            <p>Review activity for the week ending " . date('F j, Y') . "</p>
        </div>
        
        <div class='content'>
            <h3>This Week's Review Activity</h3>
            <div class='stats-grid'>
                <div class='stat-card'>
                    <div class='stat-number'>{$week_stats['total_reviews_this_week']}</div>
                    <div>Total Reviews</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-number'>{$week_stats['active_reviewers']}</div>
                    <div>Active Reviewers</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-number'>{$week_stats['approved_this_week']}</div>
                    <div>Recommended</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-number'>{$week_stats['rejected_this_week']}</div>
                    <div>Not Recommended</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-number'>{$week_stats['revisions_this_week']}</div>
                    <div>Revisions Requested</div>
                </div>
            </div>";
            
            if (!empty($pending_actions)) {
                $body .= "<h3>Pending Admin Actions (" . count($pending_actions) . ")</h3>";
                foreach ($pending_actions as $action) {
                    $action_color = [
                        'reviewer_approved' => '#059669',
                        'reviewer_rejected' => '#dc2626',
                        'revisions_requested' => '#ea580c'
                    ][$action['reviewer_status']] ?? '#6b7280';
                    
                    $body .= "
                    <div class='pending-item'>
                        <div style='display: flex; justify-content: space-between; align-items: start;'>
                            <div>
                                <strong>{$action['paper_title']}</strong><br>
                                <small>by {$action['author_name']} | Reviewed by {$action['reviewed_by']}</small>
                            </div>
                            <div style='background: {$action_color}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;'>
                                " . ucfirst(str_replace(['reviewer_', '_'], ['', ' '], $action['reviewer_status'])) . "
                            </div>
                        </div>
                    </div>";
                }
            } else {
                $body .= "<div style='text-align: center; padding: 20px; color: #6b7280;'>
                            <p>No pending reviewer actions at this time.</p>
                          </div>";
            }
            
            $body .= "
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . self::getBaseUrl() . "/admin_review_papers.php' 
                   style='background: #115D5B; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                    View Admin Dashboard
                </a>
            </div>
        </div>
        
        <div class='footer'>
            <p>&copy; " . date('Y') . " Camarines Norte Lowland Rainfed Research Station</p>
            <p style='font-size: 12px; opacity: 0.8;'>Weekly Reviewer Summary</p>
        </div>
    </div>
</body>
</html>";
            
            // Send to all admins
            $mail_sent = false;
            foreach ($admin_emails as $admin_email) {
                if (EmailService::sendEmail($admin_email, $subject, $body)) {
                    $mail_sent = true;
                }
            }
            
            return $mail_sent;
            
        } catch (Exception $e) {
            logError("Error sending weekly reviewer summary: " . $e->getMessage(), 'EMAIL_WEEKLY_SUMMARY_FAILED');
            return false;
        }
    }
}


// After successful review submission in reviewer_dashboard.php, add this:


