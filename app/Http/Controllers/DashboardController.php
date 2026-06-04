<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        // Check if user is staff and pending invitation
        if (session('workspace_status') === 'pending_invitation') {
            return view('pending-invitation');
        }

        $hour = now()->hour;

        $greeting = match(true) {
            $hour < 12 => 'Good Morning',
            $hour < 18 => 'Good Afternoon',
            default => 'Good Evening'
        };

        // 1. Fetch Cases & Count Active
        $casesRef = $this->workspaceCollection('cases')->all();
        $activeCases = count(array_filter($casesRef, fn($c) => $this->isOpenCaseStatus($c['status'] ?? 'Registered')));

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

        $caseStatusCounts = collect($casesRef)
            ->groupBy(fn ($case) => $case['status'] ?? 'Registered')
            ->map(fn ($cases) => $cases->count())
            ->sortKeys();
        $caseStatusLabels = $caseStatusCounts->keys()->values()->all();
        $caseStatusData = $caseStatusCounts->values()->all();

        // 3. Fetch Hearings (Limit 5) without requiring a Firebase index
        $hearingsRef = $this->workspaceCollection('hearings')->all();
        $allHearings = collect($hearingsRef)
            ->filter(fn ($hearing) => ($hearing['status'] ?? 'Scheduled') !== 'Completed')
            ->sortBy('hearing_time')
            ->values();
        $hearings = $allHearings->take(3);
        $hearingCount = $allHearings->count();

        // 4. Fetch Recent Documents (Limit 4) without Firebase query requirements
        $docsRef = $this->workspaceCollection('documents')->all();
        $allRecentDocuments = collect($docsRef)->reverse()->values();
        $recentDocuments = $allRecentDocuments->take(3);
        $documentsCount = count($docsRef);

        // 5. Recent Time Entries
        $timeRef = $this->workspaceCollection('time_entries')->all();
        $allRecentTimeEntryData = collect($timeRef)->map(function ($entry, $key) {
            $durationMs = $this->durationMillisecondsFromEntry($entry);

            return [
                'id' => $key,
                'durationMs' => $durationMs,
                'durationLabel' => $this->formatDurationLabel($durationMs),
                'activity' => $entry['activity'] ?? 'Unknown',
                'caseName' => $entry['case_name'] ?? 'No case',
                'billable' => $entry['billable'] ?? false,
                'notes' => $entry['description'] ?? '',
                'savedAt' => $entry['created_at'] ?? now()->toIso8601String(),
            ];
        })->reverse()->values()->all();
        $recentTimeEntryData = collect($allRecentTimeEntryData)->take(3)->values()->all();
        $recentTimeEntryCount = count($allRecentTimeEntryData);

        // 6. Clients & Case Options
        $clientsRef = $this->workspaceCollection('clients')->all();
        $clientsCount = count($clientsRef);
        $clients = collect($clientsRef)->map(fn($c, $id) => (object)['id' => $id, 'name' => $c['name']]);
        $documentRules = Firebase::database()->getReference($this->currentWorkspaceSettingsPath('document_rules'))->getValue() ?? [
            'max_upload_size' => 25,
            'allowed_file_types' => ['PDF', 'DOCX'],
        ];

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

        $aiPlan = $this->buildPrototypeAiPlan(
            $upcomingHearings,
            $activeCases,
            $documentsCount,
            $billableEntriesCount
        );
        $aiSuggestions = collect($aiPlan['suggestions'])->values();
        $aiSummary = $aiPlan['summary'];
        $allRecentActivities = $this->buildRecentActivityFeed();
        $recentActivities = $allRecentActivities->take(3)->values();
        $recentActivityCount = $allRecentActivities->count();

        return view('dashboard', compact(
            'greeting', 'activeCases', 'chartLabels', 'chartData', 'caseStatusLabels', 'caseStatusData',
            'hearings', 'recentDocuments', 'recentTimeEntryData', 
            'clients', 'clientsCount', 'documentsCount', 'caseOptions', 'documentRules', 'aiSuggestions', 'aiSummary', 'recentActivities', 'allRecentActivities', 'recentActivityCount',
            'allHearings', 'hearingCount', 'allRecentDocuments', 'allRecentTimeEntryData', 'recentTimeEntryCount'
        ));
    }

    public function generateAiSuggestions()
    {
        $hearings = $this->workspaceCollection('hearings')
            ->filter(fn ($hearing) => !empty($hearing['hearing_time']))
            ->sortBy('hearing_time')
            ->take(3)
            ->values();

        $activeCases = $this->workspaceCollection('cases')->filter(fn ($case) => $this->isOpenCaseStatus($case['status'] ?? 'Registered'))->count();
        $documentsCount = $this->workspaceCollection('documents')->count();
        $billableEntriesCount = $this->workspaceCollection('time_entries')
            ->filter(fn ($entry) => !empty($entry['billable']))
            ->count();

        $prototypePlan = $this->buildPrototypeAiPlan(
            $hearings,
            $activeCases,
            $documentsCount,
            $billableEntriesCount
        );

        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            return response()->json([
                'source' => 'prototype',
                'configured' => false,
                'message' => 'GEMINI_API_KEY is not configured yet, so the assistant is using the local scheduling prototype.',
                'summary' => $prototypePlan['summary'],
                'suggestions' => $prototypePlan['suggestions'],
            ]);
        }

        try {
            $model = config('services.gemini.model', 'gemini-2.5-flash');
            $payload = [
                'system_instruction' => [
                    'parts' => [
                        [
                            'text' => 'You are a legal workflow scheduling assistant. Generate a practical daily schedule for a small legal office. Keep suggestions specific, calm, and professional. Return only valid JSON.',
                        ],
                    ],
                ],
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => 'Build an automated daily schedule from this dashboard data and return JSON in this shape: {"summary":"Daily brief with workload counts and suggested focus. Do not give legal advice.","suggestions":[{"slot":"...","title":"...","timing":"...","detail":"...","reason":"Explain clearly which dashboard data caused this suggestion."}]}. The assistant supports workflow planning only, not legal advice. Dashboard data: ' . json_encode([
                                    'generated_at' => now()->toIso8601String(),
                                    'upcoming_hearings' => $hearings->map(function ($hearing) {
                                        return [
                                            'title' => $hearing['title'] ?? ($hearing['case_name'] ?? 'Untitled Case'),
                                            'case_name' => $hearing['case_name'] ?? 'Untitled Case',
                                            'hearing_time' => $hearing['hearing_time'],
                                            'status' => $hearing['status'] ?? 'Scheduled',
                                        ];
                                    })->values()->all(),
                                    'active_cases' => $activeCases,
                                    'documents_count' => $documentsCount,
                                    'billable_entries_count' => $billableEntriesCount,
                                ], JSON_UNESCAPED_SLASHES),
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                ],
            ];

            $response = null;
            $attemptDelays = [0, 2, 4];

            foreach ($attemptDelays as $attempt => $delaySeconds) {
                if ($delaySeconds > 0) {
                    sleep($delaySeconds);
                }

                $response = Http::withHeaders([
                        'x-goog-api-key' => $apiKey,
                    ])
                    ->timeout(30)
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent', $payload);

                if ($response->successful()) {
                    break;
                }

                if (!in_array($response->status(), [429, 503], true) || $attempt === array_key_last($attemptDelays)) {
                    break;
                }
            }

            if (!$response || $response->failed()) {
                throw new \RuntimeException('Gemini request failed with status ' . ($response ? $response->status() : 'unknown'));
            }

            $responseBody = $response->json();
            $outputText = data_get($responseBody, 'candidates.0.content.parts.0.text');

            if (!$outputText) {
                throw new \RuntimeException('Gemini response did not include candidate text.');
            }

            $aiPlan = json_decode($outputText, true, 512, JSON_THROW_ON_ERROR);

            return response()->json([
                'source' => 'gemini',
                'configured' => true,
                'message' => 'Live automated schedule generated through the Gemini API.',
                'summary' => $aiPlan['summary'] ?? $prototypePlan['summary'],
                'suggestions' => collect($aiPlan['suggestions'] ?? [])->take(3)->values()->all(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('AI suggestion fallback triggered: ' . $exception->getMessage());

            return response()->json([
                'source' => 'prototype',
                'configured' => false,
                'message' => 'Live AI could not be reached, so the assistant is using the local scheduling prototype instead.',
                'summary' => $prototypePlan['summary'],
                'suggestions' => $prototypePlan['suggestions'],
            ]);
        }
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
        $case = $this->findWorkspaceRecord('cases', $validated['case_id']);
        $timestamp = now()->toIso8601String();

        $actor = $this->currentActorMetadata();

        $newData = $this->workspacePayload([
            'case_id' => $validated['case_id'],
            'case_name' => $case['case_name'] ?? 'General',
            'work_date' => date('Y-m-d', strtotime($validated['stopped_at'])),
            'hours' => $hours,
            'duration_ms' => $validated['duration_ms'],
            'activity' => $validated['activity'],
            'billable' => (bool) $validated['billable'],
            'description' => $validated['description'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'created_by_id' => $actor['id'],
            'created_by_name' => $actor['name'],
            'updated_by_id' => $actor['id'],
            'updated_by_name' => $actor['name'],
        ]);

        $newPostKey = $database->getReference('time_entries')->push($newData)->getKey();
        $this->logActivity(
            'time',
            'Time Entry',
            'created',
            ($validated['activity'] ?? 'General Work') . ' for ' . ($case['case_name'] ?? 'General')
        );

        return response()->json([
            'message' => 'Time entry saved successfully.',
            'entry' => [
                'id' => $newPostKey,
                'durationMs' => $validated['duration_ms'],
                'durationLabel' => $this->formatDurationLabel($validated['duration_ms']),
                'activity' => $newData['activity'],
                'caseName' => $newData['case_name'],
                'billable' => $newData['billable'],
                'description' => $newData['description'],
                'savedAt' => $newData['created_at'],
            ],
            'activityLog' => [
                'module' => 'time',
                'label' => 'Time Entry',
                'actor' => $actor['name'],
                'action' => 'created',
                'subject' => ($validated['activity'] ?? 'General Work') . ' for ' . ($case['case_name'] ?? 'General'),
                'time_label' => 'Just now',
                'timestamp' => $timestamp,
            ],
        ]);
    }

    public function applyAiSuggestionsToSchedule(Request $request)
    {
        $validated = $request->validate([
            'suggestions' => ['required', 'array', 'min:1'],
            'suggestions.*.slot' => ['nullable', 'string', 'max:255'],
            'suggestions.*.title' => ['required', 'string', 'max:255'],
            'suggestions.*.timing' => ['nullable', 'string', 'max:255'],
            'suggestions.*.detail' => ['nullable', 'string'],
            'suggestions.*.reason' => ['nullable', 'string'],
            'force_conflicts' => ['nullable', 'boolean'],
        ]);

        $database = Firebase::database();
        $existingHearings = $this->workspaceCollection('hearings');
        $slotTimeMap = [
            'Morning Priority' => '09:00',
            'Midday Focus' => '11:30',
            'Afternoon Follow-up' => '14:30',
            'End-of-day Wrap-up' => '17:00',
            'Suggested Starting Point' => '10:00',
        ];

        $created = 0;
        $skipped = 0;
        $today = now()->toDateString();
        $conflicts = [];
        $actor = $this->currentActorMetadata();
        $timestamp = now()->toIso8601String();

        foreach ($validated['suggestions'] as $suggestion) {
            $slot = $suggestion['slot'] ?? 'Suggested Block';
            $time = $slotTimeMap[$slot] ?? '10:00';
            $targetTime = Carbon::parse($today . ' ' . $time);
            $hearingTime = $targetTime->toIso8601String();
            $title = trim($suggestion['title']);
            $detail = trim((string) ($suggestion['detail'] ?? ''));
            $reason = trim((string) ($suggestion['reason'] ?? ''));

            $isDuplicate = $existingHearings->contains(function ($hearing) use ($title, $hearingTime) {
                return ($hearing['title'] ?? '') === $title
                    && ($hearing['hearing_time'] ?? '') === $hearingTime;
            });

            if ($isDuplicate) {
                $skipped++;
                continue;
            }

            $nearbyConflicts = $this->findScheduleConflicts($targetTime);

            if ($nearbyConflicts->isNotEmpty() && !$request->boolean('force_conflicts')) {
                $conflicts[] = [
                    'slot' => $slot,
                    'title' => $title,
                    'scheduled_time' => $targetTime->format('d M Y, g:i A'),
                    'conflicts_with' => $nearbyConflicts->map(function ($conflict) {
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

                continue;
            }

            $notes = collect([
                'AI automated schedule block',
                $detail !== '' ? 'Plan: ' . $detail : null,
                $reason !== '' ? 'Scheduling note: ' . $reason : null,
            ])->filter()->implode("\n");

            $database->getReference('hearings')->push($this->workspacePayload([
                'title' => $title,
                'case_name' => 'General Task',
                'hearing_time' => $hearingTime,
                'status' => 'Scheduled',
                'notes' => $notes,
                'created_from_ai_schedule' => true,
                'ai_slot' => $slot,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'created_by_id' => $actor['id'],
                'created_by_name' => $actor['name'],
                'updated_by_id' => $actor['id'],
                'updated_by_name' => $actor['name'],
            ]));
            $this->logActivity('schedule', 'Task', 'created', $title, [
                'source' => 'ai_schedule',
            ]);

            $created++;
        }

        if (!empty($conflicts) && !$request->boolean('force_conflicts')) {
            return response()->json([
                'success' => false,
                'message' => 'Possible schedule conflicts detected. One or more AI blocks overlap with existing tasks.',
                'conflicts' => $conflicts,
                'skipped' => $skipped,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'message' => $created > 0
                ? 'AI schedule applied to the calendar.'
                : 'No new schedule blocks were added because matching tasks already exist for today.',
        ]);
    }

    private function buildPrototypeAiPlan($upcomingHearings, int $activeCases, int $documentsCount, int $billableEntriesCount): array
    {
        $suggestions = [];
        $summary = 'Daily brief: ' . $activeCases . ' active case' . ($activeCases === 1 ? '' : 's') . ', '
            . $upcomingHearings->count() . ' upcoming hearing' . ($upcomingHearings->count() === 1 ? '' : 's') . ', '
            . $documentsCount . ' stored document' . ($documentsCount === 1 ? '' : 's') . ', and '
            . $billableEntriesCount . ' billable time entr' . ($billableEntriesCount === 1 ? 'y' : 'ies') . '. Suggested focus: organize today around the highest-risk legal work first.';

        if ($upcomingHearings->isNotEmpty()) {
            $nextHearing = $upcomingHearings->first();
            $hearingTime = Carbon::parse($nextHearing['hearing_time']);
            $suggestions[] = [
                'slot' => 'Morning Priority',
                'title' => 'Reserve the first block for hearing preparation',
                'timing' => $hearingTime->isToday() ? 'Due today' : 'Upcoming hearing',
                'detail' => 'Focus on "' . ($nextHearing['title'] ?? $nextHearing['case_name'] ?? 'Untitled Case') . '" before ' . $hearingTime->format('d M, g:i A') . '.',
                'reason' => 'Suggested because the workspace has an upcoming hearing for "' . ($nextHearing['case_name'] ?? 'a case') . '" scheduled on ' . $hearingTime->format('d M, g:i A') . '. Court-related work is the highest-risk item to leave until later in the day.',
            ];

            $summary = 'Daily brief: the next hearing is ' . $hearingTime->format('d M, g:i A') . ' for "' . ($nextHearing['case_name'] ?? 'a case') . '". Suggested focus: prepare hearing materials first, then continue case work and document review.';
        }

        if ($activeCases > 0) {
            $suggestions[] = [
                'slot' => 'Midday Focus',
                'title' => 'Schedule a focused active-case work block',
                'timing' => 'Deep work block',
                'detail' => 'You currently have ' . $activeCases . ' active case' . ($activeCases === 1 ? '' : 's') . '. Group follow-up, research, and drafting into one focused session.',
                'reason' => 'Suggested because the workspace currently has ' . $activeCases . ' active case' . ($activeCases === 1 ? '' : 's') . '. Clustering case review reduces context switching and helps legal drafting move faster.',
            ];
        }

        if ($documentsCount > 0) {
            $suggestions[] = [
                'slot' => 'Afternoon Follow-up',
                'title' => 'Schedule a document review and filing block',
                'timing' => 'Document window',
                'detail' => 'With ' . $documentsCount . ' stored document' . ($documentsCount === 1 ? '' : 's') . ', reserve a short review window for uploads, checking, or filing.',
                'reason' => 'Suggested because the workspace has ' . $documentsCount . ' document record' . ($documentsCount === 1 ? '' : 's') . '. Documents are easier to batch together after the main case priority is under control.',
            ];
        }

        if ($billableEntriesCount > 0) {
            $suggestions[] = [
                'slot' => 'End-of-day Wrap-up',
                'title' => 'Reserve a wrap-up block for billable review',
                'timing' => 'End-of-day check',
                'detail' => 'You already have ' . $billableEntriesCount . ' billable time entr' . ($billableEntriesCount === 1 ? 'y' : 'ies') . '. Use the last slot of the day to verify time records are complete.',
                'reason' => 'Suggested because the workspace already has ' . $billableEntriesCount . ' billable time entr' . ($billableEntriesCount === 1 ? 'y' : 'ies') . '. Time records are easiest to verify after the day\'s work has been completed.',
            ];
        }

        if (empty($suggestions)) {
            $suggestions[] = [
                'slot' => 'Suggested Starting Point',
                'title' => 'Start by adding today’s first legal activity',
                'timing' => 'Setup step',
                'detail' => 'There is not enough case, hearing, or billing data yet, so begin by creating one task, one case, or one time record.',
                'reason' => 'The assistant improves as soon as the workspace has more real working data.',
            ];
        }

        return [
            'summary' => $summary,
            'suggestions' => collect($suggestions)->take(3)->values()->all(),
        ];
    }

    private function isOpenCaseStatus(string $status): bool
    {
        return !in_array($status, ['Closed', 'Completed', 'Cancelled'], true);
    }

    private function buildRecentActivityFeed()
    {
        return $this->workspaceCollection('activity_logs')
            ->map(function ($item, $id) {
                $timestamp = !empty($item['created_at']) ? Carbon::parse($item['created_at']) : null;

                return [
                    'id' => $id,
                    'module' => $item['module'] ?? 'system',
                    'label' => $item['label'] ?? 'Record',
                    'actor' => $item['actor_name'] ?? 'Workspace User',
                    'action' => $item['action'] ?? 'updated',
                    'subject' => $item['subject'] ?? 'Workspace Record',
                    'timestamp' => $timestamp,
                    'time_label' => $timestamp ? $timestamp->diffForHumans() : 'Unknown time',
                ];
            })
            ->filter(fn ($item) => !empty($item['timestamp']))
            ->sortByDesc(fn ($item) => $item['timestamp']->timestamp)
            ->take(8)
            ->values();
    }
}
