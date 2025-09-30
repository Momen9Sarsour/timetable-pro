@extends('dashboard.layout')

@section('title', 'Plan Groups Generation - New GA System')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-users text-primary me-2"></i>
                        Plan Groups Generation
                    </h1>
                    <p class="text-muted mb-0">Generate and manage student groups for genetic algorithm scheduling</p>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('new-algorithm.populations.index') }}">New GA System</a></li>
                        <li class="breadcrumb-item active">Plan Groups</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Prerequisites Check -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-double me-2"></i>
                        Prerequisites Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-layer-group text-primary me-2"></i>
                                <strong>Sections Available:</strong>
                                <span class="ms-2 badge bg-primary" id="sections-count">Checking...</span>
                            </div>
                            <small class="text-muted">Sections must be generated before creating plan groups</small>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-graduation-cap text-success me-2"></i>
                                <strong>Expected Counts:</strong>
                                <span class="ms-2 badge bg-success" id="expected-counts">Checking...</span>
                            </div>
                            <small class="text-muted">Student count forecasts for each plan/level</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Groups
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-groups">
                                {{ $stats['total_groups'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Average Group Size
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-group-size">
                                {{ number_format($stats['average_group_size'] ?? 0, 1) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Plans Covered
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="plans-covered">
                                {{ count($stats['groups_by_plan'] ?? []) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Subjects Grouped
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="subjects-grouped">
                                {{ count($stats['groups_by_subject'] ?? []) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-2"></i>
                        Plan Groups Generation Controls
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="refreshStats()">
                                <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                                Refresh Statistics
                            </a>
                            <a class="dropdown-item" href="#" onclick="viewGroupsByContext()">
                                <i class="fas fa-eye fa-sm fa-fw mr-2 text-gray-400"></i>
                                View Groups by Context
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" onclick="confirmClearGroups()">
                                <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>
                                Clear All Groups
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('new-algorithm.plan-groups.generate') }}" method="POST" id="generateForm">
                        @csrf

                        <!-- Generation Options -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="clear_existing" name="clear_existing" value="1">
                                    <label class="form-check-label" for="clear_existing">
                                        <i class="fas fa-trash text-danger me-1"></i>
                                        Clear existing plan groups
                                    </label>
                                </div>
                                <small class="text-muted">This will delete all current groups and create new ones</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="run_in_background" name="run_in_background" value="1">
                                    <label class="form-check-label" for="run_in_background">
                                        <i class="fas fa-cloud text-info me-1"></i>
                                        Run in background
                                    </label>
                                </div>
                                <small class="text-muted">Process will run in background queue</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-lg" id="generateBtn">
                                <i class="fas fa-play me-2"></i>
                                Generate Plan Groups
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="checkPrerequisites()">
                                <i class="fas fa-check-circle me-2"></i>
                                Check Prerequisites
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="refreshStats()">
                                <i class="fas fa-sync me-2"></i>
                                Refresh Stats
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Groups by Plan Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table me-2"></i>
                        Groups Distribution by Plan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>Plan ID</th>
                                    <th>Level</th>
                                    <th>Semester</th>
                                    <th>Subject</th>
                                    <th class="text-end">Groups Count</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="groups-table-body">
                                @if(isset($stats['groups_by_plan']) && count($stats['groups_by_plan']) > 0)
                                    {{-- @foreach($stats['groups_by_plan'] as $planGroup) --}}
                                     @foreach($stats['groups_by_plan_with_subjects'] as $planGroup)
                                    <tr>
                                        <td><span class="badge bg-primary">{{ $planGroup->plan_id }}</span></td>
                                        <td>Level {{ $planGroup->plan_level }}</td>
                                        <td>Semester {{ $planGroup->plan_semester }}</td>
                                        {{-- <td> {{ $planGroup->subjects->subject_no }}</td> --}}
                                        <td>
                                            <span class="text-muted small" title="{{ $planGroup->subjects }}">
                                                {{ Str::limit($planGroup->subjects, 40) }}
                                            </span>
                                        </td>
                                        <td class="text-end"><strong>{{ $planGroup->groups_count }}</strong></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info" onclick="viewGroupDetails({{ $planGroup->plan_id }}, {{ $planGroup->plan_level }}, {{ $planGroup->plan_semester }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No plan groups generated yet</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        Plan Groups Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-lightbulb me-2"></i>
                            How it works
                        </h6>
                        <p class="mb-2">The plan groups generation process:</p>
                        <ol class="mb-0">
                            <li>Groups sections by plan context</li>
                            <li>Calculates minimum section load</li>
                            <li>Distributes students into groups</li>
                            <li>Assigns sections to each group</li>
                            <li>Ensures no seat capacity violations</li>
                        </ol>
                    </div>

                    <div class="mt-3">
                        <h6 class="text-primary">Prerequisites:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Sections must be generated first</li>
                            <li><i class="fas fa-check text-success me-2"></i> Expected student counts configured</li>
                            <li><i class="fas fa-check text-success me-2"></i> Section loads properly set</li>
                            <li><i class="fas fa-check text-success me-2"></i> Plan subjects with sections</li>
                        </ul>
                    </div>

                    <div class="mt-3">
                        <h6 class="text-primary">Generation Rules:</h6>
                        <ul class="list-unstyled small">
                            <li><i class="fas fa-arrow-right text-info me-2"></i> Groups based on min section load</li>
                            <li><i class="fas fa-arrow-right text-info me-2"></i> Seats allocated per group</li>
                            <li><i class="fas fa-arrow-right text-info me-2"></i> No overlapping assignments</li>
                            <li><i class="fas fa-arrow-right text-info me-2"></i> Balanced distribution</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Groups by Subject -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>
                        Top Subjects by Groups
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($stats['groups_by_subject']) && count($stats['groups_by_subject']) > 0)
                        @foreach(array_slice($stats['groups_by_subject']->toArray(), 0, 5) as $subjectGroup)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                {{-- <span class="text-primary fw-bold">Subject {{ $subjectGroup->subject_id }}</span> --}}
                            </div>
                            {{-- <span class="badge bg-success rounded-pill">{{ $subjectGroup->groups_count }}</span> --}}
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No groups data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Processing...</span>
                </div>
                <h5>Generating Plan Groups...</h5>
                <p class="text-muted mb-0">Please wait while we distribute students into groups</p>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirm Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear all existing plan groups? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="clearGroups()">Clear Groups</button>
            </div>
        </div>
    </div>
</div>

<!-- Group Details Modal -->
<div class="modal fade" id="groupDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>
                    Group Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="groupDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.bg-gradient-info {
    background: linear-gradient(87deg, #36b9cc 0, #3699cc 100%) !important;
}
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    checkPrerequisites();

    $('#generateForm').on('submit', function() {
        $('#loadingModal').modal('show');
    });
});

function checkPrerequisites() {
    $.get('{{ route("new-algorithm.sections.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#sections-count').removeClass('bg-danger').addClass('bg-primary').text(response.data.total_sections);
            } else {
                $('#sections-count').removeClass('bg-primary').addClass('bg-danger').text('0');
            }
        });
}

function refreshStats() {
    $.get('{{ route("new-algorithm.plan-groups.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#total-groups').text(response.data.total_groups);
                $('#avg-group-size').text(parseFloat(response.data.average_group_size).toFixed(1));
                $('#plans-covered').text(response.data.groups_by_plan.length);
                $('#subjects-grouped').text(response.data.groups_by_subject.length);

                toastr.success('Statistics refreshed successfully');
            }
        })
        .fail(function() {
            toastr.error('Failed to refresh statistics');
        });
}

function confirmClearGroups() {
    $('#confirmModal').modal('show');
}

function clearGroups() {
    $('#confirmModal').modal('hide');

    $.ajax({
        url: '{{ route("new-algorithm.plan-groups.clear") }}',
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            toastr.success(response.message);
            refreshStats();
            location.reload();
        }
    })
    .fail(function() {
        toastr.error('Failed to clear plan groups');
    });
}

function viewGroupDetails(planId, planLevel, planSemester) {
    $('#groupDetailsModal').modal('show');
    $('#groupDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    $.post('{{ route("new-algorithm.plan-groups.by-context") }}', {
        plan_id: planId,
        plan_level: planLevel,
        plan_semester: planSemester,
        _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
        if (response.success && response.data.length > 0) {
            let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Group No</th><th>Section</th><th>Subject</th><th>Group Size</th></tr></thead><tbody>';

            response.data.forEach(function(group) {
                html += '<tr>';
                html += '<td><span class="badge bg-primary">' + group.group_no + '</span></td>';
                html += '<td>Section ' + group.section_id + '</td>';
                html += '<td>' + (group.section && group.section.plan_subject ? group.section.plan_subject.subject.subject_name : 'N/A') + '</td>';
                html += '<td>' + group.group_size + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            $('#groupDetailsContent').html(html);
        } else {
            $('#groupDetailsContent').html('<p class="text-center text-muted">No groups found for this context</p>');
        }
    })
    .fail(function() {
        $('#groupDetailsContent').html('<p class="text-center text-danger">Failed to load group details</p>');
    });
}

function viewGroupsByContext() {
    // You can implement a filter interface here
    toastr.info('Filter interface coming soon');
}
</script>
@endpush
