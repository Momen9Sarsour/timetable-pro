<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\RoomType; // تم استيراده
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class RoomTypeController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the room types.
     */
    public function index()
    {
        try {
            // تغيير اسم المتغير والموديل والـ view
            $roomTypes = RoomType::latest('id')->paginate(15);
            return view('dashboard.data-entry.room-types', compact('roomTypes'));
        } catch (Exception $e) {
            Log::error('Error fetching room types: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load room types.');
        }
    }

    /**
     * Store a newly created room type in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation (تغيير اسم الحقل والجدول)
        $validatedData = $request->validate([
            'room_type_name' => 'required|string|max:100|unique:rooms_types,room_type_name',
        ]);

        // 2. Prepare Data
        $data = $validatedData;

        // 3. Add to Database
        try {
            RoomType::create($data);
            // 4. Redirect (تغيير الـ route والرسالة)
            return redirect()->route('data-entry.room-types.index')
                ->with('success', 'Room Type created successfully.');
        } catch (Exception $e) {
            Log::error('Room Type Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create room type.')
                ->withInput();
        }
    }

    /**
     * Update the specified room type in storage.
     */
    public function update(Request $request, RoomType $roomType) // تغيير المتغير
    {
        // 1. Validation (تغيير اسم الحقل والجدول والمتغير)
        $validatedData = $request->validate([
            'room_type_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rooms_types')->ignore($roomType->id),
            ],
        ]);

        // 2. Prepare Data
        $data = $validatedData;

        // 3. Update Database
        try {
            $roomType->update($data);
            // 4. Redirect (تغيير الـ route والرسالة)
            return redirect()->route('data-entry.room-types.index')
                ->with('success', 'Room Type updated successfully.');
        } catch (Exception $e) {
            Log::error('Room Type Update Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update room type.')
                ->withInput();
        }
    }

    /**
     * Remove the specified room type from storage.
     */
    public function destroy(RoomType $roomType) // تغيير المتغير
    {
        // التحقق من القاعات المرتبطة
        if ($roomType->rooms()->exists()) {
            return redirect()->route('data-entry.room-types.index')
                ->with('error', 'Cannot delete room type. It is assigned to rooms.');
        }

        // 1. Delete
        try {
            $roomType->delete();
            // 2. Redirect (تغيير الـ route والرسالة)
            return redirect()->route('data-entry.room-types.index')
                ->with('success', 'Room Type deleted successfully.');
        } catch (Exception $e) {
            Log::error('Room Type Deletion Failed: ' . $e->getMessage());
            return redirect()->route('data-entry.room-types.index')
                ->with('error', 'Failed to delete room type.');
        }
    }

    // =============================================
    //             API Controller Methods
    // =============================================
    // (قم بإنشاء دوال API بنفس الطريقة إذا احتجتها)

    public function apiIndex()
    {
        try {
            // $types = RoomType::latest('id')->get(['id', 'room_type_name']);
            $types = RoomType::latest()->get();
            return response()->json(['success' => true, 'data' => $types], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }
    public function apiStore(Request $request)
    {
        $validatedData = $request->validate(['room_type_name' => 'required|string|max:100|unique:rooms_types,room_type_name']);
        try {
            $type = RoomType::create($validatedData);
            return response()->json(['success' => true, 'data' => $type, 'message' => 'Room Type created.'], 201);
        } catch (Exception $e) { /* ... */
            return response()->json(['success' => false, 'message' => 'Failed to create.'], 500);
        }
    }
    public function apiShow(RoomType $roomType)
    {
        return response()->json(['success' => true, 'data' => $roomType], 200);
    }
    public function apiUpdate(Request $request, RoomType $roomType)
    {
        $validatedData = $request->validate(['room_type_name' => ['required', 'string', 'max:100', Rule::unique('rooms_types')->ignore($roomType->id)]]);
        try {
            $roomType->update($validatedData);
            return response()->json(['success' => true, 'data' => $roomType, 'message' => 'Room Type updated.'], 200);
        } catch (Exception $e) { /* ... */
            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }
    public function apiDestroy(RoomType $roomType)
    {
        if ($roomType->rooms()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete: assigned to rooms.'], 409);
        }
        try {
            $roomType->delete();
            return response()->json(['success' => true, 'message' => 'Room Type deleted.'], 200);
        } catch (Exception $e) { /* ... */
            return response()->json(['success' => false, 'message' => 'Failed to delete.'], 500);
        }
    }
}
