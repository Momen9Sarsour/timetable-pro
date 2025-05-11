@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Manage Sections</h1>
             {{-- زر الإضافة قد يكون هنا أو في صفحة الخطة نفسها --}}
             <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                 <i class="fas fa-plus me-1"></i> Add New Section
             </button>
        </div>

        @include('dashboard.data-entry.partials._status_messages')

        {{-- TODO: Add Filters (by Plan, Level, Semester, Academic Year, Subject) --}}

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Year</th>
                                <th scope="col">Semester</th>
                                <th scope="col">Plan</th>
                                <th scope="col">Level</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Section No.</th>
                                <th scope="col">Gender</th>
                                <th scope="col">Branch</th>
                                <th scope="col">Student Count</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sections as $index => $section)
                            <tr>
                                <td>{{ $sections->firstItem() + $index }}</td>
                                <td>{{ $section->academic_year }}</td>
                                <td>{{ $section->semester }}</td>
                                {{-- الوصول للخطة والمادة عبر العلاقات المتداخلة --}}
                                <td>{{ optional(optional($section->planSubject)->plan)->plan_no ?? 'N/A' }}</td>
                                <td>{{ optional($section->planSubject)->plan_level ?? 'N/A' }}</td>
                                <td>{{ optional(optional($section->planSubject)->subject)->subject_no ?? 'N/A' }} - {{ Str::limit($section->planSubject->subject->subject_name, 30) }} </td>

                                <td>{{ $section->section_number }}</td>
                                <td>{{ $section->section_gender }}</td>
                                <td>{{ $section->branch ?? '-' }}</td>
                                <td>{{ $section->student_count }}</td>
                                <td>
                                    {{-- زر التعديل --}}
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editSectionModal-{{ $section->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- زر الحذف --}}
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSectionModal-{{ $section->id }}" title="Delete" {{-- $section->scheduleEntries()->count() > 0 ? 'disabled' : '' --}}> {{-- تعطيل إذا كانت مجدولة؟ --}}
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    {{-- تضمين Modals --}}
                                    {{-- سنحتاج لتمرير بيانات الخطط والمواد للنماذج --}}
                                    @include('dashboard.data-entry.partials._section_modals', [
                                        'section' => $section,
                                        'planSubjects' => $planSubjects ?? collect() // قائمة بكل plan_subject entries
                                    ])

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No sections found. Sections are usually created based on expected counts and plans.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="mt-3 d-flex justify-content-center">
                     {{ $sections->links() }}
                 </div>
            </div>
        </div>

         {{-- مودال الإضافة --}}
         @include('dashboard.data-entry.partials._section_modals', [
             'section' => null,
             'planSubjects' => $planSubjects ?? collect()
         ])

    </div>
</div>
@endsection

@push('scripts')
{{-- JS لـ Select2 إذا استخدمناه في المودال --}}
@endpush
