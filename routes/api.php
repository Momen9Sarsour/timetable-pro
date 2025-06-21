<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataEntry\RoleController;
use App\Http\Controllers\DataEntry\RoomController;
use App\Http\Controllers\DataEntry\UserController;
use App\Http\Controllers\DataEntry\SubjectController;
use App\Http\Controllers\DataEntry\DepartmentController;
use App\Http\Controllers\DataEntry\InstructorController;
use App\Http\Controllers\DataEntry\InstructorSubjectController;
use App\Http\Controllers\DataEntry\PlanController;
use App\Http\Controllers\DataEntry\PlanExpectedCountController;
use App\Http\Controllers\DataEntry\PlanSubjectImportController;
use App\Http\Controllers\DataEntry\RoomTypeController;
use App\Http\Controllers\DataEntry\SectionController;
use App\Http\Controllers\DataEntry\SectionController22;
use App\Http\Controllers\DataEntry\SubjectCategoryController;
use App\Http\Controllers\DataEntry\SubjectTypeController;
use App\Http\Controllers\DataEntry\TimeslotController;
use App\Models\Room;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::get('/api', function () {
    return 'sssss';
});

// Route للحصول على بيانات المستخدم الحالي (مثال يأتي مع Sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('role'); // تحميل الدور مع بيانات المستخدم
});

// --- API Routes for Data Management (Protected by Sanctum) ---
// // سنحمي هذه الروابط لاحقاً بـ middleware auth:sanctum
// Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () { // استخدام prefix v1 للـ versioning
Route::prefix('v1')->group(function () {

    // --- Departments API ---
    Route::get('/departments', [DepartmentController::class, 'apiIndex']);
    Route::post('/departments', [DepartmentController::class, 'apiStore']);
    Route::get('/departments/{department}', [DepartmentController::class, 'apiShow']);
    Route::put('/departments/{department}', [DepartmentController::class, 'apiUpdate']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'apiDestroy']);
    Route::post('/departments/bulk-upload', [DepartmentController::class, 'apiBulkUpload']);

    // --- Roles API ---
    Route::get('/roles', [RoleController::class, 'apiIndex']);
    Route::post('/roles', [RoleController::class, 'apiStore']);
    Route::get('/roles/{role}', [RoleController::class, 'apiShow']);
    Route::put('/roles/{role}', [RoleController::class, 'apiUpdate']);
    Route::delete('/roles/{role}', [RoleController::class, 'apiDestroy']);

    // --- Room Type API ---
    Route::get('/room-types', [RoomTypeController::class, 'apiIndex']);
    Route::post('/room-types', [RoomTypeController::class, 'apiStore']);
    Route::get('/room-types/{roomType}', [RoomTypeController::class, 'apiShow']);
    Route::put('/room-types/{roomType}', [RoomTypeController::class, 'apiUpdate']);
    Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'apiDestroy']);
    Route::post('/room-types/bulk-upload', [RoomTypeController::class, 'apiBulkUpload']);

    // --- Rooms API ---
    Route::get('/rooms', [RoomController::class, 'apiIndex']);
    Route::post('/rooms', [RoomController::class, 'apiStore']);
    Route::get('/rooms/{room}', [RoomController::class, 'apiShow']);
    Route::put('/rooms/{room}', [RoomController::class, 'apiUpdate']);
    Route::delete('/rooms/{room}', [RoomController::class, 'apiDestroy']);
    Route::post('/rooms/bulk-upload', [RoomController::class, 'apiBulkUpload']);

    // --- Subject-Types API ---
    Route::get('/subject-types', [SubjectTypeController::class, 'apiIndex']);
    Route::post('/subject-types', [SubjectTypeController::class, 'apiStore']);
    Route::get('/subject-types/{subjectType}', [SubjectTypeController::class, 'apiShow']); // Route Model Binding
    Route::put('/subject-types/{subjectType}', [SubjectTypeController::class, 'apiUpdate']);
    Route::delete('/subject-types/{subjectType}', [SubjectTypeController::class, 'apiDestroy']);
    Route::post('/subject-types/bulk-upload', [SubjectTypeController::class, 'apiBulkUpload']);

    // --- Subject Category API ---
    Route::get('/subject-categories', [SubjectCategoryController::class, 'apiIndex']);
    Route::post('/subject-categories', [SubjectCategoryController::class, 'apiStore']);
    Route::get('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'apiShow']); // Route Model Binding
    Route::put('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'apiUpdate']);
    Route::delete('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'apiDestroy']);
    Route::post('/subject-categories/bulk-upload', [SubjectCategoryController::class, 'apiBulkUpload']);

    // --- Subjects API ---
    Route::get('/subjects', [SubjectController::class, 'apiIndex']);
    Route::post('/subjects', [SubjectController::class, 'apiStore']);
    Route::get('/subjects/{subject}', [SubjectController::class, 'apiShow']);
    Route::put('/subjects/{subject}', [SubjectController::class, 'apiUpdate']);
    Route::delete('/subjects/{subject}', [SubjectController::class, 'apiDestroy']);
    // Route::post('/subjects/bulk-upload', [SubjectController::class, 'apiBulkUpload']); // يمكن إضافة API للرفع بالجملة
    Route::post('/subjects/bulk-upload', [SubjectController::class, 'apiBulkUpload']);

    // --- Users API ---
    Route::get('/users', [UserController::class, 'apiIndex']);
    Route::post('/users', [UserController::class, 'apiStore']);
    Route::get('/users/{user}', [UserController::class, 'apiShow']);
    Route::put('/users/{user}', [UserController::class, 'apiUpdate']);
    Route::delete('/users/{user}', [UserController::class, 'apiDestroy']);

    // --- Instructors API ---
    Route::get('/instructors', [InstructorController::class, 'apiIndex']);
    Route::post('/instructors', [InstructorController::class, 'apiStore']);
    Route::get('/instructors/{instructor}', [InstructorController::class, 'apiShow']);
    Route::put('/instructors/{instructor}', [InstructorController::class, 'apiUpdate']);
    Route::delete('/instructors/{instructor}', [InstructorController::class, 'apiDestroy']);

    // --- Plans API ---
    Route::get('/plans', [PlanController::class, 'apiIndex']);
    Route::post('/plans', [PlanController::class, 'apiStore']);
    Route::get('/plans/{plan}', [PlanController::class, 'apiShow']); // Route model binding
    Route::put('/plans/{plan}', [PlanController::class, 'apiUpdate']);
    Route::delete('/plans/{plan}', [PlanController::class, 'apiDestroy']);
    Route::post('/plans/bulk-upload', [PlanController::class, 'apiBulkUpload']); // *** روت الرفع للـ API ***

    // --- Plan Subjects API ---
    Route::post('/plans/{plan}/subjects', [PlanController::class, 'apiAddSubject']); // لإضافة مادة
    Route::delete('/plans/{plan}/subjects/{planSubject}', [PlanController::class, 'apiRemoveSubject']); // لحذف مادة (لاحظ استخدام planSubject ID هنا)
    Route::post('/plans/{plan}/bulk-upload-subjects', [PlanController::class, 'apiBulkUploadPlanSubjects'])->name('api.plans.bulkUploadSubjects'); // *** الرابط الجديد ***
    Route::post('/plans/{plan}/import-subjects', [PlanSubjectImportController::class, 'handleApiImport'])->name('api.plans.importSubjects');

    // --- Plan Expected Counts API ---
    Route::get('/plan-expected-counts', [PlanExpectedCountController::class, 'apiIndex']);
    Route::post('/plan-expected-counts', [PlanExpectedCountController::class, 'apiStore']);
    Route::get('/plan-expected-counts/{planExpectedCount}', [PlanExpectedCountController::class, 'apiShow']); // RMB
    Route::put('/plan-expected-counts/{planExpectedCount}', [PlanExpectedCountController::class, 'apiUpdate']); // RMB
    Route::delete('/plan-expected-counts/{planExpectedCount}', [PlanExpectedCountController::class, 'apiDestroy']); // RMB

    // Endpoints للتحكم بالشعب من سياق PlanExpectedCount
    // Route::get('/expected-counts/{expectedCount}/manage-sections', [SectionController22::class, 'apiManageSectionsForContext'])->name('api.sections.manageContext');
    // Route::post('/expected-counts/{expectedCount}/generate-sections', [SectionController22::class, 'apiGenerateSectionsForContextButton'])->name('api.sections.generateForContext');
    // Route::post('/expected-counts/{expectedCount}/sections', [SectionController22::class, 'apiStoreSectionInContext'])->name('api.sections.storeInContext');

    // // Endpoints لعمليات CRUD على شعبة معينة (باستخدام ID الشعبة)
    // Route::get('/expected-counts/sections/{section}', [SectionController22::class, 'apiShowSectionDetails'])->name('api.sections.show');
    // Route::put('/expected-counts/sections/{section}', [SectionController22::class, 'apiUpdateSectionDetails'])->name('api.sections.update');
    // Route::delete('/expected-counts/sections/{section}', [SectionController22::class, 'apiDestroySectionDetails'])->name('api.sections.destroy');

    // عرض جميع الشعب لسياق محدد
    Route::get('expected-counts/{expectedCount}/sections', [SectionController22::class, 'APIGetSectionsForContext']);

    // عرض شعب لمادة محددة في سياق معين
    Route::get('expected-counts/{expectedCount}/subjects/{planSubject}/sections', [SectionController22::class, 'APIGetSectionsForSubject']);

    // إنشاء شعبة جديدة
    Route::post('expected-counts/{expectedCount}/sections', [SectionController22::class, 'APIStoreSectionInContext']);

    // تحديث شعبة موجودة
    Route::put('expected-counts/sections/{section}', [SectionController22::class, 'APIUpdateSectionInContext']);

    // حذف شعبة
    Route::delete('expected-counts/sections/{section}', [SectionController22::class, 'APIDestroySectionInContext']);

    // إنشاء شعب تلقائيًا
    Route::post('expected-counts/{expectedCount}/generate-sections', [SectionController22::class, 'APIGenerateSectionsForContext']);



    //  Route::get('/expected-counts/{expectedCount}/manage-sections', [SectionController22::class, 'apiManageSectionsForContext'])->name('api.expected-counts.sections.manage');

    // // توليد الشعب آلياً لسياق ExpectedCount معين
    // Route::post('/expected-counts/{expectedCount}/generate-sections', [SectionController22::class, 'apiGenerateSectionsForContextButton'])->name('api.expected-counts.sections.generate');

    // // إضافة شعبة يدوياً لسياق ExpectedCount معين
    // // (plan_subject_id, activity_type, section_number, student_count, section_gender في الـ body)
    // Route::post('/expected-counts/{expectedCount}/sections', [SectionController22::class, 'apiStoreSectionInContext'])->name('api.expected-counts.sections.store');

    // // --- Sections API (General CRUD on existing sections - if needed separately) ---
    // // الروابط التالية تتعامل مع الشعبة مباشرة عبر الـ ID الخاص بها
    // // Route::get('/sections', [SectionController22::class, 'apiIndexAllSections']); // لعرض كل الشعب مع فلاتر
    // Route::get('/expected-counts/sections/{section}', [SectionController22::class, 'apiShowSectionDetails'])->name('api.expected-counts.sections.show');
    // Route::put('/expected-counts/sections/{section}', [SectionController22::class, 'apiUpdateSectionDetails'])->name('api.expected-counts.sections.update');
    // Route::delete('/expected-counts/sections/{section}', [SectionController22::class, 'apiDestroySectionDetails'])->name('api.expected-counts.sections.destroy');


    // --- Sections API ---
    Route::get('/sections', [SectionController::class, 'apiIndex']);
    Route::get('/sections/context', [SectionController::class, 'apiGetSectionsForSubjectContext']); // جلب شعب لسياق محدد
    Route::post('/sections', [SectionController::class, 'apiStore']);
    Route::get('/sections/{section}', [SectionController::class, 'apiShow']);
    Route::put('/sections/{section}', [SectionController::class, 'apiUpdate']);
    Route::delete('/sections/{section}', [SectionController::class, 'apiDestroy']);
    Route::post('/sections/generate-for-subject', [SectionController::class, 'apiGenerateForSubject']); // لتوليد الشعب

    // --- Instructor Subject Assignments API ---
    // جلب قائمة المدرسين مع عدد المواد
    Route::get('/instructor-assignments', [InstructorSubjectController::class, 'apiIndex'])->name('api.instructor-assignments.index');
    // جلب كل المواد لمدرس معين (مع تحديد المعين منها)
    Route::get('/instructor-assignments/{instructor}', [InstructorSubjectController::class, 'apiShowAssignments'])->name('api.instructor-assignments.show'); // استخدام {instructor} لـ RMB
    Route::post('/instructors/{instructor}/subjects/sync', [InstructorSubjectController::class, 'apiSyncAssignments']); // تحديث (مزامنة) المواد المعينة (استخدمنا POST للتبسيط، يمكن استخدام PUT)
    Route::get('/instructors/{instructor}/assigned-sections', [InstructorSubjectController::class, 'apiGetAssignedSections']); // جلب الشعب المعينة فقط للمدرس
    Route::get('/instructors/sections/available', [InstructorSubjectController::class, 'apiGetAvailableSections']); // جلب كل الشعب المتاحة (مع فلاتر اختيارية)
    // Route::get('/instructors/{instructor}/subjects', [InstructorSubjectController::class, 'apiGetAssignedSubjects']); // جلب المواد المعينة
    // Route::get('/instructors/{instructor}/available-subjects', [InstructorSubjectController::class, 'apiGetAvailableSubjects']); // جلب المواد المتاحة

    // --- Timeslots API ---
    Route::get('/timeslots', [TimeslotController::class, 'apiIndex']);
    Route::post('/timeslots', [TimeslotController::class, 'apiStore']);
    Route::post('/timeslots/generate-standard', [TimeslotController::class, 'apiGenerateStandard']); // *** الرابط الجديد ***
    Route::get('/timeslots/{timeslot}', [TimeslotController::class, 'apiShow']); // Route Model Binding
    Route::put('/timeslots/{timeslot}', [TimeslotController::class, 'apiUpdate']);
    Route::delete('/timeslots/{timeslot}', [TimeslotController::class, 'apiDestroy']);


    // --- APIs for Settings (لا تضفها الآن) ---
    // --- Timetable Generation API ---
    // Route::post('/generate-timetable', [TimetableController::class, 'generate']); // مثال لروت تشغيل الخوارزمية

    // --- Get Generated Timetables API ---
    // Route::get('/timetables/instructor/{instructorId}', [TimetableViewController::class, 'getInstructorTimetable']);
    // Route::get('/timetables/student/{studentId}', [TimetableViewController::class, 'getStudentTimetable']); // أو حسب الشعبة
    // Route::get('/timetables/room/{roomId}', [TimetableViewController::class, 'getRoomTimetable']);
    // ...

});
