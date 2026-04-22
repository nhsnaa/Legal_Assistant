@extends('layouts.app')

@section('title', 'Documents')

@section('content')
<div class="module-shell">
    @if (session('success'))
        <div class="module-alert module-alert-success">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

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
                            <th>Original Name</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td class="font-weight-bold">{{ $document->name }}</td>
                                <td>{{ $document->original_name }}</td>
                                <td>{{ number_format($document->size / 1024, 2) }} KB</td>
                                <td>{{ $document->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="module-actions-inline">
                                        <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-light">Download</a>
                                        <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-light">Edit</a>
                                        <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Delete this document?')">
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
                <p class="text-muted mb-3">Upload your first legal document to start building the file library.</p>
                <a href="{{ route('documents.create') }}" class="btn btn-primary px-4">Upload Document</a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection
