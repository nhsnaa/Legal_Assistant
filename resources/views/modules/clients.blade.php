@extends('layouts.app')

@section('title', 'Clients')

@section('content')
<div class="module-shell">
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
            All <span id="clientCountAll">{{ $clientCounts['all'] }}</span>
        </a>
        <a href="{{ route('clients.index', ['filter' => 'active']) }}" class="module-filter-tab {{ $filter === 'active' ? 'active' : '' }}">
            Active <span id="clientCountActive">{{ $clientCounts['active'] }}</span>
        </a>
        <a href="{{ route('clients.index', ['filter' => 'archived']) }}" class="module-filter-tab {{ $filter === 'archived' ? 'active' : '' }}">
            Archive <span id="clientCountArchived">{{ $clientCounts['archived'] }}</span>
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
                <tbody id="clientTableBody">
                    @forelse ($clients as $client)
                        <tr data-client-row="{{ $client->id }}">
                            <td class="module-id">#{{ $client->id }}</td>
                            <td class="font-weight-bold">{{ $client->name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>
                                <span class="module-badge {{ $client->status === 'Active' ? 'module-badge-success' : 'module-badge-warning' }}" data-client-status-badge>
                                    {{ $client->status }}
                                </span>
                            </td>
                            <td>
                                <div class="module-actions-inline">
                                    <a href="{{ route('clients.show', $client->id) }}" class="btn btn-sm btn-light">
                                        Overview
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light"
                                        onclick="openClientEditModal(@js(['id' => $client->id, 'name' => $client->name, 'email' => $client->email, 'phone' => $client->phone, 'status' => $client->status]))">
                                        Edit
                                    </button>
                                    <form action="{{ route('clients.archive', $client->id) }}" method="POST" data-async-client-action="archive" data-client-id="{{ $client->id }}" data-confirm="{{ $client->status === 'Inactive' ? 'Restore this client?' : 'Archive this client?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-light" data-client-archive-button>
                                            {{ $client->status === 'Inactive' ? 'Restore' : 'Archive' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('clients.destroy', $client->id) }}" method="POST" data-async-client-action="delete" data-client-id="{{ $client->id }}" data-confirm="Delete this client?">
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

<div id="clientViewModal" class="module-modal-backdrop" aria-hidden="true">
    <div class="module-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold mb-0">Client Details</h4>
            <button type="button" class="module-modal-close" onclick="closeClientViewModal()">&times;</button>
        </div>

        <div class="module-detail-grid">
            <div class="module-detail-item">
                <label>Client ID</label>
                <strong id="clientViewId">-</strong>
            </div>
            <div class="module-detail-item">
                <label>Status</label>
                <strong id="clientViewStatus">-</strong>
            </div>
            <div class="module-detail-item">
                <label>Full Name</label>
                <strong id="clientViewName">-</strong>
            </div>
            <div class="module-detail-item">
                <label>Email Address</label>
                <strong id="clientViewEmail">-</strong>
            </div>
            <div class="module-detail-item full">
                <label>Phone Number</label>
                <strong id="clientViewPhone">-</strong>
            </div>
            <div class="module-detail-item">
                <label>Created By</label>
                <strong id="clientViewCreatedBy">-</strong>
            </div>
            <div class="module-detail-item">
                <label>Last Updated By</label>
                <strong id="clientViewUpdatedBy">-</strong>
            </div>
            <div class="module-detail-item">
                <label>Created On</label>
                <strong id="clientViewCreatedAt">-</strong>
            </div>
            <div class="module-detail-item">
                <label>Last Updated</label>
                <strong id="clientViewUpdatedAt">-</strong>
            </div>
        </div>

        <div class="module-modal-actions">
            <button type="button" class="btn btn-light px-4" onclick="closeClientViewModal()">Close</button>
        </div>
    </div>
</div>

<div id="clientEditModal" class="module-modal-backdrop" aria-hidden="true">
    <div class="module-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold mb-0">Edit Client</h4>
            <button type="button" class="module-modal-close" onclick="closeClientEditModal()">&times;</button>
        </div>

        <form id="clientEditForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="font-weight-bold small">Full Name</label>
                <input type="text" name="name" id="clientEditName" class="form-control module-input" required>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Email Address</label>
                <input type="email" name="email" id="clientEditEmail" class="form-control module-input" required>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Phone Number</label>
                <input type="text" name="phone" id="clientEditPhone" class="form-control module-input" required>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Status</label>
                <select name="status" id="clientEditStatus" class="form-control module-input">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeClientEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Update Client</button>
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
const currentClientFilter = @json($filter);

function openClientModal() {
    document.getElementById('clientModal').classList.add('show');
}

function closeClientModal() {
    document.getElementById('clientModal').classList.remove('show');
}

function openClientViewModal(client) {
    document.getElementById('clientViewId').textContent = '#' + client.id;
    document.getElementById('clientViewName').textContent = client.name;
    document.getElementById('clientViewEmail').textContent = client.email;
    document.getElementById('clientViewPhone').textContent = client.phone;
    document.getElementById('clientViewStatus').textContent = client.status;
    document.getElementById('clientViewCreatedBy').textContent = client.created_by_name || 'Not recorded';
    document.getElementById('clientViewUpdatedBy').textContent = client.updated_by_name || 'Not recorded';
    document.getElementById('clientViewCreatedAt').textContent = client.created_at_label || 'Not recorded';
    document.getElementById('clientViewUpdatedAt').textContent = client.updated_at_label || 'Not recorded';
    document.getElementById('clientViewModal').classList.add('show');
}

function closeClientViewModal() {
    document.getElementById('clientViewModal').classList.remove('show');
}

function openClientEditModal(client) {
    const form = document.getElementById('clientEditForm');
    form.action = "{{ url('/clients') }}/" + client.id;
    document.getElementById('clientEditName').value = client.name;
    document.getElementById('clientEditEmail').value = client.email;
    document.getElementById('clientEditPhone').value = client.phone;
    document.getElementById('clientEditStatus').value = client.status;
    document.getElementById('clientEditModal').classList.add('show');
}

function closeClientEditModal() {
    document.getElementById('clientEditModal').classList.remove('show');
}

function updateClientCounts(counts) {
    if (!counts) return;
    document.getElementById('clientCountAll').textContent = counts.all ?? document.getElementById('clientCountAll').textContent;
    document.getElementById('clientCountActive').textContent = counts.active ?? document.getElementById('clientCountActive').textContent;
    document.getElementById('clientCountArchived').textContent = counts.archived ?? document.getElementById('clientCountArchived').textContent;
}

function ensureClientEmptyState() {
    const tbody = document.getElementById('clientTableBody');
    if (!tbody || tbody.querySelector('[data-client-row]')) return;

    tbody.innerHTML = '<tr><td colspan="6" class="module-empty-cell">No clients registered in the system yet.</td></tr>';
}

async function handleClientAction(form) {
    const confirmed = confirm(form.dataset.confirm || 'Continue with this action?');
    if (!confirmed) {
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton ? submitButton.innerHTML : '';

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = 'Working...';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: new FormData(form)
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Action failed.');
        }

        const row = document.querySelector(`[data-client-row="${form.dataset.clientId}"]`);
        updateClientCounts(payload.counts);

        if (form.dataset.asyncClientAction === 'delete') {
            row?.remove();
            ensureClientEmptyState();
        } else if (form.dataset.asyncClientAction === 'archive' && row) {
            const nextStatus = payload.status || 'Active';
            const badge = row.querySelector('[data-client-status-badge]');
            const archiveButton = row.querySelector('[data-client-archive-button]');
            const archiveForm = row.querySelector('[data-async-client-action="archive"]');

            if (currentClientFilter === 'active' && nextStatus === 'Inactive') {
                row.remove();
                ensureClientEmptyState();
            } else if (currentClientFilter === 'archived' && nextStatus === 'Active') {
                row.remove();
                ensureClientEmptyState();
            } else {
                if (badge) {
                    badge.textContent = nextStatus;
                    badge.classList.toggle('module-badge-success', nextStatus === 'Active');
                    badge.classList.toggle('module-badge-warning', nextStatus !== 'Active');
                }

                if (archiveButton) {
                    archiveButton.textContent = nextStatus === 'Inactive' ? 'Restore' : 'Archive';
                }

                if (archiveForm) {
                    archiveForm.dataset.confirm = nextStatus === 'Inactive' ? 'Restore this client?' : 'Archive this client?';
                }
            }
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Done',
                text: payload.message || 'Action completed successfully.',
                timer: 1400,
                showConfirmButton: false,
                customClass: {
                    popup: 'ila-swal-popup'
                }
            });
        }
    } catch (error) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Attention',
                text: error.message || 'Unable to complete this action right now.',
                customClass: {
                    popup: 'ila-swal-popup',
                    confirmButton: 'ila-swal-confirm'
                },
                buttonsStyling: false
            });
        }
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    }
}

document.querySelectorAll('form[data-async-client-action]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        handleClientAction(form);
    });
});

@if ($errors->any())
openClientModal();
@elseif (request()->boolean('create'))
openClientModal();
@endif
</script>
@endsection
