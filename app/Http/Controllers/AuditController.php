<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        if (session('firebase_user_id') !== $this->currentWorkspaceOwnerId()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Only the workspace owner can view the audit trail.');
        }

        $module = $request->query('module', 'all');
        $action = $request->query('action', 'all');

        $activityLogs = $this->workspaceCollection('activity_logs')
            ->map(function ($item, $id) {
                $timestamp = !empty($item['created_at']) ? Carbon::parse($item['created_at']) : null;

                return (object) [
                    'id' => $id,
                    'module' => $item['module'] ?? 'system',
                    'label' => $item['label'] ?? 'Record',
                    'action' => $item['action'] ?? 'updated',
                    'subject' => $item['subject'] ?? 'Workspace Record',
                    'actor_name' => $item['actor_name'] ?? 'Workspace User',
                    'document_id' => $item['document_id'] ?? null,
                    'type' => $item['type'] ?? null,
                    'created_at' => $timestamp,
                ];
            })
            ->filter(fn ($log) => $log->created_at)
            ->when($module !== 'all', fn ($logs) => $logs->filter(fn ($log) => $log->module === $module))
            ->when($action !== 'all', fn ($logs) => $logs->filter(fn ($log) => $log->action === $action))
            ->sortByDesc(fn ($log) => $log->created_at->timestamp)
            ->values();

        $documentAccessLogs = $this->workspaceCollection('document_access_logs')
            ->map(function ($item, $id) {
                $timestamp = !empty($item['created_at']) ? Carbon::parse($item['created_at']) : null;

                return (object) [
                    'id' => $id,
                    'document_id' => $item['document_id'] ?? null,
                    'document_name' => $item['document_name'] ?? 'Untitled Document',
                    'document_type' => $item['document_type'] ?? 'file',
                    'storage_disk' => $item['storage_disk'] ?? null,
                    'action' => $item['action'] ?? 'opened',
                    'actor_name' => $item['actor_name'] ?? 'Workspace User',
                    'ip_address' => $item['ip_address'] ?? 'Not recorded',
                    'user_agent' => $item['user_agent'] ?? 'Not recorded',
                    'created_at' => $timestamp,
                ];
            })
            ->filter(fn ($log) => $log->created_at)
            ->sortByDesc(fn ($log) => $log->created_at->timestamp)
            ->take(20)
            ->values();

        $summary = [
            'total' => $activityLogs->count(),
            'documents' => $activityLogs->where('module', 'document')->count(),
            'deletes' => $activityLogs->where('action', 'deleted')->count(),
            'document_accesses' => $documentAccessLogs->count(),
        ];

        $moduleOptions = $this->workspaceCollection('activity_logs')
            ->pluck('module')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $actionOptions = $this->workspaceCollection('activity_logs')
            ->pluck('action')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('modules.audit', [
            'activityLogs' => $activityLogs->take(50)->values(),
            'documentAccessLogs' => $documentAccessLogs,
            'summary' => $summary,
            'moduleOptions' => $moduleOptions,
            'actionOptions' => $actionOptions,
            'selectedModule' => $module,
            'selectedAction' => $action,
        ]);
    }
}
