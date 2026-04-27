@extends('layouts.app')

@section('title', 'Schedule')

@section('content')
<div class="module-shell">
    @if (session('success'))
        <div class="module-alert module-alert-success">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="module-alert module-alert-danger">
            <span>{{ $errors->first() }}</span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    @endif

    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Work Planner</span>
            <h1 class="module-title">Schedule & Time</h1>
            <p class="module-copy mb-0">Track hearings and tasks in the calendar, then record case-based work logs in the time section.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-calendar-check-fill"></i>
        </div>
    </div>

    <div class="schedule-tabs">
        <button type="button" class="schedule-tab active" data-schedule-tab="calendarPanel">Schedule</button>
        <button type="button" class="schedule-tab" data-schedule-tab="timePanel">Time</button>
    </div>

    <div id="calendarPanel" class="schedule-panel active">
        <div class="row mt-4">
            <div class="col-xl-8 mb-4">
                <div class="glass-card p-4">
                    <div class="schedule-toolbar">
                        <div>
                            <h5 class="font-weight-bold mb-1">Calendar</h5>
                            <p class="text-muted mb-0">Plan hearings, follow-ups, and other date-based legal work.</p>
                        </div>
                        <div class="schedule-toolbar-actions">
                            <a href="{{ route('schedule', ['month' => $previousMonth]) }}" class="btn btn-light btn-sm">Prev</a>
                            <span class="schedule-month-label">{{ $selectedMonth->format('F Y') }}</span>
                            <a href="{{ route('schedule', ['month' => $nextMonth]) }}" class="btn btn-light btn-sm">Next</a>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openScheduleModal('scheduleEventModal')">
                                <i class="bi bi-plus-lg mr-1"></i>Add Task
                            </button>
                        </div>
                    </div>

                    <div class="schedule-calendar">
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                            <div class="schedule-weekday">{{ $weekday }}</div>
                        @endforeach

                        @foreach ($calendarDays as $day)
                            <div class="schedule-day {{ $day['isCurrentMonth'] ? '' : 'is-muted' }}">
                                <div class="schedule-day-number">{{ $day['date']->format('j') }}</div>
                                <div class="schedule-day-events">
                                    @foreach ($day['events']->take(3) as $event)
                                        <div class="schedule-event-pill">
                                            <strong>{{ $event->title ?: $event->case_name }}</strong>
                                            <span>{{ $event->hearing_time->format('g:i A') }}</span>
                                        </div>
                                    @endforeach
                                    @if ($day['events']->count() > 3)
                                        <small class="text-muted">+{{ $day['events']->count() - 3 }} more</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="font-weight-bold mb-0">Upcoming Tasks</h6>
                        <button type="button" class="btn btn-sm btn-link font-weight-bold p-0" onclick="openScheduleModal('scheduleEventModal')">Add</button>
                    </div>

                    <div class="schedule-upcoming-list">
                        @forelse ($upcomingHearings as $hearing)
                            <div class="schedule-upcoming-item">
                                <div class="schedule-upcoming-time">
                                    <strong>{{ $hearing->hearing_time->format('d M') }}</strong>
                                    <span>{{ $hearing->hearing_time->format('g:i A') }}</span>
                                </div>
                                <div class="schedule-upcoming-copy">
                                    <strong>{{ $hearing->title ?: $hearing->case_name }}</strong>
                                    <p class="mb-1">{{ $hearing->case_name }}</p>
                                    <span class="module-badge {{ $hearing->status === 'Confirmed' ? 'module-badge-success' : ($hearing->status === 'Pending' ? 'module-badge-warning' : 'module-badge-info') }}">
                                        {{ $hearing->status }}
                                    </span>
                                    <form action="{{ route('schedule.events.destroy', $hearing->id) }}" method="POST" class="mt-2" onsubmit="return confirm('Delete this scheduled task?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="module-empty-state py-4">
                                <div class="module-empty-icon"><i class="bi bi-calendar2-event"></i></div>
                                <h6 class="font-weight-bold">No scheduled tasks yet</h6>
                                <p class="text-muted mb-0">Use the add task button to mark work on the calendar.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="timePanel" class="schedule-panel">
        <div class="row mt-4">
            <div class="col-xl-8 mb-4">
                <div class="glass-card p-4 h-100">
                    <div class="schedule-toolbar">
                        <div>
                            <h5 class="font-weight-bold mb-1">Case Time Records</h5>
                            <p class="text-muted mb-0">Every time entry is linked to an existing case, with date, hours, and work description.</p>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openScheduleModal('scheduleTimeModal')">
                            <i class="bi bi-plus-lg mr-1"></i>Add Time Entry
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table module-table mb-0">
                            <thead>
                                <tr>
                                    <th>Case Title</th>
                                    <th>Date</th>
                                    <th>Hours</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($timeEntries as $entry)
                                    <tr>
                                        <td class="font-weight-bold">{{ optional($entry->case)->case_name }}</td>
                                        <td>{{ $entry->work_date->format('d M Y') }}</td>
                                        <td>{{ number_format((float) $entry->hours, 2) }}</td>
                                        <td>{{ $entry->description }}</td>
                                        <td>
                                            <form action="{{ route('schedule.time-entries.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Delete this time entry?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="module-empty-cell">No time entries yet. Add one for an existing case.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="glass-card p-4 h-100">
                    <h6 class="font-weight-bold mb-3">Time Entry Rules</h6>
                    <div class="module-note-list">
                        <div class="module-note-item">
                            <strong>Case-linked only</strong>
                            <p class="mb-0">Each time entry must be assigned to one case that already exists in the system.</p>
                        </div>
                        <div class="module-note-item">
                            <strong>Simple log structure</strong>
                            <p class="mb-0">Record date, hours, and a short work description for quick legal review.</p>
                        </div>
                        <div class="module-note-item">
                            <strong>Works with dashboard timer</strong>
                            <p class="mb-0">This section complements the quick timer flow while giving a cleaner case-based record view.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="scheduleEventModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add Calendar Task</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleEventModal')">&times;</button>
            </div>

            <form action="{{ route('schedule.events.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="font-weight-bold small">Task Title</label>
                    <input type="text" name="title" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Related Case</label>
                    <select name="case_name" class="form-control module-input" required>
                        <option value="">Select case</option>
                        @foreach ($caseOptions as $caseOption)
                            <option value="{{ $caseOption->case_name }}">{{ $caseOption->case_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Date & Time</label>
                    <input type="datetime-local" name="hearing_time" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control module-input">
                        <option value="Scheduled">Scheduled</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Notes</label>
                    <textarea name="notes" rows="3" class="form-control module-input"></textarea>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleEventModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <div id="scheduleTimeModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add Time Entry</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleTimeModal')">&times;</button>
            </div>

            <form action="{{ route('schedule.time-entries.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="font-weight-bold small">Case Title</label>
                    <select name="case_id" class="form-control module-input" required>
                        <option value="">Select case</option>
                        @foreach ($caseOptions as $caseOption)
                            <option value="{{ $caseOption->id }}">{{ $caseOption->case_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Date</label>
                    <input type="date" name="work_date" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Hours</label>
                    <input type="number" step="0.25" min="0.25" max="24" name="hours" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Activity</label>
                    <select name="activity" class="form-control module-input">
                        <option>Document Review</option>
                        <option>Client Interview</option>
                        <option>Case Preparation</option>
                        <option>Legal Research</option>
                        <option>Court Preparation</option>
                        <option>Admin Work</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Description</label>
                    <textarea name="description" rows="4" class="form-control module-input" required></textarea>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleTimeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Time Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection

@section('scripts')
<script>
function openScheduleModal(modalId) {
    document.getElementById(modalId)?.classList.add('show');
}

function closeScheduleModal(modalId) {
    document.getElementById(modalId)?.classList.remove('show');
}

function activateScheduleTab(target) {
    document.querySelectorAll('.schedule-tab').forEach(function (item) {
        item.classList.toggle('active', item.getAttribute('data-schedule-tab') === target);
    });

    document.querySelectorAll('.schedule-panel').forEach(function (panel) {
        panel.classList.toggle('active', panel.id === target);
    });
}

document.querySelectorAll('[data-schedule-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
        activateScheduleTab(tab.getAttribute('data-schedule-tab'));
    });
});

@if (request('tab') === 'time')
activateScheduleTab('timePanel');
@endif

@if (request('create') === 'event')
openScheduleModal('scheduleEventModal');
@endif
</script>
@endsection
