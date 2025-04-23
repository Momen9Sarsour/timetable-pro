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

        <!-- Generation Info and Controls -->
        <div class="generation-container">
            <div class="generation-header d-flex justify-content-between align-items-center w-100">
                <div class="generation-info">
                    <i class="fas fa-code-branch"></i> Generation #45 | Current fitness score: 0.89
                </div>

                <div class="control-buttons">
                    <button class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Generate Schedule
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-pause"></i> Pause
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-stop"></i> Stop
                    </button>
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
