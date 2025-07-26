{{-- هذا الملف يعرض شبكة الجدول الأسبوعي --}}
@push('styles')
<style>
    .timetable { border-collapse: collapse; width: 100%; }
    .timetable th, .timetable td { border: 1px solid #dee2e6; padding: 0.5rem; text-align: center; vertical-align: top; }
    .timetable th { background-color: #f8f9fa; }
    .time-col { width: 100px; font-weight: bold; }
    .slot-cell { height: 80px; }
    .event-block { background-color: #e9f5ff; border: 1px solid #b3d7ff; border-radius: 5px; padding: 5px; font-size: 0.8rem; margin-bottom: 5px; text-align: left; }
    .event-subject { font-weight: bold; }
    .event-details { font-size: 0.75rem; color: #555; }
</style>
@endpush

<div class="table-responsive">
    <table class="timetable">
        <thead>
            <tr>
                <th class="time-col">Time</th>
                @php $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; @endphp
                @foreach($days as $day)
                    @if(isset($timeslots[$day])) {{-- فقط اعرض الأيام الموجودة في الـ timeslots --}}
                        <th>{{ $day }}</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                 // استخراج أوقات البدء الفريدة من كل الأيام وترتيبها
                 $uniqueStartTimes = collect($timeslots)->flatMap(fn($daySlots) => $daySlots)->pluck('start_time')->unique()->sort();
            @endphp
            @foreach($uniqueStartTimes as $startTime)
                <tr>
                    <th class="time-col">{{ \Carbon\Carbon::parse($startTime)->format('h:i A') }}</th>
                    @foreach($days as $day)
                         @if(isset($timeslots[$day]))
                            <td class="slot-cell">
                                @if(isset($schedule[$day][$startTime]))
                                    @foreach($schedule[$day][$startTime] as $gene)
                                        <div class="event-block">
                                            <div class="event-subject">{{ optional(optional($gene->section)->planSubject->subject)->subject_no }} - {{ optional(optional($gene->section)->planSubject->subject)->subject_name }}</div>
                                            <div class="event-details">
                                                 {{-- عرض معلومات مختلفة حسب نوع الجدول --}}
                                                 @if(isset($gene->section))
                                                     Sec: {{ $gene->section->section_number }}({{ $gene->section->activity_type }})
                                                 @endif
                                                 @if(isset($gene->instructor))
                                                     <br>{{ Str::limit(optional($gene->instructor->user)->name, 30) }}
                                                 @endif
                                                 @if(isset($gene->room))
                                                     <br>Room: {{ $gene->room->room_name }}
                                                 @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
