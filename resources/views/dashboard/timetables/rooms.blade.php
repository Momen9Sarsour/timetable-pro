@extends('dashboard.layout')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        .page-header {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
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
            background: linear-gradient(90deg, #06b6d4, #0891b2, #0e7490);
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
            background: linear-gradient(90deg, #06b6d4, #0891b2);
            border-radius: 2px;
        }

        .room-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #10b981;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .room-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }

        .capacity-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .capacity-bar {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .capacity-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .capacity-small { background: #22c55e; }
        .capacity-medium { background: #f59e0b; }
        .capacity-large { background: #8b5cf6; }
        .capacity-huge { background: #ef4444; }

        .utilization-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            border: 2px solid #06b6d4;
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
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 2rem;
            font-size: 1rem;
        }

        .btn-download-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
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
            color: #06b6d4;
        }

        .stats-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .room-type-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .room-type-lab { background: #fef3c7; color: #92400e; }
        .room-type-lecture { background: #dbeafe; color: #1e40af; }
        .room-type-tutorial { background: #ecfdf5; color: #166534; }
        .room-type-computer { background: #f3e8ff; color: #7c3aed; }

        /* تحسين Select2 */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #06b6d4;
            box-shadow: 0 0 0 0.2rem rgba(6, 182, 212, 0.25);
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
            border-top: 0.3em solid #06b6d4;
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
                    <h1><i class="fas fa-door-open me-3"></i>Room Timetables</h1>
                    <p class="subtitle mb-0">View and download room utilization schedules for all facilities</p>
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
                    <div class="stats-number">{{ count($timetablesByRoom ?? []) }}</div>
                    <div class="stats-label">Active Rooms</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        {{ collect($timetablesByRoom ?? [])->sum(fn($data) => collect($data['schedule'])->flatten(2)->count()) }}
                    </div>
                    <div class="stats-label">Total Bookings</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">{{ count($roomsForFilter ?? []) }}</div>
                    <div class="stats-label">Total Rooms</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        {{ collect($timetablesByRoom ?? [])->avg(fn($data) => collect($data['schedule'])->flatten(2)->count()) ? number_format(collect($timetablesByRoom ?? [])->avg(fn($data) => collect($data['schedule'])->flatten(2)->count()), 1) : 0 }}
                    </div>
                    <div class="stats-label">Avg Utilization</div>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card filter-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2 text-primary"></i>Filter Room Timetables
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.timetables.rooms') }}" method="GET" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="filter_room_id" class="form-label fw-semibold">Select Room:</label>
                            <select name="room_id" id="filter_room_id"
                                    class="form-select select2-filter"
                                    data-placeholder="Search and select a room...">
                                <option value=""></option>
                                @foreach($roomsForFilter as $room)
                                    <option value="{{ $room->id }}"
                                            {{ $request->room_id == $room->id ? 'selected' : '' }}>
                                        {{ $room->room_no }} - {{ $room->room_name ?? optional($room->roomType)->room_type_name }}
                                        (Capacity: {{ $room->room_size }})
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
                            <a href="{{ route('dashboard.timetables.rooms') }}"
                               class="btn btn-outline-secondary w-100">
                                <i class="fas fa-undo me-1"></i>Reset Filter
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Room Timetables Display --}}
        @forelse($timetablesByRoom as $roomId => $timetableData)
            @php
                $room = $timetableData['room'];
                $schedule = $timetableData['schedule'];
                $totalBookings = collect($schedule)->flatten(2)->count();

                // حساب نسبة الاستخدام (افتراض 40 فترة زمنية أسبوعياً = 100%)
                $utilizationPercentage = min(($totalBookings / 40) * 100, 100);

                // تحديد نوع السعة
                $capacity = $room->room_size ?? 0;
                $capacityClass = $capacity <= 20 ? 'small' : ($capacity <= 50 ? 'medium' : ($capacity <= 100 ? 'large' : 'huge'));

                // تحديد نوع القاعة
                $roomTypeName = $room->room_name ?? optional($room->roomType)->room_type_name ?? 'General';
                $roomTypeClass = 'room-type-' . strtolower(str_replace(' ', '', $roomTypeName));
                if (!in_array($roomTypeClass, ['room-type-lab', 'room-type-lecture', 'room-type-tutorial', 'room-type-computer'])) {
                    $roomTypeClass = 'room-type-lecture';
                }
            @endphp

            <div class="timetable-wrapper" id="timetable_room_{{ $roomId }}">
                {{-- Loading Overlay --}}
                <div class="loading-overlay" id="loading_room_{{ $roomId }}">
                    <div class="text-center">
                        <div class="loading-spinner"></div>
                        <p class="mt-2 text-muted">Generating PDF...</p>
                    </div>
                </div>

                {{-- Utilization Badge --}}
                <div class="utilization-badge">
                    <div class="text-center">
                        <div class="fw-bold text-primary">{{ number_format($utilizationPercentage, 1) }}%</div>
                        <small class="text-muted">Utilized</small>
                    </div>
                </div>

                {{-- Room Information --}}
                <div class="room-card">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="room-icon">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <span class="room-type-badge {{ $roomTypeClass }}">
                                {{ $roomTypeName }}
                            </span>
                        </div>
                        <div class="col-md-8">
                            <div class="timetable-title mb-2">
                                Room {{ $room->room_no }}
                            </div>
                            <div class="text-muted mb-2">
                                <i class="fas fa-users me-2"></i>Capacity: {{ $capacity }} students
                                @if($room->room_name && $room->room_name !== optional($room->roomType)->room_type_name)
                                    <span class="ms-3">
                                        <i class="fas fa-tag me-2"></i>{{ $room->room_name }}
                                    </span>
                                @endif
                            </div>
                            <div class="capacity-indicator">
                                <span class="text-muted small">Room Size:</span>
                                <div class="capacity-bar">
                                    <div class="capacity-fill capacity-{{ $capacityClass }}"
                                         style="width: {{ min(($capacity / 150) * 100, 100) }}%"></div>
                                </div>
                                <span class="badge bg-{{ $capacityClass === 'small' ? 'success' : ($capacityClass === 'medium' ? 'warning' : ($capacityClass === 'large' ? 'primary' : 'danger')) }}">
                                    {{ ucfirst($capacityClass) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <div class="stats-number" style="font-size: 1.5rem;">{{ $totalBookings }}</div>
                            <div class="stats-label">Weekly Bookings</div>
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
                                <i class="fas fa-calendar-check me-1"></i>Bookings: {{ $totalBookings }}
                            </span>
                            <span class="ms-3">
                                <i class="fas fa-percentage me-1"></i>Utilization: {{ number_format($utilizationPercentage, 1) }}%
                            </span>
                        </div>
                        <button type="button" class="btn btn-download"
                                onclick="downloadRoomTimetablePDF('{{ $roomId }}', 'Room {{ addslashes($room->room_no) }}')">
                            <i class="fas fa-file-pdf me-2"></i>Download PDF
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                @if(request()->has('room_id'))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No scheduled classes found for the selected room.
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Use the filter above to select a specific room, or view all room timetables.
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

            // Auto-focus on Room select when opened
            $('#filter_room_id').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });
        });

        // PDF Download Functions for Rooms
        function downloadRoomTimetablePDF(roomId, roomName) {
            const loadingOverlay = document.getElementById('loading_room_' + roomId);
            const timetableElement = document.getElementById('timetable_room_' + roomId);

            if (!timetableElement) {
                alert('Room timetable not found!');
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
                    const clonedLoading = clonedDoc.getElementById('loading_room_' + roomId);
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
                pdf.text('Room Timetable: ' + roomName, 150, 20, { align: 'center' });

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
                const fileName = roomName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_timetable.pdf';
                pdf.save(fileName);

                // Hide loading
                loadingOverlay.style.display = 'none';
            }).catch(function(error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
                loadingOverlay.style.display = 'none';
            });
        }

        function downloadAllRoomTimetables() {
            const downloadBtn = document.getElementById('downloadAllBtn');
            const originalText = downloadBtn.innerHTML;

            // Show loading state
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDFs...';
            downloadBtn.disabled = true;

            const timetables = document.querySelectorAll('[id^="timetable_room_"]');
            let completed = 0;
            const total = timetables.length;

            if (total === 0) {
                alert('No room timetables to download!');
                downloadBtn.innerHTML = originalText;
                downloadBtn.disabled = false;
                return;
            }

            // Download each timetable with delay
            timetables.forEach(function(timetable, index) {
                setTimeout(function() {
                    const roomId = timetable.id.replace('timetable_room_', '');
                    const titleElement = timetable.querySelector('.timetable-title');
                    const roomName = titleElement ? titleElement.textContent.trim() : 'Room_' + (index + 1);

                    downloadRoomTimetablePDF(roomId, roomName);

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
        document.getElementById('downloadAllBtn').addEventListener('click', downloadAllRoomTimetables);

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
