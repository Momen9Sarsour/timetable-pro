<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Instructor;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Subject;
use App\Models\SubjectCategory;
use App\Models\SubjectType;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DataEntryController extends Controller
{
    // **************************************** Department Management ****************************************
    public function departments() {}

    // Add , store, update, destroy methods
    public function storeDepartment(Request $request) {}

    //  * Update the specified department in storage.

    public function updateDepartment(Request $request, Department $department) {}

    //  * Remove the specified department from storage.

    public function destroyDepartment(Department $department) {}

    // **************************************** Room Management ****************************************
    public function index() {}

    /**
     * Store a newly created room in storage.
     */
    public function storeRoom(Request $request) {}

    /**
     * Update the specified room in storage.
     */
    public function updateRoom(Request $request, Room $room) {}

    /**
     * Remove the specified room from storage.
     */
    public function destroyRoom(Room $room) {}

    // **************************************** Instructor Management ****************************************
    public function instructors() {}

    /**
     * Update the specified instructor profile in storage.
     */
    // نستخدم Route Model Binding مع Instructor
    public function updateInstructor(Request $request, Instructor $instructor) {}

    /**
     * Remove the specified instructor profile from storage.
     */
    public function destroyInstructor(Instructor $instructor) {}

    // **************************************** Subject Management ****************************************
    public function subjects() {}

    // **************************************** Rple Management ****************************************


    // **************************************** User Management ****************************************
    public function users() {}

    // **************************************** Plan Management ****************************************
    public function plans()
    {
        // $this->authorize('manage-plans');
        $plans = Plan::with('department')->orderBy('year', 'desc')->orderBy('plan_name')->get();
        $departments = Department::orderBy('department_name')->get();
        // سنحتاج أيضاً لجلب المواد لإضافتها للخطط في الواجهة
        $subjects = Subject::orderBy('subject_name')->get();
        return view('dashboard.data-entry.plans', compact('plans', 'departments', 'subjects'));
    }
    // Add store, update, destroy methods for plans and plan_subjects later

    // --- Basic Settings Management ---
    public function settings()
    {
        //$this->authorize('manage-settings');
        $roomTypes = RoomType::orderBy('room_type_name')->get();
        $subjectTypes = SubjectType::orderBy('subject_type_name')->get();
        $subjectCategories = SubjectCategory::orderBy('subject_category_name')->get();
        // يمكنك جلب إعدادات أخرى هنا إذا أردت
        return view('dashboard.data-entry.settings', compact('roomTypes', 'subjectTypes', 'subjectCategories'));
    }
    // Add store, update, destroy methods later

    // --- Timeslot Management ---
    public function timeslots()
    {
        // $this->authorize('manage-timeslots');
        $timeslots = Timeslot::orderBy('day_of_week')->orderBy('start_time')->get();

        return view('dashboard.data-entry.timeslots', compact('timeslots'));
    }
    // Add store, update, destroy methods later

}
