<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Program;
use App\Models\ProgramSession;
use App\Models\ProgramSessionAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private Division $had;
    private Division $mtq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->had = Division::factory()->create(['name' => 'Hadroh']);
        $this->mtq = Division::factory()->create(['name' => 'MTQ']);
    }

    private function program(?Division $d, string $name = 'Latihan Hadroh', string $status = 'active'): Program
    {
        return Program::create(['name' => $name, 'division_id' => $d?->id, 'status' => $status]);
    }

    public function test_alur_buat_program_sesi_dan_absensi_beserta_rekap(): void
    {
        $sek = $this->makeUser('Sekretaris');
        $m1 = $this->makeUser(null, $this->had)->member;
        $m2 = $this->makeUser(null, $this->had)->member;

        $this->actingAs($sek)->post('/admin/program', [
            'name' => 'Latihan Hadroh', 'schedule_note' => 'Rabu 15.30', 'division_id' => $this->had->id, 'status' => 'active',
        ])->assertRedirect();
        $program = Program::firstOrFail();

        $this->actingAs($sek)->post("/admin/program/{$program->id}/sesi", ['session_date' => '2026-11-04', 'agenda' => 'Marawis dasar'])->assertRedirect();
        $session = ProgramSession::firstOrFail();

        // tanggal ganda ditolak
        $this->actingAs($sek)->post("/admin/program/{$program->id}/sesi", ['session_date' => '2026-11-04'])->assertSessionHasErrors('session_date');

        $this->actingAs($sek)->get("/admin/program/{$program->id}/sesi/{$session->id}/absensi")->assertOk()->assertSee($m1->full_name);
        $this->actingAs($sek)->post("/admin/program/{$program->id}/sesi/{$session->id}/absensi", ['attendances' => [
            ['member_id' => $m1->id, 'status' => 'hadir'], ['member_id' => $m2->id, 'status' => 'alpa'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(2, ProgramSessionAttendance::count());

        // ubah status -> audit, baris tidak ganda
        $this->actingAs($sek)->post("/admin/program/{$program->id}/sesi/{$session->id}/absensi", ['attendances' => [['member_id' => $m2->id, 'status' => 'izin']]]);
        $this->assertSame(2, ProgramSessionAttendance::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.changed']);

        $this->actingAs($sek)->get("/admin/program/{$program->id}")->assertOk()->assertSee('Rekap Kehadiran');
    }

    public function test_idor_anggota_divisi_lain_dan_sesi_program_lain_ditolak(): void
    {
        $kadiv = $this->makeUser('Ketua Divisi', $this->had);
        $own = $this->makeUser(null, $this->had)->member;
        $foreign = $this->makeUser(null, $this->mtq)->member;
        $program = $this->program($this->had);
        $session = $program->sessions()->create(['session_date' => '2026-11-04']);

        $this->actingAs($kadiv)->post("/admin/program/{$program->id}/sesi/{$session->id}/absensi", ['attendances' => [
            ['member_id' => $own->id, 'status' => 'hadir'], ['member_id' => $foreign->id, 'status' => 'hadir'],
        ]])->assertSessionHasErrors('attendances');
        $this->assertSame(0, ProgramSessionAttendance::count());

        // sesi milik program lain lewat URL program sendiri -> 404
        $other = $this->program($this->had, 'Program Lain');
        $otherSession = $other->sessions()->create(['session_date' => '2026-11-05']);
        $this->actingAs($kadiv)->get("/admin/program/{$program->id}/sesi/{$otherSession->id}/absensi")->assertNotFound();
    }

    public function test_hak_akses_ketua_divisi_dan_anggota(): void
    {
        $kadiv = $this->makeUser('Ketua Divisi', $this->had);
        $member = $this->makeUser(null, $this->had);
        $general = $this->program(null, 'Pembiasaan Pagi');
        $own = $this->program($this->had, 'Latihan Hadroh');
        $foreign = $this->program($this->mtq, 'Latihan MTQ');

        // Kadiv tidak bisa membuat program lintas divisi: dipaksa ke divisinya
        $this->actingAs($kadiv)->post('/admin/program', ['name' => 'Coba Umum', 'division_id' => '', 'status' => 'active']);
        $this->assertSame($this->had->id, Program::where('name', 'Coba Umum')->value('division_id'));
        $this->actingAs($kadiv)->post('/admin/program', ['name' => 'Curang', 'division_id' => $this->mtq->id, 'status' => 'active'])->assertForbidden();

        // Kadiv tidak boleh mengelola program umum / divisi lain
        foreach ([$general, $foreign] as $p) {
            $this->actingAs($kadiv)->get("/admin/program/{$p->id}/edit")->assertForbidden();
            $this->actingAs($kadiv)->post("/admin/program/{$p->id}/sesi", ['session_date' => '2026-11-04'])->assertForbidden();
        }

        // Anggota biasa: lihat program umum + divisinya, tanpa rekap/absensi, tidak bisa membuat
        $this->actingAs($member)->get('/admin/program')->assertOk()->assertSee('Pembiasaan Pagi')->assertSee('Latihan Hadroh')->assertDontSee('Latihan MTQ');
        $this->actingAs($member)->get("/admin/program/{$foreign->id}")->assertForbidden();
        $this->actingAs($member)->get("/admin/program/{$own->id}")->assertOk()->assertDontSee('Rekap Kehadiran');
        $this->actingAs($member)->get('/admin/program/tambah')->assertForbidden();
        $session = $own->sessions()->create(['session_date' => '2026-11-04']);
        $this->actingAs($member)->get("/admin/program/{$own->id}/sesi/{$session->id}/absensi")->assertForbidden();
    }

    public function test_program_nonaktif_tersembunyi_dari_anggota_dan_tidak_bisa_diberi_sesi(): void
    {
        $member = $this->makeUser(null, $this->had);
        $inactive = $this->program(null, 'Program Lama', 'inactive');

        $this->actingAs($member)->get('/admin/program')->assertDontSee('Program Lama');
        $this->actingAs($member)->get("/admin/program/{$inactive->id}")->assertForbidden();
        $this->actingAs($this->makeUser('Ketua Umum'))->post("/admin/program/{$inactive->id}/sesi", ['session_date' => '2026-11-04'])->assertSessionHas('error');
    }

    public function test_sasaran_divisi_dikunci_jika_sudah_ada_absensi(): void
    {
        $ku = $this->makeUser('Ketua Umum');
        $m = $this->makeUser(null, $this->had)->member;
        $program = $this->program($this->had);
        $s = $program->sessions()->create(['session_date' => '2026-11-04']);
        ProgramSessionAttendance::create(['program_session_id' => $s->id, 'member_id' => $m->id, 'status' => 'hadir']);

        $this->actingAs($ku)->put("/admin/program/{$program->id}", ['name' => 'X', 'division_id' => $this->mtq->id, 'status' => 'active'])
            ->assertSessionHasErrors('division_id');
        $this->assertSame($this->had->id, $program->fresh()->division_id);
    }
}
