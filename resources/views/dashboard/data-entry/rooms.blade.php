@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="page-title mb-1">
                        <i class="fas fa-door-open text-primary me-2"></i>
                        Manage Classrooms
                    </h4>
                    <p class="text-muted mb-0">Create and manage physical classrooms and learning spaces</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add New Classroom</span>
                    </button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUploadRoomsModal">
                        <i class="fas fa-file-excel me-1"></i>
                        <span class="d-none d-sm-inline">Bulk Upload</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    @include('dashboard.data-entry.partials._status_messages')

    @if (session('skipped_details'))
        <div class="alert alert-warning d-flex align-items-start mb-4">
            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
            <div>
                <strong class="d-block mb-1">Skipped Rows During Upload:</strong>
                <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                    @foreach (session('skipped_details') as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list text-muted me-2"></i>
                            Classrooms List
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $rooms->total() }} Total</span>
                    </div>
                </div>

                <div class="card-body pt-3">
                    @if($rooms->count() > 0)
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">#</th>
                                        <th class="border-0">Room Info</th>
                                        <th class="border-0">Type</th>
                                        <th class="border-0 text-center">Capacity</th>
                                        <th class="border-0 text-center">Gender</th>
                                        <th class="border-0">Branch</th>
                                        <th class="border-0 text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rooms as $index => $room)
                                        @php
                                            $genderColor = match($room->room_gender) {
                                                'Male' => 'info',
                                                'Female' => 'danger',
                                                'Mixed' => 'success',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <tr class="border-bottom">
                                            <td class="text-center text-muted">
                                                <small>{{ $rooms->firstItem() + $index }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="room-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-door-open"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $room->room_no }}</div>
                                                        @if($room->room_name)
                                                            <small class="text-muted">{{ $room->room_name }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    {{ $room->roomType->room_type_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    <i class="fas fa-users me-1"></i>{{ $room->room_size }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $genderColor }} bg-opacity-10 text-{{ $genderColor }}">
                                                    @if($room->room_gender === 'Male')
                                                        <i class="fas fa-mars me-1"></i>{{ $room->room_gender }}
                                                    @elseif($room->room_gender === 'Female')
                                                        <i class="fas fa-venus me-1"></i>{{ $room->room_gender }}
                                                    @else
                                                        <i class="fas fa-venus-mars me-1"></i>{{ $room->room_gender }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                @if($room->room_branch)
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $room->room_branch }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editRoomModal-{{ $room->id }}"
                                                            title="Edit Classroom">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteRoomModal-{{ $room->id }}"
                                                            title="Delete Classroom">
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
                            @foreach ($rooms as $index => $room)
                                @php
                                    $genderColor = match($room->room_gender) {
                                        'Male' => 'info',
                                        'Female' => 'danger',
                                        'Mixed' => 'success',
                                        default => 'secondary'
                                    };
                                @endphp
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <div class="room-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fas fa-door-open"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="card-title mb-1">{{ $room->room_no }}</h6>
                                                        @if($room->room_name)
                                                            <small class="text-muted d-block">{{ $room->room_name }}</small>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-wrap gap-2 mb-2">
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                        {{ $room->roomType->room_type_name ?? 'N/A' }}
                                                    </span>
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        <i class="fas fa-users me-1"></i>{{ $room->room_size }} capacity
                                                    </span>
                                                    <span class="badge bg-{{ $genderColor }} bg-opacity-10 text-{{ $genderColor }}">
                                                        @if($room->room_gender === 'Male')
                                                            <i class="fas fa-mars me-1"></i>{{ $room->room_gender }}
                                                        @elseif($room->room_gender === 'Female')
                                                            <i class="fas fa-venus me-1"></i>{{ $room->room_gender }}
                                                        @else
                                                            <i class="fas fa-venus-mars me-1"></i>{{ $room->room_gender }}
                                                        @endif
                                                    </span>
                                                </div>

                                                @if($room->room_branch)
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $room->room_branch }}
                                                    </small>
                                                @endif
                                            </div>

                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editRoomModal-{{ $room->id }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteRoomModal-{{ $room->id }}">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between text-muted small">
                                            <span class="text-muted">#{{ $rooms->firstItem() + $index }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($rooms->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $rooms->links('pagination::bootstrap-5') }}
                            </div>
                        @endif

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-door-open text-muted opacity-50" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 text-muted">No Classrooms Found</h5>
                                <p class="text-muted mb-4">Start by adding classrooms to organize your physical learning spaces.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                                    <i class="fas fa-plus me-2"></i>Add First Classroom
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals for each room -->
    @foreach($rooms as $room)
        @include('dashboard.data-entry.partials._room_modals', ['room' => $room, 'roomTypes' => $roomTypes])
    @endforeach

    <!-- Include Add Room Modal -->
    @include('dashboard.data-entry.partials._room_modals', ['room' => null, 'roomTypes' => $roomTypes])
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-format room numbers
    const roomNoInputs = document.querySelectorAll('input[name="room_no"]');
    roomNoInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Convert to uppercase and remove spaces
            this.value = this.value.toUpperCase().replace(/\s+/g, '');
        });
    });

    // Auto-generate room name from room number (optional)
    const addRoomNoInput = document.getElementById('add_room_no');
    const addRoomNameInput = document.getElementById('add_room_name');

    if (addRoomNoInput && addRoomNameInput) {
        addRoomNoInput.addEventListener('input', function() {
            if (!addRoomNameInput.value.trim()) {
                const roomNo = this.value;
                if (roomNo) {
                    addRoomNameInput.placeholder = `e.g., Room ${roomNo}`;
                }
            }
        });
    }

    // Capacity validation
    const capacityInputs = document.querySelectorAll('input[name="room_size"]');
    capacityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 1) {
                this.setCustomValidity('Capacity must be at least 1');
            } else if (value > 1000) {
                this.setCustomValidity('Capacity seems too large. Please verify.');
            } else {
                this.setCustomValidity('');
            }
        });
    });
});
</script>
@endpush
