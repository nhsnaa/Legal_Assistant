<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        
        $allClients = $this->workspaceCollection('clients')
            ->map(function ($client, $id) {
                $client['id'] = $id;
                $client['created_at_label'] = !empty($client['created_at'])
                    ? Carbon::parse($client['created_at'])->format('d M Y, h:i A')
                    : null;
                $client['updated_at_label'] = !empty($client['updated_at'])
                    ? Carbon::parse($client['updated_at'])->format('d M Y, h:i A')
                    : null;

                return (object) $client;
            });

        $clients = $allClients->when($filter === 'active', function ($c) {
            return $c->where('status', 'Active');
        })->when($filter === 'archived', function ($c) {
            return $c->where('status', 'Inactive');
        })->reverse();

        $clientCounts = [
            'all' => $allClients->count(),
            'active' => $allClients->where('status', 'Active')->count(),
            'archived' => $allClients->where('status', 'Inactive')->count(),
        ];

        return view('modules.clients', compact('clients', 'filter', 'clientCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        Firebase::database()->getReference('clients')->push($this->workspacePayload(array_merge($validated, [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ])));
        $this->logActivity('client', 'Client', 'created', $validated['name']);

        return redirect()->back()->with('success', 'Client added.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $client = $this->findWorkspaceRecord('clients', $id);

        if (!$client) {
            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('clients/' . $id)->update(array_merge($validated, [
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]));
        $this->logActivity('client', 'Client', 'updated', $validated['name']);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function show($id)
    {
        $clientData = $this->findWorkspaceRecord('clients', $id);

        if (!$clientData) {
            abort(404);
        }

        $client = (object) array_merge($clientData, ['id' => $id]);
        $client->created_at = !empty($clientData['created_at']) ? Carbon::parse($clientData['created_at']) : null;
        $client->updated_at = !empty($clientData['updated_at']) ? Carbon::parse($clientData['updated_at']) : null;

        $linkedCases = $this->workspaceCollection('cases')
            ->filter(fn ($case) => ($case['client_id'] ?? null) === $id)
            ->map(function ($case, $caseId) {
                $case['id'] = $caseId;
                $case['created_at'] = !empty($case['created_at']) ? Carbon::parse($case['created_at']) : null;
                $case['updated_at'] = !empty($case['updated_at']) ? Carbon::parse($case['updated_at']) : null;
                return (object) $case;
            })
            ->values();

        $linkedCaseIds = $linkedCases->pluck('id')->filter()->values();
        $linkedCaseNames = $linkedCases->pluck('case_name')->filter()->values();

        $linkedDocuments = $this->workspaceCollection('documents')
            ->filter(function ($document) use ($id, $linkedCaseIds) {
                $scope = $document['scope'] ?? 'general';

                return (($scope === 'client') && (($document['client_id'] ?? null) === $id))
                    || (($scope === 'case') && $linkedCaseIds->contains($document['case_id'] ?? null));
            })
            ->map(function ($document, $documentId) {
                $document['id'] = $documentId;
                $document['created_at'] = !empty($document['created_at']) ? Carbon::parse($document['created_at']) : null;
                $document['updated_at'] = !empty($document['updated_at']) ? Carbon::parse($document['updated_at']) : null;
                return (object) $document;
            })
            ->sortByDesc(fn ($document) => $document->updated_at?->timestamp ?? $document->created_at?->timestamp ?? 0)
            ->values();

        $linkedHearings = $this->workspaceCollection('hearings')
            ->filter(fn ($hearing) => $linkedCaseNames->contains($hearing['case_name'] ?? null))
            ->map(function ($hearing, $hearingId) {
                $hearing['id'] = $hearingId;
                $hearing['hearing_time'] = !empty($hearing['hearing_time'])
                    ? Carbon::parse($hearing['hearing_time'])
                    : now();
                $status = $hearing['status'] ?? 'Scheduled';
                $isCompleted = $status === 'Completed';
                $isOverdue = !$isCompleted && $hearing['hearing_time']->isPast();
                $isDueSoon = !$isCompleted && !$isOverdue && $hearing['hearing_time']->between(now(), now()->copy()->addDay());
                $hearing['display_status'] = $isCompleted ? 'Completed' : ($isOverdue ? 'Overdue' : $status);
                $hearing['status_badge_class'] = $isCompleted
                    ? 'module-badge-success'
                    : ($isOverdue
                        ? 'module-badge-danger'
                        : ($isDueSoon
                            ? 'module-badge-warning'
                            : ($status === 'Confirmed' ? 'module-badge-success' : ($status === 'Pending' ? 'module-badge-warning' : 'module-badge-info'))));
                return (object) $hearing;
            })
            ->sortBy(fn ($hearing) => $hearing->hearing_time->timestamp)
            ->values();

        $linkedTimeEntries = $this->workspaceCollection('time_entries')
            ->filter(fn ($entry) => $linkedCaseIds->contains($entry['case_id'] ?? null))
            ->map(function ($entry, $entryId) {
                $entry['id'] = $entryId;
                $entry['work_date'] = !empty($entry['work_date']) ? Carbon::parse($entry['work_date']) : now();
                $entry['created_at'] = !empty($entry['created_at']) ? Carbon::parse($entry['created_at']) : null;
                $entry['updated_at'] = !empty($entry['updated_at']) ? Carbon::parse($entry['updated_at']) : null;
                $durationMs = $this->durationMillisecondsFromEntry($entry);
                $entry['duration_label'] = $this->formatDurationLabel($durationMs);
                return (object) $entry;
            })
            ->sortByDesc(fn ($entry) => $entry->work_date->timestamp)
            ->values();

        $overviewStats = [
            'cases' => $linkedCases->count(),
            'documents' => $linkedDocuments->count(),
            'upcoming_tasks' => $linkedHearings->filter(fn ($hearing) => $hearing->hearing_time->gte(now()))->count(),
            'time_entries' => $linkedTimeEntries->count(),
        ];

        return view('modules.client-show', compact(
            'client',
            'linkedCases',
            'linkedDocuments',
            'linkedHearings',
            'linkedTimeEntries',
            'overviewStats'
        ));
    }

    public function destroy(Request $request, $id)
    {
        $clientData = $this->findWorkspaceRecord('clients', $id);

        if (!$clientData) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Client not found.'], 404);
            }

            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        $this->logActivity('client', 'Client', 'deleted', $clientData['name'] ?? 'Untitled Client');
        Firebase::database()->getReference('clients/' . $id)->remove();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Client deleted.',
                'counts' => $this->clientCounts(),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }

    public function toggleArchive(Request $request, $id)
    {
        $client = $this->findWorkspaceRecord('clients', $id);

        if (!$client) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Client not found.'], 404);
            }

            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        $nextStatus = ($client['status'] ?? 'Active') === 'Inactive' ? 'Active' : 'Inactive';
        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('clients/' . $id)->update([
            'status' => $nextStatus,
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]);
        $this->logActivity(
            'client',
            'Client',
            $nextStatus === 'Inactive' ? 'archived' : 'restored',
            $client['name'] ?? 'Untitled Client'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $nextStatus === 'Inactive'
                    ? 'Client archived successfully.'
                    : 'Client restored successfully.',
                'status' => $nextStatus,
                'counts' => $this->clientCounts(),
            ]);
        }

        return redirect()->route('clients.index')->with(
            'success',
            $nextStatus === 'Inactive'
                ? 'Client archived successfully.'
                : 'Client restored successfully.'
        );
    }

    private function clientCounts(): array
    {
        $allClients = $this->workspaceCollection('clients');

        return [
            'all' => $allClients->count(),
            'active' => $allClients->where('status', 'Active')->count(),
            'archived' => $allClients->where('status', 'Inactive')->count(),
        ];
    }
}
