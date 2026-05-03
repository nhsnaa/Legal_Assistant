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
            <p class="mb-0">Configure the system rules behind clients, cases, documents, time tracking, and scheduling.</p>

            <nav class="settings-nav">
                <a href="#profile" class="active"><i class="bi bi-person-circle"></i> Profile & Firm</a>
                <a href="#users-roles"><i class="bi bi-people"></i> Users & Roles</a>
                <a href="#case"><i class="bi bi-briefcase"></i> Case Defaults</a>
                <a href="#document"><i class="bi bi-folder2-open"></i> Document Rules</a>
                <a href="#schedule"><i class="bi bi-calendar-event"></i> Time & Schedule</a>
                <a href="#notification"><i class="bi bi-bell"></i> Notifications</a>
                <a href="#security"><i class="bi bi-shield-lock"></i> Security</a>
            </nav>
        </aside>

        <main class="settings-main">
            <section class="settings-header">
                <span class="settings-kicker"><i class="bi bi-stars"></i>Workspace Configuration</span>
                <h1 class="display-5 font-weight-bold mb-2">Intelligent Legal Assistant Settings</h1>
                <p class="mb-0">This layout focuses on the settings that matter most for a legal workflow: firm identity, case configuration, document control, time tracking, scheduling, and access security.</p>
                <div class="settings-header-actions">
                    <a href="{{ route('dashboard') }}" class="settings-btn-primary"><i class="bi bi-arrow-left"></i>Back to Dashboard</a>
                    <a href="#profile" class="settings-btn-secondary"><i class="bi bi-layout-text-window-reverse"></i>Review Sections</a>
                </div>
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
                        <button type="button" class="btn btn-info text-white" id="sendInviteBtn"><i class="bi bi-send mr-1"></i>Send Invitation</button>
                        <button type="button" class="btn btn-light" onclick="openSettingsModal('createStaffModal')"><i class="bi bi-person-plus mr-1"></i>Create Staff Account</button>
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
                                        <span class="settings-chip {{ $member->workspace_status === 'pending' ? 'accent' : '' }}">
                                            {{ $member->workspace_status === 'pending' ? 'Pending Invitation' : 'Workspace Access' }}
                                        </span>
                                        <form action="{{ route('settings.staff.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Delete this staff account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="settings-inline-danger">Delete</button>
                                        </form>
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

                <section class="settings-section" id="case">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Case Defaults</h3>
                            <p class="settings-section-note mb-0">Controls how new matters are categorized and tracked.</p>
                        </div>
                        <span class="settings-section-tag">Operations</span>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label class="settings-label">Default Case Status</label>
                            <select class="settings-select">
                                <option>Active</option>
                                <option>Pending</option>
                                <option>Closed</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Case Number Format</label>
                            <input class="settings-input" type="text" placeholder="Enter preferred case number format">
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Matter Categories</label>
                            <div class="settings-chip-row">
                                <button type="button" class="settings-chip accent settings-chip-toggle" aria-pressed="true">Litigation</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Advisory</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Employment</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Corporate</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Family Law</button>
                            </div>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Priority Labels</label>
                            <div class="settings-chip-row">
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Low</button>
                                <button type="button" class="settings-chip accent settings-chip-toggle" aria-pressed="true">Medium</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">High</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Urgent</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="settings-section" id="document">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Document Rules</h3>
                            <p class="settings-section-note mb-0">Useful for keeping legal files consistent and easy to retrieve.</p>
                        </div>
                        <span class="settings-section-tag">Records</span>
                    </div>

                    @php
                        $allowedTypes = old('allowed_file_types', $documentRules['allowed_file_types'] ?? ['PDF', 'DOCX']);
                    @endphp
                    <form class="settings-form-grid" action="{{ route('settings.document-rules.update') }}" method="POST">
                        @csrf
                        <div class="settings-field">
                            <label class="settings-label">Max Upload Size</label>
                            <select class="settings-select" name="max_upload_size">
                                <option value="25" {{ (int) old('max_upload_size', $documentRules['max_upload_size'] ?? 25) === 25 ? 'selected' : '' }}>25 MB</option>
                                <option value="50" {{ (int) old('max_upload_size', $documentRules['max_upload_size'] ?? 25) === 50 ? 'selected' : '' }}>50 MB</option>
                                <option value="100" {{ (int) old('max_upload_size', $documentRules['max_upload_size'] ?? 25) === 100 ? 'selected' : '' }}>100 MB</option>
                            </select>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Allowed File Types</label>
                            <div class="settings-chip-row">
                                @foreach (['PDF', 'DOCX', 'XLSX', 'JPG', 'PNG', 'TXT'] as $fileType)
                                    <label class="settings-chip-wrap">
                                        <input type="checkbox" name="allowed_file_types[]" value="{{ $fileType }}" class="settings-chip-input" {{ in_array($fileType, $allowedTypes) ? 'checked' : '' }}>
                                        <span class="settings-chip {{ in_array($fileType, $allowedTypes) ? 'accent' : '' }} settings-chip-toggle" aria-pressed="{{ in_array($fileType, $allowedTypes) ? 'true' : 'false' }}">{{ $fileType }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-info text-white"><i class="bi bi-check2-circle mr-1"></i>Save Document Rules</button>
                        </div>
                    </form>
                </section>

                <section class="settings-section" id="schedule">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Time & Schedule</h3>
                            <p class="settings-section-note mb-0">Sets the defaults for billable work, hearings, meetings, and reminders.</p>
                        </div>
                        <span class="settings-section-tag">Calendar</span>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label class="settings-label">Default Time Unit</label>
                            <select class="settings-select">
                                <option>15 minutes</option>
                                <option>30 minutes</option>
                                <option>1 hour</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Default Event Duration</label>
                            <select class="settings-select">
                                <option>1 hour</option>
                                <option>2 hours</option>
                                <option>Half day</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Working Days</label>
                            <input class="settings-input" type="text" placeholder="Enter working days">
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Office Hours</label>
                            <input class="settings-input" type="text" placeholder="Enter office hours">
                        </div>
                    </div>
                </section>

                <section class="settings-section" id="notification">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Notifications</h3>
                            <p class="settings-section-note mb-0">These are the most useful alerts for a legal assistant workflow.</p>
                        </div>
                        <span class="settings-section-tag">Alerts</span>
                    </div>

                    <div class="settings-switch-list">
                        <div class="settings-switch">
                            <div class="settings-switch-copy">
                                <strong>Deadline Reminders</strong>
                                <span>Notify staff before hearing dates, filing deadlines, and appointments.</span>
                            </div>
                            <button type="button" class="settings-toggle active" aria-label="Deadline reminders enabled" aria-pressed="true"></button>
                        </div>
                        <div class="settings-switch">
                            <div class="settings-switch-copy">
                                <strong>New Document Alerts</strong>
                                <span>Alert assigned users when a new case document is uploaded.</span>
                            </div>
                            <button type="button" class="settings-toggle active" aria-label="New document alerts enabled" aria-pressed="true"></button>
                        </div>
                        <div class="settings-switch">
                            <div class="settings-switch-copy">
                                <strong>Time Entry Summary</strong>
                                <span>Send end-of-day reminders for incomplete billable or work logs.</span>
                            </div>
                            <button type="button" class="settings-toggle" aria-label="Time entry summary disabled" aria-pressed="false"></button>
                        </div>
                    </div>
                </section>

                <section class="settings-section full" id="security">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Security & Access</h3>
                            <p class="settings-section-note mb-0">Important controls for protecting client confidentiality and internal records.</p>
                        </div>
                        <span class="settings-section-tag">Protection</span>
                    </div>

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label class="settings-label">Session Timeout</label>
                            <select class="settings-select">
                                <option>15 minutes</option>
                                <option selected>30 minutes</option>
                                <option>1 hour</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Password Policy</label>
                            <select class="settings-select">
                                <option selected>Strong</option>
                                <option>Standard</option>
                                <option>Strict</option>
                            </select>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Role Permissions Summary</label>
                            <div class="settings-chip-row">
                                <button type="button" class="settings-chip accent settings-chip-toggle" aria-pressed="true">Admin: Full Access</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Lawyer: Cases, Clients, Documents, Schedule</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Clerk: Documents, Schedule</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Assistant: Limited Case Support</button>
                            </div>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button type="button" class="btn btn-info text-white" data-demo-action="system settings saved"><i class="bi bi-check2-circle mr-1"></i>Save System Settings</button>
                        <button type="button" class="btn btn-light" data-demo-action="permissions review opened"><i class="bi bi-shield-check mr-1"></i>Review Permissions</button>
                    </div>
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

let emailRegistered = false;

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
        emailRegistered = false;
        inviteStaffHelper.textContent = 'Type a valid email to check registration.';
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
            emailRegistered = true;
            inviteStaffHelper.textContent = 'Email is registered. You can send an invitation.';
        } else {
            emailRegistered = false;
            inviteStaffHelper.textContent = 'Email not registered. Use "Create Staff Account" instead.';
        }
        hideInviteResults();
    } catch (error) {
        console.error('Error checking email:', error);
        emailRegistered = false;
        inviteStaffHelper.textContent = 'Error checking email registration.';
        hideInviteResults();
    }
}

function renderInviteResults(matches, query) {
    // Not used anymore
}

document.querySelectorAll('[data-demo-action]').forEach((button) => {
    button.addEventListener('click', () => {
        const action = button.getAttribute('data-demo-action');
        showSettingsToast(action.charAt(0).toUpperCase() + action.slice(1) + '.');
    });
});

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
        const isActive = toggle.classList.toggle('active');
        toggle.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        showSettingsToast((isActive ? 'Enabled: ' : 'Disabled: ') + toggle.closest('.settings-switch').querySelector('strong').textContent);
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
        emailRegistered = false;
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

    if (!emailRegistered) {
        showSettingsToast('Email not registered. Use "Create Staff Account" instead.');
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
