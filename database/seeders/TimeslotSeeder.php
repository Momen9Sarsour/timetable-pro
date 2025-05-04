<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeslotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف الفترات القديمة لضمان عدم التكرار
        DB::table('timeslots')->delete();

        $timeslots = [];
        // تحديد أيام الدوام (يمكن أخذها من إعدادات لاحقاً)
        $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']; // مثال: الأحد إلى الخميس
        // تحديد أوقات الدوام (يمكن أخذها من إعدادات لاحقاً)
        $startTime = Carbon::createFromTimeString('08:00:00');
        $endTime = Carbon::createFromTimeString('16:00:00'); // مثال: حتى 4 مساءً
        // تحديد مدة المحاضرة والاستراحة بالدقائق (يمكن أخذها من إعدادات لاحقاً)
        $lectureDurationMinutes = 50;
        $breakDurationMinutes = 10;

        foreach ($daysOfWeek as $day) {
            $currentTime = $startTime->copy(); // ابدأ من وقت البداية لكل يوم

            // استمر في إضافة فترات طالما أن وقت نهاية الفترة التالية لا يتجاوز وقت نهاية الدوام
            while ($currentTime->copy()->addMinutes($lectureDurationMinutes)->lte($endTime)) {
                $slotEndTime = $currentTime->copy()->addMinutes($lectureDurationMinutes);

                $timeslots[] = [
                    'day' => $day,
                    'start_time' => $currentTime->format('H:i:s'), // تنسيق HH:MM:SS
                    'end_time' => $slotEndTime->format('H:i:s'),
                    // created_at و updated_at سيتم إضافتهما تلقائياً إذا كان $timestamps = true في الموديل
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // الانتقال لبداية الفترة التالية (وقت النهاية الحالي + الاستراحة)
                $currentTime = $slotEndTime->copy()->addMinutes($breakDurationMinutes);
            }
        }

        // إدراج كل الفترات دفعة واحدة في قاعدة البيانات
        if (!empty($timeslots)) {
            DB::table('timeslots')->insert($timeslots);
             $this->command->info('Timeslots seeded successfully!'); // رسالة تأكيد
        } else {
             $this->command->warn('No timeslots generated based on the provided settings.');
        }

    }
}
