<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanSubject;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PlanSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // PlanSubject::truncate(); // حذف الروابط القديمة
        DB::table('plan_subjects')->delete(); // إذا استخدمت الـ DB Facade

        $faker = Faker::create();

        $plans = Plan::where('is_active', true)->get(); // جلب الخطط الفعالة فقط
        $subjects = Subject::all();

        if ($plans->isEmpty() || $subjects->isEmpty()) {
            $this->command->warn('No active plans or subjects found. Skipping PlanSubjectSeeder.');
            return;
        }

        $subjectsPool = $subjects->pluck('id')->toArray(); // مجموعة IDs المواد

        foreach ($plans as $plan) {
            // تحديد عدد المستويات والفصول (مثال: دبلوم سنتين = 4 فصول، بكالوريوس 4 سنوات = 8 فصول)
            $levels = ($plan->plan_hours < 100) ? 2 : 4; // تحديد عدد السنوات بناءً على الساعات (تقريبي)
            $semestersPerLevel = 2; // افترض فصلين دراسيين لكل مستوى (سنة)

            $addedSubjects = []; // لتتبع المواد المضافة لنفس الخطة لتجنب التكرار

            for ($level = 1; $level <= $levels; $level++) {
                for ($semester = 1; $semester <= $semestersPerLevel; $semester++) {
                    // تحديد عدد المواد في هذا المستوى/الفصل (مثال: 5-7 مواد)
                    $subjectsCount = $faker->numberBetween(5, 7);
                    $subjectsToAdd = $faker->randomElements($subjectsPool, $subjectsCount);

                    foreach ($subjectsToAdd as $subjectId) {
                         $uniqueKey = $plan->id . '-' . $subjectId . '-' . $level . '-' . $semester;
                         if (!isset($addedSubjects[$uniqueKey])) { // التأكد من عدم إضافة نفس المادة بنفس المستوى/الفصل
                             // استخدام DB::table لتجنب مشاكل الـ Mass Assignment أو الـ Unique Constraint داخل اللوب
                             DB::table('plan_subjects')->insert([
                                'plan_id' => $plan->id,
                                'subject_id' => $subjectId,
                                'plan_level' => $level,
                                'plan_semester' => $semester,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $addedSubjects[$uniqueKey] = true; // تسجيل أنه تم الإضافة
                         }
                    }
                }
            }
        }
    }
}
