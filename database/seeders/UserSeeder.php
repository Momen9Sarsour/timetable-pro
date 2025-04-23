<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // User::truncate(); // حذف المستخدمين القدامى
        DB::table('Users')->delete(); // إذا استخدمت الـ DB Facade

        // جلب الأدوار من قاعدة البيانات
        $adminRole = Role::where('name', 'admin')->first();
        $hodRole = Role::where('name', 'hod')->first();
        $instructorRole = Role::where('name', 'instructor')->first();
        $studentRole = Role::where('name', 'student')->first();

        // --- إنشاء مستخدم Admin ---
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@ptc.edu', // استخدم إيميل حقيقي إذا أردت
            'password' => Hash::make('password'), // كلمة مرور افتراضية (غيرها لاحقاً)
            'role_id' => $adminRole->id,
            'email_verified_at' => now(), // تفعيل الإيميل مباشرة
        ]);

        // --- إنشاء رؤساء أقسام (مثال: واحد لكل قسم) ---
        $departments = \App\Models\Department::all(); // جلب الأقسام
        $hodCount = 1;
        foreach ($departments as $department) {
             User::create([
                'name' => 'Head of ' . $department->department_no,
                'email' => 'hod' . $hodCount++ . '@ptc.edu',
                'password' => Hash::make('password'),
                'role_id' => $hodRole->id,
                'email_verified_at' => now(),
            ]);
            // يمكن ربط رئيس القسم بالقسم هنا مباشرة إذا أضفنا حقل head_user_id في جدول departments
            // $department->update(['head_user_id' => $newUser->id]);
        }


        // --- إنشاء مدرسين (مثال: 10 مدرسين) ---
        User::factory()->count(10)->create([
             'role_id' => $instructorRole->id,
             'password' => Hash::make('password'), // كلمة مرور موحدة للمدرسين الوهميين
             'email_verified_at' => now(),
        ]);


        // --- إنشاء طلاب (مثال: 50 طالب) ---
        User::factory()->count(50)->create([
            'role_id' => $studentRole->id,
            'password' => Hash::make('password'), // كلمة مرور موحدة للطلاب الوهميين
            'email_verified_at' => now(),
        ]);

         // يمكنك استخدام factory لتوليد بيانات أكثر تنوعاً إذا أردت
         // User::factory()->count(5)->hod()->create(); // إذا عرفت حالة hod في الـ Factory

    }
}
