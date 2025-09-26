@extends('dashboard.layout')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h5 class="page-title mb-1">
                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                    Timetable (Read-Only) — Chromosome #{{ $chromosome->chromosome_id }}
                </h5>
                <div class="text-muted" style="font-size: .8rem;">
                    <span class="badge bg-light text-dark me-1">Generation: {{ $chromosome->generation_number }}</span>
                    <span class="badge bg-light text-dark me-1">Run ID: {{ $chromosome->population_id ?? ($chromosome->population->population_id ?? '-') }}</span>
                    <span class="badge bg-light text-dark">Penalty: {{ $chromosome->penalty_value ?? 0 }}</span>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    @include('dashboard.data-entry.partials._status_messages')

    <!-- Timetable -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar-week text-primary me-2"></i>
                        Read-Only Schedule
                    </h6>
                    <span class="text-muted" style="font-size:.8rem">Display only • handles NULL/empty fields safely</span>
                </div>

                <div class="card-body p-0">
                    <div class="timetable-wrapper">
                        <div class="timetable-container" id="timetableContainer">
                            <table class="timetable table-bordered mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th class="group-header sticky-start">Student Group</th>
                                        @foreach ($timeslotsByDay as $day => $daySlots)
                                            <th class="day-header text-center" colspan="{{ $daySlots->count() }}">
                                                {{ $day }}
                                            </th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        <th class="group-header sticky-start"></th>
                                        @php $flatTimeslots = collect($timeslotsByDay)->flatten(); @endphp
                                        @foreach ($flatTimeslots as $ts)
                                            <th class="time-header text-center">
                                                {{ \Carbon\Carbon::parse($ts->start_time)->format('H:i') }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        // ترتيب المجموعات بالاسم
                                        $sortedGroups = collect($scheduleByGroup)->sortBy(fn($g) => $g['name'] ?? 'Ungrouped');
                                    @endphp

                                    @forelse ($sortedGroups as $groupKey => $group)
                                        <tr>
                                            <th class="group-header sticky-start">
                                                {{ $group['name'] ?? 'Ungrouped' }}
                                            </th>

                                            <td class="group-row position-relative" colspan="{{ $totalColumnsOverall }}">
                                                {{-- خلفية الشبكة --}}
                                                <div class="grid-background d-flex position-absolute w-100 h-100" style="top:0;left:0;pointer-events:none;">
                                                    @for ($i = 0; $i < $totalColumnsOverall; $i++)
                                                        <div class="grid-column flex-fill"></div>
                                                    @endfor
                                                </div>

                                                {{-- البلوكات --}}
                                                @foreach (collect($group['blocks'] ?? [])->unique('gene_id') as $block)
                                                    @php
                                                        // block هو مصفوفة (DTO)
                                                        $startColumn = $block['start_column'] ?? null;
                                                        $span        = max(1, (int)($block['span'] ?? 1));
                                                        if ($startColumn === null) continue;

                                                        $left   = ($startColumn / max(1,$totalColumnsOverall)) * 100;
                                                        $width  = ($span / max(1,$totalColumnsOverall)) * 100;
                                                        $height = 64;
                                                        $top    = 4; // صف واحد فقط للعرض

                                                        $label  = $block['label'] ?? '—';
                                                        $timeslotIds = $block['timeslot_ids'] ?? [];
                                                        $typeTxt  = $block['block_type'] ? ucfirst($block['block_type']) : '';
                                                        $durTxt   = ($block['block_duration'] ?? 0) ? ($block['block_duration'].'min') : '';
                                                    @endphp

                                                    <div class="event-block"
                                                         title="Gene #{{ $block['gene_id'] }} | {{ $typeTxt }} {{ $durTxt ? ' • '.$durTxt : '' }}"
                                                         style="
                                                            top: {{ $top }}px;
                                                            left: {{ $left }}%;
                                                            width: calc({{ $width }}% - 3px);
                                                            height: {{ $height }}px;
                                                         ">
                                                        <div class="event-content d-flex flex-column h-100">
                                                            <div class="event-title text-truncate">
                                                                {{ $label }}
                                                            </div>
                                                            <div class="event-meta mt-auto d-flex justify-content-between text-muted">
                                                                <span>{{ $typeTxt }}</span>
                                                                <span class="text-primary">{{ count($timeslotIds) }} slots</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $totalColumnsOverall + 1 }}" class="text-center py-4">
                                                <i class="fas fa-info-circle text-muted me-1"></i>
                                                No blocks to display for this chromosome.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div> <!-- /wrapper -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ألوان متغيرة بسيطة */
:root{
    --primary:#0d6efd;
    --primary-dark:#0b5ed7;
    --border:#e7e9ee;
    --bg:#f8f9fb;
    --text:#2b2f33;
    --text-muted:#6c757d;
}

/* الجدول */
.timetable-wrapper{
    overflow:auto;
    border:1px solid var(--border);
    border-radius:8px;
    background:var(--bg);
    height:70vh;
}

.timetable{
    border-collapse:separate;
    border-spacing:0;
    font-size:.78rem;
    min-width:1400px; /* يتسع للوقت */
    width:max-content;
    background:white;
}

.timetable th, .timetable td{
    border:1px solid var(--border);
    padding:0;
    vertical-align:top;
}

.day-header{
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff; font-weight:700; padding:.5rem; position:sticky; top:0; z-index:10;
}

.time-header{
    background:#f2f4f6; color:#65707b; font-weight:600; padding:.35rem; position:sticky; top:36px; z-index:9;
    min-width:90px; width:90px;
}

.group-header{
    background:#f7f9fb; font-weight:700; padding:.5rem .65rem; width:230px; min-width:230px;
    position:sticky; left:0; z-index:11; border-right:2px solid var(--primary) !important;
}

.group-row{
    height:90px; min-height:90px; position:relative; background:#fff;
}

/* الشبكة داخل كل صف */
.grid-column{
    border-right:1px solid rgba(0,0,0,.04);
}
.grid-column:nth-child(5n){
    border-right:1px solid rgba(0,0,0,.08);
}

/* البلوك (العرض فقط) */
.event-block{
    position:absolute;
    background:#ffffff;
    border:2px solid var(--primary);
    border-radius:6px;
    padding:6px;
    box-shadow:0 2px 8px rgba(13,110,253,.12);
    overflow:hidden;
}

.event-title{
    font-weight:700;
    color:var(--primary);
    font-size:.8rem;
    line-height:1.2;
}

.event-meta{
    font-size:.72rem;
}

/* لزوم الثبات */
.sticky-top{ position:sticky; top:0; z-index:20; }
.sticky-start{ position:sticky; left:0; z-index:21; }

@media (max-width: 992px){
    .group-header{ width:180px; min-width:180px; }
    .time-header{ min-width:72px; width:72px; font-size:.72rem; }
    .event-title{ font-size:.76rem; }
}
</style>
@endpush
