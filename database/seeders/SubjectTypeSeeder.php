<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('subjects_types')->delete();

        $types = [
            ['subject_type_name' => 'متطلب جامعة إجباري', 'created_at' => now(), 'updated_at' => now()],
            ['subject_type_name' => 'متطلب كلية إجباري', 'created_at' => now(), 'updated_at' => now()],
            ['subject_type_name' => 'متطلب تخصص إجباري', 'created_at' => now(), 'updated_at' => now()],
            ['subject_type_name' => 'متطلب تخصص اختياري', 'created_at' => now(), 'updated_at' => now()],
            ['subject_type_name' => 'مادة حرة', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('subjects_types')->insert($types);
    }
}
