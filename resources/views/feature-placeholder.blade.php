@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="glass-card p-5">
    <div class="d-flex align-items-center mb-3">
        <div class="icon-circle bg-info text-white mr-3">
            <i class="{{ $icon }}"></i>
        </div>
        <div>
            <h2 class="h4 font-weight-bold mb-1">{{ $title }}</h2>
            <p class="text-muted mb-0">{{ $description }}</p>
        </div>
    </div>

    <div class="mt-4">
        <p class="mb-4">This page is linked in the navigation and now loads safely, but the full feature screen has not been built yet.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection
