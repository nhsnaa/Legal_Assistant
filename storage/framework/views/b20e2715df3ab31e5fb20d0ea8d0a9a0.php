<?php $__env->startSection('title', $title); ?>

<?php $__env->startSection('content'); ?>
<div class="glass-card p-5">
    <div class="d-flex align-items-center mb-3">
        <div class="icon-circle bg-info text-white mr-3">
            <i class="<?php echo e($icon); ?>"></i>
        </div>
        <div>
            <h2 class="h4 font-weight-bold mb-1"><?php echo e($title); ?></h2>
            <p class="text-muted mb-0"><?php echo e($description); ?></p>
        </div>
    </div>

    <div class="mt-4">
        <p class="mb-4">This page is linked in the navigation and now loads safely, but the full feature screen has not been built yet.</p>
        <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
    <?php echo $__env->make('layouts.modals.logout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views\feature-placeholder.blade.php ENDPATH**/ ?>