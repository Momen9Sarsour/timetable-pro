<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف البيانات القديمة (اختياري لكن مفيد عند إعادة التنفيذ)
        DB::table('roles')->delete(); // إذا استخدمت الـ DB Facade

        $roles = [
            ['name' => 'admin', 'display_name' => 'مدير النظام', 'description' => 'يملك صلاحيات كاملة على النظام', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'hod', 'display_name' => 'رئيس قسم', 'description' => 'يملك صلاحيات على قسمه (إدارة، تعديل جدول)', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'instructor', 'display_name' => 'مدرس', 'description' => 'يمكنه عرض جدوله وبياناته', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'student', 'display_name' => 'طالب', 'description' => 'يمكنه عرض جدوله وبياناته', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('roles')->insert($roles); // إذا استخدمت الـ DB Facade

    }
}
