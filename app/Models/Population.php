<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Population extends Model
{
    use HasFactory;

    // اسم الجدول
    // protected $table = 'populations';
    protected $primaryKey = 'population_id';

    // الحقول المسموح بتعبئتها
    protected $fillable = [
        'population_size',
        'crossover_id',
        'selection_id',
        'mutation_rate',
        'generations_count',
        'best_chromosome_id',
        'start_time',
        'end_time',
        'status',
    ];

    /**
     * علاقة: عملية التشغيل الواحدة تتبع لنوع اختيار واحد.
     */
    public function selectionType()
    {
        // علاقة "واحد إلى واحد" مع جدول SelectionType
        return $this->belongsTo(SelectionType::class, 'selection_id', 'selection_type_id');
    }

    /**
     * علاقة: عملية التشغيل الواحدة تتبع لنوع تزاوج واحد.
     */
    public function crossover()
    {
        // علاقة "واحد إلى واحد" مع جدول CrossoverType
        return $this->belongsTo(CrossoverType::class, 'crossover_id', 'crossover_id');
    }

    /**
     * علاقة: عملية التشغيل الواحدة تحتوي على عدة كروموسومات (حلول مقترحة).
     */
    public function chromosomes()
    {
        // علاقة "واحد إلى متعدد" مع جدول Chromosome
        return $this->hasMany(Chromosome::class, 'population_id', 'population_id');
    }

    /**
     * علاقة: عملية التشغيل الواحدة لها أفضل كروموسوم واحد في النهاية.
     */
    public function bestChromosome()
    {
        // علاقة "واحد إلى واحد" مع جدول Chromosome
        return $this->belongsTo(Chromosome::class, 'best_chromosome_id', 'chromosome_id');
    }
}
