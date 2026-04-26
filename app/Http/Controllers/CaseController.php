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
    
    // Fetch specific case from Firebase
    $caseData = $database->getReference('cases/' . $id)->getValue();
    
    if (!$caseData) {
        abort(404);
    }

    // Wrap in a collection or object to mimic CaseModel behavior for the view
    $case = (object) $caseData;
    $case->id = $id;

    // Fetch linked client
    if (isset($case->client_id)) {
        $clientData = $database->getReference('clients/' . $case->client_id)->getValue();
        $case->client = (object) $clientData;
    }

    return view('modules.case-show', compact('case'));
    }
    public function destroy($id)
    {
        Firebase::database()->getReference('cases/' . $id)->remove();
        return redirect()->route('cases.index')->with('success', 'Case deleted.');
    }
}