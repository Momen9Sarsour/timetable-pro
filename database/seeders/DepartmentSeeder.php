<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('departments')->delete();

        $departments = [
            ['department_no' => 'CS', 'department_name' => 'علوم الحاسوب وتكنولوجيا المعلومات', 'created_at' => now(), 'updated_at' => now()],
            ['department_no' => 'ENG', 'department_name' => 'الهندسة والتكنولوجيا', 'created_at' => now(), 'updated_at' => now()],
            ['department_no' => 'BUS', 'department_name' => 'العلوم الإدارية والمالية', 'created_at' => now(), 'updated_at' => now()],
            ['department_no' => 'AS', 'department_name' => 'العلوم التطبيقية', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('departments')->insert($departments);
    }
}
