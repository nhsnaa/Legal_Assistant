<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $database = Firebase::database();
        $filter = $request->query('filter', 'all');
        
        $allClients = collect($database->getReference('clients')->getValue() ?? [])
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

        Firebase::database()->getReference('clients')->push($validated);
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

        $clientRef = Firebase::database()->getReference('clients/' . $id);

        if (!$clientRef->getValue()) {
            return redirect()->route('clients.index')->with('error', 'Client not found.');
        }

        $clientRef->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy($id)
    {
        Firebase::database()->getReference('clients/' . $id)->remove();
        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }
}
