<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    private function buildScheduleConflictData(Request $request, array $payload, $conflicts, ?string $eventId = null): array
    {
        return [
            'context' => $request->input('conflict_context', 'schedule_create'),
            'event_id' => $eventId,
            'payload' => $payload,
            'message' => 'Possible schedule conflict detected. There is already another task scheduled around this time.',
            'conflicts' => collect($conflicts)->map(function ($conflict) {
                return [
                    'title' => $conflict['title'] ?? 'Untitled Task',
                    'case_name' => $conflict['case_name'] ?? 'General Task',
                    'status' => $conflict['status'] ?? 'Scheduled',
                    'time' => !empty($conflict['hearing_time'])
                        ? $conflict['hearing_time']->format('d M Y, g:i A')
                        : 'Unknown time',
                ];
            })->values()->all(),
        ];
    }

    public function index(Request $request)
    {
        $monthInput = $request->query('month');
        $selectedMonth = $monthInput ? Carbon::createFromFormat('Y-m', $monthInput) : now();

        $calendarStart = $selectedMonth->copy()->startOfMonth()->startOfWeek();
        $calendarEnd = $selectedMonth->copy()->endOfMonth()->endOfWeek();

        $hearings = $this->workspaceCollection('hearings')
            ->map(function ($hearing, $id) {
                $hearing['id'] = $id;
                $hearing['hearing_time'] = !empty($hearing['hearing_time'])
                    ? Carbon::parse($hearing['hearing_time'])
                    : now();
                $hearing['created_at'] = !empty($hearing['created_at'])
                    ? Carbon::parse($hearing['created_at'])
                    : null;
                $hearing['updated_at'] = !empty($hearing['updated_at'])
                    ? Carbon::parse($hearing['updated_at'])
                    : null;
                $status = $hearing['status'] ?? 'Scheduled';
                $isCompleted = $status === 'Completed';
                $isOverdue = !$isCompleted && $hearing['hearing_time']->isPast();
                $isDueSoon = !$isCompleted && !$isOverdue && $hearing['hearing_time']->between(now(), now()->copy()->addDay());
                $hearing['display_status'] = $isCompleted ? 'Completed' : ($isOverdue ? 'Overdue' : $status);
                $hearing['state_class'] = $isCompleted ? 'is-completed' : ($isOverdue ? 'is-overdue' : ($isDueSoon ? 'is-due-soon' : 'is-scheduled'));
                $hearing['status_badge_class'] = $isCompleted
                    ? 'module-badge-success'
                    : ($isOverdue
                        ? 'module-badge-danger'
                        : ($isDueSoon
                            ? 'module-badge-warning'
                            : ($status === 'Confirmed' ? 'module-badge-success' : ($status === 'Pending' ? 'module-badge-warning' : 'module-badge-info'))));
                return (object) $hearing;
            });

        $timeEntries = $this->workspaceCollection('time_entries')
            ->map(function ($entry, $id) {
                $durationMs = $this->durationMillisecondsFromEntry($entry);
                $entry['id'] = $id;
                $entry['work_date'] = !empty($entry['work_date'])
                    ? Carbon::parse($entry['work_date'])
                    : now();
                $entry['created_at'] = !empty($entry['created_at'])
                    ? Carbon::parse($entry['created_at'])
                    : null;
                $entry['updated_at'] = !empty($entry['updated_at'])
                    ? Carbon::parse($entry['updated_at'])
                    : null;
                $entry['case'] = (object) ['case_name' => $entry['case_name'] ?? 'General'];
                $entry['duration_ms'] = $durationMs;
                $entry['duration_label'] = $this->formatDurationLabel($durationMs);
                return (object) $entry;
            })
            ->reverse()
            ->take(8)
            ->values();

        $caseOptions = $this->workspaceCollection('cases')
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

        $upcomingHearings = $hearings
            ->filter(fn ($hearing) => ($hearing->status ?? 'Scheduled') !== 'Completed')
            ->filter(fn ($hearing) => $hearing->hearing_time->gte(now()->copy()->startOfDay()))
            ->sortBy(fn ($hearing) => $hearing->hearing_time->timestamp)
            ->take(6)
            ->values();

        return view('modules.schedule', [
            'selectedMonth' => $selectedMonth,
            'previousMonth' => $selectedMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $selectedMonth->copy()->addMonth()->format('Y-m'),
            'hearings' => $hearings,
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
            'case_name' => ['nullable', 'string'],
            'hearing_time' => ['required', 'date'],
            'status' => ['required', 'in:Scheduled,Confirmed,Pending,Completed'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['case_name'] = trim((string) ($validated['case_name'] ?? '')) ?: 'General Task';
        $targetTime = Carbon::parse($validated['hearing_time']);
        $conflicts = $this->findScheduleConflicts($targetTime);

        if ($conflicts->isNotEmpty() && !$request->boolean('conflict_override')) {
            return redirect()
                ->back()
                ->withInput()
                ->with('schedule_conflict', $this->buildScheduleConflictData(
                    $request,
                    [
                        'title' => $validated['title'],
                        'case_name' => $validated['case_name'],
                        'hearing_time' => $targetTime->format('Y-m-d\TH:i'),
                        'status' => $validated['status'],
                        'notes' => $validated['notes'] ?? '',
                    ],
                    $conflicts
                ));
        }

        $validated['hearing_time'] = $targetTime->toIso8601String();
        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        Firebase::database()->getReference('hearings')->push($this->workspacePayload(array_merge($validated, [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ])));
        $this->logActivity('schedule', 'Task', 'created', $validated['title']);
        return redirect()->back()->with('success', 'Event scheduled.');
    }

    public function destroyEvent($id)
    {
        $hearing = $this->findWorkspaceRecord('hearings', $id);

        if (!$hearing) {
            return redirect()->route('schedule')->with('error', 'Schedule task not found.');
        }

        $this->logActivity('schedule', 'Task', 'deleted', $hearing['title'] ?? ($hearing['case_name'] ?? 'Untitled Task'));
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
            'status' => ['required', 'in:Scheduled,Confirmed,Pending,Completed'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!$this->findWorkspaceRecord('hearings', $id)) {
            return redirect()->route('schedule')->with('error', 'Schedule task not found.');
        }

        $validated['case_name'] = trim((string) ($validated['case_name'] ?? '')) ?: 'General Task';
        $targetTime = Carbon::parse($validated['hearing_time']);
        $conflicts = $this->findScheduleConflicts($targetTime, $id);

        if ($conflicts->isNotEmpty() && !$request->boolean('conflict_override')) {
            return redirect()
                ->back()
                ->with('schedule_conflict', $this->buildScheduleConflictData(
                    $request,
                    [
                        'title' => $validated['title'],
                        'case_name' => $validated['case_name'],
                        'hearing_time' => $targetTime->format('Y-m-d\TH:i'),
                        'status' => $validated['status'],
                        'notes' => $validated['notes'] ?? '',
                    ],
                    $conflicts,
                    $id
                ));
        }

        $validated['hearing_time'] = $targetTime->toIso8601String();
        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('hearings/' . $id)->update(array_merge($validated, [
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]));
        $this->logActivity('schedule', 'Task', 'updated', $validated['title']);

        return redirect()->route('schedule')->with('success', 'Schedule task updated successfully.');
    }

    public function completeEvent($id)
    {
        $event = $this->findWorkspaceRecord('hearings', $id);

        if (!$event) {
            return redirect()->route('schedule')->with('error', 'Schedule task not found.');
        }

        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('hearings/' . $id)->update([
            'status' => 'Completed',
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]);

        $this->logActivity('schedule', 'Task', 'completed', $event['title'] ?? ($event['case_name'] ?? 'Untitled Task'));

        return redirect()->route('schedule')->with('success', 'Schedule task marked as completed.');
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

        $case = $this->findWorkspaceRecord('cases', $validated['case_id']) ?? [];
        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        Firebase::database()->getReference('time_entries')->push($this->workspacePayload([
            'case_id' => $validated['case_id'],
            'case_name' => $case['case_name'] ?? 'General',
            'work_date' => Carbon::parse($validated['work_date'])->toDateString(),
            'hours' => (float) $validated['hours'],
            'duration_ms' => (int) round(((float) $validated['hours']) * 3600000),
            'activity' => $validated['activity'] ?? 'General Work',
            'billable' => filter_var($validated['billable'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'description' => $validated['description'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]));
        $this->logActivity(
            'time',
            'Time Entry',
            'created',
            ($validated['activity'] ?? 'General Work') . ' for ' . ($case['case_name'] ?? 'General')
        );

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
        $entry = $this->findWorkspaceRecord('time_entries', $id);

        if (!$entry) {
            return redirect()->route('schedule', ['tab' => 'time'])->with('error', 'Time entry not found.');
        }

        $this->logActivity(
            'time',
            'Time Entry',
            'deleted',
            ($entry['activity'] ?? 'General Work') . ' for ' . ($entry['case_name'] ?? 'General')
        );
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

        if (!$this->findWorkspaceRecord('time_entries', $id)) {
            return redirect()->route('schedule', ['tab' => 'time'])->with('error', 'Time entry not found.');
        }

        $case = $this->findWorkspaceRecord('cases', $validated['case_id']) ?? [];
        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('time_entries/' . $id)->update([
            'case_id' => $validated['case_id'],
            'case_name' => $case['case_name'] ?? 'General',
            'work_date' => Carbon::parse($validated['work_date'])->toDateString(),
            'hours' => (float) $validated['hours'],
            'duration_ms' => (int) round(((float) $validated['hours']) * 3600000),
            'activity' => $validated['activity'] ?? 'General Work',
            'billable' => filter_var($validated['billable'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'description' => $validated['description'],
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]);
        $this->logActivity(
            'time',
            'Time Entry',
            'updated',
            ($validated['activity'] ?? 'General Work') . ' for ' . ($case['case_name'] ?? 'General')
        );

        return redirect()
            ->route('schedule', ['tab' => 'time'])
            ->with('success', 'Time entry updated successfully.');
    }
}
