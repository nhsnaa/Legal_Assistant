<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $database = Firebase::database();
        $filter = $request->query('filter', 'all');

        $allCases = collect($database->getReference('cases')->getValue() ?? []);
        $clients = collect($database->getReference('clients')->getValue() ?? []);

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
    // Link client info for the "Case Snapshot" sidebar
    $clients = $database->getReference('clients')->getValue() ?? [];
    $case->client = (object) ($clients[$case->client_id] ?? ['name' => 'Unknown', 'email' => '-', 'phone' => '-']);
    
    // Setup an empty collection for time entries to prevent errors in the view
    $case->timeEntries = collect([]); 

    return view('modules.case-show', compact('case'));
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