<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div>
    <?php if(session('success')): ?>
        <div class="module-alert module-alert-success mb-4">
            <span><?php echo e(session('success')); ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <h6 class="font-weight-bold mb-4">Quick Create</h6>
    <div class="quick-create-grid mb-5">
        <?php
            $actions = [
                ['bi bi-person-plus-fill', 'Client', 'modal', 'dashboardClientModal'],
                ['bi bi-briefcase-fill', 'Case', 'modal', 'dashboardCaseModal'],
                ['bi bi-stopwatch-fill', 'Time', 'modal', 'dashboardTimeModal'],
                ['bi bi-calendar-plus-fill', 'Event', 'modal', 'dashboardEventModal'],
                ['bi bi-folder2-open', 'Doc', 'modal', 'dashboardDocumentModal'],
            ];
        ?>

        <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="quick-create-item">
                <?php if(($action[2] ?? null) === 'modal'): ?>
                    <button type="button" class="btn btn-action w-100 py-4" onclick="openDashboardModal('<?php echo e($action[3]); ?>')">
                        <span class="action-icon"><i class="<?php echo e($action[0]); ?>"></i></span>
                        <?php echo e($action[1]); ?>

                    </button>
                <?php else: ?>
                    <a href="<?php echo e($action[2]); ?>" class="btn btn-action w-100 py-4">
                        <span class="action-icon"><i class="<?php echo e($action[0]); ?>"></i></span>
                        <?php echo e($action[1]); ?>

                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="summary-card h-100">
                        <p class="mb-1 small opacity-75">Active Cases</p>
                        <h1 class="display-4 font-weight-bold"><?php echo e(number_format($activeCases)); ?></h1>
                        <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.1);">
                            <div class="progress-bar bg-white" style="width: <?php echo e(min(($activeCases / 50) * 100, 100)); ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4 h-100">
                        <h6 class="font-weight-bold text-muted small uppercase">Activity Trend</h6>
                        <canvas id="activityChart" style="max-height: 120px;"></canvas>
                    </div>
                </div>
            </div>

            <div class="glass-card ai-assistant-card mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-4">
                    <div>
                        <span class="module-kicker">AI Planning Assistant</span>
                        <h5 class="font-weight-bold mb-1 mt-3">Suggested day plan</h5>
                        <p class="text-muted mb-0" id="aiAssistantSummary"><?php echo e($aiSummary ?? 'A smart daily summary based on current hearings, active cases, documents, and recorded work.'); ?></p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="ai-assistant-badge mr-2" id="aiAssistantBadge"><i class="bi bi-stars mr-2"></i>Prototype</span>
                        <button type="button" class="btn btn-primary btn-sm" id="generateAiSuggestionsBtn">
                            <i class="bi bi-magic mr-1"></i>Generate Suggestions
                        </button>
                    </div>
                </div>

                <div class="ai-assistant-empty" id="aiAssistantEmptyState">
                    <i class="bi bi-stars"></i>
                    <strong>Ready to plan your day</strong>
                    <p class="mb-0">Generate a suggested task list based on your current hearings, cases, documents, and work logs.</p>
                </div>

                <div class="ai-assistant-status d-none" id="aiAssistantStatus"></div>

                <div class="ai-suggestion-list d-none" id="aiSuggestionList">
                    <?php $__empty_1 = true; $__currentLoopData = $aiSuggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $suggestion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="ai-suggestion-item">
                            <div class="ai-suggestion-icon">
                                <i class="bi bi-magic"></i>
                            </div>
                            <div class="ai-suggestion-copy">
                                <span class="ai-suggestion-slot"><?php echo e($suggestion['slot'] ?? 'Suggested Block'); ?></span>
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <strong><?php echo e($suggestion['title']); ?></strong>
                                    <span class="ai-suggestion-timing"><?php echo e($suggestion['timing']); ?></span>
                                </div>
                                <p class="mb-1"><?php echo e($suggestion['detail']); ?></p>
                                <small class="ai-suggestion-reason">Why this matters: <?php echo e($suggestion['reason'] ?? 'It helps structure the day with clearer priorities.'); ?></small>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="time-log-empty">
                            <i class="bi bi-stars"></i>
                            <strong>No AI suggestions yet</strong>
                            <p>As more cases, hearings, and time records are added, the assistant can generate a more useful daily plan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="schedule-timer-card mb-4">
                <div class="schedule-timer-copy">
                    <div class="schedule-timer-icon">
                        <i class="bi bi-stopwatch-fill"></i>
                    </div>
                    <div>
                        <span class="schedule-timer-kicker">Active Work Session</span>
                        <h4 class="font-weight-bold mb-1">Keep today's legal work in view</h4>
                        <p class="mb-0">Use the topbar timer for live tracking, while this overview card keeps the current workload visible from the dashboard.</p>
                    </div>
                </div>
                <div class="schedule-timer-actions">
                    <div class="schedule-session-summary">
                        <div class="schedule-session-stat">
                            <span>Month Focus</span>
                            <strong><?php echo e(now()->format('F')); ?></strong>
                        </div>
                        <div class="schedule-session-stat">
                            <span>Hearings</span>
                            <strong><?php echo e(collect($hearings)->count()); ?></strong>
                        </div>
                        <div class="schedule-session-stat">
                            <span>Documents</span>
                            <strong><?php echo e($documentsCount); ?></strong>
                        </div>
                    </div>
                    <div class="schedule-timer-buttons">
                        <a href="<?php echo e(route('schedule')); ?>" class="btn btn-light btn-sm">Open Calendar</a>
                        <a href="<?php echo e(route('schedule', ['tab' => 'time'])); ?>" class="btn btn-primary btn-sm">Review Timer</a>
                    </div>
                </div>
            </div>

            <div class="glass-card mb-4">
                <div class="p-4 border-bottom d-flex justify-content-between">
                    <h6 class="font-weight-bold mb-0">Upcoming Court Hearings</h6>
                    <a href="<?php echo e(route('schedule')); ?>" class="btn btn-sm btn-link font-weight-bold p-0">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="tiny uppercase font-weight-bold">
                            <tr>
                                <th class="border-0 px-4">Time</th>
                                <th class="border-0">Case Name</th>
                                <th class="border-0 text-right px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $hearings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $hearing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-4 font-weight-bold">
                                        <?php echo e(isset($hearing['hearing_time']) ? \Carbon\Carbon::parse($hearing['hearing_time'])->format('h:i A') : 'N/A'); ?>

                                    </td>
                                    <td><?php echo e($hearing['case_name'] ?? 'Untitled Case'); ?></td>
                                    <td class="text-right px-4">
                                        <span class="badge-status-<?php echo e(($hearing['status'] ?? '') === 'Confirmed' ? 'green' : 'orange'); ?>">
                                            <?php echo e($hearing['status'] ?? 'Scheduled'); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <div style="font-size: 2rem; opacity: 0.3;"><i class="bi bi-folder2-open"></i></div>
                                        <p class="mb-0">No upcoming hearings scheduled.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="font-weight-bold mb-0">Recent Documents</h6>
                    <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-sm btn-link font-weight-bold p-0">Open</a>
                </div>
                <div class="doc-list">
                    <?php $__empty_1 = true; $__currentLoopData = $recentDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $extension = strtolower(pathinfo($document['original_name'] ?? '', PATHINFO_EXTENSION));
                            $docStyles = match ($extension) {
                                'pdf' => ['bg-primary', 'bi bi-file-earmark-pdf-fill'],
                                'doc', 'docx' => ['bg-info', 'bi bi-file-earmark-word-fill'],
                                'txt' => ['bg-secondary', 'bi bi-file-earmark-text-fill'],
                                default => ['bg-warning', 'bi bi-paperclip'],
                            };
                        ?>
                        <div class="doc-item d-flex align-items-center mb-3 p-3 rounded border">
                            <div class="icon-circle <?php echo e($docStyles[0]); ?> text-white mr-3"><i class="<?php echo e($docStyles[1]); ?>"></i></div>
                            <div class="flex-grow-1">
                                <div class="small font-weight-bold"><?php echo e($document['name'] ?? 'Untitled'); ?></div>
                                <div class="text-muted tiny">
                                    <?php echo e(strtoupper($extension ?: 'FILE')); ?>

                                    <?php if(!empty($document['created_at'])): ?>
                                        | <?php echo e(\Carbon\Carbon::parse($document['created_at'])->format('d M Y')); ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="time-log-empty">
                            <i class="bi bi-folder2-open"></i>
                            <strong>No documents uploaded yet</strong>
                            <p>Use the quick create document button to start storing legal files in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="font-weight-bold mb-0">Recent Time Records</h6>
                    <span class="tiny text-muted uppercase" id="timeFilterCount"><?php echo e(count($recentTimeEntryData ?? [])); ?> entries</span>
                </div>
                <div class="mb-3">
                    <select id="timeLogFilter" class="form-control form-control-pill" style="max-width: 220px;">
                        <option value="all">All Activities</option>
                        <option value="Document Review">Document Review</option>
                        <option value="Client Interview">Client Interview</option>
                        <option value="Case Preparation">Case Preparation</option>
                        <option value="Legal Research">Legal Research</option>
                        <option value="Court Preparation">Court Preparation</option>
                        <option value="Admin Work">Admin Work</option>
                    </select>
                </div>
                <div class="time-log-list" id="timeLogList"></div>
            </div>

            <div class="glass-card p-4 mt-3">
                <h6 class="font-weight-bold mb-3">System Snapshot</h6>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center py-3">
                            <div class="h4 font-weight-bold text-primary mb-1"><?php echo e($clientsCount); ?></div>
                            <small class="text-muted">Total Clients</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center py-3">
                            <div class="h5 font-weight-bold mb-1"><?php echo e($documentsCount); ?></div>
                            <small class="text-muted">Documents Stored</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="time-entry-panel" id="timeEntryPanel">
        <div class="time-entry-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="time-entry-kicker">Timer Stopped</span>
                    <h5 class="font-weight-bold mb-1">Save Time Record</h5>
                    <p class="text-muted small mb-0">Capture what the lawyer was working on before resetting the timer.</p>
                </div>
                <button type="button" class="time-entry-close" onclick="closeTimeEntryPanel()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="timeEntryForm">
                <div class="time-entry-grid">
                    <div class="time-entry-field">
                        <label>Recorded Duration</label>
                        <input type="text" id="timeEntryDuration" readonly>
                    </div>
                    <div class="time-entry-field">
                        <label>Activity Type <span class="text-danger">*</span></label>
                        <select id="timeEntryActivity" required>
                            <option value="">Select activity</option>
                            <option>Document Review</option>
                            <option>Client Interview</option>
                            <option>Case Preparation</option>
                            <option>Legal Research</option>
                            <option>Court Preparation</option>
                            <option>Admin Work</option>
                        </select>
                    </div>
                    <div class="time-entry-field">
                        <label>Related Case</label>
                        <select id="timeEntryCase" required>
                            <option value="">Select case</option>
                            <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($caseOption->id); ?>"><?php echo e($caseOption->case_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="time-entry-field">
                        <label>Billable?</label>
                        <div class="time-entry-billable-toggle" role="radiogroup" aria-label="Billable status">
                            <input class="time-entry-toggle-input" type="radio" name="billable" id="billableYes" value="yes" checked>
                            <label class="time-entry-toggle-option" for="billableYes">Billable</label>

                            <input class="time-entry-toggle-input" type="radio" name="billable" id="billableNo" value="no">
                            <label class="time-entry-toggle-option" for="billableNo">Non-Billable</label>
                        </div>
                    </div>
                    <div class="time-entry-field full">
                        <label>Session Notes <span class="text-danger">*</span></label>
                        <textarea id="timeEntryNotes" required placeholder="Brief description of work" rows="4"></textarea>
                        <small class="text-muted">Required for records</small>
                    </div>
                </div>

                <div class="time-entry-actions">
                    <button type="button" class="btn btn-light px-4" onclick="discardTimeEntry()">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" id="saveTimeBtn">
                        <span class="spinner-border spinner-border-sm d-none mr-2" role="status" aria-hidden="true"></span>
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="dashboardClientModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add New Client</h4>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardClientModal')">&times;</button>
            </div>

            <form action="<?php echo e(route('clients.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="font-weight-bold small">Full Name</label>
                    <input type="text" name="name" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Email Address</label>
                    <input type="email" name="email" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Phone Number</label>
                    <input type="text" name="phone" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control module-input">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardClientModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Add Client</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dashboardCaseModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Open New Case</h4>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardCaseModal')">&times;</button>
            </div>

            <form action="<?php echo e(route('cases.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="font-weight-bold small">Assign Client</label>
                    <select name="client_id" class="form-control module-input" required>
                        <option value="">Select existing client</option>
                        <?php $__currentLoopData = $clients ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($client->id); ?>"><?php echo e($client->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Case Title</label>
                    <input type="text" name="case_name" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Detailed Description</label>
                    <textarea name="description" rows="3" class="form-control module-input"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Status</label>
                        <select name="status" class="form-control module-input">
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Priority</label>
                        <select name="priority" class="form-control module-input">
                            <option value="High">High</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Initial Progress (%)</label>
                    <input type="number" name="progress" min="0" max="100" class="form-control module-input" value="0">
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardCaseModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Create Case</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dashboardDocumentModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-document">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add Document</h4>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardDocumentModal')">&times;</button>
            </div>

            <form action="<?php echo e(route('documents.store')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="module-document-form-grid">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Document Category</label>
                        <select name="document_category" class="form-control module-input" required>
                            <option value="Court Filing">Court Filing</option>
                            <option value="Client Correspondence">Client Correspondence</option>
                            <option value="Evidence">Evidence</option>
                            <option value="Contract">Contract</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Document Belongs To</label>
                        <select name="document_scope" class="form-control module-input" id="dashboardDocumentScope" required>
                            <option value="general">General / Personal Document</option>
                            <option value="case">Existing Case</option>
                            <option value="client">Existing Client</option>
                        </select>
                    </div>
                    <div class="form-group mb-0 d-none" id="dashboardDocumentCaseGroup">
                        <label class="font-weight-bold small">Linked Case</label>
                        <select name="case_id" class="form-control module-input">
                            <option value="">Select case</option>
                            <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($caseOption->id); ?>"><?php echo e($caseOption->case_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-group mb-0 d-none" id="dashboardDocumentClientGroup">
                        <label class="font-weight-bold small">Linked Client</label>
                        <select name="client_id" class="form-control module-input">
                            <option value="">Select client</option>
                            <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clientOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($clientOption->id); ?>"><?php echo e($clientOption->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="font-weight-bold small">Select Files</label>
                    <?php
                        $dashboardAllowedExtensions = collect($documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'])
                            ->map(fn ($type) => '.' . strtolower($type))
                            ->values()
                            ->all();
                    ?>
                    <input type="file" name="documents[]" class="form-control module-input py-2" accept="<?php echo e(implode(',', $dashboardAllowedExtensions)); ?>" multiple>
                    <small class="text-muted d-block mt-2">Allowed: <?php echo e(implode(', ', $documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'])); ?>. Maximum <?php echo e($documentRules['max_upload_size'] ?? 25); ?>MB each.</small>
                </div>

                <div class="module-link-divider">
                    <span>or save a link</span>
                </div>

                <div class="module-document-form-grid module-document-form-grid-links">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Link Title</label>
                        <input type="text" name="link_name" class="form-control module-input" placeholder="Example: Shared draft in Google Drive">
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Document URL</label>
                        <input type="url" name="link_url" class="form-control module-input" placeholder="https://drive.google.com/...">
                        <small class="text-muted d-block mt-2">Use this when the team wants to keep working on the live document in Google Drive.</small>
                    </div>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardDocumentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Document Record</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dashboardTimeModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-compact">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add Time Entry</h4>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardTimeModal')">&times;</button>
            </div>

            <form action="<?php echo e(route('schedule.time-entries.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="return_to" value="dashboard">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Case Title</label>
                        <select name="case_id" class="form-control module-input" required>
                            <option value="">Select case</option>
                            <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($caseOption->id); ?>"><?php echo e($caseOption->case_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Date</label>
                        <input type="date" name="work_date" class="form-control module-input" value="<?php echo e(now()->toDateString()); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Hours</label>
                        <input type="number" step="0.25" min="0.25" max="24" name="hours" class="form-control module-input" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Activity</label>
                        <select name="activity" class="form-control module-input">
                            <option>Document Review</option>
                            <option>Client Interview</option>
                            <option>Case Preparation</option>
                            <option>Legal Research</option>
                            <option>Court Preparation</option>
                            <option>Admin Work</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small d-block">Billing Type</label>
                    <div class="time-entry-billable-toggle" role="radiogroup" aria-label="Billing type">
                        <input class="time-entry-toggle-input" type="radio" name="billable" id="dashboardBillableYes" value="1" checked>
                        <label class="time-entry-toggle-option" for="dashboardBillableYes">Billable</label>

                        <input class="time-entry-toggle-input" type="radio" name="billable" id="dashboardBillableNo" value="0">
                        <label class="time-entry-toggle-option" for="dashboardBillableNo">Non-Billable</label>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold small">Description</label>
                    <textarea name="description" rows="3" class="form-control module-input module-textarea-compact" required></textarea>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardTimeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Time Entry</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dashboardEventModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add Calendar Task</h4>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardEventModal')">&times;</button>
            </div>

            <form action="<?php echo e(route('schedule.events.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="font-weight-bold small">Task Title</label>
                    <input type="text" name="title" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Related Case</label>
                    <select name="case_name" class="form-control module-input">
                        <option value="">General task / no case</option>
                        <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($caseOption->case_name); ?>"><?php echo e($caseOption->case_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <small class="text-muted d-block mt-2">Optional for meetings, admin work, or general office reminders.</small>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Date & Time</label>
                    <input type="datetime-local" name="hearing_time" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control module-input">
                        <option value="Scheduled">Scheduled</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Notes</label>
                    <textarea name="notes" rows="3" class="form-control module-input"></textarea>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardEventModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
    <?php echo $__env->make('layouts.modals.logout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
document.querySelector('.topbar-center')?.setAttribute('id', 'timerWorkspace');

function openDashboardModal(modalId) {
    document.getElementById(modalId)?.classList.add('show');
}

function closeDashboardModal(modalId) {
    document.getElementById(modalId)?.classList.remove('show');
}

const chartLabels = <?php echo json_encode($chartLabels ?? [], 15, 512) ?>;
const chartData = <?php echo json_encode($chartData ?? [], 15, 512) ?>;
const recentTimeEntries = <?php echo json_encode($recentTimeEntryData ?? [], 15, 512) ?>;

const ctx = document.getElementById('activityChart')?.getContext('2d');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                borderColor: '#0891b2',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(8, 145, 178, 0.05)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}

const timeLogList = document.getElementById('timeLogList');
const timeLogFilter = document.getElementById('timeLogFilter');
const timeFilterCount = document.getElementById('timeFilterCount');
const timeEntryPanel = document.getElementById('timeEntryPanel');
const timeEntryForm = document.getElementById('timeEntryForm');
const timeEntryDuration = document.getElementById('timeEntryDuration');
const timeEntryActivity = document.getElementById('timeEntryActivity');
const timeEntryCase = document.getElementById('timeEntryCase');
const timeEntryNotes = document.getElementById('timeEntryNotes');
const saveTimeBtn = document.getElementById('saveTimeBtn');
const dashboardTimeEntryUrl = <?php echo json_encode(route('dashboard.time-entries.store'), 15, 512) ?>;
const dashboardAiSuggestionsUrl = <?php echo json_encode(route('dashboard.ai-suggestions'), 15, 512) ?>;
const csrfToken = <?php echo json_encode(csrf_token(), 15, 512) ?>;
let dashboardTimeEntries = Array.isArray(recentTimeEntries) ? recentTimeEntries.slice() : [];
let pendingTimeEntry = null;
const generateAiSuggestionsBtn = document.getElementById('generateAiSuggestionsBtn');
const aiAssistantEmptyState = document.getElementById('aiAssistantEmptyState');
const aiSuggestionList = document.getElementById('aiSuggestionList');
const aiAssistantSummary = document.getElementById('aiAssistantSummary');
const aiAssistantBadge = document.getElementById('aiAssistantBadge');
const aiAssistantStatus = document.getElementById('aiAssistantStatus');
const dashboardDocumentScope = document.getElementById('dashboardDocumentScope');
const dashboardDocumentCaseGroup = document.getElementById('dashboardDocumentCaseGroup');
const dashboardDocumentClientGroup = document.getElementById('dashboardDocumentClientGroup');
const initialAiSuggestions = <?php echo json_encode($aiSuggestions ?? [], 15, 512) ?>;
const initialAiSummary = <?php echo json_encode($aiSummary ?? '', 15, 512) ?>;

function syncDashboardDocumentScope() {
    const scope = dashboardDocumentScope ? dashboardDocumentScope.value : 'general';
    dashboardDocumentCaseGroup?.classList.toggle('d-none', scope !== 'case');
    dashboardDocumentClientGroup?.classList.toggle('d-none', scope !== 'client');
}

function formatDuration(milliseconds) {
    const totalSeconds = Math.max(1, Math.floor(milliseconds / 1000));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours) return hours + 'h ' + minutes + 'm ' + seconds + 's';
    if (minutes) return minutes + 'm ' + seconds + 's';
    return seconds + 's';
}

function formatDateTime(timestamp) {
    return new Date(timestamp).toLocaleString([], {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderAiSuggestions(summary, suggestions, source, message) {
    if (aiAssistantSummary) {
        aiAssistantSummary.textContent = summary || initialAiSummary;
    }

    if (aiAssistantBadge) {
        aiAssistantBadge.innerHTML = source === 'gemini'
            ? '<i class="bi bi-cpu mr-2"></i>Live AI'
            : '<i class="bi bi-stars mr-2"></i>Prototype';
    }

    if (aiAssistantStatus) {
        aiAssistantStatus.classList.remove('d-none', 'is-live', 'is-fallback');
        aiAssistantStatus.classList.add(source === 'gemini' ? 'is-live' : 'is-fallback');
        aiAssistantStatus.innerHTML = source === 'gemini'
            ? '<i class="bi bi-check-circle-fill mr-2"></i>' + escapeHtml(message || 'Live AI suggestions loaded successfully.')
            : '<i class="bi bi-info-circle-fill mr-2"></i>' + escapeHtml(message || 'Live AI is unavailable right now, so the local planner is being used.');
    }

    const items = Array.isArray(suggestions) ? suggestions : [];

    if (!items.length) {
        aiSuggestionList.innerHTML = `
            <div class="time-log-empty">
                <i class="bi bi-stars"></i>
                <strong>No AI suggestions yet</strong>
                <p>${escapeHtml(message || 'The assistant could not generate suggestions right now.')}</p>
            </div>
        `;
    } else {
        aiSuggestionList.innerHTML = items.map(function (suggestion) {
            return `
                <div class="ai-suggestion-item">
                    <div class="ai-suggestion-icon">
                        <i class="bi bi-magic"></i>
                    </div>
                    <div class="ai-suggestion-copy">
                        <span class="ai-suggestion-slot">${escapeHtml(suggestion.slot || 'Suggested Block')}</span>
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <strong>${escapeHtml(suggestion.title || 'Suggestion')}</strong>
                            <span class="ai-suggestion-timing">${escapeHtml(suggestion.timing || 'Today')}</span>
                        </div>
                        <p class="mb-1">${escapeHtml(suggestion.detail || '')}</p>
                        <small class="ai-suggestion-reason">Why this matters: ${escapeHtml(suggestion.reason || 'It helps structure the day with clearer priorities.')}</small>
                    </div>
                </div>
            `;
        }).join('');
    }

    aiAssistantEmptyState?.classList.add('d-none');
    aiSuggestionList?.classList.remove('d-none');
}

function renderTimeLogs() {
    const selectedFilter = timeLogFilter ? timeLogFilter.value : 'all';
    const filteredLogs = dashboardTimeEntries.filter(function (log) {
        return selectedFilter === 'all' || log.activity === selectedFilter;
    });

    if (timeFilterCount) {
        timeFilterCount.textContent = filteredLogs.length + ' ' + (filteredLogs.length === 1 ? 'entry' : 'entries');
    }

    if (!filteredLogs.length) {
        timeLogList.innerHTML = `
            <div class="time-log-empty">
                <i class="bi bi-stopwatch"></i>
                <strong>No time records yet</strong>
                <p>Stop the timer and save an entry to build your recent activity list.</p>
            </div>
        `;
        return;
    }

    timeLogList.innerHTML = filteredLogs.slice().reverse().map(function (log) {
        return `
            <div class="time-log-item">
                <div class="time-log-duration">${escapeHtml(log.durationLabel)}</div>
                <div class="time-log-copy">
                    <strong>${escapeHtml(log.activity)}</strong>
                    <span>${escapeHtml(log.caseName || 'No case')} | ${log.billable ? 'Billable' : 'Non-Billable'}</span>
                    <p>${escapeHtml(log.notes)}</p>
                    <span>${escapeHtml(formatDateTime(log.savedAt))}</span>
                </div>
            </div>
        `;
    }).join('');
}

function openTimeEntryPanel(elapsedMs) {
    if (!elapsedMs || elapsedMs < 1000 || !timeEntryPanel) {
        return;
    }

    pendingTimeEntry = {
        durationMs: elapsedMs,
        durationLabel: formatDuration(elapsedMs),
        stoppedAt: new Date().toISOString()
    };

    timeEntryDuration.value = pendingTimeEntry.durationLabel;
    timeEntryPanel.classList.add('show');
    timeEntryActivity.focus();
}

function clearTimeEntryForm() {
    timeEntryForm.reset();
    document.getElementById('billableYes').checked = true;
    timeEntryDuration.value = '';
    saveTimeBtn.disabled = false;
    saveTimeBtn.querySelector('.spinner-border').classList.add('d-none');
}

window.closeTimeEntryPanel = function closeTimeEntryPanel() {
    timeEntryPanel.classList.remove('show');
};

window.discardTimeEntry = function discardTimeEntry() {
    pendingTimeEntry = null;
    clearTimeEntryForm();
    closeTimeEntryPanel();
};

window.addEventListener('ila:timer-stopped', function (event) {
    openTimeEntryPanel(event.detail ? event.detail.elapsedTime : 0);
});

window.addEventListener('ila:timer-reset', function () {
    discardTimeEntry();
});

window.addEventListener('ila:timer-started', function () {
    discardTimeEntry();
});

timeEntryForm?.addEventListener('submit', async function (event) {
    event.preventDefault();

    if (!pendingTimeEntry) {
        return;
    }

    const spinner = saveTimeBtn.querySelector('.spinner-border');
    saveTimeBtn.disabled = true;
    spinner.classList.remove('d-none');

    try {
        const response = await fetch(dashboardTimeEntryUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                case_id: timeEntryCase.value,
                activity: timeEntryActivity.value,
                duration_ms: pendingTimeEntry.durationMs,
                description: timeEntryNotes.value.trim(),
                billable: document.querySelector('input[name="billable"]:checked').value === 'yes' ? 1 : 0,
                stopped_at: pendingTimeEntry.stoppedAt
            })
        });

        if (!response.ok) {
            throw new Error('Unable to save time entry.');
        }

        const payload = await response.json();
        dashboardTimeEntries.push({
            id: payload.entry.id,
            durationMs: payload.entry.durationMs,
            durationLabel: formatDuration(payload.entry.durationMs),
            activity: payload.entry.activity,
            caseName: payload.entry.caseName,
            billable: payload.entry.billable,
            notes: payload.entry.description,
            savedAt: payload.entry.savedAt
        });

        renderTimeLogs();
        discardTimeEntry();
    } catch (error) {
        alert('Unable to save the time entry right now. Please try again.');
    } finally {
        saveTimeBtn.disabled = false;
        spinner.classList.add('d-none');
    }
});

timeLogFilter?.addEventListener('change', renderTimeLogs);
renderTimeLogs();

generateAiSuggestionsBtn?.addEventListener('click', function () {
    generateAiSuggestionsBtn.disabled = true;
    generateAiSuggestionsBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>Generating...';

    fetch(dashboardAiSuggestionsUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Unable to generate suggestions right now.');
            }

            return response.json();
        })
        .then(function (payload) {
            renderAiSuggestions(payload.summary, payload.suggestions, payload.source, payload.message);
            generateAiSuggestionsBtn.innerHTML = '<i class="bi bi-arrow-repeat mr-1"></i>Regenerate';
            generateAiSuggestionsBtn.disabled = false;
        })
        .catch(function () {
            renderAiSuggestions(initialAiSummary, initialAiSuggestions, 'prototype', 'Live AI could not be reached, so the local planner is being used.');
            generateAiSuggestionsBtn.innerHTML = '<i class="bi bi-arrow-repeat mr-1"></i>Try Again';
            generateAiSuggestionsBtn.disabled = false;
        });
});

dashboardDocumentScope?.addEventListener('change', syncDashboardDocumentScope);
syncDashboardDocumentScope();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views/dashboard.blade.php ENDPATH**/ ?>