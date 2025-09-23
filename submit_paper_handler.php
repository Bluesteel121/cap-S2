<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

header('Content-Type: application/json');

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=cap", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input data
        $paperTitle = trim($_POST['paper_title'] ?? '');
        $researchType = $_POST['research_type'] ?? 'other';
        $keywords = trim($_POST['keywords'] ?? '');
        $authorName = trim($_POST['author_name'] ?? '');
        $authorEmail = trim($_POST['author_email'] ?? '');
        $affiliation = trim($_POST['affiliation'] ?? '');
        $coAuthors = trim($_POST['co_authors'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');
        $methodology = trim($_POST['methodology'] ?? '');
        $fundingSource = trim($_POST['funding_source'] ?? '');
        $researchStartDate = $_POST['research_start_date'] ?? null;
        $researchEndDate = $_POST['research_end_date'] ?? null;
        $ethicsApproval = trim($_POST['ethics_approval'] ?? '');
        $additionalComments = trim($_POST['additional_comments'] ?? '');
        $termsAgreement = isset($_POST['terms_agreement']) ? 1 : 0;
        $emailConsent = isset($_POST['email_consent']) ? 1 : 0;
        $dataConsent = isset($_POST['data_consent']) ? 1 : 0;

        // Validation
        $errors = [];
        
        if (empty($paperTitle)) $errors[] = 'Paper title is required';
        if (empty($authorName)) $errors[] = 'Author name is required';
        if (empty($authorEmail)) $errors[] = 'Author email is required';
        if (empty($affiliation)) $errors[] = 'Author affiliation is required';
        if (empty($keywords)) $errors[] = 'Keywords are required';
        if (empty($abstract)) $errors[] = 'Abstract is required';
        if (!$termsAgreement) $errors[] = 'Terms agreement is required';
        
        // Validate email format
        if (!empty($authorEmail) && !filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Validate research type
        $validTypes = ['experimental', 'observational', 'review', 'case_study', 'other'];
        if (!in_array($researchType, $validTypes)) {
            $researchType = 'other';
        }
        
        // Validate dates
        if (!empty($researchStartDate) && !empty($researchEndDate)) {
            if (strtotime($researchStartDate) > strtotime($researchEndDate)) {
                $errors[] = 'Research start date must be before end date';
            }
        }
        
        // Validate text lengths
        if (strlen($paperTitle) > 500) $errors[] = 'Paper title is too long';
        if (strlen($keywords) > 500) $errors[] = 'Keywords are too long';
        if (strlen($abstract) > 2000) $errors[] = 'Abstract is too long';
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode('; ', $errors)]);
            exit();
        }

        // Handle file upload
        $filePath = null;
        if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/papers/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION));
            
            // Validate file type
            if ($fileExtension !== 'pdf') {
                echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
                exit();
            }
            
            // Validate file size (25MB max)
            if ($_FILES['paper_file']['size'] > 25 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File size must be less than 25MB']);
                exit();
            }
            
            // Generate unique filename
            $fileName = uniqid() . '_' . time() . '.pdf';
            $filePath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES['paper_file']['tmp_name'], $filePath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
                exit();
            }
        }

        // Get current user info
        $stmt = $pdo->prepare("SELECT name FROM accounts WHERE username = ?");
        $stmt->execute([$_SESSION['username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $user['name'] ?? $_SESSION['username'];

        // Insert submission into database
        $sql = "INSERT INTO paper_submissions (
            user_name, author_name, author_email, affiliation, co_authors,
            paper_title, abstract, keywords, methodology, funding_source,
            research_start_date, research_end_date, ethics_approval, 
            additional_comments, terms_agreement, email_consent, data_consent,
            research_type, file_path, submission_date, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $userName,
            $authorName,
            $authorEmail,
            $affiliation,
            $coAuthors,
            $paperTitle,
            $abstract,
            $keywords,
            $methodology,
            $fundingSource,
            $researchStartDate ?: null,
            $researchEndDate ?: null,
            $ethicsApproval,
            $additionalComments,
            $termsAgreement,
            $emailConsent,
            $dataConsent,
            $researchType,
            $filePath,
            'pending' // Default status
        ]);

        if ($result) {
            $submissionId = $pdo->lastInsertId();
            
            // Send email notification if consent given
            if ($emailConsent && !empty($authorEmail)) {
                sendSubmissionConfirmation($pdo, $submissionId, $authorEmail, $authorName, $paperTitle, $researchType, $userName);
            }
            
            // Log user activity
            try {
                $activityStmt = $pdo->prepare("
                    INSERT INTO user_activity_logs (user_id, username, activity_type, activity_description, paper_id, created_at) 
                    SELECT id, username, 'submit_paper', ?, ?, NOW() 
                    FROM accounts WHERE username = ?
                ");
                $activityStmt->execute([
                    "Submitted paper: $paperTitle",
                    $submissionId,
                    $_SESSION['username']
                ]);
            } catch (Exception $e) {
                // Activity logging failed, but don't fail the submission
                error_log("Failed to log activity: " . $e->getMessage());
            }
            
            // Create notification for admin
            try {
                $notificationStmt = $pdo->prepare("
                    INSERT INTO submission_notifications (paper_id, user_name, notification_type, message, created_at) 
                    VALUES (?, ?, 'submitted', ?, NOW())
                ");
                $notificationStmt->execute([
                    $submissionId,
                    $userName,
                    "New paper submission: \"$paperTitle\" by $authorName"
                ]);
            } catch (Exception $e) {
                // Notification creation failed, but don't fail the submission
                error_log("Failed to create notification: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Paper submitted successfully',
                'submission_id' => $submissionId,
                'reference_number' => 'CNLRRS-' . date('Y') . '-' . str_pad($submissionId, 6, '0', STR_PAD_LEFT)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit paper']);
        }

    } catch (Exception $e) {
        error_log("Submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred during submission']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function sendSubmissionConfirmation($pdo, $submissionId, $authorEmail, $authorName, $paperTitle, $researchType, $submittedBy) {
    try {
        // Get email template
        $stmt = $pdo->prepare("SELECT subject, body FROM email_templates WHERE template_type = 'enhanced_submission_confirmation' AND is_active = 1 LIMIT 1");
        $stmt->execute();
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            // Fallback to basic confirmation template
            $stmt = $pdo->prepare("SELECT subject, body FROM email_templates WHERE template_type = 'paper_submitted' AND is_active = 1 LIMIT 1");
            $stmt->execute();
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($template) {
            $subject = str_replace([
                '{{paper_title}}',
                '{{author_name}}'
            ], [
                $paperTitle,
                $authorName
            ], $template['subject']);
            
            $body = str_replace([
                '{{paper_title}}',
                '{{author_name}}',
                '{{research_type}}',
                '{{affiliation}}',
                '{{submission_date}}',
                '{{submitted_by}}',
                '{{author_email}}'
            ], [
                $paperTitle,
                $authorName,
                ucfirst(str_replace('_', ' ', $researchType)),
                '', // Affiliation placeholder
                date('F j, Y'),
                $submittedBy,
                $authorEmail
            ], $template['body']);
            
            // Send email (you'll need to implement your email sending logic here)
            // For now, we'll just log that an email should be sent
            error_log("Email confirmation should be sent to $authorEmail: $subject");
            
            // You can integrate with PHPMailer, SendGrid, or your preferred email service here
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
?>