<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Client;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $casesQuery = CaseModel::with('client')->latest();

        if ($filter === 'active') {
            $casesQuery->whereIn('status', ['Active', 'Pending']);
        } elseif ($filter === 'archived') {
            $casesQuery->where('status', 'Closed');
        }

        $cases = $casesQuery->get();
        $clients = Client::orderBy('name')->get();
        $caseCounts = [
            'all' => CaseModel::count(),
            'active' => CaseModel::whereIn('status', ['Active', 'Pending'])->count(),
            'archived' => CaseModel::where('status', 'Closed')->count(),
        ];

        return view('modules.cases', compact('cases', 'clients', 'filter', 'caseCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'case_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Closed,Pending'],
            'priority' => ['required', 'in:High,Medium,Low'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        CaseModel::create($validated);

        $redirectTo = $request->input('redirect_to');

        return $redirectTo
            ? redirect()->to($redirectTo)->with('success', 'Case created successfully.')
            : redirect()->route('cases.index')->with('success', 'Case created successfully.');
    }

    public function show(CaseModel $case)
    {
        $case->load(['client', 'timeEntries']);

        return view('modules.case-show', compact('case'));
    }

    public function destroy(CaseModel $case)
    {
        $case->delete();

        return redirect()
            ->route('cases.index')
            ->with('success', 'Case deleted successfully.');
    }
}
