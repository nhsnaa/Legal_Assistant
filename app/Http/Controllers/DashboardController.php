<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $database = Firebase::database();
        $hour = now()->hour;

        $greeting = match(true) {
            $hour < 12 => 'Good Morning',
            $hour < 18 => 'Good Afternoon',
            default => 'Good Evening'
        };

        // 1. Fetch Cases & Count Active
        $casesRef = $database->getReference('cases')->getValue() ?? [];
        $activeCases = count(array_filter($casesRef, fn($c) => ($c['status'] ?? '') === 'Active'));

        // 2. Chart Logic (Last 7 Days)
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        $chartLabels = [];
        $chartData = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dayKey = $date->format('Y-m-d');
            $chartLabels[] = $date->format('D');
            
            // Count cases created on this day in Firebase
            $count = count(array_filter($casesRef, function($case) use ($dayKey) {
                return isset($case['created_at']) && str_starts_with($case['created_at'], $dayKey);
            }));
            $chartData[] = $count;
        }

        // 3. Fetch Hearings (Limit 5)
        $hearingsRef = $database->getReference('hearings')->orderByChild('hearing_time')->limitToFirst(5)->getValue() ?? [];
        $hearings = collect($hearingsRef);

        // 4. Fetch Recent Documents (Limit 4)
        $docsRef = $database->getReference('documents')->limitToLast(4)->getValue() ?? [];
        $recentDocuments = collect($docsRef)->reverse();
        $documentsCount = count($database->getReference('documents')->getValue() ?? []);

        // 5. Recent Time Entries
        $timeRef = $database->getReference('time_entries')->limitToLast(8)->getValue() ?? [];
        $recentTimeEntryData = collect($timeRef)->map(function ($entry, $key) {
            return [
                'id' => $key,
                'durationMs' => (int) round(((float) ($entry['hours'] ?? 0)) * 3600000),
                'durationLabel' => number_format((float) ($entry['hours'] ?? 0), 2) . 'h',
                'activity' => $entry['activity'] ?? 'Unknown',
                'caseName' => $entry['case_name'] ?? 'No case',
                'billable' => $entry['billable'] ?? false,
                'notes' => $entry['description'] ?? '',
                'savedAt' => $entry['created_at'] ?? now()->toIso8601String(),
            ];
        })->reverse()->values()->all();

        // 6. Clients & Case Options
        $clientsRef = $database->getReference('clients')->getValue() ?? [];
        $clientsCount = count($clientsRef);
        $clients = collect($clientsRef)->map(fn($c, $id) => (object)['id' => $id, 'name' => $c['name']]);

        $caseOptions = collect($casesRef)->map(fn($c, $id) => (object)[
            'id' => $id, 
            'case_name' => $c['case_name'],
            'client_id' => $c['client_id'] ?? null
        ]);

        return view('dashboard', compact(
            'greeting', 'activeCases', 'chartLabels', 'chartData', 
            'hearings', 'recentDocuments', 'recentTimeEntryData', 
            'clients', 'clientsCount', 'documentsCount', 'caseOptions'
        ));
    }

    public function storeTimeEntry(Request $request)
    {
        $database = Firebase::database();
        
        $validated = $request->validate([
            'case_id' => ['required', 'string'],
            'activity' => ['required', 'string', 'max:255'],
            'duration_ms' => ['required', 'integer', 'min:1000'],
            'description' => ['required', 'string'],
            'billable' => ['required', 'boolean'],
            'stopped_at' => ['required', 'date'],
        ]);

        $hours = max(round(($validated['duration_ms'] / 3600000), 2), 0.01);
        
        // Get Case Name for display
        $case = $database->getReference('cases/' . $validated['case_id'])->getValue();

        $newData = [
            'case_id' => $validated['case_id'],
            'case_name' => $case['case_name'] ?? 'General',
            'work_date' => date('Y-m-d', strtotime($validated['stopped_at'])),
            'hours' => $hours,
            'activity' => $validated['activity'],
            'billable' => (bool) $validated['billable'],
            'description' => $validated['description'],
            'created_at' => now()->toIso8601String(),
        ];

        $newPostKey = $database->getReference('time_entries')->push($newData)->getKey();

        return response()->json([
            'message' => 'Time entry saved to Firebase.',
            'entry' => array_merge($newData, ['id' => $newPostKey, 'durationMs' => $validated['duration_ms'], 'savedAt' => $newData['created_at']])
        ]);
    }
}