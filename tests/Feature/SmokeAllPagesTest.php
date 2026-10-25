<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Division;
use App\Models\IncidentalActivity;
use App\Models\Material;
use App\Models\MemberClassHistory;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

/**
 * Membuka SEMUA halaman GET untuk setiap peran dengan data nyata.
 * Tujuan: tidak ada halaman yang berakhir 500 (error server) untuk peran mana pun.
 * 403/404 untuk peran yang tidak berhak itu normal; yang dilarang hanya >= 500.
 */
class SmokeAllPagesTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    public function test_tidak_ada_halaman_yang_error_500_untuk_semua_peran(): void
    {
        $dKal = Division::factory()->create(['name' => 'Kaligrafi']);
        $dMtq = Division::factory()->create(['name' => 'MTQ']);

        $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'active']);
        $class = ClassRoom::create(['academic_year_id' => $year->id, 'grade_level' => 'X', 'major' => 'RPL', 'class_number' => 1, 'name' => 'X RPL 1', 'status' => 'active']);

        $users = [
            'ketua' => $this->makeUser('Ketua Umum', $dKal),
            'sekretaris' => $this->makeUser('Sekretaris', $dMtq),
            'kadiv' => $this->makeUser('Ketua Divisi', $dKal),
            'anggota' => $this->makeUser(null, $dKal),
            'anggota_lain' => $this->makeUser(null, $dMtq, ['full_name' => "Ma'ruf O'Brien"]),
        ];
        MemberClassHistory::create(['member_id' => $users['anggota']->member->id, 'class_id' => $class->id]);

        $activity = IncidentalActivity::factory()->create(['division_id' => null, 'attendance_mode' => 'per_event', 'status' => 'ongoing', 'created_by' => $users['ketua']->id]);
        $material = Material::factory()->create(['division_id' => $dKal->id, 'created_by' => $users['kadiv']->id]);
        $announcement = Announcement::factory()->create(['target_type' => 'all', 'created_by' => $users['ketua']->id]);
        $application = MembershipApplication::create([
            'nis' => '9990001', 'full_name' => 'Calon Anggota', 'email' => 'calon@example.com', 'phone' => '0812',
            'division_id' => $dKal->id, 'reason' => 'Ingin belajar', 'status' => 'pending',
        ]);

        $program = \App\Models\Program::create(['name' => 'Latihan', 'division_id' => $dKal->id, 'status' => 'active']);
        $psession = $program->sessions()->create(['session_date' => '2026-11-04']);

        $urls = [
            '/admin/program', '/admin/program/tambah', "/admin/program/{$program->id}", "/admin/program/{$program->id}/edit",
            "/admin/program/{$program->id}/sesi/{$psession->id}/absensi",
            '/', '/daftar', '/daftar/sukses', '/login', '/dashboard',
            '/admin/pendaftaran', "/admin/pendaftaran/{$application->id}",
            '/admin/anggota', "/admin/anggota/{$users['anggota']->member->id}", "/admin/anggota/{$users['anggota']->member->id}/edit",
            '/admin/tahun-ajaran', '/admin/tahun-ajaran/tambah', "/admin/tahun-ajaran/{$year->id}/edit",
            '/admin/kelas', '/admin/kelas/tambah', "/admin/kelas/{$class->id}/edit",
            '/admin/penetapan-kelas', '/admin/kenaikan-kelas',
            '/admin/kegiatan', '/admin/kegiatan/create', "/admin/kegiatan/{$activity->id}", "/admin/kegiatan/{$activity->id}/edit",
            "/admin/kegiatan/{$activity->id}/absensi", "/admin/kegiatan/{$activity->id}/rekap",
            '/admin/materi', '/admin/materi/create', "/admin/materi/{$material->id}", "/admin/materi/{$material->id}/edit",
            '/admin/pengumuman', '/admin/pengumuman/create', "/admin/pengumuman/{$announcement->id}", "/admin/pengumuman/{$announcement->id}/edit",
        ];

        $failures = [];
        foreach ($users as $label => $user) {
            foreach ($urls as $url) {
                $status = $this->actingAs($user)->get($url)->getStatusCode();
                if ($status >= 500) {
                    $failures[] = "[$label] GET $url => $status";
                }
            }
        }

        $this->assertSame([], $failures, "Halaman error:\n" . implode("\n", $failures));
    }

    public function test_penetapan_kelas_aman_untuk_nama_dengan_tanda_petik(): void
    {
        AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'active']);
        $this->makeUser(null, null, ['full_name' => "Ma'ruf <b>x</b>"]);
        $html = $this->actingAs($this->makeUser('Sekretaris'))->get('/admin/penetapan-kelas')->assertOk()->getContent();

        $this->assertStringNotContainsString('<b>x</b>', $html);
    }
}
