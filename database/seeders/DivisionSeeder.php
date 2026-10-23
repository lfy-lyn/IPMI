<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Kaligrafi',
                'description' => 'Divisi seni kaligrafi Islam',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'MTQ',
                'description' => 'Divisi Musabaqah Tilawatil Quran',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dakwah',
                'description' => 'Divisi dakwah dan kajian Islam',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hadroh',
                'description' => 'Divisi seni hadroh dan rebana',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \Illuminate\Support\Facades\DB::table('divisions')->insert($divisions);
    }
}
