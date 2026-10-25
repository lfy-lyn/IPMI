<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\ActivationToken;
use App\Models\Division;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class RegistrationFlowSecurityTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private function app_(array $over = []): MembershipApplication
    {
        return MembershipApplication::create(array_merge([
            'nis' => '5550001', 'full_name' => 'Calon Baru', 'email' => 'calon@example.com',
            'division_id' => Division::factory()->create()->id, 'reason' => 'Ingin belajar dan berkontribusi',
            'status' => 'pending',
        ], $over));
    }

    public function test_alur_lengkap_daftar_setujui_aktivasi_login(): void
    {
        Mail::fake();
        $div = Division::factory()->create();

        $this->post('/daftar', [
            'nis' => '777', 'full_name' => 'Siti Aisyah', 'email' => 'Siti@Example.com',
            'division_id' => $div->id, 'reason' => 'Saya ingin belajar kaligrafi dan ikut kegiatan.',
        ])->assertRedirect('/daftar/sukses');

        $application = MembershipApplication::firstOrFail();
        $this->assertSame('siti@example.com', $application->email);

        $this->actingAs($this->makeUser('Sekretaris'))->post("/admin/pendaftaran/{$application->id}/setujui")->assertRedirect();
        Mail::assertSent(AccountActivationMail::class);

        $application->refresh();
        $member = Member::findOrFail($application->member_id);
        $this->assertSame('777', $member->nis);
        $this->assertSame('pending', $member->user->status);

        $token = ActivationToken::where('member_id', $member->id)->firstOrFail();
        auth()->logout();
        $this->post("/aktivasi/{$token->token}", ['password' => 'PasswordBaru1', 'password_confirmation' => 'PasswordBaru1'])
            ->assertRedirect('/aktivasi-sukses');

        $this->assertSame('active', $member->user->fresh()->status);
        $this->post('/login', ['email' => 'siti@example.com', 'password' => 'PasswordBaru1']);
        $this->assertAuthenticated();

        // Token tidak bisa dipakai dua kali
        $this->post("/aktivasi/{$token->token}", ['password' => 'LainLagi1234', 'password_confirmation' => 'LainLagi1234']);
        $this->assertTrue(Hash::check('PasswordBaru1', $member->user->fresh()->password));
    }

    public function test_email_milik_akun_yang_sudah_ada_tidak_boleh_disetujui_pengambilalihan_akun(): void
    {
        Mail::fake();
        $victim = $this->makeUser('Ketua Umum');
        $application = $this->app_(['email' => $victim->email]);
        $sekretaris = $this->makeUser('Sekretaris');
        $users = User::count();

        $this->actingAs($sekretaris)->post("/admin/pendaftaran/{$application->id}/setujui")
            ->assertSessionHas('error');

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertSame($users, User::count());
        $this->assertSame(0, ActivationToken::count());
        Mail::assertNothingSent();
    }

    public function test_nis_yang_sudah_anggota_ditolak_saat_daftar_dan_saat_setujui(): void
    {
        $existing = $this->makeUser(null, null, ['nis' => '123123']);
        $div = Division::factory()->create();

        $this->post('/daftar', ['nis' => '123123', 'full_name' => 'X', 'email' => 'x@example.com', 'division_id' => $div->id,
            'reason' => 'Alasan yang cukup panjang untuk lolos.'])->assertSessionHasErrors('nis');

        $application = $this->app_(['nis' => '123123']);
        $this->actingAs($this->makeUser('Ketua Umum'))->post("/admin/pendaftaran/{$application->id}/setujui")->assertSessionHas('error');
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_token_tidak_dapat_mengganti_password_akun_yang_sudah_aktif(): void
    {
        $user = $this->makeUser();
        $user->update(['password' => Hash::make('PasswordAsli123')]);
        $token = ActivationToken::generate($user->member);

        $this->post("/aktivasi/{$token->token}", ['password' => 'PasswordBaru999', 'password_confirmation' => 'PasswordBaru999']);

        $this->assertTrue(Hash::check('PasswordAsli123', $user->fresh()->password));
        $this->assertNull($token->fresh()->used_at);
    }

    public function test_batas_tiga_pendaftaran_per_nis_dan_penolakan_wajib_alasan(): void
    {
        $div = Division::factory()->create();
        for ($i = 1; $i <= 3; $i++) {
            $this->post('/daftar', ['nis' => '888', 'full_name' => 'Y', 'email' => "y{$i}@example.com", 'division_id' => $div->id,
                'reason' => 'Alasan yang cukup panjang untuk lolos.'])->assertRedirect('/daftar/sukses');
        }
        $this->post('/daftar', ['nis' => '888', 'full_name' => 'Y', 'email' => 'y4@example.com', 'division_id' => $div->id,
            'reason' => 'Alasan yang cukup panjang untuk lolos.'])->assertSessionHasErrors('nis');

        $application = MembershipApplication::first();
        $ku = $this->makeUser('Ketua Umum');
        $this->actingAs($ku)->post("/admin/pendaftaran/{$application->id}/tolak", [])->assertSessionHasErrors('rejection_reason');
        $this->actingAs($ku)->post("/admin/pendaftaran/{$application->id}/tolak", ['rejection_reason' => 'Data tidak lengkap dan tidak sesuai'])->assertRedirect();
        $this->assertSame('rejected', $application->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'membership.rejected']);
    }

    public function test_anggota_biasa_dan_ketua_divisi_tidak_bisa_meninjau_pendaftaran(): void
    {
        $application = $this->app_();
        foreach ([$this->makeUser(), $this->makeUser('Ketua Divisi')] as $u) {
            $this->actingAs($u)->get('/admin/pendaftaran')->assertForbidden();
            $this->actingAs($u)->post("/admin/pendaftaran/{$application->id}/setujui")->assertForbidden();
        }
        $this->assertSame('pending', $application->fresh()->status);
    }
}
