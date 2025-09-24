<?php
/**
 * ReviewerNotifications Class
 * Handles email notifications for paper reviewers and submission updates
 */
class ReviewerNotifications {
    private $conn;
    private $from_email = 'noreply@cnlrrs.gov.ph';
    private $from_name = 'CNLRRS Research Portal';
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Send notification to author when paper status changes
     */
    public function sendStatusUpdateNotification($submission_id, $new_status, $reviewer_comments = '') {
        try {
            // Get submission details
            $stmt = $this->conn->prepare("
                SELECT author_email, author_name, paper_title, reference_number 
                FROM paper_submissions 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $submission_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Submission not found");
            }
            
            $submission = $result->fetch_assoc();
            $stmt->close();
            
            // Get email template
            $template = $this->getEmailTemplate('status_update');
            
            // Replace placeholders
            $subject = str_replace(
                ['{{reference_number}}'],
                [$submission['reference_number']],
                $template['subject']
            );
            
            $body = str_replace(
                [
                    '{{author_name}}',
                    '{{reference_number}}',
                    '{{paper_title}}',
                    '{{new_status}}',
                    '{{update_date}}',
                    '{{reviewer_comments}}'
                ],
                [
                    $submission['author_name'],
                    $submission['reference_number'],
                    $submission['paper_title'],
                    ucfirst(str_replace('_', ' ', $new_status)),
                    date('F j, Y'),
                    $reviewer_comments
                ],
                $template['body']
            );
            
            // Send email
            $this->sendEmail($submission['author_email'], $subject, $body);
            
            // Log email
            $this->logEmail($submission['author_email'], $subject, 'status_update', 'sent');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Status update notification failed: " . $e->getMessage());
            $this->logEmail($submission['author_email'] ?? 'unknown', $subject ?? 'Status Update', 'status_update', 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to reviewer when assigned to review a paper
     */
    public function sendReviewerAssignmentNotification($paper_id, $reviewer_email, $reviewer_name) {
        try {
            // Get paper details
            $stmt = $this->conn->prepare("
                SELECT paper_title, reference_number, author_name 
                FROM paper_submissions 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $paper_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Paper not found");
            }
            
            $paper = $result->fetch_assoc();
            $stmt->close();
            
            $subject = "Paper Review Assignment - {$paper['reference_number']}";
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #115D5B;'>Paper Review Assignment</h2>
                    
                    <p>Dear {$reviewer_name},</p>
                    
                    <p>You have been assigned to review a research paper submitted to CNLRRS.</p>
                    
                    <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #115D5B; margin: 20px 0;'>
                        <h3 style='margin: 0 0 10px 0; color: #115D5B;'>Paper Details</h3>
                        <p><strong>Title:</strong> {$paper['paper_title']}</p>
                        <p><strong>Reference Number:</strong> {$paper['reference_number']}</p>
                        <p><strong>Author:</strong> {$paper['author_name']}</p>
                    </div>
                    
                    <p>Please log into the reviewer portal to access the paper and submit your review.</p>
                    
                    <p>Best regards,<br>
                    <strong>CNLRRS Admin Team</strong></p>
                </div>
            </body>
            </html>
            ";
            
            $this->sendEmail($reviewer_email, $subject, $body);
            $this->logEmail($reviewer_email, $subject, 'reviewer_assignment', 'sent');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Reviewer assignment notification failed: " . $e->getMessage());
            $this->logEmail($reviewer_email, $subject ?? 'Reviewer Assignment', 'reviewer_assignment', 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send reminder notification to reviewer
     */
    public function sendReviewerReminder($paper_id, $reviewer_email, $reviewer_name, $days_overdue = 0) {
        try {
            // Get paper details
            $stmt = $this->conn->prepare("
                SELECT paper_title, reference_number 
                FROM paper_submissions 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $paper_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Paper not found");
            }
            
            $paper = $result->fetch_assoc();
            $stmt->close();
            
            $subject = "Review Reminder - {$paper['reference_number']}";
            $urgency_message = $days_overdue > 0 ? 
                "<p style='color: #dc3545;'><strong>This review is {$days_overdue} days overdue.</strong></p>" : 
                "<p>This is a friendly reminder about your pending review.</p>";
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #115D5B;'>Review Reminder</h2>
                    
                    <p>Dear {$reviewer_name},</p>
                    
                    {$urgency_message}
                    
                    <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #115D5B; margin: 20px 0;'>
                        <p><strong>Paper Title:</strong> {$paper['paper_title']}</p>
                        <p><strong>Reference Number:</strong> {$paper['reference_number']}</p>
                    </div>
                    
                    <p>Please complete your review as soon as possible. If you need an extension or cannot complete the review, please contact us immediately.</p>
                    
                    <p>Best regards,<br>
                    <strong>CNLRRS Admin Team</strong></p>
                </div>
            </body>
            </html>
            ";
            
            $this->sendEmail($reviewer_email, $subject, $body);
            $this->logEmail($reviewer_email, $subject, 'reviewer_reminder', 'sent');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Reviewer reminder notification failed: " . $e->getMessage());
            $this->logEmail($reviewer_email, $subject ?? 'Review Reminder', 'reviewer_reminder', 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send batch notifications for multiple recipients
     */
    public function sendBatchNotifications($recipients, $subject, $body_template, $template_data = []) {
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($recipients as $recipient) {
            try {
                // Replace placeholders specific to this recipient
                $personalized_body = $body_template;
                foreach ($template_data as $key => $value) {
                    if (isset($recipient[$key])) {
                        $personalized_body = str_replace("{{{$key}}}", $recipient[$key], $personalized_body);
                    }
                }
                
                $this->sendEmail($recipient['email'], $subject, $personalized_body);
                $this->logEmail($recipient['email'], $subject, 'batch_notification', 'sent');
                $sent_count++;
                
                // Add small delay to prevent overwhelming the mail server
                usleep(100000); // 0.1 second delay
                
            } catch (Exception $e) {
                error_log("Batch notification failed for {$recipient['email']}: " . $e->getMessage());
                $this->logEmail($recipient['email'], $subject, 'batch_notification', 'failed', $e->getMessage());
                $failed_count++;
            }
        }
        
        return ['sent' => $sent_count, 'failed' => $failed_count];
    }
    
    /**
     * Get email template from database
     */
    private function getEmailTemplate($template_type) {
        $stmt = $this->conn->prepare("
            SELECT subject, body 
            FROM email_templates 
            WHERE template_type = ? AND is_active = 1 
            LIMIT 1
        ");
        $stmt->bind_param("s", $template_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // Return default template if not found
        return [
            'subject' => 'CNLRRS Notification',
            'body' => '<p>This is an automated notification from CNLRRS Research Portal.</p>'
        ];
    }
    
    /**
     * Send email using PHP mail function
     */
    private function sendEmail($to, $subject, $body) {
        $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=UTF-8",
            "From: {$this->from_name} <{$this->from_email}>",
            "Reply-To: research@cnlrrs.gov.ph",
            "X-Mailer: PHP/" . phpversion()
        ];
        
        $success = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if (!$success) {
            throw new Exception("Failed to send email to {$to}");
        }
        
        return true;
    }
    
    /**
     * Log email activity
     */
    private function logEmail($recipient, $subject, $template_type, $status, $error_message = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO email_logs (recipient, subject, template_type, status, error_message, sent_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sssss", $recipient, $subject, $template_type, $status, $error_message);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats($days = 30) {
        $stmt = $this->conn->prepare("
            SELECT 
                template_type,
                status,
                COUNT(*) as count
            FROM email_logs 
            WHERE sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY template_type, status
            ORDER BY template_type, status
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        $stmt->close();
        return $stats;
    }
    
    /**
     * Get pending review reminders
     */
    public function getPendingReviewReminders($days_threshold = 7) {
        $stmt = $this->conn->prepare("
            SELECT 
                pr.id,
                pr.paper_id,
                pr.reviewer_name,
                pr.reviewer_email,
                ps.reference_number,
                ps.paper_title,
                DATEDIFF(NOW(), pr.assigned_date) as days_pending
            FROM paper_reviewers pr
            JOIN paper_submissions ps ON pr.paper_id = ps.id
            WHERE pr.review_status = 'assigned' 
            AND DATEDIFF(NOW(), pr.assigned_date) >= ?
            ORDER BY days_pending DESC
        ");
        $stmt->bind_param("i", $days_threshold);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reminders = [];
        while ($row = $result->fetch_assoc()) {
            $reminders[] = $row;
        }
        
        $stmt->close();
        return $reminders;
    }
    
    /**
     * Get reviewer statistics and metrics
     */
    public function getReviewerStats($days = 30) {
        try {
            $stats = [
                'total_reviewers' => 0,
                'active_reviewers' => 0,
                'pending_reviews' => 0,
                'completed_reviews' => 0,
                'overdue_reviews' => 0,
                'average_review_time' => 0,
                'reviewer_performance' => [],
                'recent_activity' => []
            ];
            
            // Get total number of unique reviewers
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT reviewer_email) as total_reviewers
                FROM paper_reviewers
                WHERE assigned_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stats['total_reviewers'] = (int)$row['total_reviewers'];
            }
            $stmt->close();
            
            // Get active reviewers (those with pending or in-progress reviews)
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT reviewer_email) as active_reviewers
                FROM paper_reviewers
                WHERE review_status IN ('assigned', 'in_progress')
                AND assigned_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stats['active_reviewers'] = (int)$row['active_reviewers'];
            }
            $stmt->close();
            
            // Get pending reviews count
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as pending_reviews
                FROM paper_reviewers
                WHERE review_status IN ('assigned', 'in_progress')
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stats['pending_reviews'] = (int)$row['pending_reviews'];
            }
            $stmt->close();
            
            // Get completed reviews count
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as completed_reviews
                FROM paper_reviewers
                WHERE review_status = 'completed'
                AND completed_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stats['completed_reviews'] = (int)$row['completed_reviews'];
            }
            $stmt->close();
            
            // Get overdue reviews (assigned more than 14 days ago)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as overdue_reviews
                FROM paper_reviewers
                WHERE review_status IN ('assigned', 'in_progress')
                AND DATEDIFF(NOW(), assigned_date) > 14
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stats['overdue_reviews'] = (int)$row['overdue_reviews'];
            }
            $stmt->close();
            
            // Get average review time for completed reviews
            $stmt = $this->conn->prepare("
                SELECT AVG(DATEDIFF(completed_date, assigned_date)) as avg_review_time
                FROM paper_reviewers
                WHERE review_status = 'completed'
                AND completed_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND completed_date IS NOT NULL
            ");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stats['average_review_time'] = round((float)$row['avg_review_time'], 1);
            }
            $stmt->close();
            
            // Get reviewer performance data
            $stmt = $this->conn->prepare("
                SELECT 
                    reviewer_name,
                    reviewer_email,
                    COUNT(*) as total_assigned,
                    COUNT(CASE WHEN review_status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN review_status IN ('assigned', 'in_progress') THEN 1 END) as pending,
                    AVG(CASE WHEN review_status = 'completed' AND completed_date IS NOT NULL 
                        THEN DATEDIFF(completed_date, assigned_date) END) as avg_time
                FROM paper_reviewers
                WHERE assigned_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY reviewer_email, reviewer_name
                ORDER BY total_assigned DESC
                LIMIT 10
            ");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $stats['reviewer_performance'][] = [
                    'name' => $row['reviewer_name'],
                    'email' => $row['reviewer_email'],
                    'total_assigned' => (int)$row['total_assigned'],
                    'completed' => (int)$row['completed'],
                    'pending' => (int)$row['pending'],
                    'avg_time' => round((float)$row['avg_time'], 1),
                    'completion_rate' => $row['total_assigned'] > 0 ? 
                        round(($row['completed'] / $row['total_assigned']) * 100, 1) : 0
                ];
            }
            $stmt->close();
            
            // Get recent activity
            $stmt = $this->conn->prepare("
                SELECT 
                    pr.reviewer_name,
                    ps.paper_title,
                    ps.reference_number,
                    pr.review_status,
                    pr.assigned_date,
                    pr.completed_date
                FROM paper_reviewers pr
                JOIN paper_submissions ps ON pr.paper_id = ps.id
                WHERE pr.assigned_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                   OR pr.completed_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY COALESCE(pr.completed_date, pr.assigned_date) DESC
                LIMIT 10
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $stats['recent_activity'][] = [
                    'reviewer_name' => $row['reviewer_name'],
                    'paper_title' => $row['paper_title'],
                    'reference_number' => $row['reference_number'],
                    'status' => $row['review_status'],
                    'assigned_date' => $row['assigned_date'],
                    'completed_date' => $row['completed_date']
                ];
            }
            $stmt->close();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting reviewer stats: " . $e->getMessage());
            return [
                'total_reviewers' => 0,
                'active_reviewers' => 0,
                'pending_reviews' => 0,
                'completed_reviews' => 0,
                'overdue_reviews' => 0,
                'average_review_time' => 0,
                'reviewer_performance' => [],
                'recent_activity' => []
            ];
        }
    }
    
    /**
     * Get submission statistics for dashboard
     */
    public function getSubmissionStats($days = 30) {
        try {
            $stats = [
                'total_submissions' => 0,
                'pending_submissions' => 0,
                'under_review' => 0,
                'accepted_submissions' => 0,
                'rejected_submissions' => 0,
                'recent_submissions' => []
            ];
            
            // Get total submissions in the specified period
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM paper_submissions
                WHERE submission_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stats['total_submissions'] = (int)$row['total'];
            }
            $stmt->close();
            
            // Get submissions by status
            $stmt = $this->conn->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM paper_submissions
                WHERE submission_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY status
            ");
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                switch ($row['status']) {
                    case 'pending':
                        $stats['pending_submissions'] = (int)$row['count'];
                        break;
                    case 'under_review':
                        $stats['under_review'] = (int)$row['count'];
                        break;
                    case 'accepted':
                        $stats['accepted_submissions'] = (int)$row['count'];
                        break;
                    case 'rejected':
                        $stats['rejected_submissions'] = (int)$row['count'];
                        break;
                }
            }
            $stmt->close();
            
            // Get recent submissions
            $stmt = $this->conn->prepare("
                SELECT 
                    reference_number,
                    paper_title,
                    author_name,
                    status,
                    submission_date
                FROM paper_submissions
                ORDER BY submission_date DESC
                LIMIT 10
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $stats['recent_submissions'][] = [
                    'reference_number' => $row['reference_number'],
                    'paper_title' => $row['paper_title'],
                    'author_name' => $row['author_name'],
                    'status' => $row['status'],
                    'submission_date' => $row['submission_date']
                ];
            }
            $stmt->close();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting submission stats: " . $e->getMessage());
            return [
                'total_submissions' => 0,
                'pending_submissions' => 0,
                'under_review' => 0,
                'accepted_submissions' => 0,
                'rejected_submissions' => 0,
                'recent_submissions' => []
            ];
        }
    }
}
?>