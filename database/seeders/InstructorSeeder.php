<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Instructor::truncate(); // حذف المدرسين القدامى
        DB::table('instructors')->delete(); // إذا استخدمت الـ DB Facade

        $faker = Faker::create();

        // جلب المستخدمين الذين يمكن أن يكونوا مدرسين (Instructor أو HoD) وليس لديهم سجل instructor بعد
        $potentialInstructors = User::whereIn('role_id', function ($query) {
            $query->select('id')->from('roles')->whereIn('name', ['instructor', 'hod']);
        })
            ->whereDoesntHave('instructor') // التأكد أنه غير مرتبط بسجل instructor حالي
            ->get();

        $departments = Department::pluck('id')->toArray(); // جلب IDs الأقسام
        $academicDegrees = ['Lecturer', 'Assistant Professor', 'Associate Professor', 'Professor']; // درجات علمية محتملة

        if (empty($departments)) {
            $this->command->warn('No departments found. Skipping InstructorSeeder.');
            return;
        }

        foreach ($potentialInstructors as $user) {
            // تحديد قسم عشوائي للمدرس
            $randomDepartmentId = $faker->randomElement($departments);
            $randomDegree = $faker->randomElement($academicDegrees);

            // تحديد ساعات أسبوعية قصوى بناءً على الدرجة (مثال)
            $maxHours = match ($randomDegree) {
                'Professor' => 12,
                'Associate Professor' => 14,
                'Assistant Professor' => 16,
                'Lecturer' => 18,
                default => 18,
            };

            Instructor::create([
                'user_id' => $user->id,
                'instructor_no' => 'INST' . str_pad($user->id, 4, '0', STR_PAD_LEFT), // رقم وظيفي فريد بناءً على user_id
                'instructor_name' => $user->name, // الاعتماد على اسم اليوزر
                'academic_degree' => $randomDegree,
                'department_id' => $randomDepartmentId,
                'max_weekly_hours' => $maxHours,
                // 'office_location' => 'Building ' . $faker->randomElement(['A', 'B', 'C']) . ' - Room ' . $faker->numberBetween(101, 399),
                // 'office_hours' => $faker->randomElement(['Mon/Wed 10-12', 'Tue/Thu 1-3', 'By Appointment']),
                // 'availability_preferences' => json_encode(['preferred' => [], 'unavailable' => []]), // يمكن تركه فارغاً الآن
            ]);
        }
    }
}
