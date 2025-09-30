@extends('dashboard.layout')

@section('title', 'Section Generation - New GA System')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-layer-group text-primary me-2"></i>
                        Section Generation
                    </h1>
                    <p class="text-muted mb-0">Generate and manage academic sections for the genetic algorithm</p>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('new-algorithm.populations.index') }}">New GA System</a></li>
                        <li class="breadcrumb-item active">Sections</li>
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

    <!-- System Requirements Check -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>
                        System Requirements
                    </h5>
                </div>
                <div class="card-body">
                    <div id="requirements-status" class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Checking...</span>
                        </div>
                        <span class="text-muted">Checking system requirements...</span>
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
                                Total Sections
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-sections">
                                {{ $stats['total_sections'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
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
                                Theory Sections
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="theory-sections">
                                {{ $stats['theory_sections'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
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
                                Practical Sections
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="practical-sections">
                                {{ $stats['practical_sections'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flask fa-2x text-gray-300"></i>
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
                                Plans Coverage
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="plans-count">
                                {{ count($stats['sections_by_plan'] ?? []) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Action Panel -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-2"></i>
                        Section Generation Controls
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
                            <a class="dropdown-item text-danger" href="#" onclick="confirmClearSections()">
                                <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>
                                Clear All Sections
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('new-algorithm.sections.generate') }}" method="POST" id="generateForm">
                        @csrf

                        <!-- Generation Options -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="clear_existing" name="clear_existing" value="1">
                                    <label class="form-check-label" for="clear_existing">
                                        <i class="fas fa-trash text-danger me-1"></i>
                                        Clear existing sections before generation
                                    </label>
                                </div>
                                <small class="text-muted">This will delete all current sections and create new ones</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="run_in_background" name="run_in_background" value="1">
                                    <label class="form-check-label" for="run_in_background">
                                        <i class="fas fa-cloud text-info me-1"></i>
                                        Run in background
                                    </label>
                                </div>
                                <small class="text-muted">Process will run in background (check logs for progress)</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-lg" id="generateBtn">
                                <i class="fas fa-play me-2"></i>
                                Generate Sections
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="validateRequirements()">
                                <i class="fas fa-check-circle me-2"></i>
                                Validate Requirements
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="refreshStats()">
                                <i class="fas fa-sync me-2"></i>
                                Refresh Stats
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        Generation Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-lightbulb me-2"></i>
                            How it works
                        </h6>
                        <p class="mb-2">The section generation process:</p>
                        <ol class="mb-0">
                            <li>Reads plan subjects and expected student counts</li>
                            <li>Assigns appropriate instructors to subjects</li>
                            <li>Creates theory and practical sections</li>
                            <li>Determines activity types based on subject codes</li>
                        </ol>
                    </div>

                    <div class="mt-3">
                        <h6 class="text-primary">Requirements:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Plan subjects must exist</li>
                            <li><i class="fas fa-check text-success me-2"></i> Expected student counts configured</li>
                            <li><i class="fas fa-check text-success me-2"></i> Instructors assigned to subjects</li>
                            <li><i class="fas fa-check text-success me-2"></i> Subjects have proper load values</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>
                        Sections by Plan
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($stats['sections_by_plan']) && count($stats['sections_by_plan']) > 0)
                        @foreach($stats['sections_by_plan'] as $planStat)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="text-primary fw-bold">Plan {{ $planStat->plan_id }}</span>
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $planStat->sections_count }}</span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No sections generated yet</p>
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
                <h5>Generating Sections...</h5>
                <p class="text-muted mb-0">Please wait while we process your request</p>
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
                <p>Are you sure you want to clear all existing sections? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="clearSections()">Clear Sections</button>
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
.bg-gradient-primary {
    background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
}
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    validateRequirements();

    $('#generateForm').on('submit', function() {
        $('#loadingModal').modal('show');
    });
});

function validateRequirements() {
    $('#requirements-status').html('<div class="spinner-border spinner-border-sm text-primary me-2" role="status"><span class="visually-hidden">Checking...</span></div><span class="text-muted">Checking system requirements...</span>');

    $.get('{{ route("new-algorithm.validate-requirements") }}')
        .done(function(response) {
            if (response.success) {
                $('#requirements-status').html('<i class="fas fa-check-circle text-success me-2"></i><span class="text-success">All requirements met</span>');
            } else {
                let errorHtml = '<i class="fas fa-exclamation-triangle text-danger me-2"></i><span class="text-danger">Requirements not met:</span><ul class="mt-2 mb-0">';
                response.errors.forEach(function(error) {
                    errorHtml += '<li class="text-danger">' + error + '</li>';
                });
                errorHtml += '</ul>';
                $('#requirements-status').html(errorHtml);
            }
        })
        .fail(function() {
            $('#requirements-status').html('<i class="fas fa-exclamation-triangle text-warning me-2"></i><span class="text-warning">Could not check requirements</span>');
        });
}

function refreshStats() {
    $.get('{{ route("new-algorithm.sections.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#total-sections').text(response.data.total_sections);
                $('#theory-sections').text(response.data.theory_sections);
                $('#practical-sections').text(response.data.practical_sections);
                $('#plans-count').text(response.data.sections_by_plan.length);

                toastr.success('Statistics refreshed successfully');
            }
        })
        .fail(function() {
            toastr.error('Failed to refresh statistics');
        });
}

function confirmClearSections() {
    $('#confirmModal').modal('show');
}

function clearSections() {
    $('#confirmModal').modal('hide');

    $.ajax({
        url: '{{ route("new-algorithm.sections.clear") }}',
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            toastr.success(response.message);
            refreshStats();
        }
    })
    .fail(function() {
        toastr.error('Failed to clear sections');
    });
}
</script>
@endpush
