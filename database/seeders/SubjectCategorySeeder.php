<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('subjects_categories')->delete();

        $categories = [
            ['subject_category_name' => 'نظري', 'created_at' => now(), 'updated_at' => now()],
            ['subject_category_name' => 'عملي', 'created_at' => now(), 'updated_at' => now()],
            ['subject_category_name' => 'نظري وعملي', 'created_at' => now(), 'updated_at' => now()], // إذا كانت تُدرس كوحدة واحدة
        ];

        DB::table('subjects_categories')->insert($categories);
    }
}
