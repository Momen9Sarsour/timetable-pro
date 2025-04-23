<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'loggable_id',
        'loggable_type',
        'details',
        'ip_address',
        'user_agent',
    ];

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'details' => 'json',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the model that was logged.
     * علاقة MorphTo (متعدد الأشكال)
     */
    public function loggable()
    {
        return $this->morphTo();
    }
}
