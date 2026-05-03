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

    <div class="glass-card module-table-card">
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Case Title</th>
                        <th>Case Type</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cases as $case)
                        <tr>
                            <td class="module-id">#{{ $case->id }}</td>
                            <td class="font-weight-bold">{{ $case->case_name }}</td>
                            <td>{{ $case->case_type ?? 'Other' }}</td>
                            <td>{{ $case->client->name ?? 'Unassigned client' }}</td>
                            <td>
                                <span class="module-badge {{ $case->status === 'Active' ? 'module-badge-success' : ($case->status === 'Pending' ? 'module-badge-warning' : 'module-badge-muted') }}">
                                    {{ $case->status }}
                                </span>
                            </td>
                            <td>
                                <span class="module-badge {{ $case->priority === 'High' ? 'module-badge-danger' : ($case->priority === 'Medium' ? 'module-badge-info' : 'module-badge-warning') }}">
                                    {{ $case->priority }}
                                </span>
                            </td>
                            <td>
                                <div class="module-actions-inline">
                                    <a href="{{ route('cases.show', $case->id) }}" class="btn btn-sm btn-light">View</a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light"
                                        onclick="openCaseEditModal(@js([
                                            'id' => $case->id,
                                            'client_id' => $case->client_id,
                                            'case_name' => $case->case_name,
                                            'case_type' => $case->case_type ?? 'Other',
                                            'description' => $case->description,
                                            'status' => $case->status,
                                            'priority' => $case->priority,
                                            'progress' => $case->progress ?? 0,
                                        ]))">
                                        Edit
                                    </button>
                                    <form action="{{ route('cases.archive', $case->id) }}" method="POST" onsubmit="return confirm('{{ $case->status === 'Closed' ? 'Restore this case?' : 'Archive this case?' }}')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-light">
                                            {{ $case->status === 'Closed' ? 'Restore' : 'Archive' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('cases.destroy', $case->id) }}" method="POST" onsubmit="return confirm('Delete this case?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="module-empty-cell">No cases added to the system yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
                <label class="font-weight-bold small">Case Type</label>
                <select name="case_type" class="form-control module-input" required>
                    @foreach (['Criminal', 'Divorce', 'Family', 'Civil Litigation', 'Corporate', 'Employment', 'Property', 'Immigration', 'Other'] as $caseType)
                        <option value="{{ $caseType }}" {{ old('case_type', 'Other') === $caseType ? 'selected' : '' }}>{{ $caseType }}</option>
                    @endforeach
                </select>
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

<div id="caseEditModal" class="module-modal-backdrop" aria-hidden="true">
    <div class="module-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold mb-0">Edit Case</h4>
            <button type="button" class="module-modal-close" onclick="closeCaseEditModal()">&times;</button>
        </div>

        <form id="caseEditForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="font-weight-bold small">Assign Client</label>
                <select name="client_id" id="caseEditClient" class="form-control module-input" required>
                    <option value="">Select existing client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Case Title</label>
                <input type="text" name="case_name" id="caseEditName" class="form-control module-input" required>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Case Type</label>
                <select name="case_type" id="caseEditType" class="form-control module-input" required>
                    @foreach (['Criminal', 'Divorce', 'Family', 'Civil Litigation', 'Corporate', 'Employment', 'Property', 'Immigration', 'Other'] as $caseType)
                        <option value="{{ $caseType }}">{{ $caseType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Detailed Description</label>
                <textarea name="description" id="caseEditDescription" rows="3" class="form-control module-input"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" id="caseEditStatus" class="form-control module-input">
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Priority</label>
                    <select name="priority" id="caseEditPriority" class="form-control module-input">
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Progress (%)</label>
                <input type="number" name="progress" min="0" max="100" id="caseEditProgress" class="form-control module-input">
            </div>

            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeCaseEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Update Case</button>
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

function openCaseEditModal(caseItem) {
    const form = document.getElementById('caseEditForm');
    form.action = "{{ url('/cases') }}/" + caseItem.id;
    document.getElementById('caseEditClient').value = caseItem.client_id || '';
    document.getElementById('caseEditName').value = caseItem.case_name || '';
    document.getElementById('caseEditType').value = caseItem.case_type || 'Other';
    document.getElementById('caseEditDescription').value = caseItem.description || '';
    document.getElementById('caseEditStatus').value = caseItem.status || 'Active';
    document.getElementById('caseEditPriority').value = caseItem.priority || 'Medium';
    document.getElementById('caseEditProgress').value = caseItem.progress || 0;
    document.getElementById('caseEditModal').classList.add('show');
}

function closeCaseEditModal() {
    document.getElementById('caseEditModal').classList.remove('show');
}

@if ($errors->any())
openCaseModal();
@elseif (request()->boolean('create'))
openCaseModal();
@endif
</script>
@endsection
