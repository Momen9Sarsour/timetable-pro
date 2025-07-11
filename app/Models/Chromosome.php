<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chromosome extends Model
{
    use HasFactory;

    // اسم الجدول
    // protected $table = 'chromosomes';

    // اسم المفتاح الأساسي
    protected $primaryKey = 'chromosome_id';

    // الحقول المسموح بتعبئتها
    protected $fillable = [
        'population_id',
        'penalty_value',
        'generation_number',
        'is_best_of_generation',
    ];

    /**
     * علاقة: الكروموسوم الواحد يتبع لعملية تشغيل واحدة (Population).
     */
    public function population()
    {
        // علاقة "واحد إلى واحد" مع جدول Population
        return $this->belongsTo(Population::class, 'population_id', 'population_id');
    }

    /**
     * علاقة: الكروموسوم الواحد يحتوي على عدة جينات (تفاصيل محاضرات).
     */
    public function genes()
    {
        // علاقة "واحد إلى متعدد" مع جدول Gene
        return $this->hasMany(Gene::class, 'chromosome_id', 'chromosome_id');
    }
}
