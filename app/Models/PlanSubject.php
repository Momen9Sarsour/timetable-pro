<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanSubject extends Model
{
    use HasFactory;

    protected $table = 'plan_subjects'; // تحديد اسم الجدول

    protected $fillable = [
        'plan_id',
        'subject_id',
        'plan_level',
        'plan_semester',
    ];

    // لا تحتاج timestamps إذا لم تضفها في الـ migration لجدول plan_subjects
    // public $timestamps = true;

    /**
     * Get the plan associated with this entry.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    /**
     * Get the subject associated with this entry.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

     /**
     * Get the sections defined for this specific plan subject entry.
     * علاقة: مدخل خطة المادة الواحد قد يكون له عدة شعب (One To Many)
     */
    public function sections()
    {
        return $this->hasMany(Section::class, 'plan_subject_id', 'id');
    }

}
