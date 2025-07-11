<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrossoverType extends Model
{
    use HasFactory;

    // اسم الجدول في قاعدة البيانات
    // protected $table = 'crossovers';
    protected $primaryKey = 'crossover_id';

    // الحقول المسموح بتعبئتها
    protected $fillable = ['name', 'description', 'is_active'];

    public function populations()
    {
        // علاقة "واحد إلى متعدد" مع جدول populations
        return $this->hasMany(Population::class, 'crossover_id', 'crossover_id');
    }
}
