<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ILA SYSTEM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    @php
        $loginCssPath = public_path('css/login.css');
        $loginCss = file_exists($loginCssPath) ? file_get_contents($loginCssPath) : '';
    @endphp
    @if ($loginCss)
        <style>{!! $loginCss !!}</style>
    @endif
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

                    @if (session('success'))
                        <div class="auth-success mb-4">
                            <i class="bi bi-check-circle"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="auth-alert mb-4">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="name@firm.com" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="mb-2">Password</label>
                                <button type="button" class="helper-link small border-0 bg-transparent p-0" onclick="openForgotPasswordModal()">Forgot password?</button>
                            </div>
                            <div class="password-field">
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter your password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>

                        <div class="auth-footer text-center mt-4">
                            <p class="small text-muted mb-0">Don't have an account?
                                <a href="{{ route('register') }}" class="font-weight-bold text-primary">Create one</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-modal-backdrop" id="forgotPasswordModal" aria-hidden="true">
        <div class="auth-modal-card" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordModalLabel">
            <div class="modal-content border-0" style="border-radius: 22px; background: rgba(255,255,255,0.96); backdrop-filter: blur(14px);">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title font-weight-bold" id="forgotPasswordModalLabel">Request Password Reset</h5>
                        <p class="text-muted mb-0 small">Your workspace owner will receive the request and set a temporary password for you.</p>
                    </div>
                    <button type="button" class="close" onclick="closeForgotPasswordModal()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pt-3">
                    <form action="{{ route('password.request') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="forgot_email">Registered Email Address</label>
                            <input type="email" id="forgot_email" name="email" class="form-control" placeholder="name@firm.com" value="{{ old('email') }}" required>
                            <small class="form-text text-muted">This works for staff accounts inside a lawyer's workspace. Workspace owners still need manual help.</small>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light mr-2" onclick="closeForgotPasswordModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Request</button>
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

    function openForgotPasswordModal() {
        const modal = document.getElementById('forgotPasswordModal');

        if (!modal) {
            return;
        }

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('auth-modal-open');
    }

    function closeForgotPasswordModal() {
        const modal = document.getElementById('forgotPasswordModal');

        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('auth-modal-open');
    }

    document.getElementById('forgotPasswordModal')?.addEventListener('click', function (event) {
        if (event.target === this) {
            closeForgotPasswordModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeForgotPasswordModal();
        }
    });

    </script>
</body>
</html>
