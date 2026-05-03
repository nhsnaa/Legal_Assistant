<?php $__env->startSection('title', 'Upload Documents'); ?>

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
            <h1 class="module-title mb-2">Add Document</h1>
            <p class="module-copy mb-0">Upload a file or save a Google Drive or external link for faster access to legal materials.</p>
        </div>
        <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-light px-4 py-2">Back to Documents</a>
    </div>

    <div class="glass-card p-4">
        <form action="<?php echo e(route('documents.store')); ?>" method="POST" enctype="multipart/form-data" id="documentUploadForm">
            <?php echo csrf_field(); ?>

            <div class="row mb-4 align-items-end">
                <div class="col-lg-6">
                    <label class="font-weight-bold small">Document Category</label>
                    <select name="document_category" class="form-control module-input" required>
                        <option value="Court Filing" <?php echo e(old('document_category') === 'Court Filing' ? 'selected' : ''); ?>>Court Filing</option>
                        <option value="Client Correspondence" <?php echo e(old('document_category') === 'Client Correspondence' ? 'selected' : ''); ?>>Client Correspondence</option>
                        <option value="Evidence" <?php echo e(old('document_category') === 'Evidence' ? 'selected' : ''); ?>>Evidence</option>
                        <option value="Contract" <?php echo e(old('document_category') === 'Contract' ? 'selected' : ''); ?>>Contract</option>
                    </select>
                </div>
                <div class="col-lg-6 mt-3 mt-lg-0">
                    <label class="font-weight-bold small">Document Belongs To</label>
                    <select name="document_scope" id="documentScope" class="form-control module-input" required>
                        <option value="general" <?php echo e(old('document_scope', 'general') === 'general' ? 'selected' : ''); ?>>General / Personal Document</option>
                        <option value="case" <?php echo e(old('document_scope') === 'case' ? 'selected' : ''); ?>>Existing Case</option>
                        <option value="client" <?php echo e(old('document_scope') === 'client' ? 'selected' : ''); ?>>Existing Client</option>
                    </select>
                </div>
            </div>

            <div class="row mb-4 align-items-end">
                <div class="col-lg-6 d-none" id="caseSelectionGroup">
                    <label class="font-weight-bold small">Linked Case</label>
                    <select name="case_id" class="form-control module-input">
                        <option value="">Select case</option>
                        <?php $__currentLoopData = $caseOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($caseOption->id); ?>" <?php echo e(old('case_id') === $caseOption->id ? 'selected' : ''); ?>><?php echo e($caseOption->case_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-lg-6 d-none" id="clientSelectionGroup">
                    <label class="font-weight-bold small">Linked Client</label>
                    <select name="client_id" class="form-control module-input">
                        <option value="">Select client</option>
                        <?php $__currentLoopData = $clientOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clientOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($clientOption->id); ?>" <?php echo e(old('client_id') === $clientOption->id ? 'selected' : ''); ?>><?php echo e($clientOption->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <small class="text-muted d-block">When a case or client is selected, the saved document name will automatically become more descriptive.</small>
                </div>
            </div>

            <div class="module-upload-dropzone" id="documentDropzone">
                <div class="module-empty-icon mb-3"><i class="bi bi-cloud-arrow-up"></i></div>
                <h5 class="font-weight-bold">Upload files</h5>
                <p class="text-muted mb-3">Choose files or drag and drop multiple documents here.</p>
                <label class="btn btn-primary px-4 mb-0">
                    Select Files
                    <?php
                        $allowedExtensions = collect($documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'])
                            ->map(fn ($type) => '.' . strtolower($type))
                            ->values()
                            ->all();
                    ?>
                    <input id="documentsInput" name="documents[]" type="file" multiple accept="<?php echo e(implode(',', $allowedExtensions)); ?>" hidden>
                </label>
                <small class="d-block text-muted mt-3">Allowed: <?php echo e(implode(', ', $documentRules['allowed_file_types'] ?? ['PDF', 'DOCX'])); ?> | Max <?php echo e($documentRules['max_upload_size'] ?? 25); ?> MB</small>
            </div>

            <div id="documentFileInfo" class="module-file-list-wrap d-none">
                <h6 class="font-weight-bold mb-3">Selected Files</h6>
                <div id="documentFileList" class="module-file-list"></div>
            </div>

            <div class="module-link-divider">
                <span>or save a document link</span>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-3 mb-lg-0">
                    <label class="font-weight-bold small">Link Title</label>
                    <input type="text" name="link_name" class="form-control module-input" value="<?php echo e(old('link_name')); ?>" placeholder="Example: Draft Agreement in Google Drive">
                </div>
                <div class="col-lg-7">
                    <label class="font-weight-bold small">Document URL</label>
                    <input type="url" name="link_url" class="form-control module-input" value="<?php echo e(old('link_url')); ?>" placeholder="https://drive.google.com/...">
                    <small class="text-muted d-block mt-2">Useful when the legal team wants to keep editing the original document in Google Drive.</small>
                </div>
            </div>

            <div class="module-modal-actions">
                <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-light px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4" id="documentSubmitBtn">Save Document Record</button>
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
const uploadInput = document.getElementById('documentsInput');
const dropzone = document.getElementById('documentDropzone');
const fileInfo = document.getElementById('documentFileInfo');
const fileList = document.getElementById('documentFileList');
const documentScope = document.getElementById('documentScope');
const caseSelectionGroup = document.getElementById('caseSelectionGroup');
const clientSelectionGroup = document.getElementById('clientSelectionGroup');

function syncDocumentScope() {
    const scope = documentScope ? documentScope.value : 'general';
    caseSelectionGroup?.classList.toggle('d-none', scope !== 'case');
    clientSelectionGroup?.classList.toggle('d-none', scope !== 'client');
}

function renderSelectedFiles(files) {
    if (!files.length) {
        fileInfo.classList.add('d-none');
        fileList.innerHTML = '';
        return;
    }

    fileInfo.classList.remove('d-none');
    fileList.innerHTML = Array.from(files).map(function(file) {
        return '<div class="module-file-item"><span>' + file.name + '</span><small>' + (file.size / 1024).toFixed(2) + ' KB</small></div>';
    }).join('');
}

uploadInput.addEventListener('change', function () {
    renderSelectedFiles(this.files);
});

['dragenter', 'dragover'].forEach(function (eventName) {
    dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.add('is-dragover');
    });
});

['dragleave', 'drop'].forEach(function (eventName) {
    dropzone.addEventListener(eventName, function (event) {
        event.preventDefault();
        dropzone.classList.remove('is-dragover');
    });
});

dropzone.addEventListener('drop', function (event) {
    if (!event.dataTransfer.files.length) {
        return;
    }

    uploadInput.files = event.dataTransfer.files;
    renderSelectedFiles(uploadInput.files);
});

documentScope?.addEventListener('change', syncDocumentScope);
syncDocumentScope();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ILA\resources\views\modules\documents-upload.blade.php ENDPATH**/ ?>