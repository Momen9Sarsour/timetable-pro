<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataEntry\DepartmentController;
use App\Http\Controllers\DataEntry\InstructorController;
use App\Http\Controllers\DataEntry\PlanController;
use App\Http\Controllers\DataEntry\RoleController;
use App\Http\Controllers\DataEntry\RoomController;
use App\Http\Controllers\DataEntry\RoomTypeController;
use App\Http\Controllers\DataEntry\SubjectCategoryController;
use App\Http\Controllers\DataEntry\SubjectController;
use App\Http\Controllers\DataEntry\SubjectTypeController;
use App\Http\Controllers\DataEntry\UserController;
use App\Http\Controllers\DataEntryController;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\Subject;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
// Route::get('/data-entry', [DashboardController::class, 'dataEntry'])->name('dashboard.dataEntry');
// Route::post('/store', [DashboardController::class, 'store'])->name('dashboard.store');



// Group routes that require authentication and maybe admin/HoD roles
// Route::middleware(['auth'])->prefix('dashboard')->group(function () {
Route::prefix('dashboard')->group(function () {

    // Dashboard home
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // --- Data Management Routes ---
    Route::prefix('data-entry')->name('data-entry.')->group(function () {

        // Departments CRUD Routes
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // Rooms CRUD Routes
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index'); // تغيير الاسم
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        // Room Type CRUD Routes
        Route::get('/room-types', [RoomTypeController::class, 'index'])->name('room-types.index');
        Route::post('/room-types', [RoomTypeController::class, 'store'])->name('room-types.store');
        Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update'])->name('room-types.update');
        Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy'])->name('room-types.destroy');
        // Route::resource('room-types', RoomTypeController::class)->except(['create', 'show', 'edit']);

        // Instructors CRUD Routes
        Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
        Route::post('/instructors', [InstructorController::class, 'store'])->name('instructors.store');
        Route::put('/instructors/{instructor}', [InstructorController::class, 'update'])->name('instructors.update');
        Route::delete('/instructors/{instructor}', [InstructorController::class, 'destroy'])->name('instructors.destroy');

        // Subject CRUD & Bulk Upload Routes
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
        Route::post('/subjects/bulk-upload', [SubjectController::class, 'bulkUpload'])->name('subjects.bulkUpload'); // روت الرفع بالجملة

        // Subject-Types CRUD Routes
        // Route::resource('subject-types', SubjectTypeController::class)->except(['create', 'show', 'edit']);
        Route::get('/subject-types', [SubjectTypeController::class, 'index'])->name('subject-types.index');
        Route::post('/subject-types', [SubjectTypeController::class, 'store'])->name('subject-types.store');
        Route::put('/subject-types/{subjectType}', [SubjectTypeController::class, 'update'])->name('subject-types.update');
        Route::delete('/subject-types/{subjectType}', [SubjectTypeController::class, 'destroy'])->name('subject-types.destroy');

        // Subject-Types CRUD Routes
        Route::get('/subject-categories', [SubjectCategoryController::class, 'index'])->name('subject-categories.index');
        Route::post('/subject-categories', [SubjectCategoryController::class, 'store'])->name('subject-categories.store');
        Route::put('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'update'])->name('subject-categories.update');
        Route::delete('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'destroy'])->name('subject-categories.destroy');
        // Route::resource('subject-categories', SubjectCategoryController::class)->except(['create', 'show', 'edit']);


        // Roles CRUD Routes
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // User CRUD Routes
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Plans Management Page
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
        // سنضيف رابط لعرض تفاصيل الخطة وإدارة موادها لاحقاً، ربما show أو edit
        // Route::get('/plans/{plan}/manage-subjects', [PlanController::class, 'manageSubjects'])->name('plans.manageSubjects'); // رابط مقترح لصفحة إدارة المواد
        // Route::get('/plans/{plan}/manage-subjects', [PlanController::class, 'manageSubjects'])->name('plans.manageSubjects');
        // // رابط لمعالجة إضافة المادة (POST)
        // Route::post('/plans/{plan}/add-subject', [PlanController::class, 'addSubject'])->name('plans.addSubject');
        // // رابط لمعالجة حذف المادة (DELETE) - استخدام Route Model Binding لـ PlanSubject
        // Route::delete('/plans/{plan}/remove-subject/{planSubject}', [PlanController::class, 'removeSubject'])->name('plans.removeSubject');

        Route::get('/plans/{plan}/manage-subjects', [PlanController::class, 'manageSubjects'])->name('plans.manageSubjects');
        // رابط معالجة إضافة المادة (يستقبل level و semester في الـ URL)
        Route::post('/plans/{plan}/level/{level}/semester/{semester}/add-subject', [PlanController::class, 'addSubject'])->name('plans.addSubject');
        // رابط معالجة حذف المادة (يستخدم Route Model Binding لـ PlanSubject)
        // Route::delete('/plans/{plan}/remove-subject/{planSubject}', [PlanController::class, 'removeSubject'])->name('plans.removeSubject');
        Route::delete('/plans/{plan}/remove-subject/{planSubject}', [PlanController::class, 'removeSubject'])->name('plans.removeSubject');

        // Route::resource('plans', PlanController::class)->except(['create', 'show', 'edit']);

        // Route::get('/plans', [DataEntryController::class, 'plans'])->name('plans');
        // Add POST/PUT/DELETE routes for plans and plan_subjects later

        // Basic Settings Page (Types, Categories)
        Route::get('/settings', [DataEntryController::class, 'settings'])->name('settings');
        // Add POST/PUT/DELETE routes for settings later

        // Timeslots Management Page
        Route::get('/timeslots', [DataEntryController::class, 'timeslots'])->name('timeslots');
        // Add POST/PUT/DELETE routes for timeslots later

    });
    // --- End Data Management Routes ---


    // Other dashboard routes (Constraints, Algorithm, Reports...) can go here

});
