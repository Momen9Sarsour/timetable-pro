<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataEntry\RoleController;
use App\Http\Controllers\DataEntry\RoomController;
use App\Http\Controllers\DataEntry\UserController;
use App\Http\Controllers\DataEntry\SubjectController;
use App\Http\Controllers\DataEntry\DepartmentController;
use App\Http\Controllers\DataEntry\InstructorController;
use App\Http\Controllers\DataEntry\PlanController;
use App\Http\Controllers\DataEntry\RoomTypeController;
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
    Route::get('/departments/{department}', [DepartmentController::class, 'apiShow']); // <- تغيير {id} إلى {department}
    Route::put('/departments/{department}', [DepartmentController::class, 'apiUpdate']); // <- تغيير {id} إلى {department}
    Route::delete('/departments/{department}', [DepartmentController::class, 'apiDestroy']); // <- تغيير {id} إلى {department}

    // --- Roles API ---
    Route::get('/roles', [RoleController::class, 'apiIndex']);
    Route::post('/roles', [RoleController::class, 'apiStore']);
    Route::get('/roles/{role}', [RoleController::class, 'apiShow']);
    Route::put('/roles/{role}', [RoleController::class, 'apiUpdate']);
    Route::delete('/roles/{role}', [RoleController::class, 'apiDestroy']);

    // --- Rooms API ---
    Route::get('/rooms', [RoomController::class, 'apiIndex']);
    Route::post('/rooms', [RoomController::class, 'apiStore']);
    Route::get('/rooms/{room}', [RoomController::class, 'apiShow']);
    Route::put('/rooms/{room}', [RoomController::class, 'apiUpdate']);
    Route::delete('/rooms/{room}', [RoomController::class, 'apiDestroy']);

    // --- Room Type API ---
    Route::get('/room-types', [RoomTypeController::class, 'apiIndex']);
    Route::post('/room-types', [RoomTypeController::class, 'apiStore']);
    Route::get('/room-types/{roomType}', [RoomTypeController::class, 'apiShow']);
    Route::put('/room-types/{roomType}', [RoomTypeController::class, 'apiUpdate']);
    Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'apiDestroy']);

    // --- Instructors API ---
    Route::get('/instructors', [InstructorController::class, 'apiIndex']);
    Route::post('/instructors', [InstructorController::class, 'apiStore']);
    Route::get('/instructors/{instructor}', [InstructorController::class, 'apiShow']);
    Route::put('/instructors/{instructor}', [InstructorController::class, 'apiUpdate']);
    Route::delete('/instructors/{instructor}', [InstructorController::class, 'apiDestroy']);

    // --- Subjects API ---
    Route::get('/subjects', [SubjectController::class, 'apiIndex']);
    Route::post('/subjects', [SubjectController::class, 'apiStore']);
    Route::get('/subjects/{subject}', [SubjectController::class, 'apiShow']);
    Route::put('/subjects/{subject}', [SubjectController::class, 'apiUpdate']);
    Route::delete('/subjects/{subject}', [SubjectController::class, 'apiDestroy']);
    // Route::post('/subjects/bulk-upload', [SubjectController::class, 'apiBulkUpload']); // يمكن إضافة API للرفع بالجملة

    // --- Subject-Types API ---
    Route::get('/subject-types', [SubjectTypeController::class, 'apiIndex']);
    Route::post('/subject-types', [SubjectTypeController::class, 'apiStore']);
    Route::get('/subject-types/{subjectType}', [SubjectTypeController::class, 'apiShow']); // Route Model Binding
    Route::put('/subject-types/{subjectType}', [SubjectTypeController::class, 'apiUpdate']);
    Route::delete('/subject-types/{subjectType}', [SubjectTypeController::class, 'apiDestroy']);

    // --- Subject Category API ---
    Route::get('/subject-categories', [SubjectCategoryController::class, 'apiIndex']);
    Route::post('/subject-categories', [SubjectCategoryController::class, 'apiStore']);
    Route::get('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'apiShow']); // Route Model Binding
    Route::put('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'apiUpdate']);
    Route::delete('/subject-categories/{subjectCategory}', [SubjectCategoryController::class, 'apiDestroy']);

    // --- Users API ---
    Route::get('/users', [UserController::class, 'apiIndex']);
    Route::post('/users', [UserController::class, 'apiStore']);
    Route::get('/users/{user}', [UserController::class, 'apiShow']);
    Route::put('/users/{user}', [UserController::class, 'apiUpdate']);
    Route::delete('/users/{user}', [UserController::class, 'apiDestroy']);

    // --- Plans API ---
    Route::get('/plans', [PlanController::class, 'apiIndex']);
    Route::post('/plans', [PlanController::class, 'apiStore']);
    Route::get('/plans/{plan}', [PlanController::class, 'apiShow']); // Route model binding
    Route::put('/plans/{plan}', [PlanController::class, 'apiUpdate']);
    Route::delete('/plans/{plan}', [PlanController::class, 'apiDestroy']);

    // --- Plan Subjects API ---
    Route::post('/plans/{plan}/subjects', [PlanController::class, 'apiAddSubject']); // لإضافة مادة
    Route::delete('/plans/{plan}/subjects/{planSubject}', [PlanController::class, 'apiRemoveSubject']); // لحذف مادة (لاحظ استخدام planSubject ID هنا)


    // --- Timeslots API ---
    Route::get('/timeslots', [TimeslotController::class, 'apiIndex']);
    Route::post('/timeslots', [TimeslotController::class, 'apiStore']);
    Route::get('/timeslots/{timeslot}', [TimeslotController::class, 'apiShow']); // Route Model Binding
    Route::put('/timeslots/{timeslot}', [TimeslotController::class, 'apiUpdate']);
    Route::delete('/timeslots/{timeslot}', [TimeslotController::class, 'apiDestroy']);

    // --- APIs for Settings, Timeslots (لا تضفها الآن) ---
    // --- Timetable Generation API ---
    // Route::post('/generate-timetable', [TimetableController::class, 'generate']); // مثال لروت تشغيل الخوارزمية

    // --- Get Generated Timetables API ---
    // Route::get('/timetables/instructor/{instructorId}', [TimetableViewController::class, 'getInstructorTimetable']);
    // Route::get('/timetables/student/{studentId}', [TimetableViewController::class, 'getStudentTimetable']); // أو حسب الشعبة
    // Route::get('/timetables/room/{roomId}', [TimetableViewController::class, 'getRoomTimetable']);
    // ...

});
