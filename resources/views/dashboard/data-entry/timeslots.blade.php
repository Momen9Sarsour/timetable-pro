@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-clock text-primary me-2"></i>
                        Manage Weekly Timeslots
                    </h4>
                    <p class="text-muted mb-0">Define and manage time periods for scheduling classes</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#generateStandardTimeslotsModal">
                        <i class="fas fa-cogs me-1"></i>
                        <span class="d-none d-sm-inline">Generate Standard</span>
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTimeslotModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    <!-- Main Content Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Weekly Timeslots Overview
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $timeslots->total() }} Timeslots</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($timeslots->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0" style="width: 15%;">Day</th>
                                        <th class="border-0" style="width: 15%;">Start Time</th>
                                        <th class="border-0" style="width: 15%;">End Time</th>
                                        <th class="border-0" style="width: 15%;">Duration</th>
                                        <th class="border-0 text-center" style="width: 15%;">Scheduled Classes</th>
                                        <th class="border-0 text-center" style="width: 25%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($timeslots as $index => $timeslot)
                                        @php
                                            $startTime = \Carbon\Carbon::parse($timeslot->start_time);
                                            $endTime = \Carbon\Carbon::parse($timeslot->end_time);
                                            $duration = $startTime->diffInMinutes($endTime);
                                        @endphp
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $timeslots->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">
                                                    {{ $timeslot->day }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-medium text-success">{{ $startTime->format('h:i A') }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-medium text-danger">{{ $endTime->format('h:i A') }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    {{ $duration }} min
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $timeslot->schedule_entries_count > 0 ? 'warning' : 'light' }} bg-opacity-15 text-{{ $timeslot->schedule_entries_count > 0 ? 'warning' : 'muted' }}">
                                                    {{ $timeslot->schedule_entries_count }} classes
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editTimeslotModal-{{ $timeslot->id }}"
                                                            title="Edit Timeslot">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteTimeslotModal-{{ $timeslot->id }}"
                                                            title="Delete Timeslot"
                                                            {{ $timeslot->schedule_entries_count > 0 ? 'disabled' : '' }}>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile/Tablet Cards -->
                        <div class="d-lg-none">
                            @foreach ($timeslots as $index => $timeslot)
                                @php
                                    $startTime = \Carbon\Carbon::parse($timeslot->start_time);
                                    $endTime = \Carbon\Carbon::parse($timeslot->end_time);
                                    $duration = $startTime->diffInMinutes($endTime);
                                @endphp
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">{{ $timeslot->day }}</span>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $duration }} min</span>
                                                </div>
                                                <div class="time-display d-flex align-items-center gap-2 mb-2">
                                                    <span class="fw-medium text-success">{{ $startTime->format('h:i A') }}</span>
                                                    <i class="fas fa-arrow-right text-muted small"></i>
                                                    <span class="fw-medium text-danger">{{ $endTime->format('h:i A') }}</span>
                                                </div>
                                                <div class="classes-info">
                                                    <span class="badge bg-{{ $timeslot->schedule_entries_count > 0 ? 'warning' : 'light' }} bg-opacity-15 text-{{ $timeslot->schedule_entries_count > 0 ? 'warning' : 'muted' }}">
                                                        <i class="fas fa-calendar me-1"></i>{{ $timeslot->schedule_entries_count }} scheduled classes
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editTimeslotModal-{{ $timeslot->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteTimeslotModal-{{ $timeslot->id }}"
                                                                {{ $timeslot->schedule_entries_count > 0 ? 'disabled' : '' }}>
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">#{{ $timeslots->firstItem() + $index }}</span>
                                            @if($timeslot->schedule_entries_count > 0)
                                                <small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Cannot delete - has scheduled classes
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($timeslots->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $timeslots->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-clock text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Timeslots Defined</h5>
                                <p class="text-muted mb-4">You can generate standard timeslots or add them manually to start scheduling classes.</p>
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#generateStandardTimeslotsModal">
                                        <i class="fas fa-cogs me-2"></i>Generate Standard Timeslots
                                    </button>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTimeslotModal">
                                        <i class="fas fa-plus me-2"></i>Add First Timeslot
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
@include('dashboard.data-entry.partials._timeslot_modals')

<!-- Individual Edit/Delete Modals for each timeslot -->
@foreach($timeslots as $timeslot)
    @include('dashboard.data-entry.partials._timeslot_modals', ['timeslot_to_edit' => $timeslot])
@endforeach

<style>
/* Time Display Enhancements */
.time-display {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

/* Badge Improvements */
.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
}

/* Button Group Enhancements */
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Card Hover Effects */
.card {
    transition: all 0.15s ease;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Empty State */
.empty-state i {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* Disabled button styling */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.dropdown-item:disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* Responsive Improvements */
@media (max-width: 768px) {
    .page-title {
        font-size: 1.25rem;
    }

    .time-display {
        font-size: 0.8rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .btn {
        font-size: 0.8125rem;
        padding: 0.375rem 0.75rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .page-title {
        font-size: 1.1rem;
    }

    .card-body {
        padding: 1rem;
    }

    .time-display {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .time-display i {
        display: none;
    }

    .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }

    .empty-state {
        padding: 2rem 1rem;
    }

    .empty-state i {
        font-size: 2.5rem;
    }

    .empty-state h5 {
        font-size: 1rem;
    }

    .empty-state p {
        font-size: 0.875rem;
    }

    .empty-state .d-flex {
        flex-direction: column;
    }

    .empty-state .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1rem;
    }

    .time-display {
        font-size: 0.75rem;
    }

    .badge {
        font-size: 0.6rem;
        padding: 0.15rem 0.3rem;
    }

    .btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }
}

/* Dark mode enhancements */
body.dark-mode .time-display .text-success {
    color: #22c55e !important;
}

body.dark-mode .time-display .text-danger {
    color: #ef4444 !important;
}

body.dark-mode .card {
    background: var(--dark-bg-secondary);
    border-color: var(--dark-border);
}

body.dark-mode .card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Table hover enhancements */
.table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

body.dark-mode .table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

/* Animation for action buttons */
.btn-group .btn {
    transition: all 0.15s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

/* Enhanced dropdown styling */
.dropdown-menu {
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
}

body.dark-mode .dropdown-menu {
    background: var(--dark-bg-secondary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: all 0.15s ease;
}

.dropdown-item:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

body.dark-mode .dropdown-item:hover {
    background-color: rgba(59, 130, 246, 0.15);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Modal reopening logic for validation errors
    @if($errors->any())
        @if(session('open_generate_modal') && $errors->hasBag('generateStandardModal'))
            $('#generateStandardTimeslotsModal').modal('show');
        @elseif(session('open_modal_on_error') == 'addTimeslotModal' && $errors->hasBag('addTimeslotModal'))
            $('#addTimeslotModal').modal('show');
        @elseif(Str::startsWith(session('open_modal_on_error'), 'editTimeslotModal_') && $errors->hasBag(session('open_modal_on_error')))
            $('#{{ session('open_modal_on_error') }}').modal('show');
        @endif
    @endif

    // Enhanced tooltip for disabled buttons
    $('[disabled]').each(function() {
        $(this).attr('title', 'Cannot delete - timeslot has scheduled classes');
    });

    // Smooth scroll behavior
    $('html').css('scroll-behavior', 'smooth');

    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        $('.alert:not(.alert-permanent)').fadeOut();
    }, 5000);

    // Add loading state to form submissions
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
        submitBtn.prop('disabled', true);

        // Re-enable after 5 seconds as fallback
        setTimeout(() => {
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }, 5000);
    });

    // Enhanced dropdown interaction
    $('.dropdown-toggle').on('click', function(e) {
        e.stopPropagation();
    });

    // Keyboard navigation for tables
    $(document).on('keydown', function(e) {
        if (e.altKey && e.key === 'n') {
            e.preventDefault();
            $('#addTimeslotModal').modal('show');
        }
        if (e.altKey && e.key === 'g') {
            e.preventDefault();
            $('#generateStandardTimeslotsModal').modal('show');
        }
    });

    console.log('✅ Timeslots management page initialized successfully');
});
</script>
@endsection
