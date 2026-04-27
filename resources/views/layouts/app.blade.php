<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>ILA SYSTEM | @yield('title', 'Professional Dashboard')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    @if(Request::is('settings*'))
        <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
    @endif
    @if(Request::is('login') || Request::is('register'))
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    @endif

    @yield('styles')
</head>

<body>
<div class="dashboard-wrapper">
    @include('layouts.partials.sidebar')

    <div class="main-content">
        @include('layouts.partials.topbar')

        <div id="content-area" class="content-body">
            @yield('content')
        </div>
    </div>
</div>

@yield('modals')

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
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Operation Successful',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false,
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(8, 145, 178, 0.1)`
            });
        @endif

        @if(session('error') || $errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Attention',
                text: "{{ session('error') ?: $errors->first() }}",
                confirmButtonColor: '#0891b2'
            });
        @endif
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');

        if (!sidebar) {
            return;
        }

        sidebar.classList.toggle('collapsed');
        localStorage.setItem('ilaSidebarCollapsed', sidebar.classList.contains('collapsed') ? 'true' : 'false');
    }
</script>

@yield('scripts')
</body>
</html>
