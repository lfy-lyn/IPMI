<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Ketua Umum',
                'description' => 'Pemimpin tertinggi organisasi IPMI',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sekretaris',
                'description' => 'Mengelola administrasi dan dokumentasi organisasi',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bendahara',
                'description' => 'Mengelola keuangan organisasi',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ketua Divisi',
                'description' => 'Memimpin salah satu divisi (Kaligrafi, MTQ, Dakwah, Hadroh)',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \Illuminate\Support\Facades\DB::table('positions')->insert($positions);
    }
}
