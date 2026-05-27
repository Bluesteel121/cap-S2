<?php
session_start();

// Auth check
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: account.php');
    exit();
}

$current_username = $_SESSION['username'];
$display_name     = $_SESSION['name'] ?? $_SESSION['username'];

require_once 'connect.php';

$paper_id  = (int)($_GET['id'] ?? 0);
$success   = '';
$error     = '';

if (!$paper_id) {
    header('Location: my_submissions.php');
    exit();
}

// ── Fetch paper (must belong to user and be pending) ───────────────────────────
$fetch_sql = "SELECT * FROM paper_submissions 
              WHERE id = ? AND LOWER(user_name) = LOWER(?) AND status = 'pending'";
$stmt = $conn->prepare($fetch_sql);
$stmt->bind_param('is', $paper_id, $current_username);
$stmt->execute();
$paper = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$paper) {
    // Either doesn't exist, belongs to someone else, or not editable status
    header('Location: my_submissions.php?error=not_editable');
    exit();
}

// ── Handle form save ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_paper'])) {
    try {
        // Collect & sanitize text fields
        $author_name          = trim($_POST['author_name']          ?? '');
        $author_email         = trim($_POST['author_email']         ?? '');
        $affiliation          = trim($_POST['affiliation']          ?? '');
        $co_authors           = trim($_POST['co_authors']           ?? '');
        $paper_title          = trim($_POST['paper_title']          ?? '');
        $abstract             = trim($_POST['abstract']             ?? '');
        $keywords             = trim($_POST['keywords']             ?? '');
        $research_type        = trim($_POST['research_type']        ?? 'other');
        $methodology          = trim($_POST['methodology']          ?? '');
        $funding_source       = trim($_POST['funding_source']       ?? '');
        $research_start_date  = trim($_POST['research_start_date']  ?? '') ?: null;
        $research_end_date    = trim($_POST['research_end_date']    ?? '') ?: null;
        $ethics_approval      = trim($_POST['ethics_approval']      ?? '');
        $additional_comments  = trim($_POST['additional_comments']  ?? '');

        // Required field check
        if (!$author_name || !$author_email || !$affiliation || !$paper_title || !$abstract || !$keywords) {
            throw new Exception('Please fill in all required fields.');
        }

        // Abstract length
        if (mb_strlen($abstract) > 2000) {
            throw new Exception('Abstract must not exceed 2000 characters.');
        }

        // Valid research type
        $valid_types = ['experimental','observational','review','case_study','other'];
        if (!in_array($research_type, $valid_types)) {
            $research_type = 'other';
        }

        // ── Handle optional file replacement ──────────────────────────────────
        $new_file_path = $paper['file_path']; // default: keep existing

        if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['pdf', 'doc', 'docx'];
            $file_ext    = strtolower(pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_ext)) {
                throw new Exception('Only PDF, DOC, or DOCX files are allowed.');
            }

            $max_size = 20 * 1024 * 1024; // 20 MB
            if ($_FILES['paper_file']['size'] > $max_size) {
                throw new Exception('File size must not exceed 20 MB.');
            }

            $upload_dir = 'uploads/papers/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_filename  = uniqid() . '_' . time() . '.' . $file_ext;
            $new_file_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['paper_file']['tmp_name'], $new_file_path)) {
                throw new Exception('Failed to upload file. Please try again.');
            }
        }

        // ── Update database ───────────────────────────────────────────────────
        $update_sql = "UPDATE paper_submissions SET
                        author_name          = ?,
                        author_email         = ?,
                        affiliation          = ?,
                        co_authors           = ?,
                        paper_title          = ?,
                        abstract             = ?,
                        keywords             = ?,
                        research_type        = ?,
                        methodology          = ?,
                        funding_source       = ?,
                        research_start_date  = ?,
                        research_end_date    = ?,
                        ethics_approval      = ?,
                        additional_comments  = ?,
                        file_path            = ?,
                        updated_at           = NOW()
                       WHERE id = ? AND LOWER(user_name) = LOWER(?) AND status = 'pending'";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param(
            'ssssssssssssssssis',
            $author_name, $author_email, $affiliation, $co_authors,
            $paper_title, $abstract, $keywords, $research_type,
            $methodology, $funding_source,
            $research_start_date, $research_end_date,
            $ethics_approval, $additional_comments,
            $new_file_path,
            $paper_id, $current_username
        );

        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $stmt->close();

        // Re-fetch updated paper for display
        $stmt2 = $conn->prepare("SELECT * FROM paper_submissions WHERE id = ?");
        $stmt2->bind_param('i', $paper_id);
        $stmt2->execute();
        $paper = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        $success = 'Paper updated successfully!';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Helpers
function resTypeLabel($t) {
    $map = [
        'experimental' => 'Experimental Research',
        'observational' => 'Observational Study',
        'review'        => 'Literature Review',
        'case_study'    => 'Case Study',
        'other'         => 'Other',
    ];
    return $map[$t] ?? ucfirst($t);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Paper – CNLRRS</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --green-dark:  #115D5B;
            --green-mid:   #0d4a47;
            --green-light: #e6f4f3;
            --amber:       #f59e0b;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: var(--green-dark); border-radius: 3px; }

        /* Section cards */
        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .section-card:hover {
            box-shadow: 0 4px 20px rgba(17,93,91,0.1);
        }
        .section-header {
            background: linear-gradient(135deg, var(--green-dark), var(--green-mid));
            color: white;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-header.amber { background: linear-gradient(135deg, #d97706, #b45309); }
        .section-header.blue  { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .section-header.slate { background: linear-gradient(135deg, #475569, #334155); }
        .section-body { padding: 20px; }

        /* Form fields */
        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 5px;
            letter-spacing: 0.01em;
        }
        .field-label .req { color: #ef4444; margin-left: 2px; }
        .field-label .hint { color: #9ca3af; font-weight: 400; font-size: 11px; margin-left: 6px; }

        .field-input {
            width: 100%;
            padding: 10px 13px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            color: #111827;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: white;
        }
        .field-input:focus {
            border-color: var(--green-dark);
            box-shadow: 0 0 0 3px rgba(17,93,91,0.08);
        }
        .field-input::placeholder { color: #d1d5db; }
        textarea.field-input { resize: vertical; min-height: 100px; }

        /* Character counter */
        .char-counter {
            font-size: 11px;
            text-align: right;
            margin-top: 3px;
            transition: color 0.2s;
        }
        .char-ok      { color: #9ca3af; }
        .char-warning { color: #f59e0b; }
        .char-danger  { color: #ef4444; }

        /* File drop zone */
        .dropzone {
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafafa;
        }
        .dropzone:hover, .dropzone.drag-over {
            border-color: var(--green-dark);
            background: var(--green-light);
        }
        .dropzone .dz-icon { font-size: 36px; color: #9ca3af; margin-bottom: 8px; transition: color 0.2s; }
        .dropzone:hover .dz-icon, .dropzone.drag-over .dz-icon { color: var(--green-dark); }

        /* Current file badge */
        .file-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            color: #15803d;
            font-weight: 600;
            max-width: 100%;
            word-break: break-all;
        }

        /* Progress bar */
        .progress-bar { height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--green-dark); border-radius: 2px; transition: width 0.3s; }

        /* Step indicator */
        .steps-bar {
            display: flex;
            gap: 4px;
        }
        .step-dot {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            transition: background 0.3s;
        }
        .step-dot.active   { background: var(--green-dark); }
        .step-dot.complete { background: #10b981; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 22px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,0.15); }
        .btn-primary  { background: var(--green-dark); color: white; }
        .btn-primary:hover { background: var(--green-mid); }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-amber    { background: var(--amber); color: white; }
        .btn-amber:hover { background: #d97706; }
        .btn-lg { padding: 14px 32px; font-size: 15px; border-radius: 10px; }

        /* Toast */
        .toast {
            position: fixed; bottom: 24px; right: 24px; z-index: 9999;
            padding: 14px 20px; border-radius: 10px; color: white;
            font-size: 14px; font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            animation: toastIn 0.3s ease-out;
            max-width: 360px;
            display: flex; align-items: flex-start; gap: 10px;
        }
        @keyframes toastIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .toast-success { background: #115D5B; }
        .toast-error   { background: #ef4444; }

        /* Sticky save bar */
        .save-bar {
            position: sticky;
            bottom: 0;
            z-index: 100;
            background: white;
            border-top: 2px solid #e5e7eb;
            padding: 14px 24px;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.06);
        }

        /* Unsaved indicator */
        .unsaved-dot { width: 8px; height: 8px; background: #f59e0b; border-radius: 50%; display: none; }
        .unsaved-dot.show { display: inline-block; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

        /* Responsive */
        @media (max-width: 640px) {
            .section-body { padding: 14px; }
            .btn-lg { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-teal-50 min-h-screen">

<!-- ── Header ──────────────────────────────────────────────────────────────── -->
<header class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white py-5 px-6 shadow-lg">
    <div class="max-w-5xl mx-auto flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center space-x-4">
            <img src="Images/CNLRRS_icon.png" alt="CNLRRS Logo" class="h-11 w-auto object-contain"
                 onerror="this.style.display='none'">
            <div>
                <h1 class="text-xl font-bold leading-tight">Edit Research Paper</h1>
                <p class="text-sm opacity-70">Paper ID #<?php echo $paper_id; ?> &mdash; <span class="font-medium opacity-90"><?php echo htmlspecialchars($display_name); ?></span></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="my_submissions.php" class="flex items-center gap-2 bg-white bg-opacity-15 hover:bg-opacity-25 px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fas fa-arrow-left"></i>Back to Submissions
            </a>
        </div>
    </div>
</header>

<!-- ── Main ────────────────────────────────────────────────────────────────── -->
<main class="max-w-5xl mx-auto py-8 px-4 pb-4">

    <!-- Page-level messages (PHP redirect flash) -->
    <?php if ($success): ?>
    <div id="successBanner" class="mb-6 flex items-center gap-3 bg-green-50 border border-green-300 text-green-800 px-5 py-4 rounded-xl shadow-sm">
        <i class="fas fa-check-circle text-green-500 text-lg flex-shrink-0"></i>
        <p class="font-semibold"><?php echo htmlspecialchars($success); ?></p>
        <button onclick="this.parentNode.remove()" class="ml-auto text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div id="errorBanner" class="mb-6 flex items-center gap-3 bg-red-50 border border-red-300 text-red-800 px-5 py-4 rounded-xl shadow-sm">
        <i class="fas fa-exclamation-triangle text-red-500 text-lg flex-shrink-0"></i>
        <p class="font-semibold"><?php echo htmlspecialchars($error); ?></p>
        <button onclick="this.parentNode.remove()" class="ml-auto text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <!-- Status banner -->
    <div class="mb-6 flex items-center gap-3 bg-yellow-50 border border-yellow-200 text-yellow-800 px-5 py-3 rounded-xl">
        <i class="fas fa-info-circle text-yellow-500"></i>
        <p class="text-sm"><strong>Editing is only available for Pending papers.</strong> Once a paper moves to review, this page will no longer be accessible.</p>
    </div>

    <form method="POST" enctype="multipart/form-data" id="editForm" novalidate>
        <input type="hidden" name="save_paper" value="1">

        <!-- ── Section 1: Author Info ───────────────────────────────────────── -->
        <div class="section-card mb-6">
            <div class="section-header">
                <i class="fas fa-user-circle text-lg"></i>
                <div>
                    <h2 class="font-bold text-base">Author Information</h2>
                    <p class="text-xs opacity-70 mt-0.5">Primary author and affiliation details</p>
                </div>
            </div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="field-label" for="author_name">
                            Full Name <span class="req">*</span>
                        </label>
                        <input type="text" id="author_name" name="author_name" class="field-input"
                               value="<?php echo htmlspecialchars($paper['author_name']); ?>"
                               placeholder="e.g. Juan dela Cruz" required>
                    </div>

                    <div>
                        <label class="field-label" for="author_email">
                            Author Email <span class="req">*</span>
                        </label>
                        <input type="email" id="author_email" name="author_email" class="field-input"
                               value="<?php echo htmlspecialchars($paper['author_email']); ?>"
                               placeholder="author@institution.edu.ph" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="field-label" for="affiliation">
                            Institutional Affiliation <span class="req">*</span>
                        </label>
                        <input type="text" id="affiliation" name="affiliation" class="field-input"
                               value="<?php echo htmlspecialchars($paper['affiliation']); ?>"
                               placeholder="e.g. CNLRRS, Department of Agriculture" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="field-label" for="co_authors">
                            Co-Authors <span class="hint">(optional)</span>
                        </label>
                        <textarea id="co_authors" name="co_authors" class="field-input" rows="3"
                                  placeholder="List co-authors with their affiliations, one per line&#10;e.g. Maria Santos – PhilRice, Rosario Lim – DA-Region V"><?php echo htmlspecialchars($paper['co_authors'] ?? ''); ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Section 2: Paper Details ────────────────────────────────────── -->
        <div class="section-card mb-6">
            <div class="section-header blue">
                <i class="fas fa-file-alt text-lg"></i>
                <div>
                    <h2 class="font-bold text-base">Paper Details</h2>
                    <p class="text-xs opacity-70 mt-0.5">Title, abstract, keywords, and research type</p>
                </div>
            </div>
            <div class="section-body">
                <div class="space-y-5">

                    <div>
                        <label class="field-label" for="paper_title">
                            Paper Title <span class="req">*</span>
                        </label>
                        <input type="text" id="paper_title" name="paper_title" class="field-input"
                               value="<?php echo htmlspecialchars($paper['paper_title']); ?>"
                               placeholder="Full title of your research paper" required>
                    </div>

                    <div>
                        <label class="field-label" for="abstract">
                            Abstract <span class="req">*</span>
                            <span class="hint">(max 2000 characters)</span>
                        </label>
                        <textarea id="abstract" name="abstract" class="field-input" rows="7"
                                  maxlength="2000"
                                  placeholder="Provide a concise summary of your research..."
                                  required><?php echo htmlspecialchars($paper['abstract']); ?></textarea>
                        <div class="char-counter char-ok" id="abstractCounter">
                            <span id="abstractCount"><?php echo mb_strlen($paper['abstract']); ?></span> / 2000
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>
                            <label class="field-label" for="keywords">
                                Keywords <span class="req">*</span>
                                <span class="hint">(comma-separated)</span>
                            </label>
                            <input type="text" id="keywords" name="keywords" class="field-input"
                                   value="<?php echo htmlspecialchars($paper['keywords']); ?>"
                                   placeholder="e.g. pineapple, antioxidants, biochemistry" required>
                        </div>

                        <div>
                            <label class="field-label" for="research_type">
                                Research Type <span class="req">*</span>
                            </label>
                            <select id="research_type" name="research_type" class="field-input" required>
                                <?php
                                $types = [
                                    'experimental' => 'Experimental Research',
                                    'observational' => 'Observational Study',
                                    'review'        => 'Literature Review',
                                    'case_study'    => 'Case Study',
                                    'other'         => 'Other',
                                ];
                                foreach ($types as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $paper['research_type'] === $val ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ── Section 3: Research Info ────────────────────────────────────── -->
        <div class="section-card mb-6">
            <div class="section-header amber">
                <i class="fas fa-flask text-lg"></i>
                <div>
                    <h2 class="font-bold text-base">Research Details</h2>
                    <p class="text-xs opacity-70 mt-0.5">Methodology, dates, funding, and ethics</p>
                </div>
            </div>
            <div class="section-body">
                <div class="space-y-5">

                    <div>
                        <label class="field-label" for="methodology">
                            Methodology <span class="hint">(optional)</span>
                        </label>
                        <textarea id="methodology" name="methodology" class="field-input" rows="4"
                                  placeholder="Describe the research methodology, design, and approach used..."><?php echo htmlspecialchars($paper['methodology'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                        <div>
                            <label class="field-label" for="funding_source">
                                Funding Source <span class="hint">(optional)</span>
                            </label>
                            <input type="text" id="funding_source" name="funding_source" class="field-input"
                                   value="<?php echo htmlspecialchars($paper['funding_source'] ?? ''); ?>"
                                   placeholder="e.g. DA-RRDE, Self-funded">
                        </div>

                        <div>
                            <label class="field-label" for="research_start_date">
                                Research Start Date <span class="hint">(optional)</span>
                            </label>
                            <input type="date" id="research_start_date" name="research_start_date" class="field-input"
                                   value="<?php echo htmlspecialchars($paper['research_start_date'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="field-label" for="research_end_date">
                                Research End Date <span class="hint">(optional)</span>
                            </label>
                            <input type="date" id="research_end_date" name="research_end_date" class="field-input"
                                   value="<?php echo htmlspecialchars($paper['research_end_date'] ?? ''); ?>">
                        </div>

                    </div>

                    <div>
                        <label class="field-label" for="ethics_approval">
                            Ethics Approval <span class="hint">(optional)</span>
                        </label>
                        <textarea id="ethics_approval" name="ethics_approval" class="field-input" rows="3"
                                  placeholder="Provide ethics committee approval details if applicable..."><?php echo htmlspecialchars($paper['ethics_approval'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="field-label" for="additional_comments">
                            Additional Comments for Reviewers <span class="hint">(optional)</span>
                        </label>
                        <textarea id="additional_comments" name="additional_comments" class="field-input" rows="3"
                                  placeholder="Any additional notes you'd like reviewers to know..."><?php echo htmlspecialchars($paper['additional_comments'] ?? ''); ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Section 4: File Upload ──────────────────────────────────────── -->
        <div class="section-card mb-6">
            <div class="section-header slate">
                <i class="fas fa-file-upload text-lg"></i>
                <div>
                    <h2 class="font-bold text-base">Paper File</h2>
                    <p class="text-xs opacity-70 mt-0.5">Replace or keep the current file (PDF, DOC, DOCX — max 20 MB)</p>
                </div>
            </div>
            <div class="section-body">

                <!-- Current file -->
                <?php if (!empty($paper['file_path'])): ?>
                <div class="mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Current File</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="file-badge">
                            <i class="fas fa-file-pdf text-red-500"></i>
                            <?php echo htmlspecialchars(basename($paper['file_path'])); ?>
                        </span>
                        <a href="<?php echo htmlspecialchars($paper['file_path']); ?>" target="_blank"
                           class="btn btn-secondary text-xs py-2 px-3">
                            <i class="fas fa-external-link-alt"></i> Open
                        </a>
                    </div>
                    <p class="text-xs text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i>Leave the field below empty to keep this file unchanged.</p>
                </div>
                <?php endif; ?>

                <!-- Drop zone -->
                <div class="dropzone" id="dropzone" onclick="document.getElementById('paper_file').click()">
                    <div id="dzContent">
                        <i class="fas fa-cloud-upload-alt dz-icon" id="dzIcon"></i>
                        <p class="text-sm font-semibold text-gray-600" id="dzLabel">Click to choose a new file, or drag & drop here</p>
                        <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX — max 20 MB</p>
                    </div>
                    <input type="file" id="paper_file" name="paper_file"
                           accept=".pdf,.doc,.docx" class="hidden">
                </div>

                <!-- File selected preview -->
                <div id="filePreview" class="hidden mt-3 flex items-center gap-3 bg-teal-50 border border-teal-200 rounded-lg px-4 py-3">
                    <i class="fas fa-file-alt text-[#115D5B] text-lg"></i>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-800 text-sm truncate" id="fileName"></p>
                        <p class="text-xs text-gray-500" id="fileSize"></p>
                    </div>
                    <button type="button" onclick="clearFile()" class="text-red-400 hover:text-red-600 transition">
                        <i class="fas fa-times-circle text-lg"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- ── Sticky Save Bar ──────────────────────────────────────────────── -->
        <div class="save-bar">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="unsaved-dot" id="unsavedDot"></span>
                    <span id="saveStatus">All changes saved</span>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <a href="my_submissions.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                        <i class="fas fa-save"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>

    </form>

</main>

<script>
// ── Abstract counter ────────────────────────────────────────────────────────
const abstractEl = document.getElementById('abstract');
const counterEl  = document.getElementById('abstractCounter');
const countEl    = document.getElementById('abstractCount');

function updateCounter() {
    const len = abstractEl.value.length;
    countEl.textContent = len;
    counterEl.className = 'char-counter ' + (len > 1900 ? 'char-danger' : len > 1700 ? 'char-warning' : 'char-ok');
}
abstractEl.addEventListener('input', updateCounter);
updateCounter();

// ── Unsaved changes indicator ───────────────────────────────────────────────
let isDirty = false;
const dot    = document.getElementById('unsavedDot');
const status = document.getElementById('saveStatus');

document.getElementById('editForm').addEventListener('input', () => {
    if (!isDirty) {
        isDirty = true;
        dot.classList.add('show');
        status.textContent = 'Unsaved changes';
        status.style.color = '#f59e0b';
    }
});

document.getElementById('editForm').addEventListener('submit', () => {
    isDirty = false;
    dot.classList.remove('show');
    status.textContent = 'Saving…';
    status.style.color = '#115D5B';
    document.getElementById('saveBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i>Saving…';
    document.getElementById('saveBtn').disabled = true;
});

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', e => {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
});

// ── File drag & drop ────────────────────────────────────────────────────────
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('paper_file');
const filePreview = document.getElementById('filePreview');
const fileNameEl = document.getElementById('fileName');
const fileSizeEl = document.getElementById('fileSize');
const dzLabel    = document.getElementById('dzLabel');
const dzIcon     = document.getElementById('dzIcon');

dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
dropzone.addEventListener('dragleave', ()  => dropzone.classList.remove('drag-over'));
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
});

fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) handleFile(fileInput.files[0]);
});

function handleFile(file) {
    const allowed = ['application/pdf',
                     'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const extOk = /\.(pdf|doc|docx)$/i.test(file.name);
    if (!allowed.includes(file.type) && !extOk) {
        showToast('Only PDF, DOC, or DOCX files are allowed.', 'error');
        clearFile(); return;
    }
    if (file.size > 20 * 1024 * 1024) {
        showToast('File size must not exceed 20 MB.', 'error');
        clearFile(); return;
    }
    fileNameEl.textContent = file.name;
    fileSizeEl.textContent = formatBytes(file.size);
    filePreview.classList.remove('hidden');
    dzLabel.textContent = 'File selected ✓';
    dzIcon.className = 'fas fa-check-circle dz-icon';
    dzIcon.style.color = '#115D5B';
}

function clearFile() {
    fileInput.value = '';
    filePreview.classList.add('hidden');
    dzLabel.textContent = 'Click to choose a new file, or drag & drop here';
    dzIcon.className = 'fas fa-cloud-upload-alt dz-icon';
    dzIcon.style.color = '';
}

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1024 / 1024).toFixed(2) + ' MB';
}

// ── Date validation ─────────────────────────────────────────────────────────
document.getElementById('research_end_date').addEventListener('change', function() {
    const start = document.getElementById('research_start_date').value;
    if (start && this.value && this.value < start) {
        showToast('End date cannot be before start date.', 'error');
        this.value = '';
    }
});

// ── Client-side required field check ────────────────────────────────────────
document.getElementById('editForm').addEventListener('submit', function(e) {
    const required = [
        { id: 'author_name',   label: 'Author Name' },
        { id: 'author_email',  label: 'Author Email' },
        { id: 'affiliation',   label: 'Affiliation' },
        { id: 'paper_title',   label: 'Paper Title' },
        { id: 'abstract',      label: 'Abstract' },
        { id: 'keywords',      label: 'Keywords' },
    ];
    for (const f of required) {
        const el = document.getElementById(f.id);
        if (!el.value.trim()) {
            e.preventDefault();
            showToast(f.label + ' is required.', 'error');
            el.focus();
            el.style.borderColor = '#ef4444';
            setTimeout(() => el.style.borderColor = '', 2500);
            document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i>Save Changes';
            document.getElementById('saveBtn').disabled = false;
            return;
        }
    }
});

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-triangle' };
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<i class="fas ${icons[type]} flex-shrink-0 mt-0.5"></i><span>${msg}</span>`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; setTimeout(() => t.remove(), 400); }, 4000);
}

// Auto-hide PHP success/error banners after 6 seconds
['successBanner','errorBanner'].forEach(id => {
    const el = document.getElementById(id);
    if (el) setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity 0.5s'; setTimeout(()=>el.remove(),500); }, 6000);
});

<?php if ($success): ?>
showToast('<?php echo addslashes($success); ?>', 'success');
<?php endif; ?>
<?php if ($error): ?>
showToast('<?php echo addslashes($error); ?>', 'error');
<?php endif; ?>
</script>
</body>
</html>