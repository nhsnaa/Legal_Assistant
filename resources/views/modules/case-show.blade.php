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
                        <strong>{{ $case->created_at?->format('d M Y, h:i A') }}</strong>
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
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection
