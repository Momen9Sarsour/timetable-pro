{{-- هذا الملف يعرض شبكة الجدول الأسبوعي مع دعم البلوكات الممتدة - التخطيط الأفقي --}}
@push('styles')
<style>
    .timetable-container {
        overflow-x: auto;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        font-size: 0.7rem; /* تصغير الخط العام */
    }

    .timetable {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        min-width: 900px; /* تقليل العرض الأدنى */
        background: white;
    }

    .timetable th, .timetable td {
        border: 1px solid #d1d5db;
        padding: 0.25rem; /* تقليل الـ padding */
        text-align: center;
        vertical-align: top;
        position: relative;
    }

    .timetable th {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        font-weight: 600;
        font-size: 0.65rem; /* تصغير خط الهيدر */
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 2px solid #3b82f6;
        padding: 0.4rem 0.25rem; /* تقليل الارتفاع */
    }

    .day-col {
        width: 90px; /* تقليل عرض عمود الأيام */
        font-weight: bold;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: #1f2937;
        font-size: 0.7rem; /* تصغير خط الأيام */
        position: sticky;
        left: 0;
        z-index: 10;
        writing-mode: horizontal-tb;
        text-align: center;
    }

    .time-header {
        min-width: 100px; /* تقليل عرض أعمدة الوقت */
        font-size: 0.6rem; /* تصغير خط الأوقات */
        text-align: center;
        padding: 0.4rem 0.25rem;
    }

    .slot-cell {
        width: 100px; /* تقليل عرض الخلايا */
        min-width: 100px;
        height: 70px; /* تقليل الارتفاع */
        min-height: 70px;
        background: #fafbfc;
        transition: background-color 0.2s;
        padding: 2px; /* تقليل الـ padding الداخلي */
    }

    .slot-cell:hover {
        background: #f0f9ff;
    }

    .event-block {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border: 1px solid #3b82f6; /* تقليل سمك الحد */
        border-radius: 4px; /* تقليل الانحناء */
        padding: 2px; /* تقليل الـ padding الداخلي */
        font-size: 0.55rem; /* تصغير خط البلوك */
        margin: 1px;
        text-align: left;
        position: relative;
        min-height: 60px; /* تقليل الارتفاع الأدنى */
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* تقليل الظل */
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
        line-height: 1.1; /* تقليل المسافة بين الأسطر */
    }

    .event-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
        z-index: 20;
    }

    .event-block.theory-block {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        border-color: #22c55e;
    }

    .event-block.practical-block {
        background: linear-gradient(135deg, #fecaca 0%, #f87171 100%);
        border-color: #ef4444;
    }

    /* البلوك الممتد أفقياً عبر عدة أعمدة */
    .extended-block {
        position: absolute;
        top: 2px;
        left: 2px;
        z-index: 15;
        height: calc(100% - 4px);
        background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%);
        border: 2px solid #f59e0b;
        border-radius: 4px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }

    .extended-block:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        z-index: 25;
    }

    .event-subject {
        font-weight: bold;
        font-size: 0.6rem; /* تصغير خط اسم المادة */
        color: #1f2937;
        margin-bottom: 2px;
        line-height: 1.1;
        text-align: center;
    }

    .event-details {
        font-size: 0.5rem; /* تصغير خط التفاصيل */
        color: #6b7280;
        line-height: 1.2;
        text-align: center;
    }

    .event-instructor {
        color: #059669;
        font-weight: 500;
    }

    .event-room {
        color: #dc2626;
        font-style: italic;
    }

    .block-duration {
        position: absolute;
        top: 1px;
        right: 2px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        font-size: 0.45rem; /* تصغير خط المدة */
        padding: 1px 3px;
        border-radius: 2px;
        font-weight: bold;
    }

    .block-span-indicator {
        position: absolute;
        top: 50%;
        right: 1px;
        transform: translateY(-50%);
        background: rgba(59, 130, 246, 0.8);
        color: white;
        font-size: 0.45rem; /* تصغير المؤشر */
        padding: 1px 3px;
        border-radius: 6px;
        font-weight: bold;
    }

    /* تصغير الأيقونات */
    .fas {
        font-size: 0.65rem !important;
    }

    .badge {
        font-size: 0.45rem !important; /* تصغير البادج */
        padding: 1px 3px !important;
    }

    /* تحسينات الاستجابة */
    @media (max-width: 768px) {
        .timetable-container {
            font-size: 0.6rem;
        }

        .timetable th, .timetable td {
            padding: 0.15rem;
            font-size: 0.55rem;
        }

        .event-block {
            font-size: 0.5rem;
            padding: 2px;
            min-height: 50px;
        }

        .day-col {
            width: 70px;
            font-size: 0.6rem;
        }

        .time-header {
            min-width: 80px;
            font-size: 0.5rem;
        }

        .slot-cell {
            width: 80px;
            min-width: 80px;
            height: 60px;
        }

        .event-subject {
            font-size: 0.5rem;
        }

        .event-details {
            font-size: 0.45rem;
        }
    }
</style>
@endpush

<div class="timetable-container">
    <table class="timetable">
        <thead>
            <tr>
                <th class="day-col">
                    <i class="fas fa-calendar-day me-1"></i>Days
                </th>
                @php
                    // استخراج أوقات البدء الفريدة من كل الأيام وترتيبها
                    $uniqueStartTimes = collect($timeslots)->flatMap(fn($daySlots) => $daySlots)->pluck('start_time')->unique()->sort();
                @endphp
                @foreach($uniqueStartTimes as $startTime)
                    <th class="time-header">
                        <div class="fw-bold">{{ \Carbon\Carbon::parse($startTime)->format('g:i A') }}</div>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($startTime)->format('H:i') }}</small>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $dayIcons = [
                    'Sunday' => 'fas fa-sun text-warning',
                    'Monday' => 'fas fa-moon text-primary',
                    'Tuesday' => 'fas fa-star text-info',
                    'Wednesday' => 'fas fa-mountain text-success',
                    'Thursday' => 'fas fa-tree text-success',
                    'Friday' => 'fas fa-mosque text-danger',
                    'Saturday' => 'fas fa-home text-secondary'
                ];
                $processedBlocks = []; // لتجنب عرض البلوك أكثر من مرة
            @endphp

            @foreach($days as $day)
                @if(isset($timeslots[$day])) {{-- فقط اعرض الأيام الموجودة في الـ timeslots --}}
                    <tr>
                        <th class="day-col">
                            <i class="{{ $dayIcons[$day] ?? 'fas fa-calendar' }} me-1"></i>
                            {{ substr($day, 0, 3) }} {{-- اختصار اسم اليوم --}}
                        </th>

                        @foreach($uniqueStartTimes as $startTime)
                            <td class="slot-cell" data-day="{{ $day }}" data-time="{{ $startTime }}">
                                @if(isset($schedule[$day][$startTime]))
                                    @foreach($schedule[$day][$startTime] as $gene)
                                        @php
                                            // تجنب عرض نفس البلوك مرتين
                                            $blockId = $gene->gene_id . '-' . $day . '-' . $startTime;
                                            if (in_array($blockId, $processedBlocks)) continue;
                                            $processedBlocks[] = $blockId;

                                            // تحديد نوع النشاط للتلوين
                                            $activityType = strtolower($gene->section->activity_type ?? 'unknown');
                                            $blockClass = $activityType === 'theory' ? 'theory-block' : 'practical-block';

                                            // معلومات البلوك
                                            $blockSize = $gene->block_size ?? 1;
                                            $duration = $gene->block_duration ?? 50; // دقيقة

                                            // إذا كان البلوك ممتد، احسب العرض
                                            $blockWidth = '';
                                            if ($blockSize > 1) {
                                                $cellWidth = 100; // عرض الخلية الواحدة المُصغر
                                                $totalWidth = ($cellWidth * $blockSize) + (($blockSize - 1) * 1); // مع الحدود
                                                $blockWidth = "width: {$totalWidth}px;";
                                                $blockClass = 'extended-block';
                                            }
                                        @endphp

                                        <div class="event-block {{ $blockClass }}"
                                             data-block-size="{{ $blockSize }}"
                                             data-duration="{{ $duration }}"
                                             style="{{ $blockWidth }}"
                                             title="Duration: {{ $duration }} minutes | Slots: {{ $blockSize }}">

                                            {{-- مدة البلوك --}}
                                            @if($blockSize > 1)
                                                <span class="block-duration">{{ $duration }}m</span>
                                                <span class="block-span-indicator">×{{ $blockSize }}</span>
                                            @endif

                                            {{-- معلومات المادة --}}
                                            <div class="event-subject">
                                                {{ optional(optional($gene->section)->planSubject->subject)->subject_no }}
                                                <br>
                                                {{ Str::limit(optional(optional($gene->section)->planSubject->subject)->subject_name, 15) }}
                                            </div>

                                            {{-- التفاصيل --}}
                                            <div class="event-details">
                                                @if(isset($gene->section))
                                                    <div class="mb-1">
                                                        <i class="fas fa-users me-1"></i>S{{ $gene->section->section_number }}
                                                        <span class="badge ms-1 {{ $activityType === 'theory' ? 'bg-success' : 'bg-danger' }}">
                                                            {{ $activityType === 'theory' ? 'T' : 'P' }}
                                                        </span>
                                                    </div>
                                                @endif

                                                @if(isset($gene->instructor))
                                                    <div class="event-instructor mb-1">
                                                        <i class="fas fa-user me-1"></i>
                                                        {{ Str::limit(optional($gene->instructor->user)->name ?? $gene->instructor->instructor_name, 12) }}
                                                    </div>
                                                @endif

                                                @if(isset($gene->room))
                                                    <div class="event-room">
                                                        <i class="fas fa-door-open me-1"></i>{{ $gene->room->room_no }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>

{{-- إضافة معلومات إحصائية --}}
<div class="mt-3 d-flex justify-content-between align-items-center text-muted small">
    <div>
        <span class="badge bg-success me-2">
            <i class="fas fa-book me-1"></i>Theory
        </span>
        <span class="badge bg-danger me-2">
            <i class="fas fa-flask me-1"></i>Practical
        </span>
        <span class="badge bg-warning me-2">
            <i class="fas fa-expand-arrows-alt me-1"></i>Extended Block
        </span>
        <span class="text-muted ms-2">Total Time Slots: {{ count($uniqueStartTimes) }}</span>
    </div>
    <div>
        <i class="fas fa-calendar-check me-1"></i>
        Last Updated: {{ now()->format('M d, Y H:i') }}
    </div>
</div>
