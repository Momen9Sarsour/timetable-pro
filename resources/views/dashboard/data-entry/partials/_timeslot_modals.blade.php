@php
    $daysOfWeek = \App\Http\Controllers\DataEntry\TimeslotController::DAYS_OF_WEEK; // جلب الأيام
@endphp

{{-- =============================================== --}}
{{-- 1. Modal لتوليد الفترات الزمنية القياسية --}}
{{-- =============================================== --}}
<div class="modal fade" id="generateStandardTimeslotsModal" tabindex="-1" aria-labelledby="generateStandardTimeslotsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateStandardTimeslotsModalLabel">Generate Standard Weekly Timeslots</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.timeslots.generateStandard') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-1"></i><strong>Warning:</strong> This will delete all existing timeslots and generate new ones based on your settings. This action cannot be undone.
                    </div>

                    @if ($errors->hasBag('generateStandardModal'))
                        <div class="alert alert-danger small p-2 mb-3">
                            <strong>Please correct the errors:</strong>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->getBag('generateStandardModal')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                     @endif

                    <div class="mb-3">
                        <label class="form-label">Select Working Days <span class="text-danger">*</span></label>
                        <div class="row">
                            @foreach($daysOfWeek as $day)
                                <div class="col-md-3 col-sm-4 col-6">
                                    <div class="form-check">
                                        <input class="form-check-input @error('working_days.'.$loop->index, 'generateStandardModal') is-invalid @enderror" type="checkbox" name="working_days[]" value="{{ $day }}" id="day_{{ $loop->index }}" {{ is_array(old('working_days')) && in_array($day, old('working_days', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'])) ? 'checked' : (!is_array(old('working_days')) && in_array($day, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']) && !old('_token') ? 'checked' : '') }}>
                                        <label class="form-check-label" for="day_{{ $loop->index }}">{{ $day }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('working_days', 'generateStandardModal') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gen_overall_start_time" class="form-label">Overall Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('overall_start_time', 'generateStandardModal') is-invalid @enderror" id="gen_overall_start_time" name="overall_start_time" value="{{ old('overall_start_time', '08:00') }}" required>
                            @error('overall_start_time', 'generateStandardModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gen_overall_end_time" class="form-label">Overall End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('overall_end_time', 'generateStandardModal') is-invalid @enderror" id="gen_overall_end_time" name="overall_end_time" value="{{ old('overall_end_time', '16:00') }}" required>
                            @error('overall_end_time', 'generateStandardModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gen_lecture_duration" class="form-label">Lecture Duration (minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('lecture_duration', 'generateStandardModal') is-invalid @enderror" id="gen_lecture_duration" name="lecture_duration" value="{{ old('lecture_duration', 50) }}" required min="15">
                            @error('lecture_duration', 'generateStandardModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gen_break_duration" class="form-label">Break Duration (minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('break_duration', 'generateStandardModal') is-invalid @enderror" id="gen_break_duration" name="break_duration" value="{{ old('break_duration', 10) }}" required min="0">
                            @error('break_duration', 'generateStandardModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Generate Timeslots</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ======================================= --}}
{{-- 2. Modal لإضافة فترة زمنية فردية --}}
{{-- ======================================= --}}
{{-- هذا المودال يُستخدم عندما لا يكون هناك $timeslot_to_edit (أي للإضافة) --}}
@if (!isset($timeslot_to_edit))
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
                    @if ($errors->hasBag('addTimeslotModal'))
                        <div class="alert alert-danger small p-2 mb-3">
                            <ul class="mb-0 ps-3">@foreach ($errors->getBag('addTimeslotModal')->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="add_day" class="form-label">Day <span class="text-danger">*</span></label>
                        <select class="form-select @error('day', 'addTimeslotModal') is-invalid @enderror" id="add_day" name="day" required>
                            <option value="" selected disabled>Select day...</option>
                            @foreach ($daysOfWeek as $day)
                                <option value="{{ $day }}" {{ old('day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        @error('day', 'addTimeslotModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('start_time', 'addTimeslotModal') is-invalid @enderror" id="add_start_time" name="start_time" value="{{ old('start_time') }}" required>
                            @error('start_time', 'addTimeslotModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('end_time', 'addTimeslotModal') is-invalid @enderror" id="add_end_time" name="end_time" value="{{ old('end_time') }}" required>
                            @error('end_time', 'addTimeslotModal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('time_unique', 'addTimeslotModal') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            @error('time_overlap', 'addTimeslotModal') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Timeslot</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif


{{-- ======================================= --}}
{{-- 3. Modals التعديل والحذف (لكل فترة) --}}
{{-- ======================================= --}}
@if (isset($timeslot_to_edit))
@php $timeslot = $timeslot_to_edit; @endphp {{-- لتسهيل الاستخدام --}}
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
                    @php $editErrorBag = 'editTimeslotModal_'.$timeslot->id; @endphp
                    @if ($errors->hasBag($editErrorBag))
                        <div class="alert alert-danger small p-2 mb-3">
                            <ul class="mb-0 ps-3">@foreach ($errors->getBag($editErrorBag)->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="edit_day_{{ $timeslot->id }}" class="form-label">Day <span class="text-danger">*</span></label>
                        <select class="form-select @error('day', $editErrorBag) is-invalid @enderror" id="edit_day_{{ $timeslot->id }}" name="day" required>
                            @foreach ($daysOfWeek as $day)
                                <option value="{{ $day }}" {{ old('day', $timeslot->day) == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        @error('day', $editErrorBag) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_time_{{ $timeslot->id }}" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('start_time', $editErrorBag) is-invalid @enderror" id="edit_start_time_{{ $timeslot->id }}" name="start_time" value="{{ old('start_time', \Carbon\Carbon::parse($timeslot->start_time)->format('H:i')) }}" required>
                            @error('start_time', $editErrorBag) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_time_{{ $timeslot->id }}" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('end_time', $editErrorBag) is-invalid @enderror" id="edit_end_time_{{ $timeslot->id }}" name="end_time" value="{{ old('end_time', \Carbon\Carbon::parse($timeslot->end_time)->format('H:i')) }}" required>
                            @error('end_time', $editErrorBag) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @error('time_unique', $editErrorBag) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            @error('time_overlap', $editErrorBag) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Timeslot</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal لتأكيد الحذف --}}
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
                    <p>Are you sure you want to delete: <strong>{{ $timeslot->day }} {{ \Carbon\Carbon::parse($timeslot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($timeslot->end_time)->format('h:i A') }}</strong>?</p>
                    @if($timeslot->schedule_entries_count > 0)
                        <p class="text-warning small"><i class="fas fa-exclamation-triangle"></i> This timeslot is used by {{ $timeslot->schedule_entries_count }} scheduled classes and cannot be deleted.</p>
                    @else
                        <p class="text-danger small">This action cannot be undone.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" {{ $timeslot->schedule_entries_count > 0 ? 'disabled' : '' }}>Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
