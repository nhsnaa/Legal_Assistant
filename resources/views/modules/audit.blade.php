@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Security Review</span>
            <h1 class="module-title">Audit Trail</h1>
            <p class="module-copy mb-0">Review workspace activity, record changes, and document access events for accountability.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-shield-check"></i>
        </div>
    </div>

    <div class="audit-summary-grid">
        <div class="glass-card audit-summary-card">
            <span>Total Events</span>
            <strong>{{ $summary['total'] }}</strong>
        </div>
        <div class="glass-card audit-summary-card">
            <span>Document Events</span>
            <strong>{{ $summary['documents'] }}</strong>
        </div>
        <div class="glass-card audit-summary-card">
            <span>Delete Events</span>
            <strong>{{ $summary['deletes'] }}</strong>
        </div>
        <div class="glass-card audit-summary-card">
            <span>Document Accesses</span>
            <strong>{{ $summary['document_accesses'] }}</strong>
        </div>
    </div>

    <div class="glass-card p-4">
        <div class="module-toolbar mb-3 p-0">
            <div>
                <h5 class="font-weight-bold mb-1">Workspace Activity</h5>
                <p class="text-muted mb-0">Latest recorded actions for this workspace. Only the workspace owner can view this page.</p>
            </div>
            <form action="{{ route('audit.index') }}" method="GET" class="audit-filter-form">
                <select name="module" class="form-control module-input">
                    <option value="all" {{ $selectedModule === 'all' ? 'selected' : '' }}>All modules</option>
                    @foreach ($moduleOptions as $moduleOption)
                        <option value="{{ $moduleOption }}" {{ $selectedModule === $moduleOption ? 'selected' : '' }}>{{ ucfirst($moduleOption) }}</option>
                    @endforeach
                </select>
                <select name="action" class="form-control module-input">
                    <option value="all" {{ $selectedAction === 'all' ? 'selected' : '' }}>All actions</option>
                    @foreach ($actionOptions as $actionOption)
                        <option value="{{ $actionOption }}" {{ $selectedAction === $actionOption ? 'selected' : '' }}>{{ ucfirst($actionOption) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary px-4">Filter</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table module-table audit-table mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Actor</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Record</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activityLogs as $log)
                        <tr>
                            <td>
                                <strong>{{ $log->created_at->format('d M Y') }}</strong>
                                <span class="module-document-subtext">{{ $log->created_at->format('h:i A') }}</span>
                            </td>
                            <td>{{ $log->actor_name }}</td>
                            <td><span class="module-badge module-badge-muted">{{ ucfirst($log->module) }}</span></td>
                            <td><span class="module-badge {{ $log->action === 'deleted' ? 'module-badge-danger' : ($log->action === 'downloaded' || $log->action === 'opened' ? 'module-badge-info' : 'module-badge-success') }}">{{ ucfirst($log->action) }}</span></td>
                            <td>
                                <strong>{{ $log->subject }}</strong>
                                <span class="module-document-subtext">{{ $log->label }}{{ $log->type ? ' | ' . ucfirst($log->type) : '' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="module-empty-cell">No audit events found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card p-4">
        <div class="module-toolbar mb-3 p-0">
            <div>
                <h5 class="font-weight-bold mb-1">Document Access Log</h5>
                <p class="text-muted mb-0">Shows successful document opens/downloads with user, time, IP address, and browser details.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table module-table audit-document-table mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Document</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>IP / Browser</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documentAccessLogs as $log)
                        <tr>
                            <td>
                                <strong>{{ $log->created_at->format('d M Y') }}</strong>
                                <span class="module-document-subtext">{{ $log->created_at->format('h:i A') }}</span>
                            </td>
                            <td>
                                <strong>{{ $log->document_name }}</strong>
                                <span class="module-document-subtext">{{ ucfirst($log->document_type) }}{{ $log->storage_disk ? ' | ' . ucfirst($log->storage_disk) : '' }}</span>
                            </td>
                            <td><span class="module-badge module-badge-info">{{ ucfirst($log->action) }}</span></td>
                            <td>{{ $log->actor_name }}</td>
                            <td>
                                <strong>{{ $log->ip_address }}</strong>
                                <span class="module-document-subtext">{{ $log->user_agent }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="module-empty-cell">No document access has been recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection
