@extends('layouts.app')

@section('title', 'Settings')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endsection

@section('content')
@php
    // Fallback logic for user details
    $settingsDisplayName = $user->first_name ?: ($user->name ?? 'Amir');
    $settingsRole = $user->user_role ?? 'Counselor';
@endphp

<div class="settings-page">
    <div class="settings-toast" id="settingsToast" aria-live="polite"></div>
    <div class="settings-shell">
        
        <aside class="settings-sidebar">
            <span class="settings-kicker"><i class="bi bi-sliders"></i> System Setup</span>
            <h2 class="h3 font-weight-bold mb-2">Settings</h2>
            <p class="mb-4">Configure the system rules behind clients, cases, documents, time tracking, and scheduling.</p>

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
                <span class="settings-kicker"><i class="bi bi-stars"></i> Workspace Configuration</span>
                <h1 class="display-5 font-weight-bold mb-2">Intelligent Legal Assistant Settings</h1>
                <p class="mb-0">Focus on the settings that matter most for a legal workflow: firm identity, case configuration, document control, and access security.</p>
                <div class="settings-header-actions">
                    <a href="{{ route('dashboard') }}" class="settings-btn-primary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
                    <a href="#profile" class="settings-btn-secondary"><i class="bi bi-layout-text-window-reverse"></i> Review Sections</a>
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
                            <button type="submit" class="btn btn-info text-white"><i class="bi bi-check2-circle mr-1"></i> Save Profile</button>
                            <button type="button" class="btn btn-light" data-demo-action="logo upload opened"><i class="bi bi-image mr-1"></i> Upload Logo</button>
                        </div>
                    </form>
                </section>

                <section class="settings-section full" id="users-roles">
                    <div class="settings-section-header">
                        <div>
                            <h3 class="settings-section-title">Users & Roles</h3>
                            <p class="settings-section-note mb-0">Invite staff and control workspace access.</p>
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
                            </select>
                        </div>
                        <div class="settings-field full">
                            <label class="settings-label">Access Scope</label>
                            <div class="settings-chip-row">
                                <button type="button" class="settings-chip accent settings-chip-toggle">Clients</button>
                                <button type="button" class="settings-chip accent settings-chip-toggle">Cases</button>
                                <button type="button" class="settings-chip settings-chip-toggle">Documents</button>
                                <button type="button" class="settings-chip settings-chip-toggle">Schedule</button>
                            </div>
                        </div>
                    </div>

                    <div class="settings-member-list mt-4">
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
                            </div>
                        </div>
                    </div>
                </section>

                </div> </main>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Your existing script remains fully functional here
    const settingsToast = document.getElementById('settingsToast');

    function showSettingsToast(message) {
        settingsToast.textContent = message;
        settingsToast.classList.add('show');
        setTimeout(() => settingsToast.classList.remove('show'), 2200);
    }

    document.querySelectorAll('.settings-nav a').forEach(link => {
        link.addEventListener('click', () => {
            document.querySelectorAll('.settings-nav a').forEach(item => item.classList.remove('active'));
            link.classList.add('active');
        });
    });

    // Handle Chip Toggles
    document.querySelectorAll('.settings-chip-toggle').forEach(chip => {
        chip.addEventListener('click', () => chip.classList.toggle('accent'));
    });
</script>
@endsection