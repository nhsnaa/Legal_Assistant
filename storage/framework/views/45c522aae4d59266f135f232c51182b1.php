<?php $__env->startSection('title', 'Client Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="module-shell">
    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Client Record</span>
            <h1 class="module-title"><?php echo e($client->name); ?></h1>
            <p class="module-copy mb-0">Review the saved contact details and linked case activity for this client.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-person-lines-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Client Information</h5>
            <p class="text-muted mb-0">A simple view for checking saved client details during a walkthrough or review.</p>
        </div>
        <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-light px-4 py-2">Back to Clients</a>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="module-detail-grid">
                    <div class="module-detail-item">
                        <label>Client ID</label>
                        <strong>#<?php echo e($client->id); ?></strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Status</label>
                        <strong><?php echo e($client->status); ?></strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Full Name</label>
                        <strong><?php echo e($client->name); ?></strong>
                    </div>
                    <div class="module-detail-item">
                        <label>Email Address</label>
                        <strong><?php echo e($client->email); ?></strong>
                    </div>
                    <div class="module-detail-item full">
                        <label>Phone Number</label>
                        <strong><?php echo e($client->phone); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-4">
            <div class="glass-card p-4 h-100">
                <h6 class="font-weight-bold mb-3">Linked Cases</h6>
                <div class="module-note-list">
                    <div class="module-note-item">
                        <strong>Total Linked Cases</strong>
                        <p class="mb-0"><?php echo e($linkedCases->count()); ?> case record(s) linked to this client.</p>
                    </div>
                    <?php $__empty_1 = true; $__currentLoopData = $linkedCases->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="module-note-item">
                            <strong><?php echo e($case->case_name ?? 'Untitled Case'); ?></strong>
                            <p class="mb-0"><?php echo e($case->status ?? 'Active'); ?> | <?php echo e($case->priority ?? 'Medium'); ?> Priority</p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="module-note-item">
                            <strong>No linked cases yet</strong>
                            <p class="mb-0">This client has not been assigned to any saved case.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
    <?php echo $__env->make('layouts.modals.logout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views\modules\client-show.blade.php ENDPATH**/ ?>