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
        $client->created_at = !empty($clientData['created_at']) ? Carbon::parse($clientData['created_at']) : null;
        $client->updated_at = !empty($clientData['updated_at']) ? Carbon::parse($clientData['updated_at']) : null;
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

    public function toggleArchive($id)
    {
        $client = $this->findWorkspaceRecord('clients', $id);

        if (!$client) {
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

        return redirect()->route('clients.index')->with(
            'success',
            $nextStatus === 'Inactive'
                ? 'Client archived successfully.'
                : 'Client restored successfully.'
        );
    }
}
