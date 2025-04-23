<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_no',
        'subject_name',
        'subject_load',
        'theoretical_hours',
        'practical_hours',
        'subject_type_id',
        'subject_category_id',
        'department_id',
    ];

    /**
     * Get the type of the subject.
     * علاقة: المادة تتبع لنوع واحد (One To Many Inverse)
     */
    public function subjectType()
    {
        return $this->belongsTo(SubjectType::class, 'subject_type_id', 'id');
    }

    /**
     * Get the category of the subject.
     * علاقة: المادة تتبع لفئة واحدة (One To Many Inverse)
     */
    public function subjectCategory()
    {
        return $this->belongsTo(SubjectCategory::class, 'subject_category_id', 'id');
    }

    /**
     * Get the primary department offering the subject.
     * علاقة: المادة تتبع لقسم واحد (One To Many Inverse)
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * Get the plans that include this subject.
     * علاقة: المادة موجودة في عدة خطط (Many To Many through plan_subjects)
     */
    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_subjects', 'subject_id', 'plan_id')
                    ->withPivot('plan_level', 'plan_semester') // جلب الأعمدة الإضافية من جدول الربط
                    ->withTimestamps(); // جلب created_at/updated_at من جدول الربط (إذا احتجت)
    }

     /**
     * Get the plan_subjects entries for this subject.
     * علاقة: المادة لها عدة إدخالات في جدول plan_subjects (One To Many)
     * (مفيدة للوصول المباشر لمعلومات الربط والمستوى والفصل)
     */
    public function planSubjectEntries()
    {
        return $this->hasMany(PlanSubject::class, 'subject_id', 'id');
    }


    /**
     * Get the sections for this subject (indirectly through plan_subjects).
     * علاقة: المادة لها شعب كثيرة (One Has Many Through - معقدة قليلاً)
     * أو يمكن الوصول لها عبر planSubjectEntries->sections
     */
     // ...

     /**
      * Get the generated schedule entries for this subject (indirectly).
      * يمكن الوصول لها عبر الشعب Sections
      */
      // ...
}
