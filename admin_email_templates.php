<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include required files
require_once 'connect.php';
require_once 'email_config.php';
require_once 'user_activity_logger.php';

// Initialize email templates and notification settings
initializeDefaultEmailTemplates($conn);
createNotificationSettingsTable($conn);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_template':
                $template_id = (int)$_POST['template_id'];
                $subject = $_POST['subject'];
                $body = $_POST['body'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE email_templates SET subject = ?, body = ?, is_active = ?, updated_by = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisi", $subject, $body, $is_active, $_SESSION['username'], $template_id);
                
                if ($stmt->execute()) {
                    logActivity('EMAIL_TEMPLATE_UPDATED', "Template ID: $template_id, Admin: {$_SESSION['username']}");
                    $success_message = "Email template updated successfully!";
                } else {
                    $error_message = "Failed to update template: " . $conn->error;
                }
                break;
                
            case 'update_notification_settings':
                $notification_emails = $_POST['notification_emails'] ?? '';
                $notify_on_submission = isset($_POST['notify_on_submission']) ? 1 : 0;
                $notify_on_revision = isset($_POST['notify_on_revision']) ? 1 : 0;
                
                // Validate and clean email addresses
                $emails_array = array_map('trim', explode(',', $notification_emails));
                $valid_emails = array_filter($emails_array, function($email) {
                    return filter_var($email, FILTER_VALIDATE_EMAIL);
                });
                $cleaned_emails = implode(',', $valid_emails);
                
                // Update or insert notification settings
                $sql = "INSERT INTO notification_settings (id, notification_emails, notify_on_submission, notify_on_revision, updated_by) 
                        VALUES (1, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                        notification_emails = VALUES(notification_emails),
                        notify_on_submission = VALUES(notify_on_submission),
                        notify_on_revision = VALUES(notify_on_revision),
                        updated_by = VALUES(updated_by),
                        updated_at = CURRENT_TIMESTAMP";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siis", $cleaned_emails, $notify_on_submission, $notify_on_revision, $_SESSION['username']);
                
                if ($stmt->execute()) {
                    logActivity('NOTIFICATION_SETTINGS_UPDATED', "Updated by: {$_SESSION['username']}, Emails: $cleaned_emails");
                    $success_message = "Notification settings updated successfully!";
                } else {
                    $error_message = "Failed to update notification settings: " . $conn->error;
                }
                break;
                
            case 'test_email':
                $template_id = (int)$_POST['template_id'];
                $test_email = $_POST['test_email'];
                
                // Get template
                $sql = "SELECT * FROM email_templates WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $template_id);
                $stmt->execute();
                $template = $stmt->get_result()->fetch_assoc();
                
                if ($template) {
                    // Sample variables for testing
                    $testVariables = [
                        'author_name' => 'John Doe',
                        'paper_title' => 'Sample Research Paper Title',
                        'research_type' => 'Agricultural Research',
                        'submission_date' => date('F j, Y'),
                        'review_date' => date('F j, Y'),
                        'reviewed_by' => $_SESSION['username'],
                        'reviewer_comments' => 'This is a sample review comment for testing purposes.',
                        'submitted_by' => 'test_user',
                        'paper_url' => 'https://cnlrrs.gov.ph/view_paper.php?id=123'
                    ];
                    
                    $email = EmailService::replaceTemplateVariables($template, $testVariables);
                    
                    if (EmailService::sendEmail($test_email, "[TEST] " . $email['subject'], $email['body'])) {
                        logActivity('EMAIL_TEMPLATE_TEST', "Template ID: $template_id, Test email: $test_email");
                        $success_message = "Test email sent successfully to $test_email";
                    } else {
                        $error_message = "Failed to send test email to $test_email";
                    }
                } else {
                    $error_message = "Template not found";
                }
                break;
                
            case 'test_notification':
                $test_email = $_POST['test_notification_email'];
                
                $testData = [
                    'author_name' => 'Test Author',
                    'paper_title' => 'Test Paper for Notification',
                    'research_type' => 'Agricultural Research',
                    'submission_date' => date('F j, Y'),
                    'submitted_by' => 'test_user'
                ];
                
                if (EmailService::sendAdminNotification($testData, 'submission', $conn, $test_email)) {
                    $success_message = "Test notification sent successfully to $test_email";
                } else {
                    $error_message = "Failed to send test notification";
                }
                break;
                
            case 'reset_template':
                $template_id = (int)$_POST['template_id'];
                
                // Get template type
                $sql = "SELECT template_type FROM email_templates WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $template_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result) {
                    $defaultTemplate = EmailService::getDefaultTemplate($result['template_type']);
                    if ($defaultTemplate) {
                        $sql = "UPDATE email_templates SET subject = ?, body = ?, updated_by = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssi", $defaultTemplate['subject'], $defaultTemplate['body'], $_SESSION['username'], $template_id);
                        
                        if ($stmt->execute()) {
                            logActivity('EMAIL_TEMPLATE_RESET', "Template ID: $template_id, Admin: {$_SESSION['username']}");
                            $success_message = "Template reset to default successfully!";
                        } else {
                            $error_message = "Failed to reset template";
                        }
                    }
                }
                break;
        }
    }
}

// Get all email templates
$sql = "SELECT * FROM email_templates ORDER BY template_type";
$result = $conn->query($sql);
$templates = $result->fetch_all(MYSQLI_ASSOC);

// Get notification settings
$sql = "SELECT * FROM notification_settings WHERE id = 1";
$result = $conn->query($sql);
$notification_settings = $result->fetch_assoc();

// Initialize default settings if not exists
if (!$notification_settings) {
    $notification_settings = [
        'notification_emails' => '',
        'notify_on_submission' => 1,
        'notify_on_revision' => 1,
        'updated_by' => null,
        'updated_at' => null
    ];
}

// Template type descriptions
$templateDescriptions = [
    'paper_submitted' => 'Sent to authors when they submit a new paper',
    'paper_approved' => 'Sent to authors when their paper is approved',
    'paper_rejected' => 'Sent to authors when their paper is rejected',
    'paper_published' => 'Sent to authors when their paper is published',
    'paper_revision_requested' => 'Sent to authors when revision is required',
    'paper_revision_submitted' => 'Sent to authors when revision is received',
    'paper_under_review' => 'Sent to authors when paper is under review'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - CNLRRS Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .template-card {
            transition: all 0.3s ease;
        }
        .template-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .preview-content {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
        }
        .html-preview {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-[#115D5B] text-white py-4 px-6 shadow-lg">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                <div>
                    <h1 class="text-xl font-bold">Email Templates Management</h1>
                    <p class="text-sm opacity-75">Configure automated email notifications</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="admin_loggedin_index.php" class="flex items-center space-x-2 hover:text-yellow-200 transition">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Admin Notification Settings Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold flex items-center">
                            <i class="fas fa-bell mr-3"></i>
                            Admin Notification Settings
                        </h3>
                        <p class="text-sm opacity-90 mt-1">
                            Configure who receives notifications for paper submissions and revisions
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_notification_settings">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-[#115D5B]"></i>
                            Notification Email Addresses
                        </label>
                        <p class="text-sm text-gray-600 mb-2">
                            Enter email addresses that should receive notifications. Separate multiple emails with commas.
                        </p>
                        <textarea name="notification_emails" rows="3" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                            placeholder="admin@cnlrrs.gov.ph, reviewer@cnlrrs.gov.ph"><?php echo htmlspecialchars($notification_settings['notification_emails']); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            Example: admin@cnlrrs.gov.ph, reviewer1@cnlrrs.gov.ph, reviewer2@cnlrrs.gov.ph
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                        <p class="font-medium text-gray-700 mb-3">
                            <i class="fas fa-cog mr-2"></i>Notification Triggers
                        </p>
                        
                        <div class="flex items-start space-x-3">
                            <input type="checkbox" name="notify_on_submission" id="notify_on_submission" 
                                <?php echo $notification_settings['notify_on_submission'] ? 'checked' : ''; ?>
                                class="mt-1 h-4 w-4 text-[#115D5B] border-gray-300 rounded">
                            <div>
                                <label for="notify_on_submission" class="text-sm font-medium text-gray-700 cursor-pointer">
                                    New Paper Submissions
                                </label>
                                <p class="text-xs text-gray-500 mt-1">
                                    Send notification when a user submits a new research paper
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <input type="checkbox" name="notify_on_revision" id="notify_on_revision" 
                                <?php echo $notification_settings['notify_on_revision'] ? 'checked' : ''; ?>
                                class="mt-1 h-4 w-4 text-[#115D5B] border-gray-300 rounded">
                            <div>
                                <label for="notify_on_revision" class="text-sm font-medium text-gray-700 cursor-pointer">
                                    Paper Revisions
                                </label>
                                <p class="text-xs text-gray-500 mt-1">
                                    Send notification when a user submits a revised paper
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="submit" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-md transition">
                            <i class="fas fa-save mr-2"></i>Save Notification Settings
                        </button>
                        <button type="button" onclick="showTestNotificationModal()" 
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md transition">
                            <i class="fas fa-paper-plane mr-2"></i>Send Test Notification
                        </button>
                    </div>
                </form>
                
                <?php if ($notification_settings['updated_at']): ?>
                    <div class="mt-6 pt-4 border-t text-sm text-gray-600">
                        <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($notification_settings['updated_at'])); ?></p>
                        <p><strong>Updated By:</strong> <?php echo htmlspecialchars($notification_settings['updated_by'] ?? 'System'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Email System Status -->
        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-envelope mr-3"></i>
                <div>
                    <p class="font-semibold">Email System Status: Active</p>
                    <p class="text-sm">Using PHP mail() function with elibrarycnlrrs@gmail.com</p>
                </div>
            </div>
        </div>

        <!-- Email Templates -->
        <div class="space-y-8">
            <?php foreach ($templates as $template): ?>
                <div class="template-card bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($template['template_name']); ?></h3>
                                <p class="text-sm opacity-90 mt-1">
                                    <?php echo htmlspecialchars($templateDescriptions[$template['template_type']] ?? 'Custom template'); ?>
                                </p>
                                <div class="flex items-center mt-2 space-x-4">
                                    <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded">
                                        Type: <?php echo htmlspecialchars($template['template_type']); ?>
                                    </span>
                                    <span class="text-xs <?php echo $template['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?> px-2 py-1 rounded">
                                        <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="toggleTemplate(<?php echo $template['id']; ?>)" 
                                    class="bg-white bg-opacity-20 hover:bg-opacity-30 px-3 py-1 rounded text-sm transition">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button onclick="testTemplate(<?php echo $template['id']; ?>)" 
                                    class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded text-sm text-black transition">
                                    <i class="fas fa-paper-plane mr-1"></i>Test
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Template Preview -->
                        <div class="mb-6">
                            <h4 class="font-semibold text-gray-800 mb-2">Current Template:</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="mb-3">
                                    <label class="text-sm font-medium text-gray-600">Subject:</label>
                                    <p class="text-gray-800 bg-white p-2 rounded border"><?php echo htmlspecialchars($template['subject']); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Email Preview:</label>
                                    <div class="preview-content html-preview p-3 rounded">
                                        <?php 
                                        $preview_body = $template['body'];
                                        
                                        if (strlen($preview_body) > 800) {
                                            $preview_body = substr($preview_body, 0, 800);
                                            $last_space = strrpos($preview_body, ' ');
                                            $last_tag = strrpos($preview_body, '>');
                                            $cut_point = max($last_space, $last_tag);
                                            if ($cut_point > 600) {
                                                $preview_body = substr($preview_body, 0, $cut_point);
                                            }
                                        }
                                        
                                        $sample_data = [
                                            '{{author_name}}' => 'Dr. Sample Author',
                                            '{{paper_title}}' => 'Sample Research Paper Title',
                                            '{{research_type}}' => 'Agricultural Research',
                                            '{{submission_date}}' => date('F j, Y'),
                                            '{{review_date}}' => date('F j, Y'),
                                            '{{reviewed_by}}' => 'Admin Reviewer',
                                            '{{reviewer_comments}}' => 'Sample review comment.',
                                            '{{submitted_by}}' => 'sample_user',
                                            '{{paper_url}}' => '#'
                                        ];
                                        
                                        foreach ($sample_data as $placeholder => $value) {
                                            $preview_body = str_replace($placeholder, $value, $preview_body);
                                        }
                                        
                                        echo $preview_body;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Form (Hidden by default) -->
                        <div id="edit-form-<?php echo $template['id']; ?>" class="hidden">
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_template">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Subject:</label>
                                    <input type="text" name="subject" value="<?php echo htmlspecialchars($template['subject']); ?>" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]" required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Body (HTML):</label>
                                    <textarea name="body" rows="12" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] text-sm font-mono" 
                                        required><?php echo htmlspecialchars($template['body']); ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Use template variables: {{author_name}}, {{paper_title}}, {{research_type}}, etc.
                                    </p>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="active-<?php echo $template['id']; ?>" 
                                        <?php echo $template['is_active'] ? 'checked' : ''; ?>
                                        class="h-4 w-4 text-[#115D5B] border-gray-300 rounded">
                                    <label for="active-<?php echo $template['id']; ?>" class="ml-2 text-sm text-gray-700">
                                        Template is active
                                    </label>
                                </div>
                                
                                <div class="flex space-x-3 pt-4">
                                    <button type="submit" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-md transition">
                                        <i class="fas fa-save mr-2"></i>Save Changes
                                    </button>
                                    <button type="button" onclick="toggleTemplate(<?php echo $template['id']; ?>)" 
                                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition">
                                        Cancel
                                    </button>
                                    <button type="submit" name="action" value="reset_template" 
                                        onclick="return confirm('Reset this template to default?')"
                                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
                                        <i class="fas fa-undo mr-2"></i>Reset Default
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Test Email Modal -->
    <div id="testEmailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-paper-plane mr-2"></i>Send Test Email
                    </h3>
                    <button onclick="closeTestModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="test_email">
                <input type="hidden" name="template_id" id="testTemplateId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Send test email to:</label>
                    <input type="email" name="test_email" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                        placeholder="Enter email address">
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-paper-plane mr-2"></i>Send Test
                    </button>
                    <button type="button" onclick="closeTestModal()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Test Notification Modal -->
    <div id="testNotificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-bell mr-2"></i>Send Test Notification
                    </h3>
                    <button onclick="closeTestNotificationModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="test_notification">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Send test notification to:</label>
                    <input type="email" name="test_notification_email" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B]"
                        placeholder="Enter email address">
                </div>
                
                <div class="bg-teal-50 border border-teal-200 p-3 rounded-lg mb-4">
                    <p class="text-sm text-teal-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        This will send a sample admin notification with test data.
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-4 py-2 rounded-md transition">
                        <i class="fas fa-paper-plane mr-2"></i>Send Test
                    </button>
                    <button type="button" onclick="closeTestNotificationModal()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="bg-[#115D5B] text-white text-center py-4 mt-12">
        <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
    </footer>

    <script>
        function toggleTemplate(templateId) {
            const editForm = document.getElementById('edit-form-' + templateId);
            if (editForm.classList.contains('hidden')) {
                editForm.classList.remove('hidden');
                editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                editForm.classList.add('hidden');
            }
        }

        function testTemplate(templateId) {
            document.getElementById('testTemplateId').value = templateId;
            const modal = document.getElementById('testEmailModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTestModal() {
            const modal = document.getElementById('testEmailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function showTestNotificationModal() {
            const modal = document.getElementById('testNotificationModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTestNotificationModal() {
            const modal = document.getElementById('testNotificationModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Auto-hide messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease-out';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>