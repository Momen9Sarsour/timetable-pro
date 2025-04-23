<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanExpectedCount extends Model
{
    use HasFactory;

    protected $table = 'plan_expected_counts';

    protected $fillable = [
        'plan_id',
        'plan_level',
        'plan_semester',
        'male_count',
        'female_count',
        'branch',
        'academic_year',
    ];

    /**
     * Get the plan associated with this count.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }
}
