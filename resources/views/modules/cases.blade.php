@extends('layouts.app')

@section('title', 'Cases')

@section('content')
<div class="module-shell">
    {{-- Alerts for Success/Errors --}}
    @if (session('success'))
        <div class="module-alert module-alert-success">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Matter Tracker</span>
            <h1 class="module-title">Legal Portfolio</h1>
            <p class="module-copy mb-0">Track active matters, priority, progress, and summaries.</p>
        </div>
        <div class="module-hero-icon"><i class="bi bi-briefcase-fill"></i></div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Case Workspace</h5>
            <p class="text-muted mb-0">Manage your active and closed legal cases.</p>
        </div>
        <button type="button" class="btn btn-primary px-4 py-2" onclick="openCaseModal()">
            <i class="bi bi-plus-lg mr-2"></i>Create Case
        </button>
    </div>

    {{-- Case Table --}}
    <div class="glass-card module-table-card">
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead>
                    <tr>
                        <th>Case Name</th>
                        <th>Client</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cases as $case)
                    <tr>
                        <td><strong>{{ $case->case_name }}</strong></td>
                        <td>{{ optional($case->client)->name ?? 'Unassigned' }}</td>
                        <td><span class="badge badge-{{ $case->priority == 'High' ? 'danger' : 'info' }}">{{ $case->priority }}</span></td>
                        <td>{{ $case->status }}</td>
                        <td>{{ $case->progress }}%</td>
                        <td>
                            <a href="{{ route('cases.show', $case->id) }}" class="btn btn-sm btn-light">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Case Modal --}}
<div id="caseModal" class="module-modal">
    <div class="module-modal-content glass-card">
        <h4 class="font-weight-bold mb-4">New Case Entry</h4>
        <form action="{{ route('cases.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="small font-weight-bold">Case Name</label>
                <input type="text" name="case_name" class="form-control module-input" required>
            </div>
            <div class="form-group">
                <label class="small font-weight-bold">Assign Client</label>
                <select name="client_id" class="form-control module-input">
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="module-modal-actions">
                <button type="button" class="btn btn-light" onclick="closeCaseModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Case</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openCaseModal() { document.getElementById('caseModal').classList.add('show'); }
function closeCaseModal() { document.getElementById('caseModal').classList.remove('show'); }
</script>
@endsection