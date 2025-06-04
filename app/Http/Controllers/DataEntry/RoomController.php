<?php

namespace App\Http\Controllers\DataEntry;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\Validator;

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


    /**
     * Handle bulk upload of rooms from Excel file.
     */
    // public function bulkUpload(Request $request)
    // {
    //     $request->validate([
    //         'room_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
    //     ], [], ['room_excel_file' => 'Excel file']);

    //     try {
    //         $rows = Excel::toCollection(collect(), $request->file('room_excel_file'))->first();

    //         if ($rows->isEmpty() || $rows->count() <= 1) {
    //             return redirect()->route('data-entry.rooms.index')
    //                 ->with('error', 'Uploaded Excel file is empty or has no data rows.');
    //         }

    //         $createdCount = 0;
    //         $updatedCount = 0;
    //         $skippedCount = 0;
    //         $skippedDetails = [];
    //         $processedRoomNos = collect(); // لتتبع room_no داخل الملف

    //         $header = $rows->first()->map(fn($item) => strtolower(str_replace(' ', '_', $item ?? '')));
    //         $dataRows = $rows->slice(1);

    //         // القيم المسموحة لـ room_gender (لتطابق الـ enum في قاعدة البيانات)
    //         $allowedGenders = ['Male', 'Female', 'Mixed', 'مختلط', 'ذكور', 'إناث'];
    //         $genderMap = [ // لتوحيد القيم العربية للإنجليزية إذا لزم الأمر
    //             'مختلط' => 'Mixed',
    //             'ذكور' => 'Male',
    //             'إناث' => 'Female',
    //         ];


    //         foreach ($dataRows as $rowKey => $rowArray) {
    //             $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
    //             $currentRowNumber = $rowKey + 2;

    //             $roomNo = $row->get('room_no');
    //             $roomName = $row->get('room_name');
    //             $roomSize = $row->get('room_size');
    //             $roomGenderExcel = $row->get('room_gender');
    //             $roomBranch = $row->get('room_branch');
    //             $roomTypeId = $row->get('room_type_id');
    //             // $equipmentExcel = $row->get('equipment'); // معطل حالياً

    //             // 1. تجاهل الأسطر الفارغة تماماً
    //             if (empty($roomNo) && empty($roomName)) {
    //                 $skippedCount++;
    //                 $skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty room_no and room_name).";
    //                 continue;
    //             }

    //             // 2. التحقق من الحقول المطلوبة مبدئياً
    //             if (empty($roomNo) || empty($roomName) || !isset($roomSize) || empty($roomGenderExcel) || !isset($roomTypeId)) {
    //                 $skippedCount++;
    //                 $skippedDetails[] = "Row {$currentRowNumber}: Skipped due to missing required data (No, Name, Size, Gender, or Type ID).";
    //                 continue;
    //             }

    //             // التحقق من القيم العددية
    //             if (!is_numeric($roomSize) || (int)$roomSize <= 0) {
    //                 $skippedCount++;
    //                 $skippedDetails[] = "Row {$currentRowNumber}: Skipped due to invalid room_size '{$roomSize}'. Must be a positive number.";
    //                 continue;
    //             }
    //             if (!is_numeric($roomTypeId) || !RoomType::find((int)$roomTypeId)) {
    //                 $skippedCount++;
    //                 $skippedDetails[] = "Row {$currentRowNumber}: Skipped due to invalid or non-existent room_type_id '{$roomTypeId}'.";
    //                 continue;
    //             }

    //             // توحيد قيم room_gender
    //             $roomGenderDB = $genderMap[trim($roomGenderExcel)] ?? trim($roomGenderExcel);
    //             if (!in_array($roomGenderDB, ['Male', 'Female', 'Mixed'])) {
    //                 $skippedCount++;
    //                 $skippedDetails[] = "Row {$currentRowNumber}: Skipped due to invalid room_gender '{$roomGenderExcel}'. Allowed: Male, Female, Mixed.";
    //                 continue;
    //             }


    //             // 4. فحص التكرار داخل الملف نفسه (بناءً على room_no)
    //             if ($processedRoomNos->contains($roomNo)) {
    //                 $skippedCount++;
    //                 $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate room_no '{$roomNo}' from within this file.";
    //                 continue;
    //             }

    //             // بيانات القاعة للتخزين/التحديث
    //             $roomData = [
    //                 'room_name' => $roomName,
    //                 'room_size' => (int)$roomSize,
    //                 'room_gender' => $roomGenderDB,
    //                 'room_branch' => empty($roomBranch) ? null : $roomBranch,
    //                 'room_type_id' => (int)$roomTypeId,
    //                 // 'equipment' => $equipmentExcel ? json_encode(explode(',', $equipmentExcel)) : null, // معطل
    //             ];

    //             // 3. البحث عن القاعة في قاعدة البيانات وتحديثها أو إنشاؤها
    //             $room = Room::where('room_no', $roomNo)->first();

    //             if ($room) {
    //                 // القاعة موجودة، قم بالتحديث
    //                 $room->update($roomData);
    //                 $updatedCount++;
    //             } else {
    //                 // القاعة غير موجودة، قم بإنشاء جديد
    //                 $roomData['room_no'] = $roomNo; // إضافة room_no للإنشاء
    //                 Room::create($roomData);
    //                 $createdCount++;
    //             }
    //             $processedRoomNos->push($roomNo);
    //         }

    //         $message = "Rooms bulk upload processed. ";
    //         if ($createdCount > 0) $message .= "{$createdCount} new rooms created. ";
    //         if ($updatedCount > 0) $message .= "{$updatedCount} rooms updated. ";
    //         if ($skippedCount > 0) $message .= "{$skippedCount} rows skipped. ";

    //         if (!empty($skippedDetails)) {
    //             session()->flash('skipped_details', $skippedDetails);
    //         }

    //         return redirect()->route('data-entry.rooms.index')->with('success', trim($message));
    //     } catch (Exception $e) {
    //         Log::error('Room Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    //         return redirect()->route('data-entry.rooms.index')
    //             ->with('error', 'An error occurred during bulk upload: ' . $e->getMessage());
    //     }
    // }
    /**
     * Handle bulk upload of rooms from Excel file.
     */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'room_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [], ['room_excel_file' => 'Excel file']);

        try {
            $rows = Excel::toCollection(collect(), $request->file('room_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return redirect()->route('data-entry.rooms.index')
                    ->with('error', 'Uploaded Excel file is empty or has no data rows.');
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedRoomNos = collect(); // لتتبع room_no التي تمت معالجتها من الملف

            $header = $rows->first()->map(fn($item) => strtolower(str_replace(' ', '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;

                $roomNo = $row->get('room_no');
                $roomName = $row->get('room_name');
                $roomSize = $row->get('room_size');
                $roomGender = $row->get('room_gender'); // سيتم التحقق منه
                $roomBranch = $row->get('room_branch');
                $roomTypeId = $row->get('room_type_id');
                // $equipment = $row->get('equipment'); // معطل حالياً

                // 1. تجاهل الأسطر الفارغة (إذا كان room_no فارغاً)
                if (empty($roomNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because room_no is empty.";
                    continue;
                }

                // 2. (اختياري) تجاهل الأسطر المدمجة (نفس منطق الفارغة إذا لم تكن البيانات الأساسية موجودة)

                // 4. فحص التكرار داخل الملف نفسه بناءً على room_no
                if ($processedRoomNos->contains($roomNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate room_no '{$roomNo}' from within this file.";
                    continue;
                }

                // التحقق من صحة البيانات المدخلة للسطر الحالي
                $rowData = [
                    'room_no' => $roomNo,
                    'room_name' => $roomName,
                    'room_size' => $roomSize,
                    'room_gender' => $roomGender,
                    'room_branch' => $roomBranch,
                    'room_type_id' => $roomTypeId,
                    // 'equipment' => $equipment, // معطل
                ];

                $rowValidator = Validator::make($rowData, [
                    'room_no' => 'required|string|max:20', // لا نتحقق من unique هنا لأننا سنقوم بـ updateOrCreate
                    'room_name' => 'nullable|string|max:255',
                    'room_size' => 'required|integer|min:1',
                    'room_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed', 'مختلط', 'ذكور', 'إناث'])], // إضافة القيم العربية المحتملة
                    'room_branch' => 'nullable|string|max:100',
                    'room_type_id' => 'required|integer|exists:rooms_types,id',
                    // 'equipment' => 'nullable|string', // إذا كان نصاً مفصولاً بفواصل، أو array إذا كان Excel يدعم
                ]);

                if ($rowValidator->fails()) {
                    $skippedCount++;
                    $errors = implode(', ', $rowValidator->errors()->all());
                    $skippedDetails[] = "Row {$currentRowNumber} (RoomNo: {$roomNo}): Skipped due to validation errors - {$errors}";
                    continue;
                }

                // تحويل القيم العربية لـ room_gender إلى الإنجليزية
                $genderMapping = ['مختلط' => 'Mixed', 'ذكور' => 'Male', 'إناث' => 'Female'];
                $englishGender = $genderMapping[strtolower($roomGender)] ?? ucfirst(strtolower($roomGender)); // تحويل + قيمة افتراضية
                if (!in_array($englishGender, ['Male', 'Female', 'Mixed'])) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (RoomNo: {$roomNo}): Skipped due to invalid room_gender value '{$roomGender}'.";
                    continue;
                }
                $validatedRowData = $rowValidator->validated();
                $validatedRowData['room_gender'] = $englishGender;

                // إذا كان equipment مفعلاً ويأتي كنص مفصول بفواصل
                // if (isset($validatedRowData['equipment']) && is_string($validatedRowData['equipment'])) {
                //     $validatedRowData['equipment'] = json_encode(array_map('trim', explode(',', $validatedRowData['equipment'])));
                // } else {
                //     $validatedRowData['equipment'] = null;
                // }


                // 3. البحث عن القاعة في قاعدة البيانات وتحديثها أو إنشاؤها
                // updateOrCreate تبحث عن سجل بالـ room_no، إذا وجدته تحدثه، وإلا تنشئ جديداً
                $room = Room::updateOrCreate(
                    ['room_no' => $validatedRowData['room_no']], // الشرط للبحث
                    [ // البيانات للتحديث أو الإنشاء
                        'room_name' => $validatedRowData['room_name'],
                        'room_size' => $validatedRowData['room_size'],
                        'room_gender' => $validatedRowData['room_gender'],
                        'room_branch' => $validatedRowData['room_branch'],
                        'room_type_id' => $validatedRowData['room_type_id'],
                        // 'equipment' => $validatedRowData['equipment'], // معطل
                    ]
                );

                if ($room->wasRecentlyCreated) {
                    $createdCount++;
                } elseif ($room->wasChanged()) { // للتحقق إذا تم تحديث أي حقل فعلاً
                    $updatedCount++;
                } else {
                    // لم يتم إنشاؤه ولم يتغير (موجود بنفس البيانات)
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (RoomNo: {$roomNo}): Data already up-to-date.";
                }
                $processedRoomNos->push($roomNo);
            }

            $message = "Classrooms bulk upload processed. ";
            if ($createdCount > 0) $message .= "{$createdCount} new classrooms created. ";
            if ($updatedCount > 0) $message .= "{$updatedCount} classrooms updated. ";
            if ($skippedCount > 0) $message .= "{$skippedCount} rows skipped. ";

            if (!empty($skippedDetails)) {
                session()->flash('skipped_details', $skippedDetails);
            }

            return redirect()->route('data-entry.rooms.index')->with('success', trim($message));
        } catch (Exception $e) {
            Log::error('Room Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.rooms.index')
                ->with('error', 'An error occurred during bulk upload: ' . $e->getMessage());
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

    /**
     * Handle bulk upload of rooms from Excel file via API.
     */
    public function apiBulkUpload(Request $request)
    {
        // 1. التحقق من الملف المرفوع (نفس الويب)
        $validator = Validator::make($request->all(), [
            'room_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // اسم الحقل كما في Postman
        ], [], ['room_excel_file' => 'Excel file']);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for uploaded file.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        try {
            // 2. قراءة البيانات من ملف الإكسل (نفس الويب)
            $rows = Excel::toCollection(collect(), $request->file('room_excel_file'))->first();

            if ($rows->isEmpty() || $rows->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded Excel file is empty or contains no data rows after the header.'
                ], 400); // Bad Request
            }

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $skippedDetails = [];
            $processedRoomNos = collect();

            $header = $rows->first()->map(fn($item) => strtolower(str_replace(' ', '_', $item ?? '')));
            $dataRows = $rows->slice(1);

            foreach ($dataRows as $rowKey => $rowArray) {
                $row = $header->combine($rowArray->map(fn($val) => trim($val ?? '')));
                $currentRowNumber = $rowKey + 2;

                $roomNo = $row->get('room_no'); // يجب أن يكون اسم العمود في الإكسل room_no
                // ... (جلب باقي الحقول بنفس الطريقة)
                $roomName = $row->get('room_name');
                $roomSize = $row->get('room_size');
                $roomGender = $row->get('room_gender');
                $roomBranch = $row->get('room_branch');
                $roomTypeId = $row->get('room_type_id');

                // 1. تجاهل الأسطر الفارغة (إذا كان room_no فارغاً)
                if (empty($roomNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped because room_no is empty.";
                    continue;
                }
                // 2. فحص التكرار داخل الملف نفسه
                if ($processedRoomNos->contains($roomNo)) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Skipped duplicate room_no '{$roomNo}' from within this file.";
                    continue;
                }

                // التحقق من صحة بيانات السطر
                $rowDataForValidation = [
                    'room_no' => $roomNo,
                    'room_name' => $roomName,
                    'room_size' => $roomSize,
                    'room_gender' => $roomGender,
                    'room_branch' => $roomBranch,
                    'room_type_id' => $roomTypeId,
                ];
                $rowValidator = Validator::make($rowDataForValidation, [
                    'room_no' => 'required|string|max:20',
                    'room_name' => 'nullable|string|max:255',
                    'room_size' => 'required|integer|min:1',
                    'room_gender' => ['required', Rule::in(['Male', 'Female', 'Mixed', 'مختلط', 'ذكور', 'إناث'])],
                    'room_branch' => 'nullable|string|max:100',
                    'room_type_id' => 'required|integer|exists:rooms_types,id',
                ]);

                if ($rowValidator->fails()) {
                    $skippedCount++;
                    $errors = implode(', ', $rowValidator->errors()->all());
                    $skippedDetails[] = "Row {$currentRowNumber} (RoomNo: {$roomNo}): Skipped - {$errors}";
                    continue;
                }
                $validatedRowData = $rowValidator->validated();
                $genderMapping = ['مختلط' => 'Mixed', 'ذكور' => 'Male', 'إناث' => 'Female'];
                $validatedRowData['room_gender'] = $genderMapping[strtolower($validatedRowData['room_gender'])] ?? ucfirst(strtolower($validatedRowData['room_gender']));
                if (!in_array($validatedRowData['room_gender'], ['Male', 'Female', 'Mixed'])) {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber}: Invalid gender value '{$roomGender}'.";
                    continue;
                }

                // 3. البحث والتحديث/الإنشاء
                $room = Room::updateOrCreate(
                    ['room_no' => $validatedRowData['room_no']],
                    [
                        'room_name' => $validatedRowData['room_name'],
                        'room_size' => $validatedRowData['room_size'],
                        'room_gender' => $validatedRowData['room_gender'],
                        'room_branch' => $validatedRowData['room_branch'],
                        'room_type_id' => $validatedRowData['room_type_id'],
                        // 'equipment' => null, // إذا كنت ستضيفه لاحقاً
                    ]
                );

                if ($room->wasRecentlyCreated) {
                    $createdCount++;
                } elseif ($room->wasChanged()) {
                    $updatedCount++;
                } else {
                    $skippedCount++;
                    $skippedDetails[] = "Row {$currentRowNumber} (RoomNo: {$roomNo}): Data already up-to-date.";
                }
                $processedRoomNos->push($roomNo);
            }

            // 4. بناء الاستجابة
            $summaryMessage = "Classrooms bulk upload processed via API.";
            $responseData = [
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'skipped_details' => $skippedDetails,
            ];

            return response()->json([
                'success' => true,
                'message' => $summaryMessage,
                'data' => $responseData
            ], 200); // OK

        } catch (Exception $e) {
            Log::error('API Room Bulk Upload Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during API bulk upload.',
                'error_details' => $e->getMessage() // للمطور
            ], 500); // Internal Server Error
        }
    }
}
