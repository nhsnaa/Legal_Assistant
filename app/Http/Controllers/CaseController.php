<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class CaseController extends Controller
{
    private array $caseStatuses = [
        'Active',
        'Pending',
        'Pending Client',
        'Pending Firm',
        'Pending Third Party',
        'Completed',
        'Cancelled',
        'Closed',
    ];

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $clients = $this->workspaceCollection('clients')->map(function ($client, $id) {
            $client['id'] = $id;
            return (object) $client;
        });

        $clientMap = $clients->keyBy('id');

        $allCases = $this->workspaceCollection('cases')->map(function ($case, $id) use ($clientMap) {
            $case['id'] = $id;
            $case['client'] = $clientMap->get($case['client_id'] ?? null);
            return (object) $case;
        });

        $cases = $allCases->when($filter === 'active', function ($c) {
            return $c->filter(fn ($case) => $this->isActiveCaseStatus($case->status ?? 'Active'));
        })->when($filter === 'archived', function ($c) {
            return $c->filter(fn ($case) => $this->isClosedCaseStatus($case->status ?? 'Active'));
        })->reverse();

        $caseCounts = $this->caseCounts($allCases);
        $caseStatuses = $this->caseStatuses;

        return view('modules.cases', compact('cases', 'clients', 'filter', 'caseCounts', 'caseStatuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'case_name' => ['required', 'string', 'max:255'],
            'case_type' => ['required', 'in:Criminal,Divorce,Family,Civil Litigation,Corporate,Employment,Property,Immigration,Other'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:' . implode(',', $this->caseStatuses)],
            'priority' => ['required', 'in:High,Medium,Low'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        $caseReference = Firebase::database()->getReference('cases')->push($this->workspacePayload(array_merge($validated, [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ])));

        Firebase::database()->getReference('case_updates')->push($this->workspacePayload([
            'case_id' => $caseReference->getKey(),
            'note' => $validated['description'] ?: 'Case opened in the system.',
            'status' => $validated['status'],
            'progress' => $validated['progress'],
            'created_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'system_entry' => true,
        ]));

        $this->logActivity('case', 'Case', 'created', $validated['case_name']);

        return redirect()->back()->with('success', 'Case created successfully.');
    }

    public function show($id)
    {
        $caseData = $this->findWorkspaceRecord('cases', $id);

        if (!$caseData) {
            abort(404);
        }

        $case = (object) $caseData;
        $case->id = $id;
        $case->created_at = !empty($caseData['created_at']) ? Carbon::parse($caseData['created_at']) : null;
        $case->updated_at = !empty($caseData['updated_at']) ? Carbon::parse($caseData['updated_at']) : null;

        $clients = $this->workspaceCollection('clients')->all();
        $case->client = (object) ($clients[$case->client_id] ?? ['name' => 'Unknown', 'email' => '-', 'phone' => '-']);

        $case->timeEntries = $this->workspaceCollection('time_entries')
            ->filter(fn ($entry) => ($entry['case_id'] ?? null) === $id)
            ->values();

        $caseUpdates = $this->workspaceCollection('case_updates')
            ->filter(fn ($update) => ($update['case_id'] ?? null) === $id)
            ->map(function ($update, $updateId) {
                $update['id'] = $updateId;
                $update['created_at'] = !empty($update['created_at']) ? Carbon::parse($update['created_at']) : null;
                return (object) $update;
            })
            ->sortByDesc(fn ($update) => $update->created_at?->timestamp ?? 0)
            ->values();

        $caseStatuses = $this->caseStatuses;
        $progressOptions = range(0, 100, 10);

        return view('modules.case-show', compact('case', 'caseUpdates', 'caseStatuses', 'progressOptions'));
    }

    public function update(Request $request, $id)
    {
        $existing = $this->findWorkspaceRecord('cases', $id);

        if (!$existing) {
            return redirect()->route('cases.index')->with('error', 'Case not found.');
        }

        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'case_name' => ['required', 'string', 'max:255'],
            'case_type' => ['required', 'in:Criminal,Divorce,Family,Civil Litigation,Corporate,Employment,Property,Immigration,Other'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:' . implode(',', $this->caseStatuses)],
            'priority' => ['required', 'in:High,Medium,Low'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('cases/' . $id)->update(array_merge($validated, [
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]));
        $this->logActivity('case', 'Case', 'updated', $validated['case_name']);

        return redirect()->route('cases.index')->with('success', 'Case updated successfully.');
    }

    public function storeUpdate(Request $request, $id)
    {
        $case = $this->findWorkspaceRecord('cases', $id);

        if (!$case) {
            return redirect()->route('cases.index')->with('error', 'Case not found.');
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:' . implode(',', $this->caseStatuses)],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        Firebase::database()->getReference('case_updates')->push($this->workspacePayload(array_merge($validated, [
            'case_id' => $id,
            'created_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
        ])));

        Firebase::database()->getReference('cases/' . $id)->update([
            'status' => $validated['status'],
            'progress' => $validated['progress'],
            'updated_at' => $timestamp,
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]);

        $this->logActivity('case', 'Case', 'added update to', $case['case_name'] ?? 'Untitled Case', [
            'case_id' => $id,
            'status' => $validated['status'],
            'progress' => $validated['progress'],
        ]);

        return redirect()->route('cases.show', $id)->with('success', 'Case update added successfully.');
    }

    public function destroy(Request $request, $id)
    {
        if ($case = $this->findWorkspaceRecord('cases', $id)) {
            $this->logActivity('case', 'Case', 'deleted', $case['case_name'] ?? 'Untitled Case');
            Firebase::database()->getReference('cases/' . $id)->remove();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Case deleted successfully.',
                    'counts' => $this->caseCounts(),
                ]);
            }

            return redirect()->route('cases.index')->with('success', 'Case deleted successfully.');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Case not found.'], 404);
        }

        return redirect()->route('cases.index')->with('error', 'Case not found.');
    }

    public function toggleArchive(Request $request, $id)
    {
        $case = $this->findWorkspaceRecord('cases', $id);

        if (!$case) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Case not found.'], 404);
            }

            return redirect()->route('cases.index')->with('error', 'Case not found.');
        }

        $nextStatus = $this->isClosedCaseStatus($case['status'] ?? 'Active') ? 'Active' : 'Closed';
        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        Firebase::database()->getReference('cases/' . $id)->update([
            'status' => $nextStatus,
            'updated_at' => $timestamp,
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]);

        Firebase::database()->getReference('case_updates')->push($this->workspacePayload([
            'case_id' => $id,
            'note' => $nextStatus === 'Closed' ? 'Case archived.' : 'Case restored to active status.',
            'status' => $nextStatus,
            'progress' => (int) ($case['progress'] ?? 0),
            'created_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'system_entry' => true,
        ]));

        $this->logActivity(
            'case',
            'Case',
            $nextStatus === 'Closed' ? 'archived' : 'restored',
            $case['case_name'] ?? 'Untitled Case'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $nextStatus === 'Closed'
                    ? 'Case archived successfully.'
                    : 'Case restored successfully.',
                'status' => $nextStatus,
                'counts' => $this->caseCounts(),
            ]);
        }

        return redirect()->route('cases.index')->with(
            'success',
            $nextStatus === 'Closed'
                ? 'Case archived successfully.'
                : 'Case restored successfully.'
        );
    }

    private function caseCounts($cases = null): array
    {
        $allCases = $cases ?? $this->workspaceCollection('cases');

        return [
            'all' => $allCases->count(),
            'active' => $allCases->filter(fn ($case) => $this->isActiveCaseStatus(is_array($case) ? ($case['status'] ?? 'Active') : ($case->status ?? 'Active')))->count(),
            'archived' => $allCases->filter(fn ($case) => $this->isClosedCaseStatus(is_array($case) ? ($case['status'] ?? 'Active') : ($case->status ?? 'Active')))->count(),
        ];
    }

    private function isActiveCaseStatus(string $status): bool
    {
        return !$this->isClosedCaseStatus($status);
    }

    private function isClosedCaseStatus(string $status): bool
    {
        return in_array($status, ['Closed', 'Completed', 'Cancelled'], true);
    }
}
