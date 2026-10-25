<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\MemberClassHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class ClassRoomTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private function year(string $status = 'active', string $name = '2026/2027'): AcademicYear
    {
        return AcademicYear::create([
            'name' => $name, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => $status,
        ]);
    }

    private function payload(AcademicYear $year, array $over = []): array
    {
        return array_merge([
            'academic_year_id' => $year->id, 'grade_level' => 'X', 'major' => 'RPL', 'class_number' => 1, 'status' => 'active',
        ], $over);
    }

    public function test_sekretaris_dapat_melihat_daftar_kelas(): void
    {
        $this->year();
        $this->actingAs($this->makeUser('Sekretaris'))->get('/admin/kelas')->assertOk();
    }

    public function test_halaman_tambah_dan_edit_kelas_terbuka(): void
    {
        $year = $this->year();
        $class = ClassRoom::create($this->payload($year) + ['name' => 'X RPL 1']);
        $u = $this->makeUser('Ketua Umum');

        $this->actingAs($u)->get('/admin/kelas/tambah')->assertOk();
        $this->actingAs($u)->get("/admin/kelas/{$class->id}/edit")->assertOk();
    }

    public function test_membuat_kelas_menghasilkan_nama_otomatis_dan_audit_log(): void
    {
        $year = $this->year();
        $u = $this->makeUser('Sekretaris');

        $this->actingAs($u)->post('/admin/kelas', $this->payload($year, ['grade_level' => 'xi', 'major' => ' tkj ', 'class_number' => 2]))
            ->assertRedirect('/admin/kelas');

        $this->assertDatabaseHas('classes', ['name' => 'XI TKJ 2', 'academic_year_id' => $year->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'class.created', 'actor_id' => $u->id]);
    }

    public function test_kelas_duplikat_ditolak(): void
    {
        $year = $this->year();
        $u = $this->makeUser('Sekretaris');
        $this->actingAs($u)->post('/admin/kelas', $this->payload($year));

        $this->actingAs($u)->post('/admin/kelas', $this->payload($year))->assertSessionHasErrors('class_number');
        $this->assertSame(1, ClassRoom::count());
    }

    public function test_input_tidak_valid_ditolak(): void
    {
        $year = $this->year();
        $u = $this->makeUser('Sekretaris');

        $this->actingAs($u)->post('/admin/kelas', $this->payload($year, ['grade_level' => 'XIII']))->assertSessionHasErrors('grade_level');
        $this->actingAs($u)->post('/admin/kelas', $this->payload($year, ['major' => '<script>']))->assertSessionHasErrors('major');
        $this->actingAs($u)->post('/admin/kelas', $this->payload($year, ['class_number' => 0]))->assertSessionHasErrors('class_number');
        $this->actingAs($u)->post('/admin/kelas', $this->payload($year, ['academic_year_id' => 999]))->assertSessionHasErrors('academic_year_id');
        $this->assertSame(0, ClassRoom::count());
    }

    public function test_tahun_ajaran_arsip_tidak_bisa_diberi_kelas_baru(): void
    {
        $year = $this->year('archived');
        $this->actingAs($this->makeUser('Sekretaris'))->post('/admin/kelas', $this->payload($year))->assertSessionHasErrors('academic_year_id');
    }

    public function test_kelas_berpenghuni_identitasnya_terkunci_tapi_status_boleh_diubah(): void
    {
        $year = $this->year();
        $class = ClassRoom::create($this->payload($year) + ['name' => 'X RPL 1']);
        $member = $this->makeUser()->member;
        MemberClassHistory::create(['member_id' => $member->id, 'class_id' => $class->id]);
        $u = $this->makeUser('Sekretaris');

        $this->actingAs($u)->put("/admin/kelas/{$class->id}", $this->payload($year, ['major' => 'TKJ']))->assertSessionHasErrors('grade_level');
        $this->assertSame('RPL', $class->fresh()->major);

        $this->actingAs($u)->put("/admin/kelas/{$class->id}", $this->payload($year, ['status' => 'inactive']))->assertRedirect('/admin/kelas');
        $this->assertSame('inactive', $class->fresh()->status);
    }

    public function test_ketua_divisi_dan_anggota_tidak_boleh_mengelola_kelas(): void
    {
        $year = $this->year();
        $class = ClassRoom::create($this->payload($year) + ['name' => 'X RPL 1']);

        foreach ([$this->makeUser('Ketua Divisi'), $this->makeUser()] as $u) {
            $this->actingAs($u)->get('/admin/kelas')->assertForbidden();
            $this->actingAs($u)->get('/admin/kelas/tambah')->assertForbidden();
            $this->actingAs($u)->post('/admin/kelas', $this->payload($year, ['class_number' => 5]))->assertForbidden();
            $this->actingAs($u)->get("/admin/kelas/{$class->id}/edit")->assertForbidden();
            $this->actingAs($u)->put("/admin/kelas/{$class->id}", $this->payload($year))->assertForbidden();
        }
        $this->assertSame(1, ClassRoom::count());
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/kelas')->assertRedirect('/login');
    }
}
