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

// Initialize email templates
initializeDefaultEmailTemplates($conn);

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

// Template type descriptions
$templateDescriptions = [
    'paper_submitted' => 'Sent to authors when they submit a new paper',
    'paper_approved' => 'Sent to authors when their paper is approved',
    'paper_rejected' => 'Sent to authors when their paper is rejected',
    'paper_published' => 'Sent to authors when their paper is published'
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
        .variable-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-family: monospace;
            display: inline-block;
            margin: 2px;
            cursor: pointer;
        }
        .variable-tag:hover {
            background: #bbdefb;
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
        .html-preview h1, .html-preview h2, .html-preview h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .html-preview p {
            margin-bottom: 10px;
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
                                    <label class="text-sm font-medium text-gray-600">Email Preview (rendered HTML):</label>
                                    <div class="preview-content html-preview p-3 rounded">
                                        <?php 
                                        // Show rendered HTML but truncate safely for preview
                                        $preview_body = $template['body'];
                                        
                                        // If the body is very long, truncate it intelligently
                                        if (strlen($preview_body) > 800) {
                                            $preview_body = substr($preview_body, 0, 800);
                                            // Try to end at a complete word or tag
                                            $last_space = strrpos($preview_body, ' ');
                                            $last_tag = strrpos($preview_body, '>');
                                            $cut_point = max($last_space, $last_tag);
                                            if ($cut_point > 600) {
                                                $preview_body = substr($preview_body, 0, $cut_point);
                                            }
                                        }
                                        
                                        // Replace template variables with sample data for preview
                                        $sample_data = [
                                            '{{author_name}}' => 'Dr. Sample Author',
                                            '{{paper_title}}' => 'Sample Research Paper Title',
                                            '{{research_type}}' => 'Agricultural Research',
                                            '{{submission_date}}' => date('F j, Y'),
                                            '{{review_date}}' => date('F j, Y'),
                                            '{{reviewed_by}}' => 'Admin Reviewer',
                                            '{{reviewer_comments}}' => 'This is a sample review comment.',
                                            '{{submitted_by}}' => 'sample_user',
                                            '{{paper_url}}' => '#'
                                        ];
                                        
                                        foreach ($sample_data as $placeholder => $value) {
                                            $preview_body = str_replace($placeholder, $value, $preview_body);
                                        }
                                        
                                        // Output the rendered HTML
                                        echo $preview_body;
                                        ?>
                                        <?php if (strlen($template['body']) > 800): ?>
                                            <div class="mt-3 p-2 bg-gray-100 text-gray-600 text-sm rounded">
                                                <em><i class="fas fa-info-circle mr-1"></i>Preview truncated - full email will be longer</em>
                                            </div>
                                        <?php endif; ?>
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
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Body:</label>
                                    
                                    <!-- Toggle between Visual and Code view -->
                                    <div class="flex space-x-2 mb-2">
                                        <button type="button" onclick="showVisualEditor(<?php echo $template['id']; ?>)" 
                                            id="visual-btn-<?php echo $template['id']; ?>"
                                            class="px-3 py-1 text-xs bg-blue-500 text-white rounded transition">
                                            <i class="fas fa-eye mr-1"></i>Visual
                                        </button>
                                        <button type="button" onclick="showCodeEditor(<?php echo $template['id']; ?>)" 
                                            id="code-btn-<?php echo $template['id']; ?>"
                                            class="px-3 py-1 text-xs bg-gray-300 text-gray-700 rounded transition">
                                            <i class="fas fa-code mr-1"></i>HTML Code
                                        </button>
                                    </div>
                                    
                                    <!-- Visual Editor -->
                                    <div id="visual-editor-<?php echo $template['id']; ?>" class="mb-3">
                                        <div class="border border-gray-300 rounded-md p-3 min-h-64 bg-white" 
                                             contenteditable="true" 
                                             id="visual-content-<?php echo $template['id']; ?>"
                                             style="max-height: 300px; overflow-y: auto;">
                                            <?php 
                                            // Show rendered HTML for visual editing
                                            $visual_content = $template['body'];
                                            // Replace template variables with sample data for visual editing
                                            $sample_data = [
                                                '{{author_name}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Dr. Sample Author</span>',
                                                '{{paper_title}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Sample Research Paper Title</span>',
                                                '{{research_type}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Agricultural Research</span>',
                                                '{{submission_date}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">' . date('F j, Y') . '</span>',
                                                '{{review_date}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">' . date('F j, Y') . '</span>',
                                                '{{reviewed_by}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Admin Reviewer</span>',
                                                '{{reviewer_comments}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">This is a sample review comment.</span>',
                                                '{{submitted_by}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">sample_user</span>',
                                                '{{paper_url}}' => '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">#paper-link</span>'
                                            ];
                                            
                                            foreach ($sample_data as $placeholder => $value) {
                                                $visual_content = str_replace($placeholder, $value, $visual_content);
                                            }
                                            echo $visual_content;
                                            ?>
                                        </div>
                                        <p class="text-xs text-blue-600 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>Visual editor - highlighted areas are template variables
                                        </p>
                                    </div>
                                    
                                    <!-- Code Editor (Hidden by default) -->
                                    <div id="code-editor-<?php echo $template['id']; ?>" class="hidden">
                                        <textarea name="body" rows="12" 
                                            id="code-textarea-<?php echo $template['id']; ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#115D5B] text-sm font-mono" 
                                            required><?php echo htmlspecialchars($template['body']); ?></textarea>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-code mr-1"></i>HTML code editor - use template variables like {{author_name}}
                                        </p>
                                    </div>
                                    
                                    <!-- Hidden input to store the actual body content -->
                                    <input type="hidden" name="body" id="body-input-<?php echo $template['id']; ?>" value="<?php echo htmlspecialchars($template['body']); ?>">
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="active-<?php echo $template['id']; ?>" 
                                        <?php echo $template['is_active'] ? 'checked' : ''; ?>
                                        class="h-4 w-4 text-[#115D5B] border-gray-300 rounded">
                                    <label for="active-<?php echo $template['id']; ?>" class="ml-2 text-sm text-gray-700">
                                        Template is active (emails will be sent using this template)
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
                                        onclick="return confirm('Reset this template to default? All changes will be lost.')"
                                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
                                        <i class="fas fa-undo mr-2"></i>Reset Default
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Template Info -->
                        <div class="mt-6 text-sm text-gray-600 border-t pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p><strong>Last Updated:</strong> <?php echo $template['updated_at'] ? date('M j, Y g:i A', strtotime($template['updated_at'])) : 'Never'; ?></p>
                                    <p><strong>Updated By:</strong> <?php echo htmlspecialchars($template['updated_by'] ?? 'System'); ?></p>
                                </div>
                                <div>
                                    <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($template['created_at'])); ?></p>
                                    <p><strong>Template ID:</strong> <?php echo $template['id']; ?></p>
                                </div>
                            </div>
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
                
                <div class="bg-yellow-50 border border-yellow-200 p-3 rounded-lg mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Test email will use sample data for template variables and will be sent with "[TEST]" prefix.
                    </p>
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

    <!-- Footer -->
    <footer class="bg-[#115D5B] text-white text-center py-4 mt-12">
        <p>&copy; 2025 Camarines Norte Lowland Rainfed Research Station. All rights reserved.</p>
    </footer>

    <script>
        // Editor switching functions
        function showVisualEditor(templateId) {
            document.getElementById('visual-editor-' + templateId).classList.remove('hidden');
            document.getElementById('code-editor-' + templateId).classList.add('hidden');
            document.getElementById('visual-btn-' + templateId).className = 'px-3 py-1 text-xs bg-blue-500 text-white rounded transition';
            document.getElementById('code-btn-' + templateId).className = 'px-3 py-1 text-xs bg-gray-300 text-gray-700 rounded transition';
            
            // Sync content from textarea to visual editor if code was modified
            const textarea = document.getElementById('code-textarea-' + templateId);
            const visualEditor = document.getElementById('visual-content-' + templateId);
            if (textarea) {
                let htmlContent = textarea.value;
                // Replace template variables with highlighted versions for visual editing
                const sampleData = {
                    '{{author_name}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Dr. Sample Author</span>',
                    '{{paper_title}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Sample Research Paper Title</span>',
                    '{{research_type}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Agricultural Research</span>',
                    '{{submission_date}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">' + new Date().toLocaleDateString() + '</span>',
                    '{{review_date}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">' + new Date().toLocaleDateString() + '</span>',
                    '{{reviewed_by}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Admin Reviewer</span>',
                    '{{reviewer_comments}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Sample review comment</span>',
                    '{{submitted_by}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">sample_user</span>',
                    '{{paper_url}}': '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">#paper-link</span>'
                };
                for (const [placeholder, replacement] of Object.entries(sampleData)) {
                    htmlContent = htmlContent.split(placeholder).join(replacement);
                }
                visualEditor.innerHTML = htmlContent;
            }
        }
        
        function showCodeEditor(templateId) {
            document.getElementById('visual-editor-' + templateId).classList.add('hidden');
            document.getElementById('code-editor-' + templateId).classList.remove('hidden');
            document.getElementById('visual-btn-' + templateId).className = 'px-3 py-1 text-xs bg-gray-300 text-gray-700 rounded transition';
            document.getElementById('code-btn-' + templateId).className = 'px-3 py-1 text-xs bg-blue-500 text-white rounded transition';
            syncVisualToCode(templateId);
        }
        
        function syncVisualToCode(templateId) {
            const visualEditor = document.getElementById('visual-content-' + templateId);
            const textarea = document.getElementById('code-textarea-' + templateId);
            const hiddenInput = document.getElementById('body-input-' + templateId);
            if (visualEditor && textarea) {
                let htmlContent = visualEditor.innerHTML;
                // Convert highlighted template variables back to placeholders
                const variableMap = {
                    '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Dr. Sample Author</span>': '{{author_name}}',
                    '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Sample Research Paper Title</span>': '{{paper_title}}',
                    '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Agricultural Research</span>': '{{research_type}}',
                    '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Admin Reviewer</span>': '{{reviewed_by}}',
                    '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">Sample review comment</span>': '{{reviewer_comments}}',
                    '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">sample_user</span>': '{{submitted_by}}',
                    '<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">#paper-link</span>': '{{paper_url}}'
                };
                // Handle date placeholders
                htmlContent = htmlContent.replace(
                    /<span style="background: #fef3c7; padding: 1px 4px; border-radius: 3px;">[^<]+<\/span>/g,
                    function(match) {
                        if (match.includes('Dr. Sample Author')) return '{{author_name}}';
                        if (match.includes('Sample Research Paper Title')) return '{{paper_title}}';
                        if (match.includes('Agricultural Research')) return '{{research_type}}';
                        if (match.includes('Admin Reviewer')) return '{{reviewed_by}}';
                        if (match.includes('Sample review comment')) return '{{reviewer_comments}}';
                        if (match.includes('sample_user')) return '{{submitted_by}}';
                        if (match.includes('#paper-link')) return '{{paper_url}}';
                        // For dates
                        if (/\d{1,2}\/\d{1,2}\/\d{2,4}/.test(match)) return '{{submission_date}}';
                        return match;
                    }
                );
                for (const [highlighted, placeholder] of Object.entries(variableMap)) {
                    htmlContent = htmlContent.split(highlighted).join(placeholder);
                }
                textarea.value = htmlContent;
                if (hiddenInput) hiddenInput.value = htmlContent;
            }
        }

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
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
        }

        function closeTestModal() {
            const modal = document.getElementById('testEmailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Close modal when clicking outside
            const modal = document.getElementById('testEmailModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeTestModal();
                    }
                });
            }
            // Form submission handler to sync visual content
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const templateId = this.querySelector('input[name="template_id"]');
                    if (templateId) {
                        const tid = templateId.value;
                        const visualEditor = document.getElementById('visual-editor-' + tid);
                        const codeEditor = document.getElementById('code-editor-' + tid);
                        const hiddenInput = document.getElementById('body-input-' + tid);
                        if (visualEditor && !visualEditor.classList.contains('hidden')) {
                            syncVisualToCode(tid);
                        } else if (codeEditor && !codeEditor.classList.contains('hidden')) {
                            const textarea = document.getElementById('code-textarea-' + tid);
                            if (textarea && hiddenInput) {
                                hiddenInput.value = textarea.value;
                            }
                        }
                    }
                });
            });
            // Variable tag click to copy
            document.querySelectorAll('.variable-tag').forEach(tag => {
                tag.addEventListener('click', function() {
                    navigator.clipboard.writeText(this.textContent).then(() => {
                        const original = this.textContent;
                        this.textContent = 'Copied!';
                        this.style.background = '#4caf50';
                        this.style.color = 'white';
                        setTimeout(() => {
                            this.textContent = original;
                            this.style.background = '#e3f2fd';
                            this.style.color = '#1976d2';
                        }, 1000);
                    }).catch(() => {
                        const textArea = document.createElement('textarea');
                        textArea.value = this.textContent;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        const original = this.textContent;
                        this.textContent = 'Copied!';
                        this.style.background = '#4caf50';
                        this.style.color = 'white';
                        setTimeout(() => {
                            this.textContent = original;
                            this.style.background = '#e3f2fd';
                            this.style.color = '#1976d2';
                        }, 1000);
                    });
                });
            });
        });

        // Auto-hide success/error messages
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