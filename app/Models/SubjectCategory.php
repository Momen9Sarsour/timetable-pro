<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectCategory extends Model
{
    use HasFactory;

    // اسم الجدول مختلف
    protected $table = 'subjects_categories';

    protected $fillable = [
        'subject_category_name',
    ];

    /**
     * Get the subjects in this category.
     * علاقة: الفئة الواحدة لديها عدة مواد (One To Many)
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'subject_category_id', 'id');
    }
}
