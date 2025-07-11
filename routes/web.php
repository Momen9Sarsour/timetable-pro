<?php

use App\Http\Controllers\Algorithm\TimetableGenerationController;
use App\Http\Controllers\Algorithm\TimetableResultController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataEntry\CrossoverTypeController;
use App\Http\Controllers\DataEntry\DepartmentController;
use App\Http\Controllers\DataEntry\InstructorController;
use App\Http\Controllers\DataEntry\InstructorLoadAssignmentController;
use App\Http\Controllers\DataEntry\InstructorSectionController;
use App\Http\Controllers\DataEntry\InstructorSubjectController;
use App\Http\Controllers\DataEntry\InstructorSubjectOldController;
use App\Http\Controllers\DataEntry\InstructorSubjectsController;
use App\Http\Controllers\DataEntry\PlanController;
use App\Http\Controllers\DataEntry\PlanExpectedCountController;
use App\Http\Controllers\DataEntry\PlanSubjectImportController;
use App\Http\Controllers\DataEntry\RoleController;
use App\Http\Controllers\DataEntry\RoomController;
use App\Http\Controllers\DataEntry\RoomTypeController;
use App\Http\Controllers\DataEntry\SectionController;
use App\Http\Controllers\DataEntry\SectionController22;
use App\Http\Controllers\DataEntry\SelectionTypeController;
use App\Http\Controllers\DataEntry\SubjectCategoryController;
use App\Http\Controllers\DataEntry\SubjectController;
use App\Http\Controllers\DataEntry\SubjectTypeController;
use App\Http\Controllers\DataEntry\TimeslotController;
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
        // Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit']);
        Route::post('/departments/bulk-upload', [DepartmentController::class, 'bulkUpload'])->name('departments.bulkUpload');

        // Room Type CRUD Routes
        Route::get('/room-types', [RoomTypeController::class, 'index'])->name('room-types.index');
        Route::post('/room-types', [RoomTypeController::class, 'store'])->name('room-types.store');
        Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update'])->name('room-types.update');
        Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy'])->name('room-types.destroy');
        // Route::resource('room-types', RoomTypeController::class)->except(['create', 'show', 'edit']);
        Route::post('/room-types/bulk-upload', [RoomTypeController::class, 'bulkUpload'])->name('room-types.bulkUpload');

        // Rooms CRUD Routes
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        // Route::resource('rooms', RoomController::class)->except(['create', 'show', 'edit']);
        Route::post('/rooms/bulk-upload', [RoomController::class, 'bulkUpload'])->name('rooms.bulkUpload');

        // Subject-Types CRUD Routes
        Route::get('/subject-types', [SubjectTypeController::class, 'index'])->name('subject-types.index');
        Route::post('/subject-types', [SubjectTypeController::class, 'store'])->name('subject-types.store');
        Route::put('/subject-types/{subjectType}', [SubjectTypeController::class, 'update'])->name('subject-types.update');
        Route::delete('/subject-types/{subjectType}', [SubjectTypeController::class, 'destroy'])->name('subject-types.destroy');
        // Route::resource('subject-types', SubjectTypeController::class)->except(['create', 'show', 'edit']);
        Route::post('/subject-types/bulk-upload', [SubjectTypeController::class, 'bulkUpload'])->name('subject-types.bulkUpload');

        // Subject-Types CRUD Routes
        Route::get('/subject-categories', [SubjectCategoryController::class, 'index'])->name('subject-categories.index');
        Route::post('/subject-categories', [SubjectCategoryController::class, 'store'])->name('subject-categories.store');
        Route::put('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'update'])->name('subject-categories.update');
        Route::delete('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'destroy'])->name('subject-categories.destroy');
        // Route::resource('subject-categories', SubjectCategoryController::class)->except(['create', 'show', 'edit']);
        Route::post('/subject-categories/bulk-upload', [SubjectCategoryController::class, 'bulkUpload'])->name('subject-categories.bulkUpload');

        // Subject CRUD & Bulk Upload Routes
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
        Route::post('/subjects/bulk-upload', [SubjectController::class, 'bulkUpload'])->name('subjects.bulkUpload');
        // Route::resource('subjects', SubjectController::class)->except(['create', 'show', 'edit']);
        Route::post('/subjects/bulk-upload', [SubjectController::class, 'bulkUpload'])->name('subjects.bulkUpload');

        // Roles CRUD Routes
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        // Route::resource('roles', RoleController::class)->except(['create', 'show', 'edit']);

        // User CRUD Routes
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        // Route::resource('roles', UserController::class)->except(['create', 'show', 'edit']);

        // Instructors CRUD Routes
        Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
        Route::post('/instructors', [InstructorController::class, 'store'])->name('instructors.store');
        Route::put('/instructors/{instructor}', [InstructorController::class, 'update'])->name('instructors.update');
        Route::delete('/instructors/{instructor}', [InstructorController::class, 'destroy'])->name('instructors.destroy');
        // Route::resource('instructors', InstructorController::class)->except(['create', 'show', 'edit']);
        // *** روت جديد لرفع ملف الإكسل للمدرسين ***
        Route::post('/instructors/import-excel', [InstructorController::class, 'importExcel'])->name('instructors.importExcel');


        // Plans Management Page
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
        Route::post('/plans/bulk-upload', [PlanController::class, 'bulkUpload'])->name('plans.bulkUpload'); // *** روت الرفع للويب ***

        // سنضيف رابط لعرض تفاصيل الخطة وإدارة موادها لاحقاً، ربما show أو edit
        // Route::get('/plans/{plan}/manage-subjects', [PlanController::class, 'manageSubjects'])->name('plans.manageSubjects'); // رابط مقترح لصفحة إدارة المواد
        // Route::get('/plans/{plan}/manage-subjects', [PlanController::class, 'manageSubjects'])->name('plans.manageSubjects');
        // // رابط لمعالجة إضافة المادة (POST)
        // Route::post('/plans/{plan}/add-subject', [PlanController::class, 'addSubject'])->name('plans.addSubject');
        // // رابط لمعالجة حذف المادة (DELETE) - استخدام Route Model Binding لـ PlanSubject
        // Route::delete('/plans/{plan}/remove-subject/{planSubject}', [PlanController::class, 'removeSubject'])->name('plans.removeSubject');

        Route::get('/plans/{plan}/manage-subjects', [PlanController::class, 'manageSubjects'])->name('plans.manageSubjects');
        Route::post('/plans/{plan}/level/{level}/semester/{semester}/add-subject', [PlanController::class, 'addSubject'])->name('plans.addSubject');
        Route::delete('/plans/{plan}/remove-subject/{planSubject}', [PlanController::class, 'removeSubject'])->name('plans.removeSubject');
        // Route::resource('plans', PlanController::class)->except(['create', 'show', 'edit']);

        // Route::post('/plans/{plan}/bulk-upload-subjects', [PlanController::class, 'bulkUploadPlanSubjects'])->name('plans.bulkUploadSubjects'); // *** الرابط الجديد ***
        Route::post('/plans/{plan}/import-subjects-excel', [PlanSubjectImportController::class, 'handleImport'])->name('plans.importSubjectsExcel');

        // --- Plan Expected Counts Management ---
        Route::get('/plan-expected-counts', [PlanExpectedCountController::class, 'index'])->name('plan-expected-counts.index');
        Route::post('/plan-expected-counts', [PlanExpectedCountController::class, 'store'])->name('plan-expected-counts.store');
        Route::put('/plan-expected-counts/{planExpectedCount}', [PlanExpectedCountController::class, 'update'])->name('plan-expected-counts.update'); // لاحظ {planExpectedCount}
        Route::delete('/plan-expected-counts/{planExpectedCount}', [PlanExpectedCountController::class, 'destroy'])->name('plan-expected-counts.destroy'); // لاحظ {planExpectedCount}
        // Route::resource('plan-expected-counts', PlanExpectedCountController::class)->except(['create', 'show', 'edit']);

        // --- Sections Management ---
        Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        Route::get('/sections/manage', [SectionController::class, 'manageSubjectContext'])->name('sections.manageSubjectContext');
        Route::post('/sections/generate-for-subject', [SectionController::class, 'generateForSubject'])->name('sections.generateForSubject');
        Route::post('/sections/store', [SectionController::class, 'store'])->name('sections.store');
        Route::put('/sections/{section}/update', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}/destroy', [SectionController::class, 'destroy'])->name('sections.destroy');
        // Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        // Route::get('/sections/manage', [SectionController::class, 'manageSubjectContext'])->name('sections.manageSubjectContext');
        // Route::post('/sections/generate', [SectionController::class, 'generateForSubject'])->name('sections.generateForSubject');
        // Route::get('/sections/manage-subject-context', [SectionController::class, 'manageSubjectContext'])->name('sections.manageSubjectContext');
        // Route::post('/sections/store', [SectionController::class, 'store'])->name('sections.store');
        // Route::put('/sections/{section}/update', [SectionController::class, 'update'])->name('sections.update');
        // Route::delete('/sections/{section}/destroy', [SectionController::class, 'destroy'])->name('sections.destroy');

        // ************************************
        Route::get('/sections/manage-context/{expectedCount}', [SectionController22::class, 'manageSectionsForContext'])->name('sections.manageContext');
        // روت لتشغيل التقسيم الآلي من الزر
        Route::post('/sections/generate-for-context/{expectedCount}', [SectionController22::class, 'generateSectionsForContextButton'])->name('sections.generateForContext');
        // روابط CRUD للشعب داخل هذا السياق (ستُستخدم من المودالات في صفحة manageContext)
        Route::post('/sections/store-in-context22/{expectedCount}', [SectionController22::class, 'storeSectionInContext'])->name('sections.storeInContext');
        Route::put('/sections/update-in-context/{section}', [SectionController22::class, 'updateSectionInContext'])->name('sections.updateInContext'); // {section} هنا هو section_id
        Route::delete('/sections/destroy-in-context/{section}', [SectionController22::class, 'destroySectionInContext'])->name('sections.destroyInContext');

        // --- Instructor Section Subject Assignments ---
        Route::get('/instructor-section', [InstructorSectionController::class, 'index'])->name('instructor-section.index'); // صفحة العرض الرئيسية
        Route::get('/instructor-section/{instructor}/edit', [InstructorSectionController::class, 'editAssignments'])->name('instructor-section.edit');
        Route::post('/instructor-section/{instructor}/sync', [InstructorSectionController::class, 'syncAssignments'])->name('instructor-section.sync');
        // --- Instructor Load Assignment from Excel (العمليات الجديدة) ---
        // روت لتوليد الشعب الناقصة (يُستدعى من زر)
        Route::post('/instructor-load-assignment/generate-missing-sections', [InstructorLoadAssignmentController::class, 'generateMissingSections'])->name('instructor-load.generateMissingSections');
        // روت لمعالجة رفع ملف إكسل لتوزيع الأحمال
        Route::post('/instructor-load-assignment/import-excel', [InstructorLoadAssignmentController::class, 'importInstructorLoadsExcel'])->name('instructor-load.importExcel');

        //////////////////////////////////////////////////////////////////////////////////////
        // --- Instructor Subject Assignments ---
        // Route::get('/instructor-subjects', [InstructorSubjectsController::class, 'index'])->name('instructor-subjects.index'); // صفحة العرض الرئيسية
        // Route::post('/instructor-subjects', [InstructorSubjectsController::class, 'syncSubjects'])->name('instructor-subjects.sync'); // لمعالجة حفظ الارتباطات
        Route::get('/instructor-subject-mappings', [InstructorSubjectsController::class, 'index'])->name('instructor-subjects.index');
        Route::get('/instructor-subject-mappings/{instructor}/edit', [InstructorSubjectsController::class, 'edit'])->name('instructor-subjects.edit');
        Route::post('/instructor-subject-mappings/{instructor}/sync', [InstructorSubjectsController::class, 'sync'])->name('instructor-subjects.sync');


        // Timeslots Management Page
        // Route::get('/timeslots', [DataEntryController::class, 'timeslots'])->name('timeslots');
        Route::get('/timeslots', [TimeslotController::class, 'index'])->name('timeslots.index');
        Route::post('/timeslots', [TimeslotController::class, 'store'])->name('timeslots.store');
        Route::post('/timeslots/generate-standard', [TimeslotController::class, 'generateStandard'])->name('timeslots.generateStandard');
        Route::put('/timeslots/{timeslot}', [TimeslotController::class, 'update'])->name('timeslots.update'); // لاحظ {timeslot}
        Route::delete('/timeslots/{timeslot}', [TimeslotController::class, 'destroy'])->name('timeslots.destroy'); // لاحظ {timeslot}
        // Route::resource('timeslots', TimeslotController::class)->except(['create', 'show', 'edit']);

        // Basic Settings Page (Types, Categories)
        Route::get('/settings', [DataEntryController::class, 'settings'])->name('settings');
        // Add POST/PUT/DELETE routes for settings later

        // TimetableGenerationController
        // Route::post('/timetable/generate', [TimetableGenerationController::class, 'start'])->name('timetable.generate.start');
        // --- Algorithm Base Data Management ---
        // Route::resource('crossover-types', CrossoverTypeController::class)->except(['show']);
        // Route::resource('selection-types', SelectionTypeController::class)->except(['show']);
        // --- End Data Management Routes ---
    });

    Route::prefix('algorithm/control')->name('algorithm-control.')->group(function () {

        // --- Algorithm Base Data Management ---
        Route::resource('crossover-types', CrossoverTypeController::class)->except(['create', 'show', 'edit']);
        Route::resource('selection-types', SelectionTypeController::class)->except(['create', 'show', 'edit']);

        // TimetableGenerationController
        Route::post('/timetable/generate', [TimetableGenerationController::class, 'start'])->name('timetable.generate.start');
        // 1. صفحة عرض قائمة عمليات التشغيل وأفضل الحلول
        Route::get('/timetable-results', [TimetableResultController::class, 'index'])->name('timetable.results.index');

        // 2. صفحة عرض تفصيلي لجدول (كروموسوم) معين
        // لاحظ أننا نستخدم {chromosome} الآن لـ Route Model Binding
        Route::get('/timetable-result/{chromosome}', [TimetableResultController::class, 'show'])->name('timetable.result.show');
        // --- End Timetable Results Routes ---

});
    // Other dashboard routes (Constraints, Algorithm, Reports...) can go here

});
