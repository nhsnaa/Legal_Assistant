<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $database = Firebase::database();
        $monthInput = $request->query('month');
        $selectedMonth = $monthInput ? Carbon::createFromFormat('Y-m', $monthInput) : now();

        $calendarStart = $selectedMonth->copy()->startOfMonth()->startOfWeek();
        $calendarEnd = $selectedMonth->copy()->endOfMonth()->endOfWeek();

        $hearings = collect($database->getReference('hearings')->getValue() ?? [])
            ->map(function ($hearing, $id) {
                $hearing['id'] = $id;
                $hearing['hearing_time'] = !empty($hearing['hearing_time'])
                    ? Carbon::parse($hearing['hearing_time'])
                    : now();
                return (object) $hearing;
            });

        $timeEntries = collect($database->getReference('time_entries')->getValue() ?? [])
            ->map(function ($entry, $id) {
                $entry['id'] = $id;
                $entry['work_date'] = !empty($entry['work_date'])
                    ? Carbon::parse($entry['work_date'])
                    : now();
                $entry['case'] = (object) ['case_name' => $entry['case_name'] ?? 'General'];
                return (object) $entry;
            })
            ->reverse()
            ->take(8)
            ->values();

        $caseOptions = collect($database->getReference('cases')->getValue() ?? [])
            ->map(function($case, $id) {
                $case['id'] = $id;
                return (object)$case;
            });
                
        $calendarDays = [];
        $cursor = $calendarStart->copy();

        while ($cursor->lte($calendarEnd)) {
            $dateStr = $cursor->format('Y-m-d');
            $calendarDays[] = [
                'date' => $cursor->copy(),
                'isCurrentMonth' => $cursor->month === $selectedMonth->month,
                'events' => $hearings->filter(fn($h) => $h->hearing_time->format('Y-m-d') === $dateStr)->values(),
            ];
            $cursor->addDay();
        }

        return view('modules.schedule', [
            'selectedMonth' => $selectedMonth,
            'previousMonth' => $selectedMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $selectedMonth->copy()->addMonth()->format('Y-m'),
            'hearings' => $hearings,
            'calendarDays' => $calendarDays,
            'upcomingHearings' => $hearings->sortBy(fn ($hearing) => $hearing->hearing_time->timestamp)->take(6)->values(),
            'timeEntries' => $timeEntries,
            'caseOptions' => $caseOptions,
        ]);
    }

    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'case_name' => ['nullable', 'string'],
            'hearing_time' => ['required', 'date'],
            'status' => ['required', 'in:Scheduled,Confirmed,Pending'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['case_name'] = trim((string) ($validated['case_name'] ?? '')) ?: 'General Task';
        $validated['hearing_time'] = Carbon::parse($validated['hearing_time'])->toIso8601String();

        Firebase::database()->getReference('hearings')->push($validated);
        return redirect()->back()->with('success', 'Event scheduled.');
    }

    public function destroyEvent($id)
    {
        Firebase::database()->getReference('hearings/' . $id)->remove();

        return redirect()
            ->route('schedule')
            ->with('success', 'Schedule task deleted successfully.');
    }

    public function updateEvent(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'case_name' => ['nullable', 'string'],
            'hearing_time' => ['required', 'date'],
            'status' => ['required', 'in:Scheduled,Confirmed,Pending'],
            'notes' => ['nullable', 'string'],
        ]);

        $eventRef = Firebase::database()->getReference('hearings/' . $id);

        if (!$eventRef->getValue()) {
            return redirect()->route('schedule')->with('error', 'Schedule task not found.');
        }

        $validated['case_name'] = trim((string) ($validated['case_name'] ?? '')) ?: 'General Task';
        $validated['hearing_time'] = Carbon::parse($validated['hearing_time'])->toIso8601String();
        $eventRef->update($validated);

        return redirect()->route('schedule')->with('success', 'Schedule task updated successfully.');
    }

    public function storeTimeEntry(Request $request)
    {
        $validated = $request->validate([
            'case_id' => ['required', 'string'],
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'activity' => ['nullable', 'string', 'max:255'],
            'billable' => ['nullable'],
            'description' => ['required', 'string'],
        ]);

        $case = Firebase::database()->getReference('cases/' . $validated['case_id'])->getValue() ?? [];

        Firebase::database()->getReference('time_entries')->push([
            'case_id' => $validated['case_id'],
            'case_name' => $case['case_name'] ?? 'General',
            'work_date' => Carbon::parse($validated['work_date'])->toDateString(),
            'hours' => (float) $validated['hours'],
            'activity' => $validated['activity'] ?? 'General Work',
            'billable' => filter_var($validated['billable'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'description' => $validated['description'],
            'created_at' => now()->toIso8601String(),
        ]);

        $returnTarget = $request->input('return_to');

        if ($returnTarget === 'dashboard') {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Time entry saved successfully.');
        }

        return redirect()
            ->route('schedule', ['tab' => 'time'])
            ->with('success', 'Time entry saved successfully.');
    }

    public function destroyTimeEntry($id)
    {
        Firebase::database()->getReference('time_entries/' . $id)->remove();

        return redirect()
            ->route('schedule', ['tab' => 'time'])
            ->with('success', 'Time entry deleted successfully.');
    }

    public function updateTimeEntry(Request $request, $id)
    {
        $validated = $request->validate([
            'case_id' => ['required', 'string'],
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'activity' => ['nullable', 'string', 'max:255'],
            'billable' => ['nullable'],
            'description' => ['required', 'string'],
        ]);

        $entryRef = Firebase::database()->getReference('time_entries/' . $id);

        if (!$entryRef->getValue()) {
            return redirect()->route('schedule', ['tab' => 'time'])->with('error', 'Time entry not found.');
        }

        $case = Firebase::database()->getReference('cases/' . $validated['case_id'])->getValue() ?? [];

        $entryRef->update([
            'case_id' => $validated['case_id'],
            'case_name' => $case['case_name'] ?? 'General',
            'work_date' => Carbon::parse($validated['work_date'])->toDateString(),
            'hours' => (float) $validated['hours'],
            'activity' => $validated['activity'] ?? 'General Work',
            'billable' => filter_var($validated['billable'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'description' => $validated['description'],
        ]);

        return redirect()
            ->route('schedule', ['tab' => 'time'])
            ->with('success', 'Time entry updated successfully.');
    }
}
