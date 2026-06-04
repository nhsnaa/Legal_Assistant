@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div>
    <div class="dashboard-command-bar glass-card mb-4">
        <div class="dashboard-command-copy">
            <span class="module-kicker">Workspace Shortcuts</span>
            <h5 class="font-weight-bold mb-1 mt-2">Quick Create</h5>
            <p class="text-muted mb-0">Start common records quickly, then return to the dashboard overview.</p>
        </div>
        <div class="dashboard-command-actions">
            @php
                $actions = [
                    ['bi bi-person-plus-fill', 'Client', 'dashboardClientModal'],
                    ['bi bi-briefcase-fill', 'Case', 'dashboardCaseModal'],
                    ['bi bi-stopwatch-fill', 'Time', 'dashboardTimeModal'],
                    ['bi bi-calendar-plus-fill', 'Event', 'dashboardEventModal'],
                    ['bi bi-folder2-open', 'Document', 'dashboardDocumentModal'],
                ];
            @endphp

            @foreach ($actions as $action)
                <button type="button" class="btn btn-action btn-action-compact" onclick="openDashboardModal('{{ $action[2] }}')">
                    <span class="action-icon"><i class="{{ $action[0] }}"></i></span>
                    {{ $action[1] }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="glass-card mb-4">
        <div class="p-4 border-bottom d-flex justify-content-between">
            <h6 class="font-weight-bold mb-0">Upcoming Court Hearings</h6>
            <button type="button" class="btn btn-sm btn-link font-weight-bold p-0 {{ ($hearingCount ?? 0) > 3 ? '' : 'd-none' }}" onclick="openDashboardModal('dashboardHearingsModal')">View More</button>
            <span class="text-muted tiny uppercase {{ ($hearingCount ?? 0) > 3 ? 'd-none' : '' }}">Latest 3</span>
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
                    @forelse($hearings as $id => $hearing)
                        <tr>
                            <td class="px-4 font-weight-bold">
                                {{ isset($hearing['hearing_time']) ? \Carbon\Carbon::parse($hearing['hearing_time'])->format('h:i A') : 'N/A' }}
                            </td>
                            <td>{{ $hearing['case_name'] ?? 'Untitled Case' }}</td>
                            <td class="text-right px-4">
                                <span class="badge-status-{{ ($hearing['status'] ?? '') === 'Confirmed' ? 'green' : 'orange' }}">
                                    {{ $hearing['status'] ?? 'Scheduled' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <div style="font-size: 2rem; opacity: 0.3;"><i class="bi bi-calendar2-event"></i></div>
                                <p class="mb-0">No upcoming hearings scheduled.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="glass-card ai-assistant-card mb-4">
                <div class="ai-assistant-header">
                    <div class="ai-assistant-title">
                        <span class="module-kicker">AI Automated Scheduling</span>
                        <h5 class="font-weight-bold mb-1 mt-3">Today's legal work focus</h5>
                    </div>
                    <div class="ai-assistant-actions">
                        <span class="ai-assistant-badge" id="aiAssistantBadge"><i class="bi bi-stars mr-2"></i>Prototype</span>
                        <button type="button" class="btn btn-primary btn-sm" id="generateAiSuggestionsBtn">
                            <i class="bi bi-magic mr-1"></i>Generate Schedule
                        </button>
                        <button type="button" class="btn btn-ai-apply btn-sm d-none" id="applyAiSuggestionsBtn">
                            <i class="bi bi-calendar-plus mr-1"></i>Apply to Schedule
                        </button>
                    </div>
                </div>

                <div class="ai-brief-panel">
                    <span>Daily Brief</span>
                    <p class="mb-0" id="aiAssistantSummary">{{ $aiSummary ?? 'An automated scheduling summary based on hearings, active cases, documents, and recorded legal work.' }}</p>
                </div>

                <div class="ai-safety-note">
                    <i class="bi bi-shield-check"></i>
                    <span>AI suggestions support workflow planning only. They are not legal advice and should be reviewed by a lawyer.</span>
                </div>

                <div class="ai-assistant-empty" id="aiAssistantEmptyState">
                    <i class="bi bi-calendar2-week"></i>
                    <strong>Ready to build today's schedule</strong>
                    <p class="mb-0">Generate an AI-assisted schedule based on your hearings, active matters, documents, and recorded work priorities.</p>
                </div>

                <div class="ai-assistant-status d-none" id="aiAssistantStatus"></div>

                <div class="ai-suggestion-list d-none" id="aiSuggestionList">
                    @forelse ($aiSuggestions as $suggestion)
                        <div class="ai-suggestion-item">
                            <div class="ai-suggestion-icon">
                                <i class="bi bi-magic"></i>
                            </div>
                            <div class="ai-suggestion-copy">
                                <span class="ai-suggestion-slot">{{ $suggestion['slot'] ?? 'Suggested Block' }}</span>
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <strong>{{ $suggestion['title'] }}</strong>
                                    <span class="ai-suggestion-timing">{{ $suggestion['timing'] }}</span>
                                </div>
                                <p class="mb-1">{{ $suggestion['detail'] }}</p>
                                <small class="ai-suggestion-reason"><span>Why this was suggested:</span> {{ $suggestion['reason'] ?? 'It helps structure the day with clearer priorities.' }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="time-log-empty">
                            <i class="bi bi-calendar2-week"></i>
                            <strong>No AI schedule yet</strong>
                            <p>As more cases, hearings, and time records are added, the assistant can generate a more useful daily schedule.</p>
                        </div>
                    @endforelse
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
                            <strong>{{ now()->format('F') }}</strong>
                        </div>
                        <div class="schedule-session-stat">
                            <span>Hearings</span>
                            <strong>{{ collect($hearings)->count() }}</strong>
                        </div>
                        <div class="schedule-session-stat">
                            <span>Documents</span>
                            <strong>{{ $documentsCount }}</strong>
                        </div>
                    </div>
                    <div class="schedule-timer-buttons">
                        <a href="{{ route('schedule') }}" class="btn btn-light btn-sm">Open Calendar</a>
                        <a href="{{ route('schedule', ['tab' => 'time']) }}" class="btn btn-primary btn-sm">Review Timer</a>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="summary-card summary-card-compact h-100">
                        <p class="mb-1 small opacity-75">Active Cases</p>
                        <h1 class="display-4 font-weight-bold">{{ number_format($activeCases) }}</h1>
                        <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.1);">
                            <div class="progress-bar bg-white" style="width: {{ min(($activeCases / 50) * 100, 100) }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4 h-100">
                        <h6 class="font-weight-bold text-muted small uppercase mb-3">System Snapshot</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center py-2">
                                    <div class="h4 font-weight-bold text-primary mb-1">{{ $clientsCount }}</div>
                                    <small class="text-muted">Total Clients</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center py-2">
                                    <div class="h4 font-weight-bold mb-1">{{ $documentsCount }}</div>
                                    <small class="text-muted">Documents</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-7 mb-3 mb-lg-0">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-muted small uppercase mb-0">Activity Trend</h6>
                            <span class="text-muted tiny uppercase">Last 7 Days</span>
                        </div>
                        <div style="height: 260px;">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-muted small uppercase mb-0">Case Status Mix</h6>
                            <span class="text-muted tiny uppercase">Workload</span>
                        </div>
                        <div style="height: 260px;">
                            <canvas id="caseStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="font-weight-bold mb-0">Recent Activity</h6>
                    <button type="button" class="btn btn-sm btn-link font-weight-bold p-0 {{ ($recentActivityCount ?? 0) > 3 ? '' : 'd-none' }}" id="recentActivityViewMoreBtn" onclick="openDashboardModal('dashboardActivityModal')">View More</button>
                    <span class="text-muted tiny uppercase {{ ($recentActivityCount ?? 0) > 3 ? 'd-none' : '' }}" id="recentActivityHint">Latest 3</span>
                </div>

                <div class="activity-feed-list" id="recentActivityList">
                    @forelse($recentActivities as $activity)
                        <div class="activity-feed-item">
                            <div class="activity-feed-icon activity-feed-icon-{{ $activity['module'] }}">
                                <i class="
                                    @switch($activity['module'])
                                        @case('client') bi bi-person-lines-fill @break
                                        @case('case') bi bi-briefcase-fill @break
                                        @case('document') bi bi-file-earmark-text-fill @break
                                        @case('schedule') bi bi-calendar-check-fill @break
                                        @default bi bi-stopwatch-fill
                                    @endswitch
                                "></i>
                            </div>
                            <div class="activity-feed-copy">
                                <strong>{{ $activity['actor'] }} {{ $activity['action'] }} {{ $activity['label'] }}:</strong>
                                <p>{{ $activity['subject'] }}</p>
                                <span>{{ $activity['time_label'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="time-log-empty">
                            <i class="bi bi-activity"></i>
                            <strong>No recent activity yet</strong>
                            <p>Once records are created or updated, the latest workspace actions will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="font-weight-bold mb-0">Recent Documents</h6>
                    <button type="button" class="btn btn-sm btn-link font-weight-bold p-0 {{ (($allRecentDocuments ?? collect())->count() > 3) ? '' : 'd-none' }}" onclick="openDashboardModal('dashboardDocumentsModal')">View More</button>
                    <span class="text-muted tiny uppercase {{ (($allRecentDocuments ?? collect())->count() > 3) ? 'd-none' : '' }}">Latest 3</span>
                </div>
                <div class="doc-list">
                    @forelse($recentDocuments as $id => $document)
                        @php
                            $extension = strtolower(pathinfo($document['original_name'] ?? '', PATHINFO_EXTENSION));
                            $docStyles = match ($extension) {
                                'pdf' => ['bg-primary', 'bi bi-file-earmark-pdf-fill'],
                                'doc', 'docx' => ['bg-info', 'bi bi-file-earmark-word-fill'],
                                'txt' => ['bg-secondary', 'bi bi-file-earmark-text-fill'],
                                default => ['bg-warning', 'bi bi-paperclip'],
                            };
                        @endphp
                        <div class="doc-item d-flex align-items-center mb-3 p-3 rounded border">
                            <div class="icon-circle {{ $docStyles[0] }} text-white mr-3"><i class="{{ $docStyles[1] }}"></i></div>
                            <div class="flex-grow-1">
                                <div class="small font-weight-bold">{{ $document['name'] ?? 'Untitled' }}</div>
                                <div class="text-muted tiny">
                                    {{ strtoupper($extension ?: 'FILE') }}
                                    @if (!empty($document['created_at']))
                                        | {{ \Carbon\Carbon::parse($document['created_at'])->format('d M Y') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="time-log-empty">
                            <i class="bi bi-folder2-open"></i>
                            <strong>No documents uploaded yet</strong>
                            <p>Use the quick create document button to start storing legal files in the system.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="font-weight-bold mb-0">Recent Time Records</h6>
                    <button type="button" class="btn btn-sm btn-link font-weight-bold p-0 {{ ($recentTimeEntryCount ?? 0) > 3 ? '' : 'd-none' }}" id="timeRecordsViewMoreBtn" onclick="openDashboardModal('dashboardTimeRecordsModal')">View More</button>
                    <span class="text-muted tiny uppercase {{ ($recentTimeEntryCount ?? 0) > 3 ? 'd-none' : '' }}" id="timeRecordsHint">Latest 3</span>
                </div>
                <div class="time-log-list" id="timeLogList"></div>
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
                            @foreach ($caseOptions as $caseOption)
                                <option value="{{ $caseOption->id }}">{{ $caseOption->case_name }}</option>
                            @endforeach
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

            <form action="{{ route('clients.store') }}" method="POST">
                @csrf
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

            <form action="{{ route('cases.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="font-weight-bold small">Assign Client</label>
                    <select name="client_id" class="form-control module-input" required>
                        <option value="">Select existing client</option>
                        @foreach ($clients ?? [] as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Case Title</label>
                    <input type="text" name="case_name" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Case Type</label>
                    <select name="case_type" class="form-control module-input" required>
                        @foreach (['Criminal', 'Divorce', 'Family', 'Civil Litigation', 'Corporate', 'Employment', 'Property', 'Immigration', 'Other'] as $caseType)
                            <option value="{{ $caseType }}" {{ $caseType === 'Other' ? 'selected' : '' }}>{{ $caseType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Detailed Description</label>
                    <textarea name="description" rows="3" class="form-control module-input"></textarea>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Priority</label>
                    <select name="priority" class="form-control module-input">
                        <option value="High">High</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <p class="text-muted small mb-0">Status and progress will be recorded later through case updates.</p>

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

            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
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
                            @foreach ($caseOptions as $caseOption)
                                <option value="{{ $caseOption->id }}">{{ $caseOption->case_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0 d-none" id="dashboardDocumentClientGroup">
                        <label class="font-weight-bold small">Linked Client</label>
                        <select name="client_id" class="form-control module-input">
                            <option value="">Select client</option>
                            @foreach ($clients as $clientOption)
                                <option value="{{ $clientOption->id }}">{{ $clientOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="font-weight-bold small">Select Files</label>
                    @php
                        $dashboardAllowedExtensions = collect($documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'])
                            ->map(fn ($type) => '.' . strtolower($type))
                            ->values()
                            ->all();
                    @endphp
                    <input type="file" name="documents[]" class="form-control module-input py-2" accept="{{ implode(',', $dashboardAllowedExtensions) }}" multiple>
                    <small class="text-muted d-block mt-2">Allowed: {{ implode(', ', $documentRules['allowed_file_types'] ?? ['PDF', 'DOCX']) }}. Maximum {{ $documentRules['max_upload_size'] ?? 25 }}MB each.</small>
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
                        <input type="url" name="link_url" id="dashboardDocumentLinkUrl" class="form-control module-input" placeholder="https://drive.google.com/...">
                        <small class="text-muted d-block mt-2">Use this when the team wants to keep working on the live document in Google Drive.</small>
                    </div>
                </div>

                <div class="module-alert module-alert-warning mt-3">
                    <div>
                        <strong>External link security reminder</strong>
                        <p class="mb-0">ILA can record when this link is opened, but Google Drive or the external provider controls who can access the file. Set confidential files to restricted/private before saving the link.</p>
                    </div>
                </div>

                <label class="module-confirmation-check mt-3">
                    <input type="checkbox" name="link_privacy_ack" id="dashboardDocumentLinkPrivacyAck" value="1">
                    <span>I confirm this external link is restricted/private or approved for workspace access.</span>
                </label>
                @error('link_privacy_ack')
                    <small class="text-danger d-block mt-2">{{ $message }}</small>
                @enderror

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

            <form action="{{ route('schedule.time-entries.store') }}" method="POST">
                @csrf
                <input type="hidden" name="return_to" value="dashboard">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Case Title</label>
                        <select name="case_id" class="form-control module-input" required>
                            <option value="">Select case</option>
                            @foreach ($caseOptions as $caseOption)
                                <option value="{{ $caseOption->id }}">{{ $caseOption->case_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Date</label>
                        <input type="date" name="work_date" class="form-control module-input" value="{{ now()->toDateString() }}" required>
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

            <form action="{{ route('schedule.events.store') }}" method="POST" id="dashboardEventCreateForm">
                @csrf
                <input type="hidden" name="conflict_context" value="dashboard_create">
                <input type="hidden" name="conflict_override" id="dashboardEventConflictOverride" value="0">
                <div class="form-group">
                    <label class="font-weight-bold small">Task Title</label>
                    <input type="text" name="title" class="form-control module-input" required value="{{ old('title') }}">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Related Case</label>
                    <select name="case_name" class="form-control module-input">
                        <option value="">General task / no case</option>
                        @foreach ($caseOptions as $caseOption)
                            <option value="{{ $caseOption->case_name }}" {{ old('case_name') === $caseOption->case_name ? 'selected' : '' }}>{{ $caseOption->case_name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">Optional for meetings, admin work, or general office reminders.</small>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Date & Time</label>
                    <input type="datetime-local" name="hearing_time" class="form-control module-input" required value="{{ old('hearing_time') }}">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control module-input">
                        <option value="Scheduled" {{ old('status', 'Scheduled') === 'Scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="Confirmed" {{ old('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="Pending" {{ old('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Notes</label>
                    <textarea name="notes" rows="3" class="form-control module-input">{{ old('notes') }}</textarea>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardEventModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dashboardActivityModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-activity">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="font-weight-bold mb-1">All Recent Activity</h4>
                    <p class="text-muted mb-0">A fuller view of the latest workspace actions.</p>
                </div>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardActivityModal')">&times;</button>
            </div>

            <div class="activity-feed-list activity-feed-list-modal" id="recentActivityModalList">
                @forelse($allRecentActivities as $activity)
                    <div class="activity-feed-item">
                        <div class="activity-feed-icon activity-feed-icon-{{ $activity['module'] }}">
                            <i class="
                                @switch($activity['module'])
                                    @case('client') bi bi-person-lines-fill @break
                                    @case('case') bi bi-briefcase-fill @break
                                    @case('document') bi bi-file-earmark-text-fill @break
                                    @case('schedule') bi bi-calendar-check-fill @break
                                    @default bi bi-stopwatch-fill
                                @endswitch
                            "></i>
                        </div>
                        <div class="activity-feed-copy">
                            <strong>{{ $activity['actor'] }} {{ $activity['action'] }} {{ $activity['label'] }}:</strong>
                            <p>{{ $activity['subject'] }}</p>
                            <span>{{ $activity['time_label'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="time-log-empty">
                        <i class="bi bi-activity"></i>
                        <strong>No recent activity yet</strong>
                        <p>Once records are created or updated, the latest workspace actions will appear here.</p>
                    </div>
                @endforelse
            </div>

            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardActivityModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="dashboardHearingsModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-activity">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="font-weight-bold mb-1">All Upcoming Hearings</h4>
                    <p class="text-muted mb-0">A fuller view of scheduled hearings and court-related tasks.</p>
                </div>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardHearingsModal')">&times;</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="tiny uppercase font-weight-bold">
                        <tr>
                            <th class="border-0">Time</th>
                            <th class="border-0">Case Name</th>
                            <th class="border-0 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($allHearings ?? collect()) as $hearing)
                            <tr>
                                <td class="font-weight-bold">{{ isset($hearing['hearing_time']) ? \Carbon\Carbon::parse($hearing['hearing_time'])->format('d M Y, h:i A') : 'N/A' }}</td>
                                <td>{{ $hearing['case_name'] ?? 'Untitled Case' }}</td>
                                <td class="text-right">
                                    <span class="badge-status-{{ ($hearing['status'] ?? '') === 'Confirmed' ? 'green' : 'orange' }}">
                                        {{ $hearing['status'] ?? 'Scheduled' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No upcoming hearings scheduled.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="module-modal-actions">
                <a href="{{ route('schedule') }}" class="btn btn-primary px-4">Open Calendar</a>
                <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardHearingsModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="dashboardDocumentsModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-activity">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="font-weight-bold mb-1">All Recent Documents</h4>
                    <p class="text-muted mb-0">A fuller view of the latest uploaded files and saved links.</p>
                </div>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardDocumentsModal')">&times;</button>
            </div>
            <div class="doc-list">
                @forelse(($allRecentDocuments ?? collect()) as $document)
                    @php
                        $modalExtension = strtolower(pathinfo($document['original_name'] ?? '', PATHINFO_EXTENSION));
                        $modalDocStyles = match ($modalExtension) {
                            'pdf' => ['bg-primary', 'bi bi-file-earmark-pdf-fill'],
                            'doc', 'docx' => ['bg-info', 'bi bi-file-earmark-word-fill'],
                            'txt' => ['bg-secondary', 'bi bi-file-earmark-text-fill'],
                            default => ['bg-warning', 'bi bi-paperclip'],
                        };
                    @endphp
                    <div class="doc-item d-flex align-items-center mb-3 p-3 rounded border">
                        <div class="icon-circle {{ $modalDocStyles[0] }} text-white mr-3"><i class="{{ $modalDocStyles[1] }}"></i></div>
                        <div class="flex-grow-1">
                            <div class="small font-weight-bold">{{ $document['name'] ?? 'Untitled' }}</div>
                            <div class="text-muted tiny">
                                {{ strtoupper($modalExtension ?: 'FILE') }}
                                @if (!empty($document['created_at']))
                                    | {{ \Carbon\Carbon::parse($document['created_at'])->format('d M Y') }}
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="time-log-empty">
                        <i class="bi bi-folder2-open"></i>
                        <strong>No documents uploaded yet</strong>
                        <p>Use the quick create document button to start storing legal files in the system.</p>
                    </div>
                @endforelse
            </div>
            <div class="module-modal-actions">
                <a href="{{ route('documents.index') }}" class="btn btn-primary px-4">Open Documents</a>
                <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardDocumentsModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="dashboardTimeRecordsModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-activity">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="font-weight-bold mb-1">All Recent Time Records</h4>
                    <p class="text-muted mb-0">Review the full set of recently saved work sessions.</p>
                </div>
                <button type="button" class="module-modal-close" onclick="closeDashboardModal('dashboardTimeRecordsModal')">&times;</button>
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
            <div class="time-log-list" id="timeLogModalList"></div>
            <div class="module-modal-actions">
                <a href="{{ route('schedule', ['tab' => 'time']) }}" class="btn btn-primary px-4">Open Time Records</a>
                <button type="button" class="btn btn-light px-4" onclick="closeDashboardModal('dashboardTimeRecordsModal')">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection

@section('scripts')
<script>
document.querySelector('.topbar-center')?.setAttribute('id', 'timerWorkspace');

function openDashboardModal(modalId) {
    document.getElementById(modalId)?.classList.add('show');
}

function closeDashboardModal(modalId) {
    document.getElementById(modalId)?.classList.remove('show');
}

function buildDashboardConflictHtml(conflictData) {
    const conflicts = Array.isArray(conflictData?.conflicts) ? conflictData.conflicts : [];

    if (!conflicts.length) {
        return `
            <div class="ila-conflict-body">
                <p class="ila-conflict-intro mb-0">Another task already exists near this time.</p>
            </div>
        `;
    }

    return `
        <div class="ila-conflict-body">
            <p class="ila-conflict-intro">${escapeHtml(conflictData.message || 'Possible schedule conflict detected.')}</p>
            <div class="ila-conflict-list">
            ${conflicts.map(function (conflict) {
                return `
                    <div class="ila-conflict-card">
                        <div class="ila-conflict-card-row">
                            <strong class="ila-conflict-title">${escapeHtml(conflict.title || 'Untitled Task')}</strong>
                            <span class="ila-conflict-status">${escapeHtml(conflict.status || 'Scheduled')}</span>
                        </div>
                        <div class="ila-conflict-meta">${escapeHtml(conflict.case_name || 'General Task')}</div>
                        <div class="ila-conflict-time">${escapeHtml(conflict.time || 'Unknown time')}</div>
                    </div>
                `;
            }).join('')}
            </div>
        </div>
    `;
}

function promptDashboardScheduleConflict(conflictData) {
    if (!conflictData || typeof Swal === 'undefined') {
        return;
    }

    if ((conflictData.context || '') !== 'dashboard_create') {
        return;
    }

    closeDashboardModal('dashboardEventModal');

    Swal.fire({
        icon: 'warning',
        title: 'Possible Schedule Conflict',
        html: buildDashboardConflictHtml(conflictData),
        showCancelButton: true,
        confirmButtonText: 'Continue Anyway',
        cancelButtonText: 'Review Task',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#94a3b8',
        backdrop: 'rgba(15, 23, 42, 0.5)',
        customClass: {
            popup: 'ila-swal-popup ila-conflict-popup',
            confirmButton: 'ila-swal-confirm',
            cancelButton: 'ila-swal-cancel'
        },
        buttonsStyling: false
    }).then(function (result) {
        if (!result.isConfirmed) {
            openDashboardModal('dashboardEventModal');
            return;
        }

        const form = document.getElementById('dashboardEventCreateForm');
        const overrideInput = document.getElementById('dashboardEventConflictOverride');

        if (overrideInput) {
            overrideInput.value = '1';
        }

        form?.submit();
    });
}

function buildAiConflictHtml(conflicts, message) {
    const items = Array.isArray(conflicts) ? conflicts : [];

    return `
        <div class="ila-conflict-body">
            <p class="ila-conflict-intro">${escapeHtml(message || 'Possible schedule conflicts detected.')}</p>
            <div class="ila-conflict-list ila-conflict-list-ai">
            ${items.map(function (item) {
                const nested = Array.isArray(item.conflicts_with) ? item.conflicts_with : [];
                return `
                    <div class="ila-conflict-card ila-conflict-card-ai">
                        <div class="ila-conflict-card-row">
                            <strong class="ila-conflict-title">${escapeHtml(item.title || 'Suggested Block')}</strong>
                            <span class="ila-conflict-status">AI Block</span>
                        </div>
                        <div class="ila-conflict-time">${escapeHtml(item.scheduled_time || 'Today')}</div>
                        <div class="ila-conflict-nested-list">
                        ${nested.map(function (conflict) {
                            return `
                                <div class="ila-conflict-nested-card">
                                    <div class="ila-conflict-card-row">
                                        <strong class="ila-conflict-title">${escapeHtml(conflict.title || 'Existing Task')}</strong>
                                        <span class="ila-conflict-status">${escapeHtml(conflict.status || 'Scheduled')}</span>
                                    </div>
                                    <div class="ila-conflict-meta">${escapeHtml(conflict.case_name || 'General Task')}</div>
                                    <div class="ila-conflict-time">${escapeHtml(conflict.time || 'Unknown time')}</div>
                                </div>
                            `;
                        }).join('')}
                        </div>
                    </div>
                `;
            }).join('')}
            </div>
        </div>
    `;
}

const chartLabels = @json($chartLabels ?? []);
const chartData = @json($chartData ?? []);
const caseStatusLabels = @json($caseStatusLabels ?? []);
const caseStatusData = @json($caseStatusData ?? []);
const recentTimeEntries = @json($allRecentTimeEntryData ?? []);

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
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: { legend: { display: false } }
        }
    });
}

const caseStatusCtx = document.getElementById('caseStatusChart')?.getContext('2d');
if (caseStatusCtx) {
    const hasCaseStatusData = Array.isArray(caseStatusData) && caseStatusData.some(function (value) {
        return Number(value) > 0;
    });
    const caseStatusPalette = {
        'Registered': '#7dd3fc',
        'Active': '#0891b2',
        'Pending': '#f59e0b',
        'Pending Client': '#fbbf24',
        'Pending Firm': '#d97706',
        'Pending Third Party': '#f97316',
        'Completed': '#10b981',
        'Cancelled': '#ef4444',
        'Closed': '#64748b'
    };
    const caseStatusFallbackColors = ['#0891b2', '#0e7490', '#14b8a6', '#f59e0b', '#64748b'];
    const caseStatusColors = hasCaseStatusData
        ? caseStatusLabels.map(function (label, index) {
            return caseStatusPalette[label] || caseStatusFallbackColors[index % caseStatusFallbackColors.length];
        })
        : ['#e2e8f0'];

    new Chart(caseStatusCtx, {
        type: 'doughnut',
        data: {
            labels: hasCaseStatusData ? caseStatusLabels : ['No cases yet'],
            datasets: [{
                data: hasCaseStatusData ? caseStatusData : [1],
                backgroundColor: caseStatusColors,
                hoverBackgroundColor: caseStatusColors,
                borderColor: 'rgba(255, 255, 255, 0.92)',
                borderWidth: 4,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 14,
                        color: '#64748b',
                        font: {
                            size: 11,
                            weight: '600'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#ffffff',
                    bodyColor: '#e2e8f0',
                    borderColor: 'rgba(8, 145, 178, 0.28)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function (context) {
                            return context.label + ': ' + context.parsed + ' case' + (context.parsed === 1 ? '' : 's');
                        }
                    }
                }
            }
        }
    });
}

const timeLogList = document.getElementById('timeLogList');
const timeLogModalList = document.getElementById('timeLogModalList');
const timeLogFilter = document.getElementById('timeLogFilter');
const timeEntryPanel = document.getElementById('timeEntryPanel');
const timeEntryForm = document.getElementById('timeEntryForm');
const timeEntryDuration = document.getElementById('timeEntryDuration');
const timeEntryActivity = document.getElementById('timeEntryActivity');
const timeEntryCase = document.getElementById('timeEntryCase');
const timeEntryNotes = document.getElementById('timeEntryNotes');
const saveTimeBtn = document.getElementById('saveTimeBtn');
const dashboardTimeEntryUrl = @json(route('dashboard.time-entries.store'));
const dashboardAiSuggestionsUrl = @json(route('dashboard.ai-suggestions'));
const dashboardApplyAiScheduleUrl = @json(route('dashboard.ai-suggestions.apply'));
const csrfToken = @json(csrf_token());
const aiScheduleStorageKey = @json('ilaAiSchedule:' . session('firebase_user_id'));
const pendingDashboardScheduleConflict = @json(session('schedule_conflict'));
let dashboardTimeEntries = Array.isArray(recentTimeEntries) ? recentTimeEntries.slice() : [];
let pendingTimeEntry = null;
const generateAiSuggestionsBtn = document.getElementById('generateAiSuggestionsBtn');
const applyAiSuggestionsBtn = document.getElementById('applyAiSuggestionsBtn');
const aiAssistantEmptyState = document.getElementById('aiAssistantEmptyState');
const aiSuggestionList = document.getElementById('aiSuggestionList');
const aiAssistantSummary = document.getElementById('aiAssistantSummary');
const aiAssistantBadge = document.getElementById('aiAssistantBadge');
const aiAssistantStatus = document.getElementById('aiAssistantStatus');
const dashboardDocumentScope = document.getElementById('dashboardDocumentScope');
const dashboardDocumentCaseGroup = document.getElementById('dashboardDocumentCaseGroup');
const dashboardDocumentClientGroup = document.getElementById('dashboardDocumentClientGroup');
const dashboardDocumentLinkUrl = document.getElementById('dashboardDocumentLinkUrl');
const dashboardDocumentLinkPrivacyAck = document.getElementById('dashboardDocumentLinkPrivacyAck');
const initialAiSuggestions = @json($aiSuggestions ?? []);
const initialAiSummary = @json($aiSummary ?? '');
const initialRecentActivities = @json(($allRecentActivities ?? collect())->values()->all());
const recentActivityList = document.getElementById('recentActivityList');
const recentActivityModalList = document.getElementById('recentActivityModalList');
const recentActivityViewMoreBtn = document.getElementById('recentActivityViewMoreBtn');
const recentActivityHint = document.getElementById('recentActivityHint');
let currentAiSuggestions = Array.isArray(initialAiSuggestions) ? initialAiSuggestions.slice() : [];
let dashboardRecentActivities = Array.isArray(initialRecentActivities) ? initialRecentActivities.slice() : [];

function persistAiScheduleState(summary, suggestions, source, message) {
    try {
        sessionStorage.setItem(aiScheduleStorageKey, JSON.stringify({
            summary: summary || '',
            suggestions: Array.isArray(suggestions) ? suggestions : [],
            source: source || 'prototype',
            message: message || '',
        }));
    } catch (error) {
        // Ignore storage failures quietly.
    }
}

function clearAiScheduleState() {
    try {
        sessionStorage.removeItem(aiScheduleStorageKey);
    } catch (error) {
        // Ignore storage failures quietly.
    }
}

function restoreAiScheduleState() {
    try {
        const raw = sessionStorage.getItem(aiScheduleStorageKey);

        if (!raw) {
            return false;
        }

        const parsed = JSON.parse(raw);

        if (!parsed || !Array.isArray(parsed.suggestions) || !parsed.suggestions.length) {
            return false;
        }

        renderAiSuggestions(parsed.summary || initialAiSummary, parsed.suggestions, parsed.source || 'prototype', parsed.message || '');
        return true;
    } catch (error) {
        return false;
    }
}

function syncDashboardDocumentScope() {
    const scope = dashboardDocumentScope ? dashboardDocumentScope.value : 'general';
    dashboardDocumentCaseGroup?.classList.toggle('d-none', scope !== 'case');
    dashboardDocumentClientGroup?.classList.toggle('d-none', scope !== 'client');
}

function syncDashboardExternalLinkConfirmation() {
    const hasLink = dashboardDocumentLinkUrl && dashboardDocumentLinkUrl.value.trim() !== '';

    if (dashboardDocumentLinkPrivacyAck) {
        dashboardDocumentLinkPrivacyAck.required = hasLink;
    }
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

function getActivityIconClass(module) {
    switch (module) {
        case 'client':
            return 'bi bi-person-lines-fill';
        case 'case':
            return 'bi bi-briefcase-fill';
        case 'document':
            return 'bi bi-file-earmark-text-fill';
        case 'schedule':
            return 'bi bi-calendar-check-fill';
        default:
            return 'bi bi-stopwatch-fill';
    }
}

function activityEmptyMarkup() {
    return `
        <div class="time-log-empty">
            <i class="bi bi-activity"></i>
            <strong>No recent activity yet</strong>
            <p>Once records are created or updated, the latest workspace actions will appear here.</p>
        </div>
    `;
}

function renderActivityItems(items) {
    if (!Array.isArray(items) || !items.length) {
        return activityEmptyMarkup();
    }

    return items.map(function (activity) {
        return `
            <div class="activity-feed-item">
                <div class="activity-feed-icon activity-feed-icon-${escapeHtml(activity.module || 'time')}">
                    <i class="${escapeHtml(getActivityIconClass(activity.module || 'time'))}"></i>
                </div>
                <div class="activity-feed-copy">
                    <strong>${escapeHtml(activity.actor || 'Workspace User')} ${escapeHtml(activity.action || 'updated')} ${escapeHtml(activity.label || 'Record')}:</strong>
                    <p>${escapeHtml(activity.subject || 'Workspace Record')}</p>
                    <span>${escapeHtml(activity.time_label || 'Just now')}</span>
                </div>
            </div>
        `;
    }).join('');
}

function renderRecentActivities() {
    if (recentActivityList) {
        recentActivityList.innerHTML = renderActivityItems(dashboardRecentActivities.slice(0, 3));
    }

    if (recentActivityModalList) {
        recentActivityModalList.innerHTML = renderActivityItems(dashboardRecentActivities);
    }

    if (recentActivityViewMoreBtn) {
        recentActivityViewMoreBtn.classList.toggle('d-none', dashboardRecentActivities.length <= 3);
    }

    if (recentActivityHint) {
        recentActivityHint.classList.toggle('d-none', dashboardRecentActivities.length > 3);
    }
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
            ? '<i class="bi bi-check-circle-fill mr-2"></i>' + escapeHtml(message || 'Live AI schedule generated successfully.')
            : '<i class="bi bi-info-circle-fill mr-2"></i>' + escapeHtml(message || 'Live AI scheduling is unavailable right now, so the local planner is being used.');
    }

    const items = Array.isArray(suggestions) ? suggestions : [];
    currentAiSuggestions = items.slice();

    if (!items.length) {
        aiSuggestionList.innerHTML = `
            <div class="time-log-empty">
                <i class="bi bi-calendar2-week"></i>
                <strong>No AI schedule yet</strong>
                <p>${escapeHtml(message || 'The assistant could not generate a schedule right now.')}</p>
            </div>
        `;
        applyAiSuggestionsBtn?.classList.add('d-none');
    } else {
        aiSuggestionList.innerHTML = items.map(function (suggestion) {
            return `
                <div class="ai-suggestion-item">
                    <div class="ai-suggestion-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="ai-suggestion-copy">
                        <span class="ai-suggestion-slot">${escapeHtml(suggestion.slot || 'Suggested Block')}</span>
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <strong>${escapeHtml(suggestion.title || 'Suggestion')}</strong>
                            <span class="ai-suggestion-timing">${escapeHtml(suggestion.timing || 'Today')}</span>
                        </div>
                        <p class="mb-1">${escapeHtml(suggestion.detail || '')}</p>
                        <small class="ai-suggestion-reason"><span>Why this was suggested:</span> ${escapeHtml(suggestion.reason || 'It helps structure the day with clearer priorities.')}</small>
                    </div>
                </div>
            `;
        }).join('');
        applyAiSuggestionsBtn?.classList.remove('d-none');
    }

    aiAssistantEmptyState?.classList.add('d-none');
    aiSuggestionList?.classList.remove('d-none');
    persistAiScheduleState(summary, items, source, message);
}

function renderTimeLogs() {
    const selectedFilter = timeLogFilter ? timeLogFilter.value : 'all';
    const filteredLogs = dashboardTimeEntries.filter(function (log) {
        return selectedFilter === 'all' || log.activity === selectedFilter;
    });

    if (!filteredLogs.length) {
        const emptyMarkup = `
            <div class="time-log-empty">
                <i class="bi bi-stopwatch"></i>
                <strong>No time records yet</strong>
                <p>Stop the timer and save an entry to build your recent activity list.</p>
            </div>
        `;
        timeLogList.innerHTML = emptyMarkup;
        if (timeLogModalList) {
            timeLogModalList.innerHTML = emptyMarkup;
        }
        return;
    }

    const renderLogItems = function (items) {
        return items.map(function (log) {
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
    };

    timeLogList.innerHTML = renderLogItems(filteredLogs.slice(0, 3));

    if (timeLogModalList) {
        timeLogModalList.innerHTML = renderLogItems(filteredLogs);
    }
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
            durationLabel: payload.entry.durationLabel || formatDuration(payload.entry.durationMs),
            activity: payload.entry.activity,
            caseName: payload.entry.caseName,
            billable: payload.entry.billable,
            notes: payload.entry.description,
            savedAt: payload.entry.savedAt
        });

        if (payload.activityLog) {
            dashboardRecentActivities.unshift(payload.activityLog);
            renderRecentActivities();
        }

        renderTimeLogs();
        discardTimeEntry();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Time Entry Saved',
                text: payload.message || 'The timer entry has been saved successfully.',
                timer: 1800,
                showConfirmButton: false,
                customClass: {
                    popup: 'ila-swal-popup'
                }
            });
        }
    } catch (error) {
        alert('Unable to save the time entry right now. Please try again.');
    } finally {
        saveTimeBtn.disabled = false;
        spinner.classList.add('d-none');
    }
});

timeLogFilter?.addEventListener('change', renderTimeLogs);
renderTimeLogs();
renderRecentActivities();
restoreAiScheduleState();

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

applyAiSuggestionsBtn?.addEventListener('click', function () {
    if (!Array.isArray(currentAiSuggestions) || !currentAiSuggestions.length) {
        return;
    }

    const originalHtml = applyAiSuggestionsBtn.innerHTML;
    const applyAiSchedule = function (forceConflicts) {
        applyAiSuggestionsBtn.disabled = true;
        applyAiSuggestionsBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>Applying...';

        fetch(dashboardApplyAiScheduleUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                suggestions: currentAiSuggestions,
                force_conflicts: forceConflicts ? 1 : 0
            })
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (response.status === 409) {
                        const conflictError = new Error(payload.message || 'Possible schedule conflicts detected.');
                        conflictError.type = 'schedule_conflict';
                        conflictError.payload = payload;
                        throw conflictError;
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to apply the AI schedule right now.');
                    }

                    return payload;
                });
            })
            .then(function (payload) {
                clearAiScheduleState();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Schedule Applied',
                        text: payload.message + (payload.skipped ? ' ' + payload.skipped + ' duplicate block(s) were skipped.' : ''),
                        confirmButtonColor: '#0891b2',
                        customClass: {
                            popup: 'ila-swal-popup',
                            confirmButton: 'ila-swal-confirm'
                        },
                        buttonsStyling: false
                    });
                }
            })
            .catch(function (error) {
                if (error.type === 'schedule_conflict' && typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Possible Schedule Conflicts',
                        html: buildAiConflictHtml(error.payload?.conflicts || [], error.payload?.message || ''),
                        showCancelButton: true,
                        confirmButtonText: 'Apply Anyway',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#f59e0b',
                        cancelButtonColor: '#94a3b8',
                        backdrop: 'rgba(15, 23, 42, 0.5)',
                        customClass: {
                            popup: 'ila-swal-popup ila-conflict-popup',
                            confirmButton: 'ila-swal-confirm',
                            cancelButton: 'ila-swal-cancel'
                        },
                        buttonsStyling: false
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            applyAiSchedule(true);
                        }
                    });

                    return;
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Apply Failed',
                        text: error.message || 'Unable to apply the AI schedule right now.',
                        confirmButtonColor: '#0891b2',
                        customClass: {
                            popup: 'ila-swal-popup',
                            confirmButton: 'ila-swal-confirm'
                        },
                        buttonsStyling: false
                    });
                }
            })
            .finally(function () {
                applyAiSuggestionsBtn.disabled = false;
                applyAiSuggestionsBtn.innerHTML = originalHtml;
            });
    };

    applyAiSchedule(false);
});

dashboardDocumentScope?.addEventListener('change', syncDashboardDocumentScope);
dashboardDocumentLinkUrl?.addEventListener('input', syncDashboardExternalLinkConfirmation);
syncDashboardDocumentScope();
syncDashboardExternalLinkConfirmation();
promptDashboardScheduleConflict(pendingDashboardScheduleConflict);
</script>
@endsection
