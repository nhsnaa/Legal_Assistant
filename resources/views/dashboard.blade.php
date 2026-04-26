@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div>
    {{-- Success Alerts --}}
    @if (session('success'))
        <div class="module-alert module-alert-success mb-4">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

    <h6 class="font-weight-bold mb-4">Quick Create</h6>
    <div class="row mb-5">
        @php
            $actions = [
                ['bi bi-person-plus-fill', 'Client', 'modal', 'dashboardClientModal'],
                ['bi bi-briefcase-fill', 'Case', 'modal', 'dashboardCaseModal'],
                ['bi bi-stopwatch-fill', 'Time', '#timerWorkspace'],
                ['bi bi-calendar-plus-fill', 'Event', 'modal', 'dashboardEventModal'],
                ['bi bi-folder2-open', 'Doc', 'modal', 'dashboardDocumentModal'],
                ['bi bi-journal-text', 'Note', route('settings') . '#document']
            ];
        @endphp

        @foreach ($actions as $action)
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            @if (($action[2] ?? null) === 'modal')
                <button type="button" class="btn btn-action w-100 py-4" onclick="openDashboardModal('{{ $action[3] }}')">
                    <span class="action-icon"><i class="{{ $action[0] }}"></i></span>
                    {{ $action[1] }}
                </button>
            @else
                <a href="{{ $action[2] }}" class="btn btn-action w-100 py-4">
                    <span class="action-icon"><i class="{{ $action[0] }}"></i></span>
                    {{ $action[1] }}
                </a>
            @endif
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            {{-- Summary Stats --}}
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="summary-card h-100">
                        <p class="mb-1 small opacity-75">Active Cases</p>
                        <h1 class="display-4 font-weight-bold">{{ number_format($activeCases) }}</h1>
                        <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.1);">
                            <div class="progress-bar bg-white" style="width: {{ min(($activeCases / 50) * 100, 100) }}%"></div>
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

            {{-- Active Work Session Card --}}
            <div class="schedule-timer-card mb-4">
                <div class="schedule-timer-copy">
                    <div class="schedule-timer-icon"><i class="bi bi-stopwatch-fill"></i></div>
                    <div>
                        <span class="schedule-timer-kicker">Active Work Session</span>
                        <h4 class="font-weight-bold mb-1">Keep today’s legal work in view</h4>
                        <p class="mb-0 small">Use the topbar timer for live tracking.</p>
                    </div>
                </div>
                <div class="schedule-timer-actions">
                    <div class="schedule-session-summary">
                        <div class="schedule-session-stat"><span>Month</span><strong>{{ now()->format('F') }}</strong></div>
                        <div class="schedule-session-stat"><span>Hearings</span><strong>{{ count($hearings) }}</strong></div>
                        <div class="schedule-session-stat"><span>Documents</span><strong>{{ $documentsCount }}</strong></div>
                    </div>
                    <div class="schedule-timer-buttons">
                        <a href="{{ route('schedule') }}" class="btn btn-light btn-sm">Open Calendar</a>
                        <a href="#timerWorkspace" class="btn btn-primary btn-sm">Review Timer</a>
                    </div>
                </div>
            </div>

            {{-- Upcoming Hearings --}}
            <div class="glass-card mb-4">
                <div class="p-4 border-bottom d-flex justify-content-between">
                    <h6 class="font-weight-bold mb-0">Upcoming Court Hearings</h6>
                    <a href="{{ route('schedule') }}" class="btn btn-sm btn-link font-weight-bold p-0">View All</a>
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
                                        <span class="badge-status-{{ ($hearing['status'] ?? '') == 'Confirmed' ? 'green' : 'orange' }}">
                                            {{ $hearing['status'] ?? 'Scheduled' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-5 text-muted">No upcoming hearings.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            {{-- Documents --}}
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="font-weight-bold mb-0">Recent Documents</h6>
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-link font-weight-bold p-0">Open</a>
                </div>
                <div class="doc-list">
                    @forelse($recentDocuments as $id => $document)
                        @php
                            $ext = strtolower(pathinfo($document['original_name'] ?? '', PATHINFO_EXTENSION));
                        @endphp
                        <div class="doc-item d-flex align-items-center mb-3 p-3 rounded border">
                            <div class="icon-circle bg-primary text-white mr-3"><i class="bi bi-file-earmark"></i></div>
                            <div class="flex-grow-1">
                                <div class="small font-weight-bold">{{ $document['name'] ?? 'Untitled' }}</div>
                                <div class="text-muted tiny">{{ strtoupper($ext ?: 'FILE') }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">No documents found.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent Time Records --}}
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="font-weight-bold mb-0">Recent Time Records</h6>
                    <span class="tiny text-muted uppercase" id="timeFilterCount">0 entries</span>
                </div>
                <div class="mb-3">
                    <select id="timeLogFilter" class="form-control form-control-pill">
                        <option value="all">All Activities</option>
                        <option>Document Review</option>
                        <option>Client Interview</option>
                        <option>Case Preparation</option>
                    </select>
                </div>
                <div class="time-log-list" id="timeLogList"></div>
            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}
@include('layouts.modals.logout')

{{-- Time Entry Panel --}}
<div class="time-entry-panel" id="timeEntryPanel">
    <div class="time-entry-card p-4">
        <form id="timeEntryForm">
            <div class="form-group">
                <label>Related Case</label>
                <select id="timeEntryCase" class="form-control" required>
                    <option value="">Select case</option>
                    @foreach ($caseOptions as $id => $opt)
                        <option value="{{ $id }}">{{ $opt['case_name'] ?? 'Unknown' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Activity</label>
                <select id="timeEntryActivity" class="form-control" required>
                    <option>Document Review</option>
                    <option>Client Interview</option>
                    <option>Case Preparation</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea id="timeEntryNotes" class="form-control" rows="3" required></textarea>
            </div>
            <input type="hidden" id="timeEntryDuration">
            <button type="submit" id="saveTimeBtn" class="btn btn-primary w-100">Save Record</button>
        </form>
    </div>
</div>

{{-- Case Creation Modal --}}
<div id="dashboardCaseModal" class="module-modal-backdrop">
    <div class="module-modal-card">
        <h4 class="font-weight-bold">New Case</h4>
        <form action="{{ route('cases.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Client</label>
                <select name="client_id" class="form-control" required>
                    @foreach ($clients ?? [] as $cid => $client)
                        <option value="{{ $cid }}">{{ $client['name'] ?? 'N/A' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Case Name</label>
                <input type="text" name="case_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Create</button>
            <button type="button" class="btn btn-light" onclick="closeDashboardModal('dashboardCaseModal')">Cancel</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Logic for Firebase Data Rendering
    const recentTimeEntries = @json($recentTimeEntryData ?? []);
    const dashboardTimeEntryUrl = @json(route('dashboard.time-entries.store'));
    const csrfToken = @json(csrf_token());
    let dashboardTimeEntries = Array.isArray(recentTimeEntries) ? recentTimeEntries.slice() : [];

    function renderTimeLogs() {
        const list = document.getElementById('timeLogList');
        if (!list) return;
        
        list.innerHTML = dashboardTimeEntries.map(log => `
            <div class="time-log-item mb-2 p-2 border-bottom">
                <strong>${log.activity || 'Activity'}</strong><br>
                <small>${log.durationLabel || ''} - ${log.caseName || 'No Case'}</small>
            </div>
        `).join('') || '<p class="text-muted">No logs.</p>';
    }

    function openDashboardModal(id) { document.getElementById(id)?.classList.add('show'); }
    function closeDashboardModal(id) { document.getElementById(id)?.classList.remove('show'); }

    // Chart init
    const ctx = document.getElementById('activityChart')?.getContext('2d');
    if(ctx) {
        new Chart(ctx, {
            type: 'line',
            data: { labels: @json($chartLabels ?? []), datasets: [{ data: @json($chartData ?? []), borderColor: '#0891b2' }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }

    // Handle Form Submit
    document.getElementById('timeEntryForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveTimeBtn');
        btn.disabled = true;
        
        try {
            const res = await fetch(dashboardTimeEntryUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    case_id: document.getElementById('timeEntryCase').value,
                    activity: document.getElementById('timeEntryActivity').value,
                    description: document.getElementById('timeEntryNotes').value
                })
            });
            if(res.ok) location.reload();
        } catch(err) { console.error(err); }
        finally { btn.disabled = false; }
    });

    renderTimeLogs();
</script>
@endsection