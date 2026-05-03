<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    <title>ILA SYSTEM | <?php echo $__env->yieldContent('title', 'Professional Dashboard'); ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">

    <?php if(Request::is('settings*')): ?>
        <link rel="stylesheet" href="<?php echo e(asset('css/settings.css')); ?>">
    <?php endif; ?>
    <?php if(Request::is('login') || Request::is('register')): ?>
        <link rel="stylesheet" href="<?php echo e(asset('css/login.css')); ?>">
    <?php endif; ?>

    <?php echo $__env->yieldContent('styles'); ?>
</head>

<body>
<div class="dashboard-wrapper">
    <?php if(session('workspace_status') !== 'pending_invitation'): ?>
        <?php echo $__env->make('layouts.partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    <div class="main-content">
        <?php echo $__env->make('layouts.partials.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div id="content-area" class="content-body">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>
</div>

<?php echo $__env->yieldContent('modals'); ?>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        const sidebar = document.getElementById('sidebar');
        const storedSidebarState = localStorage.getItem('ilaSidebarCollapsed');

        if (sidebar && storedSidebarState === 'true') {
            sidebar.classList.add('collapsed');
        }

        // Setup CSRF for all AJAX requests (Essential for your Time Tracker)
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Global SweetAlert Notification Handlers
        <?php if(session('success')): ?>
            Swal.fire({
                icon: 'success',
                title: 'Operation Successful',
                text: "<?php echo e(session('success')); ?>",
                timer: 3000,
                showConfirmButton: false,
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(8, 145, 178, 0.1)`
            });
        <?php endif; ?>

        <?php if(session('error') || $errors->any()): ?>
            Swal.fire({
                icon: 'error',
                title: 'Attention',
                text: "<?php echo e(session('error') ?: $errors->first()); ?>",
                confirmButtonColor: '#0891b2'
            });
        <?php endif; ?>
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');

        if (!sidebar) {
            return;
        }

        sidebar.classList.toggle('collapsed');
        localStorage.setItem('ilaSidebarCollapsed', sidebar.classList.contains('collapsed') ? 'true' : 'false');
    }

    // Load notifications
    function loadNotifications() {
        fetch("<?php echo e(route('notifications.index')); ?>")
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById('notificationList');
                const notificationDot = document.getElementById('notificationDot');
                const notificationCountBadge = document.getElementById('notificationCountBadge');

                if (!notificationList) return;

                const unreadCount = data.unread_count;
                if (unreadCount > 0) {
                    notificationDot.classList.remove('d-none');
                    notificationCountBadge.textContent = unreadCount;
                    notificationCountBadge.classList.remove('d-none');
                } else {
                    notificationDot.classList.add('d-none');
                    notificationCountBadge.classList.add('d-none');
                }

                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<div class="notification-empty"><i class="bi bi-bell-slash"></i><span>No notifications yet.</span></div>';
                    return;
                }

                notificationList.innerHTML = data.notifications.map(notification => {
                    let actions = '';
                    if (notification.action_required) {
                        actions = `<button class="btn btn-sm btn-success accept-btn" data-id="${notification.id}">Accept</button>`;
                    }
                    return `
                        <div class="notification-item ${notification.read ? '' : 'unread'}">
                            <div class="notification-content">
                                <strong>${notification.title}</strong>
                                <p>${notification.message}</p>
                                <small>${new Date(notification.created_at).toLocaleDateString()}</small>
                            </div>
                            ${actions}
                        </div>
                    `;
                }).join('');

                // Add event listeners for accept buttons
                document.querySelectorAll('.accept-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        acceptInvitation(id);
                    });
                });
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    function acceptInvitation(id) {
        fetch("<?php echo e(route('notifications.accept', ':id')); ?>".replace(':id', id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Invitation Accepted',
                    text: data.message,
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload(); // Reload to update session and UI
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error accepting invitation:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to accept invitation.'
            });
        });
    }

    // Load notifications on page load
    loadNotifications();

    // Toggle notification dropdown
    const notificationTrigger = document.getElementById('notificationTrigger');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (notificationTrigger && notificationDropdown) {
        notificationTrigger.addEventListener('click', function() {
            notificationDropdown.classList.toggle('d-none');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!notificationTrigger.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.add('d-none');
            }
        });
    }
</script>

<?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\ILA\resources\views/layouts/app.blade.php ENDPATH**/ ?>