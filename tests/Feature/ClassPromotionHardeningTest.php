<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\MemberClassHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class ClassPromotionHardeningTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private AcademicYear $old;
    private AcademicYear $new;

    protected function setUp(): void
    {
        parent::setUp();
        $this->old = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'status' => 'active']);
        $this->new = AcademicYear::create(['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'upcoming']);
    }

    private function cls(AcademicYear $y, string $g, int $n = 1, string $status = 'active'): ClassRoom
    {
        return ClassRoom::create(['academic_year_id' => $y->id, 'grade_level' => $g, 'major' => 'RPL', 'class_number' => $n, 'name' => "$g RPL $n", 'status' => $status]);
    }

    private function post_(array $mappings)
    {
        return $this->actingAs($this->makeUser('Sekretaris'))->post('/admin/kenaikan-kelas/proses', [
            'source_year_id' => $this->old->id, 'target_year_id' => $this->new->id, 'mappings' => $mappings,
        ]);
    }

    public function test_kelas_kosong_dan_kelas_lulus_boleh_tanpa_tujuan_riwayat_lama_tetap(): void
    {
        $x = $this->cls($this->old, 'X'); $xii = $this->cls($this->old, 'XII'); $empty = $this->cls($this->old, 'XI');
        $xi = $this->cls($this->new, 'XI');
        $a = $this->makeUser()->member; $b = $this->makeUser()->member;
        MemberClassHistory::create(['member_id' => $a->id, 'class_id' => $x->id]);
        MemberClassHistory::create(['member_id' => $b->id, 'class_id' => $xii->id]);

        $this->post_([$x->id => $xi->id, $xii->id => '', $empty->id => ''])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('member_class_histories', ['member_id' => $a->id, 'class_id' => $xi->id]);
        $this->assertDatabaseHas('member_class_histories', ['member_id' => $a->id, 'class_id' => $x->id]); // riwayat lama harus tetap
        $this->assertSame(1, MemberClassHistory::where('member_id', $b->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'class.promotion']);
    }

    public function test_kelas_asal_palsu_dan_kelas_tujuan_nonaktif_ditolak(): void
    {
        $x = $this->cls($this->old, 'X');
        $inactive = $this->cls($this->new, 'XI', 1, 'inactive');
        $otherYearClass = $this->cls($this->old, 'XI', 2);
        $xi = $this->cls($this->new, 'XI', 2);

        $this->post_([$x->id => $inactive->id])->assertSessionHasErrors('mappings');
        $this->post_([$x->id => $otherYearClass->id])->assertSessionHasErrors('mappings');
        // kelas tahun tujuan dipakai sebagai kelas asal -> ditolak
        $this->post_([$xi->id => $xi->id])->assertSessionHasErrors('mappings');
        $this->assertSame(0, MemberClassHistory::count());
    }

    public function test_anggota_yang_sudah_punya_kelas_tujuan_dilewati_bukan_membatalkan_semua(): void
    {
        $x = $this->cls($this->old, 'X'); $xi = $this->cls($this->new, 'XI');
        $a = $this->makeUser()->member; $b = $this->makeUser()->member;
        MemberClassHistory::create(['member_id' => $a->id, 'class_id' => $x->id]);
        MemberClassHistory::create(['member_id' => $b->id, 'class_id' => $x->id]);
        MemberClassHistory::create(['member_id' => $b->id, 'class_id' => $xi->id]);

        $this->post_([$x->id => $xi->id])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('member_class_histories', ['member_id' => $a->id, 'class_id' => $xi->id]);
        $this->assertSame(2, MemberClassHistory::where('member_id', $b->id)->count());
    }

    public function test_anggota_nonaktif_tidak_dinaikkan_dan_non_pengurus_dilarang(): void
    {
        $x = $this->cls($this->old, 'X'); $xi = $this->cls($this->new, 'XI');
        $inactive = $this->makeUser(null, null, ['status' => 'inactive'])->member;
        MemberClassHistory::create(['member_id' => $inactive->id, 'class_id' => $x->id]);
        $this->post_([$x->id => $xi->id]);
        $this->assertSame(1, MemberClassHistory::count());

        $this->actingAs($this->makeUser('Ketua Divisi'))->post('/admin/kenaikan-kelas/proses', [
            'source_year_id' => $this->old->id, 'target_year_id' => $this->new->id, 'mappings' => [$x->id => $xi->id],
        ])->assertForbidden();
    }
}
