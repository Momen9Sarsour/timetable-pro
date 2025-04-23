<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the role associated with the user.
     * علاقة: المستخدم يتبع لدور واحد (One To Many Inverse)
     */
    public function role()
    {
        // اسم الموديل المرتبط، المفتاح الأجنبي في جدول users، المفتاح الأساسي في جدول roles
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * Get the instructor record associated with the user.
     * علاقة: المستخدم قد يكون له سجل مدرس واحد (One To One)
     */
    public function instructor()
    {
        // اسم الموديل المرتبط، المفتاح الأجنبي في جدول instructors، المفتاح المحلي في جدول users
        return $this->hasOne(Instructor::class, 'user_id', 'id');
    }

    /**
     * Get the logs for the user.
     * علاقة: المستخدم له سجلات كثيرة (One To Many) - إذا لم تستخدم باكج Spatie
     */
    // public function logs()
    // {
    //     return $this->hasMany(Log::class, 'user_id', 'id');
    // }

    /**
     * Get the notifications for the user.
     * علاقة: المستخدم لديه تنبيهات كثيرة (مورف) - هذه تأتي مع Notifiable trait
     */
    // public function notifications() { ... } // تأتي جاهزة

    /**
     * Helper function to check user role by name.
     * دالة مساعدة للتحقق من دور المستخدم بسهولة
     */
    public function hasRole(string|array $roleNames): bool
    {
        if (is_string($roleNames)) {
            return $this->role && $this->role->name === $roleNames;
        }

        return $this->role && in_array($this->role->name, $roleNames);
    }
}
