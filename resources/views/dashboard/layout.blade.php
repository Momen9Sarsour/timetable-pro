<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Timetable Management System - Palestine Technical College">
    <meta name="keywords" content="timetable, schedule, palestine technical college, education">
    <meta name="author" content="Palestine Technical College">

    <title>@yield('title', 'Timetable Pro | Palestine Technical College')</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('dashboard_assets/style.css') }}">

    @stack('styles')
</head>

<body>
    <!-- زر فتح القائمة الجانبية للموبايل -->
    <button class="sidebar-toggle btn btn-primary shadow-lg" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- الهيدر الرئيسي -->
    <header class="main-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center h-100 px-3">
                <!-- اللوجو - أقصى اليسار -->
                <div class="logo-section">
                    <div class="logo">
                        <i class="fas fa-calendar-alt logo-icon"></i>
                        <span class="logo-text">Timetable Pro</span>
                    </div>
                </div>

                <!-- شريط البحث - في المنتصف -->
                <div class="search-section flex-grow-1 mx-4">
                    <div class="search-wrapper position-relative">
                        <input type="text" class="form-control search-input" placeholder="Search courses, instructors, rooms...">
                        <button class="search-btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- أزرار التحكم - أقصى اليمين -->
                <div class="header-controls d-flex align-items-center gap-2">
                    <!-- زر تبديل الثيم -->
                    <button class="theme-toggle-btn btn btn-outline-secondary btn-sm" aria-label="Toggle Theme">
                        <i class="fas fa-moon theme-icon"></i>
                    </button>

                    <!-- الإشعارات -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Notifications</h6>
                                <span class="badge bg-primary">3 New</span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-check text-success me-2"></i>Schedule Generated</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Room Conflict</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-envelope text-info me-2"></i>New Message</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">View All</a></li>
                        </ul>
                    </div>

                    <!-- قائمة المستخدم -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="user-name d-none d-md-inline">John Doe</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar-large bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">John Doe</h6>
                                        <small class="text-muted">Administrator</small>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-envelope me-2"></i>Messages <span class="badge bg-danger ms-auto">5</span></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- القائمة الجانبية -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-content">
            <!-- قائمة التنقل الرئيسية -->
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <!-- لوحة التحكم -->
                    <li class="nav-item {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                        <a href="{{ route('dashboard.index') }}" class="nav-link">
                            <i class="fas fa-tachometer-alt nav-icon"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>

                    <!-- البرامج الأكاديمية -->
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-graduation-cap nav-icon"></i>
                            <span class="nav-text">Academic Programs</span>
                        </a>
                    </li>

                    <!-- إدارة البيانات -->
                    @php
                        $isActiveMenu = request()->routeIs('data-entry.*');
                    @endphp
                    <li class="nav-item has-dropdown {{ $isActiveMenu ? 'active' : '' }}">
                        <a href="#dataManagement" class="nav-link dropdown-toggle" data-bs-toggle="collapse"
                           aria-expanded="{{ $isActiveMenu ? 'true' : 'false' }}">
                            <i class="fas fa-database nav-icon"></i>
                            <span class="nav-text">Data Management</span>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <div class="collapse submenu {{ $isActiveMenu ? 'show' : '' }}" id="dataManagement">
                            <ul class="submenu-list">
                                <li class="{{ request()->routeIs('data-entry.departments.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.departments.index') }}">
                                        <i class="fas fa-building"></i> Departments
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.roles.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.roles.index') }}">
                                        <i class="fas fa-user-shield"></i> Roles
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.users.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.users.index') }}">
                                        <i class="fas fa-users-cog"></i> Users
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.room-types.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.room-types.index') }}">
                                        <i class="fas fa-door-closed"></i> Room Types
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.rooms.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.rooms.index') }}">
                                        <i class="fas fa-door-open"></i> Rooms
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.subject-types.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.subject-types.index') }}">
                                        <i class="fas fa-tags"></i> Subject Types
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.subject-categories.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.subject-categories.index') }}">
                                        <i class="fas fa-project-diagram"></i> Subject Categories
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.subjects.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.subjects.index') }}">
                                        <i class="fas fa-book"></i> Subjects
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.plans.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.plans.index') }}">
                                        <i class="fas fa-clipboard-list"></i> Academic Plans
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.plan-expected-counts.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.plan-expected-counts.index') }}">
                                        <i class="fas fa-users"></i> Expected Counts
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.sections.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.sections.index') }}">
                                        <i class="fas fa-users-class"></i> Sections
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.plan-groups.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.plan-groups.index') }}">
                                        <i class="fas fa-users"></i> Plan Groups
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.instructors.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.instructors.index') }}">
                                        <i class="fas fa-chalkboard-teacher"></i> Instructors
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.instructor-section.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.instructor-section.index') }}">
                                        <i class="fas fa-link"></i> Instructor Section
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.instructor-subjects.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.instructor-subjects.index') }}">
                                        <i class="fas fa-link"></i> Instructor Subjects
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.timeslots.*') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.timeslots.index') }}">
                                        <i class="fas fa-clock"></i> Timeslots
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('data-entry.settings') ? 'active' : '' }}">
                                    <a href="{{ route('data-entry.settings') }}">
                                        <i class="fas fa-cogs"></i> Basic Settings
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- القيود -->
                    {{-- <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-sliders-h nav-icon"></i>
                            <span class="nav-text">Constraints</span>
                        </a>
                    </li> --}}

                    <!-- التحكم بالخوارزمية -->
                    @php
                        $isActiveAlgorithm = request()->routeIs('new-algorithm*');
                    @endphp
                    <li class="nav-item has-dropdown {{ $isActiveAlgorithm ? 'active' : '' }}">
                        <a href="#algorithmControl" class="nav-link dropdown-toggle" data-bs-toggle="collapse"
                           aria-expanded="{{ $isActiveAlgorithm ? 'true' : 'false' }}">
                            <i class="fas fa-cogs nav-icon"></i>
                            <span class="nav-text">New Algorithm Control</span>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <div class="collapse submenu {{ $isActiveAlgorithm ? 'show' : '' }}" id="algorithmControl">
                            <ul class="submenu-list">
                                <li class="{{ request()->routeIs('new-algorithm.sections.*') ? 'active' : '' }}">
                                    <a href="{{ route('new-algorithm.sections.index') }}">
                                        <i class="fas fa-code-branch"></i> Section Control
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('new-algorithm.plan-groups.*') ? 'active' : '' }}">
                                    <a href="{{ route('new-algorithm.plan-groups.index') }}">
                                        <i class="fas fa-code-branch"></i> Plan Groups Control
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('new-algorithm.populations.*') ? 'active' : '' }}">
                                    <a href="{{ route('new-algorithm.populations.index') }}">
                                        <i class="fas fa-code-branch"></i> populations Control
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>


                    <!-- التحكم بالخوارزمية -->
                    @php
                        $isActiveAlgorithm = request()->routeIs('algorithm-control*');
                    @endphp
                    <li class="nav-item has-dropdown {{ $isActiveAlgorithm ? 'active' : '' }}">
                        <a href="#algorithmControl" class="nav-link dropdown-toggle" data-bs-toggle="collapse"
                           aria-expanded="{{ $isActiveAlgorithm ? 'true' : 'false' }}">
                            <i class="fas fa-cogs nav-icon"></i>
                            <span class="nav-text">Algorithm Control</span>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <div class="collapse submenu {{ $isActiveAlgorithm ? 'show' : '' }}" id="algorithmControl">
                            <ul class="submenu-list">
                                <li class="{{ request()->routeIs('algorithm-control.crossover-types.*') ? 'active' : '' }}">
                                    <a href="{{ route('algorithm-control.crossover-types.index') }}">
                                        <i class="fas fa-code-branch"></i> Crossover Methods
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('algorithm-control.selection-types.*') ? 'active' : '' }}">
                                    <a href="{{ route('algorithm-control.selection-types.index') }}">
                                        <i class="fas fa-check-double"></i> Selection Methods
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('algorithm-control.mutation-types.*') ? 'active' : '' }}">
                                    <a href="{{ route('algorithm-control.mutation-types.index') }}">
                                        <i class="fas fa-random"></i> Mutation Methods
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('algorithm-control.populations.*') ? 'active' : '' }}">
                                    <a href="{{ route('algorithm-control.populations.index') }}">
                                        <i class="fas fa-dna"></i> Population Management
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('algorithm-control.timetable.results.index') || request()->routeIs('algorithm-control.timetable.result.show') ? 'active' : '' }}">
                                    <a href="{{ route('algorithm-control.timetable.results.index') }}">
                                        <i class="fas fa-calendar-check"></i> Generation Results
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- عرض الجداول -->
                    @php
                        $isViewTimetables = request()->routeIs('dashboard.timetables.*');
                    @endphp
                    <li class="nav-item has-dropdown {{ $isViewTimetables ? 'active' : '' }}">
                        <a href="#viewTimetables" class="nav-link dropdown-toggle" data-bs-toggle="collapse"
                           aria-expanded="{{ $isViewTimetables ? 'true' : 'false' }}">
                            <i class="fas fa-calendar-alt nav-icon"></i>
                            <span class="nav-text">View Timetables</span>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <div class="collapse submenu {{ $isViewTimetables ? 'show' : '' }}" id="viewTimetables">
                            <ul class="submenu-list">
                                <li class="{{ request()->routeIs('dashboard.timetables.sections') ? 'active' : '' }}">
                                    <a href="{{ route('dashboard.timetables.sections') }}">
                                        <i class="fas fa-users-class"></i> Section Timetables
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('dashboard.timetables.instructors') ? 'active' : '' }}">
                                    <a href="{{ route('dashboard.timetables.instructors') }}">
                                        <i class="fas fa-chalkboard-teacher"></i> Instructor Timetables
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs('dashboard.timetables.rooms') ? 'active' : '' }}">
                                    <a href="{{ route('dashboard.timetables.rooms') }}">
                                        <i class="fas fa-door-open"></i> Room Timetables
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- التقارير والتحليلات -->
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-chart-bar nav-icon"></i>
                            <span class="nav-text">Reports & Analytics</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Footer القائمة الجانبية -->
            <div class="sidebar-footer p-3 border-top">
                <button class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <i class="fas fa-question-circle me-2"></i>Help Center
                </button>
                <div class="text-center text-muted">
                    <small>Version 1.0.0</small>
                </div>
            </div>
        </div>
    </aside>

    <!-- المحتوى الرئيسي -->
    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            @yield('content')
        </div>
    </main>

    <!-- Overlay للموبايل -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Back to Top Button -->
    <button class="back-to-top btn btn-primary" id="backToTop" aria-label="Back to Top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="{{ asset('dashboard_assets/script.js') }}"></script>

    @stack('scripts')
</body>

</html>
