@extends('layouts.app')

@section('title', 'Settings')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endsection

@section('content')
@php
    $settingsDisplayName = $user->first_name ?: ($user->name ?? 'User');
    $settingsRole = $user->user_role ?? 'Counselor';
@endphp
@php
    $workspaceUsersJson = $workspaceUsers->map(function ($member) {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'user_role' => $member->user_role,
            'workspace_status' => $member->workspace_status,
            'is_owner' => $member->is_owner,
        ];
    })->values()->all();
@endphp
<div class="settings-page">
    <div class="settings-toast" id="settingsToast" aria-live="polite"></div>
    <div class="settings-shell">
        <aside class="settings-sidebar">
            <span class="settings-kicker"><i class="bi bi-sliders"></i>System Setup</span>
            <h2 class="h3 font-weight-bold mb-2">Settings</h2>
            <p class="mb-0">Manage the core workspace settings that are already meaningful in the live system.</p>

            <nav class="settings-nav">
                <a href="#profile" class="active"><i class="bi bi-person-circle"></i> Profile & Firm</a>
                <a href="#users-roles"><i class="bi bi-people"></i> Users & Roles</a>
                <a href="#notification"><i class="bi bi-bell"></i> Notifications</a>
                <a href="#security"><i class="bi bi-shield-lock"></i> Security & Access</a>
            </nav>
        </aside>

        <main class="settings-main">
            <section class="settings-header">
                <span class="settings-kicker"><i class="bi bi-stars"></i>Workspace Configuration</span>
                <h1 class="display-5 font-weight-bold mb-2">Intelligent Legal Assistant Settings</h1>
                <p class="mb-0">This page now stays focused on the settings that still matter for the real system: profile, workspace members, notifications, and security.</p>
            </section>

            <div class="settings-grid">
                
                <section class="settings-section full" id="profile">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Profile & Firm Information</h3>
                            <p class="settings-section-note mb-0">Core details that appear across the system and identify the legal office.</p>
                        </div>
                        <span class="settings-section-tag">Essential</span>
                    </div>

                    <form class="settings-form-grid" action="{{ route('settings.profile.update') }}" method="POST">
                        @csrf
                        <div class="settings-field">
                            <label class="settings-label">Display Name</label>
                            <input class="settings-input" type="text" name="display_name" value="{{ old('display_name', $settingsDisplayName) }}">
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Email Address</label>
                            <input class="settings-input" type="email" name="email" value="{{ $user->email }}" readonly>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Role</label>
                            <input class="settings-input" type="text" value="{{ $settingsRole }}" readonly>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Firm / Department</label>
                            <input class="settings-input" type="text" name="firm_name" value="{{ old('firm_name', $user->firm_name ?? '') }}" placeholder="Enter firm or department name">
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Office Address</label>
                            <textarea class="settings-textarea" name="office_address" placeholder="Enter office address">{{ old('office_address', $user->office_address ?? '') }}</textarea>
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-info text-white"><i class="bi bi-check2-circle mr-1"></i>Save Profile</button>
                        </div>
                    </form>
                </section>

                <section class="settings-section full" id="users-roles">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Users & Roles</h3>
                            <p class="settings-section-note mb-0">Invite staff into the workspace and control who can help manage clients, cases, documents, and schedules.</p>
                        </div>
                        <span class="settings-section-tag">Team Access</span>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label class="settings-label">Invite Staff by Email</label>
                            <div class="settings-invite-wrap">
                                <input class="settings-input" type="email" id="inviteStaffEmail" placeholder="assistant@firm.com" autocomplete="off">
                                <div class="settings-invite-results d-none" id="inviteStaffResults"></div>
                            </div>
                            <small class="settings-helper" id="inviteStaffHelper">Type a name or email to find an existing account.</small>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Assign Role</label>
                            <select class="settings-select" id="inviteStaffRole">
                                <option value="staff">Staff</option>
                                <option value="lawyer">Lawyer</option>
                                <option value="clerk">Clerk</option>
                                <option value="assistant">Assistant</option>
                            </select>
                        </div>
                    </div>

                    <div class="settings-actions">
                        @if ($canManageWorkspaceUsers)
                            <button type="button" class="btn btn-info text-white" id="sendInviteBtn"><i class="bi bi-send mr-1"></i>Send Invitation</button>
                            <button type="button" class="btn btn-light" onclick="openSettingsModal('createStaffModal')"><i class="bi bi-person-plus mr-1"></i>Create Staff Account</button>
                        @else
                            <span class="settings-helper">Only the workspace owner can invite or create staff accounts.</span>
                        @endif
                    </div>

                    <div class="settings-member-list">
                        @foreach ($workspaceUsers as $member)
                            <div class="settings-member-card">
                                <div class="settings-member-id">
                                    <div class="settings-member-avatar">{{ strtoupper(substr($member->name ?: 'U', 0, 1)) }}</div>
                                    <div>
                                        <strong>{{ $member->name ?: 'Unnamed User' }}</strong>
                                        <span>{{ ucfirst($member->user_role) }} | {{ $member->email }}</span>
                                    </div>
                                </div>
                                <div class="settings-member-meta">
                                    @if ($member->is_owner)
                                        <span class="settings-chip accent">Workspace Owner</span>
                                        <span class="settings-chip">Full Access</span>
                                    @else
                                        <span class="settings-chip">{{ ucfirst($member->user_role) }}</span>
                                        <span class="settings-chip {{ $member->workspace_status === 'pending_invitation' ? 'accent' : '' }}">
                                            {{ $member->workspace_status === 'pending_invitation' ? 'Pending Invitation' : 'Workspace Access' }}
                                        </span>
                                        @if ($canManageWorkspaceUsers)
                                            <form action="{{ route('settings.staff.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Delete this staff account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="settings-inline-danger">Delete</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if ($workspaceUsers->count() === 1)
                            <div class="settings-empty-state">
                                <i class="bi bi-people"></i>
                                <strong>No additional staff added yet</strong>
                                <p>Use the create staff account button when you are ready to add more team members into this workspace.</p>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="settings-section" id="notification">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Notifications</h3>
                            <p class="settings-section-note mb-0">Choose which workspace alerts should stay visible in your in-app notification panel.</p>
                        </div>
                        <span class="settings-section-tag">Alerts</span>
                    </div>

                    <form action="{{ route('settings.notifications.update') }}" method="POST">
                        @csrf
                        <div class="settings-switch-list">
                            <div class="settings-switch">
                                <div class="settings-switch-copy">
                                    <strong>Deadline Reminders</strong>
                                    <span>Show hearing, filing, and appointment reminders inside the notification panel.</span>
                                </div>
                                <input type="hidden" name="deadline_reminders" value="0">
                                <input type="checkbox" class="settings-toggle-input d-none" id="deadlineRemindersToggle" name="deadline_reminders" value="1" {{ old('deadline_reminders', $notificationSettings['deadline_reminders'] ?? true) ? 'checked' : '' }}>
                                <button type="button" class="settings-toggle {{ old('deadline_reminders', $notificationSettings['deadline_reminders'] ?? true) ? 'active' : '' }}" data-settings-toggle="deadlineRemindersToggle" aria-label="Toggle deadline reminders" aria-pressed="{{ old('deadline_reminders', $notificationSettings['deadline_reminders'] ?? true) ? 'true' : 'false' }}"></button>
                            </div>
                            <div class="settings-switch">
                                <div class="settings-switch-copy">
                                    <strong>New Document Alerts</strong>
                                    <span>Show document upload notifications when case files are added to the workspace.</span>
                                </div>
                                <input type="hidden" name="document_alerts" value="0">
                                <input type="checkbox" class="settings-toggle-input d-none" id="documentAlertsToggle" name="document_alerts" value="1" {{ old('document_alerts', $notificationSettings['document_alerts'] ?? true) ? 'checked' : '' }}>
                                <button type="button" class="settings-toggle {{ old('document_alerts', $notificationSettings['document_alerts'] ?? true) ? 'active' : '' }}" data-settings-toggle="documentAlertsToggle" aria-label="Toggle new document alerts" aria-pressed="{{ old('document_alerts', $notificationSettings['document_alerts'] ?? true) ? 'true' : 'false' }}"></button>
                            </div>
                            <div class="settings-switch">
                                <div class="settings-switch-copy">
                                    <strong>Time Entry Summary</strong>
                                    <span>Show time-summary notifications when the system flags incomplete daily work logs.</span>
                                </div>
                                <input type="hidden" name="time_entry_summary" value="0">
                                <input type="checkbox" class="settings-toggle-input d-none" id="timeEntrySummaryToggle" name="time_entry_summary" value="1" {{ old('time_entry_summary', $notificationSettings['time_entry_summary'] ?? false) ? 'checked' : '' }}>
                                <button type="button" class="settings-toggle {{ old('time_entry_summary', $notificationSettings['time_entry_summary'] ?? false) ? 'active' : '' }}" data-settings-toggle="timeEntrySummaryToggle" aria-label="Toggle time entry summary" aria-pressed="{{ old('time_entry_summary', $notificationSettings['time_entry_summary'] ?? false) ? 'true' : 'false' }}"></button>
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-info text-white"><i class="bi bi-check2-circle mr-1"></i>Save Notification Settings</button>
                        </div>
                    </form>
                </section>

                <section class="settings-section full" id="security">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Security & Access</h3>
                            <p class="settings-section-note mb-0">These workspace-wide rules are saved and used for idle sign-out and password requirements.</p>
                        </div>
                        <span class="settings-section-tag">Protection</span>
                    </div>

                    <form class="settings-form-grid" action="{{ route('settings.security.update') }}" method="POST">
                        @csrf
                        <div class="settings-field">
                            <label class="settings-label">Session Timeout</label>
                            <select class="settings-select" name="session_timeout">
                                <option value="15" {{ (int) old('session_timeout', $securitySettings['session_timeout'] ?? 30) === 15 ? 'selected' : '' }}>15 minutes</option>
                                <option value="30" {{ (int) old('session_timeout', $securitySettings['session_timeout'] ?? 30) === 30 ? 'selected' : '' }}>30 minutes</option>
                                <option value="60" {{ (int) old('session_timeout', $securitySettings['session_timeout'] ?? 30) === 60 ? 'selected' : '' }}>1 hour</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Password Policy</label>
                            <select class="settings-select" name="password_policy">
                                <option value="standard" {{ old('password_policy', $securitySettings['password_policy'] ?? 'strong') === 'standard' ? 'selected' : '' }}>Standard</option>
                                <option value="strong" {{ old('password_policy', $securitySettings['password_policy'] ?? 'strong') === 'strong' ? 'selected' : '' }}>Strong</option>
                                <option value="strict" {{ old('password_policy', $securitySettings['password_policy'] ?? 'strong') === 'strict' ? 'selected' : '' }}>Strict</option>
                            </select>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">What These Settings Do</label>
                            <div class="settings-switch-list">
                                <div class="settings-switch">
                                    <div class="settings-switch-copy">
                                        <strong>Idle session sign-out</strong>
                                        <span>The selected timeout is used to automatically sign users out after inactivity.</span>
                                    </div>
                                </div>
                                <div class="settings-switch">
                                    <div class="settings-switch-copy">
                                        <strong>Password enforcement</strong>
                                        <span>The selected policy is applied when staff accounts are created and when users change their passwords.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-info text-white"><i class="bi bi-check2-circle mr-1"></i>Save Security Settings</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
@endsection

@section('modals')
@include('layouts.modals.logout')
<div id="createStaffModal" class="settings-modal-backdrop" aria-hidden="true">
    <div class="settings-modal-card">
        <div class="settings-modal-header">
            <div>
                <span class="settings-kicker"><i class="bi bi-person-plus"></i>Team Access</span>
                <h4 class="font-weight-bold mb-1 mt-3">Create Staff Account</h4>
                <p class="text-muted mb-0">Add a staff member directly into the workspace with a role and login credentials.</p>
            </div>
            <button type="button" class="settings-modal-close" onclick="closeSettingsModal('createStaffModal')">&times;</button>
        </div>

        <form action="{{ route('settings.staff.store') }}" method="POST" class="settings-form-grid">
            @csrf
            <input type="hidden" name="form_context" value="staff_account">
            <div class="settings-field full">
                <label class="settings-label">Full Name</label>
                <input type="text" class="settings-input" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="settings-field">
                <label class="settings-label">Email Address</label>
                <input type="email" class="settings-input" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="settings-field">
                <label class="settings-label">Assign Role</label>
                <select class="settings-select" name="role">
                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="lawyer" {{ old('role') === 'lawyer' ? 'selected' : '' }}>Lawyer</option>
                    <option value="clerk" {{ old('role') === 'clerk' ? 'selected' : '' }}>Clerk</option>
                    <option value="assistant" {{ old('role') === 'assistant' ? 'selected' : '' }}>Assistant</option>
                </select>
            </div>
            <div class="settings-field full">
                <label class="settings-label">Temporary Password</label>
                <input type="password" class="settings-input" name="password" required>
                <small class="settings-helper">Use a temporary password for now. The staff member can change it later from the profile menu.</small>
            </div>
            <div class="settings-actions">
                <button type="button" class="btn btn-light" onclick="closeSettingsModal('createStaffModal')">Cancel</button>
                <button type="submit" class="btn btn-info text-white"><i class="bi bi-check2-circle mr-1"></i>Create Account</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const settingsToast = document.getElementById('settingsToast');
const workspaceUsers = @json($workspaceUsersJson);
const inviteStaffEmail = document.getElementById('inviteStaffEmail');
const inviteStaffResults = document.getElementById('inviteStaffResults');
const inviteStaffHelper = document.getElementById('inviteStaffHelper');
const inviteStaffRole = document.getElementById('inviteStaffRole');

function showSettingsToast(message) {
    settingsToast.textContent = message;
    settingsToast.classList.add('show');

    clearTimeout(showSettingsToast.timeoutId);
    showSettingsToast.timeoutId = setTimeout(() => {
        settingsToast.classList.remove('show');
    }, 2200);
}

function openSettingsModal(modalId) {
    document.getElementById(modalId)?.classList.add('show');
}

function closeSettingsModal(modalId) {
    document.getElementById(modalId)?.classList.remove('show');
}

function hideInviteResults() {
    inviteStaffResults?.classList.add('d-none');
    if (inviteStaffResults) {
        inviteStaffResults.innerHTML = '';
    }
}

async function checkEmailInFirebase(email) {
    if (!email || !email.includes('@')) {
        inviteStaffHelper.textContent = 'Type a valid email to check whether the account has already registered.';
        hideInviteResults();
        return;
    }

    try {
        const response = await fetch("{{ route('settings.check-email') }}", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ email: email })
        });

        const data = await response.json();

        if (data.registered) {
            inviteStaffHelper.textContent = 'Email is registered. You can send an invitation.';
        } else {
            inviteStaffHelper.textContent = 'Email not registered yet. Ask them to register first or use "Create Staff Account".';
        }
        hideInviteResults();
    } catch (error) {
        console.error('Error checking email:', error);
        inviteStaffHelper.textContent = 'Unable to verify email registration right now.';
        hideInviteResults();
    }
}

function renderInviteResults(matches, query) {
    // Not used anymore
}

document.querySelectorAll('.settings-chip-toggle').forEach((chip) => {
    chip.addEventListener('click', () => {
        const hiddenInput = chip.previousElementSibling;

        if (hiddenInput && hiddenInput.classList.contains('settings-chip-input')) {
            hiddenInput.checked = !hiddenInput.checked;
            chip.classList.toggle('accent', hiddenInput.checked);
            chip.setAttribute('aria-pressed', hiddenInput.checked ? 'true' : 'false');
            return;
        }

        const isActive = chip.classList.toggle('accent');
        chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
});

document.querySelectorAll('.settings-toggle').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const targetInputId = toggle.getAttribute('data-settings-toggle');
        const targetInput = targetInputId ? document.getElementById(targetInputId) : null;
        const isActive = targetInput ? !targetInput.checked : !toggle.classList.contains('active');

        if (targetInput) {
            targetInput.checked = isActive;
        }

        toggle.classList.toggle('active', isActive);
        toggle.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
});

document.querySelectorAll('.settings-nav a').forEach((link) => {
    link.addEventListener('click', () => {
        document.querySelectorAll('.settings-nav a').forEach((item) => item.classList.remove('active'));
        link.classList.add('active');
    });
});

inviteStaffEmail?.addEventListener('blur', () => {
    const email = inviteStaffEmail.value.trim();
    checkEmailInFirebase(email);
});

inviteStaffEmail?.addEventListener('input', () => {
    if (!inviteStaffEmail.value.trim()) {
        inviteStaffHelper.textContent = 'Type an email to check registration.';
        hideInviteResults();
    }
});

inviteStaffResults?.addEventListener('click', (event) => {
    const item = event.target.closest('.settings-invite-item');

    if (!item || !inviteStaffEmail || !inviteStaffRole) {
        return;
    }

    inviteStaffEmail.value = item.dataset.email || '';
    inviteStaffRole.value = item.dataset.role || 'staff';
    hideInviteResults();
    inviteStaffHelper.textContent = 'Existing account selected from the workspace directory.';
});

document.getElementById('sendInviteBtn')?.addEventListener('click', () => {
    const email = inviteStaffEmail?.value.trim() || '';
    const role = inviteStaffRole?.value || 'staff';

    if (!email) {
        showSettingsToast('Enter an email first.');
        return;
    }

    const existingMember = workspaceUsers.find((member) => member.email.toLowerCase() === email.toLowerCase());

    if (existingMember) {
        showSettingsToast('User is already in the workspace.');
        return;
    }

    fetch("{{ route('settings.staff.invite') }}", {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
            email: email,
            role: role
        })
    })
        .then(async (response) => {
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Invitation could not be sent.');
            }

            return payload;
        })
        .then((payload) => {
            showSettingsToast(payload.message || 'Invitation sent.');
            inviteStaffHelper.textContent = 'Invitation sent. The user must accept it from the notification bell.';
        })
        .catch((error) => {
            showSettingsToast(error.message || 'Invitation could not be sent.');
        });
});

document.addEventListener('click', (event) => {
    if (!inviteStaffEmail || !inviteStaffResults) {
        return;
    }

    if (!event.target.closest('.settings-invite-wrap')) {
        hideInviteResults();
    }
});

@if (old('form_context') === 'staff_account')
openSettingsModal('createStaffModal');
@endif
</script>
@endsection
