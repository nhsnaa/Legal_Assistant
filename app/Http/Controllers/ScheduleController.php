<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Hearing;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $monthInput = $request->query('month');
        $selectedMonth = $monthInput
            ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
            : now()->startOfMonth();

        $calendarStart = $selectedMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $selectedMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $hearings = Hearing::whereBetween('hearing_time', [$calendarStart, $calendarEnd])
            ->orderBy('hearing_time')
            ->get();

        $hearingsByDay = $hearings->groupBy(fn ($hearing) => $hearing->hearing_time->format('Y-m-d'));

        $calendarDays = [];
        $cursor = $calendarStart->copy();

        while ($cursor->lte($calendarEnd)) {
            $dateKey = $cursor->format('Y-m-d');
            $calendarDays[] = [
                'date' => $cursor->copy(),
                'isCurrentMonth' => $cursor->month === $selectedMonth->month,
                'events' => $hearingsByDay->get($dateKey, collect()),
            ];
            $cursor->addDay();
        }

        $upcomingHearings = Hearing::orderBy('hearing_time')->take(6)->get();
        $timeEntries = TimeEntry::with('case')->latest('work_date')->latest()->take(8)->get();
        $caseOptions = CaseModel::orderBy('case_name')->get();

        return view('modules.schedule', [
            'selectedMonth' => $selectedMonth,
            'previousMonth' => $selectedMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $selectedMonth->copy()->addMonth()->format('Y-m'),
            'calendarDays' => $calendarDays,
            'upcomingHearings' => $upcomingHearings,
            'timeEntries' => $timeEntries,
            'caseOptions' => $caseOptions,
        ]);
    }

    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'case_name' => ['required', 'string', 'max:255'],
            'hearing_time' => ['required', 'date'],
            'status' => ['required', 'in:Scheduled,Confirmed,Pending'],
            'notes' => ['nullable', 'string'],
        ]);

        Hearing::create($validated);

        $redirectTo = $request->input('redirect_to');

        return $redirectTo
            ? redirect()->to($redirectTo)->with('success', 'Schedule event added successfully.')
            : redirect()->route('schedule')->with('success', 'Schedule event added successfully.');
    }

    public function storeTimeEntry(Request $request)
    {
        $validated = $request->validate([
            'case_id' => ['required', 'exists:cases,id'],
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'activity' => ['nullable', 'string', 'max:255'],
            'billable' => ['nullable', 'boolean'],
            'description' => ['required', 'string'],
        ]);

        $validated['activity'] = $validated['activity'] ?? 'General Work';
        $validated['billable'] = (bool) ($validated['billable'] ?? true);

        TimeEntry::create($validated);

        return redirect()
            ->route('schedule')
            ->with('success', 'Time entry saved successfully.');
    }

    public function destroyEvent(Hearing $hearing)
    {
        $hearing->delete();

        return redirect()
            ->route('schedule')
            ->with('success', 'Schedule task deleted successfully.');
    }

    public function destroyTimeEntry(TimeEntry $timeEntry)
    {
        $timeEntry->delete();

        return redirect()
            ->route('schedule', ['tab' => 'time'])
            ->with('success', 'Time entry deleted successfully.');
    }
}
