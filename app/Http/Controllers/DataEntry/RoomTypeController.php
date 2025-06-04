<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\RoomType; // تم استيراده
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Handle bulk upload of room types from Excel file.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'room_type_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['room_type_excel_file' => 'Excel file']);

        try {
            $rows = Excel::toCollection(collect(), $request->file('room_type_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return redirect()->route('data-entry.room-types.index')
                    ->with('error', 'Uploaded Excel file is empty or has no data rows.');
            }

            $createdCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedNames = collect(); // لتتبع الأسماء التي تمت معالجتها من الملف

            $header = $rows->first()->map(fn($item) => strtolower(str_replace(' ', '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;

                $roomTypeName = $row->get('room_type_name', ''); // القيمة من عمود B (أو اسم العمود بعد تحويله لـ snake_case)

                // 1. تجاهل الأسطر الفارغة
                if (empty($roomTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because room_type_name is empty.";
                    continue;
                }

                // 2. فحص التكرار داخل الملف نفسه
                if ($processedNames->contains($roomTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate room_type_name '{$roomTypeName}' from within this file.";
                    continue;
                }

                // 3. التحقق من وجود الاسم في قاعدة البيانات (RoomType names should be unique)
                $existingRoomType = RoomType::where('room_type_name', $roomTypeName)->first();

                if ($existingRoomType) {
                    // النوع موجود بالفعل، تجاهله (لا نقوم بالتحديث هنا عادةً، فقط إضافة جديد)
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Room type '{$roomTypeName}' already exists in the system.";
                    $processedNames->push($roomTypeName); // اعتبره معالجاً لتجنب إضافته مرة أخرى من نفس الملف
                    continue;
                }

                // 4. إنشاء نوع قاعة جديد
                RoomType::create([
                    'room_type_name' => $roomTypeName,
                ]);
                $createdCount++;
                $processedNames->push($roomTypeName);
            }

            $message = "Room Types bulk upload processed. ";
            if ($createdCount > 0) $message .= "{$createdCount} new room types created. ";
            if ($skippedCount > 0) $message .= "{$skippedCount} rows skipped. ";

            if (!empty($skippedDetails)) {
                session()->flash('skipped_details', $skippedDetails);
            }

            return redirect()->route('data-entry.room-types.index')->with('success', trim($message));
        } catch (Exception $e) {
            Log::error('Room Type Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.room-types.index')
                ->with('error', 'An error occurred during bulk upload: ' . $e->getMessage());
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

    /**
     * Handle bulk upload of room types from Excel file via API.
     */
    public function apiBulkUpload(Request $request)
    {
        // 1. التحقق من الملف المرفوع
        $validator = Validator::make($request->all(), [
            'room_type_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['room_type_excel_file' => 'Excel file']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        try {
            $rows = Excel::toCollection(collect(), $request->file('room_type_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded Excel file is empty or contains no data rows after the header.'
                ], 400); // Bad Request
            }

            $createdCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedNames = collect();

            $header = $rows->first()->map(fn($item) => strtolower(str_replace(' ', '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;
                $roomTypeName = $row->get('room_type_name', '');

                if (empty($roomTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because room_type_name is empty.";
                    continue;
                }
                if ($processedNames->contains($roomTypeName)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate room_type_name '{$roomTypeName}' from within this file.";
                    continue;
                }

                $existingRoomType = RoomType::where('room_type_name', $roomTypeName)->first();
                if ($existingRoomType) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Room type '{$roomTypeName}' already exists in the system.";
                    $processedNames->push($roomTypeName);
                    continue;
                }

                RoomType::create(['room_type_name' => $roomTypeName]);
                $createdCount++;
                $processedNames->push($roomTypeName);
            }

            $summaryMessage = "Room Types bulk upload processed.";
            $responseData = [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'skipped_details' => $skippedDetails,
            ];

            return response()->json([
                'success' => true,
                'message' => $summaryMessage,
                'data' => $responseData
            ], 200); // OK

        } catch (Exception $e) {
            Log::error('API Room Type Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk upload.',
                'error_details' => $e->getMessage() // للمطور
            ], 500); // Internal Server Error
        }
    }
}
