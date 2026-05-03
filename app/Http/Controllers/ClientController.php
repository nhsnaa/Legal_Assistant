<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        
        $allClients = $this->workspaceCollection('clients')
            ->map(function ($client, $id) {
                return (object) array_merge($client, ['id' => $id]);
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

        Firebase::database()->getReference('clients')->push($this->workspacePayload($validated));
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

        Firebase::database()->getReference('clients/' . $id)->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function show($id)
    {
        $database = Firebase::database();
        $clientData = $this->findWorkspaceRecord('clients', $id);

        if (!$clientData) {
            abort(404);
        }

        $client = (object) array_merge($clientData, ['id' => $id]);
        $linkedCases = $this->workspaceCollection('cases')
            ->filter(fn ($case) => ($case['client_id'] ?? null) === $id)
            ->map(function ($case, $caseId) {
                return (object) array_merge($case, ['id' => $caseId]);
            })
            ->values();

        return view('modules.client-show', compact('client', 'linkedCases'));
    }

    public function destroy($id)
    {
        if (!$this->findWorkspaceRecord('clients', $id)) {
            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        Firebase::database()->getReference('clients/' . $id)->remove();
        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }
}
