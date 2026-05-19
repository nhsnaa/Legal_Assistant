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

    @php
        $dashboardCssPath = public_path('css/dashboard.css');
        $dashboardCss = file_exists($dashboardCssPath) ? file_get_contents($dashboardCssPath) : '';
        $settingsCss = '';

        if (Request::is('settings*')) {
            $settingsCssPath = public_path('css/settings.css');
            $settingsCss = file_exists($settingsCssPath) ? file_get_contents($settingsCssPath) : '';
        }
    @endphp

    @if ($dashboardCss)
        <style>{!! $dashboardCss !!}</style>
    @endif

    @if ($settingsCss)
        <style>{!! $settingsCss !!}</style>
    @endif

    @yield('styles')
</head>

<body>
<div class="dashboard-wrapper">
    @if(session('workspace_status') !== 'pending_invitation')
        @include('layouts.partials.sidebar')
    @endif

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
                title: 'Done',
                text: "{{ session('success') }}",
                timer: 1800,
                showConfirmButton: false,
                backdrop: 'rgba(15, 23, 42, 0.28)',
                customClass: {
                    popup: 'ila-swal-popup'
                }
            });
        @endif

        @if(session('error') || $errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Attention',
                text: "{{ session('error') ?: $errors->first() }}",
                confirmButtonColor: '#0891b2',
                customClass: {
                    popup: 'ila-swal-popup',
                    confirmButton: 'ila-swal-confirm'
                },
                buttonsStyling: false
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

<script type="text/javascript">
  (function(d, t) {
      var v = d.createElement(t), s = d.getElementsByTagName(t)[0];
      v.onload = function() {
        window.voiceflow.chat.load({
          verify: { projectID: '69f9de68eeb8f50e7cfd253b' },
          url: 'https://general-runtime.voiceflow.com',
          versionID: 'production',
          assistant: {
            title: 'ILA Assistant',
            color: '#148599',
            /* This full palette stops the "Blue Hover" bug */
            palette: {
                50: '#f0f9fa',
                100: '#dcf1f4',
                200: '#bee3e9',
                300: '#93ccd6',
                400: '#5ea7b7',
                500: '#148599', // Main Teal
                600: '#116d7d', // Professional Hover (Darker Teal)
                700: '#0e5663',
                800: '#0c4852',
                900: '#0a3a43'
            }
          }
        });
      };
      v.src = 'https://cdn.voiceflow.com/widget-next/bundle.mjs';
      v.type = 'text/javascript';
      s.parentNode.insertBefore(v, s);
  })(document, 'script');
</script>

</body>
</html>
