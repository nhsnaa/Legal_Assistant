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
                <form action="{{ route('documents.update', $document) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="font-weight-bold small">Document Name</label>
                        <input type="text" name="name" class="form-control module-input" value="{{ old('name', $document->name) }}" required>
                    </div>

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
                        <strong>Original File Name</strong>
                        <p class="mb-0">{{ $document->original_name }}</p>
                    </div>
                    <div class="module-note-item">
                        <strong>File Size</strong>
                        <p class="mb-0">{{ number_format($document->size / 1024, 2) }} KB</p>
                    </div>
                    <div class="module-note-item">
                        <strong>Download</strong>
                        <p class="mb-0"><a href="{{ route('documents.show', $document) }}">Open current file</a></p>
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
