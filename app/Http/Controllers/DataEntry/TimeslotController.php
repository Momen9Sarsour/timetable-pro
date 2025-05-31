<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Timeslot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator; // لاستخدام Validator يدوياً
use Exception;
use Illuminate\Support\Facades\DB;

class TimeslotController extends Controller
{
    // أيام الأسبوع المستخدمة في النظام
    const DAYS_OF_WEEK = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the timeslots.
     */
    public function index()
    {
        try {
            $timeslots = Timeslot::withCount('scheduleEntries')
                ->orderByRaw("FIELD(day, '" . implode("','", self::DAYS_OF_WEEK) . "')")
                ->orderBy('start_time')
                ->paginate(30); // عرض عدد أكبر للفترات

            return view('dashboard.data-entry.timeslots', compact('timeslots'));
        } catch (Exception $e) {
            Log::error('Error fetching timeslots: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load timeslots.');
        }
    }

    /**
     * Generate standard weekly timeslots based on user input.
     */
    public function generateStandard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'working_days' => 'required|array|min:1',
            'working_days.*' => ['required', Rule::in(self::DAYS_OF_WEEK)],
            'overall_start_time' => 'required|date_format:H:i',
            'overall_end_time' => 'required|date_format:H:i|after:overall_start_time',
            'lecture_duration' => 'required|integer|min:15', // أقل مدة محاضرة 15 دقيقة
            'break_duration' => 'required|integer|min:0',   // الاستراحة يمكن أن تكون 0
        ]);

        if ($validator->fails()) {
            return redirect()->route('data-entry.timeslots.index')
                ->withErrors($validator, 'generateStandardModal') // error bag مخصص
                ->withInput()
                ->with('open_generate_modal', true); // لإعادة فتح المودال
        }

        try {
            DB::transaction(function () use ($request) { // استخدام transaction لضمان سلامة البيانات
                // 1. حذف كل الفترات القديمة
                // Timeslot::truncate(); // يحذف كل السجلات ويُعيد الـ auto-increment (كن حذراً)
                Timeslot::query()->delete(); // إذا كنت لا تريد إعادة الـ auto-increment
                Log::info('Old timeslots deleted for standard generation.');

                // 2. إنشاء الفترات الجديدة
                $newTimeslots = [];
                $workingDays = $request->input('working_days');
                $startTime = Carbon::createFromTimeString($request->input('overall_start_time'));
                $endTime = Carbon::createFromTimeString($request->input('overall_end_time'));
                $lectureDurationMinutes = (int)$request->input('lecture_duration');
                $breakDurationMinutes = (int)$request->input('break_duration');

                foreach ($workingDays as $day) {
                    $currentTime = $startTime->copy();
                    while ($currentTime->copy()->addMinutes($lectureDurationMinutes)->lte($endTime)) {
                        $slotEndTime = $currentTime->copy()->addMinutes($lectureDurationMinutes);
                        $newTimeslots[] = [
                            'day' => $day,
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $slotEndTime->format('H:i:s'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $currentTime = $slotEndTime->copy()->addMinutes($breakDurationMinutes);
                    }
                }

                if (!empty($newTimeslots)) {
                    Timeslot::insert($newTimeslots); // إدخال جماعي
                    Log::info(count($newTimeslots) . ' new standard timeslots generated.');
                }
            });

            return redirect()->route('data-entry.timeslots.index')
                ->with('success', 'Standard weekly timeslots generated successfully.');
        } catch (Exception $e) {
            Log::error('Failed to generate standard timeslots: ' . $e->getMessage());
            return redirect()->route('data-entry.timeslots.index')
                ->with('error', 'Failed to generate timeslots: ' . $e->getMessage());
        }
    }


    /**
     * Store a newly created timeslot in storage.
     */
    public function store(Request $request)
    {
        $errorBagName = 'addTimeslotModal';
        $validator = Validator::make($request->all(), [
            'day' => ['required', Rule::in(self::DAYS_OF_WEEK)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // التحقق من التفرد والتداخل
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->hasAny()) {
                $this->validateTimeslotUniquenessAndOverlap(
                    $validator,
                    $request->input('day'),
                    $request->input('start_time'),
                    $request->input('end_time')
                );
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, $errorBagName)
                ->withInput()
                ->with('open_modal_on_error', $errorBagName)
                ->with('error', 'This timeslot overlaps with an existing timeslot on the same day.');
        }

        try {
            Timeslot::create($validator->validated());
            return redirect()->route('data-entry.timeslots.index')
                ->with('success', 'Timeslot created successfully.');
        } catch (Exception $e) {
            Log::error('Timeslot Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create timeslot.')
                ->withInput();
        }
    }

    /**
     * Update the specified timeslot in storage.
     */
    public function update(Request $request, Timeslot $timeslot)
    {
        $errorBagName = 'editTimeslotModal_' . $timeslot->id;
        $validator = Validator::make($request->all(), [
            'day' => ['required', Rule::in(self::DAYS_OF_WEEK)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // التحقق من التفرد والتداخل (مع تجاهل الفترة الحالية)
        $validator->after(function ($validator) use ($request, $timeslot) {
            if (!$validator->errors()->hasAny()) {
                $this->validateTimeslotUniquenessAndOverlap(
                    $validator,
                    $request->input('day'),
                    $request->input('start_time'),
                    $request->input('end_time'),
                    $timeslot->id // ID الفترة الحالية لاستثنائها
                );
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, $errorBagName)->withInput()
                ->with('open_modal_on_error', $errorBagName)
                ->with('error_modal_id', $timeslot->id)
                ->with('error', 'This timeslot overlaps with an existing timeslot on the same day.');
        }
        try {
            $timeslot->update($validator->validated());
            return redirect()->route('data-entry.timeslots.index')->with('success', 'Timeslot updated successfully.');
        } catch (Exception $e) {
            Log::error('Timeslot Update Failed: ' . $e->getMessage());
            return redirect()->back()
                // إرجاع الأخطاء للـ error bag الصحيح
                ->withErrors(['update_error' => 'Failed to update timeslot.'], 'update_' . $timeslot->id)
                ->withInput();
        }
    }

    /**
     * Helper function to validate timeslot uniqueness and overlap.
     * دالة مساعدة للتحقق من تفرد الفترة وعدم تداخلها
     */
    private function validateTimeslotUniquenessAndOverlap($validator, $day, $startTime, $endTime, $excludeId = null)
    {
        $startTimeCarbon = Carbon::parse($startTime);
        $endTimeCarbon = Carbon::parse($endTime);

        // 1. التحقق من التفرد (نفس الفترة بالضبط)
        $query = Timeslot::where('day', $day)
            ->where('start_time', $startTimeCarbon->format('H:i:s')) // قارن بالتنسيق الكامل
            ->where('end_time', $endTimeCarbon->format('H:i:s'));
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->exists()) {
            $validator->errors()->add('time_unique', 'This exact timeslot (day, start, end) already exists.');
            return; // لا داعي للتحقق من التداخل إذا كانت متطابقة
        }

        // 2. التحقق من التداخل
        // فترة جديدة: [S1, E1]
        // فترة موجودة: [S2, E2]
        // يحدث تداخل إذا: (S1 < E2) AND (E1 > S2)
        $overlapping = Timeslot::where('day', $day)
            ->where(function ($q) use ($startTimeCarbon, $endTimeCarbon) {
                $q->where(function ($subQ) use ($startTimeCarbon, $endTimeCarbon) { // Period 1 overlaps Period 2
                    $subQ->where('start_time', '<', $endTimeCarbon->format('H:i:s'))
                        ->where('end_time', '>', $startTimeCarbon->format('H:i:s'));
                });
                // يمكنك إضافة شروط أخرى إذا أردت أن تكون الفترات متلاصقة مسموحة أو ممنوعة
                // ->orWhere('start_time', '=', $endTimeCarbon->format('H:i:s')) // متلاصقة في النهاية
                // ->orWhere('end_time', '=', $startTimeCarbon->format('H:i:s')); // متلاصقة في البداية
            });

        if ($excludeId) {
            $overlapping->where('id', '!=', $excludeId);
        }

        if ($overlapping->exists()) {
            $validator->errors()->add('time_overlap', 'This timeslot overlaps with an existing timeslot on the same day.');
        }
    }

    /**
     * Remove the specified timeslot from storage.
     */
    public function destroy(Timeslot $timeslot)
    {
        // التحقق من وجود جداول مرتبطة
        if ($timeslot->scheduleEntries()->exists()) {
            return redirect()->route('data-entry.timeslots.index')
                ->with('error', 'Cannot delete timeslot. It is used in generated schedules.');
        }

        // 1. Delete
        try {
            $timeslot->delete();
            return redirect()->route('data-entry.timeslots.index')
                ->with('success', 'Timeslot deleted successfully.');
        } catch (Exception $e) {
            Log::error('Timeslot Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.timeslots.index')
                ->with('error', 'Failed to delete timeslot.');
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================
    /**
     * Display a listing of the timeslots (API).
     */
    public function apiIndex(Request $request) // إضافة Request للفلترة المحتملة
    {
        try {
            $query = Timeslot::query(); // ابدأ بإنشاء query builder

            // (اختياري) فلترة بسيطة
            if ($request->has('day')) {
                $query->where('day', $request->day);
            }
            // يمكنك إضافة فلترة حسب الوقت هنا إذا احتجت

            // جلب الفترات مرتبة باليوم ثم الوقت
            // وتحديد الحقول المطلوبة
            $timeslots = $query->orderByRaw("FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')") // ترتيب مخصص للأيام
                ->orderBy('start_time')
                ->get(['id', 'day', 'start_time', 'end_time']);

            // --- كود الـ Pagination (معطل) ---
            /*
            $perPage = $request->query('per_page', 50); // عرض عدد أكبر في الصفحة للـ timeslots
            $timeslotsPaginated = $query->orderByRaw(...) // نفس الترتيب
                                         ->orderBy('start_time')
                                         ->paginate($perPage, ['id', 'day', 'start_time', 'end_time']);

            return response()->json([
                'success' => true,
                'data' => $timeslotsPaginated->items(),
                'pagination' => [ 'total' => $timeslotsPaginated->total(), ... ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $timeslots], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching timeslots: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created timeslot from API request.
     */

    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'day' => ['required', Rule::in(self::DAYS_OF_WEEK)],
            'start_time' => 'required|date_format:H:i', // H:i for 24-hour format
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->hasAny()) {
                $this->validateTimeslotUniquenessAndOverlap(
                    $validator,
                    $request->input('day'),
                    $request->input('start_time'),
                    $request->input('end_time')
                );
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $timeslot = Timeslot::create($validator->validated());
            return response()->json(['success' => true, 'data' => $timeslot, 'message' => 'Timeslot created successfully.'], 201);
        } catch (Exception $e) {
            Log::error('API Timeslot Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create timeslot.'], 500);
        }
    }

    /**
     * Generate standard weekly timeslots from API request.
     */
    public function apiGenerateStandard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'working_days' => 'required|array|min:1',
            'working_days.*' => ['required', Rule::in(self::DAYS_OF_WEEK)],
            'overall_start_time' => 'required|date_format:H:i',
            'overall_end_time' => 'required|date_format:H:i|after:overall_start_time',
            'lecture_duration' => 'required|integer|min:15',
            'break_duration' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                Timeslot::query()->delete();
                // Timeslot::truncate(); // أو
                Log::info('Old timeslots deleted for standard API generation.');

                $newTimeslots = [];
                $workingDays = $request->input('working_days');
                $startTime = Carbon::createFromTimeString($request->input('overall_start_time'));
                $endTime = Carbon::createFromTimeString($request->input('overall_end_time'));
                $lectureDurationMinutes = (int)$request->input('lecture_duration');
                $breakDurationMinutes = (int)$request->input('break_duration');

                foreach ($workingDays as $day) {
                    $currentTime = $startTime->copy();
                    while ($currentTime->copy()->addMinutes($lectureDurationMinutes)->lte($endTime)) {
                        $slotEndTime = $currentTime->copy()->addMinutes($lectureDurationMinutes);
                        $newTimeslots[] = [
                            'day' => $day,
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $slotEndTime->format('H:i:s'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $currentTime = $slotEndTime->copy()->addMinutes($breakDurationMinutes);
                    }
                }
                if (!empty($newTimeslots)) {
                    Timeslot::insert($newTimeslots);
                }
            });

            $generatedCount = Timeslot::count(); // عدد الفترات التي تم إنشاؤها
            return response()->json(['success' => true, 'message' => "{$generatedCount} standard timeslots generated successfully."], 200);
        } catch (Exception $e) {
            Log::error('API Failed to generate standard timeslots: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate timeslots: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified timeslot (API).
     */
    public function apiShow(Timeslot $timeslot)
    {
        // يمكنك إضافة ->loadCount('scheduleEntries') هنا إذا أردت عرض عدد المحاضرات
        // $timeslot->loadCount('scheduleEntries');
        return response()->json(['success' => true, 'data' => $timeslot], 200);
    }

    /**
     * Update the specified timeslot from API request.
     */
    public function apiUpdate(Request $request, Timeslot $timeslot)
    {
        $validator = Validator::make($request->all(), [
            'day' => ['sometimes', 'required', Rule::in(self::DAYS_OF_WEEK)],
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
        ]);

        $validator->after(function ($validator) use ($request, $timeslot) {
            if (!$validator->errors()->hasAny()) {
                // جلب القيم للتأكد (إذا لم يتم إرسالها، استخدم القيمة الحالية)
                $day = $request->input('day', $timeslot->day);
                $startTime = $request->input('start_time', $timeslot->start_time);
                $endTime = $request->input('end_time', $timeslot->end_time);

                $this->validateTimeslotUniquenessAndOverlap(
                    $validator,
                    $day,
                    $startTime,
                    $endTime,
                    $timeslot->id
                );
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        try {
            $timeslot->update($validator->validated()); // استخدام البيانات التي تم التحقق منها فقط
            return response()->json(['success' => true, 'data' => $timeslot, 'message' => 'Timeslot updated successfully.'], 200);
        } catch (Exception $e) {
            Log::error('API Timeslot Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update timeslot.'], 500);
        }
    }

    /**
     * Remove the specified timeslot (API).
     */
    public function apiDestroy(Timeslot $timeslot)
    {
        // ... (نفس كود الحذف من الويب، ولكن يرجع JSON) ...
        if ($timeslot->scheduleEntries()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: used in schedules.'], 409);
        }
        try {
            $timeslot->delete();
            return response()->json(['success' => true, 'message' => 'Timeslot deleted.'], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }
}
