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
        $documentRules = $rootData['settings']['document_rules'] ?? [
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

        return view('dashboard', compact(
            'greeting', 'activeCases', 'chartLabels', 'chartData', 
            'hearings', 'recentDocuments', 'recentTimeEntryData', 
            'clients', 'clientsCount', 'documentsCount', 'caseOptions', 'documentRules', 'aiSuggestions', 'aiSummary'
        ));
    }

    public function generateAiSuggestions()
    {
        $rootData = Firebase::database()->getReference()->getValue() ?? [];

        $hearings = collect($rootData['hearings'] ?? [])
            ->filter(fn ($hearing) => !empty($hearing['hearing_time']))
            ->sortBy('hearing_time')
            ->take(3)
            ->values();

        $activeCases = count(array_filter($rootData['cases'] ?? [], fn ($case) => ($case['status'] ?? '') === 'Active'));
        $documentsCount = count($rootData['documents'] ?? []);
        $billableEntriesCount = collect($rootData['time_entries'] ?? [])
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
                'message' => 'GEMINI_API_KEY is not configured yet, so the assistant is using the local planning prototype.',
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
                            'text' => 'You are a legal workflow planning assistant. Generate a practical day plan for a small legal office. Keep suggestions specific, calm, and professional. Return only valid JSON.',
                        ],
                    ],
                ],
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => 'Build a suggested work plan from this dashboard data and return JSON in this shape: {"summary":"...","suggestions":[{"slot":"...","title":"...","timing":"...","detail":"...","reason":"..."}]}. Dashboard data: ' . json_encode([
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
                'message' => 'Live suggestions generated through the Gemini API.',
                'summary' => $aiPlan['summary'] ?? $prototypePlan['summary'],
                'suggestions' => collect($aiPlan['suggestions'] ?? [])->take(3)->values()->all(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('AI suggestion fallback triggered: ' . $exception->getMessage());

            return response()->json([
                'source' => 'prototype',
                'configured' => false,
                'message' => 'Live AI could not be reached, so the assistant is using the local planning prototype instead.',
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

    private function buildPrototypeAiPlan($upcomingHearings, int $activeCases, int $documentsCount, int $billableEntriesCount): array
    {
        $suggestions = [];
        $summary = 'Generate a suggested day plan based on the latest legal workload in the system.';

        if ($upcomingHearings->isNotEmpty()) {
            $nextHearing = $upcomingHearings->first();
            $hearingTime = Carbon::parse($nextHearing['hearing_time']);
            $suggestions[] = [
                'slot' => 'Morning Priority',
                'title' => 'Prepare the next hearing first',
                'timing' => $hearingTime->isToday() ? 'Due today' : 'Upcoming hearing',
                'detail' => 'Focus on "' . ($nextHearing['title'] ?? $nextHearing['case_name'] ?? 'Untitled Case') . '" before ' . $hearingTime->format('d M, g:i A') . '.',
                'reason' => 'Court-related work is the highest-risk item to leave until later in the day.',
            ];

            $summary = 'The dashboard found a hearing-based priority, so the assistant is recommending court preparation first.';
        }

        if ($activeCases > 0) {
            $suggestions[] = [
                'slot' => 'Midday Focus',
                'title' => 'Review active matters in focused blocks',
                'timing' => 'Deep work block',
                'detail' => 'You currently have ' . $activeCases . ' active case' . ($activeCases === 1 ? '' : 's') . '. Group follow-up, research, and drafting into one focused session.',
                'reason' => 'Clustering case review reduces context switching and helps legal drafting move faster.',
            ];
        }

        if ($documentsCount > 0) {
            $suggestions[] = [
                'slot' => 'Afternoon Follow-up',
                'title' => 'Set aside time for document handling',
                'timing' => 'Document window',
                'detail' => 'With ' . $documentsCount . ' stored document' . ($documentsCount === 1 ? '' : 's') . ', reserve a short review window for uploads, checking, or filing.',
                'reason' => 'Documents are easier to batch together after the main case priority is under control.',
            ];
        }

        if ($billableEntriesCount > 0) {
            $suggestions[] = [
                'slot' => 'End-of-day Wrap-up',
                'title' => 'Close the day by confirming billable logs',
                'timing' => 'End-of-day check',
                'detail' => 'You already have ' . $billableEntriesCount . ' billable time entr' . ($billableEntriesCount === 1 ? 'y' : 'ies') . '. Use the last slot of the day to verify time records are complete.',
                'reason' => 'Time records are easiest to verify after the day’s work has been completed.',
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
}
