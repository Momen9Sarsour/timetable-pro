{{-- ======================================= --}}
{{-- Modal لإضافة عدد متوقع جديد --}}
{{-- ======================================= --}}
@if (!$count)
<div class="modal fade" id="addCountModal" tabindex="-1" aria-labelledby="addCountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCountModalLabel">Add New Expected Count</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('data-entry.plan-expected-counts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            {{-- يمكنك استخدام select أو input type number --}}
                            <input type="number" class="form-control @error('academic_year', 'store') is-invalid @enderror" id="add_academic_year" name="academic_year" value="{{ old('academic_year', date('Y')) }}" required placeholder="YYYY" min="2020" max="{{ date('Y') + 5 }}">
                            @error('academic_year', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_plan_id" class="form-label">Academic Plan <span class="text-danger">*</span></label>
                            <select class="form-select @error('plan_id', 'store') is-invalid @enderror" id="add_plan_id" name="plan_id" required>
                                <option value="" selected disabled>Select plan...</option>
                                @if(isset($plans))
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->plan_name }} ({{ $plan->plan_no }})</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('plan_id', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                             <label for="add_plan_level" class="form-label">Level <span class="text-danger">*</span></label>
                             {{-- تحديد أقصى مستوى بناءً على الخطة المختارة قد يكون أفضل، لكن للتبسيط الآن نضع حقل رقمي --}}
                             <input type="number" class="form-control @error('plan_level', 'store') is-invalid @enderror" id="add_plan_level" name="plan_level" value="{{ old('plan_level') }}" required min="1" max="6"> {{-- افترض 6 مستويات كحد أقصى --}}
                             @error('plan_level', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="add_plan_semester" class="form-label">Semester <span class="text-danger">*</span></label>
                             <input type="number" class="form-control @error('plan_semester', 'store') is-invalid @enderror" id="add_plan_semester" name="plan_semester" value="{{ old('plan_semester') }}" required min="1" max="3"> {{-- افترض 3 فصول كحد أقصى (مع الصيفي) --}}
                             @error('plan_semester', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-4 mb-3">
                            <label for="add_male_count" class="form-label">Male Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('male_count', 'store') is-invalid @enderror" id="add_male_count" name="male_count" value="{{ old('male_count', 0) }}" required min="0">
                            @error('male_count', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="add_female_count" class="form-label">Female Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('female_count', 'store') is-invalid @enderror" id="add_female_count" name="female_count" value="{{ old('female_count', 0) }}" required min="0">
                            @error('female_count', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="add_branch" class="form-label">Branch (Optional)</label>
                            <input type="text" class="form-control @error('branch', 'store') is-invalid @enderror" id="add_branch" name="branch" value="{{ old('branch') }}" placeholder="e.g., Main, North">
                            @error('branch', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    {{-- عرض خطأ التفرد --}}
                    @error('count_unique', 'store') <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Count</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ========================== --}}
{{-- Modals التعديل والحذف --}}
{{-- ========================== --}}
@if ($count)

{{-- Modal لتعديل عدد متوقع --}}
<div class="modal fade" id="editCountModal-{{ $count->id }}" tabindex="-1" aria-labelledby="editCountModalLabel-{{ $count->id }}" aria-hidden="true">
     <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCountModalLabel-{{ $count->id }}">Edit Expected Count</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.plan-expected-counts.update', $count->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- نفس حقول الإضافة مع القيم الحالية --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_academic_year_{{ $count->id }}" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('academic_year', 'update_'.$count->id) is-invalid @enderror" id="edit_academic_year_{{ $count->id }}" name="academic_year" value="{{ old('academic_year', $count->academic_year) }}" required placeholder="YYYY" min="2020" max="{{ date('Y') + 5 }}">
                            @error('academic_year', 'update_'.$count->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_plan_id_{{ $count->id }}" class="form-label">Academic Plan <span class="text-danger">*</span></label>
                            <select class="form-select @error('plan_id', 'update_'.$count->id) is-invalid @enderror" id="edit_plan_id_{{ $count->id }}" name="plan_id" required>
                                <option value="" disabled>Select plan...</option>
                                @if(isset($plans))
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ old('plan_id', $count->plan_id) == $plan->id ? 'selected' : '' }}>{{ $plan->plan_name }} ({{ $plan->plan_no }})</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('plan_id', 'update_'.$count->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    {{-- ... باقي الحقول بنفس الطريقة ... --}}
                     <div class="row">
                        <div class="col-md-6 mb-3">
                             <label for="edit_plan_level_{{ $count->id }}" class="form-label">Level <span class="text-danger">*</span></label>
                             <input type="number" class="form-control @error('plan_level', 'update_'.$count->id) is-invalid @enderror" id="edit_plan_level_{{ $count->id }}" name="plan_level" value="{{ old('plan_level', $count->plan_level) }}" required min="1" max="6">
                             @error('plan_level', 'update_'.$count->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="edit_plan_semester_{{ $count->id }}" class="form-label">Semester <span class="text-danger">*</span></label>
                             <input type="number" class="form-control @error('plan_semester', 'update_'.$count->id) is-invalid @enderror" id="edit_plan_semester_{{ $count->id }}" name="plan_semester" value="{{ old('plan_semester', $count->plan_semester) }}" required min="1" max="3">
                             @error('plan_semester', 'update_'.$count->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                     <div class="row">
                         <div class="col-md-4 mb-3">
                            <label for="edit_male_count_{{ $count->id }}" class="form-label">Male Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('male_count', 'update_'.$count->id) is-invalid @enderror" id="edit_male_count_{{ $count->id }}" name="male_count" value="{{ old('male_count', $count->male_count) }}" required min="0">
                            @error('male_count', 'update_'.$count->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="edit_female_count_{{ $count->id }}" class="form-label">Female Count <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('female_count', 'update_'.$count->id) is-invalid @enderror" id="edit_female_count_{{ $count->id }}" name="female_count" value="{{ old('female_count', $count->female_count) }}" required min="0">
                            @error('female_count', 'update_'.$count->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="edit_branch_{{ $count->id }}" class="form-label">Branch (Optional)</label>
                            <input type="text" class="form-control @error('branch', 'update_'.$count->id) is-invalid @enderror" id="edit_branch_{{ $count->id }}" name="branch" value="{{ old('branch', $count->branch) }}" placeholder="e.g., Main, North">
                            @error('branch', 'update_'.$count->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    {{-- عرض خطأ التفرد للتحديث --}}
                    @error('count_unique', 'update_'.$count->id) <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
                     {{-- عرض خطأ الكنترولر العام إذا وجد --}}
                     @error('update_error', 'update_'.$count->id) <div class="alert alert-danger small p-2">{{ $message }}</div> @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Count</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal لتأكيد الحذف --}}
<div class="modal fade" id="deleteCountModal-{{ $count->id }}" tabindex="-1" aria-labelledby="deleteCountModalLabel-{{ $count->id }}" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCountModalLabel-{{ $count->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form action="{{ route('data-entry.plan-expected-counts.destroy', $count->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the expected count record for:</p>
                    <p><strong>Plan:</strong> {{ optional($count->plan)->plan_name ?? 'N/A' }}<br>
                       <strong>Year:</strong> {{ $count->academic_year }}<br>
                       <strong>Level:</strong> {{ $count->plan_level }}, <strong>Semester:</strong> {{ $count->plan_semester }}<br>
                       <strong>Branch:</strong> {{ $count->branch ?? 'Default' }}
                    </p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Count</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif
