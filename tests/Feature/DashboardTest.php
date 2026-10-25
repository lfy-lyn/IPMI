<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Division;
use App\Models\IncidentalActivity;
use App\Models\MembershipApplication;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesIpmiUsers, RefreshDatabase;

    public function test_menu_sensitif_hanya_tampil_untuk_pengurus_pusat(): void
    {
        $anggota = $this->makeUser();
        $kadiv = $this->makeUser('Ketua Divisi');
        $sekretaris = $this->makeUser('Sekretaris');

        foreach ([$anggota, $kadiv] as $user) {
            $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();
            $this->assertStringNotContainsString(route('admin.membership.index'), $html);
            $this->assertStringNotContainsString(route('academic-years.index'), $html);
            $this->assertStringNotContainsString(route('class-promotion.index'), $html);
            $this->assertStringContainsString(route('admin.members.index'), $html);
        }

        $html = $this->actingAs($sekretaris)->get('/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString(route('admin.membership.index'), $html);
        $this->assertStringContainsString(route('academic-years.index'), $html);
        $this->assertStringContainsString(route('class-promotion.index'), $html);
    }

    public function test_akun_tanpa_data_anggota_melihat_empty_state_dan_tanpa_menu_admin(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Profil anggota belum tersedia')
            ->assertDontSee(route('admin.members.index'));
    }

    public function test_dashboard_menampilkan_data_nyata_dan_tidak_ada_data_dummy(): void
    {
        $ketua = $this->makeUser('Ketua Umum', null, ['full_name' => 'Budi Santoso']);
        $div = $ketua->member->division;

        IncidentalActivity::factory()->create([
            'name' => 'Kajian Akbar Ramadhan', 'division_id' => null, 'status' => 'upcoming',
            'start_date' => now()->addDays(3)->toDateString(), 'end_date' => null, 'created_by' => $ketua->id,
        ]);
        Program::create(['name' => 'Latihan Hadroh Rutin', 'division_id' => $div->id, 'status' => 'active'])
            ->sessions()->create(['session_date' => now()->addDay()->toDateString(), 'start_time' => '15:30']);
        Announcement::factory()->create([
            'title' => 'Rapat Pengurus Pekan Ini', 'target_type' => 'all', 'status' => 'published',
            'published_at' => now()->subHour(), 'created_by' => $ketua->id,
        ]);
        MembershipApplication::create([
            'nis' => '1234567', 'full_name' => 'Calon Satu', 'email' => 'c1@example.com', 'phone' => '0812',
            'division_id' => $div->id, 'reason' => 'ingin belajar', 'status' => 'pending',
        ]);

        $this->actingAs($ketua)->get('/dashboard')
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('Kajian Akbar Ramadhan')
            ->assertSee('Latihan Hadroh Rutin')
            ->assertSee('Rapat Pengurus Pekan Ini')
            ->assertSee('Calon Satu')
            ->assertSee('1 perlu ditinjau')
            ->assertDontSee('EasyProperty');
    }

    public function test_dashboard_kosong_menampilkan_empty_state_bukan_angka_palsu(): void
    {
        $this->actingAs($this->makeUser('Sekretaris'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Belum ada kegiatan mendatang')
            ->assertSee('Belum ada sesi terjadwal')
            ->assertSee('Belum ada pengumuman')
            ->assertSee('Tidak ada antrean');
    }

    public function test_anggota_biasa_tidak_melihat_pendaftaran_dan_hanya_pengumuman_untuknya(): void
    {
        $dA = Division::factory()->create();
        $dB = Division::factory()->create();
        $anggota = $this->makeUser(null, $dA);

        MembershipApplication::create([
            'nis' => '7654321', 'full_name' => 'Rahasia Pendaftar', 'email' => 'r@example.com', 'phone' => '0812',
            'division_id' => $dA->id, 'reason' => 'x', 'status' => 'pending',
        ]);
        Announcement::factory()->create(['title' => 'Untuk Divisi Lain', 'target_type' => 'division', 'division_id' => $dB->id,
            'status' => 'published', 'published_at' => now()->subHour()]);
        Announcement::factory()->create(['title' => 'Draft Rahasia', 'target_type' => 'all', 'status' => 'draft', 'published_at' => null]);
        Announcement::factory()->create(['title' => 'Untuk Semua Orang', 'target_type' => 'all', 'status' => 'published',
            'published_at' => now()->subHour()]);

        $this->actingAs($anggota)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Rahasia Pendaftar')
            ->assertDontSee('Pendaftaran Menunggu')
            ->assertDontSee('Untuk Divisi Lain')
            ->assertDontSee('Draft Rahasia')
            ->assertSee('Untuk Semua Orang');
    }

    public function test_kegiatan_divisi_lain_tidak_muncul_di_dashboard_anggota(): void
    {
        $dA = Division::factory()->create();
        $dB = Division::factory()->create();
        $anggota = $this->makeUser(null, $dA);

        IncidentalActivity::factory()->create(['name' => 'Kegiatan Divisi Lain', 'division_id' => $dB->id,
            'status' => 'upcoming', 'start_date' => now()->addDay()->toDateString()]);

        $this->actingAs($anggota)->get('/dashboard')->assertOk()->assertDontSee('Kegiatan Divisi Lain');
    }

    public function test_flash_tidak_tampil_dobel_dan_tetap_tampil_di_halaman_tanpa_flash_sendiri(): void
    {
        $user = $this->makeUser('Sekretaris');

        // Halaman anggota sudah menampilkan flash sendiri → hanya satu kali.
        $html = $this->actingAs($user)->withSession(['success' => 'Data berhasil disimpan XYZ'])
            ->get('/admin/anggota')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'Data berhasil disimpan XYZ'));

        // Dashboard tidak menampilkan flash sendiri → layout yang menampilkannya.
        $html = $this->actingAs($user)->withSession(['warning' => 'Peringatan ABC'])
            ->get('/dashboard')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'Peringatan ABC'));
    }

    public function test_halaman_publik_tidak_memuat_aset_dashboard(): void
    {
        foreach (['/', '/daftar', '/login'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('dashboard-', $this->assetNames($html));
            $this->assertStringNotContainsString('ipmi-app', $html);
        }
    }

    private function assetNames(string $html): string
    {
        preg_match_all('/(?:href|src)="([^"]+)"/', $html, $m);

        return implode(' ', $m[1]);
    }

    public function test_route_dashboard_tidak_berubah(): void
    {
        $this->assertSame('/dashboard', route('dashboard', [], false));
    }
}
