<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\MemberClassHistory;

class MemberClassTest extends TestCase
{
    use RefreshDatabase;

    private function createKetuaUmum()
    {
        $division = Division::factory()->create();
        $user = User::factory()->create(['status' => 'active']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
        ]);
        
        $position = Position::firstOrCreate(['name' => 'Ketua Umum']);
        $member->positions()->attach($position->id);
        
        return $user;
    }

    private function createSekretaris()
    {
        $division = Division::factory()->create();
        $user = User::factory()->create(['status' => 'active']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
        ]);
        
        $position = Position::firstOrCreate(['name' => 'Sekretaris']);
        $member->positions()->attach($position->id);
        
        return $user;
    }

    private function createKetuaDivisi()
    {
        $division = Division::factory()->create();
        $user = User::factory()->create(['status' => 'active']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
        ]);
        
        $position = Position::firstOrCreate(['name' => 'Ketua Divisi']);
        $member->positions()->attach($position->id);
        
        return $user;
    }

    public function test_ketua_umum_can_access_member_class_assignment()
    {
        $user = $this->createKetuaUmum();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('member-classes.index'));

        $response->assertStatus(200);
    }

    public function test_sekretaris_can_access_member_class_assignment()
    {
        $user = $this->createSekretaris();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('member-classes.index'));

        $response->assertStatus(200);
    }

    public function test_ketua_divisi_cannot_access_member_class_assignment()
    {
        $user = $this->createKetuaDivisi();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('member-classes.index'));

        $response->assertStatus(403);
    }

    public function test_can_assign_class_to_member()
    {
        $user = $this->createKetuaUmum();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        $class = ClassRoom::create([
            'academic_year_id' => $activeYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        $division = Division::factory()->create();
        $member = Member::factory()->create(['division_id' => $division->id]);

        $response = $this->actingAs($user)->post(route('member-classes.assign', $member), [
            'class_id' => $class->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('member_class_histories', [
            'member_id' => $member->id,
            'class_id' => $class->id,
        ]);
    }

    public function test_class_must_be_from_active_academic_year()
    {
        $user = $this->createKetuaUmum();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        $archivedYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'archived',
        ]);
        $class = ClassRoom::create([
            'academic_year_id' => $archivedYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        $division = Division::factory()->create();
        $member = Member::factory()->create(['division_id' => $division->id]);

        $response = $this->actingAs($user)->post(route('member-classes.assign', $member), [
            'class_id' => $class->id,
        ]);

        $response->assertSessionHasErrors('class_id');
    }

    public function test_can_update_member_class()
    {
        $user = $this->createKetuaUmum();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        $class1 = ClassRoom::create([
            'academic_year_id' => $activeYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        $class2 = ClassRoom::create([
            'academic_year_id' => $activeYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 2,
            'name' => 'X RPL 2',
            'status' => 'active',
        ]);
        $division = Division::factory()->create();
        $member = Member::factory()->create(['division_id' => $division->id]);
        
        MemberClassHistory::create([
            'member_id' => $member->id,
            'class_id' => $class1->id,
        ]);

        // D-025: tanpa alasan koreksi, perpindahan rombel di tengah tahun ditolak.
        $this->actingAs($user)->post(route('member-classes.assign', $member), [
            'class_id' => $class2->id,
        ])->assertSessionHasErrors('correction_reason');
        $this->assertDatabaseHas('member_class_histories', ['member_id' => $member->id, 'class_id' => $class1->id]);

        // Dengan alasan: dianggap koreksi data salah, riwayat tetap 1 baris dan tercatat di audit log.
        $response = $this->actingAs($user)->post(route('member-classes.assign', $member), [
            'class_id' => $class2->id,
            'correction_reason' => 'Salah input rombel saat penetapan awal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('member_class_histories', [
            'member_id' => $member->id,
            'class_id' => $class2->id,
        ]);
        $this->assertEquals(1, MemberClassHistory::where('member_id', $member->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.class_corrected', 'subject_id' => $member->id]);
    }

    public function test_can_remove_class_assignment()
    {
        $user = $this->createKetuaUmum();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        $class = ClassRoom::create([
            'academic_year_id' => $activeYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        $division = Division::factory()->create();
        $member = Member::factory()->create(['division_id' => $division->id]);
        
        MemberClassHistory::create([
            'member_id' => $member->id,
            'class_id' => $class->id,
        ]);

        // Tanpa alasan: ditolak, riwayat tetap ada.
        $this->actingAs($user)->delete(route('member-classes.remove', $member))->assertSessionHasErrors('correction_reason');
        $this->assertDatabaseHas('member_class_histories', ['member_id' => $member->id, 'class_id' => $class->id]);

        $response = $this->actingAs($user)->delete(route('member-classes.remove', $member), [
            'correction_reason' => 'Anggota ternyata salah dimasukkan ke kelas ini',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('member_class_histories', [
            'member_id' => $member->id,
            'class_id' => $class->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.class_removed', 'subject_id' => $member->id]);
    }

    public function test_can_filter_members_by_class_status()
    {
        $user = $this->createKetuaUmum();
        $activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        $class = ClassRoom::create([
            'academic_year_id' => $activeYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        $division = Division::factory()->create();
        $memberWithClass = Member::factory()->create(['division_id' => $division->id]);
        $memberWithoutClass = Member::factory()->create(['division_id' => $division->id]);
        
        MemberClassHistory::create([
            'member_id' => $memberWithClass->id,
            'class_id' => $class->id,
        ]);

        $response = $this->actingAs($user)->get(route('member-classes.index', ['has_class' => 'yes']));
        $response->assertStatus(200);
        $response->assertSee($memberWithClass->full_name);

        $response = $this->actingAs($user)->get(route('member-classes.index', ['has_class' => 'no']));
        $response->assertStatus(200);
        $response->assertSee($memberWithoutClass->full_name);
    }
}
