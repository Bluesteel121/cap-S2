<?php
// email_config.php - Fixed Email Configuration with PHPMailer

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'elibrarycnlrrs@gmail.com');
define('SMTP_PASSWORD', 'okbl exhm tlxz mjkw'); // Use App Password, not regular password
define('FROM_EMAIL', 'elibrarycnlrrs@gmail.com');
define('FROM_NAME', 'CNLRRS E-Library');

/**
 * Email utility class using PHPMailer
 */
class EmailService {
    
    /**
     * Send email using PHPMailer with proper SMTP authentication
     */
    public static function sendEmail($to, $subject, $body, $isHTML = true) {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Try to load PHPMailer using require
            $phpmailer_paths = [
                __DIR__ . '/vendor/autoload.php',
                __DIR__ . '/../vendor/autoload.php',
                __DIR__ . '/PHPMailer/src/PHPMailer.php',
                __DIR__ . '/PHPMailer/src/Exception.php',
                __DIR__ . '/PHPMailer/src/SMTP.php'
            ];
            
            $loaded = false;
            foreach ($phpmailer_paths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $loaded = true;
                    break;
                }
            }
            
            if (!$loaded) {
                error_log("PHPMailer not found. Please install via: composer require phpmailer/phpmailer");
                return false;
            }
        }
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            // Disable SSL verification if on localhost (remove in production)
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
            
            // Plain text alternative for non-HTML email clients
            if ($isHTML) {
                $mail->AltBody = strip_tags($body);
            }
            
            // Send email
            $success = $mail->send();
            
            // Log activity
            if (function_exists('logActivity')) {
                $status = $success ? 'SUCCESS' : 'FAILED';
                logActivity('EMAIL_SENT', "To: $to, Subject: $subject, Status: $status");
            }
            
            return $success;
            
        } catch (Exception $e) {
            // Log error
            error_log("Email sending failed: {$mail->ErrorInfo}");
            if (function_exists('logError')) {
                logError("Email error: {$mail->ErrorInfo}", 'EMAIL_SEND_ERROR');
            }
            return false;
        }
    }
    
    /**
     * Get email template by type
     */
    public static function getEmailTemplate($template_type, $conn) {
        $sql = "SELECT * FROM email_templates WHERE template_type = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $template_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row;
        }
        
        // Return default template if none found
        return self::getDefaultTemplate($template_type);
    }
    
    /**
     * Get default email templates
     */
    public static function getDefaultTemplate($template_type) {
        $templates = [
            'paper_submitted' => [
                'subject' => 'Paper Submission Received - {{paper_title}}',
                'body' => '
                    <h2>Paper Submission Confirmation</h2>
                    <p>Dear {{author_name}},</p>
                    <p>Thank you for submitting your research paper to the Camarines Norte Lowland Rainfed Research Station.</p>
                    
                    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h3>Submission Details:</h3>
                        <p><strong>Title:</strong> {{paper_title}}</p>
                        <p><strong>Research Type:</strong> {{research_type}}</p>
                        <p><strong>Submission Date:</strong> {{submission_date}}</p>
                        <p><strong>Submitted by:</strong> {{submitted_by}}</p>
                    </div>
                    
                    <p>Your paper is now under review. Our review process typically takes 5-10 business days. You will receive an email notification once the review is complete.</p>
                    
                    <p>If you have any questions, please contact us at dacnlrrs@gmail.com</p>
                    
                    <p>Best regards,<br>
                    CNLRRS Review Team</p>
                '
            ],
            'paper_approved' => [
                'subject' => 'Paper Approved - {{paper_title}}',
                'body' => '
                    <h2>Paper Approval Notification</h2>
                    <p>Dear {{author_name}},</p>
                    <p>Congratulations! Your research paper has been approved for publication.</p>
                    
                    <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
                        <h3>Approved Paper Details:</h3>
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
                    
                    <p>Best regards,<br>
                    CNLRRS Review Team</p>
                '
            ],
            'paper_rejected' => [
                'subject' => 'Paper Review Update - {{paper_title}}',
                'body' => '
                    <h2>Paper Review Update</h2>
                    <p>Dear {{author_name}},</p>
                    <p>Thank you for your submission to CNLRRS. After careful review, we regret to inform you that your paper requires revision before it can be accepted for publication.</p>
                    
                    <div style="background-color: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545;">
                        <h3>Paper Details:</h3>
                        <p><strong>Title:</strong> {{paper_title}}</p>
                        <p><strong>Research Type:</strong> {{research_type}}</p>
                        <p><strong>Review Date:</strong> {{review_date}}</p>
                    </div>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
                        <h4>Reviewer Feedback:</h4>
                        <p>{{reviewer_comments}}</p>
                    </div>
                    
                    <p>We encourage you to address the reviewer feedback and resubmit your paper. Our goal is to help you publish high-quality research.</p>
                    
                    <p>If you have questions about the feedback, please contact us at dacnlrrs@gmail.com</p>
                    
                    <p>Best regards,<br>
                    CNLRRS Review Team</p>
                '
            ],
            'paper_published' => [
                'subject' => 'Paper Published - {{paper_title}}',
                'body' => '
                    <h2>Paper Published Successfully</h2>
                    <p>Dear {{author_name}},</p>
                    <p>Great news! Your research paper has been published on the CNLRRS research platform.</p>
                    
                    <div style="background-color: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #17a2b8;">
                        <h3>Published Paper Details:</h3>
                        <p><strong>Title:</strong> {{paper_title}}</p>
                        <p><strong>Research Type:</strong> {{research_type}}</p>
                        <p><strong>Publication Date:</strong> {{review_date}}</p>
                        <p><strong>Access URL:</strong> <a href="{{paper_url}}">View Paper</a></p>
                    </div>
                    
                    <p>Your research is now accessible to the agricultural research community and will contribute to advancing knowledge in your field.</p>
                    
                    <p>Thank you for your valuable contribution to CNLRRS!</p>
                    
                    <p>Best regards,<br>
                    CNLRRS Publication Team</p>
                '
            ]
        ];
        
        return $templates[$template_type] ?? null;
    }
    
    /**
     * Replace template variables with actual values
     */
    public static function replaceTemplateVariables($template, $variables) {
        $subject = $template['subject'];
        $body = $template['body'];
        
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }
        
        return ['subject' => $subject, 'body' => $body];
    }
    
    /**
     * Send paper submission notification
     */
    public static function sendPaperSubmissionNotification($paperData, $userEmail, $conn) {
        $template = self::getEmailTemplate('paper_submitted', $conn);
        
        if (!$template) {
            error_log("Email template 'paper_submitted' not found");
            return false;
        }
        
        $variables = [
            'author_name' => $paperData['author_name'],
            'paper_title' => $paperData['paper_title'],
            'research_type' => $paperData['research_type'],
            'submission_date' => date('F j, Y'),
            'submitted_by' => $paperData['user_name']
        ];
        
        $email = self::replaceTemplateVariables($template, $variables);
        
        return self::sendEmail($userEmail, $email['subject'], $email['body']);
    }
    
    /**
     * Send paper review notification (approved, rejected, etc.)
     */
    public static function sendPaperReviewNotification($paperData, $userEmail, $status, $conn) {
        $template_type = 'paper_' . $status;
        $template = self::getEmailTemplate($template_type, $conn);
        
        if (!$template) {
            error_log("Email template '$template_type' not found");
            return false;
        }
        
        $variables = [
            'author_name' => $paperData['author_name'],
            'paper_title' => $paperData['paper_title'],
            'research_type' => $paperData['research_type'],
            'review_date' => date('F j, Y'),
            'reviewed_by' => $paperData['reviewed_by'] ?? 'Admin',
            'reviewer_comments' => $paperData['reviewer_comments'] ?? '',
            'paper_url' => 'https://cnlrrs.gov.ph/view_paper.php?id=' . $paperData['id']
        ];
        
        $email = self::replaceTemplateVariables($template, $variables);
        
        return self::sendEmail($userEmail, $email['subject'], $email['body']);
    }
}

/**
 * Get user email from database
 */
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

/**
 * Create email templates table if it doesn't exist
 */
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

/**
 * Initialize default email templates
 */
function initializeDefaultEmailTemplates($conn) {
    createEmailTemplatesTable($conn);
    
    $defaultTemplates = [
        [
            'template_type' => 'paper_submitted',
            'template_name' => 'Paper Submission Confirmation',
            'subject' => 'Paper Submission Received - {{paper_title}}',
            'body' => '<h2>Paper Submission Confirmation</h2><p>Dear {{author_name}},</p><p>Thank you for submitting your research paper to CNLRRS. Your paper "{{paper_title}}" is now under review.</p><p>Best regards,<br>CNLRRS Team</p>'
        ],
        [
            'template_type' => 'paper_approved',
            'template_name' => 'Paper Approved',
            'subject' => 'Paper Approved - {{paper_title}}',
            'body' => '<h2>Paper Approved</h2><p>Dear {{author_name}},</p><p>Congratulations! Your paper "{{paper_title}}" has been approved for publication.</p><p>Best regards,<br>CNLRRS Team</p>'
        ],
        [
            'template_type' => 'paper_rejected',
            'template_name' => 'Paper Requires Revision',
            'subject' => 'Paper Review Update - {{paper_title}}',
            'body' => '<h2>Paper Review Update</h2><p>Dear {{author_name}},</p><p>Your paper "{{paper_title}}" requires revision. Please review the feedback and resubmit.</p><p>Best regards,<br>CNLRRS Team</p>'
        ],
        [
            'template_type' => 'paper_published',
            'template_name' => 'Paper Published',
            'subject' => 'Paper Published - {{paper_title}}',
            'body' => '<h2>Paper Published</h2><p>Dear {{author_name}},</p><p>Your paper "{{paper_title}}" has been published successfully!</p><p>Best regards,<br>CNLRRS Team</p>'
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