<?php $__env->startSection('title', 'Edit Document'); ?>

<?php $__env->startSection('content'); ?>
<div class="module-shell">
    <?php if($errors->any()): ?>
        <div class="module-alert module-alert-danger">
            <span><?php echo e($errors->first()); ?></span>
            <button type="button" onclick="this.parentElement.style.display='none'">&times;</button>
        </div>
    <?php endif; ?>

    <div class="module-toolbar">
        <div>
            <h1 class="module-title mb-2">Edit Document</h1>
            <p class="module-copy mb-0">Update the display name while keeping the original uploaded file intact.</p>
        </div>
        <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-light px-4 py-2">Back to Documents</a>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="glass-card p-4 h-100">
                <form action="<?php echo e(route('documents.update', $document->id)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="form-group">
                        <label class="font-weight-bold small">Document Name</label>
                        <input type="text" name="name" class="form-control module-input" value="<?php echo e(old('name', $document->name)); ?>" required>
                    </div>

                    <div class="module-document-form-grid mb-3">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Document Category</label>
                            <select name="category" class="form-control module-input" required>
                                <option value="Court Filing" <?php echo e(old('category', $document->category ?? '') === 'Court Filing' ? 'selected' : ''); ?>>Court Filing</option>
                                <option value="Client Correspondence" <?php echo e(old('category', $document->category ?? '') === 'Client Correspondence' ? 'selected' : ''); ?>>Client Correspondence</option>
                                <option value="Evidence" <?php echo e(old('category', $document->category ?? '') === 'Evidence' ? 'selected' : ''); ?>>Evidence</option>
                                <option value="Contract" <?php echo e(old('category', $document->category ?? '') === 'Contract' ? 'selected' : ''); ?>>Contract</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Document Belongs To</label>
                            <select name="document_scope" id="editDocumentScope" class="form-control module-input" required>
                                <option value="general" <?php echo e(old('document_scope', $document->scope ?? 'general') === 'general' ? 'selected' : ''); ?>>General / Personal Document</option>
                                <option value="case" <?php echo e(old('document_scope', $document->scope ?? 'general') === 'case' ? 'selected' : ''); ?>>Existing Case</option>
                                <option value="client" <?php echo e(old('document_scope', $document->scope ?? 'general') === 'client' ? 'selected' : ''); ?>>Existing Client</option>
                            </select>
                        </div>

                        <div class="form-group mb-0 d-none" id="editCaseSelectionGroup">
                            <label class="font-weight-bold small">Linked Case</label>
                            <select name="case_id" class="form-control module-input">
                                <option value="">Select case</option>
                                <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($caseOption->id); ?>" <?php echo e(old('case_id', $document->case_id ?? '') === $caseOption->id ? 'selected' : ''); ?>><?php echo e($caseOption->case_name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="form-group mb-0 d-none" id="editClientSelectionGroup">
                            <label class="font-weight-bold small">Linked Client</label>
                            <select name="client_id" class="form-control module-input">
                                <option value="">Select client</option>
                                <?php $__currentLoopData = $clientOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clientOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($clientOption->id); ?>" <?php echo e(old('client_id', $document->client_id ?? '') === $clientOption->id ? 'selected' : ''); ?>><?php echo e($clientOption->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>

                    <?php if(($document->type ?? 'file') === 'link'): ?>
                        <div class="form-group">
                            <label class="font-weight-bold small">Document URL</label>
                            <input type="url" name="link_url" class="form-control module-input" value="<?php echo e(old('link_url', $document->link_url ?? '')); ?>" required>
                        </div>
                    <?php endif; ?>

                    <div class="module-modal-actions">
                        <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="glass-card p-4 h-100">
                <h6 class="font-weight-bold mb-3">Document Details</h6>
                <div class="module-note-list">
                    <div class="module-note-item">
                        <strong><?php echo e(($document->type ?? 'file') === 'link' ? 'Source Label' : 'Original File Name'); ?></strong>
                        <p class="mb-0"><?php echo e($document->original_name); ?></p>
                    </div>
                    <div class="module-note-item">
                        <strong>Category</strong>
                        <p class="mb-0"><?php echo e($document->category ?? 'Uncategorized'); ?></p>
                    </div>
                    <div class="module-note-item">
                        <strong>Linked To</strong>
                        <p class="mb-0">
                            <?php if(($document->scope ?? 'general') === 'case'): ?>
                                Case: <?php echo e($document->case_name ?? 'Unknown Case'); ?>

                            <?php elseif(($document->scope ?? 'general') === 'client'): ?>
                                Client: <?php echo e($document->client_name ?? 'Unknown Client'); ?>

                            <?php else: ?>
                                General / Personal Document
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="module-note-item">
                        <strong><?php echo e(($document->type ?? 'file') === 'link' ? 'Document Type' : 'File Size'); ?></strong>
                        <p class="mb-0">
                            <?php if(($document->type ?? 'file') === 'link'): ?>
                                External Link
                            <?php else: ?>
                                <?php echo e(number_format(($document->size ?? 0) / 1024, 2)); ?> KB
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="module-note-item">
                        <strong><?php echo e(($document->type ?? 'file') === 'link' ? 'Open Link' : 'Download'); ?></strong>
                        <p class="mb-0"><a href="<?php echo e(route('documents.show', $document->id)); ?>" target="<?php echo e(($document->type ?? 'file') === 'link' ? '_blank' : '_self'); ?>"><?php echo e(($document->type ?? 'file') === 'link' ? 'Open current link' : 'Open current file'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('modals'); ?>
    <?php echo $__env->make('layouts.modals.logout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
const editDocumentScope = document.getElementById('editDocumentScope');
const editCaseSelectionGroup = document.getElementById('editCaseSelectionGroup');
const editClientSelectionGroup = document.getElementById('editClientSelectionGroup');

function syncEditDocumentScope() {
    const scope = editDocumentScope ? editDocumentScope.value : 'general';
    editCaseSelectionGroup?.classList.toggle('d-none', scope !== 'case');
    editClientSelectionGroup?.classList.toggle('d-none', scope !== 'client');
}

editDocumentScope?.addEventListener('change', syncEditDocumentScope);
syncEditDocumentScope();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views\modules\documents-edit.blade.php ENDPATH**/ ?>