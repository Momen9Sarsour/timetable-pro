<?php

namespace App\Http\Controllers\DataEntry;

use Exception;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\Instructor;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // اختياري

class InstructorController extends Controller
{
    // =============================================
    //            Web Controller Methods
    // =============================================

    /**
     * Display a listing of the instructors (Web View) with Pagination.
     * عرض قائمة المدرسين لصفحة الويب مع تقسيم الصفحات (الأحدث أولاً)
     */
    // public function index()
    // {
    //     try {
    //         // جلب المدرسين مرتبين بالأحدث مع علاقات user و department وتقسيم الصفحات
    //         $instructors = Instructor::with(['user', 'department']) // Eager load relations
    //             ->latest('id')                // Order by newest first
    //             ->paginate(10);               // Paginate results

    //         // جلب المستخدمين المتاحين لربطهم
    //         $availableUsers = User::whereDoesntHave('instructor')
    //             ->whereHas('role', fn($q) => $q->whereIn('name', ['instructor', 'hod', 'admin']))
    //             ->orderBy('name')->get();

    //         // جلب الأقسام للقائمة المنسدلة
    //         $departments = Department::orderBy('department_name')->get();

    //         return view('dashboard.data-entry.instructors', compact('instructors', 'availableUsers', 'departments'));
    //     } catch (Exception $e) {
    //         Log::error('Error fetching instructors for web view: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Could not load instructors.');
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $instructors = Instructor::with(['user:id,name,email', 'department:id,department_name'])
                ->latest('id')
                ->paginate(15);
            // لجلب الأقسام لنموذج الإضافة/التعديل
            $departments = Department::orderBy('department_name')->get(['id', 'department_name']);
            // لجلب الأدوار المحتملة للمدرس (إذا أردت تحديدها في الفورم)
            $instructorRoles = Role::whereIn('name', ['instructor', 'hod'])->orderBy('display_name')->get(['id', 'display_name']);


            return view('dashboard.data-entry.instructors', compact('instructors', 'departments', 'instructorRoles'));
        } catch (Exception $e) {
            Log::error('Error fetching instructors: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not load instructors list.');
        }
    }

    /**
     * Store a newly created instructor from web request.
     * تخزين مدرس جديد قادم من طلب ويب
     */
    // public function store(Request $request)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'user_id' => 'required|integer|exists:users,id|unique:instructors,user_id',
    //         'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
    //         'instructor_name' => 'required|string|max:255',
    //         'academic_degree' => 'nullable|string|max:100',
    //         'department_id' => 'required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'nullable|integer|min:0|max:100',
    //         // 'office_location' => 'nullable|string|max:255', // حقول المكتب
    //         // 'office_hours' => 'nullable|string|max:255',
    //         'availability_preferences' => 'nullable|string',
    //     ]);

    //     // التحقق الإضافي من دور المستخدم
    //     $user = User::find($request->user_id);
    //     if (!$user || !$user->hasRole(['instructor', 'hod', 'admin'])) {
    //         return redirect()->back()
    //             ->with('error', 'The selected user does not have a valid role to be an instructor.')
    //             ->withInput();
    //     }

    //     // 2. Prepare Data (validatedData جاهزة)
    //     $data = $validatedData;

    //     // 3. Add to Database
    //     try {
    //         Instructor::create($data);
    //         // 4. Redirect
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('success', 'Instructor created successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Instructor Creation Failed (Web): ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to create instructor.')
    //             ->withInput();
    //     }
    // }

    public function store(Request $request)
    {
        $errorBagName = 'addInstructorModal';
        $validatedData = $request->validateWithBag($errorBagName, [
            // User fields
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed', // استخدام قاعدة أبسط
            // 'password' => ['required', 'confirmed', Password::min(8)], // استخدام قاعدة أبسط
            'role_id_for_instructor' => 'required|integer|exists:roles,id', // دور المستخدم الجديد (مدرس/رئيس قسم)

            // Instructor fields
            'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string',
        ]);

        DB::beginTransaction(); // بدء Transaction
        try {
            // 1. إنشاء المستخدم
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => $validatedData['role_id_for_instructor'], // استخدام الدور المحدد
                'email_verified_at' => now(), // تفعيل الإيميل مباشرة (اختياري)
            ]);

            // 2. إنشاء المدرس وربطه باليوزر
            Instructor::create([
                'user_id' => $user->id,
                'instructor_no' => $validatedData['instructor_no'],
                'instructor_name' => $user->name, // استخدام اسم اليوزر كاسم افتراضي للمدرس
                'academic_degree' => $validatedData['academic_degree'],
                'department_id' => $validatedData['department_id'],
                'max_weekly_hours' => $validatedData['max_weekly_hours'],
                // 'office_location' => $validatedData['office_location'],
                // 'office_hours' => $validatedData['office_hours'],
                'availability_preferences' => $validatedData['availability_preferences'],
            ]);

            DB::commit(); // تأكيد العمليات

            return redirect()->route('data-entry.instructors.index')
                ->with('success', 'Instructor and user account created successfully.');
        } catch (Exception $e) {
            DB::rollBack(); // التراجع عن العمليات في حالة الخطأ
            Log::error('Instructor & User Creation Failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create instructor: ' . $e->getMessage())
                ->withInput()
                ->withErrors($validatedData, $errorBagName); // إعادة أخطاء التحقق إذا فشل بعد التحقق (نادر)
        }
    }

    /**
     * Update the specified instructor from web request.
     * تحديث مدرس محدد قادم من طلب ويب
     */
    // public function update(Request $request, Instructor $instructor)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no,' . $instructor->id,
    //         'instructor_name' => 'required|string|max:255',
    //         'academic_degree' => 'nullable|string|max:100',
    //         'department_id' => 'required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'nullable|integer|min:0|max:100',
    //         // 'office_location' => 'nullable|string|max:255',
    //         // 'office_hours' => 'nullable|string|max:255',
    //         'availability_preferences' => 'nullable|string',
    //     ]);

    //     // 2. Prepare Data (validatedData جاهزة)
    //     $data = $validatedData;

    //     // 3. Update Database
    //     try {
    //         $instructor->update($data);
    //         // 4. Redirect
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('success', 'Instructor updated successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Instructor Update Failed (Web): ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to update instructor.')
    //             ->withInput();
    //     }
    // }

    public function update(Request $request, Instructor $instructor)
    {
        $user = $instructor->user; // جلب المستخدم المرتبط
        if (!$user) {
            // معالجة حالة عدم وجود مستخدم مرتبط (نادر الحدوث إذا كان الإنشاء صحيحاً)
            return redirect()->route('data-entry.instructors.index')->with('error', 'User account for this instructor not found.');
        }

        $errorBagName = 'editInstructorModal_' . $instructor->id;
        $validatedData = $request->validate([
            // User fields
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed', // اختياري
            // 'password' => ['nullable', 'confirmed', Password::min(8)], // اختياري
            'role_id_for_instructor' => 'required|integer|exists:roles,id',

            // Instructor fields
            'instructor_no' => ['required', 'string', 'max:20', Rule::unique('instructors')->ignore($instructor->id)],
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string',
        ]);

        // dd('dddd');
        DB::beginTransaction();
        try {
            // 1. تحديث بيانات المستخدم
            $userData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'role_id' => $validatedData['role_id_for_instructor'],
            ];
            if (!empty($validatedData['password'])) {
                $userData['password'] = Hash::make($validatedData['password']);
            }
            $user->update($userData);

            // 2. تحديث بيانات المدرس
            $instructor->update([
                'instructor_no' => $validatedData['instructor_no'],
                'instructor_name' => $validatedData['name'], // تحديث اسم المدرس ليتطابق
                'academic_degree' => $validatedData['academic_degree'],
                'department_id' => $validatedData['department_id'],
                'max_weekly_hours' => $validatedData['max_weekly_hours'],
                // 'office_location' => $validatedData['office_location'],
                // 'office_hours' => $validatedData['office_hours'],
                'availability_preferences' => $validatedData['availability_preferences'],
            ]);

            DB::commit();
            return redirect()->route('data-entry.instructors.index')
                ->with('success', 'Instructor and user account updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Instructor & User Update Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update instructor: ' . $e->getMessage())
                ->withInput()
                ->withErrors($validatedData, $errorBagName);
        }
    }

    /**
     * Remove the specified instructor from web request.
     * حذف مدرس محدد قادم من طلب ويب
     */
    // public function destroy(Instructor $instructor)
    // {
    //     // (اختياري) التحقق من السجلات المرتبطة
    //     // if ($instructor->scheduleEntries()->exists()) { ... }

    //     // 1. Delete from Database
    //     try {
    //         $instructor->delete();
    //         // 2. Redirect
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('success', 'Instructor record deleted successfully.');
    //     } catch (Exception $e) {
    //         Log::error('Instructor Deletion Failed (Web): ' . $e->getMessage());
    //         return redirect()->route('data-entry.instructors.index') // تأكد من اسم الروت
    //             ->with('error', 'Failed to delete instructor record.');
    //     }
    // }

    public function destroy(Instructor $instructor)
    {
        // (اختياري) التحقق من وجود ارتباطات أخرى للمدرس قبل الحذف (مثل شعب معينة له)
        // if ($instructor->sections()->exists() || $instructor->scheduleEntries()->exists()) {
        //     return redirect()->route('data-entry.instructors.index')
        //                      ->with('error', 'Cannot delete instructor. They are assigned to sections or schedules.');
        // }

        DB::beginTransaction();
        try {
            $user = $instructor->user; // جلب المستخدم المرتبط

            // حذف سجل المدرس أولاً (سيؤدي لتشغيل onDelete('set null') إذا كان user_id في instructors يقبله)
            // لكن بما أننا سنحذف اليوزر، ترتيب الحذف هنا ليس حرجاً جداً
            $instructor->delete();

            // ثم حذف المستخدم المرتبط (إذا وجد)
            if ($user) {
                // (اختياري) تحقق إذا كان هذا المستخدم له أدوار أخرى غير "instructor" أو "hod"
                // إذا كان له أدوار إدارية أخرى هامة، قد لا ترغب بحذفه تلقائياً
                // if ($user->role->name === 'admin' && User::where('role_id', $user->role_id)->count() <= 1) {
                //     DB::rollBack();
                //     return redirect()->route('data-entry.instructors.index')
                //                      ->with('error', 'Cannot delete the last admin user via instructor deletion.');
                // }
                $user->delete();
            }

            DB::commit();
            return redirect()->route('data-entry.instructors.index')
                ->with('success', 'Instructor and associated user account deleted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Instructor & User Deletion Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return redirect()->route('data-entry.instructors.index')
                ->with('error', 'Failed to delete instructor: ' . $e->getMessage());
        }
    }

    private $createdCount = 0;
    private $updatedCount = 0;
    private $skippedCount = 0;
    private $skippedDetails = [];
    private $processedInstructorNos = []; // لتتبع التكرار داخل الملف

    /**
     * Handle the import of instructors from an Excel file.
     */
    public function importExcel(Request $request)
    {
        $request->validate(['instructor_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);
        $this->resetCounters();

        try {
            $rows = Excel::toArray(new \stdClass(), $request->file('instructor_excel_file'))[0];
            if (count($rows) <= 1) {
                return redirect()->route('data-entry.instructors.index')->with('error', 'Uploaded Excel file is empty or contains only a header row.');
            }

            $header = array_map('strtolower', array_map('trim', array_shift($rows)));
            // البحث عن مواقع الأعمدة (مع مرونة في الأسماء)
            $instructorNoCol = $this->getColumnIndex($header, ['instructor_no', 'instructorno', 'رقم المدرس', 'الرقم الوظيفي']);
            $instructorNameCol = $this->getColumnIndex($header, ['instructor_name', 'instructorname', 'اسم المدرس', 'الاسم']);
            $departmentCol = $this->getColumnIndex($header, ['department_id', 'departmentid', 'department', 'القسم']);
            $emailCol = $this->getColumnIndex($header, ['email', 'البريد الالكتروني', 'الايميل']);
            $degreeCol = $this->getColumnIndex($header, ['academic_degree', 'academicdegree', 'degree', 'الدرجة العلمية']);
            $maxHoursCol = $this->getColumnIndex($header, ['max_weekly_hours', 'maxweeklyhours', 'ساعات الدوام']);

            if (is_null($instructorNoCol) || is_null($instructorNameCol) || is_null($departmentCol)) {
                $missing = [];
                if (is_null($instructorNoCol)) $missing[] = "'instructor_no'";
                if (is_null($instructorNameCol)) $missing[] = "'instructor_name'";
                if (is_null($departmentCol)) $missing[] = "'department_id' or 'department_name'";
                return redirect()->route('data-entry.instructors.index')->with('error', 'Excel file is missing required columns: ' . implode(', ', $missing));
            }

            $currentRowNumber = 1;
            DB::beginTransaction(); // بدء Transaction لضمان سلامة البيانات

            foreach ($rows as $row) {
                $currentRowNumber++;
                $rowData = [];
                foreach ($header as $index => $colName) {
                    $rowData[$colName] = $row[$index] ?? null;
                }

                if (count(array_filter($row)) == 0) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty).";
                    $this->skippedCount++;
                    continue;
                }

                $instructorNo = trim($rowData[$header[$instructorNoCol]] ?? null);
                if (empty($instructorNo)) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing instructor_no).";
                    $this->skippedCount++;
                    continue;
                }

                // منع تكرار معالجة نفس الرقم الوظيفي من الملف
                if (in_array($instructorNo, $this->processedInstructorNos)) {
                    $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (duplicate instructor_no '{$instructorNo}' within the file).";
                    $this->skippedCount++;
                    continue;
                }
                $this->processedInstructorNos[] = $instructorNo;

                $instructorNameInput = trim($rowData[$header[$instructorNameCol]] ?? null);
                $departmentIdentifier = trim($rowData[$header[$departmentCol]] ?? null);
                $emailInput = isset($emailCol) ? trim($rowData[$header[$emailCol]] ?? null) : null;
                $degreeInputFromFile = isset($degreeCol) ? trim($rowData[$header[$degreeCol]] ?? null) : null;
                $maxHoursInput = isset($maxHoursCol) ? trim($rowData[$header[$maxHoursCol]] ?? null) : null;

                if (empty($instructorNameInput) || empty($departmentIdentifier)) {
                    $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (missing instructor_name or department).";
                    $this->skippedCount++;
                    continue;
                }

                // 1. استخراج الاسم والدرجة العلمية
                $name = $instructorNameInput;
                $degreeFromName = null;
                if (preg_match('/\(.+\)/u', $instructorNameInput, $matches)) {
                    $degreeFromName = trim($matches[0], '()');
                    $name = trim(str_replace($matches[0], '', $instructorNameInput));
                }
                $academicDegree = $degreeFromName ?: $degreeInputFromFile;

                // 2. البحث عن القسم
                $department = $this->findDepartment($departmentIdentifier);
                if (!$department) {
                    $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (Department '{$departmentIdentifier}' not found).";
                    $this->skippedCount++;
                    continue;
                }

                // 3. تجهيز الإيميل
                $email = $emailInput;
                if (empty($email)) {
                    $baseEmailName = 'momen' . preg_replace('/[^A-Za-z0-9]/', '', $instructorNo); // إزالة أي رموز من رقم المدرس
                    $email = $this->generateUniqueEmail($baseEmailName, '@ptc.edu'); // افترض نطاق الكلية
                } else {
                    // التحقق من صحة الإيميل إذا تم إدخاله
                    $emailValidator = Validator::make(['email' => $email], ['email' => 'email']);
                    if ($emailValidator->fails()) {
                        $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (Invalid email format '{$email}').";
                        $this->skippedCount++;
                        continue;
                    }
                }

                // 4. البحث عن المدرس أو إنشاء جديد (User و Instructor)
                $instructor = Instructor::where('instructor_no', $instructorNo)->first();
                $userToUpdateOrCreate = null;

                if ($instructor) { // تحديث مدرس موجود
                    $userToUpdateOrCreate = $instructor->user;
                    if (!$userToUpdateOrCreate) { // في حالة نادرة أن المدرس موجود بدون مستخدم
                        Log::warning("Instructor ID {$instructor->id} exists without a user. Creating a new user.");
                        $userToUpdateOrCreate = $this->createUserForInstructor($name, $email, 'instructor'); // افترض دور افتراضي
                        $instructor->user_id = $userToUpdateOrCreate->id;
                    }

                    // تحديث بيانات المستخدم
                    $userData = ['name' => $name, 'email' => $email];
                    // التحقق من تفرد الإيميل عند التحديث
                    if (User::where('email', $email)->where('id', '!=', $userToUpdateOrCreate->id)->exists()) {
                        $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (Email '{$email}' already taken by another user).";
                        $this->skippedCount++;
                        continue;
                    }
                    $userToUpdateOrCreate->update($userData);

                    // تحديث بيانات المدرس
                    $instructor->instructor_name = $name;
                    $instructor->academic_degree = $academicDegree;
                    $instructor->department_id = $department->id;
                    if (!is_null($maxHoursInput) && is_numeric($maxHoursInput)) {
                        $instructor->max_weekly_hours = (int)$maxHoursInput;
                    }
                    // يمكن إضافة تحديث للحقول الأخرى المعلقة
                    $instructor->save();
                    $this->updatedCount++;
                } else { // إنشاء مدرس جديد ومستخدم جديد
                    // التحقق من تفرد الإيميل قبل إنشاء مستخدم جديد
                    if (User::where('email', $email)->exists()) {
                        $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (Generated/Provided email '{$email}' already exists).";
                        $this->skippedCount++;
                        continue;
                    }
                    $userToUpdateOrCreate = $this->createUserForInstructor($name, $email, 'instructor'); // افترض دور افتراضي

                    Instructor::create([
                        'user_id' => $userToUpdateOrCreate->id,
                        'instructor_no' => $instructorNo,
                        'instructor_name' => $name,
                        'academic_degree' => $academicDegree,
                        'department_id' => $department->id,
                        'max_weekly_hours' => (!is_null($maxHoursInput) && is_numeric($maxHoursInput)) ? (int)$maxHoursInput : null,
                        // يمكن إضافة الحقول المعلقة
                    ]);
                    $this->createdCount++;
                }
            } // نهاية حلقة الصفوف

            DB::commit(); // تأكيد كل العمليات

            // بناء رسالة النجاح
            $messages = [];
            if ($this->createdCount > 0) $messages[] = "{$this->createdCount} new instructor(s) created.";
            if ($this->updatedCount > 0) $messages[] = "{$this->updatedCount} existing instructor(s) updated.";
            if ($this->skippedCount > 0) $messages[] = "{$this->skippedCount} row(s) were skipped.";
            if (empty($messages)) {
                return redirect()->route('data-entry.instructors.index')->with('info', 'Excel file processed. No changes made or no valid data found.');
            } else {
                return redirect()->route('data-entry.instructors.index')->with('success', implode(' ', $messages))->with('skipped_details', $this->skippedDetails);
            }
        } catch (Exception $e) {
            DB::rollBack(); // تراجع في حالة الخطأ
            Log::error('Instructors Excel Import Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('data-entry.instructors.index')
                ->with('error', 'An error occurred during Excel import: ' . $e->getMessage())
                ->with('skipped_details', $this->skippedDetails); // إرجاع ما تم تخطيه حتى الآن
        }
    }

    // --- دوال مساعدة للـ Import ---
    private function resetCounters()
    {
        $this->createdCount = 0;
        $this->updatedCount = 0;
        $this->skippedCount = 0;
        $this->skippedDetails = [];
        $this->processedInstructorNos = [];
    }
    private function getColumnIndex(array $header, array $possibleNames)
    {
        $normalizedHeader = array_map(fn($h) => strtolower(str_replace([' ', '_', '-'], '', trim($h))), $header);

        foreach ($possibleNames as $name) {
            $normalizedName = strtolower(str_replace([' ', '_', '-'], '', trim($name)));
            $index = array_search($normalizedName, $normalizedHeader);
            if ($index !== false) {
                return $index; // Return the original index from the header
            }
        }
        return null;
    }

    private function findDepartment($identifier)
    {
        if (is_numeric($identifier)) return Department::find($identifier);
        $normalized = $this->normalizeArabicStringForSearch($identifier);
        return Department::whereRaw('REPLACE(LOWER(department_name), " ", "") LIKE ?', ["%{$normalized}%"])
            ->orWhereRaw('LOWER(department_no) LIKE ?', ["%{$normalized}%"])
            ->first();
    }

    private function normalizeArabicStringForSearch($string)
    {
        // Normalize Hamza (أ, إ, آ -> ا)
        $string = str_replace(['أ', 'إ', 'آ'], 'ا', $string);
        // Normalize Alef Maqsura (ى -> ي)
        $string = str_replace('ى', 'ي', $string);
        // Normalize Taa Marbuta (ة -> ه)
        $string = str_replace('ة', 'ه', $string);
        // Remove "ال" from the beginning of words
        $string = preg_replace('/\bال/u', '', $string);
        // Remove extra spaces and convert to lowercase
        $string = strtolower(preg_replace('/\s+/u', '', trim($string)));

        $string = preg_replace('/[أإآ]/u', 'ا', $string); // توحيد الألفات
        $string = preg_replace('/^ال/u', '', $string); // إزالة "ال" التعريف من البداية
        $string = preg_replace('/\s+/u', '', trim($string)); // إزالة كل الفراغات وتحويل لحروف صغيرة
        return strtolower($string);
    }

    private function generateUniqueEmail($baseName, $domain, $counter = 0) {
        $email = $baseName . ($counter > 0 ? str_pad($counter, 2, '0', STR_PAD_LEFT) : '') . $domain;
        if (User::where('email', $email)->exists()) {
            return $this->generateUniqueEmail($baseName, $domain, $counter + 1);
        }
        return $email;
    }

    // private function generateUniqueEmail($baseName, $domain = '@ptc.edu', $instructorNo = null, $counter = 0): string
    // {
    //     // بناء الجزء الأول من الإيميل (يفضل استخدام شيء فريد مثل الرقم الوظيفي إذا توفر)
    //     $prefix = Str::slug($baseName, ''); // إزالة الفراغات والرموز من الاسم
    //     if (empty($prefix) && $instructorNo) { // إذا كان الاسم عربياً بالكامل، استخدم الرقم الوظيفي
    //         $prefix = 'inst' . preg_replace('/[^A-Za-z0-9]/', '', $instructorNo);
    //     } elseif (empty($prefix) && empty($instructorNo)) {
    //         $prefix = 'instructor' . Str::random(4); // اسم عشوائي إذا فشل كل شيء
    //     }


    //     $emailTry = $prefix . ($counter > 0 ? str_pad($counter, 2, '0', STR_PAD_LEFT) : ($instructorNo ? preg_replace('/[^A-Za-z0-9]/', '', $instructorNo) : '')) . $domain;
    //     if (empty($emailTry)) { // في حالة نادرة جداً أن كل شيء فارغ
    //         $emailTry = 'user' . time() . $counter . $domain;
    //     }


    //     // ضمان أن الجزء المحلي لا يتجاوز 64 حرفاً (حد شائع)
    //     $localPart = explode('@', $emailTry)[0];
    //     if (strlen($localPart) > 60) { // ترك بعض الحروف للـ counter إذا لزم الأمر
    //         $localPart = substr($localPart, 0, 60);
    //         $emailTry = $localPart . $domain;
    //     }


    //     if (User::where('email', $emailTry)->exists()) {
    //         // إذا كان الإيميل موجوداً، حاول بإضافة/زيادة العداد
    //         // إذا لم يكن هناك رقم وظيفي، استخدم عداداً عشوائياً أكثر
    //         $newBaseForCounter = $instructorNo ? preg_replace('/[^A-Za-z0-9]/', '', $instructorNo) : Str::random(2);
    //         return $this->generateUniqueEmail($baseName, $domain, $newBaseForCounter . ($counter + 1)); // تغيير طريقة العداد
    //     }
    //     return $emailTry;
    // }

    private function createUserForInstructor($name, $email, $roleName = 'instructor')
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) { // دور افتراضي إذا لم يوجد
            $role = Role::firstOrCreate(['name' => 'instructor'], ['display_name' => 'Instructor']);
        }
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('123456789'), // كلمة مرور افتراضية
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }


    // =============================================
    //             API Controller Methods
    // =============================================

    /**
     * Display a listing of the instructors (API).
     */
    public function apiIndex(Request $request)
    {
        try {
            $query = Instructor::with([
                'user:id,name,email,role_id', // جلب دور المستخدم أيضاً
                'user.role:id,name,display_name', // تفاصيل الدور
                'department:id,department_name'
            ]);

            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->has('q')) {
                $searchTerm = $request->q;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('instructor_no', 'like', "%{$searchTerm}%")
                        ->orWhere('instructor_name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$searchTerm}%")->orWhere('email', 'like', "%{$searchTerm}%"));
                });
            }

            // --- الـ Pagination للـ API (معطل حالياً، جلب الكل) ---
            $instructors = $query->latest('id')->get();
            /*
            $perPage = $request->query('per_page', 15);
            $instructorsPaginated = $query->latest('id')->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $instructorsPaginated->items(),
                'pagination' => [ 'total' => $instructorsPaginated->total(), ... ]
            ], 200);
            */

            return response()->json(['success' => true, 'data' => $instructors], 200);
        } catch (Exception $e) {
            Log::error('API Error fetching instructors: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * Store a newly created instructor and associated user from API request.
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // User fields
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8', // لا حاجة لـ confirmed في API عادةً
            'role_id_for_instructor' => 'required|integer|exists:roles,id',

            // Instructor fields
            'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
            'academic_degree' => 'nullable|string|max:100',
            'department_id' => 'required|integer|exists:departments,id',
            'max_weekly_hours' => 'nullable|integer|min:0|max:100',
            // 'office_location' => 'nullable|string|max:255',
            // 'office_hours' => 'nullable|string|max:255',
            'availability_preferences' => 'nullable|string', // أو 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated(); // الحصول على البيانات المتحقق منها

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => $validatedData['role_id_for_instructor'],
                'email_verified_at' => now(), // افترض التفعيل المباشر للـ API
            ]);

            $instructor = Instructor::create([
                'user_id' => $user->id,
                'instructor_no' => $validatedData['instructor_no'],
                'instructor_name' => $user->name,
                'academic_degree' => $validatedData['academic_degree'] ?? null,
                'department_id' => $validatedData['department_id'],
                'max_weekly_hours' => $validatedData['max_weekly_hours'] ?? null,
                // 'office_location' => $validatedData['office_location'] ?? null,
                // 'office_hours' => $validatedData['office_hours'] ?? null,
                'availability_preferences' => $validatedData['availability_preferences'] ?? null,
            ]);

            DB::commit();
            // تحميل العلاقات لعرضها في الاستجابة
            $instructor->load(['user.role', 'department']);
            return response()->json([
                'success' => true,
                'data' => $instructor,
                'message' => 'Instructor and user account created successfully.'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Instructor & User Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create instructor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified instructor (API).
     */
    public function apiShow(Instructor $instructor) // Route Model Binding
    {
        try {
            $instructor->load(['user.role', 'department', 'subjects:subjects.id,subject_no,subject_name']); // تحميل المواد المعينة أيضاً
            return response()->json(['success' => true, 'data' => $instructor], 200);
        } catch (Exception $e) {
            Log::error("API Error fetching instructor ID {$instructor->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Instructor not found or server error.'], 404); // أو 500
        }
    }

    /**
     * Update the specified instructor and associated user from API request.
     */
    public function apiUpdate(Request $request, Instructor $instructor)
    {
        $user = $instructor->user;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User account for this instructor not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            // User fields (sometimes لأن الـ API قد يرسل جزءاً من البيانات)
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'required|string|min:8', // كلمة المرور اختيارية
            'role_id_for_instructor' => 'sometimes|required|integer|exists:roles,id',

            // Instructor fields
            'instructor_no' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('instructors')->ignore($instructor->id)],
            'academic_degree' => 'sometimes|nullable|string|max:100',
            'department_id' => 'sometimes|required|integer|exists:departments,id',
            'max_weekly_hours' => 'sometimes|nullable|integer|min:0|max:100',
            // 'office_location' => 'sometimes|nullable|string|max:255',
            // 'office_hours' => 'sometimes|nullable|string|max:255',
            'availability_preferences' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated(); // الحصول فقط على البيانات التي تم التحقق منها وتم إرسالها

        DB::beginTransaction();
        try {
            // 1. تحديث بيانات المستخدم (فقط إذا تم إرسالها)
            $userDataToUpdate = [];
            if (isset($validatedData['name'])) $userDataToUpdate['name'] = $validatedData['name'];
            if (isset($validatedData['email'])) $userDataToUpdate['email'] = $validatedData['email'];
            if (isset($validatedData['role_id_for_instructor'])) $userDataToUpdate['role_id'] = $validatedData['role_id_for_instructor'];
            if (!empty($validatedData['password'])) {
                $userDataToUpdate['password'] = Hash::make($validatedData['password']);
            }
            if (!empty($userDataToUpdate)) {
                $user->update($userDataToUpdate);
            }

            // 2. تحديث بيانات المدرس (فقط إذا تم إرسالها)
            $instructorDataToUpdate = [];
            if (isset($validatedData['instructor_no'])) $instructorDataToUpdate['instructor_no'] = $validatedData['instructor_no'];
            if (isset($validatedData['name'])) $instructorDataToUpdate['instructor_name'] = $validatedData['name']; // تحديث اسم المدرس
            if (array_key_exists('academic_degree', $validatedData)) $instructorDataToUpdate['academic_degree'] = $validatedData['academic_degree'];
            if (isset($validatedData['department_id'])) $instructorDataToUpdate['department_id'] = $validatedData['department_id'];
            if (array_key_exists('max_weekly_hours', $validatedData)) $instructorDataToUpdate['max_weekly_hours'] = $validatedData['max_weekly_hours'];
            // if (array_key_exists('office_location', $validatedData)) $instructorDataToUpdate['office_location'] = $validatedData['office_location'];
            // if (array_key_exists('office_hours', $validatedData)) $instructorDataToUpdate['office_hours'] = $validatedData['office_hours'];
            if (array_key_exists('availability_preferences', $validatedData)) $instructorDataToUpdate['availability_preferences'] = $validatedData['availability_preferences'];

            if (!empty($instructorDataToUpdate)) {
                $instructor->update($instructorDataToUpdate);
            }

            DB::commit();
            $instructor->load(['user.role', 'department']); // إعادة تحميل العلاقات
            return response()->json([
                'success' => true,
                'data' => $instructor,
                'message' => 'Instructor and user account updated successfully.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Instructor & User Update Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update instructor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified instructor and their associated user account (API).
     */
    public function apiDestroy(Instructor $instructor)
    {
        // (اختياري) التحقق من الارتباطات قبل الحذف
        // if ($instructor->sections()->exists() || $instructor->scheduleEntries()->exists()) {
        //     return response()->json(['success' => false, 'message' => 'Cannot delete: Instructor has assignments.'], 409);
        // }

        DB::beginTransaction();
        try {
            $user = $instructor->user;
            $instructor->delete(); // حذف المدرس
            if ($user) {
                // (اختياري) التحقق من الأدوار الأخرى للمستخدم قبل حذفه
                $user->delete(); // حذف المستخدم
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Instructor and associated user deleted successfully.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('API Instructor & User Deletion Failed for Instructor ID ' . $instructor->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete instructor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Handle the import of instructors from an Excel file.
     */
    public function apiImportExcel(Request $request)
    {
        // 1. التحقق من وجود الملف وصيغته (باستخدام Validator لـ API)
        $validator = Validator::make($request->all(), [
            'instructor_excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for uploaded file.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        $this->resetCounters(); // إعادة تعيين العدادات لكل عملية رفع

        try {
            $rows = Excel::toArray(new \stdClass(), $request->file('instructor_excel_file'))[0];

            if (count($rows) <= 1) { // إذا كان الملف فارغاً أو يحتوي على العناوين فقط
                return response()->json(['success' => false, 'message' => 'Uploaded Excel file is empty or contains only a header row.'], 400);
            }

            $header = array_map('strtolower', array_map('trim', array_shift($rows)));
            $instructorNoCol = $this->getColumnIndex($header, ['instructor_no', 'instructorno', 'رقم المدرس', 'الرقم الوظيفي']);
            $instructorNameCol = $this->getColumnIndex($header, ['instructor_name', 'instructorname', 'اسم المدرس', 'الاسم']);
            $departmentCol = $this->getColumnIndex($header, ['department_id', 'departmentid', 'department', 'القسم']);
            $emailCol = $this->getColumnIndex($header, ['email', 'البريد الالكتروني', 'الايميل']);
            $degreeCol = $this->getColumnIndex($header, ['academic_degree', 'academicdegree', 'degree', 'الدرجة العلمية']);
            $maxHoursCol = $this->getColumnIndex($header, ['max_weekly_hours', 'maxweeklyhours', 'ساعات النصاب']);

            if (is_null($instructorNoCol) || is_null($instructorNameCol) || is_null($departmentCol)) {
                $missing = []; /* ... (نفس منطق تحديد الأعمدة المفقودة) ... */
                if(is_null($instructorNoCol)) $missing[] = "'instructor_no'";
                if(is_null($instructorNameCol)) $missing[] = "'instructor_name'";
                if(is_null($departmentCol)) $missing[] = "'department_id' or 'department_name'";
                return response()->json(['success' => false, 'message' => 'Excel file is missing required columns: ' . implode(', ', $missing)], 400);
            }

            $currentRowNumber = 1;
            // لا نحتاج Transaction هنا لكل صف، يمكن عمل Transaction كبير حول الحلقة كلها إذا أردت
            // ولكن إذا فشل صف واحد، قد ترغب في تجاهله فقط ومتابعة الباقي

            foreach ($rows as $row) {
                $currentRowNumber++;
                $rowData = []; foreach ($header as $index => $colName) { $rowData[$colName] = $row[$index] ?? null; }

                if (count(array_filter($row)) == 0) { $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (empty)."; $this->skippedCount++; continue; }

                $instructorNo = trim($rowData[$header[$instructorNoCol]] ?? null);
                if (empty($instructorNo)) { $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (missing instructor_no)."; $this->skippedCount++; continue; }

                if (in_array($instructorNo, $this->processedInstructorNos)) { $this->skippedDetails[] = "Row {$currentRowNumber}: Skipped (duplicate instructor_no '{$instructorNo}' within file)."; $this->skippedCount++; continue; }
                $this->processedInstructorNos[] = $instructorNo;

                $instructorNameInput = trim($rowData[$header[$instructorNameCol]] ?? null);
                $departmentIdentifier = trim($rowData[$header[$departmentCol]] ?? null);
                $emailInput = isset($emailCol) ? trim($rowData[$header[$emailCol]] ?? null) : null;
                $degreeInputFromFile = isset($degreeCol) ? trim($rowData[$header[$degreeCol]] ?? null) : null;
                $maxHoursInput = isset($maxHoursCol) ? trim($rowData[$header[$maxHoursCol]] ?? null) : null;

                if (empty($instructorNameInput) || empty($departmentIdentifier)) { $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (missing name or department)."; $this->skippedCount++; continue; }

                $name = $instructorNameInput; $degreeFromName = null;
                if (preg_match('/\(.+\)/u', $instructorNameInput, $matches)) { $degreeFromName = trim($matches[0], '()'); $name = trim(str_replace($matches[0], '', $instructorNameInput)); }
                $academicDegree = $degreeFromName ?: $degreeInputFromFile;

                $department = $this->findDepartment($departmentIdentifier);
                if (!$department) { $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (Department '{$departmentIdentifier}' not found)."; $this->skippedCount++; continue; }

                $email = $emailInput;
                if (empty($email)) { $baseEmailName = 'momen' . preg_replace('/[^A-Za-z0-9]/', '', $instructorNo); $email = $this->generateUniqueEmail($baseEmailName, '@ptc.edu', $instructorNo); }
                else { $emailValidator = Validator::make(['email' => $email], ['email' => 'email']); if ($emailValidator->fails()) { $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Skipped (Invalid email '{$email}')."; $this->skippedCount++; continue; } }

                // --- التحديث أو الإنشاء (داخل Transaction لكل مدرس لضمان سلامة User و Instructor معاً) ---
                DB::transaction(function () use ($instructorNo, $name, $email, $academicDegree, $department, $maxHoursInput, $currentRowNumber) {
                    $instructor = Instructor::where('instructor_no', $instructorNo)->first();
                    $defaultRoleName = 'instructor'; // الدور الافتراضي للمدرس الجديد

                    if ($instructor) { // تحديث
                        $user = $instructor->user;
                        if (!$user) {
                            Log::warning("Instructor ID {$instructor->id} (EmpNo: {$instructorNo}) exists without a user. Creating user for update.");
                            $user = $this->createUserForInstructor($name, $email, $defaultRoleName);
                            $instructor->user_id = $user->id; // ربط المستخدم الجديد
                        } else {
                            if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                                $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Update Skipped (Email '{$email}' already taken).";
                                $this->skippedCount++;
                                DB::rollBack(); // التراجع عن أي تغييرات محتملة في هذا الـ transaction
                                return; // الخروج من الـ closure الخاص بالـ transaction
                            }
                            $user->name = $name;
                            $user->email = $email;
                            // لا نغير كلمة المرور أو الدور عند التحديث من الإكسل عادةً إلا إذا كان هناك عمود مخصص
                            $user->save();
                        }
                        $instructor->instructor_name = $name;
                        $instructor->academic_degree = $academicDegree;
                        $instructor->department_id = $department->id;
                        if (!is_null($maxHoursInput) && is_numeric($maxHoursInput)) $instructor->max_weekly_hours = (int)$maxHoursInput;
                        $instructor->save();
                        $this->updatedCount++;
                    } else { // إنشاء جديد
                        if (User::where('email', $email)->exists()) {
                            $this->skippedDetails[] = "Row {$currentRowNumber} (EmpNo:{$instructorNo}): Create Skipped (Email '{$email}' already exists for new user).";
                            $this->skippedCount++;
                            DB::rollBack(); return;
                        }
                        $user = $this->createUserForInstructor($name, $email, $defaultRoleName);
                        Instructor::create([
                            'user_id' => $user->id, 'instructor_no' => $instructorNo, 'instructor_name' => $name,
                            'academic_degree' => $academicDegree, 'department_id' => $department->id,
                            'max_weekly_hours' => (!is_null($maxHoursInput) && is_numeric($maxHoursInput)) ? (int)$maxHoursInput : null,
                        ]);
                        $this->createdCount++;
                    }
                }); // نهاية الـ Transaction لكل مدرس
                // -----------------------------------------------------------------------------------

            } // نهاية حلقة الصفوف

            $summary = [];
            if ($this->createdCount > 0) $summary['new_instructors_created'] = $this->createdCount;
            if ($this->updatedCount > 0) $summary['existing_instructors_updated'] = $this->updatedCount;
            if ($this->skippedCount > 0) $summary['rows_skipped'] = $this->skippedCount;

            if (empty($summary) && empty(array_filter($this->skippedDetails))) {
                 return response()->json(['success' => true, 'message' => 'Excel file processed. No new data imported or all data already matched/skipped.', 'summary' => $summary, 'skipped_details' => $this->skippedDetails], 200);
            }

            return response()->json([
                'success' => true,
                'message' => "Instructors import processed.",
                'summary' => $summary,
                'skipped_details' => $this->skippedDetails
            ], 200);

        } catch (Exception $e) {
            Log::error('API Instructors Excel Import Failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            // إرجاع أي تفاصيل تم تجميعها حتى الآن
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during Excel import: ' . $e->getMessage(),
                'skipped_details' => $this->skippedDetails
            ], 500);
        }
    }


    /**
     * Display a listing of the instructors (API).
     */
    // public function apiIndex(Request $request)
    // {
    //     try {
    //         $query = Instructor::with(['user:id,name,email', 'department:id,department_name']);

    //         // (اختياري) فلترة
    //         if ($request->has('department_id')) {
    //             $query->where('department_id', $request->department_id);
    //         }

    //         // --- الخيار 1: جلب كل المدرسين (الحالة الحالية) ---
    //         $instructors = $query->latest('id')->get();

    //         /*
    //         $perPage = $request->query('per_page', 15);
    //         $instructorsPaginated = $query->latest('id')
    //                                       ->paginate($perPage);

    //         return response()->json([
    //             'success' => true,
    //             'data' => $instructorsPaginated->items(),
    //             'pagination' => [
    //                 'total' => $instructorsPaginated->total(),
    //                 'per_page' => $instructorsPaginated->perPage(),
    //                 'current_page' => $instructorsPaginated->currentPage(),
    //                 'last_page' => $instructorsPaginated->lastPage(),
    //             ]
    //         ], 200);
    //         */

    //         return response()->json(['success' => true, 'data' => $instructors], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Error fetching instructors: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server Error'], 500);
    //     }
    // }

    // /**
    //  * Store a newly created instructor from API request.
    //  */
    // public function apiStore(Request $request)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'user_id' => 'required|integer|exists:users,id|unique:instructors,user_id',
    //         'instructor_no' => 'required|string|max:20|unique:instructors,instructor_no',
    //         'instructor_name' => 'required|string|max:255',
    //         'academic_degree' => 'nullable|string|max:100',
    //         'department_id' => 'required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'nullable|integer|min:0|max:100',
    //         // 'office_location' => 'nullable|string|max:255',
    //         // 'office_hours' => 'nullable|string|max:255',
    //         'availability_preferences' => 'nullable|string',
    //     ]);

    //     // التحقق من دور المستخدم
    //     $user = User::find($request->user_id);
    //     if (!$user || !$user->hasRole(['instructor', 'hod', 'admin'])) {
    //         return response()->json(['success' => false, 'message' => 'The selected user does not have a valid role.'], 422);
    //     }

    //     // 2. Add to Database
    //     try {
    //         $instructor = Instructor::create($validatedData);
    //         $instructor->load(['user:id,name,email', 'department:id,department_name']); // تحميل العلاقات
    //         // 3. Return Success JSON Response
    //         return response()->json([
    //             'success' => true,
    //             'data' => $instructor,
    //             'message' => 'Instructor created successfully.'
    //         ], 201);
    //     } catch (Exception $e) {
    //         Log::error('API Instructor Creation Failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to create instructor.'], 500);
    //     }
    // }

    // /**
    //  * Display the specified instructor (API).
    //  */
    // public function apiShow(Instructor $instructor)
    // {
    //     $instructor->load(['user:id,name,email', 'department:id,department_name']);
    //     return response()->json([
    //         'success' => true,
    //         'data' => $instructor,
    //     ], 200);
    // }

    // /**
    //  * Update the specified instructor from API request.
    //  */
    // public function apiUpdate(Request $request, Instructor $instructor)
    // {
    //     // 1. Validation
    //     $validatedData = $request->validate([
    //         'instructor_no' => [
    //             'sometimes',
    //             'required',
    //             'string',
    //             'max:20',
    //             'unique:instructors,instructor_no,' . $instructor->id,
    //         ],
    //         'instructor_name' => 'sometimes|required|string|max:255',
    //         'academic_degree' => 'sometimes|nullable|string|max:100',
    //         'department_id' => 'sometimes|required|integer|exists:departments,id',
    //         'max_weekly_hours' => 'sometimes|nullable|integer|min:0|max:100',
    //         // 'office_location' => 'sometimes|nullable|string|max:255',
    //         // 'office_hours' => 'sometimes|nullable|string|max:255',
    //         'availability_preferences' => 'sometimes|nullable|string',
    //     ]);

    //     // 2. Update Database
    //     try {
    //         $instructor->update($validatedData);
    //         $instructor->load(['user:id,name,email', 'department:id,department_name']); // تحميل العلاقات بعد التحديث
    //         // 3. Return Success JSON Response
    //         return response()->json([
    //             'success' => true,
    //             'data' => $instructor,
    //             'message' => 'Instructor updated successfully.'
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Instructor Update Failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to update instructor.'], 500);
    //     }
    // }

    // /**
    //  * Remove the specified instructor from API request.
    //  * حذف مدرس محدد قادم من طلب API
    //  */
    // public function apiDestroy(Instructor $instructor)
    // {
    //     // (اختياري) التحقق من السجلات المرتبطة
    //     // if ($instructor->scheduleEntries()->exists()) { ... }

    //     // 1. Delete from Database
    //     try {
    //         $instructor->delete(); // حذف سجل المدرس فقط، المستخدم المرتبط يبقى
    //         // 2. Return Success JSON Response
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Instructor record deleted successfully.'
    //         ], 200);
    //     } catch (Exception $e) {
    //         Log::error('API Instructor Deletion Failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Failed to delete instructor record.'], 500);
    //     }
    // }
} // نهاية الكلاس
