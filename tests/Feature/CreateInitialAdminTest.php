<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateInitialAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_ketua_umum_pertama_dan_bisa_login(): void
    {
        $this->artisan('ipmi:buat-admin', ['--name' => 'Admin IPMI', '--email' => 'admin@ipmi.test', '--nis' => '1001'])
            ->expectsQuestion('Password (min. 10 karakter)', 'RahasiaKuat123')
            ->assertSuccessful();

        $member = Member::where('nis', '1001')->firstOrFail();
        $this->assertTrue($member->isKetuaUmum());
        $this->assertSame(4, \App\Models\Division::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.initial_created']);

        $this->post('/login', ['email' => 'admin@ipmi.test', 'password' => 'RahasiaKuat123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_menolak_password_pendek_dan_menolak_jika_ketua_umum_sudah_ada(): void
    {
        $this->artisan('ipmi:buat-admin', ['--name' => 'A', '--email' => 'a@ipmi.test', '--nis' => '1'])
            ->expectsQuestion('Password (min. 10 karakter)', 'pendek')
            ->assertFailed();
        $this->assertSame(0, User::count());

        $this->artisan('ipmi:buat-admin', ['--name' => 'A', '--email' => 'a@ipmi.test', '--nis' => '1'])
            ->expectsQuestion('Password (min. 10 karakter)', 'RahasiaKuat123')->assertSuccessful();

        $this->artisan('ipmi:buat-admin', ['--name' => 'B', '--email' => 'b@ipmi.test', '--nis' => '2'])->assertFailed();
        $this->assertSame(1, User::count());
    }
}
