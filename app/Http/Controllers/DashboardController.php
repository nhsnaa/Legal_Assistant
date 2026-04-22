<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Client;
use App\Models\Document;
use App\Models\Hearing;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $hour = now()->hour;

        $greeting = match(true) {
            $hour < 12 => 'Good Morning',
            $hour < 18 => 'Good Afternoon',
            default => 'Good Evening'
        };

        $activeCases = CaseModel::where('status', 'Active')->count();

        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $casesByDay = CaseModel::whereBetween('created_at', [$startDate, $endDate])
            ->get(['created_at'])
            ->groupBy(fn ($case) => $case->created_at->format('Y-m-d'));

        $chartLabels = [];
        $chartData = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $chartLabels[] = $date->format('D');
            $chartData[] = $casesByDay->get($date->format('Y-m-d'), collect())->count();
        }

        $hearings = Hearing::orderBy('hearing_time')
            ->limit(5)
            ->get(['hearing_time', 'case_name', 'status']);

        $recentDocuments = Document::latest()
            ->limit(4)
            ->get(['name', 'original_name', 'mime_type', 'created_at']);

        $recentTimeEntries = TimeEntry::with('case')
            ->latest()
            ->take(8)
            ->get();

        $recentTimeEntryData = $recentTimeEntries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'durationMs' => (int) round(((float) $entry->hours) * 3600000),
                'durationLabel' => number_format((float) $entry->hours, 2) . 'h',
                'activity' => $entry->activity,
                'caseName' => optional($entry->case)->case_name,
                'billable' => $entry->billable,
                'notes' => $entry->description,
                'savedAt' => optional($entry->created_at)->toIso8601String(),
            ];
        })->values()->all();

        $clientsCount = Client::count();
        $documentsCount = Document::count();
        $clients = Client::orderBy('name')->get(['id', 'name']);

        $caseOptions = CaseModel::with('client')
            ->whereNotNull('case_name')
            ->where('case_name', '!=', '')
            ->orderBy('case_name')
            ->limit(50)
            ->get(['id', 'client_id', 'case_name']);

        return view('dashboard', compact(
            'greeting',
            'user',
            'activeCases',
            'chartLabels',
            'chartData',
            'hearings',
            'recentDocuments',
            'recentTimeEntries',
            'recentTimeEntryData',
            'clients',
            'clientsCount',
            'documentsCount',
            'caseOptions'
        ));
    }

    public function storeTimeEntry(Request $request)
    {
        $validated = $request->validate([
            'case_id' => ['required', 'exists:cases,id'],
            'activity' => ['required', 'string', 'max:255'],
            'duration_ms' => ['required', 'integer', 'min:1000'],
            'description' => ['required', 'string'],
            'billable' => ['required', 'boolean'],
            'stopped_at' => ['required', 'date'],
        ]);

        $hours = max(round(($validated['duration_ms'] / 3600000), 2), 0.01);

        $timeEntry = TimeEntry::create([
            'case_id' => $validated['case_id'],
            'work_date' => date('Y-m-d', strtotime($validated['stopped_at'])),
            'hours' => $hours,
            'activity' => $validated['activity'],
            'billable' => (bool) $validated['billable'],
            'description' => $validated['description'],
        ]);

        $timeEntry->load('case');

        return response()->json([
            'message' => 'Time entry saved successfully.',
            'entry' => [
                'id' => $timeEntry->id,
                'activity' => $timeEntry->activity,
                'billable' => $timeEntry->billable,
                'description' => $timeEntry->description,
                'hours' => (float) $timeEntry->hours,
                'durationMs' => (int) $validated['duration_ms'],
                'savedAt' => $timeEntry->created_at?->toIso8601String(),
                'workDate' => $timeEntry->work_date?->toDateString(),
                'caseName' => optional($timeEntry->case)->case_name,
            ],
        ]);
    }
}

