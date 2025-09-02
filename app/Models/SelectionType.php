<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectionType extends Model
{
    use HasFactory;

    // اسم الجدول في قاعدة البيانات
    // protected $table = 'selection_types';
    protected $primaryKey = 'selection_type_id';

    // الحقول المسموح بتعبئتها
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    public function populations()
    {
        // علاقة "واحد إلى متعدد" مع جدول populations
        return $this->hasMany(Population::class, 'selection_id', 'selection_type_id');
    }
}
