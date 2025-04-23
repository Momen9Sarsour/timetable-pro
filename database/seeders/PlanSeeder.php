<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Plan::truncate();
        DB::table('plans')->delete(); // إذا استخدمت الـ DB Facade

        $faker = Faker::create();
        $departments = Department::all();

        if ($departments->isEmpty()) {
            $this->command->warn('No departments found. Skipping PlanSeeder.');
            return;
        }

        // إنشاء خطة أو اثنتين لكل قسم
        foreach ($departments as $department) {
            for ($i = 1; $i <= $faker->numberBetween(1, 2); $i++) {
                $year = $faker->randomElement([2022, 2023, 2024]);
                $planName = $department->department_name . ' Plan - ' . $year;
                $planNo = $department->department_no . $year . 'P' . $i;

                Plan::create([
                    'plan_no' => $planNo,
                    'plan_name' => $planName,
                    'year' => $year,
                    'plan_hours' => $faker->randomElement([68, 72, 120, 135]), // ساعات معتمدة إجمالية للخطة
                    'is_active' => $faker->boolean(80), // 80% active
                    'department_id' => $department->id,
                ]);
            }
        }
    }
}
