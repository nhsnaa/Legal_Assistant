<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ILA System | Smart Legal Workspace for Solo Lawyers</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}">
</head>
<body>
    @php
        $isAuthenticated = auth()->check();
    @endphp

    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container">
            <a class="navbar-brand font-weight-bold" href="{{ route('welcome') }}">ILA <span class="text-primary">SYSTEM</span></a>
            <div class="ml-auto d-flex align-items-center">
                <a href="#features" class="nav-inline-link mr-4">Features</a>
                @if ($isAuthenticated)
                    <a href="{{ route('dashboard') }}" class="btn btn-link text-dark">Dashboard</a>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline-block mb-0 ml-2">
                        @csrf
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Switch Account</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-link text-dark">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary rounded-pill px-4">Get Started</a>
                @endif
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center hero-grid">
                <div class="col-lg-6 text-center text-lg-left">
                    <span class="hero-badge">
                        <i class="bi bi-stars mr-2"></i> Intelligent legal workspace for sole-proprietor lawyers
                    </span>
                    <h1 class="display-4 font-weight-bold mt-4">
                        Manage cases, clients, documents, and billable time in one
                        <span class="text-primary">calm professional system</span>
                    </h1>
                    <p class="lead hero-copy mt-4">
                        ILA System helps solo lawyers stay organized without juggling spreadsheets, folders, and reminders.
                        Keep your matters moving with a cleaner workflow for client records, schedules, case files, and daily time tracking.
                    </p>

                    <div class="hero-actions mt-5">
                        @if ($isAuthenticated)
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg rounded-pill px-5 mr-sm-4 mb-3 mb-sm-0">Open Your Dashboard</a>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline-block mb-3 mb-sm-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-dark btn-lg rounded-pill px-5">Switch Account</button>
                            </form>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-5 mr-sm-4 mb-3 mb-sm-0">Start Your Workspace</a>
                            <a href="#features" class="btn btn-outline-dark btn-lg rounded-pill px-5">Explore Features</a>
                        @endif
                    </div>

                    <div class="hero-trust row mt-5">
                        <div class="col-sm-4 mb-3">
                            <div class="trust-item">
                                <strong>Client-first</strong>
                                <span>Keep every matter organized and easy to retrieve.</span>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-3">
                            <div class="trust-item">
                                <strong>Time-saving</strong>
                                <span>Reduce admin work and focus more on legal service.</span>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-3">
                            <div class="trust-item">
                                <strong>Professional</strong>
                                <span>Built for a solo practice that still wants structure.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mt-5 mt-lg-0">
                    <div class="hero-panel">
                        <div class="hero-panel-top">
                            <span class="panel-pill">Today at a glance</span>
                            <span class="panel-date">Sole Practice Dashboard</span>
                        </div>

                        <div class="hero-stat-grid">
                            <div class="hero-stat-card accent">
                                <span class="stat-label">Active Matters</span>
                                <strong>18</strong>
                                <small>4 require follow-up this week</small>
                            </div>
                            <div class="hero-stat-card">
                                <span class="stat-label">Billable Hours</span>
                                <strong>42.5</strong>
                                <small>Monthly target 55 hours</small>
                            </div>
                        </div>

                        <div class="hero-panel-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Upcoming Schedule</h6>
                                <span class="mini-tag">3 events today</span>
                            </div>

                            <div class="schedule-item">
                                <div class="schedule-time">9:30 AM</div>
                                <div>
                                    <strong>Client Consultation</strong>
                                    <p class="mb-0">Family dispute advisory meeting</p>
                                </div>
                            </div>
                            <div class="schedule-item">
                                <div class="schedule-time">1:00 PM</div>
                                <div>
                                    <strong>Court Filing Review</strong>
                                    <p class="mb-0">Prepare final documents before submission</p>
                                </div>
                            </div>
                            <div class="schedule-item mb-0">
                                <div class="schedule-time">4:30 PM</div>
                                <div>
                                    <strong>Billing Check</strong>
                                    <p class="mb-0">Review this week's time entries and notes</p>
                                </div>
                            </div>
                        </div>

                        <div class="hero-panel-card compact">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Document Readiness</h6>
                                <span class="text-primary font-weight-bold">82%</span>
                            </div>
                            <div class="progress soft-progress">
                                <div class="progress-bar" role="progressbar" style="width: 82%"></div>
                            </div>
                            <p class="small text-muted mb-0 mt-3">Recent files, hearing notes, and case materials are centralized and ready for quick access.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="feature-section" id="features">
        <div class="container">
            <div class="section-heading text-center">
                <span class="section-kicker">Designed for daily practice</span>
                <h2 class="font-weight-bold">Everything a solo lawyer needs in one focused system</h2>
                <p class="text-muted mx-auto">Simple enough to use every day, structured enough to present a professional practice.</p>
            </div>

            <div class="row mt-5">
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                        <h5>Client Management</h5>
                        <p>Store contact details, matter history, and communication context in one place.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="bi bi-briefcase-fill"></i></div>
                        <h5>Case Tracking</h5>
                        <p>Monitor case status, deadlines, priorities, and linked documents without losing visibility.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="bi bi-folder2-open"></i></div>
                        <h5>Document Control</h5>
                        <p>Keep drafts, evidence, court files, and supporting records organized and searchable.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon"><i class="bi bi-stopwatch-fill"></i></div>
                        <h5>Time & Schedule</h5>
                        <p>Track billable work, manage appointments, and stay ahead of hearings and reminders.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="trust-section">
        <div class="container">
            <div class="trust-panel">
                <div class="row align-items-center">
                    <div class="col-lg-5 mb-4 mb-lg-0">
                        <span class="section-kicker">Built for legal confidence</span>
                        <h2 class="font-weight-bold mt-3">A system that supports trust, confidentiality, and organized legal work</h2>
                        <p class="text-muted mt-3 mb-0">
                            For a sole-proprietor lawyer, credibility comes from being prepared, organized, and consistent.
                            ILA System is designed to make that daily discipline easier to maintain.
                        </p>
                    </div>

                    <div class="col-lg-7">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="trust-card h-100">
                                    <div class="feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                                    <h5>Confidential Client Records</h5>
                                    <p>Keep sensitive details, legal documents, and case notes in one controlled workspace.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="trust-card h-100">
                                    <div class="feature-icon"><i class="bi bi-journal-check"></i></div>
                                    <h5>Organized Case Workflow</h5>
                                    <p>Track matters, deadlines, supporting files, and follow-ups without losing context.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="trust-card h-100">
                                    <div class="feature-icon"><i class="bi bi-clock-history"></i></div>
                                    <h5>Reliable Time Tracking</h5>
                                    <p>Maintain clear work records for billing, reviews, and day-to-day legal administration.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</body>
</html>
