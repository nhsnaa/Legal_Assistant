<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kreait\Laravel\Firebase\Facades\Firebase;

abstract class Controller
{
    protected function durationMillisecondsFromEntry(array $entry): int
    {
        if (isset($entry['duration_ms']) && is_numeric($entry['duration_ms'])) {
            return max((int) $entry['duration_ms'], 0);
        }

        return max((int) round(((float) ($entry['hours'] ?? 0)) * 3600000), 0);
    }

    protected function formatDurationLabel(int $milliseconds): string
    {
        $totalSeconds = max(1, (int) floor($milliseconds / 1000));
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm ' . $seconds . 's';
        }

        if ($minutes > 0) {
            return $minutes . 'm ' . $seconds . 's';
        }

        return $seconds . 's';
    }

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

    protected function generateDisplayId(string $node, string $prefix): string
    {
        $year = now()->format('Y');
        $needle = $prefix . '-' . $year . '-';

        $nextNumber = $this->workspaceCollection($node)
            ->pluck('display_id')
            ->filter(fn ($displayId) => is_string($displayId) && str_starts_with($displayId, $needle))
            ->map(fn ($displayId) => (int) substr($displayId, strlen($needle)))
            ->max() + 1;

        return sprintf('%s-%s-%03d', $prefix, $year, max($nextNumber, 1));
    }

    protected function displayIdFor(string $prefix, array|object $record, string $firebaseId): string
    {
        $displayId = is_array($record)
            ? ($record['display_id'] ?? null)
            : ($record->display_id ?? null);

        if (is_string($displayId) && $displayId !== '') {
            return $displayId;
        }

        $createdAt = is_array($record)
            ? ($record['created_at'] ?? null)
            : ($record->created_at ?? null);

        $year = $createdAt ? Carbon::parse($createdAt)->format('Y') : now()->format('Y');
        $shortId = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $firebaseId), -6));

        return sprintf('%s-%s-%s', $prefix, $year, $shortId ?: 'LEGACY');
    }

    protected function logActivity(string $module, string $label, string $action, string $subject, array $meta = []): void
    {
        $actor = $this->currentActorMetadata();

        Firebase::database()->getReference('activity_logs')->push($this->workspacePayload(array_merge([
            'module' => $module,
            'label' => $label,
            'action' => $action,
            'subject' => $subject,
            'actor_id' => $actor['id'],
            'actor_name' => $actor['name'],
            'created_at' => now()->toIso8601String(),
        ], $meta)));
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

                if (($hearing['status'] ?? null) === 'Completed') {
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
