@php
    $displayName = session('first_name')
        ?: session('user_name')
        ?: 'User';
    $greetingText = $greeting ?? match (true) {
        now()->hour < 12 => 'Good Morning',
        now()->hour < 18 => 'Good Afternoon',
        default => 'Good Evening',
    };
    $displayRole = session('user_role', 'staff');
    $displayEmail = session('email', 'No Email');
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
            <input type="text" class="form-control-pill" id="globalSearchInput" placeholder="Search cases, clients, documents..." autocomplete="off">
            <div class="search-results-dropdown d-none" id="globalSearchResults"></div>
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
                            {{ $displayEmail }}
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
    const globalSearchInput = document.getElementById('globalSearchInput');
    const globalSearchResults = document.getElementById('globalSearchResults');
    let elapsedTime = 0;
    let startTime = null;
    let timerInterval = null;
    let searchTimeoutId = null;

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

    function hideSearchResults() {
        if (!globalSearchResults) {
            return;
        }

        globalSearchResults.classList.add('d-none');
        globalSearchResults.innerHTML = '';
    }

    function renderSearchResults(items) {
        if (!globalSearchResults) {
            return;
        }

        if (!items.length) {
            globalSearchResults.innerHTML = '<div class="search-results-empty">No matching records found.</div>';
            globalSearchResults.classList.remove('d-none');
            return;
        }

        globalSearchResults.innerHTML = items.map(function (item) {
            return `
                <a href="${item.url}" class="search-result-item">
                    <span class="search-result-type">${item.type}</span>
                    <strong>${item.title}</strong>
                    <small>${item.meta || ''}</small>
                </a>
            `;
        }).join('');

        globalSearchResults.classList.remove('d-none');
    }

    globalSearchInput?.addEventListener('input', function () {
        const query = this.value.trim();

        clearTimeout(searchTimeoutId);

        if (query.length < 2) {
            hideSearchResults();
            return;
        }

        searchTimeoutId = setTimeout(function () {
            fetch("{{ route('search.index') }}?q=" + encodeURIComponent(query), {
                headers: {
                    'Accept': 'application/json',
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search failed');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    renderSearchResults(payload.results || []);
                })
                .catch(function () {
                    renderSearchResults([]);
                });
        }, 220);
    });

    document.addEventListener('click', function (event) {
        if (!globalSearchInput || !globalSearchResults) {
            return;
        }

        if (!globalSearchInput.closest('.search-box')?.contains(event.target)) {
            hideSearchResults();
        }
    });

    loadState();
})();
</script>
