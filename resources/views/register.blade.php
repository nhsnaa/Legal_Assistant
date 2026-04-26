<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | ILA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body>
    <div class="auth-shell container-fluid">
        <div class="row min-vh-100">
            {{-- Showcase Side --}}
            <div class="col-lg-6 d-none d-lg-flex auth-showcase">
                <div class="auth-showcase-inner">
                    <span class="auth-kicker"><i class="bi bi-stars"></i> Start Your Legal Workspace</span>
                    <h1>Create a clean and professional <span class="text-primary">practice dashboard</span></h1>
                    <p>Set up your account to manage clients, track cases, organize documents, and keep your legal schedule under control.</p>

                    <div class="showcase-panel">
                        <div class="showcase-row">
                            <div>
                                <strong>Client management</strong>
                                <span>Keep records and legal matters easy to access.</span>
                            </div>
                        </div>
                        <div class="showcase-row">
                            <div>
                                <strong>Case visibility</strong>
                                <span>Track status and deadlines without extra clutter.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Side --}}
            <div class="col-lg-6 d-flex justify-content-center align-items-center auth-form-column">
                <div class="register-container">
                    <div class="auth-brand text-center mb-4">
                        <h2 class="mb-2">Create <span class="text-primary">Account</span></h2>
                        <p class="text-muted mb-0">Register your access to the ILA workspace</p>
                    </div>

                    @if ($errors->any())
                        <div class="auth-alert mb-4">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST" id="regForm">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="firstName">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="lastName">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="userRole">User Role</label>
                            <select class="form-control" name="userRole" required>
                                <option value="" disabled selected>Select your role</option>
                                <option value="lawyer">Lawyer</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Password</label>
                                <div class="password-field">
                                    <input type="password" id="password" name="password" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Confirm</label>
                                <div class="password-field">
                                    <input type="password" id="confirmPassword" name="password_confirmation" class="form-control" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" onclick="this.disabled=true; this.form.submit();">
                            Create Account
                        </button>
                    </form>

                    <p class="text-center mt-4">
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
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    }
    </script>
</body>
</html>