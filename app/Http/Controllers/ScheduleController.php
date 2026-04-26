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

        // Fetch everything and filter locally for the calendar
        $hearings = collect($database->getReference('hearings')->getValue() ?? []);
        $timeEntries = collect($database->getReference('time_entries')->getValue() ?? [])->reverse()->take(8);
        $caseOptions = collect($database->getReference('cases')->getValue() ?? []);

        $calendarDays = [];
        $cursor = $calendarStart->copy();

        while ($cursor->lte($calendarEnd)) {
            $dateStr = $cursor->format('Y-m-d');
            $calendarDays[] = [
                'date' => $cursor->copy(),
                'isCurrentMonth' => $cursor->month === $selectedMonth->month,
                'events' => $hearings->filter(fn($h) => str_starts_with($h['hearing_time'], $dateStr))
            ];
            $cursor->addDay();
        }

        return view('modules.schedule', [
            'selectedMonth' => $selectedMonth,
            'calendarDays' => $calendarDays,
            'upcomingHearings' => $hearings->sortBy('hearing_time')->take(6),
            'timeEntries' => $timeEntries,
            'caseOptions' => $caseOptions,
        ]);
    }

    public function storeEvent(Request $request)
    {
        Firebase::database()->getReference('hearings')->push($request->all());
        return redirect()->back()->with('success', 'Event scheduled.');
    }
}