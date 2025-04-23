<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_no',
        'room_name',
        'room_size',
        'room_gender',
        'room_branch',
        'room_type_id',
        // 'equipment', // أضفنا هذا
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'equipment' => 'json', // مهم لحقل JSON
    ];

    /**
     * Get the type of the room.
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'id');
    }

    /**
     * Get the schedule entries (lectures) assigned to this room.
     */
    public function scheduleEntries()
    {
        return $this->hasMany(GeneratedSchedule::class, 'room_id', 'id');
    }
}
