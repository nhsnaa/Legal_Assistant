@extends('layouts.app')

@section('title', 'Upload Documents')

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
            <h1 class="module-title mb-2">Add Document</h1>
            <p class="module-copy mb-0">Upload a file or save a Google Drive or external link for faster access to legal materials.</p>
        </div>
        <a href="{{ route('documents.index') }}" class="btn btn-light px-4 py-2">Back to Documents</a>
    </div>

    <div class="glass-card p-4">
        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" id="documentUploadForm">
            @csrf

            <div class="row mb-4">
                <div class="col-lg-6">
                    <label class="font-weight-bold small">Document Category</label>
                    <select name="document_category" class="form-control module-input" required>
                        <option value="Court Filing" {{ old('document_category') === 'Court Filing' ? 'selected' : '' }}>Court Filing</option>
                        <option value="Client Correspondence" {{ old('document_category') === 'Client Correspondence' ? 'selected' : '' }}>Client Correspondence</option>
                        <option value="Evidence" {{ old('document_category') === 'Evidence' ? 'selected' : '' }}>Evidence</option>
                        <option value="Contract" {{ old('document_category') === 'Contract' ? 'selected' : '' }}>Contract</option>
                    </select>
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
                    <input type="url" name="link_url" class="form-control module-input" value="{{ old('link_url') }}" placeholder="https://drive.google.com/...">
                    <small class="text-muted d-block mt-2">Useful when the legal team wants to keep editing the original document in Google Drive.</small>
                </div>
            </div>

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
</script>
@endsection
