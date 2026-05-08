<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kreait\Laravel\Firebase\Facades\Firebase;

abstract class Controller
{
    protected function currentActorMetadata(): array
    {
        return [
            'id' => session('firebase_user_id'),
            'name' => session('user_name')
                ?: session('first_name')
                ?: session('user_email')
                ?: 'Workspace User',
        ];
    }

    protected function defaultNotificationSettings(): array
    {
        return [
            'deadline_reminders' => true,
            'document_alerts' => true,
            'time_entry_summary' => false,
        ];
    }

    protected function defaultSecuritySettings(): array
    {
        return [
            'session_timeout' => 30,
            'password_policy' => 'strong',
        ];
    }

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

    protected function findScheduleConflicts(Carbon|string $targetTime, ?string $ignoreId = null, int $bufferMinutes = 59): Collection
    {
        $target = $targetTime instanceof Carbon ? $targetTime->copy() : Carbon::parse($targetTime);

        return $this->workspaceCollection('hearings')
            ->map(function ($hearing, $id) {
                return [
                    'id' => $id,
                    'title' => $hearing['title'] ?? ($hearing['case_name'] ?? 'Untitled Task'),
                    'case_name' => $hearing['case_name'] ?? 'General Task',
                    'status' => $hearing['status'] ?? 'Scheduled',
                    'hearing_time' => !empty($hearing['hearing_time'])
                        ? Carbon::parse($hearing['hearing_time'])
                        : null,
                ];
            })
            ->filter(function ($hearing) use ($target, $ignoreId, $bufferMinutes) {
                if (($hearing['id'] ?? null) === $ignoreId || empty($hearing['hearing_time'])) {
                    return false;
                }

                return abs($hearing['hearing_time']->diffInMinutes($target, false)) <= $bufferMinutes;
            })
            ->sortBy(fn ($hearing) => $hearing['hearing_time']?->timestamp ?? 0)
            ->values();
    }

    protected function currentWorkspaceSecuritySettings(): array
    {
        return array_merge(
            $this->defaultSecuritySettings(),
            session('workspace_security_settings', [])
        );
    }

    protected function validatePasswordAgainstPolicy(string $password, string $policy): ?string
    {
        return match ($policy) {
            'strict' => preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{10,}$/', $password)
                ? null
                : 'Strict password policy requires at least 10 characters with uppercase, lowercase, number, and symbol.',
            'strong' => preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)
                ? null
                : 'Strong password policy requires at least 8 characters with letters and numbers.',
            default => strlen($password) >= 4
                ? null
                : 'Password must be at least 4 characters long.',
        };
    }
}
