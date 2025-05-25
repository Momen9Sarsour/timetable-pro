<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_subject_id',
        'section_number',
        'student_count',
        'section_gender',
        'branch',
        'academic_year',
        'semester',
        'activity_type',
    ];

    /**
     * Get the plan subject entry associated with this section.
     * علاقة: الشعبة تتبع لمدخل خطة مادة واحد (One To Many Inverse)
     */
    public function planSubject()
    {
        return $this->belongsTo(PlanSubject::class, 'plan_subject_id', 'id');
    }

     /**
     * Get the schedule entries (lectures) for this section.
     * علاقة: الشعبة لديها عدة محاضرات في الجدول النهائي (One To Many)
     */
    public function scheduleEntries()
    {
        return $this->hasMany(GeneratedSchedule::class, 'section_id', 'id');
    }

     // يمكنك إضافة علاقات للوصول السريع للمادة والخطة من هنا
     public function subject()
     {
         // للوصول للمادة من خلال علاقة planSubject
         return $this->planSubject->subject(); // قد تحتاج لتحسين الأداء Eager Loading
     }

     public function plan()
     {
          // للوصول للخطة من خلال علاقة planSubject
         return $this->planSubject->plan();
     }
}
