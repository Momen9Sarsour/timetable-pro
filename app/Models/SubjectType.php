<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectType extends Model
{
    use HasFactory;

    // اسم الجدول مختلف عن اسم الموديل (subjects_types vs SubjectType)
    protected $table = 'subjects_types'; // حدد اسم الجدول يدوياً

    protected $fillable = [
        'subject_type_name',
    ];

    /**
     * Get the subjects of this type.
     * علاقة: النوع الواحد لديه عدة مواد (One To Many)
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'subject_type_id', 'id');
    }
}
