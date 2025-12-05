<?php
/**
 * send_email.php - Email sending utility wrapper
 * Uses EmailService class from email_config.php
 */

require_once 'email_config.php';

/**
 * Simple function to send emails using the EmailService class
 * 
 * @param string $to - Recipient email address
 * @param string $username - Recipient username (for personalization)
 * @param string $subject - Email subject
 * @param string $body - Email body (HTML or plain text)
 * @param bool $isHTML - Whether body is HTML (default: true)
 * @return bool - True if email sent successfully, false otherwise
 */
function sendEmail($to, $username, $subject, $body, $isHTML = true) {
    try {
        // Validate inputs
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("sendEmail: Invalid email address - $to");
            return false;
        }
        
        if (empty($subject)) {
            error_log("sendEmail: Empty subject");
            return false;
        }
        
        if (empty($body)) {
            error_log("sendEmail: Empty body");
            return false;
        }
        
        // Use EmailService to send
        $result = EmailService::sendEmail($to, $subject, $body, $isHTML);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("sendEmail Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Send verification code email for password reset
 * 
 * @param string $to - Recipient email address
 * @param string $username - Username for personalization
 * @param string $code - 6-digit verification code
 * @return bool
 */
function sendPasswordResetCodeEmail($to, $username, $code) {
    $subject = "Password Reset Code - CNLRRS E-Library";
    
    $body = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #115D5B; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                    .code-box { 
                        background-color: #fff; 
                        border: 2px dashed #115D5B; 
                        padding: 20px; 
                        text-align: center; 
                        margin: 20px 0;
                        border-radius: 8px;
                    }
                    .code { 
                        font-size: 36px; 
                        font-weight: bold; 
                        letter-spacing: 8px; 
                        color: #115D5B;
                        font-family: 'Courier New', monospace;
                    }
                    .footer { background-color: #f1f1f1; padding: 15px; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; text-align: center; }
                    .warning { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; border-radius: 3px; }
                    .info { background-color: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 15px 0; border-radius: 3px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🔐 Password Reset Code</h1>
                    </div>
                    
                    <div class='content'>
                        <p>Dear <strong>" . htmlspecialchars($username) . "</strong>,</p>
                        
                        <p>You requested to reset your password for your CNLRRS E-Library account. Use the verification code below to continue:</p>
                        
                        <div class='code-box'>
                            <p style='margin: 0; font-size: 14px; color: #666;'>Your Verification Code</p>
                            <div class='code'>" . htmlspecialchars($code) . "</div>
                            <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>Valid for 15 minutes</p>
                        </div>
                        
                        <div class='info'>
                            <strong>📱 How to use this code:</strong>
                            <ol style='margin: 10px 0; padding-left: 20px;'>
                                <li>Return to the password reset page</li>
                                <li>Enter this 6-digit code</li>
                                <li>Create your new password</li>
                            </ol>
                        </div>
                        
                        <div class='warning'>
                            <strong>⚠️ Security Notice:</strong>
                            <ul style='margin: 10px 0; padding-left: 20px;'>
                                <li>This code will expire in <strong>15 minutes</strong></li>
                                <li>Never share this code with anyone</li>
                                <li>If you didn't request this, please ignore this email and secure your account</li>
                                <li>CNLRRS staff will never ask you for this code</li>
                            </ul>
                        </div>
                        
                        <p>If you're having trouble, you can request a new code or contact our support team at <strong>dacnlrrs@gmail.com</strong></p>
                        
                        <p>
                            Best regards,<br>
                            <strong>CNLRRS E-Library Team</strong>
                        </p>
                    </div>
                    
                    <div class='footer'>
                        <p>© 2024 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
                        <p>This is an automated email. Please do not reply directly to this message.</p>
                        <p>If you didn't request this code, please ignore this email.</p>
                    </div>
                </div>
            </body>
        </html>
    ";
    
    return sendEmail($to, $username, $subject, $body, true);
}

/**
 * Send password reset email
 * 
 * @param string $to - Recipient email address
 * @param string $username - Username for personalization
 * @param string $resetLink - Full password reset link
 * @return bool
 */
function sendPasswordResetEmail($to, $username, $resetLink) {
    $subject = "Password Reset Request - CNLRRS E-Library";
    
    $body = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #115D5B; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                    .button { background-color: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                    .footer { background-color: #f1f1f1; padding: 15px; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; text-align: center; }
                    .warning { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Password Reset Request</h1>
                    </div>
                    
                    <div class='content'>
                        <p>Dear <strong>" . htmlspecialchars($username) . "</strong>,</p>
                        
                        <p>We received a request to reset the password for your CNLRRS E-Library account associated with this email address.</p>
                        
                        <p style='text-align: center;'>
                            <a href='" . htmlspecialchars($resetLink) . "' class='button'>Reset Your Password</a>
                        </p>
                        
                        <p><strong>Or copy and paste this link in your browser:</strong></p>
                        <p style='word-break: break-all; background-color: #f0f0f0; padding: 10px; border-radius: 3px;'>
                            " . htmlspecialchars($resetLink) . "
                        </p>
                        
                        <div class='warning'>
                            <strong>⚠️ Security Notice:</strong>
                            <ul style='margin: 10px 0; padding-left: 20px;'>
                                <li>This link will expire in <strong>1 hour</strong></li>
                                <li>This link is unique to your account and should not be shared</li>
                                <li>If you didn't request this, please ignore this email</li>
                                <li>Your password will not be reset until you click the link and set a new password</li>
                            </ul>
                        </div>
                        
                        <p>If you have any questions or need further assistance, please contact our support team at <strong>dacnlrrs@gmail.com</strong></p>
                        
                        <p>
                            Best regards,<br>
                            <strong>CNLRRS E-Library Team</strong>
                        </p>
                    </div>
                    
                    <div class='footer'>
                        <p>© 2024 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
                        <p>This is an automated email. Please do not reply directly to this message.</p>
                    </div>
                </div>
            </body>
        </html>
    ";
    
    return sendEmail($to, $username, $subject, $body, true);
}

/**
 * Send password reset confirmation email
 * 
 * @param string $to - Recipient email address
 * @param string $username - Username for personalization
 * @return bool
 */
function sendPasswordResetConfirmation($to, $username) {
    $subject = "Password Reset Successful - CNLRRS E-Library";
    
    $body = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #28a745; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                    .success-box { background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; border-radius: 3px; }
                    .footer { background-color: #f1f1f1; padding: 15px; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>✔ Password Reset Successful</h1>
                    </div>
                    
                    <div class='content'>
                        <p>Dear <strong>" . htmlspecialchars($username) . "</strong>,</p>
                        
                        <div class='success-box'>
                            <p style='margin: 0;'><strong>Your password has been successfully reset!</strong></p>
                        </div>
                        
                        <p>You can now log in to your CNLRRS E-Library account using your new password.</p>
                        
                        <h3>What to do next:</h3>
                        <ol>
                            <li>Go to the login page</li>
                            <li>Enter your username and new password</li>
                            <li>You're all set!</li>
                        </ol>
                        
                        <div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; border-radius: 3px;'>
                            <strong>💡 Tip:</strong> If you continue to have trouble logging in, please contact our support team.
                        </div>
                        
                        <p>
                            Best regards,<br>
                            <strong>CNLRRS E-Library Team</strong>
                        </p>
                    </div>
                    
                    <div class='footer'>
                        <p>© 2024 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
                        <p>This is an automated email. Please do not reply directly to this message.</p>
                    </div>
                </div>
            </body>
        </html>
    ";
    
    return sendEmail($to, $username, $subject, $body, true);
}

/**
 * Send account verification email
 * 
 * @param string $to - Recipient email address
 * @param string $username - Username for personalization
 * @param string $verificationLink - Account verification link
 * @return bool
 */
function sendAccountVerificationEmail($to, $username, $verificationLink) {
    $subject = "Verify Your Email - CNLRRS E-Library";
    
    $body = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #115D5B; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                    .button { background-color: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                    .footer { background-color: #f1f1f1; padding: 15px; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Email Verification Required</h1>
                    </div>
                    
                    <div class='content'>
                        <p>Dear <strong>" . htmlspecialchars($username) . "</strong>,</p>
                        
                        <p>Thank you for registering with CNLRRS E-Library! To complete your account setup, please verify your email address by clicking the button below:</p>
                        
                        <p style='text-align: center;'>
                            <a href='" . htmlspecialchars($verificationLink) . "' class='button'>Verify Email Address</a>
                        </p>
                        
                        <p style='color: #666; font-size: 14px;'>This link will expire in 24 hours.</p>
                        
                        <p>If you did not create this account, please ignore this email.</p>
                        
                        <p>
                            Best regards,<br>
                            <strong>CNLRRS E-Library Team</strong>
                        </p>
                    </div>
                    
                    <div class='footer'>
                        <p>© 2024 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
                    </div>
                </div>
            </body>
        </html>
    ";
    
    return sendEmail($to, $username, $subject, $body, true);
}

?>