<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use App\Models\IncidentalActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IncidentalActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DivisionSeeder::class);
        $this->seed(\Database\Seeders\PositionSeeder::class);
    }

    public function test_authenticated_member_can_view_activities_list()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('activities.index'))
            ->assertStatus(200);
    }

    public function test_ketua_umum_can_create_activity()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $division = Division::first();

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Test',
                'description' => 'Deskripsi test',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(1)->format('Y-m-d'),
                'division_id' => $division->id,
                'attendance_mode' => 'per_event',
                'status' => 'upcoming',
            ])
            ->assertRedirect(route('activities.index'));

        $this->assertDatabaseHas('incidental_activities', [
            'name' => 'Kegiatan Test',
            'division_id' => $division->id,
        ]);
    }

    public function test_sekretaris_can_create_activity()
    {
        $sekretarisPosition = Position::where('name', 'Sekretaris')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($sekretarisPosition->id);

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Sekretaris',
                'start_date' => now()->format('Y-m-d'),
                'attendance_mode' => 'per_event',
                'status' => 'upcoming',
            ])
            ->assertRedirect(route('activities.index'));
    }

    public function test_ketua_divisi_can_create_activity_for_own_division()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division = Division::first();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'status' => 'active'
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Divisi',
                'start_date' => now()->format('Y-m-d'),
                'division_id' => $division->id,
                'attendance_mode' => 'per_event',
                'status' => 'upcoming',
            ])
            ->assertRedirect(route('activities.index'));
    }

    public function test_ketua_divisi_cannot_create_activity_for_other_division()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();
        
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
            'status' => 'active'
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Divisi Lain',
                'start_date' => now()->format('Y-m-d'),
                'division_id' => $division2->id,
                'attendance_mode' => 'per_event',
                'status' => 'upcoming',
            ])
            ->assertStatus(403);
    }

    public function test_regular_member_cannot_create_activity()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Test',
                'start_date' => now()->format('Y-m-d'),
                'attendance_mode' => 'per_event',
                'status' => 'upcoming',
            ])
            ->assertStatus(403);
    }

    public function test_member_can_view_activity_detail()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $activity = IncidentalActivity::factory()->create([
            'division_id' => null,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('activities.show', $activity))
            ->assertStatus(200)
            ->assertSee($activity->name);
    }

    public function test_ketua_divisi_cannot_view_other_division_activity()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();
        
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
            'status' => 'active'
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $otherUser = User::factory()->create();
        $activity = IncidentalActivity::factory()->create([
            'division_id' => $division2->id,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->get(route('activities.show', $activity))
            ->assertStatus(403);
    }

    public function test_ketua_umum_can_edit_any_activity()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $otherUser = User::factory()->create();
        $activity = IncidentalActivity::factory()->create([
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->put(route('activities.update', $activity), [
                'name' => 'Updated Name',
                'start_date' => $activity->start_date->format('Y-m-d'),
                'attendance_mode' => $activity->attendance_mode,
                'status' => $activity->status,
            ])
            ->assertRedirect(route('activities.show', $activity));

        $this->assertDatabaseHas('incidental_activities', [
            'id' => $activity->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_ketua_divisi_cannot_edit_other_division_activity()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();
        
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
            'status' => 'active'
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $otherUser = User::factory()->create();
        $activity = IncidentalActivity::factory()->create([
            'division_id' => $division2->id,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->put(route('activities.update', $activity), [
                'name' => 'Hacked Name',
                'start_date' => $activity->start_date->format('Y-m-d'),
                'attendance_mode' => $activity->attendance_mode,
                'status' => $activity->status,
            ])
            ->assertStatus(403);
    }

    public function test_only_ketua_umum_and_sekretaris_can_archive_activity()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $activity = IncidentalActivity::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('activities.archive', $activity))
            ->assertRedirect(route('activities.index'));

        $this->assertDatabaseHas('incidental_activities', [
            'id' => $activity->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_ketua_divisi_cannot_archive_activity()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division = Division::first();
        
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'status' => 'active'
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $activity = IncidentalActivity::factory()->create([
            'division_id' => $division->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('activities.archive', $activity))
            ->assertStatus(403);
    }

    public function test_activity_filter_by_division_works()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $division = Division::first();
        IncidentalActivity::factory()->create([
            'name' => 'Activity Division',
            'division_id' => $division->id,
            'created_by' => $user->id,
        ]);
        IncidentalActivity::factory()->create([
            'name' => 'Activity All IPMI',
            'division_id' => null,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('activities.index', ['division_id' => $division->id]))
            ->assertStatus(200)
            ->assertSee('Activity Division')
            ->assertDontSee('Activity All IPMI');
    }

    public function test_validation_requires_name_and_dates()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'description' => 'Test',
                'attendance_mode' => 'per_event',
            ])
            ->assertSessionHasErrors(['name', 'start_date', 'status']);
    }
}
