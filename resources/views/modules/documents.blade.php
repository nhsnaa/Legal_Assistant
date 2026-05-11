@extends('layouts.app')

@section('title', 'Documents')

@section('content')
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Records Vault</span>
            <h1 class="module-title">Documents</h1>
            <p class="module-copy mb-0">Store, review, rename, and retrieve legal files in one document-focused workspace.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-folder-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Document Library</h5>
            <p class="text-muted mb-0">Manage uploaded legal files, rename them when needed, and download records quickly.</p>
        </div>
        <a href="{{ route('documents.create') }}" class="btn btn-primary px-4 py-2">
            <i class="bi bi-upload mr-2"></i>Upload New
        </a>
    </div>

    <div class="glass-card module-table-card">
        @if ($documents->count() > 0)
            <div class="table-responsive">
                <table class="table module-table mb-0">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Category</th>
                            <th>Linked To</th>
                            <th>Type</th>
                            <th>Original Name</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documentTableBody">
                        @foreach ($documents as $document)
                            <tr data-document-row="{{ $document->id }}">
                                <td class="font-weight-bold">{{ $document->name }}</td>
                                <td>{{ $document->category ?? 'Uncategorized' }}</td>
                                <td>
                                    @if (($document->scope ?? 'general') === 'case')
                                        <span class="module-badge module-badge-info">Case</span>
                                        <div class="small text-muted mt-1">{{ $document->case_name ?? 'Unknown Case' }}</div>
                                    @elseif (($document->scope ?? 'general') === 'client')
                                        <span class="module-badge module-badge-muted">Client</span>
                                        <div class="small text-muted mt-1">{{ $document->client_name ?? 'Unknown Client' }}</div>
                                    @else
                                        <span class="small text-muted">General / Personal</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="module-badge {{ ($document->type ?? 'file') === 'link' ? 'module-badge-info' : 'module-badge-muted' }}">
                                        {{ ($document->type ?? 'file') === 'link' ? 'Link' : 'File' }}
                                    </span>
                                </td>
                                <td>{{ $document->original_name }}</td>
                                <td>
                                    @if (($document->type ?? 'file') === 'link')
                                        --
                                    @else
                                        {{ number_format(($document->size ?? 0) / 1024, 2) }} KB
                                    @endif
                                </td>
                                <td>{{ $document->created_at ? $document->created_at->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    <div class="module-actions-inline">
                                        <a href="{{ route('documents.show', $document->id) }}" class="btn btn-sm btn-light" target="{{ ($document->type ?? 'file') === 'link' ? '_blank' : '_self' }}">
                                            {{ ($document->type ?? 'file') === 'link' ? 'Open Link' : 'Download' }}
                                        </a>
                                        <a href="{{ route('documents.edit', $document->id) }}" class="btn btn-sm btn-light">Edit</a>
                                        <form action="{{ route('documents.destroy', $document->id) }}" method="POST" data-async-document-delete="true" data-document-id="{{ $document->id }}" data-confirm="Delete this document?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="module-empty-state">
                <div class="module-empty-icon"><i class="bi bi-folder2-open"></i></div>
                <h5 class="font-weight-bold">No documents yet</h5>
                <p class="text-muted mb-3">Upload your first legal document or save a working link to start building the file library.</p>
                <a href="{{ route('documents.create') }}" class="btn btn-primary px-4">Add Document</a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection

@section('scripts')
<script>
function ensureDocumentEmptyState() {
    const tbody = document.getElementById('documentTableBody');
    if (!tbody || tbody.querySelector('[data-document-row]')) return;

    const wrapper = tbody.closest('.glass-card');
    if (!wrapper) return;

    wrapper.innerHTML = `
        <div class="module-empty-state">
            <div class="module-empty-icon"><i class="bi bi-folder2-open"></i></div>
            <h5 class="font-weight-bold">No documents yet</h5>
            <p class="text-muted mb-3">Upload your first legal document or save a working link to start building the file library.</p>
            <a href="{{ route('documents.create') }}" class="btn btn-primary px-4">Add Document</a>
        </div>
    `;
}

async function handleDocumentDelete(form) {
    const confirmed = confirm(form.dataset.confirm || 'Delete this document?');
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
            throw new Error(payload.message || 'Unable to delete this document.');
        }

        document.querySelector(`[data-document-row="${form.dataset.documentId}"]`)?.remove();
        ensureDocumentEmptyState();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Done',
                text: payload.message || 'Document deleted.',
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
                text: error.message || 'Unable to delete this document right now.',
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

document.querySelectorAll('form[data-async-document-delete]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        handleDocumentDelete(form);
    });
});
</script>
@endsection
