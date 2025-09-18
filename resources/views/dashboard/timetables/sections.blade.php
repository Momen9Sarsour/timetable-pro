@extends('dashboard.layout')

@push('styles')
    {{-- Select2 styles --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    {{-- Custom styles for timetable --}}
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
        }

        .timetable-title {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
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
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 2px;
        }

        .context-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1.5rem 0;
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
            color: #3b82f6;
        }

        .stats-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* تحسين Select2 */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
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
            border-top: 0.3em solid #3b82f6;
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
                        <h1><i class="fas fa-calendar-alt me-3"></i>Section Timetables</h1>
                        <p class="subtitle mb-0">View and download generated timetables for student sections</p>
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
                        <div class="stats-number">{{ count($timetablesByContext ?? []) }}</div>
                        <div class="stats-label">Contexts</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            {{ collect($timetablesByContext ?? [])->sum(fn($context) => count($context['timetables'])) }}
                        </div>
                        <div class="stats-label">Total Groups</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">{{ count($plans ?? []) }}</div>
                        <div class="stats-label">Academic Plans</div>
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
                        <i class="fas fa-filter me-2 text-primary"></i>Filter Section Timetables
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('dashboard.timetables.sections') }}" method="GET" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="filter_plan_id" class="form-label fw-semibold">Academic Plan</label>
                                <select class="form-select select2-filter" id="filter_plan_id" name="plan_id"
                                    data-placeholder="Search and select a plan...">
                                    <option value=""></option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}"
                                            {{ $request->plan_id == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->plan_no }} - {{ $plan->plan_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_plan_level" class="form-label fw-semibold">Level</label>
                                <select class="form-select" id="filter_plan_level" name="plan_level">
                                    <option value="">All Levels</option>
                                    @foreach ($levels as $level_option)
                                        <option value="{{ $level_option }}"
                                            {{ $request->plan_level == $level_option ? 'selected' : '' }}>
                                            Level {{ $level_option }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_plan_semester" class="form-label fw-semibold">Semester</label>
                                <select class="form-select" id="filter_semester" name="plan_semester">
                                    <option value="">All Semesters</option>
                                    @foreach ($semesters as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ $request->plan_semester == $key ? 'selected' : '' }}>
                                            {{ $value }} Semester
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_academic_year" class="form-label fw-semibold">Academic Year</label>
                                <select class="form-select" id="filter_academic_year" name="academic_year">
                                    <option value="">All Years</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year }}"
                                            {{ $request->academic_year == $year ? 'selected' : '' }}>
                                            {{ $year }}/{{ $year + 1 }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fas fa-search me-1"></i>Apply
                                    </button>
                                    <a href="{{ route('dashboard.timetables.sections') }}"
                                        class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-1"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Timetables Display --}}
            @forelse($timetablesByContext as $contextKey => $contextData)
                @php
                    $contextInfo = $contextData['info'];
                    $timetables = $contextData['timetables'];
                @endphp

                {{-- Context Information --}}
                <div class="context-info">
                    <div class="row align-items-center">
                        <div class="col-md-10">
                            <h6 class="mb-1 fw-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>Academic Context
                            </h6>
                            <div class="d-flex flex-wrap gap-3">
                                <span><strong>Plan:</strong> {{ optional($contextInfo['plan'])->plan_no }} - {{ optional($contextInfo['plan'])->plan_name }}</span>
                                <span><strong>Level:</strong> {{ $contextInfo['level'] }}</span>
                                <span><strong>Semester:</strong> {{ $contextInfo['semester'] }}</span>
                                <span><strong>Year:</strong> {{ $contextInfo['year'] }}</span>
                                @if($contextInfo['branch'])
                                    <span><strong>Branch:</strong> {{ $contextInfo['branch'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="badge bg-primary badge-lg">{{ count($timetables) }} Groups</span>
                        </div>
                    </div>
                </div>

                {{-- Individual Timetables --}}
                @foreach ($timetables as $timetableIndex => $timetableData)
                    @php
                        $title = $timetableData['title'];
                        $schedule = $timetableData['schedule'];
                        $timetableId = $contextKey . '_' . $timetableIndex;
                    @endphp

                    <div class="timetable-wrapper" id="timetable_{{ $timetableId }}">
                        {{-- Loading Overlay --}}
                        <div class="loading-overlay" id="loading_{{ $timetableId }}">
                            <div class="text-center">
                                <div class="loading-spinner"></div>
                                <p class="mt-2 text-muted">Generating PDF...</p>
                            </div>
                        </div>

                        {{-- Timetable Header --}}
                        <div class="d-flex justify-content-center align-items-start mb-3 align-items-center">
                            <div>
                                <div class="timetable-title">
                                    {{ optional($contextInfo['plan'])->plan_no }} {{ optional($contextInfo['plan'])->plan_name }}
                                    - Level {{ $contextInfo['level'] }} - {{ $title }}
                                </div>
                                <p class="text-muted text-center mb-0">
                                    Academic Year {{ $contextInfo['year'] }} | Semester {{ $contextInfo['semester'] }}
                                </p>
                            </div>
                        </div>

                        {{-- Timetable Grid --}}
                        @include('dashboard.timetables.partials._timetable_grid', [
                            'schedule' => $schedule,
                            'timeslots' => $timeslots,
                        ])

                        {{-- Download Section --}}
                        <div class="download-section">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i>Generated: {{ now()->format('M d, Y \a\t H:i') }}
                                    <span class="ms-3">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Classes: {{ collect($schedule)->flatten(2)->count() }}
                                    </span>
                                </div>
                                <button type="button" class="btn btn-download"
                                        onclick="downloadTimetablePDF('{{ $timetableId }}', '{{ $title }}')">
                                    <i class="fas fa-file-pdf me-2"></i>Download {{ $title }} PDF
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach

            @empty
                <div class="text-center py-5">
                    @if (request()->hasAny(['plan_id', 'plan_level', 'plan_semester', 'academic_year']))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No timetables found for the selected criteria. Try adjusting your filters.
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Use the filters above to display specific timetables, or download all available timetables.
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

            // Auto-focus on Academic Plan select when opened
            $('#filter_plan_id').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });

            // Auto-submit form when filters change (optional)
            $('.form-select').on('change', function() {
                if ($(this).val() !== '') {
                    // Optional: Auto-submit on change
                    // $('#filterForm').submit();
                }
            });

            // Smooth scrolling for timetables
            $('a[href^="#timetable"]').on('click', function(e) {
                e.preventDefault();
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });
        });

        // PDF Download Functions
        function downloadTimetablePDF(timetableId, title) {
            const loadingOverlay = document.getElementById('loading_' + timetableId);
            const timetableElement = document.getElementById('timetable_' + timetableId);

            if (!timetableElement) {
                alert('Timetable not found!');
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
                    const clonedLoading = clonedDoc.getElementById('loading_' + timetableId);
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
                pdf.text(title, 150, 20, { align: 'center' });

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
                const fileName = title.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_timetable.pdf';
                pdf.save(fileName);

                // Hide loading
                loadingOverlay.style.display = 'none';
            }).catch(function(error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
                loadingOverlay.style.display = 'none';
            });
        }

        function downloadAllTimetables() {
            const downloadBtn = document.getElementById('downloadAllBtn');
            const originalText = downloadBtn.innerHTML;

            // Show loading state
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDFs...';
            downloadBtn.disabled = true;

            const timetables = document.querySelectorAll('[id^="timetable_"]');
            let completed = 0;
            const total = timetables.length;

            if (total === 0) {
                alert('No timetables to download!');
                downloadBtn.innerHTML = originalText;
                downloadBtn.disabled = false;
                return;
            }

            // Download each timetable with delay
            timetables.forEach(function(timetable, index) {
                setTimeout(function() {
                    const timetableId = timetable.id.replace('timetable_', '');
                    const titleElement = timetable.querySelector('.timetable-title');
                    const title = titleElement ? titleElement.textContent.trim() : 'Timetable_' + (index + 1);

                    downloadTimetablePDF(timetableId, title);

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
        document.getElementById('downloadAllBtn').addEventListener('click', downloadAllTimetables);

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

        // Print function for individual timetables
        function printTimetable(timetableId) {
            const timetableElement = document.getElementById('timetable_' + timetableId);
            if (!timetableElement) return;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Timetable Print</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .timetable { width: 100%; border-collapse: collapse; }
                        .timetable th, .timetable td { border: 1px solid #000; padding: 8px; text-align: center; }
                        .timetable th { background-color: #f0f0f0; }
                        @media print { body { margin: 0; } .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    ${timetableElement.outerHTML}
                    <script>window.print(); window.close();</script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
@endpush
