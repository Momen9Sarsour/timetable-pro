<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTable Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('dashboard_assets/style.css') }}">

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

            <li class="nav-item {{ request()->routeIs('data-entry.plans.*') ? 'active' : '' }}">
                <a href="{{ route('data-entry.plans.index') }}"><i class="fas fa-clipboard-list fa-fw me-2"></i> Academic Plans</a>
            </li>
            {{-- <li>
                <a href="{{ route('dashboard.dataEntry') }}"><i class="fas fa-database"></i> Data Entry</a>
            </li> --}}
            <!-- Data Management Dropdown -->
            {{-- // قائمة منسدلة لإدارة البيانات --}}
            @php
                $isActiveMenu = request()->routeIs('data-entry.*');
            @endphp

            <li class="nav-item">
                <a class="nav-link d-flex align-items-center py-1 small {{ $isActiveMenu ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#dataManagementMenu" role="button"
                    aria-expanded="{{ $isActiveMenu ? 'true' : 'false' }}" aria-controls="dataManagementMenu">
                    <i class="fas fa-database me-2 fa-xs"></i>
                    <span class="small">Data Management</span>
                    <i class="fas fa-chevron-down ms-auto fa-2xs"></i>
                </a>

                <div class="collapse {{ $isActiveMenu ? 'show' : '' }}" id="dataManagementMenu">
                    <ul class="nav flex-column ms-3">

                        <li class="nav-item {{ request()->routeIs('data-entry.departments.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.departments.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.departments') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-building fa-fw me-2 fa-2xs"></i> Departments
                            </a>
                        </li>

                        {{-- @can('manage-rooms') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.rooms.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.rooms.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.rooms') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-door-open fa-fw me-2 fa-2xs"></i> Classrooms
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-subjects') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.room-types.*') ? 'active' : '' }}">

                            <a href="{{ route('data-entry.room-types.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.room-types') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-door-closed fa-fw me-2"></i> Room Types
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-instructors') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.instructors.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.instructors.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.instructors') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-chalkboard-teacher fa-fw me-2 fa-2xs"></i> Instructors
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-subjects') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.subjects.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.subjects.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.subjects') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-book fa-fw me-2 fa-2xs"></i> Subjects
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
                        <li class="nav-item {{ request()->routeIs('data-entry.subject-categories.*') ? 'active' : '' }}">

                            <a href="{{ route('data-entry.subject-categories.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.subject-types') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-project-diagram fa-fw me-2"></i> Subject Categories
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-users') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.roles.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.roles') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-user-shield fa-fw me-2 fa-2xs"></i> Roles
                            </a>
                        </li>
                        {{-- @endcan --}}

                        {{-- @can('manage-users') --}}
                        <li class="nav-item {{ request()->routeIs('data-entry.users.*') ? 'active' : '' }}">
                            <a href="{{ route('data-entry.users.index') }}"
                                class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.users') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                <i class="fas fa-users-cog fa-fw me-2 fa-2xs"></i> Users
                            </a>
                        </li>
                        {{-- @endcan --}}

                        @can('manage-plans')
                            <li class="nav-item">
                                <a href="{{ route('data-entry.plans') }}"
                                    class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.plans') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                    <i class="fas fa-clipboard-list fa-fw me-2 fa-2xs"></i> Academic Plans
                                </a>
                            </li>
                        @endcan

                        @can('manage-settings')
                            <li class="nav-item">
                                <a href="{{ route('data-entry.settings') }}"
                                    class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.settings') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                    <i class="fas fa-cogs fa-fw me-2 fa-2xs"></i> Basic Settings
                                </a>
                            </li>
                        @endcan

                        @can('manage-timeslots')
                            <li class="nav-item">
                                <a href="{{ route('data-entry.timeslots') }}"
                                    class="nav-link py-1 small d-flex align-items-center {{ request()->routeIs('data-entry.timeslots') ? 'active text-primary fw-semibold' : 'text-secondary' }}">
                                    <i class="fas fa-clock fa-fw me-2 fa-2xs"></i> Timeslots
                                </a>
                            </li>
                        @endcan

                    </ul>
                </div>
            </li>


            <li>
                <a href="#"><i class="fas fa-sliders-h"></i> Constraints</a>
            </li>
            <li>
                <a href="#"><i class="fas fa-cogs"></i> Algorithm Control</a>
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
</body>

</html>
