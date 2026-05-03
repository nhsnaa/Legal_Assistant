<?php $__env->startSection('title', 'Schedule'); ?>

<?php $__env->startSection('content'); ?>
<div class="module-shell">
    <?php if(session('success')): ?>
        <div class="module-alert module-alert-success">
            <span><?php echo e(session('success')); ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="module-alert module-alert-danger">
            <span><?php echo e($errors->first()); ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

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
                            <span class="schedule-month-label" id="scheduleMonthLabel"><?php echo e($selectedMonth->format('F Y')); ?></span>
                            <button type="button" class="btn btn-light btn-sm" id="scheduleNextMonthBtn">Next</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openScheduleModal('scheduleEventModal')">
                                <i class="bi bi-plus-lg mr-1"></i>Add Task
                            </button>
                        </div>
                    </div>

                    <div class="schedule-calendar" id="scheduleCalendar">
                        <?php $__currentLoopData = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $weekday): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="schedule-weekday"><?php echo e($weekday); ?></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <?php $__currentLoopData = $calendarDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="schedule-day <?php echo e($day['isCurrentMonth'] ? '' : 'is-muted'); ?>">
                                <div class="schedule-day-number"><?php echo e($day['date']->format('j')); ?></div>
                                <div class="schedule-day-events">
                                    <?php $__currentLoopData = $day['events']->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <button type="button" class="schedule-event-pill text-left border-0 w-100" onclick="openEventViewModal(<?php echo \Illuminate\Support\Js::from([
                                            'id' => $event->id,
                                            'title' => $event->title ?: $event->case_name,
                                            'case_name' => $event->case_name,
                                            'status' => $event->status,
                                            'notes' => $event->notes ?? '',
                                            'hearing_time' => $event->hearing_time->toIso8601String(),
                                        ])->toHtml() ?>)">
                                            <strong><?php echo e($event->title ?: $event->case_name); ?></strong>
                                            <span><?php echo e($event->hearing_time->format('g:i A')); ?></span>
                                        </button>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($day['events']->count() > 3): ?>
                                        <small class="text-muted">+<?php echo e($day['events']->count() - 3); ?> more</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                        <?php $__empty_1 = true; $__currentLoopData = $upcomingHearings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hearing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="schedule-upcoming-item">
                                <div class="schedule-upcoming-time">
                                    <strong><?php echo e($hearing->hearing_time->format('d M')); ?></strong>
                                    <span><?php echo e($hearing->hearing_time->format('g:i A')); ?></span>
                                </div>
                                <div class="schedule-upcoming-copy">
                                    <strong><?php echo e($hearing->title ?: $hearing->case_name); ?></strong>
                                    <p class="mb-1"><?php echo e($hearing->case_name); ?></p>
                                    <span class="module-badge <?php echo e($hearing->status === 'Confirmed' ? 'module-badge-success' : ($hearing->status === 'Pending' ? 'module-badge-warning' : 'module-badge-info')); ?>">
                                        <?php echo e($hearing->status); ?>

                                    </span>
                                    <div class="module-actions-inline mt-2">
                                        <button type="button" class="btn btn-sm btn-light" onclick="openEventViewModal(<?php echo \Illuminate\Support\Js::from([
                                            'id' => $hearing->id,
                                            'title' => $hearing->title ?: $hearing->case_name,
                                            'case_name' => $hearing->case_name,
                                            'status' => $hearing->status,
                                            'notes' => $hearing->notes ?? '',
                                            'hearing_time' => $hearing->hearing_time->toIso8601String(),
                                        ])->toHtml() ?>)">View</button>
                                        <button type="button" class="btn btn-sm btn-light" onclick="openEventEditModal(<?php echo \Illuminate\Support\Js::from([
                                            'id' => $hearing->id,
                                            'title' => $hearing->title ?: $hearing->case_name,
                                            'case_name' => $hearing->case_name,
                                            'status' => $hearing->status,
                                            'notes' => $hearing->notes ?? '',
                                            'hearing_time' => $hearing->hearing_time->format('Y-m-d\\TH:i'),
                                        ])->toHtml() ?>)">Edit</button>
                                        <form action="<?php echo e(route('schedule.events.destroy', $hearing->id)); ?>" method="POST" onsubmit="return confirm('Delete this scheduled task?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="module-empty-state py-4">
                                <div class="module-empty-icon"><i class="bi bi-calendar2-event"></i></div>
                                <h6 class="font-weight-bold">No scheduled tasks yet</h6>
                                <p class="text-muted mb-0">Use the add task button to mark work on the calendar.</p>
                            </div>
                        <?php endif; ?>
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
                                    <th>Billing</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $timeEntries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="font-weight-bold"><?php echo e(optional($entry->case)->case_name); ?></td>
                                        <td><?php echo e($entry->work_date->format('d M Y')); ?></td>
                                        <td><?php echo e(number_format((float) $entry->hours, 2)); ?></td>
                                        <td>
                                            <span class="module-badge <?php echo e(!empty($entry->billable) ? 'module-badge-info' : 'module-badge-muted'); ?>">
                                                <?php echo e(!empty($entry->billable) ? 'Billable' : 'Non-Billable'); ?>

                                            </span>
                                        </td>
                                        <td><?php echo e($entry->description); ?></td>
                                        <td>
                                            <div class="module-actions-inline">
                                                <button type="button" class="btn btn-sm btn-light" onclick="openTimeViewModal(<?php echo \Illuminate\Support\Js::from([
                                                    'id' => $entry->id,
                                                    'case_id' => $entry->case_id,
                                                    'case_name' => optional($entry->case)->case_name,
                                                    'work_date' => $entry->work_date->format('Y-m-d'),
                                                    'hours' => (float) $entry->hours,
                                                    'activity' => $entry->activity ?? 'General Work',
                                                    'billable' => !empty($entry->billable),
                                                    'description' => $entry->description,
                                                ])->toHtml() ?>)">View</button>
                                                <button type="button" class="btn btn-sm btn-light" onclick="openTimeEditModal(<?php echo \Illuminate\Support\Js::from([
                                                    'id' => $entry->id,
                                                    'case_id' => $entry->case_id,
                                                    'work_date' => $entry->work_date->format('Y-m-d'),
                                                    'hours' => (float) $entry->hours,
                                                    'activity' => $entry->activity ?? 'General Work',
                                                    'billable' => !empty($entry->billable),
                                                    'description' => $entry->description,
                                                ])->toHtml() ?>)">Edit</button>
                                                <form action="<?php echo e(route('schedule.time-entries.destroy', $entry->id)); ?>" method="POST" onsubmit="return confirm('Delete this time entry?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="module-empty-cell">No time entries yet. Add one for an existing case.</td>
                                    </tr>
                                <?php endif; ?>
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

            <form action="<?php echo e(route('schedule.events.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="font-weight-bold small">Task Title</label>
                    <input type="text" name="title" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Related Case</label>
                    <select name="case_name" class="form-control module-input">
                        <option value="">General task / no case</option>
                        <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($caseOption->case_name); ?>"><?php echo e($caseOption->case_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <small class="text-muted d-block mt-2">Leave this blank if the task is internal, administrative, or not tied to one case.</small>
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
        <div class="module-modal-card module-modal-card-compact">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-weight-bold mb-0">Add Time Entry</h4>
                <button type="button" class="module-modal-close" onclick="closeScheduleModal('scheduleTimeModal')">&times;</button>
            </div>

            <form action="<?php echo e(route('schedule.time-entries.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Case Title</label>
                        <select name="case_id" class="form-control module-input" required>
                            <option value="">Select case</option>
                            <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($caseOption->id); ?>"><?php echo e($caseOption->case_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Date</label>
                        <input type="date" name="work_date" class="form-control module-input" value="<?php echo e(now()->toDateString()); ?>" required>
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
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div class="form-group">
                    <label class="font-weight-bold small">Task Title</label>
                    <input type="text" name="title" id="scheduleEventEditTitle" class="form-control module-input" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold small">Related Case</label>
                    <select name="case_name" id="scheduleEventEditCase" class="form-control module-input">
                        <option value="">General task / no case</option>
                        <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($caseOption->case_name); ?>"><?php echo e($caseOption->case_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                    <label>Hours</label>
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
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold small">Case Title</label>
                        <select name="case_id" id="scheduleTimeEditCase" class="form-control module-input" required>
                            <option value="">Select case</option>
                            <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($caseOption->id); ?>"><?php echo e($caseOption->case_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
    <?php echo $__env->make('layouts.modals.logout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php
    $scheduleEventsJson = $hearings->map(function ($hearing) {
        return [
            'id' => $hearing->id,
            'title' => $hearing->title ?: $hearing->case_name,
            'case_name' => $hearing->case_name,
            'status' => $hearing->status,
            'notes' => $hearing->notes ?? '',
            'hearing_time' => $hearing->hearing_time->toIso8601String(),
        ];
    })->values()->all();
?>

<?php $__env->startSection('scripts'); ?>
<script>
function openScheduleModal(modalId) {
    document.getElementById(modalId)?.classList.add('show');
}

function closeScheduleModal(modalId) {
    document.getElementById(modalId)?.classList.remove('show');
}

function openEventViewModal(eventItem) {
    document.getElementById('scheduleEventViewTitle').textContent = eventItem.title || '-';
    document.getElementById('scheduleEventViewStatus').textContent = eventItem.status || '-';
    document.getElementById('scheduleEventViewCase').textContent = eventItem.case_name || '-';
    document.getElementById('scheduleEventViewTime').textContent = new Date(eventItem.hearing_time).toLocaleString();
    document.getElementById('scheduleEventViewNotes').textContent = eventItem.notes || 'No notes added.';
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
    document.getElementById('scheduleEventEditForm').action = "<?php echo e(url('/schedule/events')); ?>/" + eventItem.id;
    document.getElementById('scheduleEventEditTitle').value = eventItem.title || '';
    document.getElementById('scheduleEventEditCase').value = eventItem.case_name || '';
    document.getElementById('scheduleEventEditTime').value = eventItem.hearing_time || '';
    document.getElementById('scheduleEventEditStatus').value = eventItem.status || 'Scheduled';
    document.getElementById('scheduleEventEditNotes').value = eventItem.notes || '';
    openScheduleModal('scheduleEventEditModal');
}

function openTimeViewModal(entry) {
    document.getElementById('scheduleTimeViewCase').textContent = entry.case_name || 'General';
    document.getElementById('scheduleTimeViewDate').textContent = entry.work_date || '-';
    document.getElementById('scheduleTimeViewHours').textContent = Number(entry.hours || 0).toFixed(2);
    document.getElementById('scheduleTimeViewBilling').textContent = entry.billable ? 'Billable' : 'Non-Billable';
    document.getElementById('scheduleTimeViewActivity').textContent = entry.activity || 'General Work';
    document.getElementById('scheduleTimeViewDescription').textContent = entry.description || 'No description added.';
    openScheduleModal('scheduleTimeViewModal');
}

function openTimeEditModal(entry) {
    document.getElementById('scheduleTimeEditForm').action = "<?php echo e(url('/schedule/time-entries')); ?>/" + entry.id;
    document.getElementById('scheduleTimeEditCase').value = entry.case_id || '';
    document.getElementById('scheduleTimeEditDate').value = entry.work_date || '';
    document.getElementById('scheduleTimeEditHours').value = entry.hours || '';
    document.getElementById('scheduleTimeEditActivity').value = entry.activity || 'General Work';
    document.getElementById('scheduleEditBillableYes').checked = !!entry.billable;
    document.getElementById('scheduleEditBillableNo').checked = !entry.billable;
    document.getElementById('scheduleTimeEditDescription').value = entry.description || '';
    activateScheduleTab('timePanel');
    openScheduleModal('scheduleTimeEditModal');
}

const scheduleEvents = <?php echo json_encode($scheduleEventsJson, 15, 512) ?>;

let currentScheduleMonth = new Date(<?php echo json_encode($selectedMonth->copy()->startOfMonth()->format('Y-m-d'), 15, 512) ?> + 'T00:00:00');

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
                <button type="button" class="schedule-event-pill text-left border-0 w-100" onclick="openEventViewModalById('${escapeHtml(event.id)}')">
                    <strong>${escapeHtml(event.title || event.case_name)}</strong>
                    <span>${escapeHtml(eventTime.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }))}</span>
                </button>
            `;
        }).join('');

        const extraCount = dayEvents.length > 3
            ? `<small class="text-muted">+${dayEvents.length - 3} more</small>`
            : '';

        dayMarkup.push(`
            <div class="schedule-day ${isCurrentMonth ? '' : 'is-muted'}">
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

renderScheduleCalendar();

<?php if(request('tab') === 'time'): ?>
activateScheduleTab('timePanel');
<?php endif; ?>

<?php if(request('create') === 'event'): ?>
openScheduleModal('scheduleEventModal');
<?php endif; ?>

<?php if(request('create') === 'time'): ?>
activateScheduleTab('timePanel');
openScheduleModal('scheduleTimeModal');
<?php endif; ?>
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views/modules/schedule.blade.php ENDPATH**/ ?>