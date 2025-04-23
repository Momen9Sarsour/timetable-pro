<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    // اسم الجدول مختلف
    protected $table = 'rooms_types';

    protected $fillable = [
        'room_type_name',
    ];

    /**
     * Get the rooms of this type.
     * علاقة: النوع الواحد لديه عدة قاعات (One To Many)
     */
    public function rooms()
    {
        return $this->hasMany(Room::class, 'room_type_id', 'id');
    }
}
