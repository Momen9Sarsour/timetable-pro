<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            // الأولوية الأولى
            RoleSeeder::class,
            DepartmentSeeder::class,
            RoomTypeSeeder::class,
            SubjectTypeSeeder::class,
            SubjectCategorySeeder::class,
            TimeslotSeeder::class,

            // يمكنك إضافة الـ Seeders التالية هنا لاحقاً عندما نكتبها
            UserSeeder::class,      // يجب أن يكون بعد RoleSeeder
            InstructorSeeder::class,// يجب أن يكون بعد UserSeeder و DepartmentSeeder
            RoomSeeder::class,      // يجب أن يكون بعد RoomTypeSeeder
            SubjectSeeder::class,   // يجب أن يكون بعد SubjectType, SubjectCategory, Department Seeders
            PlanSeeder::class,      // يجب أن يكون بعد DepartmentSeeder
            PlanSubjectSeeder::class,// يجب أن يكون بعد PlanSeeder و SubjectSeeder

            // يمكنك إضافة Seeders اختيارية لاحقاً هنا
            // PlanExpectedCountSeeder::class,
            // SectionSeeder::class,
            // GeneratedScheduleSeeder::class,
        ]);
    }
}
