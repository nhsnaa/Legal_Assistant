<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $clientsQuery = Client::latest();

        if ($filter === 'active') {
            $clientsQuery->where('status', 'Active');
        } elseif ($filter === 'archived') {
            $clientsQuery->where('status', 'Inactive');
        }

        $clients = $clientsQuery->get();
        $clientCounts = [
            'all' => Client::count(),
            'active' => Client::where('status', 'Active')->count(),
            'archived' => Client::where('status', 'Inactive')->count(),
        ];

        return view('modules.clients', compact('clients', 'filter', 'clientCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        Client::create($validated);

        $redirectTo = $request->input('redirect_to');

        return $redirectTo
            ? redirect()->to($redirectTo)->with('success', 'Client added successfully.')
            : redirect()->route('clients.index')->with('success', 'Client added successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
