{{-- // Modal لإضافة مدرس جديد --}}
@if (!$instructor)
<div class="modal fade" id="addInstructorModal" tabindex="-1" aria-labelledby="addInstructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInstructorModalLabel">Add New Instructor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.instructors.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                         {{-- // اختيار المستخدم لربطه --}}
                        <div class="col-md-6 mb-3">
                            <label for="add_user_id" class="form-label">Link to User Account <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_id') is-invalid @enderror" id="add_user_id" name="user_id" required>
                                <option value="" selected disabled>Select user...</option>
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if($availableUsers->isEmpty())
                             <small class="text-muted">No available users found. Create a user first with an appropriate role (Instructor, HoD, Admin).</small>
                            @endif
                        </div>
                        {{-- // الرقم الوظيفي --}}
                         <div class="col-md-6 mb-3">
                            <label for="add_instructor_no" class="form-label">Instructor Number/ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('instructor_no') is-invalid @enderror" id="add_instructor_no" name="instructor_no" value="{{ old('instructor_no') }}" required>
                            @error('instructor_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        {{-- // اسم المدرس (يمكن ملؤه تلقائياً من اليوزر؟ أو نتركه للإدخال) --}}
                         <div class="col-md-6 mb-3">
                             <label for="add_instructor_name" class="form-label">Instructor Display Name <span class="text-danger">*</span></label>
                             <input type="text" class="form-control @error('instructor_name') is-invalid @enderror" id="add_instructor_name" name="instructor_name" value="{{ old('instructor_name') }}" required>
                             <small class="text-muted">Can be the same as the user account name.</small>
                             @error('instructor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                         </div>
                        {{-- // الدرجة العلمية --}}
                         <div class="col-md-6 mb-3">
                            <label for="add_academic_degree" class="form-label">Academic Degree</label>
                            <input type="text" class="form-control @error('academic_degree') is-invalid @enderror" id="add_academic_degree" name="academic_degree" value="{{ old('academic_degree') }}" placeholder="e.g., PhD, MSc, BSc">
                            @error('academic_degree') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                     <div class="row">
                        {{-- // القسم --}}
                        <div class="col-md-6 mb-3">
                            <label for="add_department_id" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="add_department_id" name="department_id" required>
                                <option value="" selected disabled>Select department...</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        {{-- // الحد الأقصى للساعات --}}
                         <div class="col-md-6 mb-3">
                            <label for="add_max_weekly_hours" class="form-label">Max Weekly Hours</label>
                            <input type="number" class="form-control @error('max_weekly_hours') is-invalid @enderror" id="add_max_weekly_hours" name="max_weekly_hours" value="{{ old('max_weekly_hours') }}" min="0">
                            @error('max_weekly_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- // إضافة حقول المكتب --}}
                    {{-- <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_office_location" class="form-label">Office Location</label>
                            <input type="text" class="form-control @error('office_location') is-invalid @enderror" id="add_office_location" name="office_location" value="{{ old('office_location') }}">
                            @error('office_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_office_hours" class="form-label">Office Hours</label>
                            <input type="text" class="form-control @error('office_hours') is-invalid @enderror" id="add_office_hours" name="office_hours" value="{{ old('office_hours') }}" placeholder="e.g., Mon/Wed 10:00 - 12:00">
                            @error('office_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div> --}}

                    {{-- // حقل تفضيلات التوفر (مؤقتاً كـ textarea) --}}
                    <div class="mb-3">
                        <label for="add_availability_preferences" class="form-label">Availability Preferences (Optional)</label>
                        <textarea class="form-control @error('availability_preferences') is-invalid @enderror" id="add_availability_preferences" name="availability_preferences" rows="3" placeholder="Enter preferred/unavailable times, e.g., Unavailable: Monday morning, Preferred: Tuesday afternoon">{{ old('availability_preferences') }}</textarea>
                        <small class="text-muted">Enter preferences as text for now. JSON format can be used later.</small>
                        @error('availability_preferences') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" {{ $availableUsers->isEmpty() ? 'disabled' : '' }}>Save Instructor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- // Modal لتعديل مدرس موجود --}}
@if ($instructor)
<div class="modal fade" id="editInstructorModal-{{ $instructor->id }}" tabindex="-1" aria-labelledby="editInstructorModalLabel-{{ $instructor->id }}" aria-hidden="true">
     <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editInstructorModalLabel-{{ $instructor->id }}">Edit Instructor: {{ $instructor->instructor_name ?? $instructor->user->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.instructors.update', $instructor->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- // نفس حقول نموذج الإضافة ولكن مع قيم المدرس الحالية --}}
                     {{-- // حقل اليوزر يكون للقراءة فقط أو مخفي لأنه لا يجب تغييره --}}
                     <div class="row">
                          <div class="col-md-6 mb-3">
                            <label class="form-label">Linked User Account</label>
                            <input type="text" class="form-control" value="{{ $instructor->user->name ?? 'N/A' }} ({{ $instructor->user->email ?? 'N/A' }})" disabled readonly>
                            {{-- // لا نرسل user_id في التعديل --}}
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_instructor_no_{{ $instructor->id }}" class="form-label">Instructor Number/ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('instructor_no', 'update_'.$instructor->id) is-invalid @enderror" id="edit_instructor_no_{{ $instructor->id }}" name="instructor_no" value="{{ old('instructor_no', $instructor->instructor_no) }}" required>
                            @error('instructor_no', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_instructor_name_{{ $instructor->id }}" class="form-label">Instructor Display Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('instructor_name', 'update_'.$instructor->id) is-invalid @enderror" id="edit_instructor_name_{{ $instructor->id }}" name="instructor_name" value="{{ old('instructor_name', $instructor->instructor_name) }}" required>
                            @error('instructor_name', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                         </div>
                         <div class="col-md-6 mb-3">
                            <label for="edit_academic_degree_{{ $instructor->id }}" class="form-label">Academic Degree</label>
                            <input type="text" class="form-control @error('academic_degree', 'update_'.$instructor->id) is-invalid @enderror" id="edit_academic_degree_{{ $instructor->id }}" name="academic_degree" value="{{ old('academic_degree', $instructor->academic_degree) }}">
                            @error('academic_degree', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                     <div class="row">
                         <div class="col-md-6 mb-3">
                            <label for="edit_department_id_{{ $instructor->id }}" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id', 'update_'.$instructor->id) is-invalid @enderror" id="edit_department_id_{{ $instructor->id }}" name="department_id" required>
                                <option value="" disabled>Select department...</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $instructor->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                            @error('department_id', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_max_weekly_hours_{{ $instructor->id }}" class="form-label">Max Weekly Hours</label>
                            <input type="number" class="form-control @error('max_weekly_hours', 'update_'.$instructor->id) is-invalid @enderror" id="edit_max_weekly_hours_{{ $instructor->id }}" name="max_weekly_hours" value="{{ old('max_weekly_hours', $instructor->max_weekly_hours) }}" min="0">
                            @error('max_weekly_hours', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                     {{-- // حقول المكتب --}}
                    {{-- <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_office_location_{{ $instructor->id }}" class="form-label">Office Location</label>
                            <input type="text" class="form-control @error('office_location', 'update_'.$instructor->id) is-invalid @enderror" id="edit_office_location_{{ $instructor->id }}" name="office_location" value="{{ old('office_location', $instructor->office_location) }}">
                            @error('office_location', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_office_hours_{{ $instructor->id }}" class="form-label">Office Hours</label>
                            <input type="text" class="form-control @error('office_hours', 'update_'.$instructor->id) is-invalid @enderror" id="edit_office_hours_{{ $instructor->id }}" name="office_hours" value="{{ old('office_hours', $instructor->office_hours) }}">
                            @error('office_hours', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div> --}}

                    <div class="mb-3">
                         {{-- // تحويل JSON المخزن إلى نص للعرض (أو استخدام حقل input منفصل إذا أردت) --}}
                        @php
                             $availabilityText = '';
                             if (is_string($instructor->availability_preferences)) {
                                 $availabilityText = $instructor->availability_preferences; // إذا كان النص بسيطاً
                             } elseif (is_array($instructor->availability_preferences) || is_object($instructor->availability_preferences)) {
                                 // محاولة تحويل JSON المعقد لنص مقروء (يمكن تحسينها)
                                 $availabilityText = json_encode($instructor->availability_preferences, JSON_PRETTY_PRINT);
                             }
                        @endphp
                        <label for="edit_availability_preferences_{{ $instructor->id }}" class="form-label">Availability Preferences (Optional)</label>
                        <textarea class="form-control @error('availability_preferences', 'update_'.$instructor->id) is-invalid @enderror" id="edit_availability_preferences_{{ $instructor->id }}" name="availability_preferences" rows="3">{{ old('availability_preferences', $availabilityText) }}</textarea>
                         <small class="text-muted">Enter preferences as text for now.</small>
                        @error('availability_preferences', 'update_'.$instructor->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Instructor</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- // Modal لتأكيد الحذف --}}
<div class="modal fade" id="deleteInstructorModal-{{ $instructor->id }}" tabindex="-1" aria-labelledby="deleteInstructorModalLabel-{{ $instructor->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteInstructorModalLabel-{{ $instructor->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.instructors.destroy', $instructor->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the instructor record for: <strong>{{ $instructor->instructor_name ?? $instructor->user->name }} ({{ $instructor->instructor_no }})</strong>?</p>
                    <p class="text-warning small">Note: This will only delete the instructor-specific data. The associated user account ({{ $instructor->user->email ?? 'N/A' }}) will not be deleted.</p>
                    <p class="text-danger small">Any scheduled classes assigned to this instructor might be affected.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Instructor Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
