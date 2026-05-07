@extends('layouts.app')

@section('title', 'Edit Document')

@section('content')
<div class="module-shell">
    @if ($errors->any())
        <div class="module-alert module-alert-danger">
            <span>{{ $errors->first() }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

    <div class="module-toolbar">
        <div>
            <h1 class="module-title mb-2">Edit Document</h1>
            <p class="module-copy mb-0">Update the display name while keeping the original uploaded file intact.</p>
        </div>
        <a href="{{ route('documents.index') }}" class="btn btn-light px-4 py-2">Back to Documents</a>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="glass-card p-4 h-100">
                <form action="{{ route('documents.update', $document->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="font-weight-bold small">Document Name</label>
                        <input type="text" name="name" class="form-control module-input" value="{{ old('name', $document->name) }}" required>
                    </div>

                    <div class="module-document-form-grid mb-3">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Document Category</label>
                            <select name="category" class="form-control module-input" required>
                                <option value="Court Filing" {{ old('category', $document->category ?? '') === 'Court Filing' ? 'selected' : '' }}>Court Filing</option>
                                <option value="Client Correspondence" {{ old('category', $document->category ?? '') === 'Client Correspondence' ? 'selected' : '' }}>Client Correspondence</option>
                                <option value="Evidence" {{ old('category', $document->category ?? '') === 'Evidence' ? 'selected' : '' }}>Evidence</option>
                                <option value="Contract" {{ old('category', $document->category ?? '') === 'Contract' ? 'selected' : '' }}>Contract</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Document Belongs To</label>
                            <select name="document_scope" id="editDocumentScope" class="form-control module-input" required>
                                <option value="general" {{ old('document_scope', $document->scope ?? 'general') === 'general' ? 'selected' : '' }}>General / Personal Document</option>
                                <option value="case" {{ old('document_scope', $document->scope ?? 'general') === 'case' ? 'selected' : '' }}>Existing Case</option>
                                <option value="client" {{ old('document_scope', $document->scope ?? 'general') === 'client' ? 'selected' : '' }}>Existing Client</option>
                            </select>
                        </div>

                        <div class="form-group mb-0 d-none" id="editCaseSelectionGroup">
                            <label class="font-weight-bold small">Linked Case</label>
                            <select name="case_id" class="form-control module-input">
                                <option value="">Select case</option>
                                @foreach ($caseOptions as $caseOption)
                                    <option value="{{ $caseOption->id }}" {{ old('case_id', $document->case_id ?? '') === $caseOption->id ? 'selected' : '' }}>{{ $caseOption->case_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-0 d-none" id="editClientSelectionGroup">
                            <label class="font-weight-bold small">Linked Client</label>
                            <select name="client_id" class="form-control module-input">
                                <option value="">Select client</option>
                                @foreach ($clientOptions as $clientOption)
                                    <option value="{{ $clientOption->id }}" {{ old('client_id', $document->client_id ?? '') === $clientOption->id ? 'selected' : '' }}>{{ $clientOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if (($document->type ?? 'file') === 'link')
                        <div class="form-group">
                            <label class="font-weight-bold small">Document URL</label>
                            <input type="url" name="link_url" class="form-control module-input" value="{{ old('link_url', $document->link_url ?? '') }}" required>
                        </div>
                    @endif

                    <div class="module-modal-actions">
                        <a href="{{ route('documents.index') }}" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="glass-card p-4 h-100">
                <h6 class="font-weight-bold mb-3">Document Details</h6>
                <div class="module-note-list">
                    <div class="module-note-item">
                        <strong>{{ ($document->type ?? 'file') === 'link' ? 'Source Label' : 'Original File Name' }}</strong>
                        <p class="mb-0">{{ $document->original_name }}</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Category</strong>
                        <p class="mb-0">{{ $document->category ?? 'Uncategorized' }}</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Linked To</strong>
                        <p class="mb-0">
                            @if (($document->scope ?? 'general') === 'case')
                                Case: {{ $document->case_name ?? 'Unknown Case' }}
                            @elseif (($document->scope ?? 'general') === 'client')
                                Client: {{ $document->client_name ?? 'Unknown Client' }}
                            @else
                                General / Personal Document
                            @endif
                        </p>
                    </div>
                    <div class="module-note-item">
                        <strong>{{ ($document->type ?? 'file') === 'link' ? 'Document Type' : 'File Size' }}</strong>
                        <p class="mb-0">
                            @if (($document->type ?? 'file') === 'link')
                                External Link
                            @else
                                {{ number_format(($document->size ?? 0) / 1024, 2) }} KB
                            @endif
                        </p>
                    </div>
                    <div class="module-note-item">
                        <strong>{{ ($document->type ?? 'file') === 'link' ? 'Open Link' : 'Download' }}</strong>
                        <p class="mb-0"><a href="{{ route('documents.show', $document->id) }}" target="{{ ($document->type ?? 'file') === 'link' ? '_blank' : '_self' }}">{{ ($document->type ?? 'file') === 'link' ? 'Open current link' : 'Open current file' }}</a></p>
                    </div>
                    <div class="module-note-item">
                        <strong>Created By</strong>
                        <p class="mb-0">{{ $document->created_by_name ?? 'Not recorded' }}</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Created On</strong>
                        <p class="mb-0">{{ $document->created_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Last Updated By</strong>
                        <p class="mb-0">{{ $document->updated_by_name ?? 'Not recorded' }}</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Last Updated</strong>
                        <p class="mb-0">{{ $document->updated_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</p>
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

@section('scripts')
<script>
const editDocumentScope = document.getElementById('editDocumentScope');
const editCaseSelectionGroup = document.getElementById('editCaseSelectionGroup');
const editClientSelectionGroup = document.getElementById('editClientSelectionGroup');

function syncEditDocumentScope() {
    const scope = editDocumentScope ? editDocumentScope.value : 'general';
    editCaseSelectionGroup?.classList.toggle('d-none', scope !== 'case');
    editClientSelectionGroup?.classList.toggle('d-none', scope !== 'client');
}

editDocumentScope?.addEventListener('change', syncEditDocumentScope);
syncEditDocumentScope();
</script>
@endsection
