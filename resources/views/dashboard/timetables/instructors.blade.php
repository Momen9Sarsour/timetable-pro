@extends('dashboard.layout')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        .page-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            padding: 2rem 0;
            margin: -1.5rem -1.5rem 2rem -1.5rem;
            border-radius: 0 0 1rem 1rem;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .page-header .subtitle {
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .filter-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }

        .filter-card .card-header {
            background: linear-gradient(45deg, #f8fafc, #e2e8f0);
            border: none;
            padding: 1.5rem;
        }

        .timetable-wrapper {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }

        .timetable-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #8b5cf6, #6366f1, #06b6d4);
        }

        .timetable-title {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1rem;
            color: #1f2937;
            position: relative;
        }

        .timetable-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #8b5cf6, #6366f1);
            border-radius: 2px;
        }

        .instructor-info {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .instructor-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }

        .download-section {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid #e2e8f0;
        }

        .btn-download {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-download-all {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 2rem;
            font-size: 1rem;
        }

        .btn-download-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
            color: white;
        }

        .stats-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #8b5cf6;
        }

        .stats-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .instructor-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .workload-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .workload-bar {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .workload-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .workload-light { background: #22c55e; }
        .workload-medium { background: #f59e0b; }
        .workload-heavy { background: #ef4444; }

        /* تحسين Select2 */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
        }

        /* Loading States */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            z-index: 10;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border: 0.3em solid #e5e7eb;
            border-top: 0.3em solid #8b5cf6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="page-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chalkboard-teacher me-3"></i>Instructor Timetables</h1>
                    <p class="subtitle mb-0">View and download teaching schedules for all instructors</p>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-download-all btn-lg" id="downloadAllBtn">
                        <i class="fas fa-download me-2"></i>Download All PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="data-entry-container">
        @include('dashboard.data-entry.partials._status_messages')

        {{-- Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">{{ count($timetablesByInstructor ?? []) }}</div>
                    <div class="stats-label">Active Instructors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        {{ collect($timetablesByInstructor ?? [])->sum(fn($data) => collect($data['schedule'])->flatten(2)->count()) }}
                    </div>
                    <div class="stats-label">Total Classes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">{{ count($instructorsForFilter ?? []) }}</div>
                    <div class="stats-label">Total Instructors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">{{ now()->format('Y') }}</div>
                    <div class="stats-label">Current Year</div>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card filter-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2 text-primary"></i>Filter Instructor Timetables
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.timetables.instructors') }}" method="GET" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="filter_instructor_id" class="form-label fw-semibold">Select Instructor:</label>
                            <select name="instructor_id" id="filter_instructor_id"
                                    class="form-select select2-filter"
                                    data-placeholder="Search and select an instructor...">
                                <option value=""></option>
                                @foreach($instructorsForFilter as $instructor)
                                    <option value="{{ $instructor->id }}"
                                            {{ $request->instructor_id == $instructor->id ? 'selected' : '' }}>
                                        {{ $instructor->instructor_name ?? optional($instructor->user)->name }}
                                        ({{ $instructor->instructor_no }}) -
                                        {{ optional($instructor->department)->department_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Apply Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('dashboard.timetables.instructors') }}"
                               class="btn btn-outline-secondary w-100">
                                <i class="fas fa-undo me-1"></i>Reset Filter
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Instructor Timetables Display --}}
        @forelse($timetablesByInstructor as $instructorId => $timetableData)
            @php
                $instructor = $timetableData['instructor'];
                $schedule = $timetableData['schedule'];
                $totalClasses = collect($schedule)->flatten(2)->count();

                // حساب الأحمال (workload)
                $workloadPercentage = min(($totalClasses / 25) * 100, 100); // افتراض 25 محاضرة = 100%
                $workloadClass = $totalClasses <= 10 ? 'light' : ($totalClasses <= 20 ? 'medium' : 'heavy');
            @endphp

            <div class="timetable-wrapper" id="timetable_instructor_{{ $instructorId }}">
                {{-- Loading Overlay --}}
                <div class="loading-overlay" id="loading_instructor_{{ $instructorId }}">
                    <div class="text-center">
                        <div class="loading-spinner"></div>
                        <p class="mt-2 text-muted">Generating PDF...</p>
                    </div>
                </div>

                {{-- Instructor Information --}}
                <div class="instructor-card">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="instructor-avatar">
                                {{ strtoupper(substr($instructor->instructor_name ?? optional($instructor->user)->name, 0, 2)) }}
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="timetable-title mb-2">
                                {{ $instructor->instructor_name ?? optional($instructor->user)->name }}
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-id-card me-2"></i>ID: {{ $instructor->instructor_no }}
                                @if($instructor->department)
                                    <span class="ms-3">
                                        <i class="fas fa-building me-2"></i>{{ $instructor->department->department_name }}
                                    </span>
                                @endif
                            </div>
                            <div class="workload-indicator">
                                <span class="text-muted small">Teaching Load:</span>
                                <div class="workload-bar">
                                    <div class="workload-fill workload-{{ $workloadClass }}"
                                         style="width: {{ $workloadPercentage }}%"></div>
                                </div>
                                <span class="badge bg-{{ $workloadClass === 'light' ? 'success' : ($workloadClass === 'medium' ? 'warning' : 'danger') }}">
                                    {{ $totalClasses }} Classes
                                </span>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <div class="stats-number" style="font-size: 1.5rem;">{{ $totalClasses }}</div>
                            <div class="stats-label">Weekly Classes</div>
                        </div>
                    </div>
                </div>

                {{-- Timetable Grid --}}
                @include('dashboard.timetables.partials._timetable_grid', [
                    'schedule' => $schedule,
                    'timeslots' => $timeslots
                ])

                {{-- Download Section --}}
                <div class="download-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="fas fa-clock me-1"></i>Generated: {{ now()->format('M d, Y \a\t H:i') }}
                            <span class="ms-3">
                                <i class="fas fa-chart-bar me-1"></i>Classes: {{ $totalClasses }}
                            </span>
                            <span class="ms-3">
                                <i class="fas fa-percentage me-1"></i>Load: {{ number_format($workloadPercentage, 1) }}%
                            </span>
                        </div>
                        <button type="button" class="btn btn-download"
                                onclick="downloadInstructorTimetablePDF('{{ $instructorId }}', '{{ addslashes($instructor->instructor_name ?? optional($instructor->user)->name) }}')">
                            <i class="fas fa-file-pdf me-2"></i>Download PDF
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                @if(request()->has('instructor_id'))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No scheduled classes found for the selected instructor.
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Use the filter above to select a specific instructor, or view all instructor timetables.
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 with search focus
            $('.select2-filter').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true,
                width: '100%'
            });

            // Auto-focus on Instructor select when opened
            $('#filter_instructor_id').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });
        });

        // PDF Download Functions for Instructors
        function downloadInstructorTimetablePDF(instructorId, instructorName) {
            const loadingOverlay = document.getElementById('loading_instructor_' + instructorId);
            const timetableElement = document.getElementById('timetable_instructor_' + instructorId);

            if (!timetableElement) {
                alert('Instructor timetable not found!');
                return;
            }

            // Show loading
            loadingOverlay.style.display = 'flex';

            // Use html2canvas to capture the timetable
            html2canvas(timetableElement, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false,
                onclone: function(clonedDoc) {
                    // Hide loading overlay in cloned document
                    const clonedLoading = clonedDoc.getElementById('loading_instructor_' + instructorId);
                    if (clonedLoading) {
                        clonedLoading.style.display = 'none';
                    }
                }
            }).then(function(canvas) {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('l', 'mm', 'a4'); // Landscape orientation

                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 280; // A4 landscape width minus margins
                const pageHeight = 200; // A4 landscape height minus margins
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                let heightLeft = imgHeight;
                let position = 10;

                // Add title
                pdf.setFontSize(16);
                pdf.setFont(undefined, 'bold');
                pdf.text('Instructor Timetable: ' + instructorName, 150, 20, { align: 'center' });

                position = 30;

                // Add image
                pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // Add new pages if needed
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight + 10;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // Add footer
                const pageCount = pdf.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    pdf.setPage(i);
                    pdf.setFontSize(8);
                    pdf.setFont(undefined, 'normal');
                    pdf.text(
                        'Generated on ' + new Date().toLocaleString() + ' | Page ' + i + ' of ' + pageCount,
                        150, 205,
                        { align: 'center' }
                    );
                }

                // Download the PDF
                const fileName = instructorName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_timetable.pdf';
                pdf.save(fileName);

                // Hide loading
                loadingOverlay.style.display = 'none';
            }).catch(function(error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
                loadingOverlay.style.display = 'none';
            });
        }

        function downloadAllInstructorTimetables() {
            const downloadBtn = document.getElementById('downloadAllBtn');
            const originalText = downloadBtn.innerHTML;

            // Show loading state
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDFs...';
            downloadBtn.disabled = true;

            const timetables = document.querySelectorAll('[id^="timetable_instructor_"]');
            let completed = 0;
            const total = timetables.length;

            if (total === 0) {
                alert('No instructor timetables to download!');
                downloadBtn.innerHTML = originalText;
                downloadBtn.disabled = false;
                return;
            }

            // Download each timetable with delay
            timetables.forEach(function(timetable, index) {
                setTimeout(function() {
                    const instructorId = timetable.id.replace('timetable_instructor_', '');
                    const titleElement = timetable.querySelector('.timetable-title');
                    const instructorName = titleElement ? titleElement.textContent.trim() : 'Instructor_' + (index + 1);

                    downloadInstructorTimetablePDF(instructorId, instructorName);

                    completed++;

                    // Update button text
                    downloadBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>Generating... (${completed}/${total})`;

                    // Reset button when all completed
                    if (completed === total) {
                        setTimeout(function() {
                            downloadBtn.innerHTML = originalText;
                            downloadBtn.disabled = false;
                        }, 1000);
                    }
                }, index * 1500); // Stagger downloads by 1.5 seconds
            });
        }

        // Attach download all function to button
        document.getElementById('downloadAllBtn').addEventListener('click', downloadAllInstructorTimetables);

        // Enhanced form validation
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
            submitBtn.disabled = true;

            // Re-enable after 3 seconds as fallback
            setTimeout(function() {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    </script>
@endpush
