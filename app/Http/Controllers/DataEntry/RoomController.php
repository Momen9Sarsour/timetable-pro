<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class RoomController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the rooms (Web View) with Pagination.
     */
    public function index()
    {
        try {
            // جلب القاعات مرتبة بالأحدث مع نوعها وتقسيم الصفحات
            $rooms = Room::with('roomType')->latest()->paginate(15);

            // جلب أنواع القاعات للـ dropdown في نموذج الإضافة/التعديل
            $roomTypes = RoomType::orderBy('room_type_name')->get();

            return view('dashboard.data-entry.rooms', compact('rooms', 'roomTypes'));
        } catch (Exception $e) {
            Log::error('Error fetching rooms for web view: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load rooms.');
        }
    }

    /**
     * Store a newly created room from web request.
     */
    public function store(Request $request)
    {
        // 1. Validation
        $data = $request->validate([
            'room_no' => 'required|string|max:20|unique:rooms,room_no',
            'room_name' => 'nullable|string|max:255',
            'room_type_id' => 'required|integer|exists:rooms_types,id',
            'room_size' => 'required|integer|min:1',
            'room_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
            'room_branch' => 'nullable|string|max:100',
            // 'equipment' => 'nullable|array', // يجب أن يكون مصفوفة إذا تم إرساله
            // 'equipment.*' => 'nullable|string|max:50', // التحقق من كل عنصر
        ]);

        // 2. Prepare Data (تحويل equipment إلى JSON)
        // نستخدم validated() لجلب البيانات التي تم التحقق منها فقط
        // $data = $request->validated();
        // $data['equipment'] = isset($data['equipment']) ? json_encode($data['equipment']) : null;


        // 3. Add to Database
        try {
            Room::create($data);
            // 4. Redirect
            return redirect()->route('data-entry.rooms.index') // تأكد من اسم الروت
                ->with('success', 'Classroom created successfully.');
        } catch (Exception $e) {
            Log::error('Room Creation Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create classroom.')
                ->withInput();
        }
    }

    /**
     * Update the specified room from web request.
     */
    public function update(Request $request, Room $room)
    {
        // 1. Validation
        $data = $request->validate([
            'room_no' => 'required|string|max:20|unique:rooms,room_no,' . $room->id,
            'room_name' => 'nullable|string|max:255',
            'room_type_id' => 'required|integer|exists:rooms_types,id',
            'room_size' => 'required|integer|min:1',
            'room_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
            'room_branch' => 'nullable|string|max:100',
            // 'equipment' => 'nullable|array', // السماح بإرسال مصفوفة فارغة أو null
            // 'equipment.*' => 'nullable|string|max:50',
        ]);

        // 2. Prepare Data for Update (تحويل equipment)
        // $data = $request->validated();
        // التحويل إلى JSON فقط إذا كان equipment موجوداً في الطلب
        // إذا لم يتم إرسال equipment، لن يتم تحديثه في $data
        // if ($request->has('equipment')) {
        //      $data['equipment'] = isset($data['equipment']) ? json_encode($data['equipment']) : null; // null إذا كانت المصفوفة فارغة أو لم يتم إرسالها
        // }


        // 3. Update Database
        try {
            $room->update($data);
            // 4. Redirect
            return redirect()->route('data-entry.rooms.index') // تأكد من اسم الروت
                ->with('success', 'Classroom updated successfully.');
        } catch (Exception $e) {
            Log::error('Room Update Failed (Web): ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update classroom.')
                ->withInput();
        }
    }

    /**
     * Remove the specified room from web request.
     */
    public function destroy(Room $room)
    {
        // (اختياري) التحقق من السجلات المرتبطة
        // if ($room->scheduleEntries()->exists()) { ... }

        // 1. Delete from Database
        try {
            $room->delete();
            // 2. Redirect
            return redirect()->route('data-entry.rooms.index') // تأكد من اسم الروت
                ->with('success', 'Classroom deleted successfully.');
        } catch (Exception $e) {
            Log::error('Room Deletion Failed (Web): ' . $e->getMessage());
            return redirect()->route('data-entry.rooms.index') // تأكد من اسم الروت
                ->with('error', 'Failed to delete classroom.');
        }
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the rooms (API).
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Room::with('roomType:id,room_type_name');

            $rooms = $query->latest()->get(); // الترتيب بالأحدث

            // Pagination للـ API (معطل حالياً)
            /*
            $perPage = $request->query('per_page', 15);
            $roomsPaginated = $query->latest('id')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $roomsPaginated->items(),
                'pagination' => [
                    'total' => $roomsPaginated->total(),
                    'per_page' => $roomsPaginated->perPage(),
                    'current_page' => $roomsPaginated->currentPage(),
                    'last_page' => $roomsPaginated->lastPage(),
                ]
            ], 200);
            */
            // --- نهاية كود الـ Pagination المعطل ---


            return response()->json(['success' => true, 'data' => $rooms], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching rooms: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created room from API request.
     */
    public function apiStore(Request $request)
    {
        // 1. Validation
        $validatedData = $request->validate([
            'room_no' => 'required|string|max:20|unique:rooms,room_no',
            'room_name' => 'nullable|string|max:255',
            'room_type_id' => 'required|integer|exists:rooms_types,id',
            'room_size' => 'required|integer|min:1',
            'room_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed'])],
            'room_branch' => 'nullable|string|max:100',
            // 'equipment' => 'nullable|array',
            // 'equipment.*' => 'nullable|string|max:50',
        ]);

        // 2. Prepare Data (تحويل equipment)
        $data = $validatedData;
        // $data['equipment'] = isset($data['equipment']) ? json_encode($data['equipment']) : null;

        // 3. Add to Database
        try {
            $room = Room::create($data);
            $room->load('roomType:id,room_type_name'); // تحميل العلاقة للـ response
            return response()->json([
                'success' => true,
                'data' => $room,
                'message' => 'Classroom created successfully.'
            ], 201);
        } catch (Exception $e) {
            Log::error('API Room Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create classroom.'], 500);
        }
    }

    /**
     * Display the specified room (API).
     */
    public function apiShow(Room $room)
    {
        $room->load('roomType');
        // $room->load('roomType:id,room_type_name');
        return response()->json(['success' => true, 'data' => $room], 200);
    }

    /**
     * Update the specified room from API request.
     */
    public function apiUpdate(Request $request, Room $room)
    {
        // 1. Validation
        $data = $request->validate([
            'room_no' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'unique:rooms,room_no,' . $room->id,
            ],
            'room_name' => 'sometimes|nullable|string|max:255',
            'room_type_id' => 'sometimes|required|integer|exists:rooms_types,id',
            'room_size' => 'sometimes|required|integer|min:1',
            'room_gender' => ['sometimes', 'required', Rule::in(['Male', 'Female', 'Mixed'])],
            'room_branch' => 'sometimes|nullable|string|max:100',
            // 'equipment' => 'sometimes|nullable|array', // السماح بإرسال null أو مصفوفة فارغة
            // 'equipment.*' => 'nullable|string|max:50',
        ]);

        // 2. Prepare Data for Update
        // $data = $validatedData;
        // التحديث الشرطي للـ equipment
        // if ($request->has('equipment')) {
        //      $data['equipment'] = isset($validatedData['equipment']) ? json_encode($validatedData['equipment']) : null;
        // }

        // 3. Update Database
        try {
            $room->update($data);
            $room->load('roomType:id,room_type_name'); // تحميل العلاقة بعد التحديث

            return response()->json([
                'success' => true,
                'data' => $room,
                'message' => 'Classroom updated successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Room Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update classroom.'], 500);
        }
    }

    /**
     * Remove the specified room from API request.
     */
    public function apiDestroy(Room $room)
    {
        // (اختياري) التحقق من السجلات المرتبطة
        // if ($room->scheduleEntries()->exists()) { ... }

        // 1. Delete from Database
        try {
            $room->delete();
            // 2. Return Success JSON Response
            return response()->json([
                'success' => true,
                'message' => 'Classroom deleted successfully.'
            ], 200);
        } catch (Exception $e) {
            Log::error('API Room Deletion Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete classroom.'], 500);
        }
    }
}
