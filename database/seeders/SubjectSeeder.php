<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Subject;
use App\Models\SubjectCategory;
use App\Models\SubjectType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Subject::truncate();
        DB::table('subjects')->delete(); // إذا استخدمت الـ DB Facade

        $faker = Faker::create();

        $types = SubjectType::pluck('id')->toArray();
        $categories = SubjectCategory::pluck('id')->toArray();
        $departments = Department::pluck('id')->toArray();

         if (empty($types) || empty($categories) || empty($departments)) {
             $this->command->warn('Missing Subject Types, Categories, or Departments. Skipping SubjectSeeder.');
             return;
         }

        // مثال لإنشاء 50 مادة
        $coursePrefixes = ['CS', 'MATH', 'PHYS', 'ENGL', 'ARAB', 'BUS', 'EE', 'ME'];
        for ($i = 1; $i <= 50; $i++) {
             $subjectNo = $faker->randomElement($coursePrefixes) . $faker->numberBetween(101, 499);
             $subjectName = $faker->catchPhrase() . ' ' . $faker->randomElement(['I', 'II', 'Introduction', 'Advanced Topics']); // اسم مادة وهمي
             $category = SubjectCategory::find($faker->randomElement($categories));

             // تحديد الساعات بناءً على الفئة (مثال)
             $theoHours = 0;
             $pracHours = 0;
             $load = 3; // افتراضي

             if ($category->subject_category_name == 'نظري') {
                 $theoHours = $faker->randomElement([2, 3]);
                 $pracHours = 0;
                 $load = $theoHours;
             } elseif ($category->subject_category_name == 'عملي') {
                  $theoHours = 0;
                  $pracHours = $faker->randomElement([1, 2, 3]); // ساعة العملي قد تحسب بـ 1 أو أكثر
                  $load = $pracHours; // أو حساب مختلف للساعات المعتمدة للعملي
             } elseif ($category->subject_category_name == 'نظري وعملي') {
                  $theoHours = $faker->randomElement([2, 3]);
                  $pracHours = $faker->randomElement([1, 2]);
                  $load = $theoHours + 1; // مثال لحساب الساعات المعتمدة (عدله حسب نظام الكلية)
             }


            Subject::create([
                'subject_no' => $subjectNo,
                'subject_name' => $subjectName,
                'subject_load' => $load,
                'theoretical_hours' => $theoHours,
                'practical_hours' => $pracHours,
                'subject_type_id' => $faker->randomElement($types),
                'subject_category_id' => $category->id,
                'department_id' => $faker->randomElement($departments), // القسم المسؤول عن المادة
            ]);
        }
    }
}
