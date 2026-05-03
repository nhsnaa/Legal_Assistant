<?php $__env->startSection('title', 'Cases'); ?>

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
            <span class="module-kicker">Matter Tracker</span>
            <h1 class="module-title">Legal Portfolio</h1>
            <p class="module-copy mb-0">Track active matters, priority, progress, and summaries in one case-focused workspace.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-briefcase-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Case Workspace</h5>
            <p class="text-muted mb-0">Track and manage your active and closed legal cases in a single workspace.</p>
        </div>
        <button type="button" class="btn btn-primary px-4 py-2" onclick="openCaseModal()">
            <i class="bi bi-plus-lg mr-2"></i>Open New Case
        </button>
    </div>

    <div class="module-filter-tabs mb-4">
        <a href="<?php echo e(route('cases.index', ['filter' => 'all'])); ?>" class="module-filter-tab <?php echo e($filter === 'all' ? 'active' : ''); ?>">
            All <span><?php echo e($caseCounts['all']); ?></span>
        </a>
        <a href="<?php echo e(route('cases.index', ['filter' => 'active'])); ?>" class="module-filter-tab <?php echo e($filter === 'active' ? 'active' : ''); ?>">
            Active <span><?php echo e($caseCounts['active']); ?></span>
        </a>
        <a href="<?php echo e(route('cases.index', ['filter' => 'archived'])); ?>" class="module-filter-tab <?php echo e($filter === 'archived' ? 'active' : ''); ?>">
            Archive <span><?php echo e($caseCounts['archived']); ?></span>
        </a>
    </div>

    <div class="module-case-grid">
        <?php $__empty_1 = true; $__currentLoopData = $cases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="glass-card module-case-card">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="font-weight-bold mb-1"><?php echo e($case->case_name); ?></h5>
                            <small class="module-id">REF-ID: <?php echo e($case->id); ?></small>
                        </div>
                    </div>

                    <div class="module-case-tags">
                        <span class="module-badge <?php echo e($case->status === 'Active' ? 'module-badge-success' : ($case->status === 'Pending' ? 'module-badge-warning' : 'module-badge-muted')); ?>">
                            <?php echo e($case->status); ?>

                        </span>
                        <span class="module-badge <?php echo e($case->priority === 'High' ? 'module-badge-danger' : ($case->priority === 'Medium' ? 'module-badge-info' : 'module-badge-warning')); ?>">
                            <?php echo e($case->priority); ?> Priority
                        </span>
                    </div>

                    <div class="module-case-meta mb-3">
                        <span><strong>Client:</strong> <?php echo e($case->client->name ?? 'Unassigned client'); ?></span>
                    </div>

                    <div class="module-progress-wrap">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted font-weight-bold">Completion</span>
                            <span class="small text-primary font-weight-bold"><?php echo e($case->progress ?? 0); ?>%</span>
                        </div>
                        <div class="progress module-progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo e($case->progress ?? 0); ?>%"></div>
                        </div>
                    </div>

                    <p class="module-case-description mb-0">
                        <?php echo e($case->description ?: 'No case summary provided.'); ?>

                    </p>
                </div>

                <div class="module-actions-inline mt-4">
                    <a href="<?php echo e(route('cases.show', $case->id)); ?>" class="btn btn-sm btn-light">View Details</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-light"
                        onclick="openCaseEditModal(<?php echo \Illuminate\Support\Js::from([
                            'id' => $case->id,
                            'client_id' => $case->client_id,
                            'case_name' => $case->case_name,
                            'description' => $case->description,
                            'status' => $case->status,
                            'priority' => $case->priority,
                            'progress' => $case->progress ?? 0,
                        ])->toHtml() ?>)">
                        Edit
                    </button>
                    <form action="<?php echo e(route('cases.destroy', $case->id)); ?>" method="POST" onsubmit="return confirm('Delete this case?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="glass-card module-empty-state">
                <div class="module-empty-icon"><i class="bi bi-briefcase"></i></div>
                <h5 class="font-weight-bold">No cases found</h5>
                <p class="text-muted mb-0">You haven't added any legal cases to the system yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="caseModal" class="module-modal-backdrop" aria-hidden="true">
    <div class="module-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold mb-0">Open New Case</h4>
            <button type="button" class="module-modal-close" onclick="closeCaseModal()">&times;</button>
        </div>

        <form action="<?php echo e(route('cases.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="font-weight-bold small">Assign Client</label>
                <select name="client_id" class="form-control module-input" required>
                    <option value="">Select existing client</option>
                    <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($client->id); ?>" <?php echo e((string) old('client_id') === (string) $client->id ? 'selected' : ''); ?>>
                            <?php echo e($client->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php if($clients->isEmpty()): ?>
                    <small class="text-muted d-block mt-2">Add a client first before opening a new case.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Case Title</label>
                <input type="text" name="case_name" class="form-control module-input" required value="<?php echo e(old('case_name')); ?>">
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Detailed Description</label>
                <textarea name="description" rows="3" class="form-control module-input"><?php echo e(old('description')); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" class="form-control module-input">
                        <option value="Active" <?php echo e(old('status') === 'Active' ? 'selected' : ''); ?>>Active</option>
                        <option value="Pending" <?php echo e(old('status') === 'Pending' ? 'selected' : ''); ?>>Pending</option>
                        <option value="Closed" <?php echo e(old('status') === 'Closed' ? 'selected' : ''); ?>>Closed</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Priority</label>
                    <select name="priority" class="form-control module-input">
                        <option value="High" <?php echo e(old('priority') === 'High' ? 'selected' : ''); ?>>High</option>
                        <option value="Medium" <?php echo e(old('priority', 'Medium') === 'Medium' ? 'selected' : ''); ?>>Medium</option>
                        <option value="Low" <?php echo e(old('priority') === 'Low' ? 'selected' : ''); ?>>Low</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Initial Progress (%)</label>
                <input type="number" name="progress" min="0" max="100" class="form-control module-input" value="<?php echo e(old('progress', 0)); ?>">
            </div>

            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeCaseModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Create Case</button>
            </div>
        </form>
    </div>
</div>

<div id="caseEditModal" class="module-modal-backdrop" aria-hidden="true">
    <div class="module-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold mb-0">Edit Case</h4>
            <button type="button" class="module-modal-close" onclick="closeCaseEditModal()">&times;</button>
        </div>

        <form id="caseEditForm" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="form-group">
                <label class="font-weight-bold small">Assign Client</label>
                <select name="client_id" id="caseEditClient" class="form-control module-input" required>
                    <option value="">Select existing client</option>
                    <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($client->id); ?>"><?php echo e($client->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Case Title</label>
                <input type="text" name="case_name" id="caseEditName" class="form-control module-input" required>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Detailed Description</label>
                <textarea name="description" id="caseEditDescription" rows="3" class="form-control module-input"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Status</label>
                    <select name="status" id="caseEditStatus" class="form-control module-input">
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold small">Priority</label>
                    <select name="priority" id="caseEditPriority" class="form-control module-input">
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="font-weight-bold small">Progress (%)</label>
                <input type="number" name="progress" min="0" max="100" id="caseEditProgress" class="form-control module-input">
            </div>

            <div class="module-modal-actions">
                <button type="button" class="btn btn-light px-4" onclick="closeCaseEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Update Case</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
    <?php echo $__env->make('layouts.modals.logout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function openCaseModal() {
    document.getElementById('caseModal').classList.add('show');
}

function closeCaseModal() {
    document.getElementById('caseModal').classList.remove('show');
}

function openCaseEditModal(caseItem) {
    const form = document.getElementById('caseEditForm');
    form.action = "<?php echo e(url('/cases')); ?>/" + caseItem.id;
    document.getElementById('caseEditClient').value = caseItem.client_id || '';
    document.getElementById('caseEditName').value = caseItem.case_name || '';
    document.getElementById('caseEditDescription').value = caseItem.description || '';
    document.getElementById('caseEditStatus').value = caseItem.status || 'Active';
    document.getElementById('caseEditPriority').value = caseItem.priority || 'Medium';
    document.getElementById('caseEditProgress').value = caseItem.progress || 0;
    document.getElementById('caseEditModal').classList.add('show');
}

function closeCaseEditModal() {
    document.getElementById('caseEditModal').classList.remove('show');
}

<?php if($errors->any()): ?>
openCaseModal();
<?php elseif(request()->boolean('create')): ?>
openCaseModal();
<?php endif; ?>
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views\modules\cases.blade.php ENDPATH**/ ?>