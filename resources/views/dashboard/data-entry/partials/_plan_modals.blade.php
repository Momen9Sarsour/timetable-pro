{{-- // ====================== --}}
{{-- // Modal لإضافة خطة جديدة --}}
{{-- // ====================== --}}
@if (!$plan)
    <div class="modal fade" id="addPlanModal" tabindex="-1" aria-labelledby="addPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPlanModalLabel">Add New Academic Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('data-entry.plans.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_plan_no" class="form-label">Plan Code <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('plan_no', 'store') is-invalid @enderror"
                                    id="add_plan_no" name="plan_no" value="{{ old('plan_no') }}" required>
                                @error('plan_no', 'store')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_plan_name" class="form-label">Plan Name <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('plan_name', 'store') is-invalid @enderror"
                                    id="add_plan_name" name="plan_name" value="{{ old('plan_name') }}" required>
                                @error('plan_name', 'store')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_year" class="form-label">Adoption Year <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('year', 'store') is-invalid @enderror"
                                    id="add_year" name="year" value="{{ old('year', date('Y')) }}" required
                                    placeholder="YYYY" min="2000" max="{{ date('Y') + 5 }}">
                                @error('year', 'store')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_plan_hours" class="form-label">Total Credit Hours <span
                                        class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('plan_hours', 'store') is-invalid @enderror"
                                    id="add_plan_hours" name="plan_hours" value="{{ old('plan_hours') }}" required
                                    min="1">
                                @error('plan_hours', 'store')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="add_department_id" class="form-label">Associated Department <span
                                    class="text-danger">*</span></label>
                            <select class="form-select @error('department_id', 'store') is-invalid @enderror"
                                id="add_department_id" name="department_id" required>
                                <option value="" selected disabled>Select department...</option>
                                @if (isset($departments))
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->department_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('department_id', 'store')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active"
                                value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="add_is_active">
                                Mark plan as active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- // ========================= --}}
{{-- // Modals التعديل والحذف --}}
{{-- // ========================= --}}
@if ($plan)

    {{-- // Modal لتعديل خطة موجودة --}}
    <div class="modal fade" id="editPlanModal-{{ $plan->id }}" tabindex="-1"
        aria-labelledby="editPlanModalLabel-{{ $plan->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPlanModalLabel-{{ $plan->id }}">Edit Academic Plan:
                        {{ $plan->plan_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('data-entry.plans.update', $plan->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        {{-- // نفس حقول الإضافة مع القيم الحالية --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_plan_no_{{ $plan->id }}" class="form-label">Plan Code <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('plan_no', 'update_' . $plan->id) is-invalid @enderror"
                                    id="edit_plan_no_{{ $plan->id }}" name="plan_no"
                                    value="{{ old('plan_no', $plan->plan_no) }}" required>
                                @error('plan_no', 'update_' . $plan->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_plan_name_{{ $plan->id }}" class="form-label">Plan Name <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('plan_name', 'update_' . $plan->id) is-invalid @enderror"
                                    id="edit_plan_name_{{ $plan->id }}" name="plan_name"
                                    value="{{ old('plan_name', $plan->plan_name) }}" required>
                                @error('plan_name', 'update_' . $plan->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        {{-- ... باقي الحقول بنفس الطريقة ... --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_year_{{ $plan->id }}" class="form-label">Adoption Year <span
                                        class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('year', 'update_' . $plan->id) is-invalid @enderror"
                                    id="edit_year_{{ $plan->id }}" name="year"
                                    value="{{ old('year', $plan->year) }}" required placeholder="YYYY"
                                    min="2000" max="{{ date('Y') + 5 }}">
                                @error('year', 'update_' . $plan->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_plan_hours_{{ $plan->id }}" class="form-label">Total Credit
                                    Hours <span class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('plan_hours', 'update_' . $plan->id) is-invalid @enderror"
                                    id="edit_plan_hours_{{ $plan->id }}" name="plan_hours"
                                    value="{{ old('plan_hours', $plan->plan_hours) }}" required min="1">
                                @error('plan_hours', 'update_' . $plan->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_department_id_{{ $plan->id }}" class="form-label">Associated
                                Department <span class="text-danger">*</span></label>
                            <select
                                class="form-select @error('department_id', 'update_' . $plan->id) is-invalid @enderror"
                                id="edit_department_id_{{ $plan->id }}" name="department_id" required>
                                <option value="" disabled>Select department...</option>
                                @if (isset($departments))
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('department_id', $plan->department_id) == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->department_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('department_id', 'update_' . $plan->id)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check mb-3">
                            {{-- // استخدام is_active من الموديل (تذكر إضافة cast لـ boolean) --}}
                            <input class="form-check-input" type="checkbox" id="edit_is_active_{{ $plan->id }}"
                                name="is_active" value="1"
                                {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="edit_is_active_{{ $plan->id }}">
                                Mark plan as active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- // Modal لتأكيد الحذف --}}
    @if ($plan->planSubjectEntries()->count() == 0 || $plan->planSubjectEntries()->count() != 0)
        <div class="modal fade" id="deletePlanModal-{{ $plan->id }}" tabindex="-1"
            aria-labelledby="deletePlanModalLabel-{{ $plan->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deletePlanModalLabel-{{ $plan->id }}">Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form action="{{ route('data-entry.plans.destroy', $plan->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">

                            @if ($plan->planSubjectEntries()->count())
                                <p>Are you sure you want to delete the academic plan: <strong>{{ $plan->plan_name }}
                                        ({{ $plan->plan_no }})</strong>?</p>
                                <p>This academic plan (<strong id="planNameToForceDelete">{{ $plan->plan_name }}</strong>) has
                                    associated subjects.</p>
                                <p><strong>Are you absolutely sure you want to delete this plan AND all its associated
                                        subject entries?</strong></p>
                                        <h3>The count Subject of plan :{{ $plan->planSubjectEntries()->count() }}</h3>
                                <p class="text-danger">This will remove the subjects from this specific plan structure
                                    but will NOT delete the subjects themselves from the system.</p>
                                <p class="text-danger fw-bold">This action cannot be undone.</p>

                            @else
                                <p>Are you sure you want to delete the academic plan: <strong>{{ $plan->plan_name }}
                                        ({{ $plan->plan_no }})</strong>?</p>
                                <p class="text-danger small">This action cannot be undone. Ensure no subjects are
                                    linked to
                                    this plan before deleting (though the button should be disabled if subjects exist).
                                </p>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Yes, Delete Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endif
