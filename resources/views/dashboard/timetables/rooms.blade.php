@extends('dashboard.layout')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .select2-container--bootstrap-5 .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; font-size: 0.875rem; }
    /* نفس ستايل الجدول من صفحة الشعب */
    .timetable-wrapper { border: 2px solid #333; margin-bottom: 2rem; padding: 0.5rem; border-radius: 5px; }
    .timetable-title { font-size: 1.8rem; font-weight: bold; text-align: center; margin-bottom: 1rem; }
    .timetable-header-row { background-color: #f2f2f2; border-top: 2px solid #333; border-bottom: 2px solid #333; }
    .timetable { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.8rem; }
    .timetable th, .timetable td { border: 1px solid #ccc; padding: 4px; text-align: center; vertical-align: middle; min-height: 70px; }
    .timetable .day-col { font-weight: bold; font-size: 1.1rem; width: 8%; background-color: #f2f2f2; }
    .timetable .time-slot-header { font-size: 0.7rem; font-weight: normal; border-bottom: 1px solid #aaa; padding-bottom: 2px; margin-bottom: 2px; }
    .timetable .time-slot-number { font-weight: bold; }
    .timetable .event-block { background-color: #e9fff2; border: 1px solid #ffdccb; border-radius: 0; font-size: 0.75rem; height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 2px; }
    .event-subject { font-weight: bold; font-size: 0.8rem; margin-bottom: 2px; }
    .event-details { color: #555555; line-height: 1.2; }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <h1 class="data-entry-header mb-4">Room Timetables</h1>
        @include('dashboard.data-entry.partials._status_messages')

        {{-- قسم الفلاتر --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('dashboard.timetables.rooms') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label for="filter_room_id" class="form-label form-label-sm">Filter by Room:</label>
                            <select name="room_id" id="filter_room_id" class="form-select form-select-sm select2-filter" data-placeholder="View All Rooms">
                                <option value=""></option>
                                @foreach($roomsForFilter as $room)
                                    <option value="{{ $room->id }}" {{ $request->room_id == $room->id ? 'selected' : '' }}>
                                        {{ $room->room_no }} - {{ $room->room_name ?? optional($room->roomType)->room_type_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- يمكنك إضافة فلتر حسب نوع القاعة (Room Type) هنا --}}
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                        </div>
                         <div class="col-md-auto">
                            <a href="{{ route('dashboard.timetables.rooms') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- عرض الجداول --}}
        @forelse($timetablesByRoom as $timetableData)
            @php
                $room = $timetableData['room'];
                $schedule = $timetableData['schedule'];
            @endphp
            <div class="timetable-wrapper">
                <div class="timetable-title">
                    Timetable for Room: {{ $room->room_no }}
                    <small class="d-block text-muted fs-6">{{ $room->room_name ?? optional($room->roomType)->room_type_name }} (Capacity: {{ $room->room_size }})</small>
                </div>

                @include('dashboard.timetables.partials._timetable_grid', ['schedule' => $schedule, 'timeslots' => $timeslots])

                 <div class="d-flex justify-content-between align-items-center mt-2">
                     <div class="text-muted small">Generated: {{ now()->format('Y-m-d') }}</div>
                     <a href="#" class="btn btn-sm btn-outline-success"><i class="fas fa-file-download me-1"></i> Export Timetable</a>
                 </div>
            </div>
        @empty
             @if(request()->has('room_id'))
                 <div class="alert alert-warning">No scheduled classes found for the selected room.</div>
             @else
                 <div class="alert alert-info">Displaying timetables for all rooms with scheduled classes. Use the filter to select a specific room.</div>
             @endif
             @if(empty($timetablesByRoom) && !request()->has('room_id'))
                <div class="alert alert-secondary">No room timetables available to display.</div>
             @endif
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-filter').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: $(this).data('placeholder')
        });
    });
</script>
@endpush
