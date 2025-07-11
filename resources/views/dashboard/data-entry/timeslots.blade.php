@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Manage Weekly Timeslots</h1>
             <div class="d-flex">
                {{-- زر توليد الفترات القياسية --}}
                <button class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#generateStandardTimeslotsModal">
                    <i class="fas fa-cogs me-1"></i> Generate Standard Timeslots
                </button>
                {{-- زر إضافة فترة فردية --}}
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTimeslotModal">
                    <i class="fas fa-plus me-1"></i> Add New Timeslot
                </button>
             </div>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        <div class="card shadow-sm"> <div class="card-body"> <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="table-light">
                    <tr><th>#</th><th>Day</th><th>Start Time</th><th>End Time</th><th>Duration</th><th>Scheduled Classes</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($timeslots as $index => $timeslot)
                    @php
                        $startTime = \Carbon\Carbon::parse($timeslot->start_time);
                        $endTime = \Carbon\Carbon::parse($timeslot->end_time);
                        $duration = $startTime->diffInMinutes($endTime);
                    @endphp
                    <tr>
                        <td>{{ $timeslots->firstItem() + $index }}</td>
                        <td>{{ $timeslot->day }}</td>
                        <td>{{ $startTime->format('h:i A') }}</td>
                        <td>{{ $endTime->format('h:i A') }}</td>
                        <td>{{ $duration }} min</td>
                        <td><span class="badge bg-secondary">{{ $timeslot->schedule_entries_count }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary py-0 px-1 me-1" data-bs-toggle="modal" data-bs-target="#editTimeslotModal-{{ $timeslot->id }}">Edit</button>
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" data-bs-toggle="modal" data-bs-target="#deleteTimeslotModal-{{ $timeslot->id }}" {{ $timeslot->schedule_entries_count > 0 ? 'disabled' : '' }}>Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No timeslots defined. You can generate standard timeslots or add them manually.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div> <div class="mt-3 d-flex justify-content-center"> {{ $timeslots->links('pagination::bootstrap-5') }} </div> </div> </div>

        {{-- *** تضمين المودالات من الـ partial *** --}}
        @include('dashboard.data-entry.partials._timeslot_modals')
        {{-- المودالات الفردية (إذا لم تكن في الـ partial) --}}
        @foreach($timeslots as $timeslot)
            @include('dashboard.data-entry.partials._timeslot_modals', ['timeslot_to_edit' => $timeslot])
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // لإعادة فتح المودال عند وجود أخطاء validation
    @if($errors->any())
        @if(session('open_generate_modal') && $errors->hasBag('generateStandardModal'))
            $('#generateStandardTimeslotsModal').modal('show');
        @elseif(session('open_modal_on_error') == 'addTimeslotModal' && $errors->hasBag('addTimeslotModal'))
            $('#addTimeslotModal').modal('show');
        @elseif(Str::startsWith(session('open_modal_on_error'), 'editTimeslotModal_') && $errors->hasBag(session('open_modal_on_error')))
            $('#{{ session('open_modal_on_error') }}').modal('show'); // فتح مودال التعديل المحدد
        @endif
    @endif
});
</script>
@endpush
