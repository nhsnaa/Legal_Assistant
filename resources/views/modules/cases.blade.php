@extends('layouts.app')

@section('title', 'Cases')

@section('content')
<div class="module-shell">
    @if (session('success'))
        <div class="module-alert module-alert-success">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="module-alert module-alert-danger">
            <span>{{ $errors->first() }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Matter Tracker</span>
            <h1 class="module-title">Legal Portfolio</h1>
            <p class="module-copy mb-0">Track active matters, priority, progress, and summaries in one case-focused workspace.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-briefcase-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Case Workspace</h5>
            <p class="text-muted mb-0">Track and manage your active and closed legal cases in a single workspace.</p>
        </div>
        <button type="button" class="btn btn-primary px-4 py-2" onclick="openCaseModal()">
            <i class="bi bi-plus-lg mr-2"></i>Open New Case
        </button>
    </div>

    <div class="module-filter-tabs mb-4">
        <a href="{{ route('cases.index', ['filter' => 'all']) }}" class="module-filter-tab {{ $filter === 'all' ? 'active' : '' }}">
            All <span>{{ $caseCounts['all'] }}</span>
        </a>
        <a href="{{ route('cases.index', ['filter' => 'active']) }}" class="module-filter-tab {{ $filter === 'active' ? 'active' : '' }}">
            Active <span>{{ $caseCounts['active'] }}</span>
        </a>
        <a href="{{ route('cases.index', ['filter' => 'archived']) }}" class="module-filter-tab {{ $filter === 'archived' ? 'active' : '' }}">
            Archive <span>{{ $caseCounts['archived'] }}</span>
        </a>
    </div>

    <div class="module-case-grid">
        @forelse ($cases as $case)
            <div class="glass-card module-case-card">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1">{{ $case->case_name }}</h5>
                            <small class="module-id">REF-ID: {{ $case->id }}</small>
                        </div>
                    </div>

                    <div class="module-case-tags">
                        <span class="module-badge {{ $case->status === 'Active' ? 'module-badge-success' : ($case->status === 'Pending' ? 'module-badge-warning' : 'module-badge-muted') }}">
                            {{ $case->status }}
                        </span>
                        <span class="module-badge {{ $case->priority === 'High' ? 'module-badge-danger' : ($case->priority === 'Medium' ? 'module-badge-info' : 'module-badge-warning') }}">
                            {{ $case->priority }} Priority
                        </span>
                    </div>

                    <div class="module-case-meta mb-3">
                        <span><strong>Client:</strong> {{ $case->client->name ?? 'Unassigned client' }}</span>
                    </div>

                    <div class="module-progress-wrap">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted font-weight-bold">Completion</span>
                            <span class="small text-primary font-weight-bold">{{ $case->progress ?? 0 }}%</span>
                        </div>
                        <div class="progress module-progress">
                            <div class="progress-bar" role="progressbar" style="width: {{ $case->progress ?? 0 }}%"></div>
                        </div>
                    </div>

                    <p class="module-case-description mb-0">
                        {{ $case->description ?: 'No case summary provided.' }}
                    </p>
                </div>

                <div class="module-actions-inline mt-4">
                    <a href="{{ route('cases.show', $case->id) }}" class="btn btn-sm btn-light">View Details</a>
                    <form action="{{ route('cases.destroy', $case->id) }}" method="POST" onsubmit="return confirm('Delete this case?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="glass-card module-empty-state">
                <div class="module-empty-icon"><i class="bi bi-briefcase"></i></div>
                <h5 class="font-weight-bold">No cases found</h5>
                <p class="text-muted mb-0">You haven't added any legal cases to the system yet.</p>
            </div>
        @endforelse
    </div>
</div>

<div id="caseModal" class="module-modal-backdrop" aria-hidden="true">
    <div class="module-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold mb-0">Open New Case</h4>
            <button type="button" class="module-modal-close" onclick="closeCaseModal()">&times;</button>
        </div>

        <form action="{{ route('cases.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="font-weight-bold small">Assign Client</label>
                <select name="client_id" class="form-control module-input" required>
                    <option value="">Select existing client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" {{ (string) old('client_id') === (string) $client->id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
                @if ($clients->isEmpty())
                    <small class="text-muted d-block mt-2">Add a client first before opening a new case.</small>
                @endif
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Case Title</label>
                <input type="text" name="case_name" class="form-control module-input" required value="{{ old('case_name') }}">
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Detailed Description</label>
                <textarea name="description" rows="3" class="form-control module-input">{{ old('description') }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control module-input">
                        <option value="Active" {{ old('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Pending" {{ old('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Closed" {{ old('status') === 'Closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Priority</label>
                    <select name="priority" class="form-control module-input">
                        <option value="High" {{ old('priority') === 'High' ? 'selected' : '' }}>High</option>
                        <option value="Medium" {{ old('priority', 'Medium') === 'Medium' ? 'selected' : '' }}>Medium</option>
                        <option value="Low" {{ old('priority') === 'Low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Initial Progress (%)</label>
                <input type="number" name="progress" min="0" max="100" class="form-control module-input" value="{{ old('progress', 0) }}">
            </div>

            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeCaseModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Create Case</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection

@section('scripts')
<script>
function openCaseModal() {
    document.getElementById('caseModal').classList.add('show');
}

function closeCaseModal() {
    document.getElementById('caseModal').classList.remove('show');
}

@if ($errors->any())
openCaseModal();
@elseif (request()->boolean('create'))
openCaseModal();
@endif
</script>
@endsection
