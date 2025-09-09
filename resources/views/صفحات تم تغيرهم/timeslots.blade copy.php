@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Manage Timeslots</h1>
             <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTimeslotModal">
                 <i class="fas fa-plus me-1"></i> Add New Timeslot
             </button>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Day</th>
                                <th scope="col">Start Time</th>
                                <th scope="col">End Time</th>
                                <th scope="col">Duration</th>
                                <th scope="col">Scheduled Classes</th> {{-- عدد الحصص المرتبطة --}}
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($timeslots as $index => $timeslot)
                            @php
                                // حساب المدة (اختياري)
                                $startTime = \Carbon\Carbon::parse($timeslot->start_time);
                                $endTime = \Carbon\Carbon::parse($timeslot->end_time);
                                $duration = $startTime->diffInMinutes($endTime);
                            @endphp
                            <tr>
                                <td>{{ $timeslots->firstItem() + $index }}</td>
                                <td>{{ $timeslot->day }}</td>
                                <td>{{ $startTime->format('h:i A') }}</td> {{-- تنسيق الوقت --}}
                                <td>{{ $endTime->format('h:i A') }}</td>   {{-- تنسيق الوقت --}}
                                <td>{{ $duration }} min</td>
                                <td>{{ $timeslot->schedule_entries_count }}</td> {{-- استخدام count المحمل --}}
                                <td>
                                    {{-- زر التعديل --}}
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editTimeslotModal-{{ $timeslot->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- زر الحذف (معطل إذا كان مرتبطاً بجدول) --}}
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteTimeslotModal-{{ $timeslot->id }}" title="Delete" {{ $timeslot->schedule_entries_count > 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    {{-- تضمين Modals (في ملف واحد) --}}
                                    @include('dashboard.data-entry.partials._timeslot_modals', ['timeslot' => $timeslot])

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No timeslots found. Click 'Add New Timeslot' to create some.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="mt-3 d-flex justify-content-center">
                     {{ $timeslots->links('pagination::bootstrap-5') }}
                 </div>
            </div>
        </div>

         {{-- مودال الإضافة --}}
         @include('dashboard.data-entry.partials._timeslot_modals', ['timeslot' => null])

    </div>
</div>
@endsection

@push('scripts')
{{-- لا حاجة لـ JS خاص هنا إلا إذا أردت تحسينات إضافية --}}
@endpush
