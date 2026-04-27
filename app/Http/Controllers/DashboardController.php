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

        // Fetch the dashboard data in a single Firebase read to reduce page-load latency.
        $rootData = $database->getReference()->getValue() ?? [];

        // 1. Fetch Cases & Count Active
        $casesRef = $rootData['cases'] ?? [];
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

        // 3. Fetch Hearings (Limit 5) without requiring a Firebase index
        $hearingsRef = $rootData['hearings'] ?? [];
        $hearings = collect($hearingsRef)
            ->sortBy('hearing_time')
            ->take(5);

        // 4. Fetch Recent Documents (Limit 4) without Firebase query requirements
        $docsRef = $rootData['documents'] ?? [];
        $recentDocuments = collect($docsRef)->reverse()->take(4);
        $documentsCount = count($docsRef);

        // 5. Recent Time Entries
        $timeRef = $rootData['time_entries'] ?? [];
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
        })->reverse()->take(8)->values()->all();

        // 6. Clients & Case Options
        $clientsRef = $rootData['clients'] ?? [];
        $clientsCount = count($clientsRef);
        $clients = collect($clientsRef)->map(fn($c, $id) => (object)['id' => $id, 'name' => $c['name']]);

        $caseOptions = collect($casesRef)->map(fn($c, $id) => (object)[
            'id' => $id, 
            'case_name' => $c['case_name'],
            'client_id' => $c['client_id'] ?? null
        ]);

        $upcomingHearings = collect($hearingsRef)
            ->filter(fn ($hearing) => !empty($hearing['hearing_time']))
            ->sortBy('hearing_time')
            ->take(3)
            ->values();

        $billableEntriesCount = collect($timeRef)
            ->filter(fn ($entry) => !empty($entry['billable']))
            ->count();

        $aiSuggestions = [];

        if ($upcomingHearings->isNotEmpty()) {
            $nextHearing = $upcomingHearings->first();
            $hearingTime = Carbon::parse($nextHearing['hearing_time']);
            $aiSuggestions[] = [
                'title' => 'Prepare the next hearing first',
                'timing' => $hearingTime->isToday() ? 'Priority for today' : 'Upcoming priority',
                'detail' => 'Focus on "' . ($nextHearing['title'] ?? $nextHearing['case_name'] ?? 'Untitled Case') . '" before ' . $hearingTime->format('d M, g:i A') . '.',
            ];
        }

        if ($activeCases > 0) {
            $aiSuggestions[] = [
                'title' => 'Review active matters in focused blocks',
                'timing' => 'Midday workflow',
                'detail' => 'You currently have ' . $activeCases . ' active case' . ($activeCases === 1 ? '' : 's') . '. Group follow-up, research, and drafting into one focused session.',
            ];
        }

        if ($documentsCount > 0) {
            $aiSuggestions[] = [
                'title' => 'Set aside time for document handling',
                'timing' => 'Admin window',
                'detail' => 'With ' . $documentsCount . ' stored document' . ($documentsCount === 1 ? '' : 's') . ', reserve a short review window for uploads, checking, or filing.',
            ];
        }

        if ($billableEntriesCount > 0) {
            $aiSuggestions[] = [
                'title' => 'Close the day by confirming billable logs',
                'timing' => 'End-of-day check',
                'detail' => 'You already have ' . $billableEntriesCount . ' billable time entr' . ($billableEntriesCount === 1 ? 'y' : 'ies') . '. Use the last slot of the day to verify time records are complete.',
            ];
        }

        $aiSuggestions = collect($aiSuggestions)->take(3)->values();

        return view('dashboard', compact(
            'greeting', 'activeCases', 'chartLabels', 'chartData', 
            'hearings', 'recentDocuments', 'recentTimeEntryData', 
            'clients', 'clientsCount', 'documentsCount', 'caseOptions', 'aiSuggestions'
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
