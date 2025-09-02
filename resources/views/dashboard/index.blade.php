@extends('dashboard.layout')
@section('content')
    <div class="main-content">
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">324</div>
                    <div class="stat-label">Total Courses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">48</div>
                    <div class="stat-label">Active Instructors</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-info">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">12</div>
                    <div class="stat-label">Available Rooms</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">3</div>
                    <div class="stat-label">Current Conflicts</div>
                </div>
            </div>

        </div>

        @include('dashboard.data-entry.partials._status_messages')


        <!-- Generation Info and Controls -->
        <div class="generation-container">
            <div class="generation-header d-flex justify-content-between align-items-center w-100">
                <div class="generation-info">
                    <i class="fas fa-code-branch"></i> Generation #45 | Current fitness score: 0.89
                </div>

                <div class="control-buttons">
                    {{-- <button class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Generate Schedule
                    </button> --}}
                    {{-- <div class="control-buttons"> --}}
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateScheduleModal">
                        <i class="fas fa-sync-alt"></i> Generate Schedule
                    </button>

                    {{-- <button class="btn btn-outline-secondary">
                            <i class="fas fa-pause"></i> Pause
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-stop"></i> Stop
                        </button> --}}
                    {{-- أزرار Pause و Stop (تحتاج لـ JS متقدم) --}}
                    <button class="btn btn-outline-secondary" disabled> <i class="fas fa-pause"></i> Pause </button>
                    <button class="btn btn-outline-secondary" disabled> <i class="fas fa-stop"></i> Stop </button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress-container w-100 mt-3">
                <div class="d-flex justify-content-between mb-1">
                    <small>Progress: 89%</small>
                    <small>Time remaining: 2m 15s</small>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar"
                        style="width: 89%" aria-valuenow="89" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <!-- Schedule Table -->
        <div class="schedule-container">
            <div class="schedule-header">Weekly Schedule</div>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>8:00 AM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>9:00 AM</td>
                        <td>
                            <div class="course-event">CS101</div>
                            <div class="course-event">Room 301</div>
                            <div class="course-event">Dr. Smith</div>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>10:00 AM</td>
                        <td></td>
                        <td>
                            <div class="course-event">MATH201</div>
                            <div class="course-event">Room 205</div>
                            <div class="course-event">Dr. Johnson</div>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>11:00 AM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>12:00 PM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>1:00 PM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>2:00 PM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>3:00 PM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>4:00 PM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>5:00 PM</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Conflicts Section -->
        <div class="conflicts-container">
            <div class="conflicts-header">
                <i class="fas fa-exclamation-triangle"></i> Current Conflicts
            </div>

            <div class="conflict-item">
                <div class="conflict-title">
                    <i class="fas fa-door-open"></i> Room Conflict
                </div>
                <div class="conflict-desc">CS101 and MATH201 scheduled in Room 301 at the same time</div>
            </div>

            <div class="conflict-item">
                <div class="conflict-title">
                    <i class="fas fa-user-tie"></i> Instructor Availability
                </div>
                <div class="conflict-desc">Dr. Smith is scheduled for two classes simultaneously</div>
            </div>

            <div class="conflict-item">
                <div class="conflict-title">
                    <i class="fas fa-clock"></i> Time Constraint
                </div>
                <div class="conflict-desc">DHYS101 scheduled outside of allowed time slot</div>
            </div>
        </div>
    </div>
@endsection


{{-- ===================================================== --}}
{{-- مودال بدء عملية الجدولة مع الإعدادات --}}
{{-- ===================================================== --}}
<div class="modal fade" id="generateScheduleModal" tabindex="-1" aria-labelledby="generateScheduleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateScheduleModalLabel"><i class="fas fa-cogs me-2"></i>Timetable
                    Generation Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- الفورم يرسل لدالة بدء التشغيل في الكنترولر --}}
            <form action="{{ route('algorithm-control.timetable.generate.start') }}" method="POST">
                {{-- <form action="#" method="POST"> --}}
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-4">Configure the parameters for this generation run. Different settings
                        can affect the speed and quality of the result.</p>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="academic_year" class="form-label">Academic Year <span
                                    class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('academic_year') is-invalid @enderror"
                                id="academic_year" name="academic_year" value="{{ old('academic_year', date('Y')) }}"
                                required placeholder="e.g., 2025">
                            @error('academic_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="semester" class="form-label">Semester <span
                                    class="text-danger">*</span></label>
                            <select class="form-select @error('semester') is-invalid @enderror" id="semester"
                                name="semester" required>
                                <option value="" disabled>Select a semester...</option>
                                <option value="1" {{ old('semester') == 1 ? 'selected' : '' }}>First</option>
                                <option value="2" {{ old('semester') == 2 ? 'selected' : '' }}>Second</option>
                                <option value="3" {{ old('semester') == 3 ? 'selected' : '' }}>Summer</option>
                            </select>
                            @error('semester')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    {{-- *********************************** --}}

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="setting_population_size" class="form-label">Population Size</label>
                            <input type="number" class="form-control" id="setting_population_size"
                                name="population_size" value="{{ config('algorithm.settings.population_size', 10) }}"
                                required min="10" step="10">
                            <small class="text-muted">No. of schedules per generation.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="setting_max_generations" class="form-label">Max Generations</label>
                            <input type="number" class="form-control" id="setting_max_generations"
                                name="max_generations" value="{{ config('algorithm.settings.max_generations', 10) }}"
                                required min="10" step="10">
                            <small class="text-muted">When to stop if no solution is found.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="setting_mutation_rate" class="form-label">Mutation Rate</label>
                            <input type="number" class="form-control" id="setting_mutation_rate"
                                name="mutation_rate" value="{{ config('algorithm.settings.mutation_rate', 0.05) }}"
                                required min="0" max="1" step="0.01">
                            <small class="text-muted">e.g., 0.01 for 1%.</small>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="mb-3">Lecture Duration Settings</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="setting_theory_credit_to_slots" class="form-label">Slots per Theory Credit
                                Hour</label>
                            <input type="number"
                                class="form-control @error('theory_credit_to_slots') is-invalid @enderror"
                                id="setting_theory_credit_to_slots" name="theory_credit_to_slots"
                                value="{{ old('theory_credit_to_slots', 1) }}" required min="1"
                                max="4">
                            <small class="text-muted">How many consecutive timeslots for each theoretical credit hour.
                                (e.g., 1)</small>
                            @error('theory_credit_to_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="setting_practical_credit_to_slots" class="form-label">Slots per Practical
                                Credit Hour</label>
                            <input type="number"
                                class="form-control @error('practical_credit_to_slots') is-invalid @enderror"
                                id="setting_practical_credit_to_slots" name="practical_credit_to_slots"
                                value="{{ old('practical_credit_to_slots', 2) }}" required min="1"
                                max="4">
                            <small class="text-muted">How many consecutive timeslots for each practical credit hour.
                                (e.g., 2)</small>
                            @error('practical_credit_to_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="setting_crossover_type_id" class="form-label">Crossover Method</label>
                            <select class="form-select" id="setting_crossover_type_id" name="crossover_type_id"
                                required>
                                {{-- جلب الخيارات من قاعدة البيانات --}}
                                @foreach (\App\Models\CrossoverType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->crossover_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.crossover_type_id') == $type->crossover_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="setting_selection_type_id" class="form-label">Selection Method</label>
                            <select class="form-select" id="setting_selection_type_id" name="selection_type_id"
                                required>
                                @foreach (\App\Models\SelectionType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->selection_type_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.selection_type_id') == $type->selection_type_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="crossover_rate" class="form-label">Crossover Rate (0.0 - 1.0)</label>
                            <input type="number" class="form-control" id="crossover_rate" value="0.8"
                                step="0.1" min="0" max="1" name="crossover_rate"
                                value="{{ config('algorithm.settings.crossover_rate', 0.1) }}" required
                                min="10" step="10">
                            <small class="text-muted">احتمالية تطبيق التزاوج بين الآباء</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="setting_mutation_type_id" class="form-label">Mutation Type</label>
                            <select class="form-select" id="setting_mutation_type_id" name="mutation_type_id"
                                required>
                                {{-- جلب الخيارات من قاعدة البيانات --}}
                                @foreach (\App\Models\mutationType::where('is_active', true)->get() as $type)
                                    <option value="{{ $type->mutation_id }}" title="{{ $type->description }}"
                                        {{ config('algorithm.settings.mutation_type_id') == $type->mutation_id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">When to stop if no solution is found.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="setting_selection_size" class="form-label">Selection Tournament Size</label>
                            <input type="number" class="form-control" id="setting_selection_size"
                                name="selection_size" value="{{ config('algorithm.settings.selection_size', 3) }}"
                                required value="3" min="2" max="10">
                            <small class="text-muted">عدد المتنافسين في كل tournament</small>
                        </div>
                    </div>

                    <hr>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="setting_stop_at_first_valid" name="stop_at_first_valid" value="1"
                            {{ config('algorithm.settings.stop_at_first_valid', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="setting_stop_at_first_valid">Stop at First Valid
                            Solution</label>
                        <small class="text-muted d-block">Stops when it finds a schedule with zero hard-constraint
                            violations.</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-1"></i> Start Generation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
