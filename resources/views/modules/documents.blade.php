@extends('layouts.app')

@section('title', 'Documents')

@section('content')
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Records Vault</span>
            <h1 class="module-title">Documents</h1>
            <p class="module-copy mb-0">Store, review, and retrieve legal files in one workspace.</p>
        </div>
        <div class="module-hero-icon"><i class="bi bi-folder-fill"></i></div>
    </div>

    <div class="module-toolbar">
        <div><h5 class="font-weight-bold">Document Library</h5></div>
        <a href="{{ route('documents.create') }}" class="btn btn-primary px-4 py-2">
            <i class="bi bi-upload mr-2"></i>Upload New
        </a>
    </div>

    <div class="glass-card module-table-card">
        @if ($documents->count() > 0)
            <table class="table module-table">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Uploaded At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $document)
                    <tr>
                        <td>{{ $document->name }}</td>
                        <td>{{ number_format($document->size / 1024, 2) }} KB</td>
                        <td>{{ $document->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-light">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="module-empty-state">
                <i class="bi bi-folder2-open" style="font-size: 3rem;"></i>
                <h5>No documents yet</h5>
                <a href="{{ route('documents.create') }}" class="btn btn-primary mt-3">Upload Now</a>
            </div>
        @endif
    </div>
</div>
@endsection