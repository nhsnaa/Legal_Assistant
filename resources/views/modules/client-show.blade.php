@extends('layouts.app')

@section('title', 'Client Details')

@section('content')
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Client Record</span>
            <h1 class="module-title">{{ $client->name }}</h1>
            <p class="module-copy mb-0">Review the saved contact details and linked case activity for this client.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-person-lines-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Client Information</h5>
            <p class="text-muted mb-0">A simple view for checking saved client details during a walkthrough or review.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn btn-light px-4 py-2">Back to Clients</a>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="module-detail-grid">
                    <div class="module-detail-item">
                        <label>Client ID</label>
                        <strong>#{{ $client->id }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Status</label>
                        <strong>{{ $client->status }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Full Name</label>
                        <strong>{{ $client->name }}</strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Email Address</label>
                        <strong>{{ $client->email }}</strong>
                    </div>
                    <div class="module-detail-item full">
                        <label>Phone Number</label>
                        <strong>{{ $client->phone }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="glass-card p-4 h-100">
                <h6 class="font-weight-bold mb-3">Linked Cases</h6>
                <div class="module-note-list">
                    <div class="module-note-item">
                        <strong>Total Linked Cases</strong>
                        <p class="mb-0">{{ $linkedCases->count() }} case record(s) linked to this client.</p>
                    </div>
                    @forelse ($linkedCases->take(4) as $case)
                        <div class="module-note-item">
                            <strong>{{ $case->case_name ?? 'Untitled Case' }}</strong>
                            <p class="mb-0">{{ $case->status ?? 'Active' }} | {{ $case->priority ?? 'Medium' }} Priority</p>
                        </div>
                    @empty
                        <div class="module-note-item">
                            <strong>No linked cases yet</strong>
                            <p class="mb-0">This client has not been assigned to any saved case.</p>
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
