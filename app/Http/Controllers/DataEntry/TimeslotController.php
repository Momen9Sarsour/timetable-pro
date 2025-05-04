<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Timeslot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator; // لاستخدام Validator يدوياً
use Exception;

class TimeslotController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the timeslots.
     */
    public function index()
    {
        try {
            // جلب الفترات مرتبة باليوم ثم الوقت + حساب عدد المحاضرات المرتبطة بكفاءة
            $timeslots = Timeslot::withCount('scheduleEntries') // يحسب العلاقة ويضيف عمود schedule_entries_count
                ->orderBy('day') // يمكنك تخصيص الترتيب هنا إذا أردت ترتيباً محدداً للأيام
                ->orderBy('start_time')
                ->paginate(20); // زيادة العدد في الصفحة للفترات الزمنية

            return view('dashboard.data-entry.timeslots', compact('timeslots'));
        } catch (Exception $e) {
            Log::error('Error fetching timeslots: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load timeslots.');
        }
    }

    /**
     * Store a newly created timeslot in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation (مع التحقق من الترتيب والتفرد)
        $validator = Validator::make($request->all(), [
            'day' => ['required', Rule::in(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])],
            'start_time' => 'required|date_format:H:i', // استخدام H:i لتنسيق 24 ساعة
            'end_time' => 'required|date_format:H:i|after:start_time', // end_time يجب أن يكون بعد start_time
        ]);

        // 2. التحقق من التفرد يدوياً (لأن unique rule في validate لا تكفي لـ 3 أعمدة معاً بسهولة)
        $validator->after(function ($validator) use ($request) {
            $exists = Timeslot::where('day', $request->input('day'))
                ->where('start_time', $request->input('start_time'))
                ->where('end_time', $request->input('end_time'))
                ->exists();
            if ($exists) {
                // إضافة خطأ مخصص إذا كانت الفترة موجودة بالفعل
                $validator->errors()->add('time_unique', 'This exact timeslot (day, start, end) already exists.');
            }
            // التحقق من عدم تداخل الأوقات (منطق معقد، يمكن إضافته لاحقاً إذا لزم الأمر)
        });

        // إذا فشل التحقق، ارجع مع الأخطاء
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'store') // استخدام error bag مميز
                ->withInput();
        }

        // 3. Prepare Data
        $data = $validator->validated(); // الحصول على البيانات المتحقق منها

        // 4. Add to Database
        try {
            Timeslot::create($data);
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
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'day' => ['required', Rule::in(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // 2. التحقق من التفرد يدوياً (مع تجاهل الصف الحالي)
        $validator->after(function ($validator) use ($request, $timeslot) {
            $exists = Timeslot::where('day', $request->input('day'))
                ->where('start_time', $request->input('start_time'))
                ->where('end_time', $request->input('end_time'))
                ->where('id', '!=', $timeslot->id) // استثناء الـ ID الحالي
                ->exists();
            if ($exists) {
                $validator->errors()->add('time_unique', 'This exact timeslot (day, start, end) already exists.');
            }
        });

        if ($validator->fails()) {
            // استخدام error bag مميز للتحديث
            return redirect()->back()
                ->withErrors($validator, 'update_' . $timeslot->id)
                ->withInput();
        }

        // 3. Prepare Data
        $data = $validator->validated();

        // 4. Update Database
        try {
            $timeslot->update($data);
            return redirect()->route('data-entry.timeslots.index')
                ->with('success', 'Timeslot updated successfully.');
        } catch (Exception $e) {
            Log::error('Timeslot Update Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update timeslot.')
                ->withInput();
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
        // 1. Validation (باستخدام Validator يدوي للتحقق المخصص)
        $validator = Validator::make($request->all(), [
            'day' => ['required', Rule::in(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // التحقق من التفرد يدوياً
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->hasAny(['day', 'start_time', 'end_time'])) { // تحقق فقط إذا كانت الحقول الأساسية صحيحة
                $exists = Timeslot::where('day', $request->input('day'))
                    ->where('start_time', $request->input('start_time'))
                    ->where('end_time', $request->input('end_time'))
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('time_unique', 'This exact timeslot already exists.');
                }
            }
        });

        // إذا فشل التحقق، أرجع الأخطاء كـ JSON 422
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Add to Database
        try {
            // استخدام البيانات التي تم التحقق منها فقط
            $timeslot = Timeslot::create($validator->validated());
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $timeslot,
                'message' => 'Timeslot created successfully.'
            ], 201); // 201 Created
        } catch (Exception $e) {
            Log::error('API Timeslot Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create timeslot.'], 500);
        }
    }

    /**
     * Display the specified timeslot (API).
     */
    public function apiShow(Timeslot $timeslot) // استخدام Route Model Binding
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
        // 1. Validation (استخدام sometimes والتحقق اليدوي)
        $validator = Validator::make($request->all(), [
            'day' => ['sometimes', 'required', Rule::in(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])],
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
        ]);

        // التحقق من التفرد يدوياً (مع تجاهل الصف الحالي)
        $validator->after(function ($validator) use ($request, $timeslot) {
            if (!$validator->errors()->hasAny(['day', 'start_time', 'end_time'])) {
                // جلب القيم للتأكد (إذا لم يتم إرسالها، استخدم القيمة الحالية)
                $day = $request->input('day', $timeslot->day);
                $startTime = $request->input('start_time', $timeslot->start_time);
                $endTime = $request->input('end_time', $timeslot->end_time);

                $exists = Timeslot::where('day', $day)
                    ->where('start_time', $startTime)
                    ->where('end_time', $endTime)
                    ->where('id', '!=', $timeslot->id) // استثناء الـ ID الحالي
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('time_unique', 'This exact timeslot already exists.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Update Database
        try {
            // استخدام البيانات التي تم التحقق منها فقط للتحديث
            $timeslot->update($validator->validated());
            // 3. Return Success JSON Response
            return response()->json([
                'success' => true,
                'data' => $timeslot, // إرجاع الفترة المحدثة
                'message' => 'Timeslot updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Timeslot Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update timeslot.'], 500);
        }
    }

    /**
     * Remove the specified timeslot from API request.
     * حذف فترة زمنية محددة قادمة من طلب API
     */
    public function apiDestroy(Timeslot $timeslot)
    {
        // التحقق من وجود جداول مرتبطة
        if ($timeslot->scheduleEntries()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete timeslot. It is used in schedules.'
            ], 409); // 409 Conflict
        }

        // 1. Delete from Database
        try {
            $timeslot->delete();
            // 2. Return Success JSON Response
            return response()->json([
                'success' => true,
                'message' => 'Timeslot deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Timeslot Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete timeslot.'], 500);
        }
    }
}
