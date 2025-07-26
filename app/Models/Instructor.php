<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // تأكد من إضافته هنا
        'instructor_no',
        'instructor_name', // قرر إذا كنت ستحتفظ به أم لا
        'academic_degree',
        'department_id',
        'availability_preferences',
        'max_weekly_hours',
        'office_location', // أضفنا هذا
        'office_hours',    // أضفنا هذا
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'availability_preferences' => 'json', // مهم لتحويل حقل JSON لمصفوفة PHP والعكس
    ];

    /**
     * Get the user account associated with the instructor.
     * علاقة: المدرس يتبع لمستخدم واحد (One To One Inverse)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the department that the instructor belongs to.
     * علاقة: المدرس يتبع لقسم واحد (One To Many Inverse)
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * Get the schedule entries (lectures) assigned to the instructor.
     * علاقة: المدرس لديه عدة محاضرات في الجدول النهائي (One To Many)
     */
    public function scheduleEntries()
    {
        return $this->hasMany(GeneratedSchedule::class, 'instructor_id', 'id');
    }

    public function subjects()
    {
        // اسم الموديل المرتبط، اسم الجدول الوسيط، المفتاح الأجنبي لهذا الموديل، المفتاح الأجنبي للموديل المرتبط
        return $this->belongsToMany(Subject::class, 'instructor_subject', 'instructor_id', 'subject_id')
            ->withTimestamps(); // إذا أضفت timestamps للجدول الوسيط
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'instructor_section');
    }

    public function canTeach(Subject $subject): bool
    {
        // الطريقة الأكثر كفاءة هي التحقق من وجود العلاقة
        //  exists() تنفذ استعلاماً سريعاً للتحقق من وجود سجل واحد على الأقل يطابق الشرط
        return $this->subjects()->where('subjects.id', $subject->id)->exists();

        // طريقة أخرى (أقل كفاءة إذا لم تكن العلاقة محملة مسبقاً):
        // $assignedSubjectIds = $this->subjects->pluck('id');
        // return $assignedSubjectIds->contains($subject->id);
    }

}
