<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $database = Firebase::database();
        $filter = $request->query('filter', 'all');

        $clients = collect($database->getReference('clients')->getValue() ?? [])->map(function ($client, $id) {
            $client['id'] = $id;
            return (object) $client;
        });

        $clientMap = $clients->keyBy('id');

        $allCases = collect($database->getReference('cases')->getValue() ?? [])->map(function ($case, $id) use ($clientMap) {
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
        $database = Firebase::database();
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'case_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Closed,Pending'],
            'priority' => ['required', 'in:High,Medium,Low'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $validated['created_at'] = now()->toIso8601String();
        $database->getReference('cases')->push($validated);

        return redirect()->back()->with('success', 'Case created successfully.');
    }
// app/Http/Controllers/CaseController.php

public function show($id)
{
    $database = Firebase::database();
    $caseData = $database->getReference('cases/' . $id)->getValue();

    if (!$caseData) { abort(404); }

    $case = (object) $caseData;
    $case->id = $id;
    $case->created_at = !empty($caseData['created_at']) ? Carbon::parse($caseData['created_at']) : null;
    // Link client info for the "Case Snapshot" sidebar
    $clients = $database->getReference('clients')->getValue() ?? [];
    $case->client = (object) ($clients[$case->client_id] ?? ['name' => 'Unknown', 'email' => '-', 'phone' => '-']);

    $case->timeEntries = collect($database->getReference('time_entries')->getValue() ?? [])
        ->filter(fn ($entry) => ($entry['case_id'] ?? null) === $id)
        ->values();

    return view('modules.case-show', compact('case'));
}

    public function update(Request $request, $id)
    {
        $database = Firebase::database();
        $caseRef = $database->getReference('cases/' . $id);
        $existing = $caseRef->getValue();

        if (!$existing) {
            return redirect()->route('cases.index')->with('error', 'Case not found.');
        }

        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'case_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Closed,Pending'],
            'priority' => ['required', 'in:High,Medium,Low'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $caseRef->update($validated);

        return redirect()->route('cases.index')->with('success', 'Case updated successfully.');
    }

    public function destroy($id)
    {
        $database = Firebase::database();
        $caseRef = $database->getReference('cases/' . $id);
        if ($caseRef->getValue()) {
            $caseRef->remove();
            return redirect()->route('cases.index')->with('success', 'Case deleted successfully.');
        }
        return redirect()->route('cases.index')->with('error', 'Case not found.');
    }

}
