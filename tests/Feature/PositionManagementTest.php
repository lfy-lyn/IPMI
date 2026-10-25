<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class PositionManagementTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private function pos(string $n): Position
    {
        return Position::firstOrCreate(['name' => $n]);
    }

    public function test_ketua_umum_menetapkan_dan_mencabut_jabatan_dengan_audit(): void
    {
        $ku = $this->makeUser('Ketua Umum');
        $target = $this->makeUser()->member;

        $this->actingAs($ku)->get("/admin/anggota/{$target->id}")->assertOk()->assertSee('Tetapkan jabatan');
        $this->actingAs($ku)->post("/admin/anggota/{$target->id}/jabatan", ['position_id' => $this->pos('Sekretaris')->id])->assertSessionHas('success');
        $this->assertTrue($target->fresh()->isSekretaris());
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.position_assigned']);

        $this->actingAs($ku)->delete("/admin/anggota/{$target->id}/jabatan/{$this->pos('Sekretaris')->id}")->assertSessionHas('success');
        $this->assertFalse($target->fresh()->isSekretaris());
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.position_removed']);
    }

    public function test_sekretaris_tidak_bisa_menetapkan_ketua_umum_atau_sekretaris(): void
    {
        $sek = $this->makeUser('Sekretaris');
        $target = $this->makeUser()->member;

        foreach (['Ketua Umum', 'Sekretaris'] as $name) {
            $this->actingAs($sek)->post("/admin/anggota/{$target->id}/jabatan", ['position_id' => $this->pos($name)->id])->assertForbidden();
        }
        $this->assertSame(0, $target->positions()->count());

        $this->actingAs($sek)->post("/admin/anggota/{$target->id}/jabatan", ['position_id' => $this->pos('Ketua Divisi')->id])->assertSessionHas('success');
        $this->assertTrue($target->fresh()->isKetuaDivisi());
    }

    public function test_anggota_biasa_dan_ketua_divisi_tidak_bisa_mengelola_jabatan(): void
    {
        $target = $this->makeUser()->member;
        foreach ([$this->makeUser(), $this->makeUser('Ketua Divisi')] as $u) {
            $this->actingAs($u)->post("/admin/anggota/{$target->id}/jabatan", ['position_id' => $this->pos('Bendahara')->id])->assertForbidden();
        }
        $this->assertSame(0, $target->positions()->count());
    }

    public function test_satu_ketua_divisi_per_divisi_dan_satu_sekretaris(): void
    {
        $ku = $this->makeUser('Ketua Umum');
        $div = Division::factory()->create();
        $a = $this->makeUser(null, $div)->member;
        $b = $this->makeUser(null, $div)->member;
        $kadiv = $this->pos('Ketua Divisi');

        $this->actingAs($ku)->post("/admin/anggota/{$a->id}/jabatan", ['position_id' => $kadiv->id])->assertSessionHas('success');
        $this->actingAs($ku)->post("/admin/anggota/{$b->id}/jabatan", ['position_id' => $kadiv->id])->assertSessionHas('error');
        $this->assertFalse($b->fresh()->isKetuaDivisi());

        // divisi lain boleh
        $c = $this->makeUser()->member;
        $this->actingAs($ku)->post("/admin/anggota/{$c->id}/jabatan", ['position_id' => $kadiv->id])->assertSessionHas('success');

        $sekPos = $this->pos('Sekretaris');
        $this->actingAs($ku)->post("/admin/anggota/{$a->id}/jabatan", ['position_id' => $sekPos->id])->assertSessionHas('success');
        $this->actingAs($ku)->post("/admin/anggota/{$b->id}/jabatan", ['position_id' => $sekPos->id])->assertSessionHas('error');
    }

    public function test_ketua_umum_terakhir_tidak_bisa_dicabut_dan_anggota_nonaktif_tidak_bisa_diberi_jabatan(): void
    {
        $ku = $this->makeUser('Ketua Umum');
        $kuPos = $this->pos('Ketua Umum');
        $this->actingAs($ku)->delete("/admin/anggota/{$ku->member->id}/jabatan/{$kuPos->id}")->assertSessionHas('error');
        $this->assertTrue($ku->member->fresh()->isKetuaUmum());

        $inactive = $this->makeUser(null, null, ['status' => 'inactive'])->member;
        $this->actingAs($ku)->post("/admin/anggota/{$inactive->id}/jabatan", ['position_id' => $this->pos('Bendahara')->id])->assertSessionHas('error');
    }
}
