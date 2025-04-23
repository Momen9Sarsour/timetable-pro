<?php

namespace App\Imports;

use App\Models\Subject;
use Exception;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // للتعامل مع صف العناوين
use Maatwebsite\Excel\Concerns\WithValidation; // لتطبيق التحقق
use Maatwebsite\Excel\Concerns\Importable;     // لاستخدام trait
use Illuminate\Validation\Rule;             // لاستخدام rules متقدمة
use Illuminate\Support\Facades\Log;        // لتسجيل الأخطاء (اختياري)
use Maatwebsite\Excel\Concerns\SkipsEmptyRows; // *** لتجاهل الصفوف الفارغة ***
use Maatwebsite\Excel\Concerns\OnEachRow; // *** استخدام OnEachRow ***
use Illuminate\Support\Facades\Validator; // *** لاستخدام Validator يدوياً ***
use Maatwebsite\Excel\Row; // *** لاستخدام Row object ***


// class SubjectsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
class SubjectsImport implements OnEachRow, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row بيانات الصف الحالي (المفاتيح هي عناوين الأعمدة snake_case)
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    // public function model(array $row)
    // {
    //     // مكتبة Laravel Excel تحول العناوين تلقائياً لـ snake_case

    //     // التحقق الأساسي من وجود الأعمدة المطلوبة (قد يكون غير ضروري بسبب Validation)
    //     //     $requiredKeys = [
    //     //         'subject_no',
    //     //         'subject_name',
    //     //         'subject_load',
    //     //         'theoretical_hours',
    //     //         'practical_hours',
    //     //         'subject_type_id',
    //     //         'subject_category_id',
    //     //         'department_id'
    //     //     ];
    //     //     foreach ($requiredKeys as $key) {
    //     //         // التحقق إذا كان المفتاح موجوداً والقيمة ليست فارغة تماماً
    //     //         if (!isset($row[$key]) || $row[$key] === null || $row[$key] === '') {
    //     //             // هذا الشرط قد يساعد في تجاهل الصفوف التي تبدو شبه فارغة أو مدمجة
    //     //             // ولكن الاعتماد على Validation أفضل
    //     //             Log::warning("Skipping row in Subject import due to missing or empty required column: {$key}", $row);
    //     //             return null; // تجاهل الصف
    //     //         }
    //     //     }


    //     //     // إنشاء وحفظ موديل Subject جديد
    //     //     return new Subject([
    //     //         'subject_no' => $row['subject_no'],
    //     //         'subject_name' => $row['subject_name'],
    //     //         'subject_load' => $row['subject_load'],
    //     //         'theoretical_hours' => $row['theoretical_hours'],
    //     //         'practical_hours' => $row['practical_hours'],
    //     //         'subject_type_id' => $row['subject_type_id'],
    //     //         'subject_category_id' => $row['subject_category_id'],
    //     //         'department_id' => $row['department_id'],
    //     //     ]);
    //     // }

    //     // /**
    //     //  * قواعد التحقق لكل صف.
    //     //  *
    //     //  * @return array
    //     //  */
    //     // public function rules(): array
    //     // {
    //     //     // المفاتيح هنا يجب أن تكون snake_case لتطابق ما تقرأه المكتبة
    //     //     return [
    //     //         'subject_no' => ['required', 'string', 'max:20', Rule::unique('subjects', 'subject_no')],
    //     //         'subject_name' => ['required', 'string', 'max:255'],
    //     //         'subject_load' => ['required', 'integer', 'min:0'],
    //     //         'theoretical_hours' => ['required', 'integer', 'min:0'],
    //     //         'practical_hours' => ['required', 'integer', 'min:0'],
    //     //         'subject_type_id' => ['required', 'integer', 'exists:subjects_types,id'],
    //     //         'subject_category_id' => ['required', 'integer', 'exists:subjects_categories,id'],
    //     //         'department_id' => ['required', 'integer', 'exists:departments,id'],
    //     //     ];
    // }
        // عداد لتتبع الأخطاء (اختياري)
        public $errors = [];
        public $importedRowCount = 0;

        /**
         * يتم استدعاؤها لكل صف في الملف.
         * @param Row $row كائن يمثل الصف الحالي
         */
        public function onRow(Row $row)
        {
            // الحصول على بيانات الصف كمصفوفة (المفاتيح snake_case)
            $rowData = $row->toArray();
            $rowIndex = $row->getIndex(); // رقم الصف في الملف (يبدأ من 1)

            // --- خطوة 1: التحقق الأساسي لتجاهل الصفوف غير المرغوبة ---
            // تحقق إذا كان الصف فارغاً تماماً أو شبه فارغ (لا يحتوي على رقم مادة واسم)
            if (empty($rowData['subject_no']) && empty($rowData['subject_name'])) {
                // قد يكون صفاً فارغاً (فاصل فصول) أو صف عنوان تخصص مدمج
                 Log::info("Skipping empty or header row at index: {$rowIndex}");
                return; // تجاهل هذا الصف وانتقل للتالي
            }

            // تحقق إذا كانت الأعمدة الرقمية الأساسية مفقودة أو غير رقمية (قد يدل على صف عنوان)
            $numericColumns = ['subject_load', 'theoretical_hours', 'practical_hours', 'subject_type_id', 'subject_category_id', 'department_id'];
            $isLikelyHeaderOrEmpty = false;
            foreach ($numericColumns as $col) {
                if (!isset($rowData[$col]) || !is_numeric($rowData[$col])) {
                    $isLikelyHeaderOrEmpty = true;
                    break;
                }
            }
            if ($isLikelyHeaderOrEmpty) {
                Log::info("Skipping potentially invalid data or header row at index: {$rowIndex}", $rowData);
                 return; // تجاهل هذا الصف
            }

            // --- خطوة 2: التحقق اليدوي (Validation) للصف الحالي ---
            $validator = Validator::make($rowData, [
                'subject_no' => ['required', 'string', 'max:20', Rule::unique('subjects', 'subject_no')],
                'subject_name' => ['required', 'string', 'max:255'],
                'subject_load' => ['required', 'integer', 'min:0'],
                'theoretical_hours' => ['required', 'integer', 'min:0'],
                'practical_hours' => ['required', 'integer', 'min:0'],
                'subject_type_id' => ['required', 'integer', 'exists:subjects_types,id'],
                'subject_category_id' => ['required', 'integer', 'exists:subjects_categories,id'],
                'department_id' => ['required', 'integer', 'exists:departments,id'],
            ], $this->customValidationMessages(), $this->customValidationAttributes());

            if ($validator->fails()) {
                // تسجيل الخطأ مع رقم الصف
                $this->errors[$rowIndex] = $validator->errors()->all();
                Log::warning("Validation failed for row {$rowIndex}: ", $validator->errors()->toArray());
                return; // تجاهل الصف الذي فشل التحقق منه
            }

            // --- خطوة 3: إنشاء الموديل (إذا نجح التحقق) ---
            try {
                Subject::create([
                    'subject_no' => $rowData['subject_no'],
                    'subject_name' => $rowData['subject_name'],
                    'subject_load' => $rowData['subject_load'],
                    'theoretical_hours' => $rowData['theoretical_hours'],
                    'practical_hours' => $rowData['practical_hours'],
                    'subject_type_id' => $rowData['subject_type_id'],
                    'subject_category_id' => $rowData['subject_category_id'],
                    'department_id' => $rowData['department_id'],
                ]);
                $this->importedRowCount++; // زيادة عداد الصفوف المستوردة بنجاح
            } catch (Exception $e) {
                 // تسجيل خطأ في إنشاء الموديل (غير متوقع إذا نجح التحقق)
                 $this->errors[$rowIndex] = ['Database error: ' . $e->getMessage()];
                 Log::error("Failed to create Subject from row {$rowIndex}: " . $e->getMessage(), $rowData);
            }
        }

    /**
     * رسائل خطأ مخصصة (اختياري).
     *
     * @return array
     */
    public function customValidationMessages(): array
    {
        return [
            'subject_no.required' => 'Subject Code (subject_no) is required.',
            'subject_no.unique' => 'Subject Code (:input) already exists.',
            'subject_type_id.exists' => 'Subject Type ID (:input) not found.',
            'subject_category_id.exists' => 'Subject Category ID (:input) not found.',
            'department_id.exists' => 'Department ID (:input) not found.',
            '*.integer' => 'The :attribute must be a whole number.',
            '*.min' => 'The :attribute must be at least :min.',
            '*.required' => 'The :attribute field is required.', // رسالة عامة للحقول المطلوبة
        ];
    }

    /**
     * أسماء مخصصة للحقول في رسائل الخطأ (اختياري).
     *
     * @return array
     */
    public function customValidationAttributes(): array
    {
        // المفاتيح snake_case
        return [
            'subject_no' => 'Subject Code',
            'subject_name' => 'Subject Name',
            'subject_load' => 'Credit Hours',
            'theoretical_hours' => 'Theoretical Hours',
            'practical_hours' => 'Practical Hours',
            'subject_type_id' => 'Subject Type ID',
            'subject_category_id' => 'Subject Category ID',
            'department_id' => 'Department ID',
        ];
    }

    // يمكنك إضافة دالة للحصول على الأخطاء بعد الانتهاء من الـ import
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedRowCount(): int
    {
        return $this->importedRowCount;
    }

    /**
     * يمكنك استخدام هذا لتحديد الصف الذي تبدأ منه القراءة (افتراضياً 1)
     * مفيد إذا كان لديك صفوف إضافية قبل العناوين
     */
    public function headingRow(): int
    {
        return 1; // افترض أن العناوين في الصف الأول
    }
}
