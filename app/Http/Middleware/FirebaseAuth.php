<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('firebase_user_id')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in to continue.',
            ]);
        }

        $userId = session('firebase_user_id');
        $database = Firebase::database();
        $user = $database->getReference('users/' . $userId)->getValue() ?? [];
        $workspaceId = $user['workspace_id'] ?? $userId;
        $workspaceOwnerId = $user['workspace_owner_id'] ?? $workspaceId;
        $workspaceStatus = $user['workspace_status'] ?? 'active';

        if (empty($user['workspace_id']) || empty($user['workspace_owner_id']) || empty($user['workspace_status'])) {
            $database->getReference('users/' . $userId)->update([
                'workspace_id' => $workspaceId,
                'workspace_owner_id' => $workspaceOwnerId,
                'workspace_status' => $workspaceStatus,
            ]);
        }

        if ($workspaceOwnerId === $userId) {
            foreach (['clients', 'cases', 'documents', 'hearings', 'time_entries'] as $node) {
                $records = $database->getReference($node)->getValue() ?? [];

                foreach ($records as $recordId => $record) {
                    if (!empty($record['workspace_id'])) {
                        continue;
                    }

                    $database->getReference($node . '/' . $recordId)->update([
                        'workspace_id' => $workspaceId,
                        'workspace_owner_id' => $workspaceOwnerId,
                    ]);
                }
            }

            if (!($database->getReference('settings/workspaces/' . $workspaceId . '/document_rules')->getValue())) {
                $legacyRules = $database->getReference('settings/document_rules')->getValue();

                if ($legacyRules) {
                    $database->getReference('settings/workspaces/' . $workspaceId . '/document_rules')->set($legacyRules);
                }
            }
        }

        session([
            'workspace_id' => $workspaceId,
            'workspace_owner_id' => $workspaceOwnerId,
            'workspace_status' => $workspaceStatus,
        ]);

        return $next($request);
    }
}
