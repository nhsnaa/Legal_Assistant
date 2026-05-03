<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title font-weight-normal text-dark" style="font-size: 1.5rem;">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body px-4 pb-4">
                <form action="<?php echo e(route('password.update')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group mb-4">
                        <label class="small text-muted mb-1">Email</label>
                        <input type="email" class="form-control bg-light border" name="account_email_display" value="<?php echo e(session('email', 'No Email')); ?>" readonly autocomplete="username" style="border-radius: 8px; padding: 12px;">
                    </div>

                    <div class="form-group mb-4 position-relative">
                        <input type="password" class="form-control border <?php $__errorArgs = ['new_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="new_password" id="new_password" placeholder="New Password" autocomplete="new-password" style="border-radius: 8px; padding: 12px; height: 50px; padding-right: 45px;" required>

                        <span class="position-absolute d-flex align-items-center justify-content-center"
                            style="right: 15px; top: 0; bottom: 0; cursor: pointer; color: #aaa; width: 30px;"
                            onclick="toggleVisibility('new_password', 'icon_new')">
                            <i id="icon_new" class="bi bi-eye-slash" style="font-size: 1.2rem;"></i>
                        </span>
                        <?php $__errorArgs = ['new_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group mb-4 position-relative">
                        <input type="password" class="form-control border <?php $__errorArgs = ['new_password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="new_password_confirmation" id="confirm_password" placeholder="Confirm Password" autocomplete="new-password" style="border-radius: 8px; padding: 12px; height: 50px; padding-right: 45px;" required>

                        <span class="position-absolute d-flex align-items-center justify-content-center"
                            style="right: 15px; top: 0; bottom: 0; cursor: pointer; color: #aaa; width: 30px;"
                            onclick="toggleVisibility('confirm_password', 'icon_confirm')">
                            <i id="icon_confirm" class="bi bi-eye-slash" style="font-size: 1.2rem;"></i>
                        </span>
                        <?php $__errorArgs = ['new_password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="d-flex justify-content-start">
                        <button type="button" class="btn btn-outline-secondary mr-3 px-4" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-light px-4 border" style="border-radius: 8px; background-color: #e9ecef; color: #495057;">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\ILA\resources\views/layouts/modals/change-password.blade.php ENDPATH**/ ?>