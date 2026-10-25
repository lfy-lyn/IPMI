<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class AnnouncementVisibilityTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private function make(string $title, array $over = []): Announcement
    {
        $ku = $this->ku ??= $this->makeUser('Ketua Umum');

        return Announcement::factory()->create(array_merge([
            'title' => $title, 'target_type' => 'all', 'division_id' => null,
            'status' => 'published', 'published_at' => now()->subHour(), 'created_by' => $ku->id,
        ], $over));
    }

    private $ku;

    public function test_anggota_hanya_melihat_pengumuman_terbit_sesuai_sasaran(): void
    {
        $kal = Division::factory()->create();
        $mtq = Division::factory()->create();
        $member = $this->makeUser(null, $kal);

        $visible = $this->make('Umum Terbit');
        $own = $this->make('Divisi Sendiri Terbit', ['target_type' => 'division', 'division_id' => $kal->id]);
        $hidden = [
            $this->make('Draft Rahasia', ['status' => 'draft', 'published_at' => null]),
            $this->make('Arsip Lama', ['status' => 'archived']),
            $this->make('Terjadwal Nanti', ['published_at' => now()->addDay()]),
            $this->make('Divisi Lain', ['target_type' => 'division', 'division_id' => $mtq->id]),
            $this->make('Khusus Pengurus', ['target_type' => 'pengurus']),
        ];

        $this->actingAs($member)->get('/admin/pengumuman')
            ->assertOk()->assertSee('Umum Terbit')->assertSee('Divisi Sendiri Terbit')
            ->assertDontSee('Draft Rahasia')->assertDontSee('Arsip Lama')->assertDontSee('Terjadwal Nanti')
            ->assertDontSee('Divisi Lain')->assertDontSee('Khusus Pengurus');

        // Akses langsung lewat ID (IDOR) juga ditolak
        foreach ($hidden as $a) {
            $this->actingAs($member)->get("/admin/pengumuman/{$a->id}")->assertForbidden();
        }
        $this->actingAs($member)->get("/admin/pengumuman/{$visible->id}")->assertOk();
        $this->actingAs($member)->get("/admin/pengumuman/{$own->id}")->assertOk();
    }

    public function test_ketua_divisi_melihat_pengumuman_pengurus_tapi_tidak_draft(): void
    {
        $kadiv = $this->makeUser('Ketua Divisi');
        $this->make('Khusus Pengurus', ['target_type' => 'pengurus']);
        $draft = $this->make('Draft Pengurus', ['target_type' => 'pengurus', 'status' => 'draft', 'published_at' => null]);

        $this->actingAs($kadiv)->get('/admin/pengumuman')->assertSee('Khusus Pengurus')->assertDontSee('Draft Pengurus');
        $this->actingAs($kadiv)->get("/admin/pengumuman/{$draft->id}")->assertForbidden();
    }

    public function test_sekretaris_dapat_menjadwalkan_dan_melihat_draft(): void
    {
        $sek = $this->makeUser('Sekretaris');
        $draft = $this->make('Draft Internal', ['status' => 'draft', 'published_at' => null]);

        $this->actingAs($sek)->get('/admin/pengumuman')->assertSee('Draft Internal');
        $this->actingAs($sek)->post('/admin/pengumuman', [
            'title' => 'Terjadwal', 'content' => 'Isi', 'target_type' => 'all', 'status' => 'published',
            'published_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect('/admin/pengumuman');

        $this->actingAs($this->makeUser())->get('/admin/pengumuman')->assertDontSee('Terjadwal');
    }
}
