@extends('layouts.app')

@section('title', 'Case Details')

@section('content')
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Matter Record</span>
            <h1 class="module-title">{{ $case->case_name }}</h1>
            <p class="module-copy mb-0">Review the full case information exactly as it was saved into the system.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-journal-richtext"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Case Information</h5>
            <p class="text-muted mb-0">This view helps users check the linked client, progress, priority, and case description later.</p>
        </div>
        <a href="{{ route('cases.index') }}" class="btn btn-light px-4 py-2">Back to Cases</a>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="module-detail-grid">
                    <div class="module-detail-item">
                        <label>Case Title</label>
                        <strong>{{ $case->case_name }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Client</label>
                        <strong>{{ optional($case->client)->name ?: 'Unassigned client' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Case Type</label>
                        <strong>{{ $case->case_type ?? 'Other' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Status</label>
                        <strong>{{ $case->status }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Priority</label>
                        <strong>{{ $case->priority }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Progress</label>
                        <strong>{{ $case->progress }}%</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Created</label>
                        <strong>{{ $case->created_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Created By</label>
                        <strong>{{ $case->created_by_name ?? 'Not recorded' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Last Updated</label>
                        <strong>{{ $case->updated_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Last Updated By</label>
                        <strong>{{ $case->updated_by_name ?? 'Not recorded' }}</strong>
                    </div>
                    <div class="module-detail-item full">
                        <label>Description</label>
                        <p class="mb-0">{{ $case->description ?: 'No case summary provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="glass-card p-4 h-100">
                <h6 class="font-weight-bold mb-3">Case Snapshot</h6>
                <div class="module-note-list">
                    <div class="module-note-item">
                        <strong>Linked Time Entries</strong>
                        <p class="mb-0">{{ $case->timeEntries->count() }} work log(s) recorded for this case.</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Client Email</strong>
                        <p class="mb-0">{{ optional($case->client)->email ?: 'No client email available.' }}</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Client Phone</strong>
                        <p class="mb-0">{{ optional($case->client)->phone ?: 'No client phone available.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4">
            <div class="glass-card p-4 h-100">
                <h5 class="font-weight-bold mb-2">Add Case Update</h5>
                <p class="text-muted small mb-4">Record the latest progress without replacing the original case information.</p>

                <form action="{{ route('cases.updates.store', $case->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold small">Update Note</label>
                        <textarea name="note" rows="5" class="form-control module-input" required placeholder="Example: Documents submitted to land office.">{{ old('note') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Current Status</label>
                        <select name="status" class="form-control module-input" required>
                            @foreach ($caseStatuses as $status)
                                <option value="{{ $status }}" {{ old('status', $case->status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Progress</label>
                        <select name="progress" class="form-control module-input" required>
                            @foreach ($progressOptions as $progress)
                                <option value="{{ $progress }}" {{ (int) old('progress', $case->progress ?? 0) === $progress ? 'selected' : '' }}>{{ $progress }}%</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save Update</button>
                </form>
            </div>
        </div>

        <div class="col-xl-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h5 class="font-weight-bold mb-1">Case Update Timeline</h5>
                        <p class="text-muted small mb-0">A dated history of progress notes, status changes, and users who recorded them.</p>
                    </div>
                    <span class="module-badge module-badge-info">{{ $caseUpdates->count() }} update{{ $caseUpdates->count() === 1 ? '' : 's' }}</span>
                </div>

                <div class="case-timeline">
                    @forelse ($caseUpdates as $update)
                        <div class="case-timeline-item">
                            <div class="case-timeline-marker"></div>
                            <div class="case-timeline-card">
                                <div class="case-timeline-meta">
                                    <span>{{ $update->created_at?->format('d M Y, h:i A') ?? 'Date not recorded' }}</span>
                                    <span>{{ $update->created_by_name ?? 'Workspace User' }}</span>
                                </div>
                                <p class="mb-3">{{ $update->note ?? 'No update note provided.' }}</p>
                                <div class="case-timeline-tags">
                                    <span class="module-badge module-badge-info">{{ $update->status ?? 'Active' }}</span>
                                    <span class="module-badge module-badge-muted">{{ (int) ($update->progress ?? 0) }}% progress</span>
                                    @if (!empty($update->system_entry))
                                        <span class="module-badge module-badge-muted">System entry</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="time-log-empty">
                            <i class="bi bi-clock-history"></i>
                            <strong>No case updates yet</strong>
                            <p>Add the first update when there is new progress, a status change, or an important follow-up.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection
