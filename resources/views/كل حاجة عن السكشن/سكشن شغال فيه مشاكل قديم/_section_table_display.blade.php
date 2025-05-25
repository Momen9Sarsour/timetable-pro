{{-- داخل manage-sections-for-subject.blade.php --}}

{{-- الجزء النظري --}}
@if (($planSubject->subject->theoretical_hours ?? 0) > 0 )
    <div class="card ...">
        {{-- ... card header ... --}}
        <div class="card-body p-0">
            @if($theorySections->isNotEmpty())
                {{-- *** التأكد من اسم الـ partial والمتغيرات الممررة *** --}}
                @include('dashboard.data-entry.partials._section_table_display', ['sections' => $theorySections, 'activityType' => 'Theory'])
            @else
                <p class="text-muted text-center p-3 mb-0">No theory sections defined.</p>
            @endif
        </div>
    </div>
@endif

{{-- الجزء العملي --}}
@if (($planSubject->subject->practical_hours ?? 0) > 0 )
    <div class="card ...">
        {{-- ... card header ... --}}
        <div class="card-body p-0">
            @if($practicalSections->isNotEmpty())
                {{-- *** التأكد من اسم الـ partial والمتغيرات الممررة *** --}}
                @include('dashboard.data-entry.partials._section_table_display', ['sections' => $practicalSections, 'activityType' => 'Practical'])
           @else
               <p class="text-muted text-center p-3 mb-0">No practical sections defined.</p>
           @endif
        </div>
    </div>
@endif
