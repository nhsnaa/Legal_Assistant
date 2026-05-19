@extends('layouts.app')

@section('title', 'Upload Documents')

@section('content')
<div class="module-shell">
    <div class="module-toolbar">
        <div>
            <h1 class="module-title mb-2">Add Document</h1>
            <p class="module-copy mb-0">Upload a file or save a Google Drive or external link for faster access to legal materials.</p>
        </div>
        <a href="{{ route('documents.index') }}" class="btn btn-light px-4 py-2">Back to Documents</a>
    </div>

    <div class="glass-card p-4">
        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" id="documentUploadForm">
            @csrf

            <div class="row mb-4 align-items-end">
                <div class="col-lg-6">
                    <label class="font-weight-bold small">Document Category</label>
                    <select name="document_category" class="form-control module-input" required>
                        <option value="Court Filing" {{ old('document_category') === 'Court Filing' ? 'selected' : '' }}>Court Filing</option>
                        <option value="Client Correspondence" {{ old('document_category') === 'Client Correspondence' ? 'selected' : '' }}>Client Correspondence</option>
                        <option value="Evidence" {{ old('document_category') === 'Evidence' ? 'selected' : '' }}>Evidence</option>
                        <option value="Contract" {{ old('document_category') === 'Contract' ? 'selected' : '' }}>Contract</option>
                    </select>
                </div>
                <div class="col-lg-6 mt-3 mt-lg-0">
                    <label class="font-weight-bold small">Document Belongs To</label>
                    <select name="document_scope" id="documentScope" class="form-control module-input" required>
                        <option value="general" {{ old('document_scope', 'general') === 'general' ? 'selected' : '' }}>General / Personal Document</option>
                        <option value="case" {{ old('document_scope') === 'case' ? 'selected' : '' }}>Existing Case</option>
                        <option value="client" {{ old('document_scope') === 'client' ? 'selected' : '' }}>Existing Client</option>
                    </select>
                </div>
            </div>

            <div class="row mb-4 align-items-end">
                <div class="col-lg-6 d-none" id="caseSelectionGroup">
                    <label class="font-weight-bold small">Linked Case</label>
                    <select name="case_id" class="form-control module-input">
                        <option value="">Select case</option>
                        @foreach ($caseOptions as $caseOption)
                            <option value="{{ $caseOption->id }}" {{ old('case_id') === $caseOption->id ? 'selected' : '' }}>{{ $caseOption->case_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-6 d-none" id="clientSelectionGroup">
                    <label class="font-weight-bold small">Linked Client</label>
                    <select name="client_id" class="form-control module-input">
                        <option value="">Select client</option>
                        @foreach ($clientOptions as $clientOption)
                            <option value="{{ $clientOption->id }}" {{ old('client_id') === $clientOption->id ? 'selected' : '' }}>{{ $clientOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <small class="text-muted d-block">When a case or client is selected, the saved document name will automatically become more descriptive.</small>
                </div>
            </div>

            <div class="module-upload-dropzone" id="documentDropzone">
                <div class="module-empty-icon mb-3"><i class="bi bi-cloud-arrow-up"></i></div>
                <h5 class="font-weight-bold">Upload files</h5>
                <p class="text-muted mb-3">Choose files or drag and drop multiple documents here.</p>
                <label class="btn btn-primary px-4 mb-0">
                    Select Files
                    @php
                        $allowedExtensions = collect($documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'])
                            ->map(fn ($type) => '.' . strtolower($type))
                            ->values()
                            ->all();
                    @endphp
                    <input id="documentsInput" name="documents[]" type="file" multiple accept="{{ implode(',', $allowedExtensions) }}" hidden>
                </label>
                <small class="d-block text-muted mt-3">Allowed: {{ implode(', ', $documentRules['allowed_file_types'] ?? ['PDF', 'DOCX']) }} | Max {{ $documentRules['max_upload_size'] ?? 25 }} MB</small>
            </div>

            <div id="documentFileInfo" class="module-file-list-wrap d-none">
                <h6 class="font-weight-bold mb-3">Selected Files</h6>
                <div id="documentFileList" class="module-file-list"></div>
            </div>

            <div class="module-link-divider">
                <span>or save a document link</span>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-3 mb-lg-0">
                    <label class="font-weight-bold small">Link Title</label>
                    <input type="text" name="link_name" class="form-control module-input" value="{{ old('link_name') }}" placeholder="Example: Draft Agreement in Google Drive">
                </div>
                <div class="col-lg-7">
                    <label class="font-weight-bold small">Document URL</label>
                    <input type="url" name="link_url" id="documentLinkUrl" class="form-control module-input" value="{{ old('link_url') }}" placeholder="https://drive.google.com/...">
                    <small class="text-muted d-block mt-2">Useful when the legal team wants to keep editing the original document in Google Drive.</small>
                </div>
            </div>

            <div class="module-alert module-alert-warning mt-3" id="documentLinkWarning">
                <div>
                    <strong>External link security reminder</strong>
                    <p class="mb-0">ILA can record when this link is opened, but Google Drive or the external provider controls who can access the file. Set confidential files to restricted/private before saving the link.</p>
                </div>
            </div>

            <label class="module-confirmation-check mt-3" id="documentLinkPrivacyGroup">
                <input type="checkbox" name="link_privacy_ack" id="documentLinkPrivacyAck" value="1" {{ old('link_privacy_ack') ? 'checked' : '' }}>
                <span>I confirm this external link is restricted/private or approved for workspace access.</span>
            </label>
            @error('link_privacy_ack')
                <small class="text-danger d-block mt-2">{{ $message }}</small>
            @enderror

            <div class="module-modal-actions">
                <a href="{{ route('documents.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4" id="documentSubmitBtn">Save Document Record</button>
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
const uploadInput = document.getElementById('documentsInput');
const dropzone = document.getElementById('documentDropzone');
const fileInfo = document.getElementById('documentFileInfo');
const fileList = document.getElementById('documentFileList');
const documentScope = document.getElementById('documentScope');
const caseSelectionGroup = document.getElementById('caseSelectionGroup');
const clientSelectionGroup = document.getElementById('clientSelectionGroup');
const documentLinkUrl = document.getElementById('documentLinkUrl');
const documentLinkPrivacyAck = document.getElementById('documentLinkPrivacyAck');

function syncDocumentScope() {
    const scope = documentScope ? documentScope.value : 'general';
    caseSelectionGroup?.classList.toggle('d-none', scope !== 'case');
    clientSelectionGroup?.classList.toggle('d-none', scope !== 'client');
}

function syncExternalLinkConfirmation() {
    const hasLink = documentLinkUrl && documentLinkUrl.value.trim() !== '';

    if (documentLinkPrivacyAck) {
        documentLinkPrivacyAck.required = hasLink;
    }
}

function renderSelectedFiles(files) {
    if (!files.length) {
        fileInfo.classList.add('d-none');
        fileList.innerHTML = '';
        return;
    }

    fileInfo.classList.remove('d-none');
    fileList.innerHTML = Array.from(files).map(function(file) {
        return '<div class="module-file-item"><span>' + file.name + '</span><small>' + (file.size / 1024).toFixed(2) + ' KB</small></div>';
    }).join('');
}

uploadInput.addEventListener('change', function () {
    renderSelectedFiles(this.files);
});

['dragenter', 'dragover'].forEach(function (eventName) {
    dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.add('is-dragover');
    });
});

['dragleave', 'drop'].forEach(function (eventName) {
    dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.remove('is-dragover');
    });
});

dropzone.addEventListener('drop', function (event) {
    if (!event.dataTransfer.files.length) {
        return;
    }

    uploadInput.files = event.dataTransfer.files;
    renderSelectedFiles(uploadInput.files);
});

documentScope?.addEventListener('change', syncDocumentScope);
documentLinkUrl?.addEventListener('input', syncExternalLinkConfirmation);
syncDocumentScope();
syncExternalLinkConfirmation();
</script>
@endsection
