<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;

class CaseController extends Controller
{
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

        // Filter logic
        $cases = $allCases->when($filter === 'active', function ($c) {
            return $c->whereIn('status', ['Active', 'Pending']);
        })->when($filter === 'archived', function ($c) {
            return $c->where('status', 'Closed');
        })->reverse();

        $caseCounts = [
            'all' => $allCases->count(),
            'active' => $allCases->whereIn('status', ['Active', 'Pending'])->count(),
            'archived' => $allCases->where('status', 'Closed')->count(),
        ];

        return view('modules.cases', compact('cases', 'clients', 'filter', 'caseCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'case_name' => ['required', 'string', 'max:255'],
            'case_type' => ['required', 'in:Criminal,Divorce,Family,Civil Litigation,Corporate,Employment,Property,Immigration,Other'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Closed,Pending'],
            'priority' => ['required', 'in:High,Medium,Low'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        Firebase::database()->getReference('cases')->push($this->workspacePayload(array_merge($validated, [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ])));
        $this->logActivity('case', 'Case', 'created', $validated['case_name']);

        return redirect()->back()->with('success', 'Case created successfully.');
    }
// app/Http/Controllers/CaseController.php

public function show($id)
{
    $database = Firebase::database();
    $caseData = $this->findWorkspaceRecord('cases', $id);

    if (!$caseData) { abort(404); }

    $case = (object) $caseData;
    $case->id = $id;
    $case->created_at = !empty($caseData['created_at']) ? Carbon::parse($caseData['created_at']) : null;
    $case->updated_at = !empty($caseData['updated_at']) ? Carbon::parse($caseData['updated_at']) : null;
    // Link client info for the "Case Snapshot" sidebar
    $clients = $this->workspaceCollection('clients')->all();
    $case->client = (object) ($clients[$case->client_id] ?? ['name' => 'Unknown', 'email' => '-', 'phone' => '-']);

    $case->timeEntries = $this->workspaceCollection('time_entries')
        ->filter(fn ($entry) => ($entry['case_id'] ?? null) === $id)
        ->values();

    return view('modules.case-show', compact('case'));
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
            'status' => ['required', 'in:Active,Closed,Pending'],
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

    public function destroy($id)
    {
        if ($case = $this->findWorkspaceRecord('cases', $id)) {
            $this->logActivity('case', 'Case', 'deleted', $case['case_name'] ?? 'Untitled Case');
            Firebase::database()->getReference('cases/' . $id)->remove();
            return redirect()->route('cases.index')->with('success', 'Case deleted successfully.');
        }
        return redirect()->route('cases.index')->with('error', 'Case not found.');
    }

    public function toggleArchive($id)
    {
        $case = $this->findWorkspaceRecord('cases', $id);

        if (!$case) {
            return redirect()->route('cases.index')->with('error', 'Case not found.');
        }

        $nextStatus = ($case['status'] ?? 'Active') === 'Closed' ? 'Active' : 'Closed';
        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('cases/' . $id)->update([
            'status' => $nextStatus,
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]);
        $this->logActivity(
            'case',
            'Case',
            $nextStatus === 'Closed' ? 'archived' : 'restored',
            $case['case_name'] ?? 'Untitled Case'
        );

        return redirect()->route('cases.index')->with(
            'success',
            $nextStatus === 'Closed'
                ? 'Case archived successfully.'
                : 'Case restored successfully.'
        );
    }

}
