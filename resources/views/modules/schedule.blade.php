@extends('layouts.app')

@section('title', 'Schedule')

@section('content')
<div class="module-shell">
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
                            <button type="button" class="btn btn-light btn-sm" id="schedulePrevMonthBtn">Prev</button>
                            <span class="schedule-month-label" id="scheduleMonthLabel">{{ $selectedMonth->format('F Y') }}</span>
                            <button type="button" class="btn btn-light btn-sm" id="scheduleNextMonthBtn">Next</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openScheduleModal('scheduleEventModal')">
                                <i class="bi bi-plus-lg mr-1"></i>Add Task
                            </button>
                        </div>
                    </div>

                    <div class="schedule-calendar" id="scheduleCalendar">
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                            <div class="schedule-weekday">{{ $weekday }}</div>
                        @endforeach

                        @foreach ($calendarDays as $day)
                            <div class="schedule-day {{ $day['isCurrentMonth'] ? '' : 'is-muted' }}" data-schedule-date="{{ $day['date']->format('Y-m-d') }}" role="button" tabindex="0" aria-label="Add task on {{ $day['date']->format('d M Y') }}">
                                <div class="schedule-day-number">{{ $day['date']->format('j') }}</div>
                                <div class="schedule-day-events">
                                    @foreach ($day['events']->take(3) as $event)
                                        <button type="button" class="schedule-event-pill {{ $event->state_class }} text-left border-0 w-100" onclick="openEventViewModal(@js([
                                            'id' => $event->id,
                                            'title' => $event->title ?: $event->case_name,
                                            'case_name' => $event->case_name,
                                            'status' => $event->display_status,
                                            'notes' => $event->notes ?? '',
                                            'hearing_time' => $event->hearing_time->toIso8601String(),
                                            'created_by_name' => $event->created_by_name ?? null,
                                            'updated_by_name' => $event->updated_by_name ?? null,
                                            'created_at' => $event->created_at?->toIso8601String(),
                                            'updated_at' => $event->updated_at?->toIso8601String(),
                                            'state_class' => $event->state_class,
                                        ]))">
                                            <strong>{{ $event->title ?: $event->case_name }}</strong>
                                            <span>{{ $event->hearing_time->format('g:i A') }}</span>
                                        </button>
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

                    @php
                        $upcomingTaskFilterLabels = [
                            'today' => 'Today',
                            'week' => 'This Week',
                            'all' => 'All',
                            'completed' => 'Completed',
                        ];
                    @endphp

                    <div class="schedule-upcoming-filters" role="tablist" aria-label="Upcoming task filters">
                        @foreach ($upcomingTaskFilterLabels as $filterKey => $filterLabel)
                            <button
                                type="button"
                                class="schedule-upcoming-filter {{ $filterKey === 'week' ? 'active' : '' }}"
                                data-upcoming-filter="{{ $filterKey }}"
                                role="tab"
                                aria-selected="{{ $filterKey === 'week' ? 'true' : 'false' }}"
                            >
                                {{ $filterLabel }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($upcomingTaskFilterLabels as $filterKey => $filterLabel)
                        <div class="schedule-upcoming-list {{ $filterKey === 'week' ? 'active' : '' }}" data-upcoming-panel="{{ $filterKey }}">
                            @forelse (($upcomingTaskFilters[$filterKey] ?? collect()) as $hearing)
                                <div class="schedule-upcoming-item {{ $hearing->state_class }}">
                                    <div class="schedule-upcoming-time">
                                        <strong>{{ $hearing->hearing_time->format('d M') }}</strong>
                                        <span>{{ $hearing->hearing_time->format('g:i A') }}</span>
                                    </div>
                                    <div class="schedule-upcoming-copy">
                                        <strong>{{ $hearing->title ?: $hearing->case_name }}</strong>
                                        <p class="mb-1">{{ $hearing->case_name }}</p>
                                        <span class="module-badge {{ $hearing->status_badge_class }}">
                                            {{ $hearing->display_status }}
                                        </span>
                                        <div class="module-actions-inline mt-2">
                                            <button type="button" class="btn btn-sm btn-light" onclick="openEventViewModal(@js([
                                                'id' => $hearing->id,
                                                'title' => $hearing->title ?: $hearing->case_name,
                                                'case_name' => $hearing->case_name,
                                                'status' => $hearing->display_status,
                                                'notes' => $hearing->notes ?? '',
                                                'hearing_time' => $hearing->hearing_time->toIso8601String(),
                                                'created_by_name' => $hearing->created_by_name ?? null,
                                                'updated_by_name' => $hearing->updated_by_name ?? null,
                                                'created_at' => $hearing->created_at?->toIso8601String(),
                                                'updated_at' => $hearing->updated_at?->toIso8601String(),
                                                'state_class' => $hearing->state_class,
                                            ]))">View</button>
                                            @if ($hearing->status !== 'Completed')
                                                <form action="{{ route('schedule.events.complete', $hearing->id) }}" method="POST" onsubmit="return confirm('Mark this task as completed?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Done</button>
                                                </form>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-light" onclick="openEventEditModal(@js([
                                                'id' => $hearing->id,
                                                'title' => $hearing->title ?: $hearing->case_name,
                                                'case_name' => $hearing->case_name,
                                                'status' => $hearing->status,
                                                'notes' => $hearing->notes ?? '',
                                                'hearing_time' => $hearing->hearing_time->format('Y-m-d\\TH:i'),
                                            ]))">Edit</button>
                                            <form action="{{ route('schedule.events.destroy', $hearing->id) }}" method="POST" onsubmit="return confirm('Delete this scheduled task?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="module-empty-state py-4">
                                    <div class="module-empty-icon"><i class="bi bi-calendar2-event"></i></div>
                                    <h6 class="font-weight-bold">No {{ strtolower($filterLabel) }} tasks</h6>
                                    <p class="text-muted mb-0">Use the add task button to mark work on the calendar.</p>
                                </div>
                            @endforelse
                        </div>
                    @endforeach
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
                                    <th>Duration</th>
                                    <th>Billing</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($timeEntries as $entry)
                                    <tr>
                                        <td class="font-weight-bold">{{ optional($entry->case)->case_name }}</td>
                                        <td>{{ $entry->work_date->format('d M Y') }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $entry->duration_label }}</div>
                                            <small class="text-muted">{{ number_format((float) $entry->hours, 2) }} hours</small>
                                        </td>
                                        <td>
                                            <span class="module-badge {{ !empty($entry->billable) ? 'module-badge-info' : 'module-badge-muted' }}">
                                                {{ !empty($entry->billable) ? 'Billable' : 'Non-Billable' }}
                                            </span>
                                        </td>
                                        <td>{{ $entry->description }}</td>
                                        <td>
                                            <div class="module-actions-inline">
                                                <button type="button" class="btn btn-sm btn-light" onclick="openTimeViewModal(@js([
                                                    'id' => $entry->id,
                                                    'case_id' => $entry->case_id,
                                                    'case_name' => optional($entry->case)->case_name,
                                                    'work_date' => $entry->work_date->format('Y-m-d'),
                                                    'hours' => (float) $entry->hours,
                                                    'duration_label' => $entry->duration_label,
                                                    'activity' => $entry->activity ?? 'General Work',
                                                    'billable' => !empty($entry->billable),
                                                    'description' => $entry->description,
                                                    'created_by_name' => $entry->created_by_name ?? null,
                                                    'updated_by_name' => $entry->updated_by_name ?? null,
                                                    'created_at' => $entry->created_at?->toIso8601String(),
                                                    'updated_at' => $entry->updated_at?->toIso8601String(),
                                                ]))">View</button>
                                                <button type="button" class="btn btn-sm btn-light" onclick="openTimeEditModal(@js([
                                                    'id' => $entry->id,
                                                    'case_id' => $entry->case_id,
                                                    'work_date' => $entry->work_date->format('Y-m-d'),
                                                    'hours' => (float) $entry->hours,
                                                    'duration_label' => $entry->duration_label,
                                                    'activity' => $entry->activity ?? 'General Work',
                                                    'billable' => !empty($entry->billable),
                                                    'description' => $entry->description,
                                                ]))">Edit</button>
                                                <form action="{{ route('schedule.time-entries.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Delete this time entry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="module-empty-cell">No time entries yet. Add one for an existing case.</td>
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

            <form action="{{ route('schedule.events.store') }}" method="POST" id="scheduleEventCreateForm">
                @csrf
                <input type="hidden" name="conflict_context" value="schedule_create">
                <input type="hidden" name="conflict_override" id="scheduleEventConflictOverride" value="0">
                <div class="form-group">
                    <label class="font-weight-bold small">Task Title</label>
                    <input type="text" name="title" class="form-control module-input" required value="{{ old('title') }}">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Related Case</label>
                    <select name="case_name" class="form-control module-input">
                        <option value="">General task / no case</option>
                        @foreach ($caseOptions as $caseOption)
                            <option value="{{ $caseOption->case_name }}" {{ old('case_name') === $caseOption->case_name ? 'selected' : '' }}>{{ $caseOption->case_name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">Leave this blank if the task is internal, administrative, or not tied to one case.</small>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Date & Time</label>
                    <input type="datetime-local" name="hearing_time" id="scheduleEventCreateTime" class="form-control module-input" required value="{{ old('hearing_time') }}">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control module-input">
                        <option value="Scheduled" {{ old('status', 'Scheduled') === 'Scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="Confirmed" {{ old('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="Pending" {{ old('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Completed" {{ old('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Notes</label>
                    <textarea name="notes" rows="3" class="form-control module-input">{{ old('notes') }}</textarea>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleEventModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <div id="scheduleTimeModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-compact">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add Time Entry</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleTimeModal')">&times;</button>
            </div>

            <form action="{{ route('schedule.time-entries.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Case Title</label>
                        <select name="case_id" class="form-control module-input" required>
                            <option value="">Select case</option>
                            @foreach ($caseOptions as $caseOption)
                                <option value="{{ $caseOption->id }}">{{ $caseOption->case_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Date</label>
                        <input type="date" name="work_date" class="form-control module-input" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Hours</label>
                        <input type="number" step="0.25" min="0.25" max="24" name="hours" class="form-control module-input" required>
                    </div>
                    <div class="form-group col-md-6">
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
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small d-block">Billing Type</label>
                    <div class="time-entry-billable-toggle" role="radiogroup" aria-label="Billing type">
                        <input class="time-entry-toggle-input" type="radio" name="billable" id="scheduleBillableYes" value="1" checked>
                        <label class="time-entry-toggle-option" for="scheduleBillableYes">Billable</label>

                        <input class="time-entry-toggle-input" type="radio" name="billable" id="scheduleBillableNo" value="0">
                        <label class="time-entry-toggle-option" for="scheduleBillableNo">Non-Billable</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Description</label>
                    <textarea name="description" rows="3" class="form-control module-input module-textarea-compact" required></textarea>
                </div>

                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleTimeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Time Entry</button>
                </div>
            </form>
        </div>
    </div>

    <div id="scheduleEventViewModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Task Details</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleEventViewModal')">&times;</button>
            </div>
            <div class="module-detail-grid">
                <div class="module-detail-item">
                    <label>Task Title</label>
                    <strong id="scheduleEventViewTitle">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Status</label>
                    <strong id="scheduleEventViewStatus">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Related Case</label>
                    <strong id="scheduleEventViewCase">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Date & Time</label>
                    <strong id="scheduleEventViewTime">-</strong>
                </div>
                <div class="module-detail-item full">
                    <label>Notes</label>
                    <p class="mb-0" id="scheduleEventViewNotes">-</p>
                </div>
                <div class="module-detail-item">
                    <label>Created By</label>
                    <strong id="scheduleEventViewCreatedBy">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Last Updated By</label>
                    <strong id="scheduleEventViewUpdatedBy">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Created On</label>
                    <strong id="scheduleEventViewCreatedAt">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Last Updated</label>
                    <strong id="scheduleEventViewUpdatedAt">-</strong>
                </div>
            </div>
            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleEventViewModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="scheduleEventEditModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Edit Calendar Task</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleEventEditModal')">&times;</button>
            </div>
            <form id="scheduleEventEditForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="conflict_context" value="schedule_edit">
                <input type="hidden" name="conflict_override" id="scheduleEventEditConflictOverride" value="0">
                <div class="form-group">
                    <label class="font-weight-bold small">Task Title</label>
                    <input type="text" name="title" id="scheduleEventEditTitle" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Related Case</label>
                    <select name="case_name" id="scheduleEventEditCase" class="form-control module-input">
                        <option value="">General task / no case</option>
                        @foreach ($caseOptions as $caseOption)
                            <option value="{{ $caseOption->case_name }}">{{ $caseOption->case_name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">Leave blank if this task is not related to a specific case.</small>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Date & Time</label>
                    <input type="datetime-local" name="hearing_time" id="scheduleEventEditTime" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" id="scheduleEventEditStatus" class="form-control module-input">
                        <option value="Scheduled">Scheduled</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Notes</label>
                    <textarea name="notes" id="scheduleEventEditNotes" rows="3" class="form-control module-input"></textarea>
                </div>
                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleEventEditModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Task</button>
                </div>
            </form>
        </div>
    </div>

    <div id="scheduleTimeViewModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Time Entry Details</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleTimeViewModal')">&times;</button>
            </div>
            <div class="module-detail-grid">
                <div class="module-detail-item">
                    <label>Case</label>
                    <strong id="scheduleTimeViewCase">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Date</label>
                    <strong id="scheduleTimeViewDate">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Duration</label>
                    <strong id="scheduleTimeViewHours">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Billing</label>
                    <strong id="scheduleTimeViewBilling">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Activity</label>
                    <strong id="scheduleTimeViewActivity">-</strong>
                </div>
                <div class="module-detail-item full">
                    <label>Description</label>
                    <p class="mb-0" id="scheduleTimeViewDescription">-</p>
                </div>
                <div class="module-detail-item">
                    <label>Created By</label>
                    <strong id="scheduleTimeViewCreatedBy">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Last Updated By</label>
                    <strong id="scheduleTimeViewUpdatedBy">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Created On</label>
                    <strong id="scheduleTimeViewCreatedAt">-</strong>
                </div>
                <div class="module-detail-item">
                    <label>Last Updated</label>
                    <strong id="scheduleTimeViewUpdatedAt">-</strong>
                </div>
            </div>
            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleTimeViewModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="scheduleTimeEditModal" class="module-modal-backdrop" aria-hidden="true">
        <div class="module-modal-card module-modal-card-compact">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Edit Time Entry</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleTimeEditModal')">&times;</button>
            </div>
            <form id="scheduleTimeEditForm" method="POST">
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Case Title</label>
                        <select name="case_id" id="scheduleTimeEditCase" class="form-control module-input" required>
                            <option value="">Select case</option>
                            @foreach ($caseOptions as $caseOption)
                                <option value="{{ $caseOption->id }}">{{ $caseOption->case_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Date</label>
                        <input type="date" name="work_date" id="scheduleTimeEditDate" class="form-control module-input" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Hours</label>
                        <input type="number" step="0.25" min="0.25" max="24" name="hours" id="scheduleTimeEditHours" class="form-control module-input" required>
                        <small class="text-muted d-block mt-2" id="scheduleTimeEditDurationHint">Saved as decimal hours for editing.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Activity</label>
                        <select name="activity" id="scheduleTimeEditActivity" class="form-control module-input">
                            <option>Document Review</option>
                            <option>Client Interview</option>
                            <option>Case Preparation</option>
                            <option>Legal Research</option>
                            <option>Court Preparation</option>
                            <option>Admin Work</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small d-block">Billing Type</label>
                    <div class="time-entry-billable-toggle" role="radiogroup" aria-label="Billing type">
                        <input class="time-entry-toggle-input" type="radio" name="billable" id="scheduleEditBillableYes" value="1">
                        <label class="time-entry-toggle-option" for="scheduleEditBillableYes">Billable</label>
                        <input class="time-entry-toggle-input" type="radio" name="billable" id="scheduleEditBillableNo" value="0">
                        <label class="time-entry-toggle-option" for="scheduleEditBillableNo">Non-Billable</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Description</label>
                    <textarea name="description" id="scheduleTimeEditDescription" rows="3" class="form-control module-input module-textarea-compact" required></textarea>
                </div>
                <div class="module-modal-actions">
                    <button type="button" class="btn btn-light px-4" onclick="closeScheduleModal('scheduleTimeEditModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Time Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('modals')
    @include('layouts.modals.logout')
@endsection

@php
    $scheduleEventsJson = $hearings->map(function ($hearing) {
        return [
            'id' => $hearing->id,
            'title' => $hearing->title ?: $hearing->case_name,
            'case_name' => $hearing->case_name,
            'status' => $hearing->display_status,
            'notes' => $hearing->notes ?? '',
            'hearing_time' => $hearing->hearing_time->toIso8601String(),
            'created_by_name' => $hearing->created_by_name ?? null,
            'updated_by_name' => $hearing->updated_by_name ?? null,
            'created_at' => $hearing->created_at?->toIso8601String(),
            'updated_at' => $hearing->updated_at?->toIso8601String(),
            'state_class' => $hearing->state_class,
        ];
    })->values()->all();
    $scheduleConflictJson = session('schedule_conflict');
@endphp

@section('scripts')
<script>
function openScheduleModal(modalId) {
    document.getElementById(modalId)?.classList.add('show');
}

function closeScheduleModal(modalId) {
    document.getElementById(modalId)?.classList.remove('show');
}

function buildSuggestedTaskDateTime(dateString) {
    const baseDate = dateString || new Date().toISOString().slice(0, 10);
    return baseDate + 'T09:00';
}

function openScheduleCreateForDate(dateString) {
    const createTimeInput = document.getElementById('scheduleEventCreateTime');

    if (createTimeInput && !createTimeInput.value) {
        createTimeInput.value = buildSuggestedTaskDateTime(dateString);
    } else if (createTimeInput && dateString) {
        const existingTime = createTimeInput.value && createTimeInput.value.includes('T')
            ? createTimeInput.value.split('T')[1]
            : '09:00';
        createTimeInput.value = dateString + 'T' + existingTime;
    }

    openScheduleModal('scheduleEventModal');
}

function openEventViewModal(eventItem) {
    document.getElementById('scheduleEventViewTitle').textContent = eventItem.title || '-';
    document.getElementById('scheduleEventViewStatus').textContent = eventItem.status || '-';
    document.getElementById('scheduleEventViewCase').textContent = eventItem.case_name || '-';
    document.getElementById('scheduleEventViewTime').textContent = new Date(eventItem.hearing_time).toLocaleString();
    document.getElementById('scheduleEventViewNotes').textContent = eventItem.notes || 'No notes added.';
    document.getElementById('scheduleEventViewCreatedBy').textContent = eventItem.created_by_name || 'Not recorded';
    document.getElementById('scheduleEventViewUpdatedBy').textContent = eventItem.updated_by_name || 'Not recorded';
    document.getElementById('scheduleEventViewCreatedAt').textContent = eventItem.created_at ? new Date(eventItem.created_at).toLocaleString() : 'Not recorded';
    document.getElementById('scheduleEventViewUpdatedAt').textContent = eventItem.updated_at ? new Date(eventItem.updated_at).toLocaleString() : 'Not recorded';
    openScheduleModal('scheduleEventViewModal');
}

function findScheduleEvent(eventId) {
    return scheduleEvents.find(function (eventItem) {
        return String(eventItem.id) === String(eventId);
    });
}

function openEventViewModalById(eventId) {
    const eventItem = findScheduleEvent(eventId);
    if (eventItem) {
        openEventViewModal(eventItem);
    }
}

function openEventEditModal(eventItem) {
    document.getElementById('scheduleEventEditForm').action = "{{ url('/schedule/events') }}/" + eventItem.id;
    document.getElementById('scheduleEventEditTitle').value = eventItem.title || '';
    document.getElementById('scheduleEventEditCase').value = eventItem.case_name || '';
    document.getElementById('scheduleEventEditTime').value = eventItem.hearing_time || '';
    document.getElementById('scheduleEventEditStatus').value = ['Overdue', 'Due Soon'].includes(eventItem.status) ? 'Scheduled' : (eventItem.status || 'Scheduled');
    document.getElementById('scheduleEventEditNotes').value = eventItem.notes || '';
    openScheduleModal('scheduleEventEditModal');
}

function scheduleConflictHtml(conflictData) {
    const conflicts = Array.isArray(conflictData?.conflicts) ? conflictData.conflicts : [];

    if (!conflicts.length) {
        return `
            <div class="ila-conflict-body">
                <p class="ila-conflict-intro mb-0">Another task already exists near this time.</p>
            </div>
        `;
    }

    return `
        <div class="ila-conflict-body">
            <p class="ila-conflict-intro">${escapeHtml(conflictData.message || 'Possible schedule conflict detected.')}</p>
            <div class="ila-conflict-list">
            ${conflicts.map(function (conflict) {
                return `
                    <div class="ila-conflict-card">
                        <div class="ila-conflict-card-row">
                            <strong class="ila-conflict-title">${escapeHtml(conflict.title || 'Untitled Task')}</strong>
                            <span class="ila-conflict-status">${escapeHtml(conflict.status || 'Scheduled')}</span>
                        </div>
                        <div class="ila-conflict-meta">${escapeHtml(conflict.case_name || 'General Task')}</div>
                        <div class="ila-conflict-time">${escapeHtml(conflict.time || 'Unknown time')}</div>
                    </div>
                `;
            }).join('')}
            </div>
        </div>
    `;
}

function promptScheduleConflict(conflictData) {
    if (!conflictData || typeof Swal === 'undefined') {
        return;
    }

    const context = conflictData.context || 'schedule_create';
    let form = document.getElementById('scheduleEventCreateForm');

    if (context === 'schedule_edit') {
        const payload = conflictData.payload || {};
        document.getElementById('scheduleEventEditForm').action = "{{ url('/schedule/events') }}/" + (conflictData.event_id || '');
        document.getElementById('scheduleEventEditTitle').value = payload.title || '';
        document.getElementById('scheduleEventEditCase').value = payload.case_name || '';
        document.getElementById('scheduleEventEditTime').value = payload.hearing_time || '';
        document.getElementById('scheduleEventEditStatus').value = payload.status || 'Scheduled';
        document.getElementById('scheduleEventEditNotes').value = payload.notes || '';
        form = document.getElementById('scheduleEventEditForm');
    } else {
        closeScheduleModal('scheduleEventModal');
    }

    closeScheduleModal('scheduleEventEditModal');
    closeScheduleModal('scheduleEventModal');

    Swal.fire({
        icon: 'warning',
        title: 'Possible Schedule Conflict',
        html: scheduleConflictHtml(conflictData),
        showCancelButton: true,
        confirmButtonText: 'Continue Anyway',
        cancelButtonText: 'Review Task',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#94a3b8',
        backdrop: 'rgba(15, 23, 42, 0.5)',
        customClass: {
            popup: 'ila-swal-popup ila-conflict-popup',
            confirmButton: 'ila-swal-confirm',
            cancelButton: 'ila-swal-cancel'
        },
        buttonsStyling: false
    }).then(function (result) {
        if (!result.isConfirmed || !form) {
            if (context === 'schedule_edit') {
                openScheduleModal('scheduleEventEditModal');
            } else {
                openScheduleModal('scheduleEventModal');
            }
            return;
        }

        const overrideInput = form.querySelector('input[name="conflict_override"]');
        if (overrideInput) {
            overrideInput.value = '1';
        }

        form.submit();
    });
}

function openTimeViewModal(entry) {
    document.getElementById('scheduleTimeViewCase').textContent = entry.case_name || 'General';
    document.getElementById('scheduleTimeViewDate').textContent = entry.work_date || '-';
    document.getElementById('scheduleTimeViewHours').textContent = entry.duration_label || (Number(entry.hours || 0).toFixed(2) + ' hours');
    document.getElementById('scheduleTimeViewBilling').textContent = entry.billable ? 'Billable' : 'Non-Billable';
    document.getElementById('scheduleTimeViewActivity').textContent = entry.activity || 'General Work';
    document.getElementById('scheduleTimeViewDescription').textContent = entry.description || 'No description added.';
    document.getElementById('scheduleTimeViewCreatedBy').textContent = entry.created_by_name || 'Not recorded';
    document.getElementById('scheduleTimeViewUpdatedBy').textContent = entry.updated_by_name || 'Not recorded';
    document.getElementById('scheduleTimeViewCreatedAt').textContent = entry.created_at ? new Date(entry.created_at).toLocaleString() : 'Not recorded';
    document.getElementById('scheduleTimeViewUpdatedAt').textContent = entry.updated_at ? new Date(entry.updated_at).toLocaleString() : 'Not recorded';
    openScheduleModal('scheduleTimeViewModal');
}

function openTimeEditModal(entry) {
    document.getElementById('scheduleTimeEditForm').action = "{{ url('/schedule/time-entries') }}/" + entry.id;
    document.getElementById('scheduleTimeEditCase').value = entry.case_id || '';
    document.getElementById('scheduleTimeEditDate').value = entry.work_date || '';
    document.getElementById('scheduleTimeEditHours').value = entry.hours || '';
    document.getElementById('scheduleTimeEditDurationHint').textContent = entry.duration_label
        ? 'This entry currently equals ' + entry.duration_label + '. Editing still uses decimal hours.'
        : 'Saved as decimal hours for editing.';
    document.getElementById('scheduleTimeEditActivity').value = entry.activity || 'General Work';
    document.getElementById('scheduleEditBillableYes').checked = !!entry.billable;
    document.getElementById('scheduleEditBillableNo').checked = !entry.billable;
    document.getElementById('scheduleTimeEditDescription').value = entry.description || '';
    activateScheduleTab('timePanel');
    openScheduleModal('scheduleTimeEditModal');
}

document.getElementById('scheduleTimeEditHours')?.addEventListener('input', function () {
    const hours = Number(this.value || 0);
    const totalSeconds = Math.max(1, Math.round(hours * 3600));
    const hourPart = Math.floor(totalSeconds / 3600);
    const minutePart = Math.floor((totalSeconds % 3600) / 60);
    const secondPart = totalSeconds % 60;
    const label = hourPart > 0
        ? `${hourPart}h ${minutePart}m ${secondPart}s`
        : (minutePart > 0 ? `${minutePart}m ${secondPart}s` : `${secondPart}s`);

    document.getElementById('scheduleTimeEditDurationHint').textContent = hours > 0
        ? `This equals about ${label}.`
        : 'Saved as decimal hours for editing.';
});

const scheduleEvents = @json($scheduleEventsJson);
const pendingScheduleConflict = @json($scheduleConflictJson);

let currentScheduleMonth = new Date(@json($selectedMonth->copy()->startOfMonth()->format('Y-m-d')) + 'T00:00:00');

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

document.querySelectorAll('[data-upcoming-filter]').forEach(function (button) {
    button.addEventListener('click', function () {
        const target = button.getAttribute('data-upcoming-filter');

        document.querySelectorAll('[data-upcoming-filter]').forEach(function (item) {
            const isActive = item.getAttribute('data-upcoming-filter') === target;
            item.classList.toggle('active', isActive);
            item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        document.querySelectorAll('[data-upcoming-panel]').forEach(function (panel) {
            panel.classList.toggle('active', panel.getAttribute('data-upcoming-panel') === target);
        });
    });
});

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderScheduleCalendar() {
    const calendar = document.getElementById('scheduleCalendar');
    const monthLabel = document.getElementById('scheduleMonthLabel');

    if (!calendar || !monthLabel) {
        return;
    }

    monthLabel.textContent = currentScheduleMonth.toLocaleDateString([], {
        month: 'long',
        year: 'numeric'
    });

    const firstDay = new Date(currentScheduleMonth.getFullYear(), currentScheduleMonth.getMonth(), 1);
    const startDate = new Date(firstDay);
    startDate.setDate(firstDay.getDate() - firstDay.getDay());

    const lastDay = new Date(currentScheduleMonth.getFullYear(), currentScheduleMonth.getMonth() + 1, 0);
    const endDate = new Date(lastDay);
    endDate.setDate(lastDay.getDate() + (6 - lastDay.getDay()));

    const weekdayMarkup = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        .map(function (weekday) {
            return '<div class="schedule-weekday">' + weekday + '</div>';
        })
        .join('');

    const dayMarkup = [];
    const cursor = new Date(startDate);

    while (cursor <= endDate) {
        const cursorKey = cursor.getFullYear() + '-' + String(cursor.getMonth() + 1).padStart(2, '0') + '-' + String(cursor.getDate()).padStart(2, '0');
        const isCurrentMonth = cursor.getMonth() === currentScheduleMonth.getMonth();

        const dayEvents = scheduleEvents.filter(function (event) {
            const eventDate = new Date(event.hearing_time);
            const eventKey = eventDate.getFullYear() + '-' + String(eventDate.getMonth() + 1).padStart(2, '0') + '-' + String(eventDate.getDate()).padStart(2, '0');
            return eventKey === cursorKey;
        });

        const visibleEvents = dayEvents.slice(0, 3).map(function (event) {
            const eventTime = new Date(event.hearing_time);
            return `
                <button type="button" class="schedule-event-pill ${escapeHtml(event.state_class || 'is-scheduled')} text-left border-0 w-100" onclick="openEventViewModalById('${escapeHtml(event.id)}')">
                    <strong>${escapeHtml(event.title || event.case_name)}</strong>
                    <span>${escapeHtml(eventTime.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }))}</span>
                </button>
            `;
        }).join('');

        const extraCount = dayEvents.length > 3
            ? `<small class="text-muted">+${dayEvents.length - 3} more</small>`
            : '';

        dayMarkup.push(`
            <div class="schedule-day ${isCurrentMonth ? '' : 'is-muted'}" data-schedule-date="${cursorKey}" role="button" tabindex="0" aria-label="Add task on ${cursor.toLocaleDateString([], { day: 'numeric', month: 'short', year: 'numeric' })}">
                <div class="schedule-day-number">${cursor.getDate()}</div>
                <div class="schedule-day-events">
                    ${visibleEvents}
                    ${extraCount}
                </div>
            </div>
        `);

        cursor.setDate(cursor.getDate() + 1);
    }

    calendar.innerHTML = weekdayMarkup + dayMarkup.join('');
}

document.getElementById('schedulePrevMonthBtn')?.addEventListener('click', function () {
    currentScheduleMonth = new Date(currentScheduleMonth.getFullYear(), currentScheduleMonth.getMonth() - 1, 1);
    renderScheduleCalendar();
});

document.getElementById('scheduleNextMonthBtn')?.addEventListener('click', function () {
    currentScheduleMonth = new Date(currentScheduleMonth.getFullYear(), currentScheduleMonth.getMonth() + 1, 1);
    renderScheduleCalendar();
});

document.getElementById('scheduleCalendar')?.addEventListener('click', function (event) {
    if (event.target.closest('.schedule-event-pill')) {
        return;
    }

    const dayCell = event.target.closest('.schedule-day[data-schedule-date]');

    if (!dayCell) {
        return;
    }

    openScheduleCreateForDate(dayCell.getAttribute('data-schedule-date'));
});

document.getElementById('scheduleCalendar')?.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    const dayCell = event.target.closest('.schedule-day[data-schedule-date]');

    if (!dayCell) {
        return;
    }

    event.preventDefault();
    openScheduleCreateForDate(dayCell.getAttribute('data-schedule-date'));
});

renderScheduleCalendar();
promptScheduleConflict(pendingScheduleConflict);

@if (request('tab') === 'time')
activateScheduleTab('timePanel');
@endif

@if (request('create') === 'event')
openScheduleModal('scheduleEventModal');
@endif

@if (request('create') === 'time')
activateScheduleTab('timePanel');
openScheduleModal('scheduleTimeModal');
@endif
</script>
@endsection
