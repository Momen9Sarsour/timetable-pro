@php
    // تعريف الأيام المتاحة للاختيار
    $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
@endphp

{{-- ========================== --}}
{{-- Modal لإضافة فترة زمنية --}}
{{-- ========================== --}}
@if (!$timeslot)
<div class="modal fade" id="addTimeslotModal" tabindex="-1" aria-labelledby="addTimeslotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTimeslotModalLabel">Add New Timeslot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.timeslots.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_day" class="form-label">Day <span class="text-danger">*</span></label>
                        <select class="form-select @error('day', 'store') is-invalid @enderror" id="add_day" name="day" required>
                            <option value="" selected disabled>Select day...</option>
                            @foreach ($daysOfWeek as $day)
                                <option value="{{ $day }}" {{ old('day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        @error('day', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('start_time', 'store') is-invalid @enderror" id="add_start_time" name="start_time" value="{{ old('start_time') }}" required>
                            @error('start_time', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('end_time', 'store') is-invalid @enderror" id="add_end_time" name="end_time" value="{{ old('end_time') }}" required>
                             {{-- عرض الأخطاء العامة أو الخاصة بالوقت --}}
                            @error('end_time', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                             @error('time_order', 'store') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                             @error('time_unique', 'store') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Timeslot</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ========================== --}}
{{-- Modals التعديل والحذف --}}
{{-- ========================== --}}
@if ($timeslot)

{{-- Modal لتعديل فترة زمنية --}}
@php
    // تعريف الأيام هنا أيضاً لتكون متاحة في هذا النطاق
    $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
@endphp
{{-- Modal لتعديل فترة زمنية --}}
<div class="modal fade" id="editTimeslotModal-{{ $timeslot->id }}" tabindex="-1" aria-labelledby="editTimeslotModalLabel-{{ $timeslot->id }}" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTimeslotModalLabel-{{ $timeslot->id }}">Edit Timeslot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.timeslots.update', $timeslot->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_day_{{ $timeslot->id }}" class="form-label">Day <span class="text-danger">*</span></label>
                        <select class="form-select @error('day', 'update_'.$timeslot->id) is-invalid @enderror" id="edit_day_{{ $timeslot->id }}" name="day" required>
                            <option value="" disabled>Select day...</option>
                            @foreach ($daysOfWeek as $day)
                                <option value="{{ $day }}" {{ old('day', $timeslot->day) == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        @error('day', 'update_'.$timeslot->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_time_{{ $timeslot->id }}" class="form-label">Start Time <span class="text-danger">*</span></label>
                            {{-- *** استخدام Carbon لتنسيق القيمة القديمة *** --}}
                            <input type="time" class="form-control @error('start_time', 'update_'.$timeslot->id) is-invalid @enderror" id="edit_start_time_{{ $timeslot->id }}" name="start_time" value="{{ old('start_time', \Carbon\Carbon::parse($timeslot->start_time)->format('H:i')) }}" required>
                            @error('start_time', 'update_'.$timeslot->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_time_{{ $timeslot->id }}" class="form-label">End Time <span class="text-danger">*</span></label>
                             {{-- *** استخدام Carbon لتنسيق القيمة القديمة *** --}}
                            <input type="time" class="form-control @error('end_time', 'update_'.$timeslot->id) is-invalid @enderror" id="edit_end_time_{{ $timeslot->id }}" name="end_time" value="{{ old('end_time', \Carbon\Carbon::parse($timeslot->end_time)->format('H:i')) }}" required>
                            @error('end_time', 'update_'.$timeslot->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            {{-- عرض خطأ التفرد إذا حدث --}}
                             @error('start_time', 'update_'.$timeslot->id)
                                @if(Str::contains($message, 'already exists'))
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @endif
                             @enderror

                        </div>
                    </div>
                     {{-- عرض خطأ الكنترولر العام إذا وجد --}}
                     @error('update_error', 'update_'.$timeslot->id) <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Timeslot</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- Modal لتأكيد الحذف --}}
@if ($timeslot->schedule_entries_count == 0) {{-- لا تسمح بحذف فترة مستخدمة --}}
<div class="modal fade" id="deleteTimeslotModal-{{ $timeslot->id }}" tabindex="-1" aria-labelledby="deleteTimeslotModalLabel-{{ $timeslot->id }}" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteTimeslotModalLabel-{{ $timeslot->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.timeslots.destroy', $timeslot->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the timeslot: <strong>{{ $timeslot->day }} {{ \Carbon\Carbon::parse($timeslot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($timeslot->end_time)->format('h:i A') }}</strong>?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Timeslot</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endif
