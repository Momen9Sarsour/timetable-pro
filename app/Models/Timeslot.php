<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeslot extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
    ];

    // لا تحتاج timestamps إذا لم تضفها في الـ migration
    // public $timestamps = false;

    /**
     * Get the schedule entries (lectures) assigned to this timeslot.
     * علاقة: الفترة الزمنية لديها عدة محاضرات في الجدول النهائي (One To Many)
     */
    public function scheduleEntries()
    {
        return $this->hasMany(GeneratedSchedule::class, 'timeslot_id', 'id');
    }

     /**
     * Accessor to get day name.
     * دالة مساعدة للحصول على اسم اليوم
     */
    public function getDayNameAttribute(): string
    {
        // تأكد من أن الأرقام تبدأ من 0 للأحد
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Accessor to format start time.
      * دالة مساعدة لتنسيق وقت البداية
     */
    public function getFormattedStartTimeAttribute(): string
    {
        try {
            return \Carbon\Carbon::parse($this->start_time)->format('h:i A');
        } catch (\Exception $e) {
            return $this->start_time; // fallback
        }
    }

     /**
     * Accessor to format end time.
      * دالة مساعدة لتنسيق وقت النهاية
     */
    public function getFormattedEndTimeAttribute(): string
    {
       try {
            return \Carbon\Carbon::parse($this->end_time)->format('h:i A');
        } catch (\Exception $e) {
            return $this->end_time; // fallback
        }
    }

}
