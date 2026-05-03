<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Kreait\Laravel\Firebase\Facades\Firebase;

abstract class Controller
{
    protected function currentWorkspaceId(): ?string
    {
        return session('workspace_id') ?: session('firebase_user_id');
    }

    protected function currentWorkspaceOwnerId(): ?string
    {
        return session('workspace_owner_id') ?: session('firebase_user_id');
    }

    protected function currentWorkspaceSettingsPath(string $suffix = ''): string
    {
        $workspaceId = $this->currentWorkspaceId();
        $base = 'settings/workspaces/' . $workspaceId;

        return $suffix !== '' ? $base . '/' . ltrim($suffix, '/') : $base;
    }

    protected function workspaceCollection(string $node): Collection
    {
        $workspaceId = $this->currentWorkspaceId();

        return collect(Firebase::database()->getReference($node)->getValue() ?? [])
            ->filter(function ($item) use ($workspaceId) {
                return ($item['workspace_id'] ?? null) === $workspaceId;
            });
    }

    protected function findWorkspaceRecord(string $node, string $id): ?array
    {
        $record = Firebase::database()->getReference($node . '/' . $id)->getValue();

        if (!$record) {
            return null;
        }

        return ($record['workspace_id'] ?? null) === $this->currentWorkspaceId()
            ? $record
            : null;
    }

    protected function workspacePayload(array $payload = []): array
    {
        return array_merge($payload, [
            'workspace_id' => $this->currentWorkspaceId(),
            'workspace_owner_id' => $this->currentWorkspaceOwnerId(),
        ]);
    }
}
