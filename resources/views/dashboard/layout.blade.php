<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TimeTable Pro')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('dashboard_assets/style.css') }}">
    {{-- Push Styles Stack --}}
    <style>
        /* السايدبار مع التمرير */
        .sidebar {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 250px;
            background: #fff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            z-index: 999;
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
    </style>
    @stack('styles')

</head>

<body>
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle btn btn-primary">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Header -->
    <div class="header">
        <div class="logo">TimeTable Pro</div>

        <div class="search-bar">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search courses, instructors...">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <div class="header-controls">
            <div class="dropdown">
                <button class="notification-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <h6 class="dropdown-header">Notifications</h6>
                    </li>
                    <li><a class="dropdown-item" href="#">New schedule generated</a></li>
                    <li><a class="dropdown-item" href="#">Room conflict detected</a></li>
                    <li><a class="dropdown-item" href="#">New message from admin</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="#">View all notifications</a></li>
                </ul>
            </div>

            <div class="dropdown">
                <button class="profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle"></i> John Doe
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-envelope me-2"></i> Messages</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li class="nav-item {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                <a href="{{ route('dashboard.index') }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <li>
                <a href="#"><i class="fas fa-graduation-cap"></i> Academic Programs</a>
            </li>

            {{-- <li class="nav-item {{ request()->routeIs('data-entry.plans.*') ? 'active' : '' }}">
                <a href="{{ route('data-entry.plans.index') }}">
                    <i class="fas fa-clipboard-list fa-fw me-2"></i>
                    Academic Plans</a>
            </li> --}}
            {{-- <li>
                <a href="{{ route('dashboard.dataEntry') }}"><i class="fas fa-database"></i> Data Entry</a>
            </li> --}}
            <!-- Data Management Dropdown -->
            {{-- // قائمة منسدلة لإدارة البيانات --}}
            @php
                $isActiveMenu = request()->routeIs('data-entry.*');
                $isActiveAlgorithm = request()->routeIs('algorithm-control*');
            @endphp

            <li class="nav-item">
                <a class="nav-link d-flex align-items-center py-1 small {{ $isActiveMenu ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#dataManagementMenu" role="button"
                    aria-expanded="{{ $isActiveMenu ? 'true' : 'false' }}" aria-controls="dataManagementMenu">
                    <i class="fas fa-database me-2 fa-4xs"></i>
                    <span class="small">Data Management</span>
                    <i class="fas fa-chevron-down ms-auto fa-3xs"></i>
                </a>

                <div class="collapse {{ $isActiveMenu ? 'show' : '' }}" id="dataManagementMenu">
                    <ul class="nav flex-column">

                        <li class="nav-item {{ request()->routeIs('data-entry.departments.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.departments.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.departments') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-building fa-fw me-2 fa-4xs"></i> Departments
                            </a>
                        </li>

                        {{-- @can('manage-users') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.roles.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.roles') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-user-shield fa-fw me-2 fa-4xs"></i> Roles
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-users') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.users.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.users.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.users') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-users-cog fa-fw me-2 fa-4xs"></i> Users
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-subjects') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.room-types.*') ? 'active' : '' }}">

                            <a href="{{ route('data-entry.room-types.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.room-types') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-door-closed fa-fw me-2 fa-4xs"></i> Room Types
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-rooms') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.rooms.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.rooms.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.rooms') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-door-open fa-fw me-2 fa-4xs"></i> Rooms
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-subjects') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.subject-types.*') ? 'active' : '' }}">

                            <a href="{{ route('data-entry.subject-types.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.subject-types') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-tags fa-fw me-2"></i>SubjectTypes
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-subjects') --}}
                        <li
                            class="nav-item {{ request()->routeIs('data-entry.subject-categories.*') ? 'active' : '' }}">

                            <a href="{{ route('data-entry.subject-categories.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.subject-types') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-project-diagram fa-fw me-2"></i> Subject Categories
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-subjects') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.subjects.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.subjects.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.subjects') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-book fa-fw me-2 fa-4xs"></i> Subjects
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-plans') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.plans.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.plans.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.plans') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-clipboard-list fa-fw me-2 fa-4xs"></i> Academic Plans
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('plan-expected-counts') --}}
                        <li
                            class="nav-item {{ request()->routeIs('data-entry.plan-expected-counts.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.plan-expected-counts.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.plan-expected-counts') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-users fa-fw me-2 fa-4xs"></i> Expected Counts
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('sections') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.sections.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.sections.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.plan-expected-counts') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-users-class fa-fw me-2"></i> Sections
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-instructors') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.instructors.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.instructors.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.instructors') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-chalkboard-teacher fa-fw me-2 fa-4xs"></i> Instructors
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('sections') --}}
                        <li
                            class="nav-item {{ request()->routeIs('data-entry.instructor-section.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.instructor-section.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.instructor-section') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-link fa-fw me-2"></i> Instructor Section
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('sections') --}}
                        <li
                            class="nav-item {{ request()->routeIs('data-entry.instructor-subjects.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.instructor-subjects.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.instructor-subjects') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-link fa-fw me-2"></i> Instructor Subjects
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-timeslots') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.timeslots.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.timeslots.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.timeslots') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-clock fa-fw me-2 fa-4xs"></i> Timeslots
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-settings') --}}
                        <li class="nav-item">
                            <a href="{{ route('data-entry.settings') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.settings') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-cogs fa-fw me-2 fa-4xs"></i> Basic Settings
                            </a>
                        </li>
                        {{-- @endcan --}}

                    </ul>
                </div>
            </li>


            <li>
                <a href="#"><i class="fas fa-sliders-h"></i> Constraints</a>
            </li>
            {{-- <li>
                <a href="#"><i class="fas fa-cogs"></i> Algorithm Control</a>
            </li> --}}

            <li class="nav-item">
                <a class="nav-link d-flex align-items-center py-1 small {{ $isActiveAlgorithm ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#dataAlgorithmMenu" role="button"
                    aria-expanded="{{ $isActiveAlgorithm ? 'true' : 'false' }}" aria-controls="dataAlgorithmMenu">
                    <i class="fas fa-database me-2 fa-4xs"></i>
                    <span class="small">Algorithm Control</span>
                    <i class="fas fa-chevron-down ms-auto fa-3xs"></i>
                </a>
                <div class="collapse {{ $isActiveAlgorithm ? 'show' : '' }}" id="dataAlgorithmMenu">
                    <ul class="nav flex-column">
                        <li class="{{ request()->routeIs('algorithm-control.crossover-types.*') ? 'active' : '' }}">
                            <a href="{{ route('algorithm-control.crossover-types.index') }}"><i
                                    class="fas fa-code-branch fa-fw me-2"></i> Crossover Methods</a>
                        </li>
                        <li class="{{ request()->routeIs('algorithm-control.selection-types.*') ? 'active' : '' }}">
                            <a href="{{ route('algorithm-control.selection-types.index') }}"><i
                                    class="fas fa-check-double fa-fw me-2"></i> Selection Methods</a>
                        </li>
                            {{-- رابط جديد لنتائج الخوارزمية --}}
                            <li class="{{ request()->routeIs('algorithm-control.timetable.results.index') || request()->routeIs('algorithm-control.timetable.result.show') ? 'active' : '' }}">
                                <a href="{{ route('algorithm-control.timetable.results.index') }}">
                                    <i class="fas fa-calendar-alt"></i> Generation Results
                                </a>
                            </li>

                    </ul>
                </div>
            </li>

            <li>
                <a href="#"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <button class="dark-mode-toggle">
                <i class="fas fa-moon"></i> Dark Mode
            </button>
            <button class="help-btn">
                <i class="fas fa-question-circle"></i> Help
            </button>
        </div>
    </div>

    <!-- Main Content -->
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('dashboard_assets/script.js') }}"></script>
    <!-- Page-specific Scripts -->
    @stack('scripts')
</body>

</html>
