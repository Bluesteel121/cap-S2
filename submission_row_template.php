<div class="submission-row p-6 transition-all duration-200">
    <div class="flex flex-wrap lg:flex-nowrap justify-between items-start gap-4">
        <!-- Paper Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <h4 class="text-lg font-semibold text-gray-900 mb-1">
    <?php echo htmlspecialchars($submission['paper_title']); ?>
</h4>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-2">
                        <span><strong>Type:</strong> <?php echo getResearchTypeDisplay($submission['research_type']); ?></span>
                        <span><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></span>
                        <?php if ($submission['total_views'] > 0 || $submission['total_downloads'] > 0): ?>
                            <span class="flex items-center space-x-3">
                                <span class="flex items-center"><i class="fas fa-eye text-blue-500 mr-1"></i><?php echo $submission['total_views']; ?></span>
                                <span class="flex items-center"><i class="fas fa-download text-green-500 mr-1"></i><?php echo $submission['total_downloads']; ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($submission['status']); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $submission['status'])); ?>
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                <div>
                    <p><strong>Primary Author:</strong> <?php echo htmlspecialchars($submission['author_name']); ?></p>
                    <?php if (!empty($submission['author_email'])): ?>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['author_email']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($submission['affiliation'])): ?>
                        <p><strong>Affiliation:</strong> <?php echo htmlspecialchars($submission['affiliation']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($submission['co_authors']): ?>
                        <p><strong>Co-authors:</strong> <?php echo htmlspecialchars($submission['co_authors']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($submission['funding_source'])): ?>
                        <p><strong>Funding:</strong> <?php echo htmlspecialchars($submission['funding_source']); ?></p>
                    <?php endif; ?>
                    <?php if ($submission['research_start_date'] && $submission['research_end_date']): ?>
                        <p><strong>Research Period:</strong> 
                           <?php echo date('M Y', strtotime($submission['research_start_date'])); ?> - 
                           <?php echo date('M Y', strtotime($submission['research_end_date'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-3">
                <p class="text-sm"><strong>Keywords:</strong> <?php echo htmlspecialchars($submission['keywords']); ?></p>
            </div>
            
            <?php if ($submission['reviewer_comments']): ?>
                <div class="mt-4 p-4 bg-gray-50 rounded-lg border-l-4 border-blue-400">
                    <p class="text-sm font-semibold text-gray-700 mb-1">Reviewer Comments:</p>
                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($submission['reviewer_comments'])); ?></p>
                    <?php if ($submission['reviewed_by']): ?>
                        <p class="text-xs text-gray-500 mt-2">
                            Reviewed by: <?php echo htmlspecialchars($submission['reviewed_by']); ?>
                            <?php if ($submission['review_date']): ?>
                                on <?php echo date('M j, Y', strtotime($submission['review_date'])); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-2 min-w-40">
            <button onclick="viewPaper(<?php echo $submission['id']; ?>)" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center">
                <i class="fas fa-eye mr-2"></i>View Details
            </button>
            
            <?php if ($submission['file_path']): ?>
                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank"
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
            <?php endif; ?>
            
            <?php if ($submission['status'] === 'pending'): ?>
                <button onclick="editPaper(<?php echo $submission['id']; ?>)" 
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-edit mr-2"></i>Edit
                </button>
            <?php endif; ?>
            
            <?php if ($submission['status'] === 'published'): ?>
                <a href="view_paper.php?id=<?php echo $submission['id']; ?>" target="_blank"
                   class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-external-link-alt mr-2"></i>View Published
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>