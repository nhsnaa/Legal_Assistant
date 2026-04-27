@extends('layouts.app')

@section('title', 'Clients')

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
            <span class="module-kicker">Relationship Hub</span>
            <h1 class="module-title">Managing Clients</h1>
            <p class="module-copy mb-0">Keep client contact records organized, accessible, and ready for daily legal follow-up.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-people-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Client Directory</h5>
            <p class="text-muted mb-0">Manage your client information and contacts in one clean directory.</p>
        </div>
        <button type="button" class="btn btn-primary px-4 py-2" onclick="openClientModal()">
            <i class="bi bi-plus-lg mr-2"></i>Add New Client
        </button>
    </div>

    <div class="module-filter-tabs mb-4">
        <a href="{{ route('clients.index', ['filter' => 'all']) }}" class="module-filter-tab {{ $filter === 'all' ? 'active' : '' }}">
            All <span>{{ $clientCounts['all'] }}</span>
        </a>
        <a href="{{ route('clients.index', ['filter' => 'active']) }}" class="module-filter-tab {{ $filter === 'active' ? 'active' : '' }}">
            Active <span>{{ $clientCounts['active'] }}</span>
        </a>
        <a href="{{ route('clients.index', ['filter' => 'archived']) }}" class="module-filter-tab {{ $filter === 'archived' ? 'active' : '' }}">
            Archive <span>{{ $clientCounts['archived'] }}</span>
        </a>
    </div>

    <div class="glass-card module-table-card">
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td class="module-id">#{{ $client->id }}</td>
                            <td class="font-weight-bold">{{ $client->name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>
                                <span class="module-badge {{ $client->status === 'Active' ? 'module-badge-success' : 'module-badge-warning' }}">
                                    {{ $client->status }}
                                </span>
                            </td>
                            <td>
                                <div class="module-actions-inline">
                                    <form action="{{ route('clients.destroy', $client->id) }}" method="POST" onsubmit="return confirm('Delete this client?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="module-empty-cell">No clients registered in the system yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="clientModal" class="module-modal-backdrop" aria-hidden="true">
    <div class="module-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold mb-0">Add New Client</h4>
            <button type="button" class="module-modal-close" onclick="closeClientModal()">&times;</button>
        </div>

        <form action="{{ route('clients.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="font-weight-bold small">Full Name</label>
                <input type="text" name="name" class="form-control module-input" required value="{{ old('name') }}">
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Email Address</label>
                <input type="email" name="email" class="form-control module-input" required value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Phone Number</label>
                <input type="text" name="phone" class="form-control module-input" required value="{{ old('phone') }}">
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Status</label>
                <select name="status" class="form-control module-input">
                    <option value="Active" {{ old('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeClientModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Add Client</button>
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
function openClientModal() {
    document.getElementById('clientModal').classList.add('show');
}

function closeClientModal() {
    document.getElementById('clientModal').classList.remove('show');
}

@if ($errors->any())
openClientModal();
@elseif (request()->boolean('create'))
openClientModal();
@endif
</script>
@endsection
