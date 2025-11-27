<?php
// email_config.php - Enhanced Email Configuration with Reliability Features

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
define('EMAIL_RETRY_DELAY', 2); // seconds

/**
 * Enhanced Email utility class with reliability features
 */
class EmailService {
    
    /**
     * Send email with retry mechanism and comprehensive logging
     */
    public static function sendEmail($to, $subject, $body, $isHTML = true, $retries = MAX_EMAIL_RETRIES) {
        // Validate email address
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::logEmailError("Invalid email address: $to", $subject);
            return false;
        }

        // Load PHPMailer
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
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                $mail->Timeout = 30; // Increase timeout
                $mail->SMTPKeepAlive = true; // Keep connection alive
                
                // Enable debug output for troubleshooting (set to 0 in production)
                $mail->SMTPDebug = 0; // 0 = off, 1 = client, 2 = client and server
                $mail->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug Level $level: $str");
                };
                
                // SSL options
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // Recipients
                $mail->setFrom(FROM_EMAIL, FROM_NAME);
                $mail->addAddress($to);
                $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
                
                // Content
                $mail->isHTML($isHTML);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->CharSet = 'UTF-8';
                
                // Plain text alternative
                if ($isHTML) {
                    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
                }
                
                // Send email
                $success = $mail->send();
                
                if ($success) {
                    self::logEmailSuccess($to, $subject, $attempt);
                    return true;
                }
                
            } catch (Exception $e) {
                $lastError = $mail->ErrorInfo;
                self::logEmailAttempt($to, $subject, $attempt, $retries, $lastError);
                
                // If not the last attempt, wait before retrying
                if ($attempt < $retries) {
                    sleep(EMAIL_RETRY_DELAY);
                }
            }
        }
        
        // All attempts failed
        self::logEmailError("All $retries attempts failed. Last error: $lastError", $subject, $to);
        
        // Queue email for later retry
        self::queueFailedEmail($to, $subject, $body, $isHTML);
        
        return false;
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
        
        // Try loading individual files
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
        
        // Log to database
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
        
        // Create failed_emails table if it doesn't exist
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
        
        // Insert failed email
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
        // Create email_logs table if it doesn't exist
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
        
        // Insert log
        $sql = "INSERT INTO email_logs (status, recipient_email, subject, message, error_message) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $status, $to, $subject, $message, $error);
        $stmt->execute();
    }
    
    /**
     * Process queued emails (run via cron job)
     */
    public static function processQueuedEmails($conn) {
        $sql = "SELECT * FROM failed_emails 
                WHERE status IN ('pending', 'retrying') 
                AND retry_count < max_retries 
                ORDER BY created_at ASC 
                LIMIT 10";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            return;
        }
        
        while ($row = $result->fetch_assoc()) {
            $success = self::sendEmail(
                $row['recipient_email'],
                $row['subject'],
                $row['body'],
                (bool)$row['is_html'],
                1 // Single retry per cron run
            );
            
            // Update queue status
            if ($success) {
                $update_sql = "UPDATE failed_emails SET status = 'sent', last_retry_at = NOW() WHERE id = ?";
            } else {
                $retry_count = $row['retry_count'] + 1;
                $new_status = ($retry_count >= $row['max_retries']) ? 'failed' : 'retrying';
                $update_sql = "UPDATE failed_emails SET 
                               retry_count = $retry_count, 
                               status = '$new_status', 
                               last_retry_at = NOW() 
                               WHERE id = ?";
            }
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
        }
    }
    
    /**
     * Send paper review notification with guaranteed delivery
     */
    public static function sendPaperReviewNotification($paperData, $userEmail, $status, $conn) {
        // Validate inputs
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
            'paper_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                          "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                          "/view_paper.php?id=" . $paperData['id']
        ];
        
        $email = self::replaceTemplateVariables($template, $variables);
        
        // Send with retry mechanism
        $result = self::sendEmail($userEmail, $email['subject'], $email['body']);
        
        // Log to database regardless of success
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
    
    // Keep all the existing template methods (getEmailTemplate, getDefaultTemplate, replaceTemplateVariables)
    // ... (rest of the code from original file)
    
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
                'body' => '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                        <h2 style="color: #115D5B;">Paper Submission Confirmation</h2>
                        <p>Dear {{author_name}},</p>
                        <p>Thank you for submitting your research paper to the Camarines Norte Lowland Rainfed Research Station.</p>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <h3 style="color: #115D5B;">Submission Details:</h3>
                            <p><strong>Title:</strong> {{paper_title}}</p>
                            <p><strong>Research Type:</strong> {{research_type}}</p>
                            <p><strong>Submission Date:</strong> {{submission_date}}</p>
                            <p><strong>Submitted by:</strong> {{submitted_by}}</p>
                        </div>
                        
                        <p>Your paper is now under review. Our review process typically takes 5-10 business days. You will receive an email notification once the review is complete.</p>
                        
                        <p>If you have any questions, please contact us at dacnlrrs@gmail.com</p>
                        
                        <p>Best regards,<br>CNLRRS Review Team</p>
                    </div>
                '
            ],
            'paper_approved' => [
                'subject' => 'Paper Approved - {{paper_title}}',
                'body' => '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                        <h2 style="color: #28a745;">Paper Approval Notification</h2>
                        <p>Dear {{author_name}},</p>
                        <p>Congratulations! Your research paper has been approved for publication.</p>
                        
                        <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
                            <h3 style="color: #155724;">Approved Paper Details:</h3>
                            <p><strong>Title:</strong> {{paper_title}}</p>
                            <p><strong>Research Type:</strong> {{research_type}}</p>
                            <p><strong>Review Date:</strong> {{review_date}}</p>
                            <p><strong>Reviewed by:</strong> {{reviewed_by}}</p>
                        </div>
                        
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <h4>Reviewer Comments:</h4>
                            <p>{{reviewer_comments}}</p>
                        </div>
                        
                        <p>Your paper will now be published on our research platform. You will receive another notification when it becomes publicly available.</p>
                        
                        <p>Thank you for contributing to agricultural research!</p>
                        
                        <p>Best regards,<br>CNLRRS Review Team</p>
                    </div>
                '
            ],
            'paper_rejected' => [
                'subject' => 'Paper Review Update - {{paper_title}}',
                'body' => '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                        <h2 style="color: #dc3545;">Paper Review Update</h2>
                        <p>Dear {{author_name}},</p>
                        <p>Thank you for your submission to CNLRRS. After careful review, we have feedback regarding your paper that requires your attention.</p>
                        
                        <div style="background-color: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545;">
                            <h3 style="color: #721c24;">Paper Details:</h3>
                            <p><strong>Title:</strong> {{paper_title}}</p>
                            <p><strong>Research Type:</strong> {{research_type}}</p>
                            <p><strong>Review Date:</strong> {{review_date}}</p>
                        </div>
                        
                        <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
                            <h4 style="color: #856404;">Reviewer Feedback:</h4>
                            <p>{{reviewer_comments}}</p>
                        </div>
                        
                        <p>We encourage you to address the reviewer feedback and resubmit your paper. Our goal is to help you publish high-quality research.</p>
                        
                        <p>If you have questions about the feedback, please contact us at dacnlrrs@gmail.com</p>
                        
                        <p>Best regards,<br>CNLRRS Review Team</p>
                    </div>
                '
            ],
            'paper_under_review' => [
                'subject' => 'Paper Under Review - {{paper_title}}',
                'body' => '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                        <h2 style="color: #17a2b8;">Paper Under Review</h2>
                        <p>Dear {{author_name}},</p>
                        <p>Your research paper is now under active review by our team.</p>
                        
                        <div style="background-color: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #17a2b8;">
                            <h3 style="color: #0c5460;">Paper Details:</h3>
                            <p><strong>Title:</strong> {{paper_title}}</p>
                            <p><strong>Research Type:</strong> {{research_type}}</p>
                            <p><strong>Review Started:</strong> {{review_date}}</p>
                            <p><strong>Reviewer:</strong> {{reviewed_by}}</p>
                        </div>
                        
                        <p>We will notify you as soon as the review is complete. This typically takes 5-10 business days.</p>
                        
                        <p>Thank you for your patience.</p>
                        
                        <p>Best regards,<br>CNLRRS Review Team</p>
                    </div>
                '
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
            'template_type' => 'paper_rejected',
            'template_name' => 'Paper Requires Revision',
            'subject' => 'Paper Review Update - {{paper_title}}',
            'body' => '<h2>Paper Review Update</h2><p>Dear {{author_name}},</p><p>Your paper requires revision.</p>'
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