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

class ClassPromotionTest extends TestCase
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

    public function test_ketua_umum_can_access_class_promotion()
    {
        $user = $this->createKetuaUmum();

        $response = $this->actingAs($user)->get(route('class-promotion.index'));

        $response->assertStatus(200);
    }

    public function test_sekretaris_can_access_class_promotion()
    {
        $user = $this->createSekretaris();

        $response = $this->actingAs($user)->get(route('class-promotion.index'));

        $response->assertStatus(200);
    }

    public function test_ketua_divisi_cannot_access_class_promotion()
    {
        $user = $this->createKetuaDivisi();

        $response = $this->actingAs($user)->get(route('class-promotion.index'));

        $response->assertStatus(403);
    }

    public function test_can_preview_class_promotion()
    {
        $user = $this->createKetuaUmum();
        $sourceYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'archived',
        ]);
        $targetYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('class-promotion.preview'), [
            'source_year_id' => $sourceYear->id,
            'target_year_id' => $targetYear->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_source_and_target_year_must_be_different()
    {
        $user = $this->createKetuaUmum();
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('class-promotion.preview'), [
            'source_year_id' => $year->id,
            'target_year_id' => $year->id,
        ]);

        $response->assertSessionHasErrors('target_year_id');
    }

    public function test_can_process_class_promotion()
    {
        $user = $this->createKetuaUmum();
        $sourceYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'archived',
        ]);
        $targetYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        
        $sourceClass = ClassRoom::create([
            'academic_year_id' => $sourceYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        
        $targetClass = ClassRoom::create([
            'academic_year_id' => $targetYear->id,
            'grade_level' => 'XI',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'XI RPL 1',
            'status' => 'active',
        ]);
        
        $division = Division::factory()->create();
        $member = Member::factory()->create(['division_id' => $division->id, 'status' => 'active']);
        
        MemberClassHistory::create([
            'member_id' => $member->id,
            'class_id' => $sourceClass->id,
        ]);

        $response = $this->actingAs($user)->post(route('class-promotion.process'), [
            'source_year_id' => $sourceYear->id,
            'target_year_id' => $targetYear->id,
            'mappings' => [
                $sourceClass->id => $targetClass->id,
            ],
        ]);

        $response->assertRedirect(route('class-promotion.index'));
        $this->assertDatabaseHas('member_class_histories', [
            'member_id' => $member->id,
            'class_id' => $targetClass->id,
        ]);
        // Old history should be preserved
        $this->assertDatabaseHas('member_class_histories', [
            'member_id' => $member->id,
            'class_id' => $sourceClass->id,
        ]);
    }

    public function test_inactive_members_are_skipped_in_promotion()
    {
        $user = $this->createKetuaUmum();
        $sourceYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'archived',
        ]);
        $targetYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        
        $sourceClass = ClassRoom::create([
            'academic_year_id' => $sourceYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        
        $targetClass = ClassRoom::create([
            'academic_year_id' => $targetYear->id,
            'grade_level' => 'XI',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'XI RPL 1',
            'status' => 'active',
        ]);
        
        $division = Division::factory()->create();
        $activeMember = Member::factory()->create(['division_id' => $division->id, 'status' => 'active']);
        $inactiveMember = Member::factory()->create(['division_id' => $division->id, 'status' => 'inactive']);
        
        MemberClassHistory::create(['member_id' => $activeMember->id, 'class_id' => $sourceClass->id]);
        MemberClassHistory::create(['member_id' => $inactiveMember->id, 'class_id' => $sourceClass->id]);

        $this->actingAs($user)->post(route('class-promotion.process'), [
            'source_year_id' => $sourceYear->id,
            'target_year_id' => $targetYear->id,
            'mappings' => [$sourceClass->id => $targetClass->id],
        ]);

        $this->assertDatabaseHas('member_class_histories', [
            'member_id' => $activeMember->id,
            'class_id' => $targetClass->id,
        ]);
        $this->assertDatabaseMissing('member_class_histories', [
            'member_id' => $inactiveMember->id,
            'class_id' => $targetClass->id,
        ]);
    }

    public function test_target_classes_must_be_from_target_year()
    {
        $user = $this->createKetuaUmum();
        $sourceYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'archived',
        ]);
        $targetYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        
        $sourceClass = ClassRoom::create([
            'academic_year_id' => $sourceYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        
        $wrongClass = ClassRoom::create([
            'academic_year_id' => $sourceYear->id, // Wrong year
            'grade_level' => 'XI',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'XI RPL 1',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('class-promotion.process'), [
            'source_year_id' => $sourceYear->id,
            'target_year_id' => $targetYear->id,
            'mappings' => [$sourceClass->id => $wrongClass->id],
        ]);

        $response->assertSessionHasErrors('mappings');
    }

    public function test_old_class_history_is_preserved()
    {
        $user = $this->createKetuaUmum();
        $sourceYear = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'archived',
        ]);
        $targetYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
        ]);
        
        $sourceClass = ClassRoom::create([
            'academic_year_id' => $sourceYear->id,
            'grade_level' => 'X',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'X RPL 1',
            'status' => 'active',
        ]);
        
        $targetClass = ClassRoom::create([
            'academic_year_id' => $targetYear->id,
            'grade_level' => 'XI',
            'major' => 'RPL',
            'class_number' => 1,
            'name' => 'XI RPL 1',
            'status' => 'active',
        ]);
        
        $division = Division::factory()->create();
        $member = Member::factory()->create(['division_id' => $division->id, 'status' => 'active']);
        
        MemberClassHistory::create([
            'member_id' => $member->id,
            'class_id' => $sourceClass->id,
        ]);

        $this->actingAs($user)->post(route('class-promotion.process'), [
            'source_year_id' => $sourceYear->id,
            'target_year_id' => $targetYear->id,
            'mappings' => [$sourceClass->id => $targetClass->id],
        ]);

        $this->assertEquals(2, MemberClassHistory::where('member_id', $member->id)->count());
    }
}
