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

                    <form class="settings-form-grid" data-demo-form="profile">
                        <div class="settings-field">
                            <label class="settings-label">Display Name</label>
                            <input class="settings-input" type="text" name="first_name" value="{{ $settingsDisplayName }}">
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
                            <input class="settings-input" type="text" placeholder="Enter firm or department name">
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Office Address</label>
                            <textarea class="settings-textarea" placeholder="Enter office address"></textarea>
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-info text-white" data-demo-action="profile saved"><i class="bi bi-check2-circle mr-1"></i>Save Profile</button>
                            <button type="button" class="btn btn-light" data-demo-action="logo upload opened"><i class="bi bi-image mr-1"></i>Upload Logo</button>
                        </div>
                    </form>
                </section>

                <!-- Remaining sections identical to original PHP -->
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
                            <input class="settings-input" type="email" placeholder="assistant@firm.com">
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Assign Role</label>
                            <select class="settings-select">
                                <option>Staff</option>
                                <option>Lawyer</option>
                                <option>Clerk</option>
                                <option>Assistant</option>
                            </select>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Access Scope</label>
                            <div class="settings-chip-row">
                                <button type="button" class="settings-chip accent settings-chip-toggle" aria-pressed="true">Clients</button>
                                <button type="button" class="settings-chip accent settings-chip-toggle" aria-pressed="true">Cases</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Documents</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Schedule</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">Billing Review</button>
                            </div>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button type="button" class="btn btn-info text-white" data-demo-action="staff invitation sent"><i class="bi bi-send mr-1"></i>Send Invitation</button>
                        <button type="button" class="btn btn-light" data-demo-action="staff account form opened"><i class="bi bi-person-plus mr-1"></i>Create Staff Account</button>
                    </div>

                    <div class="settings-member-list">
                        <div class="settings-member-card">
                            <div class="settings-member-id">
                                <div class="settings-member-avatar">{{ strtoupper(substr($settingsDisplayName, 0, 1)) }}</div>
                                <div>
                                    <strong>{{ $settingsDisplayName }}</strong>
                                    <span>{{ $settingsRole }} | {{ $user->email }}</span>
                                </div>
                            </div>
                            <div class="settings-member-meta">
                                <span class="settings-chip accent">Workspace Owner</span>
                                <span class="settings-chip">Full Access</span>
                            </div>
                        </div>

                        <div class="settings-empty-state">
                            <i class="bi bi-people"></i>
                            <strong>No additional staff added yet</strong>
                            <p>Use the invite form above when you are ready to add staff accounts into this workspace.</p>
                        </div>
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

                    <div class="settings-form-grid">
                        <div class="settings-field">
                            <label class="settings-label">Default Category</label>
                            <select class="settings-select">
                                <option>Court Filing</option>
                                <option>Client Correspondence</option>
                                <option>Evidence</option>
                                <option>Contract</option>
                            </select>
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Max Upload Size</label>
                            <select class="settings-select">
                                <option>25 MB</option>
                                <option>50 MB</option>
                                <option>100 MB</option>
                            </select>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Allowed File Types</label>
                            <div class="settings-chip-row">
                                <button type="button" class="settings-chip accent settings-chip-toggle" aria-pressed="true">PDF</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">DOCX</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">XLSX</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">JPG</button>
                                <button type="button" class="settings-chip settings-chip-toggle" aria-pressed="false">PNG</button>
                            </div>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">File Naming Rule</label>
                            <input class="settings-input" type="text" placeholder="Enter preferred document naming rule">
                        </div>
                    </div>
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

@section('scripts')
<script>
const settingsToast = document.getElementById('settingsToast');

function showSettingsToast(message) {
    settingsToast.textContent = message;
    settingsToast.classList.add('show');

    clearTimeout(showSettingsToast.timeoutId);
    showSettingsToast.timeoutId = setTimeout(() => {
        settingsToast.classList.remove('show');
    }, 2200);
}

document.querySelectorAll('[data-demo-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        showSettingsToast('Profile changes updated for demo view.');
    });
});

document.querySelectorAll('[data-demo-action]').forEach((button) => {
    button.addEventListener('click', () => {
        const action = button.getAttribute('data-demo-action');
        showSettingsToast(action.charAt(0).toUpperCase() + action.slice(1) + '.');
    });
});

document.querySelectorAll('.settings-chip-toggle').forEach((chip) => {
    chip.addEventListener('click', () => {
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
</script>
@endsection

