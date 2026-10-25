<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;

class TestingUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan divisions dan positions sudah ada
        $kaligrafi = Division::where('name', 'Kaligrafi')->first();
        $mtq = Division::where('name', 'MTQ')->first();
        $dakwah = Division::where('name', 'Dakwah')->first();
        $hadroh = Division::where('name', 'Hadroh')->first();

        $ketuaUmum = Position::where('name', 'Ketua Umum')->first();
        $sekretaris = Position::where('name', 'Sekretaris')->first();
        $bendahara = Position::where('name', 'Bendahara')->first();
        $ketuaDivisi = Position::where('name', 'Ketua Divisi')->first();

        // 1. Ketua Umum
        $user1 = User::create([
            'email' => 'ketua@ipmi.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $member1 = Member::create([
            'user_id' => $user1->id,
            'nis' => '2024001',
            'full_name' => 'Ahmad Ketua Umum',
            'division_id' => $kaligrafi->id,
            'phone' => '081234567001',
            'status' => 'active',
        ]);

        $member1->positions()->attach($ketuaUmum->id);

        // 2. Sekretaris
        $user2 = User::create([
            'email' => 'sekretaris@ipmi.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $member2 = Member::create([
            'user_id' => $user2->id,
            'nis' => '2024002',
            'full_name' => 'Siti Sekretaris',
            'division_id' => $mtq->id,
            'phone' => '081234567002',
            'status' => 'active',
        ]);

        $member2->positions()->attach($sekretaris->id);

        // 3. Ketua Divisi Kaligrafi
        $user3 = User::create([
            'email' => 'kadiv.kaligrafi@ipmi.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $member3 = Member::create([
            'user_id' => $user3->id,
            'nis' => '2024003',
            'full_name' => 'Budi Ketua Divisi Kaligrafi',
            'division_id' => $kaligrafi->id,
            'phone' => '081234567003',
            'status' => 'active',
        ]);

        $member3->positions()->attach($ketuaDivisi->id);

        // 4. Anggota Biasa Kaligrafi
        $user4 = User::create([
            'email' => 'anggota.kaligrafi@ipmi.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $member4 = Member::create([
            'user_id' => $user4->id,
            'nis' => '2024004',
            'full_name' => 'Dewi Anggota Kaligrafi',
            'division_id' => $kaligrafi->id,
            'phone' => '081234567004',
            'status' => 'active',
        ]);

        // 5. Anggota Biasa MTQ
        $user5 = User::create([
            'email' => 'anggota.mtq@ipmi.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $member5 = Member::create([
            'user_id' => $user5->id,
            'nis' => '2024005',
            'full_name' => 'Eko Anggota MTQ',
            'division_id' => $mtq->id,
            'phone' => '081234567005',
            'status' => 'active',
        ]);

        // 6. Ketua Divisi Hadroh
        $user6 = User::create([
            'email' => 'kadiv.hadroh@ipmi.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $member6 = Member::create([
            'user_id' => $user6->id,
            'nis' => '2024006',
            'full_name' => 'Fajar Ketua Divisi Hadroh',
            'division_id' => $hadroh->id,
            'phone' => '081234567006',
            'status' => 'active',
        ]);

        $member6->positions()->attach($ketuaDivisi->id);

        $this->command->info('✅ Testing users created successfully!');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('1. Ketua Umum: ketua@ipmi.test / password123');
        $this->command->info('2. Sekretaris: sekretaris@ipmi.test / password123');
        $this->command->info('3. Kadiv Kaligrafi: kadiv.kaligrafi@ipmi.test / password123');
        $this->command->info('4. Anggota Kaligrafi: anggota.kaligrafi@ipmi.test / password123');
        $this->command->info('5. Anggota MTQ: anggota.mtq@ipmi.test / password123');
        $this->command->info('6. Kadiv Hadroh: kadiv.hadroh@ipmi.test / password123');
    }
}
