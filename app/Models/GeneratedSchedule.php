<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedSchedule extends Model
{
    use HasFactory;

    // اسم الجدول مختلف
    protected $table = 'generated_schedules';

    protected $fillable = [
        'section_id',
        'instructor_id',
        'room_id',
        'timeslot_id',
        'academic_year',
        'semester',
        'lecture_type',
    ];

    /**
     * Get the section associated with this schedule entry.
     * علاقة: إدخال الجدول يتبع لشعبة واحدة (One To Many Inverse)
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    /**
     * Get the instructor assigned to this schedule entry.
     * علاقة: إدخال الجدول يتبع لمدرس واحد (One To Many Inverse)
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'instructor_id', 'id');
    }

    /**
     * Get the room assigned to this schedule entry.
     * علاقة: إدخال الجدول يتبع لقاعة واحدة (One To Many Inverse)
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    /**
     * Get the timeslot for this schedule entry.
     * علاقة: إدخال الجدول يتبع لفترة زمنية واحدة (One To Many Inverse)
     */
    public function timeslot()
    {
        return $this->belongsTo(Timeslot::class, 'timeslot_id', 'id');
    }

     // يمكنك إضافة علاقات للوصول السريع للمادة والخطة واليوم والوقت من هنا
     public function subject() {
         // يجب أن يكون لديك علاقة معرفة في موديل Section للوصول للمادة
         return $this->section->subject(); // قد تحتاج لتحسين الأداء Eager Loading
     }
      public function plan() {
         // يجب أن يكون لديك علاقة معرفة في موديل Section للوصول للخطة
         return $this->section->plan();
     }
     public function dayOfWeek() {
         return $this->timeslot->day_of_week; // أو DayName إذا استخدمت الـ Accessor
     }
     public function startTime() {
         return $this->timeslot->start_time; // أو FormattedStartTime
     }
     public function endTime() {
         return $this->timeslot->end_time; // أو FormattedEndTime
     }
}
