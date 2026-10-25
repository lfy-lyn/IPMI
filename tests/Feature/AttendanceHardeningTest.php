<?php

namespace Tests\Feature;

use App\Models\ActivityAttendance;
use App\Models\Division;
use App\Models\IncidentalActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

class AttendanceHardeningTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private Division $kal;
    private Division $mtq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kal = Division::factory()->create(['name' => 'Kaligrafi']);
        $this->mtq = Division::factory()->create(['name' => 'MTQ']);
    }

    private function activity(?Division $d, string $mode = 'per_event', array $over = []): IncidentalActivity
    {
        return IncidentalActivity::factory()->create(array_merge([
            'division_id' => $d?->id, 'attendance_mode' => $mode, 'status' => 'ongoing',
            'start_date' => '2026-11-01', 'end_date' => null,
        ], $over));
    }

    public function test_ketua_divisi_tidak_bisa_mencatat_absensi_anggota_divisi_lain(): void
    {
        $kadiv = $this->makeUser('Ketua Divisi', $this->kal);
        $ownMember = $this->makeUser(null, $this->kal)->member;
        $foreign = $this->makeUser(null, $this->mtq)->member;
        $activity = $this->activity($this->kal);

        $this->actingAs($kadiv)->post("/admin/kegiatan/{$activity->id}/absensi", [
            'attendances' => [
                ['member_id' => $ownMember->id, 'status' => 'hadir'],
                ['member_id' => $foreign->id, 'status' => 'hadir'],
            ],
        ])->assertSessionHasErrors('attendances');

        $this->assertSame(0, ActivityAttendance::count(), 'Tidak boleh ada yang tersimpan sebagian');
    }

    public function test_ketua_divisi_tidak_bisa_mengelola_kegiatan_seluruh_ipmi_atau_divisi_lain(): void
    {
        $kadiv = $this->makeUser('Ketua Divisi', $this->kal);
        $m = $this->makeUser(null, $this->kal)->member;

        foreach ([$this->activity(null), $this->activity($this->mtq)] as $a) {
            $this->actingAs($kadiv)->get("/admin/kegiatan/{$a->id}/absensi")->assertForbidden();
            $this->actingAs($kadiv)->post("/admin/kegiatan/{$a->id}/absensi", [
                'attendances' => [['member_id' => $m->id, 'status' => 'hadir']],
            ])->assertForbidden();
        }
    }

    public function test_absensi_per_event_menolak_hari_dan_mengubah_status_tercatat_audit(): void
    {
        $ketua = $this->makeUser('Ketua Umum', $this->kal);
        $m = $this->makeUser(null, $this->kal)->member;
        $activity = $this->activity($this->kal);

        $this->actingAs($ketua)->post("/admin/kegiatan/{$activity->id}/absensi", [
            'attendances' => [['member_id' => $m->id, 'status' => 'hadir']],
        ])->assertSessionHasNoErrors();

        $this->actingAs($ketua)->post("/admin/kegiatan/{$activity->id}/absensi", [
            'attendances' => [['member_id' => $m->id, 'status' => 'alpa']],
        ]);

        $this->assertSame(1, ActivityAttendance::where('member_id', $m->id)->count());
        $this->assertDatabaseHas('activity_attendances', ['member_id' => $m->id, 'status' => 'alpa']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.changed']);
    }

    public function test_kegiatan_per_hari_membuat_hari_otomatis_dan_absensi_wajib_memilih_hari(): void
    {
        $ketua = $this->makeUser('Ketua Umum', $this->kal);
        $m = $this->makeUser(null, $this->kal)->member;

        $this->actingAs($ketua)->post('/admin/kegiatan', [
            'name' => 'Pesantren Kilat', 'start_date' => '2026-11-02', 'end_date' => '2026-11-04',
            'attendance_mode' => 'per_day', 'status' => 'upcoming', 'division_id' => null,
        ])->assertRedirect('/admin/kegiatan');

        $activity = IncidentalActivity::where('name', 'Pesantren Kilat')->firstOrFail();
        $this->assertSame(3, $activity->activityDays()->count());

        // Tanpa hari -> ditolak
        $this->actingAs($ketua)->post("/admin/kegiatan/{$activity->id}/absensi", [
            'attendances' => [['member_id' => $m->id, 'status' => 'hadir']],
        ])->assertSessionHasErrors('activity_day_id');

        // Hari milik kegiatan lain -> ditolak
        $other = $this->activity($this->kal, 'per_day');
        $otherDay = $other->activityDays()->create(['date' => '2026-11-01']);
        $this->actingAs($ketua)->post("/admin/kegiatan/{$activity->id}/absensi", [
            'activity_day_id' => $otherDay->id,
            'attendances' => [['member_id' => $m->id, 'status' => 'hadir']],
        ])->assertSessionHasErrors('activity_day_id');

        // Hari yang benar -> tersimpan dan terhubung ke hari tsb
        $day = $activity->activityDays()->orderBy('date')->first();
        $this->actingAs($ketua)->post("/admin/kegiatan/{$activity->id}/absensi", [
            'activity_day_id' => $day->id,
            'attendances' => [['member_id' => $m->id, 'status' => 'hadir']],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_attendances', ['member_id' => $m->id, 'activity_day_id' => $day->id]);

        $this->actingAs($ketua)->get("/admin/kegiatan/{$activity->id}/absensi")->assertOk();
        $this->actingAs($ketua)->get("/admin/kegiatan/{$activity->id}/rekap")->assertOk();
    }

    public function test_ketua_divisi_tidak_bisa_membuat_kegiatan_lintas_divisi(): void
    {
        $kadiv = $this->makeUser('Ketua Divisi', $this->kal);

        $this->actingAs($kadiv)->post('/admin/kegiatan', [
            'name' => 'Coba Lintas', 'start_date' => '2026-11-02', 'attendance_mode' => 'per_event',
            'status' => 'upcoming', 'division_id' => null,
        ]);

        $this->assertSame($this->kal->id, IncidentalActivity::where('name', 'Coba Lintas')->value('division_id'));
    }

    public function test_anggota_biasa_hanya_melihat_kegiatan_seluruh_ipmi_dan_divisinya(): void
    {
        $member = $this->makeUser(null, $this->kal);
        $this->activity(null, 'per_event', ['name' => 'Kegiatan Umum Terlihat']);
        $this->activity($this->kal, 'per_event', ['name' => 'Kegiatan Kaligrafi Terlihat']);
        $this->activity($this->mtq, 'per_event', ['name' => 'Kegiatan MTQ Tersembunyi']);

        $this->actingAs($member)->get('/admin/kegiatan')
            ->assertOk()
            ->assertSee('Kegiatan Umum Terlihat')
            ->assertSee('Kegiatan Kaligrafi Terlihat')
            ->assertDontSee('Kegiatan MTQ Tersembunyi');
    }
}
