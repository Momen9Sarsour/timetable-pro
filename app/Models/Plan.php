<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_no',
        'plan_name',
        'year',
        'plan_hours',
        'is_active',
        'department_id',
    ];

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean', // تحويل is_active إلى boolean
    ];


    /**
     * Get the department associated with the plan.
     * علاقة: الخطة تتبع لقسم واحد (One To Many Inverse)
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * Get the subjects included in this plan.
     * علاقة: الخطة تحتوي على عدة مواد (Many To Many through plan_subjects)
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'plan_subjects', 'plan_id', 'subject_id')
                    ->withPivot('plan_level', 'plan_semester')
                    ->withTimestamps();
    }

     /**
     * Get the plan_subjects entries for this plan.
     * علاقة: الخطة لها عدة إدخالات في جدول plan_subjects (One To Many)
     */
    public function planSubjectEntries()
    {
        return $this->hasMany(PlanSubject::class, 'plan_id', 'id');
    }

    /**
     * Get the expected student counts for this plan.
     * علاقة: الخطة لها عدة سجلات أعداد متوقعة (One To Many)
     */
    public function expectedCounts()
    {
        return $this->hasMany(PlanExpectedCount::class, 'plan_id', 'id');
    }
}
