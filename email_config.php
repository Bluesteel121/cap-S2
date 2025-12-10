<?php
// email_config.php - Enhanced Email Configuration with Admin Notification Features

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'elibrarycnlrrs@gmail.com');
define('SMTP_PASSWORD', 'okbl exhm tlxz mjkw');
define('FROM_EMAIL', 'elibrarycnlrrs@gmail.com');
define('FROM_NAME', 'CNLRRS E-Library');
define('MAX_EMAIL_RETRIES', 3);
define('EMAIL_RETRY_DELAY', 2);

/**
 * Enhanced Email utility class with admin notification support
 */
class EmailService {
    
    /**
     * Send email with retry mechanism
     */
    public static function sendEmail($to, $subject, $body, $isHTML = true, $retries = MAX_EMAIL_RETRIES) {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::logEmailError("Invalid email address: $to", $subject);
            return false;
        }

        if (!self::loadPHPMailer()) {
            self::logEmailError("PHPMailer could not be loaded", $subject);
            return false;
        }
        
        $attempt = 0;
        $lastError = '';
        
        while ($attempt < $retries) {
            $attempt++;
            
            try {
                $mail = new PHPMailer(true);
                
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                $mail->Timeout = 30;
                $mail->SMTPKeepAlive = true;
                $mail->SMTPDebug = 0;
                
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                $mail->setFrom(FROM_EMAIL, FROM_NAME);
                $mail->addAddress($to);
                $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
                $mail->isHTML($isHTML);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->CharSet = 'UTF-8';
                
                if ($isHTML) {
                    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
                }
                
                $success = $mail->send();
                
                if ($success) {
                    self::logEmailSuccess($to, $subject, $attempt);
                    return true;
                }
                
            } catch (Exception $e) {
                $lastError = $mail->ErrorInfo;
                self::logEmailAttempt($to, $subject, $attempt, $retries, $lastError);
                
                if ($attempt < $retries) {
                    sleep(EMAIL_RETRY_DELAY);
                }
            }
        }
        
        self::logEmailError("All $retries attempts failed. Last error: $lastError", $subject, $to);
        self::queueFailedEmail($to, $subject, $body, $isHTML);
        
        return false;
    }
    
    /**
     * Send admin notification for paper submissions and revisions
     */
    public static function sendAdminNotification($paperData, $notificationType, $conn, $testEmail = null) {
        // Get notification settings
        $sql = "SELECT * FROM notification_settings WHERE id = 1";
        $result = $conn->query($sql);
        $settings = $result->fetch_assoc();
        
        if (!$settings) {
            error_log("Admin notification settings not configured");
            return false;
        }
        
        // Check if notifications are enabled for this type
        if ($notificationType === 'submission' && !$settings['notify_on_submission']) {
            return false;
        }
        if ($notificationType === 'revision' && !$settings['notify_on_revision']) {
            return false;
        }
        
        // Get email addresses
        $emails = $testEmail ? [$testEmail] : array_map('trim', explode(',', $settings['notification_emails']));
        $emails = array_filter($emails, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        
        if (empty($emails)) {
            error_log("No valid admin notification emails configured");
            return false;
        }
        
        // Prepare email content based on notification type
        if ($notificationType === 'submission') {
            $subject = "[NEW SUBMISSION] {$paperData['paper_title']}";
            $body = self::getAdminSubmissionTemplate($paperData);
        } else if ($notificationType === 'revision') {
            $subject = "[REVISION SUBMITTED] {$paperData['paper_title']}";
            $body = self::getAdminRevisionTemplate($paperData);
        } else {
            return false;
        }
        
        // Send to all admin emails
        $success = true;
        foreach ($emails as $email) {
            $result = self::sendEmail($email, $subject, $body);
            if (!$result) {
                $success = false;
                error_log("Failed to send admin notification to: $email");
            }
        }
        
        // Log the notification attempt
        if (function_exists('logActivity')) {
            $emailList = implode(', ', $emails);
            logActivity('ADMIN_NOTIFICATION_SENT', "Type: $notificationType, Paper: {$paperData['paper_title']}, To: $emailList");
        }
        
        return $success;
    }
    
    /**
     * Get admin notification template for new submissions
     */
    private static function getAdminSubmissionTemplate($paperData) {
        $paper_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                     "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                     "/admin_manage_papers.php";
        
        return '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="margin: 0; font-size: 24px;">
                        <span style="font-size: 30px;">📄</span><br>
                        New Paper Submission
                    </h1>
                </div>
                
                <div style="background-color: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
                    <div style="background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h2 style="color: #667eea; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                            Submission Details
                        </h2>
                        
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057; width: 40%;">
                                    Paper Title:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['paper_title'] ?? 'Untitled') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057;">
                                    Author:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['author_name'] ?? 'Unknown') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057;">
                                    Research Type:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['research_type'] ?? 'Not specified') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057;">
                                    Submitted By:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['submitted_by'] ?? 'Unknown') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; font-weight: bold; color: #495057;">
                                    Submission Date:
                                </td>
                                <td style="padding: 12px 0; color: #212529;">
                                    ' . date('F j, Y \a\t g:i A') . '
                                </td>
                            </tr>
                        </table>
                        
                        <div style="margin-top: 30px; text-align: center;">
                            <a href="' . $paper_url . '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.4);">
                                Review Paper →
                            </a>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 4px;">
                        <p style="margin: 0; color: #0d47a1; font-size: 14px;">
                            <strong>⏰ Action Required:</strong> This paper is awaiting your review. Please log in to the admin panel to review and approve or request revisions.
                        </p>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center; color: #6c757d; font-size: 12px;">
                        <p>This is an automated notification from CNLRRS E-Library System</p>
                        <p style="margin-top: 5px;">
                            To manage notification settings, visit the 
                            <a href="' . dirname($paper_url) . '/admin_email_templates.php" style="color: #667eea;">Email Templates</a> page
                        </p>
                    </div>
                </div>
            </div>
        ';
    }
    
    /**
     * Get admin notification template for revisions
     */
    private static function getAdminRevisionTemplate($paperData) {
        $paper_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                     "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                     "/admin_manage_papers.php";
        
        $revisionNumber = $paperData['revision_count'] ?? 1;
        
        return '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="margin: 0; font-size: 24px;">
                        <span style="font-size: 30px;">🔄</span><br>
                        Paper Revision Submitted
                    </h1>
                </div>
                
                <div style="background-color: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
                    <div style="background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                            <p style="margin: 0; color: #856404; font-weight: bold;">
                                📝 Revision #' . $revisionNumber . '
                            </p>
                        </div>
                        
                        <h2 style="color: #f5576c; margin-top: 0; border-bottom: 2px solid #f5576c; padding-bottom: 10px;">
                            Revision Details
                        </h2>
                        
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057; width: 40%;">
                                    Paper Title:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['paper_title'] ?? 'Untitled') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057;">
                                    Author:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['author_name'] ?? 'Unknown') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057;">
                                    Research Type:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['research_type'] ?? 'Not specified') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057;">
                                    Revision Number:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    #' . $revisionNumber . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; font-weight: bold; color: #495057;">
                                    Previous Reviewer:
                                </td>
                                <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; color: #212529;">
                                    ' . htmlspecialchars($paperData['reviewed_by'] ?? 'N/A') . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 0; font-weight: bold; color: #495057;">
                                    Revision Submitted:
                                </td>
                                <td style="padding: 12px 0; color: #212529;">
                                    ' . date('F j, Y \a\t g:i A') . '
                                </td>
                            </tr>
                        </table>
                        
                        ' . (isset($paperData['revision_notes']) && !empty($paperData['revision_notes']) ? '
                        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
                            <h4 style="margin-top: 0; color: #495057;">Author\'s Revision Notes:</h4>
                            <p style="color: #212529; white-space: pre-wrap;">' . htmlspecialchars($paperData['revision_notes']) . '</p>
                        </div>
                        ' : '') . '
                        
                        <div style="margin-top: 30px; text-align: center;">
                            <a href="' . $paper_url . '" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(245, 87, 108, 0.4);">
                                Review Revision →
                            </a>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                        <p style="margin: 0; color: #856404; font-size: 14px;">
                            <strong>⏰ Action Required:</strong> The author has submitted a revised version based on your feedback. Please review the changes.
                        </p>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center; color: #6c757d; font-size: 12px;">
                        <p>This is an automated notification from CNLRRS E-Library System</p>
                        <p style="margin-top: 5px;">
                            To manage notification settings, visit the 
                            <a href="' . dirname($paper_url) . '/admin_email_templates.php" style="color: #f5576c;">Email Templates</a> page
                        </p>
                    </div>
                </div>
            </div>
        ';
    }
    
    /**
     * Load PHPMailer library
     */
    private static function loadPHPMailer() {
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return true;
        }
        
        $phpmailer_paths = [
            __DIR__ . '/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
        ];
        
        foreach ($phpmailer_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    return true;
                }
            }
        }
        
        $individual_files = [
            __DIR__ . '/PHPMailer/src/PHPMailer.php',
            __DIR__ . '/PHPMailer/src/Exception.php',
            __DIR__ . '/PHPMailer/src/SMTP.php'
        ];
        
        $all_exist = true;
        foreach ($individual_files as $file) {
            if (!file_exists($file)) {
                $all_exist = false;
                break;
            }
        }
        
        if ($all_exist) {
            foreach ($individual_files as $file) {
                require_once $file;
            }
            return class_exists('PHPMailer\PHPMailer\PHPMailer');
        }
        
        return false;
    }
    
    /**
     * Log successful email
     */
    private static function logEmailSuccess($to, $subject, $attempt) {
        global $conn;
        
        $log_message = "Email sent successfully to $to | Subject: $subject | Attempt: $attempt";
        error_log($log_message);
        
        if (function_exists('logActivity')) {
            logActivity('EMAIL_SENT_SUCCESS', $log_message);
        }
        
        if (isset($conn) && $conn) {
            self::logToDatabase($conn, 'success', $to, $subject, "Sent on attempt $attempt", null);
        }
    }
    
    /**
     * Log email attempt
     */
    private static function logEmailAttempt($to, $subject, $attempt, $maxAttempts, $error) {
        $log_message = "Email attempt $attempt/$maxAttempts failed to $to | Subject: $subject | Error: $error";
        error_log($log_message);
        
        if (function_exists('logActivity')) {
            logActivity('EMAIL_ATTEMPT_FAILED', $log_message);
        }
    }
    
    /**
     * Log email error
     */
    private static function logEmailError($error, $subject, $to = '') {
        $log_message = "Email error: $error | Subject: $subject" . ($to ? " | To: $to" : "");
        error_log($log_message);
        
        if (function_exists('logError')) {
            logError($log_message, 'EMAIL_SEND_ERROR');
        }
        
        if (function_exists('logActivity')) {
            logActivity('EMAIL_SEND_FAILED', $log_message);
        }
    }
    
    /**
     * Queue failed email for later retry
     */
    private static function queueFailedEmail($to, $subject, $body, $isHTML) {
        global $conn;
        
        if (!isset($conn) || !$conn) {
            return;
        }
        
        $create_table = "CREATE TABLE IF NOT EXISTS failed_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            is_html BOOLEAN DEFAULT TRUE,
            retry_count INT DEFAULT 0,
            max_retries INT DEFAULT 5,
            last_error TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_retry_at TIMESTAMP NULL,
            status ENUM('pending', 'retrying', 'failed', 'sent') DEFAULT 'pending',
            INDEX(status),
            INDEX(created_at)
        )";
        
        $conn->query($create_table);
        
        $sql = "INSERT INTO failed_emails (recipient_email, subject, body, is_html, last_error) 
                VALUES (?, ?, ?, ?, 'Initial send failed after multiple attempts')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $to, $subject, $body, $isHTML);
        $stmt->execute();
        
        error_log("Failed email queued for retry: To=$to, Subject=$subject");
    }
    
    /**
     * Log email to database
     */
    private static function logToDatabase($conn, $status, $to, $subject, $message, $error) {
        $create_table = "CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            status VARCHAR(20) NOT NULL,
            recipient_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(status),
            INDEX(recipient_email),
            INDEX(created_at)
        )";
        
        $conn->query($create_table);
        
        $sql = "INSERT INTO email_logs (status, recipient_email, subject, message, error_message) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $status, $to, $subject, $message, $error);
        $stmt->execute();
    }
    
    /**
     * Send paper review notification
     */
    public static function sendPaperReviewNotification($paperData, $userEmail, $status, $conn) {
        if (empty($userEmail)) {
            self::logEmailError("No email address provided for paper review notification", "Paper Review: " . $paperData['paper_title']);
            return false;
        }
        
        $template_type = 'paper_' . $status;
        $template = self::getEmailTemplate($template_type, $conn);
        
        if (!$template) {
            error_log("Email template '$template_type' not found, using default");
            $template = self::getDefaultTemplate($template_type);
        }
        
        if (!$template) {
            self::logEmailError("No template available for '$template_type'", "Paper Review");
            return false;
        }
        
        $variables = [
            'author_name' => $paperData['author_name'] ?? 'Author',
            'paper_title' => $paperData['paper_title'] ?? 'Untitled',
            'research_type' => $paperData['research_type'] ?? 'Research',
            'review_date' => $paperData['review_date'] ?? date('F j, Y'),
            'reviewed_by' => $paperData['reviewed_by'] ?? 'Admin',
            'reviewer_comments' => $paperData['reviewer_comments'] ?? 'No comments provided',
            'submission_date' => date('F j, Y'),
            'revision_number' => $paperData['revision_count'] ?? 1,
            'paper_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                          "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                          "/my_submissions.php"
        ];
        
        $email = self::replaceTemplateVariables($template, $variables);
        $result = self::sendEmail($userEmail, $email['subject'], $email['body']);
        
        self::logToDatabase($conn, $result ? 'sent' : 'failed', $userEmail, $email['subject'], 
                           "Paper review notification: $status", $result ? null : 'Send failed');
        
        return $result;
    }
    
    /**
     * Send paper submission notification
     */
    public static function sendPaperSubmissionNotification($paperData, $userEmail, $conn) {
        if (empty($userEmail)) {
            self::logEmailError("No email address provided for submission notification", "Paper Submission: " . $paperData['paper_title']);
            return false;
        }
        
        $template = self::getEmailTemplate('paper_submitted', $conn);
        
        if (!$template) {
            $template = self::getDefaultTemplate('paper_submitted');
        }
        
        $variables = [
            'author_name' => $paperData['author_name'] ?? 'Author',
            'paper_title' => $paperData['paper_title'] ?? 'Untitled',
            'research_type' => $paperData['research_type'] ?? 'Research',
            'submission_date' => date('F j, Y'),
            'submitted_by' => $paperData['user_name'] ?? 'User'
        ];
        
        $email = self::replaceTemplateVariables($template, $variables);
        $result = self::sendEmail($userEmail, $email['subject'], $email['body']);
        
        self::logToDatabase($conn, $result ? 'sent' : 'failed', $userEmail, $email['subject'], 
                           "Paper submission notification", $result ? null : 'Send failed');
        
        return $result;
    }
    
    // Template methods
    public static function getEmailTemplate($template_type, $conn) {
        $sql = "SELECT * FROM email_templates WHERE template_type = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $template_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row;
        }
        
        return self::getDefaultTemplate($template_type);
    }
    
    public static function getDefaultTemplate($template_type) {
        $templates = [
            'paper_submitted' => [
                'subject' => 'Paper Submission Received - {{paper_title}}',
                'body' => '<h2>Paper Submission Confirmation</h2><p>Dear {{author_name}},</p><p>Thank you for submitting your research paper.</p>'
            ],
            'paper_approved' => [
                'subject' => 'Paper Approved - {{paper_title}}',
                'body' => '<h2>Paper Approved</h2><p>Dear {{author_name}},</p><p>Your paper has been approved!</p>'
            ],
            'paper_revision_requested' => [
                'subject' => 'Revision Required - {{paper_title}}',
                'body' => '<h2>Paper Revision Requested</h2><p>Dear {{author_name}},</p><p>Your paper requires revision.</p>'
            ],
            'paper_revision_submitted' => [
                'subject' => 'Revision Received - {{paper_title}}',
                'body' => '<h2>Revision Successfully Submitted</h2><p>Dear {{author_name}},</p><p>We have received your revised paper.</p>'
            ],
            'paper_under_review' => [
                'subject' => 'Paper Under Review - {{paper_title}}',
                'body' => '<h2>Paper Under Review</h2><p>Dear {{author_name}},</p><p>Your paper is under review.</p>'
            ]
        ];
        
        return $templates[$template_type] ?? null;
    }
    
    public static function replaceTemplateVariables($template, $variables) {
        $subject = $template['subject'];
        $body = $template['body'];
        
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, htmlspecialchars($value), $subject);
            $body = str_replace($placeholder, htmlspecialchars($value), $body);
        }
        
        return ['subject' => $subject, 'body' => $body];
    }
}

// Helper functions
function getUserEmail($username, $conn) {
    $sql = "SELECT email FROM accounts WHERE name = ? OR username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['email'];
    }
    
    return null;
}

function createEmailTemplatesTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_type VARCHAR(50) NOT NULL UNIQUE,
        template_name VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by VARCHAR(50),
        updated_by VARCHAR(50)
    )";
    
    return $conn->query($sql);
}

function createNotificationSettingsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS notification_settings (
        id INT PRIMARY KEY DEFAULT 1,
        notification_emails TEXT,
        notify_on_submission BOOLEAN DEFAULT TRUE,
        notify_on_revision BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by VARCHAR(50)
    )";
    
    return $conn->query($sql);
}

function initializeDefaultEmailTemplates($conn) {
    createEmailTemplatesTable($conn);
    
    $defaultTemplates = [
        [
            'template_type' => 'paper_submitted',
            'template_name' => 'Paper Submission Confirmation',
            'subject' => 'Paper Submission Received - {{paper_title}}',
            'body' => '<h2>Paper Submission Confirmation</h2><p>Dear {{author_name}},</p><p>Thank you for submitting your research paper.</p>'
        ],
        [
            'template_type' => 'paper_approved',
            'template_name' => 'Paper Approved',
            'subject' => 'Paper Approved - {{paper_title}}',
            'body' => '<h2>Paper Approved</h2><p>Dear {{author_name}},</p><p>Your paper has been approved!</p>'
        ],
        [
            'template_type' => 'paper_revision_requested',
            'template_name' => 'Paper Revision Required',
            'subject' => 'Revision Required - {{paper_title}}',
            'body' => '<h2>Paper Revision Requested</h2><p>Dear {{author_name}},</p><p>Your paper requires revision.</p>'
        ],
        [
            'template_type' => 'paper_revision_submitted',
            'template_name' => 'Revision Submitted Confirmation',
            'subject' => 'Revision Received - {{paper_title}}',
            'body' => '<h2>Revision Successfully Submitted</h2><p>Dear {{author_name}},</p><p>We have received your revised paper.</p>'
        ],
        [
            'template_type' => 'paper_under_review',
            'template_name' => 'Paper Under Review',
            'subject' => 'Paper Under Review - {{paper_title}}',
            'body' => '<h2>Paper Under Review</h2><p>Dear {{author_name}},</p><p>Your paper is under review.</p>'
        ]
    ];
    
    foreach ($defaultTemplates as $template) {
        $sql = "INSERT IGNORE INTO email_templates (template_type, template_name, subject, body) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", 
            $template['template_type'],
            $template['template_name'], 
            $template['subject'], 
            $template['body']
        );
        $stmt->execute();
    }
}
?>