<?php

// namespace App\Imports;

// use App\Models\PlanSubject;
// use Maatwebsite\Excel\Concerns\ToModel;

// class PlanSubjectsImport implements ToModel
// {
//     /**
//     * @param array $row
//     *
//     * @return \Illuminate\Database\Eloquent\Model|null
//     */
//     public function model(array $row)
//     {
//         return new PlanSubject([
//             //
//         ]);
//     }
// }


namespace App\Imports;

use App\Models\Plan;
use App\Models\PlanSubject;
use App\Models\Subject;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError; // لتجاهل الأخطاء ومتابعة باقي الصفوف
use Maatwebsite\Excel\Concerns\SkipsErrors; // لتجميع الأخطاء
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Throwable; // لالتقاط كل أنواع الأخطاء

class PlanSubjectsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError
{
    use Importable, SkipsErrors; // استخدام SkipsErrors لتجميع الأخطاء

    private Plan $currentPlan; // الخطة الحالية التي نعمل عليها
    private $createdCount = 0;
    private $updatedCount = 0; // سنستخدمه إذا كان هناك حقول للتحديث في plan_subjects
    private $skippedCount = 0;
    private $alreadyExistedCount = 0;
    private $invalidPlanCount = 0;
    private $processedPlanSubjectKeys = []; // لتتبع التكرار داخل الملف

    // استقبال الخطة الحالية في الـ constructor
    public function __construct(Plan $plan)
    {
        $this->currentPlan = $plan;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // تحويل العناوين لـ snake_case (المكتبة تقوم بهذا) والبحث عن القيم
        $planIdentifier = trim($row['plan_id'] ?? $row['planid'] ?? $row['plan_name'] ?? $row['planname'] ?? null); // اسم الخطة أو رقمها
        $levelInput = trim($row['plan_level'] ?? $row['planlevel'] ?? $row['level'] ?? null);
        $semesterInput = trim($row['plan_semester'] ?? $row['plansemester'] ?? $row['semester'] ?? null);
        $subjectIdentifier = trim($row['subject_id'] ?? $row['subjectid'] ?? $row['subject_no'] ?? $row['subjectno'] ?? $row['subject_name'] ?? $row['subjectname'] ?? null);

        // 1. تجاهل الصفوف الفارغة تماماً
        if (empty($planIdentifier) || empty($subjectIdentifier) || empty($levelInput) || empty($semesterInput)) {
            Log::info('PlanSubjectsImport: Skipping row due to missing core data.', $row);
            $this->skippedCount++;
            return null;
        }

        // 2. التحقق من تطابق الخطة مع الخطة الحالية
        $planIdInFile = null;
        if (is_numeric($planIdentifier)) {
            $planIdInFile = (int) $planIdentifier;
        } else {
            // محاولة البحث بالاسم (مع تجاهل حالة الأحرف والسنة إذا كانت مدمجة)
            // نفترض أن اسم الخطة في الملف قد يكون "Plan Name Only" أو "Plan Name - YYYY"
            $planNameFromFile = preg_replace('/-\s*\d{4}$/', '', $planIdentifier); // إزالة "- YYYY"
            $foundPlan = Plan::whereRaw('LOWER(REPLACE(plan_name, " ", "")) LIKE ?', ['%' . strtolower(str_replace(' ', '', $planNameFromFile)) . '%'])
                            ->orWhereRaw('LOWER(REPLACE(plan_no, " ", "")) LIKE ?', ['%' . strtolower(str_replace(' ', '', $planIdentifier)) . '%'])
                            ->first();
            if ($foundPlan) {
                $planIdInFile = $foundPlan->id;
            }
        }

        if (is_null($planIdInFile) || $planIdInFile !== $this->currentPlan->id) {
            Log::warning("PlanSubjectsImport: Row skipped. Plan '{$planIdentifier}' in file does not match current plan ID {$this->currentPlan->id}.", $row);
            $this->invalidPlanCount++;
            return null;
        }

        // 3. معالجة وتحويل المستوى والفصل
        $level = $this->normalizeLevelOrSemester($levelInput);
        $semester = $this->normalizeLevelOrSemester($semesterInput);

        if (is_null($level) || is_null($semester)) {
            Log::warning("PlanSubjectsImport: Row skipped. Invalid level '{$levelInput}' or semester '{$semesterInput}'.", $row);
            $this->skippedCount++;
            return null;
        }

        // 4. معالجة وتحويل معرّف المادة
        $subject = $this->findSubject($subjectIdentifier);
        if (!$subject) {
            Log::warning("PlanSubjectsImport: Row skipped. Subject '{$subjectIdentifier}' not found.", $row);
            $this->skippedCount++;
            return null;
        }

        // 5. التحقق من التكرار داخل الملف نفسه لهذه الخطة والمادة والمستوى والفصل
        $uniqueKey = $this->currentPlan->id . '-' . $subject->id . '-' . $level . '-' . $semester;
        if (isset($this->processedPlanSubjectKeys[$uniqueKey])) {
            Log::info("PlanSubjectsImport: Skipping duplicate entry in file for key: {$uniqueKey}", $row);
            $this->skippedCount++;
            return null;
        }
        $this->processedPlanSubjectKeys[$uniqueKey] = true;


        // 6. التحقق من وجود الربط مسبقاً في قاعدة البيانات
        $existingPlanSubject = PlanSubject::where('plan_id', $this->currentPlan->id)
                                          ->where('subject_id', $subject->id)
                                          ->where('plan_level', $level)
                                          ->where('plan_semester', $semester)
                                          ->first();

        if ($existingPlanSubject) {
            // المادة مضافة بالفعل، لا نقوم بالتحديث لأن جدول الربط بسيط
            Log::info("PlanSubjectsImport: Subject ID {$subject->id} already exists in Plan ID {$this->currentPlan->id} for L{$level}S{$semester}. No update needed.");
            $this->alreadyExistedCount++;
            return null; // لا ترجع موديل إذا لم يكن هناك تحديث
        } else {
            // إنشاء سجل ربط جديد
            $this->createdCount++;
            Log::info("PlanSubjectsImport: Creating new PlanSubject for Plan ID {$this->currentPlan->id}, Subject ID {$subject->id}, L{$level}S{$semester}.");
            return new PlanSubject([
                'plan_id'       => $this->currentPlan->id,
                'subject_id'    => $subject->id,
                'plan_level'    => $level,
                'plan_semester' => $semester,
            ]);
        }
    }

    /**
     * Helper function to normalize level/semester input.
     */
    private function normalizeLevelOrSemester($input)
    {
        if (is_numeric($input)) {
            return (int) $input;
        }
        $inputLower = strtolower(trim($input));
        $map = [
            'أولى' => 1, 'اولى' => 1, 'first' => 1, '1st' => 1, 'one' => 1, '1' => 1,
            'ثانية' => 2, 'ثانيه' => 2, 'second' => 2, '2nd' => 2, 'two' => 2, '2' => 2,
            'ثالثة' => 3, 'ثالثه' => 3, 'third' => 3, '3rd' => 3, 'three' => 3, '3' => 3,
            'رابعة' => 4, 'رابعه' => 4, 'fourth' => 4, '4th' => 4, 'four' => 4, '4' => 4,
            // أضف المزيد حسب الحاجة للفصول والمستويات
            'فصل أول' => 1, 'فصل اول' => 1,
            'فصل ثاني' => 2, 'فصل ثاني' => 2,
            'صيفي' => 3, 'summer' => 3,
        ];
        return $map[$inputLower] ?? (is_numeric($inputLower) ? (int)$inputLower : null);
    }

    /**
     * Helper function to find subject by ID, No, or Name.
     */
    private function findSubject($identifier)
    {
        if (is_numeric($identifier)) {
            return Subject::find($identifier);
        }
        // البحث بالكود (رقم المادة) - تجاهل حالة الأحرف
        $subject = Subject::whereRaw('LOWER(subject_no) = ?', [strtolower($identifier)])->first();
        if ($subject) {
            return $subject;
        }
        // البحث بالاسم - تجاهل حالة الأحرف والهمزات والفراغات الزائدة
        $normalizedIdentifier = $this->normalizeArabicString($identifier);
        return Subject::whereRaw('REPLACE(LOWER(subject_name), " ", "") LIKE ?', ['%' . str_replace(' ', '', $normalizedIdentifier) . '%'])
                      ->orWhereRaw('LOWER(subject_name) LIKE ?', ['%' . $normalizedIdentifier . '%']) // بحث أوسع قليلاً
                      ->first(); // قد تحتاج لمنطق أدق إذا كان هناك أسماء متشابهة جداً
    }

    /**
     * Helper function to normalize Arabic string (remove Alif variants, normalize spaces).
     */
    private function normalizeArabicString($string)
    {
        $string = preg_replace('/[أإآ]/u', 'ا', $string); // توحيد الألفات
        $string = preg_replace('/\s+/u', ' ', trim($string)); // إزالة الفراغات الزائدة
        return strtolower($string);
    }


    /**
     * Validation rules for each row.
     */
    public function rules(): array
    {
        // المفاتيح يجب أن تطابق عناوين الأعمدة في ملف Excel (بعد تحويلها لـ snake_case بواسطة المكتبة)
        // هذه القواعد تطبق على البيانات الخام قبل أي تحويل في دالة model()
        return [
            'plan_id' => ['required'], // أو planid, plan_name, planname (حسب ما سيقرأه كـ heading)
            'plan_level' => ['required'],
            'plan_semester' => ['required'],
            'subject_id' => ['required'], // أو subjectid, subject_no, subject_name

            // يمكنك إضافة rules أكثر تفصيلاً هنا إذا أردت
            // '*.plan_level' => 'integer|min:1|max:6',
            // '*.plan_semester' => 'integer|min:1|max:3',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function customValidationMessages(): array
    {
        return [
            'plan_id.required' => 'Plan identifier (ID or Name) is required in column B.',
            'plan_level.required' => 'Plan Level is required in column C.',
            'plan_semester.required' => 'Plan Semester is required in column D.',
            'subject_id.required' => 'Subject identifier (ID, Code, or Name) is required in column E.',
        ];
    }

    // Getters for counts
    public function getCreatedCount(): int { return $this->createdCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getSkippedCount(): int { return $this->skippedCount; }
    public function getAlreadyExistedCount(): int { return $this->alreadyExistedCount; }
    public function getInvalidPlanCount(): int { return $this->invalidPlanCount; }
}
