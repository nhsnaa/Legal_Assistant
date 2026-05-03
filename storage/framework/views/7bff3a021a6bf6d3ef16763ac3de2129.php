<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ILA SYSTEM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/login.css')); ?>">
</head>
<body>

    <div class="auth-shell container-fluid">
        <div class="row min-vh-100">
            <div class="col-lg-6 d-none d-lg-flex auth-showcase">
                <div class="auth-showcase-inner">
                    <span class="auth-kicker"><i class="bi bi-shield-check"></i> Secure Legal Access</span>
                    <h1>Welcome back to your <span class="text-primary">legal workspace</span></h1>
                    <p>Access your dashboard to review matters, update client records, manage hearing schedules, and keep your daily legal work organized.</p>

                    <div class="showcase-panel">
                        <div class="showcase-row">
                            <div>
                                <strong>Confidential records</strong>
                                <span>Case notes and legal documents stay organized in one place.</span>
                            </div>
                        </div>
                        <div class="showcase-row">
                            <div>
                                <strong>Daily visibility</strong>
                                <span>See active matters, upcoming hearings, and billable work quickly.</span>
                            </div>
                        </div>
                        <div class="showcase-row">
                            <div>
                                <strong>Professional workflow</strong>
                                <span>Designed to support a calm and credible solo legal practice.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 d-flex justify-content-center align-items-center auth-form-column">
                <div class="login-container">
                    <div class="auth-brand text-center mb-4">
                        <h4 class="font-weight-bold mb-2">ILA <span class="text-primary">SYSTEM</span></h4>
                        <p class="text-muted mb-0">Sign in to access your legal office dashboard</p>
                    </div>

                    <?php if(session('success')): ?>
                        <div class="auth-success mb-4">
                            <i class="bi bi-check-circle"></i>
                            <span><?php echo e(session('success')); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if($errors->any()): ?>
                        <div class="auth-alert mb-4">
                            <i class="bi bi-exclamation-circle"></i>
                            <span><?php echo e($errors->first()); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo e(route('login')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="name@firm.com" value="<?php echo e(old('email')); ?>" required>
                            <?php $__errorArgs = ['email'];
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

                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="mb-2">Password</label>
                                <button type="button" class="helper-link small border-0 bg-transparent p-0" onclick="showPasswordHelp()">Forgot password?</button>
                            </div>
                            <div class="password-field">
                                <input type="password" id="password" name="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Enter your password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php $__errorArgs = ['password'];
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

                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>

                        <div class="auth-footer text-center mt-4">
                            <p class="small text-muted mb-0">Don't have an account?
                                <a href="<?php echo e(route('register')); ?>" class="font-weight-bold text-primary">Create one</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');

        if (!input || !icon) {
            return;
        }

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    function showPasswordHelp() {
        alert('Password reset is not available yet. Please contact the system administrator or create a new account if appropriate.');
    }
    </script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\ILA\resources\views/login.blade.php ENDPATH**/ ?>