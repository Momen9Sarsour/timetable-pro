<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'id',
        'display_name',
        'description',
    ];

    /**
     * Get the users that have this role.
     * علاقة: الدور الواحد يمتلكه عدة مستخدمين (One To Many)
     */

    public function users()
    {
        // اسم الموديل المرتبط، المفتاح الأجنبي في جدول users، المفتاح المحلي في جدول roles
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
