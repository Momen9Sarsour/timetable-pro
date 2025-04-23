<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Room::truncate(); // حذف القاعات القديمة
        DB::table('rooms')->delete(); // إذا استخدمت الـ DB Facade

        $faker = Faker::create();

        $roomTypes = RoomType::all(); // جلب أنواع القاعات

        if ($roomTypes->isEmpty()) {
             $this->command->warn('No room types found. Skipping RoomSeeder.');
             return;
        }

        // مثال لإنشاء 20 قاعة متنوعة
        for ($i = 1; $i <= 20; $i++) {
            $roomType = $faker->randomElement($roomTypes);
            $roomNumber = $faker->randomElement(['A', 'B', 'C', 'D']) . $faker->numberBetween(101, 499); // رقم قاعة عشوائي
            $capacity = $faker->randomElement([20, 25, 30, 35, 40, 50, 60, 100]); // سعة عشوائية

            // تحديد معدات بناءً على نوع القاعة (مثال)
            $equipment = ['whiteboard'];
            if (str_contains(strtolower($roomType->room_type_name), 'lecture') || str_contains(strtolower($roomType->room_type_name), 'seminar')) {
                $equipment[] = 'projector';
                if ($faker->boolean(70)) $equipment[] = 'sound system'; // 70% probability
            }
            if (str_contains(strtolower($roomType->room_type_name), 'lab')) {
                $equipment[] = 'projector';
                if (str_contains(strtolower($roomType->room_type_name), 'computer') || str_contains(strtolower($roomType->room_type_name), 'network')) {
                     $equipment[] = 'computers';
                }
            }
             if ($faker->boolean(30)) $equipment[] = 'smart board'; // 30% probability for any room

            Room::create([
                'room_no' => $roomNumber,
                'room_name' => $roomType->room_type_name . ' ' . $roomNumber, // اسم وصفي
                'room_size' => $capacity,
                'room_gender' => $faker->randomElement(['Male', 'Female', 'Mixed']), // تخصيص عشوائي للجنس
                // 'room_branch' => 'Main Campus', // افترض فرع واحد الآن
                'room_type_id' => $roomType->id,
                // 'equipment' => json_encode(array_unique($equipment)), // تخزين المعدات كـ JSON فريد
            ]);
        }
    }
}
