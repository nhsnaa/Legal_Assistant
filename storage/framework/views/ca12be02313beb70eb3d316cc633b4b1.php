<?php
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
    $showWorkspaceTimer = session('workspace_status') !== 'pending_invitation';
    $isPendingWorkspace = session('workspace_status') === 'pending_invitation';
    $workspaceSessionTimeout = (int) session('workspace_security_settings.session_timeout', 30);
?>

<header class="topbar">
    <div class="greeting-text">
        <h5><?php echo e($greetingText); ?>, <?php echo e($displayName); ?>!</h5>
        <small><?php echo e(ucfirst($displayRole)); ?> Dashboard</small>
    </div>

    <div class="topbar-center d-flex align-items-center">
        <?php if($showWorkspaceTimer): ?>
            <div class="timer-wrapper mr-3">
                <div class="timer-display" id="display">00:00:00</div>
            </div>
            <div class="timer-controls">
                <button class="btn btn-sm btn-success px-3 mr-2" id="startStopBtn" onclick="toggleTimer()">
                    <i class="bi bi-play-fill mr-1"></i>Start
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="resetTimer()">Reset</button>
            </div>
        <?php else: ?>
            <div class="pending-workspace-banner">
                <i class="bi bi-envelope-exclamation mr-2"></i>
                Awaiting workspace invitation
            </div>
        <?php endif; ?>
    </div>

    <div class="topbar-right d-flex align-items-center">
        <div class="search-box mr-4 d-none d-md-block">
            <input type="text" class="form-control-pill" id="globalSearchInput" placeholder="Search cases, clients, documents..." autocomplete="off">
            <div class="search-results-dropdown d-none" id="globalSearchResults"></div>
        </div>

        <div class="notification-menu mr-3">
            <button type="button" class="notification-trigger" id="notificationTrigger" aria-label="Notifications" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <span class="notification-dot d-none" id="notificationDot"></span>
            </button>
        </div>

        <div class="dropdown" id="profileDropdownWrapper">
            <div class="user-avatar-circle" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
                <?php echo e(strtoupper(substr($displayName, 0, 1))); ?>

            </div>

            <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 p-0" aria-labelledby="userDropdown" style="min-width: 280px; border-radius: 15px; overflow: hidden; margin-top: 10px;">
                <a href="<?php echo e(route('settings')); ?>" class="p-3 d-flex align-items-center bg-light user-profile-link text-decoration-none">
                    <div class="user-avatar-circle mr-3" style="width: 45px; height: 45px; font-size: 1rem;">
                        <?php echo e(strtoupper(substr($displayName, 0, 1))); ?>

                    </div>

                    <div>
                        <h6 class="mb-0 font-weight-bold" style="font-size: 0.9rem;"><?php echo e($displayName); ?></h6>
                        <p class="mb-0 text-muted small">
                            <?php echo e($displayEmail); ?>

                        </p>
                    </div>
                </a>

                <div class="py-2">
                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="<?php echo e(route('settings')); ?>">
                        <i class="bi bi-gear mr-3 text-muted"></i> Settings
                    </a>

                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="#" data-toggle="modal" data-target="#changePasswordModal">
                        <i class="bi bi-shield-lock mr-3 text-muted"></i> Change Password
                    </a>

                    <form action="<?php echo e(route('settings.account.destroy')); ?>" method="POST" onsubmit="return confirm('Delete this account? This action cannot be undone.');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="dropdown-item d-flex align-items-center py-2 px-3 text-warning border-0 bg-transparent w-100">
                            <i class="bi bi-person-x mr-3"></i> Delete Account
                        </button>
                    </form>

                    <div class="dropdown-divider"></div>

                    <a class="dropdown-item d-flex align-items-center py-2 px-3 text-danger" href="#" data-toggle="modal" data-target="#logoutModal">
                        <i class="bi bi-power mr-3"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<form action="<?php echo e(route('logout')); ?>" method="POST" id="autoLogoutForm" class="d-none">
    <?php echo csrf_field(); ?>
</form>
<div class="profile-menu-backdrop d-none" id="profileMenuBackdrop"></div>
<div class="notification-panel-backdrop d-none" id="notificationPanelBackdrop"></div>
<aside class="notification-panel d-none" id="notificationPanel" aria-hidden="true">
    <div class="notification-panel-header">
        <div>
            <strong>Notifications</strong>
            <small>Workspace updates for your account</small>
        </div>
        <div class="d-flex align-items-center">
            <span class="notification-count-badge d-none mr-2" id="notificationCountBadge"></span>
            <button type="button" class="notification-panel-close" id="notificationPanelClose" aria-label="Close notifications">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div class="notification-list" id="notificationList">
        <div class="notification-empty">
            <i class="bi bi-bell-slash"></i>
            <span>No notifications yet.</span>
        </div>
    </div>
</aside>

<?php echo $__env->make('layouts.modals.change-password', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

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
    const notificationTrigger = document.getElementById('notificationTrigger');
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationPanelBackdrop = document.getElementById('notificationPanelBackdrop');
    const notificationPanelClose = document.getElementById('notificationPanelClose');
    const notificationList = document.getElementById('notificationList');
    const notificationDot = document.getElementById('notificationDot');
    const notificationCountBadge = document.getElementById('notificationCountBadge');
    const userDropdown = document.getElementById('userDropdown');
    const profileDropdownWrapper = document.getElementById('profileDropdownWrapper') || userDropdown?.closest('.dropdown');
    const profileMenuBackdrop = document.getElementById('profileMenuBackdrop');
    const autoLogoutForm = document.getElementById('autoLogoutForm');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const pendingWorkspaceMode = <?php echo json_encode($isPendingWorkspace, 15, 512) ?>;
    const workspaceSessionTimeoutMinutes = <?php echo json_encode($workspaceSessionTimeout, 15, 512) ?>;
    let elapsedTime = 0;
    let startTime = null;
    let timerInterval = null;
    let searchTimeoutId = null;
    let notificationsLoaded = false;
    let profileDropdownEventsBound = false;
    let idleTimeoutId = null;
    let idleTimeoutLocked = false;

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

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNotificationDate(value) {
        if (!value) {
            return 'Just now';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return 'Just now';
        }

        return new Intl.DateTimeFormat(undefined, {
            day: 'numeric',
            month: 'short',
            hour: 'numeric',
            minute: '2-digit'
        }).format(date);
    }

    function getNotificationIcon(type) {
        switch (type) {
            case 'account':
                return 'bi-person-plus';
            case 'workspace_invitation':
                return 'bi-envelope-check';
            case 'password_reset_request':
                return 'bi-key';
            case 'document':
                return 'bi-file-earmark-text';
            case 'schedule':
                return 'bi-calendar-event';
            default:
                return 'bi-info-circle';
        }
    }

    function updateNotificationIndicators(unreadCount) {
        if (notificationDot) {
            notificationDot.classList.toggle('d-none', unreadCount < 1);
        }

        if (notificationCountBadge) {
            notificationCountBadge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
            notificationCountBadge.classList.toggle('d-none', unreadCount < 1);
        }
    }

    function renderNotifications(items, unreadCount) {
        if (!notificationList) {
            return;
        }

        updateNotificationIndicators(unreadCount);

        if (!items.length) {
            notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-bell-slash"></i>
                    <span>No notifications yet.</span>
                </div>
            `;
            return;
        }

        notificationList.innerHTML = items.map(function (item) {
            const itemClasses = ['notification-item'];

            if (!item.read) {
                itemClasses.push('is-unread');
            }

            return `
                <div
                    class="${itemClasses.join(' ')}"
                    data-notification-id="${escapeHtml(item.id)}"
                    data-notification-link="${escapeHtml(item.link || '')}"
                    data-notification-action-required="${item.action_required ? 'true' : 'false'}"
                >
                    <span class="notification-item-icon">
                        <i class="bi ${getNotificationIcon(item.type)}"></i>
                    </span>
                    <span class="notification-item-copy">
                        <strong>${escapeHtml(item.title)}</strong>
                        <span>${escapeHtml(item.message)}</span>
                        <small>${escapeHtml(formatNotificationDate(item.created_at))}</small>
                        ${item.action_required ? `
                            <span class="notification-item-actions">
                                <button
                                    type="button"
                                    class="notification-action-btn"
                                    ${item.type === 'workspace_invitation'
                                        ? `data-notification-accept="${escapeHtml(item.id)}"`
                                        : `data-notification-reset="${escapeHtml(item.id)}"`}
                                >
                                    ${escapeHtml(item.action_label || 'Open')}
                                </button>
                            </span>
                        ` : ''}
                    </span>
                </div>
            `;
        }).join('');
    }

    function loadNotifications() {
        return fetch("<?php echo e(route('notifications.index')); ?>", {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Notification fetch failed');
                }

                return response.json();
            })
            .then(function (payload) {
                renderNotifications(payload.notifications || [], payload.unread_count || 0);
                notificationsLoaded = true;
            })
            .catch(function () {
                if (notificationList) {
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>Notifications are unavailable right now.</span>
                        </div>
                    `;
                }
            });
    }

    function toggleNotificationPanel(forceOpen) {
        if (!notificationPanel || !notificationTrigger || !notificationPanelBackdrop) {
            return;
        }

        const shouldOpen = typeof forceOpen === 'boolean'
            ? forceOpen
            : notificationPanel.classList.contains('d-none');

        notificationPanel.classList.toggle('d-none', !shouldOpen);
        notificationPanelBackdrop.classList.toggle('d-none', !shouldOpen);
        notificationPanel.classList.toggle('show', shouldOpen);
        notificationPanelBackdrop.classList.toggle('show', shouldOpen);
        notificationTrigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        notificationPanel.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');

        if (shouldOpen && !notificationsLoaded) {
            loadNotifications();
        }
    }

    function toggleProfileBackdrop(forceOpen) {
        if (!profileMenuBackdrop) {
            return;
        }

        const shouldOpen = typeof forceOpen === 'boolean'
            ? forceOpen
            : profileMenuBackdrop.classList.contains('d-none');

        profileMenuBackdrop.classList.toggle('d-none', !shouldOpen);
        profileMenuBackdrop.classList.toggle('show', shouldOpen);
        document.body.classList.toggle('profile-menu-open', shouldOpen);
    }

    function triggerIdleLogout() {
        if (idleTimeoutLocked || !autoLogoutForm) {
            return;
        }

        idleTimeoutLocked = true;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Session expired',
                text: 'You were signed out after being inactive for too long.',
                confirmButtonColor: '#0891b2',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(function () {
                autoLogoutForm.submit();
            });

            return;
        }

        autoLogoutForm.submit();
    }

    function resetIdleLogoutTimer() {
        if (!workspaceSessionTimeoutMinutes || workspaceSessionTimeoutMinutes < 1 || !autoLogoutForm) {
            return;
        }

        clearTimeout(idleTimeoutId);
        idleTimeoutId = setTimeout(triggerIdleLogout, workspaceSessionTimeoutMinutes * 60 * 1000);
    }

    function markNotificationRead(notificationId) {
        return fetch("<?php echo e(url('/notifications')); ?>/" + encodeURIComponent(notificationId) + "/read", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({})
        }).then(function () {
            return loadNotifications();
        });
    }

    function acceptNotificationInvitation(notificationId) {
        return fetch("<?php echo e(url('/notifications')); ?>/" + encodeURIComponent(notificationId) + "/accept", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({})
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload.message || 'Invitation could not be accepted.');
                    }

                    return payload;
                });
            })
            .then(function (payload) {
                return loadNotifications().then(function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Invitation Accepted',
                            text: payload.message || 'You are now part of the workspace.',
                            timer: 2400,
                            showConfirmButton: false,
                            background: 'rgba(255, 255, 255, 0.96)',
                            backdrop: 'rgba(8, 145, 178, 0.12)'
                        });
                    }
                });
            });
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
            fetch("<?php echo e(route('search.index')); ?>?q=" + encodeURIComponent(query), {
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
        if (globalSearchInput && globalSearchResults && !globalSearchInput.closest('.search-box')?.contains(event.target)) {
            hideSearchResults();
        }

        if (notificationPanel && notificationTrigger && notificationPanelBackdrop) {
            const insideNotifications = notificationPanel.contains(event.target)
                || notificationTrigger.contains(event.target);

            if (!insideNotifications && !notificationPanel.classList.contains('d-none')) {
                toggleNotificationPanel(false);
            }
        }
    });

    notificationTrigger?.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        toggleNotificationPanel();
    });

    notificationTrigger?.addEventListener('mousedown', function (event) {
        event.preventDefault();
    });

    notificationPanelClose?.addEventListener('click', function () {
        toggleNotificationPanel(false);
    });

    notificationPanelBackdrop?.addEventListener('click', function () {
        toggleNotificationPanel(false);
    });

    function bindProfileDropdownEvents() {
        if (profileDropdownEventsBound || typeof $ === 'undefined' || !profileDropdownWrapper) {
            return;
        }

        $(profileDropdownWrapper).on('show.bs.dropdown', function () {
            toggleProfileBackdrop(true);
        });

        $(profileDropdownWrapper).on('hidden.bs.dropdown', function () {
            toggleProfileBackdrop(false);
        });

        profileDropdownEventsBound = true;
    }

    bindProfileDropdownEvents();

    document.addEventListener('DOMContentLoaded', bindProfileDropdownEvents);
    window.addEventListener('load', bindProfileDropdownEvents);

    profileMenuBackdrop?.addEventListener('click', function () {
        if (typeof $ !== 'undefined' && userDropdown) {
            $(userDropdown).dropdown('toggle');
            return;
        }

        toggleProfileBackdrop(false);
    });

    notificationList?.addEventListener('click', function (event) {
        const acceptButton = event.target.closest('[data-notification-accept]');
        const resetButton = event.target.closest('[data-notification-reset]');

        if (acceptButton) {
            const notificationId = acceptButton.getAttribute('data-notification-accept');

            if (!notificationId) {
                return;
            }

            acceptNotificationInvitation(notificationId).catch(function (error) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invitation Error',
                        text: error.message || 'Invitation could not be accepted.',
                        confirmButtonColor: '#0891b2'
                    });
                }
            });
            return;
        }

        if (resetButton) {
            const notificationId = resetButton.getAttribute('data-notification-reset');

            if (!notificationId) {
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Set Temporary Password',
                    text: 'Create a temporary password for this workspace member.',
                    input: 'password',
                    inputPlaceholder: 'Enter temporary password',
                    confirmButtonText: 'Save Password',
                    confirmButtonColor: '#0891b2',
                    showCancelButton: true,
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocorrect: 'off',
                    },
                    preConfirm: function (value) {
                        if (!value || value.length < 4) {
                            Swal.showValidationMessage('Temporary password must be at least 4 characters.');
                            return false;
                        }

                        return value;
                    }
                }).then(function (result) {
                    if (!result.isConfirmed || !result.value) {
                        return;
                    }

                    fetch("<?php echo e(url('/notifications')); ?>/" + encodeURIComponent(notificationId) + "/reset-password", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        },
                        body: JSON.stringify({
                            temp_password: result.value
                        })
                    })
                        .then(function (response) {
                            return response.json().then(function (payload) {
                                if (!response.ok) {
                                    throw new Error(payload.message || 'Temporary password could not be saved.');
                                }

                                return payload;
                            });
                        })
                        .then(function (payload) {
                            return loadNotifications().then(function () {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Temporary Password Saved',
                                    text: payload.message || 'Share the temporary password with the user.',
                                    confirmButtonColor: '#0891b2'
                                });
                            });
                        })
                        .catch(function (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Reset Failed',
                                text: error.message || 'Temporary password could not be saved.',
                                confirmButtonColor: '#0891b2'
                            });
                        });
                });

                return;
            }

            return;
        }

        const notificationItem = event.target.closest('.notification-item');

        if (!notificationItem) {
            return;
        }

        if (notificationItem.getAttribute('data-notification-action-required') === 'true') {
            return;
        }

        const notificationId = notificationItem.getAttribute('data-notification-id');
        const notificationLink = notificationItem.getAttribute('data-notification-link');

        const navigate = function () {
            if (notificationLink) {
                window.location.href = notificationLink;
            }
        };

        if (!notificationId) {
            navigate();
            return;
        }

        markNotificationRead(notificationId)
            .catch(function () {
                return null;
            })
            .finally(navigate);
    });

    loadState();
    loadNotifications();

    if (workspaceSessionTimeoutMinutes > 0 && autoLogoutForm) {
        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (eventName) {
            document.addEventListener(eventName, resetIdleLogoutTimer, { passive: true });
        });

        resetIdleLogoutTimer();
    }
})();
</script>
<?php /**PATH C:\xampp\htdocs\ILA\resources\views\layouts\partials\topbar.blade.php ENDPATH**/ ?>