<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Research Paper - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .field-help {
            background: linear-gradient(135deg, #e0f2fe 0%, #f1f8ff 100%);
            border-left: 4px solid #2196F3;
            transition: all 0.3s ease;
        }
        .field-help:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
        }
        .required::after {
            content: " *";
            color: #ef4444;
            font-weight: bold;
        }
        .form-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .form-section:hover {
            box-shadow: 0 8px 25px -2px rgba(0, 0, 0, 0.1);
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
        .section-icon {
            background: linear-gradient(135deg, #115D5B 0%, #0d4a47 100%);
        }
        .help-toggle {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .help-toggle:hover {
            color: #2196F3;
        }
        .character-count {
            font-size: 0.75rem;
            transition: color 0.3s ease;
        }
        .character-warning {
            color: #f59e0b;
        }
        .character-error {
            color: #ef4444;
        }
        .file-drop-zone {
            border: 2px dashed #cbd5e1;
            transition: all 0.3s ease;
        }
        .file-drop-zone.dragover {
            border-color: #115D5B;
            background-color: #f0fdfa;
        }
        .research-type-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .research-type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .research-type-card.selected {
            border-color: #115D5B;
            background-color: #f0fdfa;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white py-6 px-6 shadow-lg">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="Images/Logo.jpg" alt="CNLRRS Logo" class="h-12 w-auto object-contain">
                    <div>
                        <h1 class="text-2xl font-bold">Research Paper Submission</h1>
                        <p class="text-sm opacity-90">Camarines Norte Lowland Rainfed Research Station</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="loggedin_index.php" class="flex items-center space-x-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Home</span>
                    </a>
                    <div class="text-right">
                        <p class="text-sm opacity-75">DOST Format Compliant</p>
                        <p class="text-xs opacity-60">Follow instructions for best results</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Progress Indicator -->
    <div class="max-w-6xl mx-auto px-6 py-4">
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span>Submission Progress</span>
                <span id="progressText">0% Complete</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progressBar" class="progress-bar bg-gradient-to-r from-[#115D5B] to-green-500 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 py-8">
        <!-- Instructions Panel -->
        <div class="form-section p-6 mb-8 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-start space-x-4">
                <div class="section-icon text-white p-3 rounded-full">
                    <i class="fas fa-info-circle text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-3">Submission Requirements</h2>
                    <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-700">
                        <div>
                            <h3 class="font-semibold mb-2">Required Information:</h3>
                               
                               
                                <li>DOST Research  Proposal Template
                                    <a href="Images/worksheet.xlsx" download class="text-blue-600 underline ml-2">
                                         Download Here</a> </li>
                                          
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="paperSubmissionForm" enctype="multipart/form-data">
            <!-- Section 1: Basic Information -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-clipboard text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">1. Basic Information</h2>
                </div>

                <!-- Paper Title -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700 required">Research Paper Title</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('titleHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <input type="text" id="paperTitle" name="paper_title" required maxlength="200"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="Enter the complete title of your research paper">
                    <div class="flex justify-between items-center mt-1">
                        <span id="titleCount" class="character-count text-gray-500">0/200 characters</span>
                    </div>
                    <div id="titleHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Paper Title Guidelines:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>Be Specific:</strong> Clearly state what your research is about</li>
                            <li><strong>Include Keywords:</strong> Use terms that researchers would search for</li>
                            <li><strong>Keep Concise:</strong> Aim for 10-15 words when possible</li>
                            <li><strong>Avoid Jargon:</strong> Use terminology that broader audience can understand</li>
                        </ul>
                        <p class="text-xs text-blue-600 mt-2">
                            <strong>Example:</strong> "Effects of Organic Fertilizers on Rice Yield and Soil Quality in Lowland Areas"
                        </p>
                    </div>
                </div>

                <!-- Research Type -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-gray-700 required">Research Type</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('typeHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('experimental')">
                            <input type="radio" name="research_type" value="experimental" id="experimental" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-flask text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800">Experimental</h3>
                                <p class="text-xs text-gray-600 mt-1">Controlled studies with variables</p>
                            </div>
                        </div>
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('observational')">
                            <input type="radio" name="research_type" value="observational" id="observational" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-eye text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800">Observational</h3>
                                <p class="text-xs text-gray-600 mt-1">Field observations & surveys</p>
                            </div>
                        </div>
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('review')">
                            <input type="radio" name="research_type" value="review" id="review" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-book text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800">Literature Review</h3>
                                <p class="text-xs text-gray-600 mt-1">Analysis of existing research</p>
                            </div>
                        </div>
                        <div class="research-type-card bg-white p-4 rounded-lg shadow-sm" onclick="selectResearchType('case_study')">
                            <input type="radio" name="research_type" value="case_study" id="case_study" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-search text-2xl text-[#115D5B] mb-2"></i>
                                <h3 class="font-semibold text-gray-800">Case Study</h3>
                                <p class="text-xs text-gray-600 mt-1">In-depth specific analysis</p>
                            </div>
                        </div>
                    </div>
                    <div id="typeHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Research Type Selection:</h4>
                        <div class="grid md:grid-cols-2 gap-4 text-sm text-blue-700">
                            <div>
                                <p><strong>Experimental:</strong> You manipulated variables and measured outcomes (e.g., testing different fertilizer types)</p>
                                <p><strong>Observational:</strong> You collected data without manipulating conditions (e.g., monitoring crop growth patterns)</p>
                            </div>
                            <div>
                                <p><strong>Literature Review:</strong> You analyzed and synthesized existing research papers on a topic</p>
                                <p><strong>Case Study:</strong> You conducted detailed analysis of specific instances or locations</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Keywords -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700 required">Keywords</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('keywordHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <input type="text" id="keywords" name="keywords" required maxlength="500"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="Enter 5-8 keywords separated by commas">
                    <div class="flex justify-between items-center mt-1">
                        <span id="keywordCount" class="character-count text-gray-500">0/500 characters</span>
                        <span id="keywordWordCount" class="character-count text-gray-500">0 keywords</span>
                    </div>
                    <div id="keywordHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Keyword Guidelines:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>5-8 Keywords:</strong> Provide between 5-8 relevant terms</li>
                            <li><strong>Specific Terms:</strong> Use precise scientific terms, not general words</li>
                            <li><strong>Mix Levels:</strong> Include both broad and specific terms</li>
                            <li><strong>Separate by Commas:</strong> Use commas to separate each keyword</li>
                        </ul>
                        <p class="text-xs text-blue-600 mt-2">
                            <strong>Example:</strong> rice cultivation, organic fertilizer, soil fertility, crop yield, sustainable agriculture, lowland farming
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Author Information -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-users text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">2. Author Information</h2>
                </div>

                <!-- Primary Author -->
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-700 required">Primary Author Full Name</label>
                            <button type="button" class="help-toggle" onclick="toggleHelp('authorHelp')">
                                <i class="fas fa-question-circle text-gray-400"></i>
                            </button>
                        </div>
                        <input type="text" id="authorName" name="author_name" required maxlength="100"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                               placeholder="Dr. Juan A. Dela Cruz">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 required mb-2">Primary Author Email</label>
                        <input type="email" id="authorEmail" name="author_email" required maxlength="100"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                               placeholder="juan.delacruz@institution.edu.ph">
                    </div>
                </div>

                <!-- Author Affiliation -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700 required">Primary Author Affiliation</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('affiliationHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <input type="text" id="affiliation" name="affiliation" required maxlength="200"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="Department of Agriculture, University of the Philippines Los Baños">
                    <div id="affiliationHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Affiliation Format:</h4>
                        <p class="text-sm text-blue-700">Include your department, institution, and location if relevant.</p>
                        <p class="text-xs text-blue-600 mt-2">
                            <strong>Format:</strong> Department/Unit, Institution Name, City (if needed)
                        </p>
                    </div>
                </div>

                <!-- Co-Authors -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Co-Authors (Optional)</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('coauthorHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <textarea id="coAuthors" name="co_authors" rows="3" maxlength="500"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="List co-authors with their affiliations"></textarea>
                    <span id="coAuthorCount" class="character-count text-gray-500 block mt-1">0/500 characters</span>
                    <div id="coauthorHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Co-Author Information:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>List each co-author on a separate line</li>
                            <li>Include their affiliation after their name</li>
                            <li>List in order of contribution to the research</li>
                        </ul>
                        <p class="text-xs text-blue-600 mt-2">
                            <strong>Example:</strong><br>
                            Dr. Maria Santos - Department of Soil Science, UPLB<br>
                            Prof. Roberto Mendoza - CNLRRS, Camarines Norte
                        </p>
                    </div>
                </div>

                <div id="authorHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                    <h4 class="font-semibold text-blue-800 mb-2">Author Name Format:</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>Include academic titles (Dr., Prof., etc.) if applicable</li>
                        <li>Use full name, not initials only</li>
                        <li>Include middle initial or name if commonly used</li>
                        <li>Ensure spelling matches your official documents</li>
                    </ul>
                </div>
            </div>

            <!-- Section 3: Abstract & Research Details -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-file-alt text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">3. Abstract & Research Details</h2>
                </div>

                <!-- Abstract -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700 required">Abstract</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('abstractHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <textarea id="abstract" name="abstract" rows="8" required maxlength="2000"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="Provide a comprehensive abstract of your research..."></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <span id="abstractCount" class="character-count text-gray-500">0/2000 characters</span>
                        <span id="abstractWordCount" class="character-count text-gray-500">0 words</span>
                    </div>
                    <div id="abstractHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Abstract Structure (200-400 words):</h4>
                        <div class="text-sm text-blue-700 space-y-2">
                            <p><strong>1. Background/Problem (1-2 sentences):</strong> What issue does your research address?</p>
                            <p><strong>2. Objective (1 sentence):</strong> What was the main goal of your study?</p>
                            <p><strong>3. Methods (2-3 sentences):</strong> How did you conduct the research?</p>
                            <p><strong>4. Results (2-4 sentences):</strong> What were your main findings?</p>
                            <p><strong>5. Conclusion (1-2 sentences):</strong> What do your results mean?</p>
                        </div>
                        <p class="text-xs text-blue-600 mt-2">
                            <strong>Tip:</strong> Write the abstract after completing your paper, summarizing each major section.
                        </p>
                    </div>
                </div>

                <!-- Research Methodology -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Research Methodology (Optional)</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('methodHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <textarea id="methodology" name="methodology" rows="4" maxlength="1000"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="Brief description of your research methods and approach..."></textarea>
                    <span id="methodCount" class="character-count text-gray-500 block mt-1">0/1000 characters</span>
                    <div id="methodHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Methodology Summary:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>Study Design:</strong> Experimental, observational, etc.</li>
                            <li><strong>Location:</strong> Where was the study conducted?</li>
                            <li><strong>Duration:</strong> How long did the study take?</li>
                            <li><strong>Key Methods:</strong> Main techniques or approaches used</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 4: File Upload -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-cloud-upload-alt text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">4. Upload Research Proposal</h2>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-semibold text-gray-700 ">  <li>Submit PDF format only</li>
                        <li>Maximum file size: 25MB</li>
                       </label>
                       
                        
                        <button type="button" class="help-toggle" onclick="toggleHelp('fileHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    
                    <div class="file-drop-zone p-8 border-2 border-dashed rounded-lg text-center transition" 
                         ondrop="dropHandler(event)" ondragover="dragOverHandler(event)" ondragleave="dragLeaveHandler(event)">
                        <i class="fas fa-file-pdf text-4xl text-gray-400 mb-4"></i>
                        <p class="text-lg font-semibold text-gray-700 mb-2">Drop your PDF file here</p>
                        <p class="text-sm text-gray-500 mb-4">or click to browse</p>
                        <input type="file" id="paperFile" name="paper_file" accept=".pdf" required
                               class="hidden" onchange="fileSelected(event)">
                        <button type="button" onclick="document.getElementById('paperFile').click()"
                                class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-lg transition">
                            Choose File
                        </button>
                    </div>
                    
                    <div id="fileInfo" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-pdf text-red-500"></i>
                                <span id="fileName" class="font-semibold text-gray-700"></span>
                                <span id="fileSize" class="text-sm text-gray-500"></span>
                            </div>
                            <button type="button" onclick="removeFile()" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="uploadProgress" class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <div id="fileHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">File Requirements:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>Format:</strong> PDF only (no Word documents or other formats)</li>
                            <li><strong>Size:</strong> Maximum 25MB file size</li>
                            <li><strong>Content:</strong> Complete paper with all sections, figures, and references</li>
                            <li><strong>Quality:</strong> Clear, readable text and images</li>
                            <li><strong>Structure:</strong> Follow standard academic paper format</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 5: Additional Information -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-plus-circle text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">5. Additional Information</h2>
                </div>

                <!-- Funding Source -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Funding Source (Optional)</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('fundingHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <input type="text" id="fundingSource" name="funding_source" maxlength="200"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                           placeholder="e.g., DOST-PCAARRD, University Research Grant">
                    <div id="fundingHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Funding Information:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>List the main funding agency or organization</li>
                            <li>Include grant number if available</li>
                            <li>If self-funded or institutional support, mention that</li>
                            <li>Leave blank if no specific funding was received</li>
                        </ul>
                    </div>
                </div>

                <!-- Research Duration -->
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-700">Research Start Date</label>
                            <button type="button" class="help-toggle" onclick="toggleHelp('durationHelp')">
                                <i class="fas fa-question-circle text-gray-400"></i>
                            </button>
                        </div>
                        <input type="date" id="startDate" name="research_start_date"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Research End Date</label>
                        <input type="date" id="endDate" name="research_end_date"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition">
                    </div>
                </div>

                <div id="durationHelp" class="field-help p-4 rounded-lg mb-6 hidden">
                    <h4 class="font-semibold text-blue-800 mb-2">Research Duration:</h4>
                    <p class="text-sm text-blue-700">Provide the actual dates when data collection and analysis were conducted. This helps reviewers understand the timeline and seasonal factors that may have influenced your results.</p>
                </div>

                <!-- Ethics Approval -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Ethics Approval/Permits</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('ethicsHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <textarea id="ethicsApproval" name="ethics_approval" rows="3" maxlength="500"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="List any ethics approvals, permits, or clearances obtained for this research..."></textarea>
                    <span id="ethicsCount" class="character-count text-gray-500 block mt-1">0/500 characters</span>
                    <div id="ethicsHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Ethics and Permits:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><strong>Human Subjects:</strong> IRB/Ethics committee approval</li>
                            <li><strong>Animal Studies:</strong> Animal care and use committee approval</li>
                            <li><strong>Field Research:</strong> Land use permits, local government clearances</li>
                            <li><strong>Biological Samples:</strong> Collection and transport permits</li>
                        </ul>
                        <p class="text-xs text-blue-600 mt-2">Include approval numbers and issuing institutions if applicable.</p>
                    </div>
                </div>

                <!-- Additional Comments -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-semibold text-gray-700">Additional Comments (Optional)</label>
                        <button type="button" class="help-toggle" onclick="toggleHelp('commentsHelp')">
                            <i class="fas fa-question-circle text-gray-400"></i>
                        </button>
                    </div>
                    <textarea id="additionalComments" name="additional_comments" rows="4" maxlength="1000"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-transparent transition"
                              placeholder="Any additional information you would like the reviewers to know..."></textarea>
                    <span id="commentsCount" class="character-count text-gray-500 block mt-1">0/1000 characters</span>
                    <div id="commentsHelp" class="field-help p-4 rounded-lg mt-3 hidden">
                        <h4 class="font-semibold text-blue-800 mb-2">Additional Comments:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>Unique challenges or limitations encountered</li>
                            <li>Special circumstances affecting the research</li>
                            <li>Significance or potential impact of the findings</li>
                            <li>Suggestions for future research directions</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="form-section p-6 mb-8">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="section-icon text-white p-3 rounded-full">
                        <i class="fas fa-shield-alt text-lg"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">6. Terms and Submission Agreement</h2>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Submission Guidelines and Agreement</h3>
                    <div class="text-sm text-gray-700 space-y-3">
                        <p><strong>By submitting this research paper, you confirm that:</strong></p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>This work is original and has not been published elsewhere</li>
                            <li>All co-authors have agreed to this submission</li>
                            <li>The research was conducted ethically and with proper approvals</li>
                            <li>You have the right to submit this work for review</li>
                            <li>All sources and references are properly cited</li>
                            <li>The data and findings are accurate to the best of your knowledge</li>
                        </ul>
                        <p class="mt-4"><strong>Review Process:</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-4">
                            <li>Your submission will be reviewed by CNLRRS experts</li>
                            <li>You will receive email notifications about status updates</li>
                            <li>Reviewers may request revisions or additional information</li>
                            <li>Review timeline is typically 2-4 weeks</li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" id="termsAgree" name="terms_agreement" required
                               class="mt-1 h-5 w-5 text-[#115D5B] border-2 border-gray-300 rounded focus:ring-2 focus:ring-[#115D5B]">
                        <span class="text-sm text-gray-700">
                            <span class="font-semibold required">I agree to the submission terms and conditions</span> listed above and confirm that all information provided is accurate and complete.
                        </span>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" id="emailConsent" name="email_consent"
                               class="mt-1 h-5 w-5 text-[#115D5B] border-2 border-gray-300 rounded focus:ring-2 focus:ring-[#115D5B]">
                        <span class="text-sm text-gray-700">
                            I consent to receive email notifications about my submission status and related communications from CNLRRS.
                        </span>
                    </label>

                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" id="dataConsent" name="data_consent"
                               class="mt-1 h-5 w-5 text-[#115D5B] border-2 border-gray-300 rounded focus:ring-2 focus:ring-[#115D5B]">
                        <span class="text-sm text-gray-700">
                            I understand that my submission data will be stored securely and used only for review purposes and research database management.
                        </span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" id="submitBtn" disabled
                        class="bg-gradient-to-r from-[#115D5B] to-green-600 hover:from-[#0d4a47] hover:to-green-700 disabled:from-gray-400 disabled:to-gray-500 text-white px-12 py-4 rounded-lg font-bold text-lg shadow-lg transition-all duration-300 transform hover:scale-105 disabled:transform-none disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane mr-3"></i>
                    Submit Research Paper
                </button>
                <p class="text-sm text-gray-500 mt-4">
                    Please review all information carefully before submitting. You will receive a confirmation email once your submission is received.
                </p>
            </div>
        </form>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Submission Successful!</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Your research paper has been submitted successfully. You will receive a confirmation email shortly with your submission reference number.
                </p>
                <button onclick="closeSuccessModal()" 
                        class="bg-[#115D5B] hover:bg-[#0d4a47] text-white px-6 py-2 rounded-lg transition">
                    Continue
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-[#115D5B] to-[#0d4a47] text-white text-center py-8 mt-12">
        <div class="max-w-6xl mx-auto px-6">
            <p class="text-lg font-semibold mb-2">Camarines Norte Lowland Rainfed Research Station</p>
            <p class="text-sm opacity-75">Supporting agricultural research and development in the Philippines</p>
            <p class="text-xs opacity-60 mt-2">&copy; 2025 CNLRRS. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Form validation and progress tracking
        let formProgress = 0;
        const totalFields = 8; // Main required fields

        // Character counting functions
        function setupCharacterCounters() {
            const counters = [
                { input: 'paperTitle', counter: 'titleCount', max: 200 },
                { input: 'keywords', counter: 'keywordCount', max: 500, wordCounter: 'keywordWordCount' },
                { input: 'abstract', counter: 'abstractCount', max: 2000, wordCounter: 'abstractWordCount' },
                { input: 'coAuthors', counter: 'coAuthorCount', max: 500 },
                { input: 'methodology', counter: 'methodCount', max: 1000 },
                { input: 'ethicsApproval', counter: 'ethicsCount', max: 500 },
                { input: 'additionalComments', counter: 'commentsCount', max: 1000 }
            ];

            counters.forEach(({ input, counter, max, wordCounter }) => {
                const inputEl = document.getElementById(input);
                const counterEl = document.getElementById(counter);
                
                if (inputEl && counterEl) {
                    inputEl.addEventListener('input', function() {
                        const count = this.value.length;
                        counterEl.textContent = `${count}/${max} characters`;
                        
                        // Color coding for character limits
                        if (count > max * 0.9) {
                            counterEl.className = 'character-count character-error';
                        } else if (count > max * 0.7) {
                            counterEl.className = 'character-count character-warning';
                        } else {
                            counterEl.className = 'character-count text-gray-500';
                        }
                        
                        // Word counting for specific fields
                        if (wordCounter) {
                            const words = this.value.trim().split(/\s+/).filter(word => word.length > 0);
                            document.getElementById(wordCounter).textContent = `${words.length} ${wordCounter.includes('keyword') ? 'keywords' : 'words'}`;
                        }
                        
                        updateProgress();
                    });
                }
            });
        }

        // Progress tracking
        function updateProgress() {
            const requiredFields = [
                'paperTitle', 'keywords', 'authorName', 'authorEmail', 
                'affiliation', 'abstract', 'paperFile', 'termsAgree'
            ];
            
            let completed = 0;
            let totalRequired = requiredFields.length + 1; // +1 for research type
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    if (field.type === 'checkbox') {
                        if (field.checked) completed++;
                    } else if (field.type === 'file') {
                        if (field.files.length > 0) completed++;
                    } else {
                        if (field.value.trim()) completed++;
                    }
                }
            });
            
            // Check research type selection
            const researchTypes = document.querySelectorAll('input[name="research_type"]');
            let researchTypeSelected = false;
            for (let radio of researchTypes) {
                if (radio.checked) {
                    researchTypeSelected = true;
                    break;
                }
            }
            if (researchTypeSelected) completed++;
            
            const progress = Math.round((completed / totalRequired) * 100);
            document.getElementById('progressBar').style.width = `${progress}%`;
            document.getElementById('progressText').textContent = `${progress}% Complete`;
            
            // Enable submit button when form is complete
            const submitBtn = document.getElementById('submitBtn');
            if (completed === totalRequired) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('disabled:from-gray-400', 'disabled:to-gray-500');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('disabled:from-gray-400', 'disabled:to-gray-500');
            }
        }

        // Research type selection
        function selectResearchType(type) {
            // Remove selected class from all cards
            document.querySelectorAll('.research-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById(type).checked = true;
            
            updateProgress();
        }

        // Help toggle function
        function toggleHelp(helpId) {
            const helpDiv = document.getElementById(helpId);
            helpDiv.classList.toggle('hidden');
        }

        // File handling functions
        function dragOverHandler(event) {
            event.preventDefault();
            event.currentTarget.classList.add('dragover');
        }

        function dragLeaveHandler(event) {
            event.currentTarget.classList.remove('dragover');
        }

        function dropHandler(event) {
            event.preventDefault();
            event.currentTarget.classList.remove('dragover');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('paperFile');
                fileInput.files = files;
                fileSelected({ target: fileInput });
            }
        }

        function fileSelected(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.type !== 'application/pdf') {
                    alert('Please select a PDF file only.');
                    event.target.value = '';
                    return;
                }
                
                if (file.size > 25 * 1024 * 1024) { // 25MB
                    alert('File size must be less than 25MB.');
                    event.target.value = '';
                    return;
                }
                
                // Show file info
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                document.getElementById('fileInfo').classList.remove('hidden');
                
                updateProgress();
            }
        }

        function removeFile() {
            document.getElementById('paperFile').value = '';
            document.getElementById('fileInfo').classList.add('hidden');
            updateProgress();
        }

        // Form submission
        document.getElementById('paperSubmissionForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Simulate submission (replace with actual submission logic)
            setTimeout(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Show success modal
                document.getElementById('successModal').classList.remove('hidden');
                document.getElementById('successModal').classList.add('flex');
                
                // Reset form
                this.reset();
                document.getElementById('fileInfo').classList.add('hidden');
                document.querySelectorAll('.research-type-card').forEach(card => {
                    card.classList.remove('selected');
                });
                updateProgress();
            }, 2000);
        });

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            document.getElementById('successModal').classList.remove('flex');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupCharacterCounters();
            updateProgress();
            
            // Add event listeners for checkboxes and other form elements
            document.querySelectorAll('input, textarea, select').forEach(element => {
                element.addEventListener('change', updateProgress);
                element.addEventListener('input', updateProgress);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeSuccessModal();
                // Close all help sections
                document.querySelectorAll('.field-help').forEach(help => {
                    help.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>