@extends('layouts.app')

@section('title', 'Schedule')

@section('content')
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Work Planner</span>
            <h1 class="module-title">Schedule & Time</h1>
            <p class="module-copy">Track hearings and tasks, then record work logs.</p>
        </div>
        <div class="module-hero-icon"><i class="bi bi-calendar-check-fill"></i></div>
    </div>

    <div class="schedule-tabs">
        <button type="button" class="schedule-tab active" data-schedule-tab="calendarPanel">Schedule</button>
        <button type="button" class="schedule-tab" data-schedule-tab="timePanel">Time</button>
    </div>

    {{-- Panels --}}
    <div id="calendarPanel" class="schedule-panel active">
        </div>

    <div id="timePanel" class="schedule-panel">
        </div>
</div>
@endsection

@section('scripts')
<script>
function activateScheduleTab(target) {
    document.querySelectorAll('.schedule-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.schedule-panel').forEach(p => p.classList.remove('active'));
    
    document.querySelector(`[data-schedule-tab="${target}"]`).classList.add('active');
    document.getElementById(target).classList.add('active');
}

document.querySelectorAll('[data-schedule-tab]').forEach(tab => {
    tab.addEventListener('click', () => activateScheduleTab(tab.getAttribute('data-schedule-tab')));
});
</script>
@endsection