<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | ILA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    @php
        $registerCssPath = public_path('css/register.css');
        $registerCss = file_exists($registerCssPath) ? file_get_contents($registerCssPath) : '';
    @endphp
    @if ($registerCss)
        <style>{!! $registerCss !!}</style>
    @endif
</head>
<body>

    <div class="auth-shell container-fluid">
        <div class="row min-vh-100">
            <div class="col-lg-6 d-none d-lg-flex auth-showcase">
                <div class="auth-showcase-inner">
                    <span class="auth-kicker"><i class="bi bi-stars"></i> Start Your Legal Workspace</span>
                    <h1>Create a clean and professional <span class="text-primary">practice dashboard</span></h1>
                    <p>Set up your account to manage clients, track cases, organize documents, and keep your legal schedule under control from one central workspace.</p>

                    <div class="showcase-panel">
                        <div class="showcase-row">
                            <div>
                                <strong>Client management</strong>
                                <span>Keep records, communication details, and legal matters easy to access.</span>
                            </div>
                        </div>
                        <div class="showcase-row">
                            <div>
                                <strong>Case visibility</strong>
                                <span>Track status, deadlines, and related materials without extra clutter.</span>
                            </div>
                        </div>
                        <div class="showcase-row">
                            <div>
                                <strong>Time and schedule support</strong>
                                <span>Stay on top of hearings, tasks, and billable work more confidently.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 d-flex justify-content-center align-items-center auth-form-column">
                <div class="register-container">
                    <div class="auth-brand text-center mb-4">
                        <h2 class="mb-2">Create <span class="text-primary">Account</span></h2>
                        <p class="text-muted mb-0">Register your access to the ILA legal workspace</p>
                    </div>

                    @if ($errors->any())
                        <div class="auth-alert mb-4">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="auth-success mb-4">
                            <i class="bi bi-check-circle"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="firstName">First Name</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="firstName" name="first_name" placeholder="Jane" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="lastName">Last Name</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="lastName" name="last_name" placeholder="Doe" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="name@firm.com" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="userRole">User Role</label>
                            <select class="form-control @error('userRole') is-invalid @enderror" id="userRole" name="userRole" required>
                                <option value="" disabled {{ old('userRole') ?: 'selected' }}>Select your role</option>
                                <option value="lawyer" {{ old('userRole') == 'lawyer' ? 'selected' : '' }}>Lawyer</option>
                                <option value="staff" {{ old('userRole') == 'staff' ? 'selected' : '' }}>Staff</option>
                            </select>
                            @error('userRole')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">Password</label>
                                <div class="password-field">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Create password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="confirmPassword">Confirm Password</label>
                                <div class="password-field">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="confirmPassword" name="password_confirmation" placeholder="Repeat password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="password-hint">
                            Use a strong password with a mix of letters, numbers, and symbols.
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        Already have an account? <a href="{{ route('login') }}" class="text-primary font-weight-bold">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    </script>
</body>
</html>
