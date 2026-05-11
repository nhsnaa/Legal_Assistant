@extends('layouts.app')

@section('title', 'Client Overview')

@section('content')
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Client Overview</span>
            <h1 class="module-title">{{ $client->name }}</h1>
            <p class="module-copy mb-0">See this client's contact details, linked cases, documents, schedule activity, and recorded work in one place.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-person-lines-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Client Summary Hub</h5>
            <p class="text-muted mb-0">A client-centered view for reviewing connected legal work without jumping across multiple modules.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn btn-light px-4 py-2">Back to Clients</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 h-100 text-center">
                <small class="text-muted d-block mb-2">Cases</small>
                <div class="h3 font-weight-bold mb-0">{{ $overviewStats['cases'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 h-100 text-center">
                <small class="text-muted d-block mb-2">Documents</small>
                <div class="h3 font-weight-bold mb-0">{{ $overviewStats['documents'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 h-100 text-center">
                <small class="text-muted d-block mb-2">Upcoming Tasks</small>
                <div class="h3 font-weight-bold mb-0">{{ $overviewStats['upcoming_tasks'] }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 h-100 text-center">
                <small class="text-muted d-block mb-2">Time Records</small>
                <div class="h3 font-weight-bold mb-0">{{ $overviewStats['time_entries'] }}</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Profile</h6>
                    <span class="module-badge {{ $client->status === 'Active' ? 'module-badge-success' : 'module-badge-warning' }}">
                        {{ $client->status }}
                    </span>
                </div>

                <div class="module-detail-grid">
                    <div class="module-detail-item">
                        <label>Client ID</label>
                        <strong>#{{ $client->id }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Full Name</label>
                        <strong>{{ $client->name }}</strong>
                    </div>
                    <div class="module-detail-item full">
                        <label>Email Address</label>
                        <strong>{{ $client->email }}</strong>
                    </div>
                    <div class="module-detail-item full">
                        <label>Phone Number</label>
                        <strong>{{ $client->phone }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Created By</label>
                        <strong>{{ $client->created_by_name ?? 'Not recorded' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Last Updated By</label>
                        <strong>{{ $client->updated_by_name ?? 'Not recorded' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Created On</label>
                        <strong>{{ $client->created_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Last Updated</label>
                        <strong>{{ $client->updated_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Related Cases</h6>
                    <span class="text-muted tiny uppercase">{{ $linkedCases->count() }} case{{ $linkedCases->count() === 1 ? '' : 's' }}</span>
                </div>

                @if ($linkedCases->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table module-table mb-0">
                            <thead>
                                <tr>
                                    <th>Case Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Progress</th>
                                    <th>Open</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($linkedCases as $case)
                                    <tr>
                                        <td class="font-weight-bold">{{ $case->case_name ?? 'Untitled Case' }}</td>
                                        <td>{{ $case->case_type ?? 'Other' }}</td>
                                        <td>
                                            <span class="module-badge {{ ($case->status ?? 'Active') === 'Active' ? 'module-badge-success' : (($case->status ?? '') === 'Pending' ? 'module-badge-warning' : 'module-badge-muted') }}">
                                                {{ $case->status ?? 'Active' }}
                                            </span>
                                        </td>
                                        <td>{{ $case->priority ?? 'Medium' }}</td>
                                        <td>{{ (int) ($case->progress ?? 0) }}%</td>
                                        <td>
                                            <a href="{{ route('cases.show', $case->id) }}" class="btn btn-sm btn-light">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="module-empty-state py-4">
                        <div class="module-empty-icon"><i class="bi bi-briefcase"></i></div>
                        <h6 class="font-weight-bold">No linked cases yet</h6>
                        <p class="text-muted mb-0">This client has not been connected to any case record yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Related Documents</h6>
                    <span class="text-muted tiny uppercase">{{ $linkedDocuments->count() }} file{{ $linkedDocuments->count() === 1 ? '' : 's' }}</span>
                </div>

                @if ($linkedDocuments->isNotEmpty())
                    <div class="module-note-list">
                        @foreach ($linkedDocuments->take(6) as $document)
                            <div class="module-note-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $document->name }}</strong>
                                        <p class="mb-1">{{ $document->category ?? 'Uncategorized' }} | {{ ($document->type ?? 'file') === 'link' ? 'Link' : 'File' }}</p>
                                        <small class="text-muted">
                                            @if (($document->scope ?? 'general') === 'case')
                                                Linked through case: {{ $document->case_name ?? 'Unknown Case' }}
                                            @elseif (($document->scope ?? 'general') === 'client')
                                                Linked directly to this client
                                            @else
                                                General document
                                            @endif
                                        </small>
                                    </div>
                                    <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-sm btn-light ml-3">Open</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="module-empty-state py-4">
                        <div class="module-empty-icon"><i class="bi bi-folder2-open"></i></div>
                        <h6 class="font-weight-bold">No related documents</h6>
                        <p class="text-muted mb-0">Documents linked to this client or their cases will appear here.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold mb-0">Schedule Snapshot</h6>
                    <span class="text-muted tiny uppercase">{{ $linkedHearings->count() }} item{{ $linkedHearings->count() === 1 ? '' : 's' }}</span>
                </div>

                @if ($linkedHearings->isNotEmpty())
                    <div class="module-note-list">
                        @foreach ($linkedHearings->take(6) as $hearing)
                            <div class="module-note-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $hearing->title ?? ($hearing->case_name ?? 'Untitled Task') }}</strong>
                                        <p class="mb-1">{{ $hearing->case_name ?? 'General Task' }}</p>
                                        <small class="text-muted">{{ $hearing->hearing_time->format('d M Y, g:i A') }}</small>
                                    </div>
                                    <span class="module-badge {{ $hearing->status_badge_class }}">
                                        {{ $hearing->display_status }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="module-empty-state py-4">
                        <div class="module-empty-icon"><i class="bi bi-calendar2-event"></i></div>
                        <h6 class="font-weight-bold">No linked schedule activity</h6>
                        <p class="text-muted mb-0">Tasks and hearings tied to this client's cases will appear here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="font-weight-bold mb-0">Time Record Snapshot</h6>
            <span class="text-muted tiny uppercase">{{ $linkedTimeEntries->count() }} entr{{ $linkedTimeEntries->count() === 1 ? 'y' : 'ies' }}</span>
        </div>

        @if ($linkedTimeEntries->isNotEmpty())
            <div class="table-responsive">
                <table class="table module-table mb-0">
                    <thead>
                        <tr>
                            <th>Case</th>
                            <th>Date</th>
                            <th>Duration</th>
                            <th>Activity</th>
                            <th>Billing</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($linkedTimeEntries->take(8) as $entry)
                            <tr>
                                <td class="font-weight-bold">{{ $entry->case_name ?? 'General' }}</td>
                                <td>{{ $entry->work_date->format('d M Y') }}</td>
                                <td>
                                    <div class="font-weight-bold">{{ $entry->duration_label }}</div>
                                    <small class="text-muted">{{ number_format((float) ($entry->hours ?? 0), 2) }} hours</small>
                                </td>
                                <td>{{ $entry->activity ?? 'General Work' }}</td>
                                <td>
                                    <span class="module-badge {{ !empty($entry->billable) ? 'module-badge-info' : 'module-badge-muted' }}">
                                        {{ !empty($entry->billable) ? 'Billable' : 'Non-Billable' }}
                                    </span>
                                </td>
                                <td>{{ $entry->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="module-empty-state py-4">
                <div class="module-empty-icon"><i class="bi bi-stopwatch"></i></div>
                <h6 class="font-weight-bold">No linked time records</h6>
                <p class="text-muted mb-0">Time entries recorded against this client's cases will appear here.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection
