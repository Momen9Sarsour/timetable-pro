<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('rooms_types')->delete();

        $types = [
            ['room_type_name' => 'قاعة محاضرات', 'created_at' => now(), 'updated_at' => now()],
            ['room_type_name' => 'مختبر حاسوب', 'created_at' => now(), 'updated_at' => now()],
            ['room_type_name' => 'مختبر شبكات', 'created_at' => now(), 'updated_at' => now()],
            ['room_type_name' => 'مختبر كيمياء', 'created_at' => now(), 'updated_at' => now()],
            ['room_type_name' => 'قاعة رسم', 'created_at' => now(), 'updated_at' => now()],
            ['room_type_name' => 'قاعة متعددة الأغراض', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('rooms_types')->insert($types);
    }
}
