<aside class="sidebar" id="sidebar">
    <div class="brand-section mb-5 d-flex align-items-center">
        <button class="btn btn-link text-dark p-0 mr-3" onclick="toggleSidebar()" id="toggleBtn">
            <span style="font-size: 1.25rem;"><i class="bi bi-list"></i></span>
        </button>
        <h4 class="font-weight-bold mb-0 brand-text">ILA <span class="text-primary">SYSTEM</span></h4>
    </div>

    <nav class="nav flex-column px-2">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <span class="nav-icon"><i class="bi bi-grid-1x2-fill"></i></span>
            <span class="link-text">Dashboard</span>
        </a>
        <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">
            <span class="nav-icon"><i class="bi bi-people-fill"></i></span>
            <span class="link-text">Clients</span>
        </a>
        <a class="nav-link {{ request()->routeIs('cases.*') ? 'active' : '' }}" href="{{ route('cases.index') }}">
            <span class="nav-icon"><i class="bi bi-briefcase-fill"></i></span>
            <span class="link-text">Cases</span>
        </a>
        <a class="nav-link {{ request()->routeIs('schedule') ? 'active' : '' }}" href="{{ route('schedule') }}">
            <span class="nav-icon"><i class="bi bi-calendar-event-fill"></i></span>
            <span class="link-text">Schedule</span>
        </a>
        <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
            <span class="nav-icon"><i class="bi bi-folder-fill"></i></span>
            <span class="link-text">Documents</span>
        </a>
        <a class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}">
            <span class="nav-icon"><i class="bi bi-gear-fill"></i></span>
            <span class="link-text">Settings</span>
        </a>
    </nav>
</aside>

