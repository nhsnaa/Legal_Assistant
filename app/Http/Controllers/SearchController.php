<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([
                'results' => [],
            ]);
        }

        $database = Firebase::database();
        $needle = mb_strtolower($query);
        $results = collect();

        $clients = collect($database->getReference('clients')->getValue() ?? [])
            ->map(function ($client, $id) use ($needle) {
                $name = $client['name'] ?? 'Unnamed Client';
                $email = $client['email'] ?? '';

                if (!str_contains(mb_strtolower($name . ' ' . $email), $needle)) {
                    return null;
                }

                return [
                    'type' => 'Client',
                    'title' => $name,
                    'meta' => $email !== '' ? $email : ($client['phone'] ?? 'Client record'),
                    'url' => route('clients.show', $id),
                ];
            })
            ->filter()
            ->take(4);

        $cases = collect($database->getReference('cases')->getValue() ?? [])
            ->map(function ($case, $id) use ($needle) {
                $title = $case['case_name'] ?? 'Untitled Case';
                $description = $case['description'] ?? '';

                if (!str_contains(mb_strtolower($title . ' ' . $description), $needle)) {
                    return null;
                }

                return [
                    'type' => 'Case',
                    'title' => $title,
                    'meta' => $case['status'] ?? 'Case record',
                    'url' => route('cases.show', $id),
                ];
            })
            ->filter()
            ->take(4);

        $documents = collect($database->getReference('documents')->getValue() ?? [])
            ->map(function ($document, $id) use ($needle) {
                $name = $document['name'] ?? 'Untitled Document';
                $original = $document['original_name'] ?? '';

                if (!str_contains(mb_strtolower($name . ' ' . $original), $needle)) {
                    return null;
                }

                return [
                    'type' => 'Document',
                    'title' => $name,
                    'meta' => $document['category'] ?? ($document['type'] ?? 'Document record'),
                    'url' => route('documents.edit', $id),
                ];
            })
            ->filter()
            ->take(4);

        $results = $results
            ->concat($clients)
            ->concat($cases)
            ->concat($documents)
            ->take(8)
            ->values()
            ->all();

        return response()->json([
            'results' => $results,
        ]);
    }
}
