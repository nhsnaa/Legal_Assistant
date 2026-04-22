@php
    $currentUser = Auth::user();
    $greetingText = $greeting ?? match (true) {
        now()->hour < 12 => 'Good Morning',
        now()->hour < 18 => 'Good Afternoon',
        default => 'Good Evening',
    };
    $displayName = $currentUser->first_name ?: ($currentUser->name ?? 'User');
    $displayRole = $currentUser->user_role ?: 'staff';
@endphp

<header class="topbar">
    <div class="greeting-text">
        <h5>{{ $greetingText }}, {{ $displayName }}!</h5>
        <small>{{ ucfirst($displayRole) }} Dashboard</small>
    </div>

    <div class="topbar-center d-flex align-items-center">
        <div class="timer-wrapper mr-3">
            <div class="timer-display" id="display">00:00:00</div>
        </div>
        <div class="timer-controls">
            <button class="btn btn-sm btn-success px-3 mr-2" id="startStopBtn" onclick="toggleTimer()">
                <i class="bi bi-play-fill mr-1"></i>Start
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="resetTimer()">Reset</button>
        </div>
    </div>

    <div class="topbar-right d-flex align-items-center">
        <div class="search-box mr-4 d-none d-md-block">
            <input type="text" class="form-control-pill" placeholder="Search cases, clients, documents...">
        </div>

        <div class="dropdown">
            <div class="user-avatar-circle" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
                {{ strtoupper(substr($displayName, 0, 1)) }}
            </div>

            <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 p-0" aria-labelledby="userDropdown" style="min-width: 280px; border-radius: 15px; overflow: hidden; margin-top: 10px;">
                <a href="{{ route('settings') }}" class="p-3 d-flex align-items-center bg-light user-profile-link text-decoration-none">
                    <div class="user-avatar-circle mr-3" style="width: 45px; height: 45px; font-size: 1rem;">
                        {{ strtoupper(substr($displayName, 0, 1)) }}
                    </div>

                    <div>
                        <h6 class="mb-0 font-weight-bold" style="font-size: 0.9rem;">{{ $displayName }}</h6>
                        <p class="mb-0 text-muted small">
                            {{ $currentUser->email }}
                        </p>
                    </div>
                </a>

                <div class="py-2">
                    <div class="dropdown-item d-flex align-items-center py-2 px-3">
                        <i class="bi bi-globe mr-3 text-muted"></i>
                        Language
                        <div class="ml-auto">
                            <a href="?lang=en" class="badge badge-light border-0 {{ app()->getLocale() == 'en' ? 'badge-primary' : '' }}">EN</a>
                            <a href="?lang=ms" class="badge badge-light border-0 {{ app()->getLocale() == 'ms' ? 'badge-primary' : '' }}">BM</a>
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="{{ route('settings') }}">
                        <i class="bi bi-gear mr-3 text-muted"></i> Settings
                    </a>

                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="#" data-toggle="modal" data-target="#changePasswordModal">
                        <i class="bi bi-shield-lock mr-3 text-muted"></i> Change Password
                    </a>

                    <div class="dropdown-divider"></div>

                    <a class="dropdown-item d-flex align-items-center py-2 px-3 text-danger" href="#" data-toggle="modal" data-target="#logoutModal">
                        <i class="bi bi-power mr-3"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

@include('layouts.modals.change-password')

<script>
function toggleVisibility(inputId, iconId) {
    var passwordInput = document.getElementById(inputId);
    var iconElement = document.getElementById(iconId);

    if (passwordInput && iconElement) {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            iconElement.classList.remove('bi-eye-slash');
            iconElement.classList.add('bi-eye');
        } else {
            passwordInput.type = "password";
            iconElement.classList.remove('bi-eye');
            iconElement.classList.add('bi-eye-slash');
        }
    }
}

(function () {
    const STORAGE_KEY = 'ilaActiveTimer';
    const display = document.getElementById('display');
    const startStopBtn = document.getElementById('startStopBtn');
    let elapsedTime = 0;
    let startTime = null;
    let timerInterval = null;

    function formatTime(milliseconds) {
        const totalSeconds = Math.floor(milliseconds / 1000);
        const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const seconds = String(totalSeconds % 60).padStart(2, '0');

        return hours + ':' + minutes + ':' + seconds;
    }

    function updateDisplay() {
        if (!display) {
            return;
        }

        const currentElapsed = startTime ? Date.now() - startTime : elapsedTime;
        display.textContent = formatTime(currentElapsed);
    }

    function updateButton(isRunning) {
        if (!startStopBtn) {
            return;
        }

        startStopBtn.innerHTML = isRunning
            ? '<i class="bi bi-pause-fill mr-1"></i>Pause'
            : '<i class="bi bi-play-fill mr-1"></i>Start';

        startStopBtn.classList.toggle('btn-success', !isRunning);
        startStopBtn.classList.toggle('btn-warning', isRunning);
    }

    function persistState(isRunning) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            elapsedTime,
            startTime,
            isRunning
        }));
    }

    function stopInterval() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function startInterval() {
        stopInterval();
        timerInterval = setInterval(updateDisplay, 1000);
    }

    function loadState() {
        try {
            const savedState = JSON.parse(localStorage.getItem(STORAGE_KEY));

            if (!savedState) {
                updateDisplay();
                updateButton(false);
                return;
            }

            elapsedTime = Number(savedState.elapsedTime) || 0;
            startTime = savedState.isRunning && savedState.startTime ? Number(savedState.startTime) : null;

            if (startTime) {
                startInterval();
            }

            updateDisplay();
            updateButton(Boolean(startTime));
        } catch (error) {
            elapsedTime = 0;
            startTime = null;
            updateDisplay();
            updateButton(false);
        }
    }

    window.toggleTimer = function toggleTimer() {
        if (startTime) {
            const stoppedAt = Date.now();
            elapsedTime = stoppedAt - startTime;
            startTime = null;
            stopInterval();
            updateDisplay();
            updateButton(false);
            persistState(false);
            window.dispatchEvent(new CustomEvent('ila:timer-stopped', {
                detail: {
                    elapsedTime: elapsedTime,
                    stoppedAt: stoppedAt
                }
            }));
            return;
        }

        startTime = Date.now() - elapsedTime;
        startInterval();
        updateDisplay();
        updateButton(true);
        persistState(true);
        window.dispatchEvent(new CustomEvent('ila:timer-started', {
            detail: {
                elapsedTime: elapsedTime
            }
        }));
    };

    window.resetTimer = function resetTimer() {
        elapsedTime = 0;
        startTime = null;
        stopInterval();
        updateDisplay();
        updateButton(false);
        persistState(false);
        window.dispatchEvent(new CustomEvent('ila:timer-reset'));
    };

    window.addEventListener('storage', function (event) {
        if (event.key === STORAGE_KEY) {
            stopInterval();
            loadState();
        }
    });

    loadState();
})();
</script>

