<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Member;
use App\Models\Position;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Membuat divisi, jabatan, dan Ketua Umum pertama pada instalasi baru.
 * Aman dijalankan ulang (tidak membuat data ganda); menolak jika Ketua Umum aktif sudah ada.
 */
class CreateInitialAdmin extends Command
{
    protected $signature = 'ipmi:buat-admin
                            {--name= : Nama lengkap}
                            {--email= : Email login}
                            {--nis= : NIS}
                            {--division=Dakwah : Divisi (Hadroh/MTQ/Dakwah/Kaligrafi)}';

    protected $description = 'Membuat divisi, jabatan, dan akun Ketua Umum pertama';

    public function handle(): int
    {
        foreach (['Hadroh', 'MTQ', 'Dakwah', 'Kaligrafi'] as $name) {
            Division::firstOrCreate(['name' => $name]);
        }
        foreach (['Ketua Umum', 'Sekretaris', 'Bendahara', 'Ketua Divisi'] as $name) {
            Position::firstOrCreate(['name' => $name]);
        }

        $ketuaUmum = Position::where('name', 'Ketua Umum')->first();
        $exists = Member::where('status', 'active')
            ->whereHas('positions', fn ($q) => $q->whereKey($ketuaUmum->id))->exists();

        if ($exists) {
            $this->error('Ketua Umum aktif sudah ada. Perintah ini hanya untuk instalasi baru.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->option('name') ?: $this->ask('Nama lengkap'),
            'email' => $this->option('email') ?: $this->ask('Email'),
            'nis' => $this->option('nis') ?: $this->ask('NIS'),
            'password' => $this->secret('Password (min. 10 karakter)'),
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'nis' => 'required|string|max:20|regex:/^[0-9A-Za-z\-]+$/|unique:members,nis',
            'password' => 'required|string|min:10',
        ]);

        $division = Division::where('name', $this->option('division'))->first();
        if (!$division) {
            $this->error('Divisi tidak dikenal.');

            return self::FAILURE;
        }

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($data, $division, $ketuaUmum) {
            $user = User::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);
            $member = Member::create([
                'user_id' => $user->id,
                'nis' => $data['nis'],
                'full_name' => $data['name'],
                'division_id' => $division->id,
                'status' => 'active',
            ]);
            $member->positions()->attach($ketuaUmum->id);
            AuditLog::record('admin.initial_created', $member, 'Ketua Umum pertama dibuat lewat perintah artisan');
        });

        $this->info('Ketua Umum berhasil dibuat. Silakan login.');

        return self::SUCCESS;
    }
}
