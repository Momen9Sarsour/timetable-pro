<div class="modal fade" id="addSubjectToPlanModal" tabindex="-1" aria-labelledby="addSubjectToPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubjectToPlanModalLabel">Add Subject to Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSubjectForm" action="{{ route('data-entry.plans.addSubject', $plan->id) }}" method="POST">
                @csrf
                {{-- *** الحقول المخفية داخل الفورم *** --}}
                <input type="hidden" name="plan_level" id="modal_plan_level" value="">
                <input type="hidden" name="plan_semester" id="modal_plan_semester" value="">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_subject_id" class="form-label">Select Subject <span class="text-danger">*</span></label>
                        {{-- استهداف select بواسطة ID لـ Select2 --}}
                        <select class="form-select select2-subjects @error('subject_id', 'addSubject') is-invalid @enderror" id="modal_subject_id" name="subject_id" required style="width: 100%;" data-placeholder="Search or select a subject..."> {{-- إضافة data-placeholder --}}
                            <option value=""></option> {{-- خيار فارغ للـ placeholder --}}
                            @isset($allSubjects)
                                @foreach ($allSubjects as $subject)
                                    {{-- إخفاء المواد المضافة بالفعل في *كل* الخطة --}}
                                    @if(!in_array($subject->id, $addedSubjectIds ?? []))
                                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->subject_no }} - {{ $subject->subject_name }}
                                        </option>
                                    @endif
                                @endforeach
                            @endisset
                        </select>
                        @error('subject_id', 'addSubject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror {{-- Changed to d-block --}}
                        @error('plan_level', 'addSubject') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('plan_semester', 'addSubject') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        {{-- عرض خطأ الـ unique إذا حدث --}}
                         @error('subject_id', 'addSubject')
                             @if (Str::contains($message, 'already added')) {{-- Check if the error is the specific unique one --}}
                                 <div class="text-danger small mt-1">{{ $message }}</div>
                             @endif
                         @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Subject to Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
