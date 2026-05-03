<?php $__env->startSection('title', 'Documents'); ?>

<?php $__env->startSection('content'); ?>
<div class="module-shell">
    <?php if(session('success')): ?>
        <div class="module-alert module-alert-success">
            <span><?php echo e(session('success')); ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <div class="module-hero glass-card">
        <div>
            <span class="module-kicker">Records Vault</span>
            <h1 class="module-title">Documents</h1>
            <p class="module-copy mb-0">Store, review, rename, and retrieve legal files in one document-focused workspace.</p>
        </div>
        <div class="module-hero-icon">
            <i class="bi bi-folder-fill"></i>
        </div>
    </div>

    <div class="module-toolbar">
        <div>
            <h5 class="font-weight-bold mb-1">Document Library</h5>
            <p class="text-muted mb-0">Manage uploaded legal files, rename them when needed, and download records quickly.</p>
        </div>
        <a href="<?php echo e(route('documents.create')); ?>" class="btn btn-primary px-4 py-2">
            <i class="bi bi-upload mr-2"></i>Upload New
        </a>
    </div>

    <div class="glass-card module-table-card">
        <?php if($documents->count() > 0): ?>
            <div class="table-responsive">
                <table class="table module-table mb-0">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Category</th>
                            <th>Linked To</th>
                            <th>Type</th>
                            <th>Original Name</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="font-weight-bold"><?php echo e($document->name); ?></td>
                                <td><?php echo e($document->category ?? 'Uncategorized'); ?></td>
                                <td>
                                    <?php if(($document->scope ?? 'general') === 'case'): ?>
                                        <span class="module-badge module-badge-info">Case</span>
                                        <div class="small text-muted mt-1"><?php echo e($document->case_name ?? 'Unknown Case'); ?></div>
                                    <?php elseif(($document->scope ?? 'general') === 'client'): ?>
                                        <span class="module-badge module-badge-muted">Client</span>
                                        <div class="small text-muted mt-1"><?php echo e($document->client_name ?? 'Unknown Client'); ?></div>
                                    <?php else: ?>
                                        <span class="small text-muted">General / Personal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="module-badge <?php echo e(($document->type ?? 'file') === 'link' ? 'module-badge-info' : 'module-badge-muted'); ?>">
                                        <?php echo e(($document->type ?? 'file') === 'link' ? 'Link' : 'File'); ?>

                                    </span>
                                </td>
                                <td><?php echo e($document->original_name); ?></td>
                                <td>
                                    <?php if(($document->type ?? 'file') === 'link'): ?>
                                        --
                                    <?php else: ?>
                                        <?php echo e(number_format(($document->size ?? 0) / 1024, 2)); ?> KB
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($document->created_at ? $document->created_at->format('M d, Y') : 'N/A'); ?></td>
                                <td>
                                    <div class="module-actions-inline">
                                        <a href="<?php echo e(route('documents.show', $document->id)); ?>" class="btn btn-sm btn-light" target="<?php echo e(($document->type ?? 'file') === 'link' ? '_blank' : '_self'); ?>">
                                            <?php echo e(($document->type ?? 'file') === 'link' ? 'Open Link' : 'Download'); ?>

                                        </a>
                                        <a href="<?php echo e(route('documents.edit', $document->id)); ?>" class="btn btn-sm btn-light">Edit</a>
                                        <form action="<?php echo e(route('documents.destroy', $document->id)); ?>" method="POST" onsubmit="return confirm('Delete this document?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="module-empty-state">
                <div class="module-empty-icon"><i class="bi bi-folder2-open"></i></div>
                <h5 class="font-weight-bold">No documents yet</h5>
                <p class="text-muted mb-3">Upload your first legal document or save a working link to start building the file library.</p>
                <a href="<?php echo e(route('documents.create')); ?>" class="btn btn-primary px-4">Add Document</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
    <?php echo $__env->make('layouts.modals.logout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views\modules\documents.blade.php ENDPATH**/ ?>