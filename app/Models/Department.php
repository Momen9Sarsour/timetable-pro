<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_no',
        'department_name',
    ];

    /**
     * Get the instructors for the department.
     * علاقة: القسم الواحد لديه عدة مدرسين (One To Many)
     */
    public function instructors()
    {
        return $this->hasMany(Instructor::class, 'department_id', 'id');
    }

    /**
     * Get the subjects offered primarily by the department.
     * علاقة: القسم الواحد يقدم عدة مواد (One To Many)
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'department_id', 'id');
    }

    /**
     * Get the academic plans associated with the department.
     * علاقة: القسم الواحد لديه عدة خطط دراسية (One To Many)
     */
    public function plans()
    {
        return $this->hasMany(Plan::class, 'department_id', 'id');
    }

}
