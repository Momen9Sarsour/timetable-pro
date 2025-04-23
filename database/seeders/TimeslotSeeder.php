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
        //
        DB::table('timeslots')->delete();

        $timeslots = [];
        $daysOfWeek = ['Sunday', 'Monday']; // 0=الأحد, 1=الإثنين, ..., 4=الخميس (افترض أن الجمعة والسبت عطلة)
        $startTime = Carbon::createFromTimeString('08:00:00');
        $endTime = Carbon::createFromTimeString('16:00:00'); // نهاية الدوام مثلاً 4 مساءً
        $lectureDurationMinutes = 50; // مدة المحاضرة 50 دقيقة
        $breakDurationMinutes = 10; // مدة الاستراحة 10 دقائق

        foreach ($daysOfWeek as $day) {
            $currentTime = $startTime->copy();
            while ($currentTime->copy()->addMinutes($lectureDurationMinutes)->lte($endTime)) {
                $slotEndTime = $currentTime->copy()->addMinutes($lectureDurationMinutes);
                $timeslots[] = [
                    'day' => $day,
                    'start_time' => $currentTime->format('H:i:s'),
                    'end_time' => $slotEndTime->format('H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                // الانتقال لبداية الفترة التالية (بعد المحاضرة والاستراحة)
                $currentTime->addMinutes($lectureDurationMinutes + $breakDurationMinutes);
            }
        }

        DB::table('timeslots')->insert($timeslots);
    }
}
